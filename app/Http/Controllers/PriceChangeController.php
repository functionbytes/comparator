<?php

namespace App\Http\Controllers;

use App\Models\PriceChange;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class PriceChangeController extends Controller
{
    public function __construct()
    {
        // Fuerza el guard web para esta ruta
        // $this->middleware(['web', 'auth:web']);
    }

    /**
     * Registra un cambio de precio.
     *
     * Espera un POST JSON con:
     * {
     *   referencia: string (req),
     *   precio_con_iva: number|string (nullable),
     *   nuevo_precio_con_iva: number|string (req),
     *   source: "manual"|"sugerencia" (optional),
     *   contexto: object (optional)
     * }
     */
    public function store(Request $request): JsonResponse
    {
        // Paranoia: asegúrate del guard correcto
        // if (! Auth::guard('web')->check()) {
        //     return response()->json([
        //         'ok'      => false,
        //         'message' => 'Unauthenticated (web guard).',
        //     ], 401);
        // }

        try {
            $data = $request->validate([
                'referencia'           => ['required', 'string', 'max:150'],
                'precio_con_iva'       => ['nullable'], // lo normalizamos abajo
                'nuevo_precio_con_iva' => ['required'], // lo normalizamos abajo
                'source'               => ['nullable', 'in:manual,sugerencia'],
                'contexto'             => ['nullable', 'array'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Datos inválidos.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // Normaliza números: admite "104.99" o "104,99" o números puros
        $precioConIvaRaw       = $data['precio_con_iva']       ?? null;
        $nuevoPrecioConIvaRaw  = $data['nuevo_precio_con_iva'] ?? null;

        $precioConIva      = $this->toDecimalOrNull($precioConIvaRaw);
        $nuevoPrecioConIva = $this->toDecimalOrNull($nuevoPrecioConIvaRaw);

        if ($nuevoPrecioConIva === null) {
            return response()->json([
                'ok'      => false,
                'message' => 'El campo nuevo_precio_con_iva es obligatorio y debe ser numérico.',
            ], 422);
        }

        try {
            $change = PriceChange::create([
                'referencia'           => $data['referencia'],
                'precio_con_iva'       => $precioConIva,
                'nuevo_precio_con_iva' => $nuevoPrecioConIva,
                'source'               => $data['source'] ?? 'manual',
                'user_id'              => Auth::guard('web')->id(),
                'ip'                   => $request->ip(),
                'contexto'             => $data['contexto'] ?? null,
            ]);

            return response()->json([
                'ok' => true,
                'id' => $change->id,
            ], 201);
        } catch (Throwable $e) {
            // Log opcional:
            // \Log::error('PriceChange store error', ['ex' => $e]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo registrar el cambio.',
            ], 500);
        }
    }

    /**
     * Convierte un valor a decimal string compatible con DECIMAL(12,4) o null.
     * Admite string con coma o punto, y números nativos.
     */
    private function toDecimalOrNull(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Si viene como número, formatea a 4 decimales
        if (is_numeric($value)) {
            return number_format((float) $value, 4, '.', '');
        }

        // Si viene como string: quita espacios, cambia coma por punto y valida
        if (is_string($value)) {
            $v = trim($value);
            // eliminar separador de miles europeo "1.234,56" -> "1234,56"
            // (si quieres ser más agresivo con miles, puedes añadir más reglas)
            $v = str_replace([' ', "\u{00A0}"], '', $v); // incluye non-breaking space
            // cambia coma decimal por punto
            $v = str_replace(',', '.', $v);

            // ahora debe ser numérico
            if (is_numeric($v)) {
                return number_format((float) $v, 4, '.', '');
            }
        }

        return null;
    }
}
