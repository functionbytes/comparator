@extends('layouts.administratives')

@section('content')
<div class="container-fluid">
    @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Importar Proveedores (CSV)</h5>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('providers.import') }}" enctype="multipart/form-data" id="formImport">
                @csrf
                <div class="form-group">
                    <label for="csv">Archivo CSV</label>
                    <input type="file" class="form-control-file @error('csv') is-invalid @enderror" id="csv" name="csv" accept=".csv,.txt" required>
                    @error('csv') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    <small class="form-text text-muted">
                        Columnas: <code>COD.PROV</code> → <b>code</b>, <code>NOMBRE</code> → <b>title</b>. Delimitador ; (detectado automáticamente).
                    </small>
                </div>

                <div class="mb-2">
                    <button type="button" class="btn btn-secondary" id="btnValidate">Validar</button>
                    <button type="submit" class="btn btn-primary">Importar</button>
                </div>

                <div id="validationBox" class="alert d-none" role="alert"></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h5 class="mb-0">Resumen: Proveedores agrupados por título</h5>
            <span class="badge badge-info ml-2">{{ number_format($grouped->sum('total')) }} registros</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Título</th>
                            <th class="text-right">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grouped as $g)
                        <tr>
                            <td>{{ $g->title }}</td>
                            <td class="text-right">{{ $g->total }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted">Sin proveedores cargados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>

// const res = await fetch('{{ route('providers.validate') }}', { method: 'POST', body: fd });
// const data = await res.json();

// let html = `
//   <b>Delimitador:</b> ${data.delimiter} ·
//   <b>Filas:</b> ${data.rows}<br>
//   <b>Duplicados en CSV (descartados):</b> ${data.duplicates_in_file}<br>
//   <b>Inválidos por code (bloqueantes):</b> ${data.invalid_blocking_by_code}<br>
//   <b>Válidos tras deduplicar archivo:</b> ${data.valid_after_file_dedup}<br>
//   <b>Se insertarían hoy (tras filtrar existentes en BD):</b> ${data.would_insert_after_db}
//   <hr><small>${data.notes}</small>
// `;
// box.innerHTML = html;
// box.className = 'alert alert-warning';

document.getElementById('btnValidate').addEventListener('click', async function(){
    const inp = document.getElementById('csv');
    if (!inp.files.length) { alert('Selecciona un CSV primero.'); return; }

    const fd = new FormData();
    fd.append('csv', inp.files[0]);
    fd.append('_token', '{{ csrf_token() }}');

    const box = document.getElementById('validationBox');
    box.className = 'alert alert-info';
    box.textContent = 'Validando…';

    try {
        const res = await fetch('{{ route('providers.validate') }}', { method: 'POST', body: fd });
        if (!res.ok) throw new Error('Error HTTP ' + res.status);
        const data = await res.json();

        let html = `
            <b>Delimitador:</b> ${data.delimiter} ·
            <b>Filas:</b> ${data.rows} ·
            <b>Con incidencias:</b> ${data.invalid} <br>
            <b>Mapeo:</b> code = <code>${data.mapped.code}</code>, title = <code>${data.mapped.title}</code><br>
            <b>Duplicados de code (en CSV):</b> ${data.dup_codes?.length || 0}
        `;
        if (data.invalid > 0) {
            html += `<hr><small>Mostrando primeras 200 incidencias. Las filas con <code>CODE_EMPTY</code>, <code>TITLE_EMPTY</code>, <code>_TOO_LONG</code> o <code>_CONTROL_CHARS</code> serán descartadas. <br>Las etiquetas <code>WARN_*</code> son solo avisos (se importan).</small>`;
        }
        box.innerHTML = html;
        box.className = 'alert alert-warning';
    } catch (e) {
        box.textContent = 'Error validando: ' + e.message;
        box.className = 'alert alert-danger';
    }
});
</script>
@endpush