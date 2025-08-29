@extends('layouts.administratives')

@section('content')
<div class="widget-content searchable-container list">
    {{-- Tarjeta con el formulario de filtros (migración del PHP puro) --}}
    <div class="card card-body">
        <script>
            window.lista_nombres = @json($listaNombres);
        </script>

        <form method="get"
              action="/administrative/reports/view"
              id="form_filtros"
              @if($buscando) class="generar_informe" @endif>

            {{-- Parámetros calculados por JS --}}
            <input type="hidden" name="sport" id="sport" value="">
            <input type="hidden" name="status" id="status" value="">

            <div class="list-filter">

                {{-- Filtros guardados --}}
                <div class="list-filter-row">
                    <i class="fa fa-plus fa_variable2"></i>
                    <b class="fa_variable2">Filtros guardados</b><br />
                    <div class="filtros_guardados invisible row">
                        @foreach($filtrosGuardados as $f)
                            <div class="col-xs-offset-1 col-xs-3">
                                <i class="fa fa-times cursor-pointer remove_filter"
                                   id_filtro="{{ $f['id'] }}"
                                   style="border-right:1px solid black;padding-right:10px;margin-right:10px;"></i>
                                <a href="{{ $f['href'] }}" title="{{ $f['tooltip'] }}">{{ $f['name'] }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>

                <hr />

                {{-- Deportes (sin name, solo data-key para JS) --}}
                <div class="list-filter-row">
                    <b>Deportes</b>:
                    <table width="100%" style="margin-top:5px;text-align:center;" class="sports_table">
                        <tr>
                            @php $pos = 0; @endphp
                            @foreach($datos['sports'] as $nombre => $sport)
                                <td width="33%">
                                    <input type="checkbox"
                                           class="show_sport"
                                           id="show_{{ $nombre }}"
                                           data-sport-key="{{ $nombre }}"
                                           @checked($sport) />
                                    <label for="show_{{ $nombre }}">{{ strtoupper($nombre) }}</label>
                                </td>
                                @php $pos++; @endphp
                                @if($pos % 3 === 0)
                                    </tr><tr>
                                @endif
                            @endforeach
                        </tr>
                    </table>
                </div>

                <hr />

                {{-- Estado en gestión (sin name; se consolidan en hidden #status) --}}
                <div class="list-filter-row">
                    <b>Estado en gestión</b>
                    <table width="100%" style="margin-top:5px;text-align:center;">
                        <tr>
                            @foreach($datos['status'] as $nombre => $mostrar)
                                <td width="25%">
                                    <label class="switch d-inline-flex align-items-center gap-2">
                                        <input type="checkbox"
                                               class="data_status"
                                               value="{{ $nombre }}"
                                               @checked($mostrar)>
                                        <span class="slider round"></span>
                                        <span>{{ $nombre }}</span>
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    </table>
                </div>

                <hr />

                {{-- País / idioma (al cambiar recarga el índice para refrescar competidores) --}}
                <div class="list-filter-row">
                    <b>País a comparar</b><br />
                    <div class="mt-2" style="text-align:center;">
                        @foreach(['es'=>'España','pt'=>'Portugal','fr'=>'Francia','de'=>'Alemania','it'=>'Italia'] as $iso => $label)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input rb_idioma"
                                       type="radio"
                                       name="iso_code"
                                       id="rb_{{ $iso }}"
                                       value="{{ $iso }}"
                                       @checked($datos['idioma'][$iso] ?? false)>
                                <label class="form-check-label" for="rb_{{ $iso }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <hr />

                {{-- Etiqueta --}}
                <div class="list-filter-row">
                    <b>Etiqueta</b><br />
                    <select id="select_etiqueta" name="etiqueta" class="form-control data" style="width:95%;display:inline;">
                        <option value="">Seleccione etiqueta</option>
                        @foreach($datos['etiquetas'] as $nombreEtiqueta)
                            <option value="{{ $nombreEtiqueta }}" @selected(($datos['etiquetasSelect'] ?? '' )===$nombreEtiqueta)>
                                {{ $nombreEtiqueta }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <hr />

                {{-- Proveedor --}}
                <div class="list-filter-row">
                    <b>Proveedor</b><br />
                    <select id="select_proveedor" name="proveedor" class="form-control data" style="width:95%;display:inline;">
                        <option value="">Seleccione proveedor</option>
                        @foreach($datos['proveedor'] as $p)
                            <option value="{{ $p->nombre }}" @selected(($datos['proveedorSelect'] ?? '' )===$p->nombre)>
                                {{ $p->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <hr />

                {{-- Competidores (UI: listas duales, cargados por ISO desde BD) --}}
                <div class="list-filter-row">
                    <div class="filtros_adicionales row" style="margin-top:10px;text-align:center;">
                        <table width="100%" style="margin-top:10px;text-align:center;">
                            <tr>
                                <td colspan="3" style="padding-bottom:15px;">
                                    <hr />
                                    <big>
                                        <b>Competidores Que Incluir</b>
                                        <i class="fa fa-info-circle" style="color:blue;"
                                           title="Si no hay ninguno seleccionado, se considerarán todos los competidores disponibles para el país."></i>
                                    </big><br />
                                </td>
                            </tr>

                            <tr>
                                {{-- Disponibles --}}
                                <td width="45%">
                                    <b>Disponibles</b><br />
                                    <select multiple id="competidores_disponibles" size="10" style="width:15em;">
                                        @foreach($datos['competidores'] as $c)
                                            <option value="{{ $c->id }}" class="opt_disp"
                                                @if(!(int)$c->available) disabled @endif>
                                                {{ $c->nombre }}@if(!(int)$c->available) (no disponible)@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Botonera mover --}}
                                <td width="10%" valign="middle">
                                    <button type="button" id="compet_incl_all" class="btn btn-secondary btn-xs btn_comp" title="Incluir todos">
                                        <i class="fa fa-angle-double-right"></i>
                                    </button><br />
                                    <button type="button" id="compet_incl_sel" class="btn btn-secondary btn-xs btn_comp" title="Incluir seleccionados">
                                        <i class="fa fa-angle-right"></i>
                                    </button><br />
                                    <hr />
                                    <button type="button" id="compet_quit_sel" class="btn btn-secondary btn-xs btn_comp" title="Quitar seleccionados">
                                        <i class="fa fa-angle-left"></i>
                                    </button><br />
                                    <button type="button" id="compet_quit_all" class="btn btn-secondary btn-xs btn_comp" title="Quitar todos">
                                        <i class="fa fa-angle-double-left"></i>
                                    </button><br />
                                </td>

                                {{-- Seleccionados --}}
                                <td width="45%">
                                    <b>Seleccionados</b><br />
                                    <select multiple id="competidores_seleccionados" size="10" style="width:15em;">
                                        {{-- Vacío al cargar; se va llenando con la botonera --}}
                                    </select>
                                </td>
                            </tr>
                        </table>

                        {{-- Si quieres enviar seleccionados por GET, descomenta: --}}
                        {{-- <input type="hidden" id="competidores_ids" name="competidores_ids" value=""> --}}
                    </div>
                </div>

                <hr />

                {{-- Botón GENERAR INFORME --}}
                <div class="list-filter-row text-center">
                    <div id="botones_informe" class="d-flex justify-content-center gap-3 my-3">
                        <button type="submit"
                                id="submit_form"
                                class="btn btn-success"
                                @disabled($buscando)>
                            GENERAR INFORME
                        </button>
                    </div>
                </div>

            </div> {{-- /.list-filter --}}
        </form>
    </div> {{-- /.card card-body --}}
</div> {{-- /.widget-content --}}

{{-- JS para armar parámetros y UX de competidores/idioma --}}
<script>
(function() {
    const SPORT_MAP = {
        golf: 3,
        caza: 4,
        pesca: 5,
        hipica: 6,
        buceo: 7,
        nautica: 8,
        esqui: 9,
        padel: 10,
        aventura: 11
    };

    const form = document.getElementById('form_filtros');
    const sportH = document.getElementById('sport');
    const statusH = document.getElementById('status');

    // ====== Submit: SPORT y STATUS ======
    form.addEventListener('submit', function() {
        // SPORT (CSV)
        const selectedSports = Array.from(form.querySelectorAll('.show_sport:checked'))
            .map(cb => SPORT_MAP[(cb.getAttribute('data-sport-key') || '').toLowerCase()])
            .filter(Boolean);
        sportH.value = selectedSports.join(',');

        // STATUS (con guiones)
        const selectedStatus = Array.from(form.querySelectorAll('.data_status:checked'))
            .map(cb => cb.value);
        statusH.value = selectedStatus.join('-');

        // Si decides enviar competidores seleccionados:
        // const out = Array.from(document.querySelectorAll('#competidores_seleccionados option'))
        //     .map(o => o.value);
        // const hidden = document.getElementById('competidores_ids');
        // if (hidden) hidden.value = out.join(',');
    });

    // ====== Cambio de idioma: recarga índice con ?idioma=xx para refrescar competidores ======
    document.querySelectorAll('.rb_idioma').forEach(rb => {
        rb.addEventListener('change', function() {
            if (!this.checked) return;
            const iso = this.value;
            const base = "{{ url()->current() }}";
            // Si quisieras preservar otros parámetros del índice, añade aquí query extras.
            window.location.href = base + '?idioma=' + encodeURIComponent(iso);
        });
    });

    // ====== Botonera mover competidores (solo UI) ======
    const disp = document.getElementById('competidores_disponibles');
    const sel  = document.getElementById('competidores_seleccionados');

    function moveSelected(from, to) {
        Array.from(from.selectedOptions).forEach(opt => {
            to.appendChild(opt);
            opt.selected = false;
        });
    }
    function moveAll(from, to) {
        Array.from(from.options).forEach(opt => {
            to.appendChild(opt);
            opt.selected = false;
        });
    }

    document.getElementById('compet_incl_all').addEventListener('click', () => moveAll(disp, sel));
    document.getElementById('compet_incl_sel').addEventListener('click', () => moveSelected(disp, sel));
    document.getElementById('compet_quit_sel').addEventListener('click', () => moveSelected(sel, disp));
    document.getElementById('compet_quit_all').addEventListener('click', () => moveAll(sel, disp));
})();
</script>
@endsection
