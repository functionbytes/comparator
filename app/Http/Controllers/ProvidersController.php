<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProvidersController extends Controller
{
    public function index(Request $request)
    {
        $grouped = Provider::query()
            ->select('title', DB::raw('COUNT(*) as total'))
            ->groupBy('title')
            ->orderByDesc('total')
            ->orderBy('title')
            ->get();

        return view('administratives.providers.index', compact('grouped'));
    }

    public function validateCsv(Request $request)
    {
        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        [$rows, $map, $delimiter] = $this->readCsv($request->file('csv')->getRealPath());

        $issues = [];
        $seenCodesExact = [];         // para duplicados en CSV (code EXACTO, sin trim)
        $validCandidates = [];        // filas válidas (solo chequeos bloqueantes)
        $dupInFile = 0;
        $invalidCode = 0;

        foreach ($rows as $r) {
            $rowNum   = $r['_line'];
            $codeRaw  = (string)($r[$map['code']] ?? '');
            $titleRaw = (string)($r[$map['title']] ?? '');

            $codeTrim  = trim($codeRaw); // solo para detectar vacío
            $rowIssues = [];

            // BLOQUEANTES SOLO PARA CODE
            if ($codeTrim === '') $rowIssues[] = 'CODE_EMPTY';
            if (mb_strlen($codeRaw) > 255) $rowIssues[] = 'CODE_TOO_LONG';
            if ($this->hasControlChars($codeRaw)) $rowIssues[] = 'CODE_CONTROL_CHARS';

            // Avisos NO bloqueantes (se importan igual si pasan lo de arriba)
            if ($codeTrim !== '' && preg_match('/^\s|\s$/u', $codeRaw)) $rowIssues[] = 'WARN_CODE_LEADING_TRAILING_SPACES';
            if ($codeTrim !== '' && preg_match('/\s/u', $codeRaw))      $rowIssues[] = 'WARN_CODE_HAS_WHITESPACE';

            // Duplicados en archivo (mismo code EXACTO)
            if ($codeTrim !== '') {
                if (isset($seenCodesExact[$codeRaw])) {
                    $dupInFile++;
                    $rowIssues[] = 'DUP_CODE_IN_FILE';
                } else {
                    $seenCodesExact[$codeRaw] = true;
                }
            }

            if (!empty($rowIssues)) {
                $issues[] = [
                    'row'   => $rowNum,
                    'code'  => $codeRaw,
                    'title' => $titleRaw,
                    'issues' => implode(',', $rowIssues),
                ];
            }

            // Candidato “insertable” si NO tiene bloqueantes y NO es duplicado en archivo
            $hasBlocking = in_array('CODE_EMPTY', $rowIssues, true)
                || in_array('CODE_TOO_LONG', $rowIssues, true)
                || in_array('CODE_CONTROL_CHARS', $rowIssues, true)
                || in_array('DUP_CODE_IN_FILE', $rowIssues, true);

            if (!$hasBlocking) {
                $validCandidates[] = $codeRaw;
            } else {
                // contar inválidos por code (bloqueantes)
                if (
                    in_array('CODE_EMPTY', $rowIssues, true)
                    || in_array('CODE_TOO_LONG', $rowIssues, true)
                    || in_array('CODE_CONTROL_CHARS', $rowIssues, true)
                ) {
                    $invalidCode++;
                }
            }
        }

        // ¿Cuántos se insertarían hoy? (descartando existentes en BD)
        $uniqueCandidates = array_values(array_unique($validCandidates, SORT_STRING));
        $exists = [];
        foreach (array_chunk($uniqueCandidates, 1000) as $chunk) {
            Provider::query()->whereIn('code', $chunk)->pluck('code')
                ->each(function ($c) use (&$exists) {
                    $exists[$c] = true;
                });
        }
        $wouldInsert = 0;
        foreach ($uniqueCandidates as $c) {
            if (!isset($exists[$c])) $wouldInsert++;
        }

        return response()->json([
            'delimiter'                 => $delimiter,
            'mapped'                    => $map,
            'rows'                      => count($rows),
            'invalid_blocking_by_code'  => $invalidCode,
            'duplicates_in_file'        => $dupInFile,
            'valid_after_file_dedup'    => count($uniqueCandidates),
            'would_insert_after_db'     => $wouldInsert,  // estimación de inserciones reales
            'issues_preview'            => array_slice($issues, 0, 200),
            'notes'                     => 'Se insertará la PRIMERA aparición de cada code en el CSV. Si el code ya existe en BD, se descarta. title se normaliza y puede truncarse a 255.',
        ]);
    }


    /** Importa: inserta SOLO si no existe ese code; NO actualiza title si existe */
    public function import(Request $request)
    {
        ini_set('max_execution_time', 1200);
        ini_set('memory_limit', '4096M');

        $request->validate([
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        [$rows, $map] = $this->readCsv($request->file('csv')->getRealPath());

        $clean = [];
        $seenInFile = [];

        foreach ($rows as $r) {

            $codeRaw  = (string)($r[$map['code']] ?? '');
            $titleRaw = (string)($r[$map['title']] ?? '');



            $codeTrim  = trim($codeRaw);

            // BLOQUEANTES mínimos para BD:
            if ($codeTrim === '') continue;                 // code no puede ser “solo espacios”
            if (mb_strlen($codeRaw) > 255) continue;        // respeta longitud de columna
            if ($this->hasControlChars($codeRaw)) continue; // evita caracteres de control en code

            // Duplicados dentro del archivo (EXACTOS). Conservamos la PRIMERA aparición.
            if (isset($seenInFile[$codeRaw])) continue;
            $seenInFile[$codeRaw] = true;

            // Normalización suave del title (puede ser null/empty). Truncar 255.
            $titleForDb = $this->normalizeTitle($titleRaw);
            if (mb_strlen($titleForDb) > 255) {
                $titleForDb = mb_substr($titleForDb, 0, 255);
            }

            $clean[] = ['code' => $codeRaw, 'title' => $titleForDb];
        }

        if (empty($clean)) {
            return back()->with('error', 'No hay filas válidas para importar.');
        }

        // Saltar los que ya existen en BD (EXACT MATCH del code tal cual)
        $fileCodes = array_column($clean, 'code');
        $exists = [];
        foreach (array_chunk($fileCodes, 1000) as $chunk) {

            Provider::query()->whereIn('code', $chunk)->pluck('code')
                ->each(function ($c) use (&$exists) {
                    $exists[$c] = true;
                });
        }

        $toInsert = [];
        $now = now();
        $seenInFile = []; // seguimos descartando duplicados dentro del CSV (primera aparición)

        foreach ($clean as $row) {
            if (isset($seenInFile[$row['code']])) continue;
            $seenInFile[$row['code']] = true;

            $toInsert[] = [
                'uid'        => (string) Str::uuid(),
                'title'      => $row['title'],
                'code'       => $row['code'],
                'available'  => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

        }

        $insertedTotal = 0;
        DB::beginTransaction();
        try {
            foreach (array_chunk($toInsert, 1000) as $chunk) {
                $insertedTotal += DB::table('providers')->insertOrIgnore($chunk);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Error importando proveedores', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Error al insertar: ' . $e->getMessage());
        }
        $descartados = count($toInsert) - $insertedTotal;
        return redirect()->route('providers.index')
            ->with('status', 'Insertados: ' . number_format($insertedTotal) . ' · Descartados por duplicado (BD/CSV): ' . number_format($descartados));
    }


    // ----------------- Helpers -----------------

    /**
     * Lee CSV detectando delimitador y mapeando columnas.
     * Devuelve [rows, map, delimiter].
     * NOTE: No hacemos trim de los valores; sólo quitamos BOM al inicio si aparece.
     */
    private function readCsv(string $path): array
    {
        $raw  = file_get_contents($path);
        $text = @mb_convert_encoding($raw, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

        // Detectar delimitador en primeras líneas no vacías
        $lines  = preg_split("/\r\n|\n|\r/", $text);
        $sample = array_slice(array_filter($lines, fn($l) => trim($l) !== ''), 0, 5);
        $cands  = [",", ";", "\t", "|"];
        $scores = [];
        foreach ($cands as $d) $scores[$d] = array_sum(array_map(fn($l) => substr_count($l, $d), $sample));
        arsort($scores);
        $delimiter = key($scores) ?? ';';

        $rows = [];
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $text);
        rewind($fh);

        $i = 0;
        $headers = null;
        while (($r = fgetcsv($fh, 0, $delimiter)) !== false) {
            $i++;
            // Quitar BOM solo si aparece al inicio del primer campo de la primera fila
            if ($i === 1 && isset($r[0])) {
                $r[0] = preg_replace('/^\xEF\xBB\xBF/u', '', (string)$r[0]);
            }

            // Si la fila entera está vacía (todas las celdas vacías o espacios), la saltamos
            $allEmpty = true;
            foreach ($r as $cell) {
                if (trim((string)$cell) !== '') {
                    $allEmpty = false;
                    break;
                }
            }
            if ($allEmpty) continue;

            if ($headers === null) {
                $headers = array_map(fn($h) => (string)$h, $r);
                $map = $this->mapColumns($headers);

                // Si no parecen cabeceras, asumimos [code, title]
                $looksHeader = ($map['code'] !== null || $map['title'] !== null);
                if (!$looksHeader && count($r) >= 2) {
                    $headers = ['code', 'title'];
                    // registramos esta misma fila como datos
                    $rows[] = ['code' => $r[0] ?? '', 'title' => $r[1] ?? '', '_line' => $i];
                }
                continue;
            }

            $r['_line'] = $i;
            $rows[] = $r;
        }
        fclose($fh);

        $map = $this->mapColumns($headers);
        if ($map['code'] === null && $map['title'] === null && !empty($rows)) {
            $map = ['code' => 0, 'title' => 1];
        }

        return [$rows, $map, $delimiter];
    }

    private function mapColumns(array $headers): array
    {
        $norm = array_map(function ($h) {
            $h = mb_strtolower((string)$h, 'UTF-8');
            $h = str_replace([' ', '-'], ['', '_'], $h);
            return $h;
        }, $headers);

        $codeAliases  = ['code', 'cod', 'codprov', 'cod_prov', 'cod.prov', 'provider_id', 'id', 'codigo', 'código', 'id_proveedor', 'idproveedor'];
        $titleAliases = ['title', 'nombre', 'name', 'proveedor', 'provider', 'descripcion', 'descripción'];

        $codeIdx = null;
        $titleIdx = null;
        foreach ($norm as $i => $h) {
            foreach ($codeAliases as $a)  if (str_contains($h, $a)) {
                $codeIdx  = $codeIdx  ?? $i;
            }
            foreach ($titleAliases as $a) if (str_contains($h, $a)) {
                $titleIdx = $titleIdx ?? $i;
            }
        }
        return ['code' => $codeIdx, 'title' => $titleIdx];
    }

    private function hasControlChars(string $s): bool
    {
        return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $s) === 1;
    }

    /** Normalización suave para title: trim extremos + colapsar espacios + quitar chars de control */
    private function normalizeTitle(string $title): string
    {
        // quitar caracteres de control
        $title = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $title);
        // recortar extremos
        $title = trim($title);
        // colapsar espacios internos múltiples a uno
        $title = preg_replace('/\s+/u', ' ', $title);
        return $title;
    }
}
