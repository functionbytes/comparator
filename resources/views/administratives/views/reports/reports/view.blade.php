@extends('layouts.administratives')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    /* base */
    .ag-cell.cell-num {
        font-weight: normal;
    }

    /* efectos */
    .cell-bold {
        font-weight: 700;
    }

    .cell-strike {
        text-decoration: line-through;
    }

    /* texto */
    .cell-text-white {
        color: #ffffff;
    }

    .cell-text-red {
        color: #ff0000;
    }

    /* fondos (Excel) */
    .bg-white {
        background-color: #ffffff;
    }

    .bg-gray-200 {
        background-color: #dddddd;
    }

    /* NUMERO_PROPUESTO */
    .bg-gray-250 {
        background-color: #dedede;
    }

    /* NUMERO_MODIFICABLE */
    .bg-gray-300 {
        background-color: #b8b8b8;
    }

    /* *_OLD */
    .bg-peach {
        background-color: #facec4;
    }

    /* NUMERO_SUGERIDO */
    .bg-green {
        background-color: #6ac13c;
    }

    /* MIN_PRICE */
    .bg-green-light {
        background-color: #a5d98a;
    }

    /* code >=20 */
    .bg-coral {
        background-color: #f08080;
    }

    /* code >=8 */
    .bg-red-soft {
        background-color: #f78080;
    }

    /* ALERTA */
    .bg-cyan {
        background-color: #00ddff;
    }

    /* MIN_PRICE_SIN_STOCK */
    .bg-red-strong {
        background-color: #ff0000;
    }

    /* code >=24 */
    .bg-blue {
        background-color: #0000ff;
    }

    /* code >=22 */
    .bg-yellow {
        background-color: #ffff00;
    }

    /* OCULTO */

    /* Todos los precios con enlace en negro, sin importar el tema */
    .price-link,
    .price-link:visited {
        color: #000 !important;
        text-decoration: none;
    }

    .price-link:hover {
        text-decoration: underline;
    }

    .ag-cell.click-to-apply {
        cursor: pointer;
    }

    .col-modal.hidden {
        display: none;
    }

    .col-modal {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .35);
        display: grid;
        place-items: center;
        z-index: 999;
    }

    .col-modal__dialog {
        width: min(900px, 92vw);
        max-height: 85vh;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, .25);
        display: flex;
        flex-direction: column;
    }

    .col-modal__header,
    .col-modal__footer {
        padding: 12px 16px;
        border-bottom: 1px solid #eee;
    }

    .col-modal__footer {
        border-top: 1px solid #eee;
        border-bottom: 0;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    .col-modal__body {
        padding: 12px 16px;
        overflow: auto;
    }

    .col-modal__close {
        background: none;
        border: 0;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
    }

    .primary {
        background: #0f62fe;
        color: #fff;
        border: 0;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
    }

    .muted {
        color: #666;
    }

    .col-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        grid-template-columns: repeat(2, minmax(300px, 1fr));
        gap: 6px 16px;
    }

    .col-item {
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid #ddd;
        padding: 8px;
        border-radius: 6px;
        background: #fafafa;
    }

    .col-item[draggable="true"] {
        cursor: grab;
    }

    .col-item.dragging {
        opacity: .5;
    }

    .col-item .handle {
        font-family: ui-monospace, monospace;
        cursor: grab;
        user-select: none;
    }

    .col-item .label {
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bg-gray-250 {
        background-color: #dedede;
    }

    .link-black,
    .link-black:visited {
        color: #000;
        text-decoration: none;
    }

    .link-black:hover {
        text-decoration: underline;
    }

    .ag-row.row-selected {
        background: #fff7cc !important;
        /* amarillo suave */
    }

    .ag-cell.cell-copyable {
        user-select: text !important;
        /* deja seleccionar con ratón */
        cursor: text !important;
    }
</style>
<button id="btnColumnManager" type="button">Columnas</button>
<button onclick="onBtnExport()">Download CSV export file</button>
<div id="colModal" class="col-modal hidden" aria-hidden="true">
    <div class="col-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="colModalTitle">
        <div class="col-modal__header">
            <h3 id="colModalTitle">Configurar columnas</h3>
            <button id="btnCloseCols" class="col-modal__close" aria-label="Cerrar">×</button>
        </div>
        <div class="col-modal__body">
            <p class="muted">Arrastra para reordenar. Marca para mostrar / desmarca para ocultar.</p>
            <ul id="colList" class="col-list"></ul>
        </div>
        <div class="col-modal__footer">
            <button id="btnResetCols" type="button">Restablecer</button>
            <button id="btnApplyCols" type="button" class="primary">Visualizar</button>
        </div>
    </div>
</div>

<div id="myGrid" style="height: 90%"></div>




@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/ag-grid-community@34.0.2/dist/ag-grid-community.min.js"></script>

<script>
    const PRICE_LOG_URL = "{{ route('precio-cambio.store', [], false) }}";
    const CSRF_TOKEN = "{{ csrf_token() }}";

    const myTheme = agGrid.themeQuartz.withParams({
        sideBarBackgroundColor: "#08f3",
        sideButtonBarBackgroundColor: "#fff6",
        sideButtonBarTopPadding: 20,
        sideButtonSelectedUnderlineColor: "orange",
        sideButtonTextColor: "#0009",
        sideButtonHoverBackgroundColor: "#fffa",
        sideButtonSelectedBackgroundColor: "#08f1",
        sideButtonHoverTextColor: "#000c",
        sideButtonSelectedTextColor: "#000e",
        sideButtonSelectedBorder: false,
    });

    const result = @json($result);


    // ——— helpers ———
    const esCurrency = new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR'
    });
    const fmtMoney = p => (typeof p.value === 'number' ? esCurrency.format(p.value) : (p.value ?? ''));
    const moneyFmt = new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR'
    });
    const fmtPct = p => (typeof p.value === 'number' ?
        `${(p.value * 100).toFixed(2).replace('.', ',')}%` :
        (p.value ?? ''));

    const parseNumberEs = (params) => {
        if (params.newValue == null || params.newValue === '') return null;
        let s = String(params.newValue).trim().replace(/\s/g, '');
        s = s.replace(/\./g, '').replace(',', '.'); // "1.234,56" -> "1234.56"
        const n = parseFloat(s);
        return Number.isNaN(n) ? null : n;
    };
    const toNumber = v => (v == null || v === '' ? 0 : Number(v));
    const r2 = (x) => Math.round(x * 100) / 100;
    const r4 = (x) => Math.round(x * 10000) / 10000;

    // 🔒 Campos protegidos por *field*, *colId* o *headerName* (minúsculas)
    // const PROTECTED_FIELD_CANDIDATES = [
    //     'referencia'
    // ];
    const PROTECTED_FIELD_CANDIDATES = []; // el modal puede mostrar/ocultar 'referencia'

    let PROTECTED_COL_IDS = new Set();

    let selectedRowKey = null;

    const norm = (s) => (s ?? '').toString().toLowerCase().trim();

    // ===== Vista compacta por fila (ocultar columnas vacías de la fila clicada) =====
    const ALWAYS_VISIBLE_FIELDS = new Set([
        // Identificación y contexto
        'referencia',
        'nombre',
        'matchs',
        'estado_gestion',
        'precio_fijo',
        'etiqueta',
        'visible_web_mas_portes',
        'externo',
        'proveedor_por_defecto',

        // Precios base y comparativas
        'precio_con_iva',
        'sugerencia_precio',
        'sugerencia_margen_eur',
        'sugerencia_margen_pct',
        'mejor_precio_con_iva',
        'diferencia_pct',
        'precio_costo',
        'precio_sin_iva',
        'margen_eur',
        'margen_pct',

        // Nuevos (bloque importante, no ocultar)
        'nuevo_precio_con_iva',
        'nuevo_precio_sin_iva',
        'nuevo_margen_eur',
        'nuevo_margen_pct',

        // Cálculos nuevos derivados de coste
        'precio_costo_nuevo',
        'margen_nuevo_eur',
        'margen_nuevo_pct',
    ]);

    // Columnas que NO deben disparar la compactación al hacer click en la fila
    // const EXCLUDED_ROW_CLICK_FIELDS = new Set(['referencia']);
    const EXCLUDED_ROW_CLICK_FIELDS = new Set(['referencia', 'nuevo_precio_con_iva', 'precio_con_iva', 'sugerencia_precio']);

    // 🔒 Columnas que maneja EXCLUSIVAMENTE el botón "Columnas"
    const COLUMN_MODAL_WHITELIST = new Set([
        "nombre",
        "matchs",
        "estado_gestion",
        "precio_fijo",
        "visible_web_mas_portes",
        "precio_con_iva",
        "sugerencia_precio",
        "sugerencia_margen_eur",
        "sugerencia_margen_pct",
        "mejor_precio_con_iva",
        "diferencia_pct",
        "precio_costo",
        "precio_sin_iva",
        "margen_eur",
        "margen_pct",
        "precio_costo_nuevo",
        "margen_nuevo_eur",
        "margen_nuevo_pct",
        "proveedor_por_defecto",
        // "amazon_con_portes",
        // "amazon_con_portes_vendedor",
        // "amazon_sin_portes",
        // "amazon_sin_portes_vendedor",
        // "decathlon",
        // "decathlon_vendedor",
        // "google_con_portes",
        // "google_con_portes_vendedor",
        // "google_sin_portes",
        // "google_sin_portes_vendedor",
        "nuevo_precio_con_iva",
        "nuevo_precio_sin_iva",
        "nuevo_margen_eur",
        "nuevo_margen_pct",
    ]);


    let lastCompact = {
        rowId: null, // id de la fila a la que se aplicó la vista compacta
        columnState: null // estado de columnas antes de compactar
    };

    let IN_COMPACT_APPLY = false;

    function linkMoneyRenderer(params) {
        const urlField = params.colDef?.cellRendererParams?.urlField;
        const url = urlField ? params.data?.[urlField] : null;
        const val = params.value;

        if (val == null || val === '' || isNaN(Number(val))) return '';
        const txt = moneyFmt.format(Number(val));

        if (url) {
            return `<a class="link-black" href="${url}" target="_blank" rel="noopener">${txt}</a>`;
        }
        return `<span class="link-black">${txt}</span>`;
    }

    function linkTextRenderer(params) {
        const urlField = params.colDef?.cellRendererParams?.urlField;
        const url = urlField ? params.data?.[urlField] : null;
        const txt = (params.value ?? '').toString().trim();
        if (!txt) return '';
        if (url) {
            return `<a class="link-black" href="${url}" target="_blank" rel="noopener">${txt}</a>`;
        }
        return `<span class="link-black">${txt}</span>`;
    }


    const columnDefs = [{
            field: "referencia",
            minWidth: 170,
            editable: false,
            pinned: "left",
            cellClass: 'cell-copyable',
            suppressNavigable: false, // permite foco en la celda
        },
        {
            field: "nombre",
            minWidth: 260,
            editable: false,
            flex: 1
        },
        {
            field: "matchs",
            width: 90,
            editable: false,
            type: "numericColumn"
        },
        {
            field: "estado_gestion",
            width: 150,
            editable: false
        },
        {
            field: "precio_fijo",
            width: 120,
            editable: false
        },
        // {
        //     field: "etiqueta",
        //     width: 110,
        //     editable: false
        // },
        {
            field: "visible_web_mas_portes",
            width: 180,
            editable: false
        },
        // {
        //     field: "externo",
        //     width: 160,
        //     editable: false
        // },

        {
            field: "precio_con_iva",
            width: 150,
            editable: false,
            valueFormatter: fmtMoney,
            tooltipField: "precio_con_iva_url",
            cellRenderer: linkMoneyRenderer,
            cellRendererParams: {
                urlField: "precio_con_iva_url"
            },
            tooltipValueGetter: p => {
                const base = calcTooltipGetter(p); // nuestras líneas
                const url = p.data?.precio_con_iva_url;
                const extra = url ? `URL: ${url}` : '';
                return [base, extra].filter(Boolean).join('\n'); // evita tooltipField
            }
        },
        {
            field: "sugerencia_precio",
            width: 170,
            editable: false,
            valueFormatter: fmtMoney,
            cellClass: params => (params.value ? 'click-to-apply' : null),
            // tooltipValueGetter: p => (p.value ? 'Click para aplicar como “Nuevo precio (con IVA)”' : ''),
        },
        {
            field: "sugerencia_margen_eur",
            width: 170,
            editable: false,
            valueFormatter: fmtMoney
        },
        {
            field: "sugerencia_margen_pct",
            width: 170,
            editable: false,
            valueFormatter: fmtPct
        },

        {
            field: "mejor_precio_con_iva",
            width: 180,
            editable: false,
            valueFormatter: fmtMoney
        },
        {
            field: "diferencia_pct",
            width: 140,
            editable: false,
            valueFormatter: fmtPct
        },

        {
            field: "precio_costo",
            width: 140,
            editable: false,
            valueFormatter: fmtMoney
        },
        {
            field: "precio_sin_iva",
            width: 150,
            editable: false,
            valueFormatter: fmtMoney
        },
        {
            field: "margen_eur",
            width: 130,
            editable: false,
            valueFormatter: fmtMoney
        },
        {
            field: "margen_pct",
            width: 130,
            editable: false,
            valueFormatter: fmtPct
        },

        {
            field: "precio_costo_nuevo",
            width: 170,
            editable: false,
            valueFormatter: fmtMoney
        },
        {
            field: "margen_nuevo_eur",
            width: 170,
            editable: false,
            valueFormatter: fmtMoney
        },
        {
            field: "margen_nuevo_pct",
            width: 160,
            editable: false,
            valueFormatter: fmtPct
        },

        {
            field: "proveedor_por_defecto",
            width: 190,
            editable: false
        },

        // --- Bloque Amazon (si el JSON trae estas claves, se mostrarán) ---
        {
            field: "amazon_con_portes",
            width: 170,
            editable: false,
            valueFormatter: fmtMoney,
            tooltipField: "amazon_con_portes_url",
            cellRenderer: linkMoneyRenderer,
            cellRendererParams: {
                urlField: "amazon_con_portes_url"
            },
        },
        {
            field: "amazon_con_portes_vendedor",
            width: 220,
            editable: false
        },
        {
            field: "amazon_sin_portes",
            width: 170,
            editable: false,
            valueFormatter: fmtMoney,
            tooltipField: "amazon_sin_portes_url",
            cellRenderer: linkMoneyRenderer,
            cellRendererParams: {
                urlField: "amazon_sin_portes_url"
            },
        },
        {
            field: "amazon_sin_portes_vendedor",
            width: 220,
            editable: false
        },

        // --- Bloque Decathlon ---
        {
            field: "decathlon",
            width: 150,
            editable: false,
            valueFormatter: fmtMoney,
            tooltipField: "decathlon_url",
            cellRenderer: linkMoneyRenderer,
            cellRendererParams: {
                urlField: "decathlon_url"
            },
        },
        {
            field: "decathlon_vendedor",
            width: 200,
            editable: false
        },

        // --- Bloque Google ---
        {
            field: "google_con_portes",
            width: 170,
            editable: false,
            valueFormatter: fmtMoney,
            tooltipField: "google_con_portes_url",
            cellRenderer: linkMoneyRenderer,
            cellRendererParams: {
                urlField: "google_con_portes_url"
            },
        },
        {
            field: "google_con_portes_vendedor",
            width: 220,
            editable: false
        },
        {
            field: "google_sin_portes",
            width: 170,
            editable: false,
            valueFormatter: fmtMoney,
            tooltipField: "google_sin_portes_url",
            cellRenderer: linkMoneyRenderer,
            cellRendererParams: {
                urlField: "google_sin_portes_url"
            },
        },
        {
            field: "google_sin_portes_vendedor",
            width: 220,
            editable: false
        },

        // --- Campos de "nuevo ..." (tras Google) ---
        {
            field: "nuevo_precio_con_iva",
            headerName: "Nuevo precio (con IVA)",
            width: 190,
            editable: true,
            valueFormatter: fmtMoney,
            valueParser: (params) => parseNumberEs({
                newValue: params.newValue
            }), // 👈
            cellClassRules: {
                'bg-gray-250': () => true,
            },
        },
        // derivados (no editables)
        {
            field: "nuevo_precio_sin_iva",
            width: 190,
            editable: false,
            valueFormatter: fmtMoney,
            cellDataType: 'number'
        },
        {
            field: "nuevo_margen_eur",
            width: 170,
            editable: false,
            valueFormatter: fmtMoney,
            cellDataType: 'number'
        },
        {
            field: "nuevo_margen_pct",
            width: 160,
            editable: false,
            valueFormatter: fmtPct,
            cellDataType: 'number'
        },

        // --- Competidores genéricos dinámicos (blackrecon, armeriamateo, pecheur, etc.) ---
        // Añade aquí más defs si conoces las claves; si vienen en el JSON, AG Grid las puede tomar también de forma dinámica.
    ];

    let gridApi;

    // ——— grid ———
    const gridOptions = {
        theme: myTheme,
        columnDefs,
        defaultColDef: {
            editable: true,
            filter: true,
            floatingFilter: true,
            cellClass: styleCellClass, // <-- aquí la magia
            tooltipValueGetter: calcTooltipGetter, // 👈
        },
        tooltipShowDelay: 0, // 👈 sin retraso
        tooltipHideDelay: 15000, // opcional
        // pagination: true,
        // paginationPageSize: 10,
        // paginationPageSizeSelector: [10, 25, 50],
        // A) clave de fila y clase de selección
        getRowId: params => params.data?.referencia ?? String(params.data?.id ?? Math.random()),
        rowClassRules: {
            'row-selected': params => getRowKey(params.node) === selectedRowKey,
        },

        // aplica fórmulas cuando cambie "nuevo_precio_con_iva"
        onCellValueChanged: (p) => {
            if (p.colDef.field !== 'nuevo_precio_con_iva') return;

            recalcNuevoPrecio(p.node);

            const oldV = toNumber(p.oldValue);
            const newV = toNumber(p.newValue);
            if (newV > 0 && newV !== oldV) {
                const key = `chg:${p.data.referencia}`;
                debounceByKey(key, () => {
                    const payload = buildPriceChangePayload(p.data, 'manual');
                    logPriceChange(payload);
                }, 400);
            }
        },
        onCellClicked: (p) => {
            const field = p.colDef?.field || p.column?.getColId?.();

            if (!field) return;

            // 👉 NUEVO: si la celda contiene un link, no hacer nada
            if (p.event?.target?.closest('a')) {
                return;
            }

            // --- Mantén el comportamiento especial de sugerencia_precio ---
            if (field === 'sugerencia_precio') {
                const val = toNumber(p.value);
                if (val > 0) {
                    p.node.setDataValue('nuevo_precio_con_iva', val);
                    recalcNuevoPrecio(p.node);

                    // registra como "sugerencia" (evita duplicado con debounce)
                    const key = `sug:${p.data.referencia}`;
                    debounceByKey(key, () => {
                        const payload = buildPriceChangePayload(p.data, 'sugerencia');
                        logPriceChange(payload);
                    }, 200);
                }
                return; // 👈 no seguimos con compactación
            }

            // --- Excluir columnas del click-compact (p.ej. referencia) ---
            if (EXCLUDED_ROW_CLICK_FIELDS.has(field)) {
                return; // permitir copiar/seleccionar sin activar compactación
            }

            // --- Compactación por fila (toggle) ---
            const clickedKey = getRowKey(p.node);

            if (lastCompact.rowId && lastCompact.rowId === clickedKey) {
                restorePreviousColumns();
                return;
            }
            if (lastCompact.rowId && lastCompact.rowId !== clickedKey) {
                restorePreviousColumns();
            }
            lastCompact.rowId = clickedKey;
            applyCompactForRow(p.node);
        },
    };

    document.getElementById('btnColumnManager')?.addEventListener('click', openColumnManager);
    document.getElementById('btnCloseCols')?.addEventListener('click', closeColumnManager);
    document.getElementById('btnApplyCols')?.addEventListener('click', onApplyColumns);
    document.getElementById('btnResetCols')?.addEventListener('click', onResetColumns);

    document.addEventListener("DOMContentLoaded", function() {
        const gridDiv = document.querySelector("#myGrid");
        gridApi = agGrid.createGrid(gridDiv, gridOptions);
        gridApi.setGridOption("rowData", result);

        insertDynamicCompetitors(gridApi, result);
        buildCompactEligibleFields();
        captureInitialColumnState();

        // 🔔 Si el usuario cambia columnas (visibilidad/orden/tamaño/pin) fuera de la compactación,
        // invalida la baseline para que el próximo click use el estado nuevo.
        // const invalidateIfNotCompact = () => {
        //     if (IN_COMPACT_APPLY) return;
        //     lastCompact.rowId = null;
        //     lastCompact.columnState = null;
        // };

        // gridApi.addEventListener('columnVisible', invalidateIfNotCompact);
        // gridApi.addEventListener('columnMoved', invalidateIfNotCompact);
        // gridApi.addEventListener('columnPinned', invalidateIfNotCompact);
        // gridApi.addEventListener('columnResized', invalidateIfNotCompact);
    });


    // setup the grid after the page has finished loading
    // document.addEventListener("DOMContentLoaded", function() {
    //     const gridDiv = document.querySelector("#myGrid");
    //     gridApi = agGrid.createGrid(gridDiv, gridOptions);
    //     gridApi.setGridOption("rowData", result);

    //     // Inserta precio+vendedor dinámicos de competidores genéricos
    //     insertDynamicCompetitors(gridApi, result);
    // });


    function onBtnExport() {
        gridApi.exportDataAsCsv();
    }
    // 1) Detecta claves base de competidores genéricos (no amazon/google/decathlon)
    function extractGenericBaseKeys(rows) {
        const baseKeys = new Set();
        rows.forEach(r => {
            Object.keys(r || {}).forEach(k => {
                if (/_(vendedor|url)$/.test(k)) return; // ignorar suffix auxiliares
                if (/^(amazon|google|decathlon)_/i.test(k)) return; // familias especiales

                // Consideramos "competidor" si existe algún *_vendedor o *_vendedor_url hermano
                if ((k + '_vendedor') in r || (k + '_vendedor_url') in r) {
                    baseKeys.add(k);
                }
            });
        });
        return Array.from(baseKeys).sort((a, b) => a.localeCompare(b, 'es'));
    }

    // 2) Columnas dinámicas por competidor: precio con link + vendedor con link
    function makeGenericPriceCol(baseKey) {
        return {
            field: baseKey, // ej: "acerosdehispania_es"
            headerName: baseKey,
            width: 140,
            editable: false,
            valueFormatter: null, // lo formatea el renderer
            cellRenderer: linkMoneyRenderer,
            cellRendererParams: {
                urlField: baseKey + '_vendedor_url' // <<< del backend
            },
            // si pintas estilos por número:
            cellClass: p => styleCellClass(p, baseKey + '_style'),
            filter: true,
            floatingFilter: true,
        };
    }

    function hasVendorTextField(rowData, baseKey) {
        return rowData.some(r => (baseKey + '_vendedor') in r && String(r[baseKey + '_vendedor']).trim() !== '');
    }

    function makeGenericVendorCol(baseKey) {
        return {
            field: baseKey + '_vendedor',
            headerName: baseKey + ' (vendedor)',
            width: 200,
            editable: false,
            tooltipField: baseKey + '_vendedor_url',
            cellRenderer: linkTextRenderer,
            cellRendererParams: {
                urlField: baseKey + '_vendedor_url'
            },
            filter: true,
            floatingFilter: true,
        };
    }

    // 3) Inserta dinámicamente entre proveedor_por_defecto y amazon_con_portes
    function insertDynamicCompetitors(gridApi, rowData) {
        const baseKeys = extractGenericBaseKeys(rowData);
        if (baseKeys.length === 0) return;

        const current = gridApi.getColumnDefs() ?? [];
        const existing = new Set(current.map(c => c.field));

        const newCols = [];
        baseKeys.forEach(k => {
            if (!existing.has(k)) newCols.push(makeGenericPriceCol(k)); // precio con link
            const vendField = k + '_vendedor';
            if (hasVendorTextField(rowData, k) && !existing.has(vendField)) {
                newCols.push(makeGenericVendorCol(k));
            }
        });
        if (newCols.length === 0) return;

        const idxProv = current.findIndex(c => c.field === 'proveedor_por_defecto');
        const idxAmazon = current.findIndex(c => c.field === 'amazon_con_portes');
        let insertAt = (idxProv >= 0) ? idxProv + 1 : current.length;
        if (idxAmazon >= 0 && idxAmazon < insertAt) insertAt = idxAmazon;

        const nextDefs = current.slice();
        nextDefs.splice(insertAt, 0, ...newCols);
        gridApi.setGridOption('columnDefs', nextDefs);
    }


    // tokens (p.ej. "NUMERO_MIN_PRICE") -> clases
    function styleTokenToClasses(tok) {
        switch (tok) {
            case 'CABECERA':
                return 'cell-bold bg-black cell-text-white'; // si lo usas
            case 'NUMERO':
                return '';
            case 'TEXTO':
                return '';
            case 'NUMERO_PROPUESTO':
                return 'bg-gray-200';
            case 'NUMERO_SUGERIDO':
                return 'bg-peach cell-bold';
            case 'NUMERO_OLD':
                return 'bg-gray-300';
            case 'NUMERO_MIN_PRICE':
                return 'bg-green';
            case 'NUMERO_SIN_STOCK':
                return 'cell-strike';
            case 'NUMERO_ALERTA':
                return 'bg-red-soft cell-bold';
            case 'NUMERO_MIN_PRICE_SIN_STOCK':
                return 'bg-cyan';
            case 'NUMERO_PERC':
                return '';
            case 'NUMERO_PERC_OLD':
                return 'bg-gray-300';
            case 'NUMERO_PERC_NEW':
                return 'bg-gray-300 cell-bold cell-text-red';
            case 'NUMERO_MODIFICABLE':
                return 'bg-gray-250';
            case 'NUMERO_OCULTO':
                return 'bg-yellow'; // (en Excel tb ponía texto amarillo)
            default:
                return '';
        }
    }

    // special_number (int) -> clases
    function styleCodeToClasses(code) {
        let c = Number(code) || 0;
        const classes = ['cell-num'];

        // >=24 rojo fuerte + texto blanco
        if (c >= 24) {
            classes.push('bg-red-strong', 'cell-text-white');
            c -= 24;
        }
        // >=22 azul + texto blanco
        else if (c >= 22) {
            // classes.push('bg-blue', 'cell-text-white');
            c -= 22;
        }

        // >=20 verde claro
        if (c >= 20) {
            classes.push('bg-green-light');
            c -= 20;
        }
        // >=8 coral
        else if (c >= 8) {
            classes.push('bg-coral');
            c -= 8;
        }
        // >=4 verde
        else if (c >= 4) {
            classes.push('bg-green');
            c -= 4;
        }

        // >=2 en Excel “no bold” (por defecto ya no es bold) => no añadimos nada, solo consumimos
        if (c >= 2) {
            c -= 2;
        }

        // >=1 tachado
        if (c >= 1) {
            classes.push('cell-strike');
            c -= 1;
        }

        return classes.join(' ');
    }

    // Único punto de entrada: mira <field>_style
    function styleCellClass(params) {
        const field = params.colDef.field;
        const styleVal = params.data ? params.data[field + '_style'] : undefined;

        if (typeof styleVal === 'number') {
            return styleCodeToClasses(styleVal);
        }
        if (typeof styleVal === 'string' && styleVal) {
            return styleTokenToClasses(styleVal);
        }
        return null;
    }


    let initialColumnState = null; // para "Restablecer"

    // abre/cierra modal
    function openColumnManager() {
        resolveProtectedColIds(); // 👈 ahora soporta varias
        const modal = document.getElementById('colModal');
        buildColumnList();
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeColumnManager() {
        const modal = document.getElementById('colModal');
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
    }

    // construye la lista desde el estado + defs
    function buildColumnList() {
        const ul = document.getElementById('colList');
        ul.innerHTML = '';

        const defs = gridApi.getColumnDefs() || [];
        const state = gridApi.getColumnState() || [];

        const headerById = {};
        defs.forEach(def => {
            const colId = def.colId || def.field;
            if (!colId) return;
            headerById[colId] = def.headerName || def.field || colId;
        });

        // Solo columnas whitelisted en el orden actual:
        state.forEach(s => {
            const colId = s.colId;
            if (!COLUMN_MODAL_WHITELIST.has(colId)) return; // 👈 filtro clave
            // (ya no comprobamos protegidas aquí)
            const li = document.createElement('li');
            li.className = 'col-item';
            li.draggable = true;
            li.dataset.colId = colId;

            const handle = document.createElement('span');
            handle.className = 'handle';
            handle.textContent = '↕';
            li.appendChild(handle);

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'visible-toggle';
            cb.dataset.colId = colId;
            cb.checked = !s.hide;
            li.appendChild(cb);

            const span = document.createElement('span');
            span.className = 'label';
            span.textContent = headerById[colId] || colId;
            li.appendChild(span);

            ul.appendChild(li);
        });

        // defs sin state (caso raro), también solo whitelist
        defs.forEach(def => {
            const colId = def.colId || def.field;
            if (!colId) return;
            if (!COLUMN_MODAL_WHITELIST.has(colId)) return; // 👈 filtro
            if (state.some(s => s.colId === colId)) return;

            const li = document.createElement('li');
            li.className = 'col-item';
            li.draggable = true;
            li.dataset.colId = colId;

            const handle = document.createElement('span');
            handle.className = 'handle';
            handle.textContent = '↕';
            li.appendChild(handle);

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'visible-toggle';
            cb.dataset.colId = colId;
            cb.checked = true;
            li.appendChild(cb);

            const span = document.createElement('span');
            span.className = 'label';
            span.textContent = headerById[colId] || colId;
            li.appendChild(span);

            ul.appendChild(li);
        });

        wireDragAndDrop(ul);
    }


    // drag & drop simple
    function wireDragAndDrop(listEl) {
        let dragEl = null;
        listEl.querySelectorAll('.col-item').forEach(li => {
            li.addEventListener('dragstart', e => {
                dragEl = li;
                li.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            li.addEventListener('dragend', () => {
                if (dragEl) dragEl.classList.remove('dragging');
                dragEl = null;
            });
        });

        listEl.addEventListener('dragover', e => {
            e.preventDefault();
            const afterEl = getDragAfterElement(listEl, e.clientY);
            const dragging = listEl.querySelector('.dragging');
            if (!dragging) return;
            if (afterEl == null) {
                listEl.appendChild(dragging);
            } else {
                listEl.insertBefore(dragging, afterEl);
            }
        });

        function getDragAfterElement(container, y) {
            const els = [...container.querySelectorAll('.col-item:not(.dragging)')];
            return els.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return {
                        offset,
                        element: child
                    };
                } else {
                    return closest;
                }
            }, {
                offset: Number.NEGATIVE_INFINITY
            }).element;
        }
    }

    // aplica selección/orden (tu función)
    function onBtExcludeMedalColumns() {

        // 🔴 clave: si había vista compacta, volvemos a baseline
        collapseCompactBeforeExternalChange();

        const ul = document.getElementById('colList');
        const items = [...ul.querySelectorAll('.col-item')];

        // Orden ELEGIDO en el modal (solo whitelist)
        const wlOrderedIds = items.map(li => li.dataset.colId);

        // Visibilidad elegida
        const wlVisibility = {};
        for (const li of items) {
            const colId = li.dataset.colId;
            const checked = li.querySelector('.visible-toggle').checked;
            wlVisibility[colId] = !checked; // hide = !checked
        }



        // Aplica sin mover no-whitelist y sin empujar whitelist al inicio
        applyWhitelistState(wlOrderedIds, wlVisibility);

        lastCompact.rowId = null;
        lastCompact.columnState = null;
    }





    // aplicar y cerrar
    function onApplyColumns() {
        onBtExcludeMedalColumns();
        closeColumnManager();
    }

    // restablecer
    function onResetColumns() {
        // 🔴 clave: si había vista compacta, volvemos a baseline
        collapseCompactBeforeExternalChange();

        const snapshot = initialColumnState || gridApi.getColumnState();

        // Orden inicial del whitelist (según snapshot)
        const wlOrderedIds = snapshot
            .map(s => s.colId)
            .filter(colId => COLUMN_MODAL_WHITELIST.has(colId));

        // Visibilidad inicial del whitelist
        const wlVisibility = {};
        snapshot.forEach(s => {
            if (COLUMN_MODAL_WHITELIST.has(s.colId)) {
                wlVisibility[s.colId] = !!s.hide;
            }
        });

        applyWhitelistState(wlOrderedIds, wlVisibility);

        lastCompact.rowId = null;
        lastCompact.columnState = null;
        closeColumnManager();
    }

    // capturar estado inicial
    function captureInitialColumnState() {
        initialColumnState = gridApi.getColumnState();
    }

    function isProtectedColId(colId) {
        return PROTECTED_COL_IDS.has(colId);
    }

    function resolveProtectedColIds() {
        PROTECTED_COL_IDS = new Set();

        const defs = gridApi.getColumnDefs() || [];
        const state = gridApi.getColumnState() || [];

        // map colId -> def (para lookup rápido por colId de state)
        const defById = new Map();
        defs.forEach(def => {
            const id = def.colId || def.field;
            if (id) defById.set(id, def);
        });

        // 1) intenta por defs (field, colId, headerName)
        PROTECTED_FIELD_CANDIDATES.forEach(target => {
            const t = norm(target);
            let match = defs.find(def =>
                norm(def.field) === t ||
                norm(def.colId) === t ||
                norm(def.headerName) === t
            );
            if (match) {
                const id = match.colId || match.field;
                if (id && state.some(s => s.colId === id)) {
                    PROTECTED_COL_IDS.add(id);
                }
            }
        });

        // 2) fallback: por colId directo en el state si alguien ya coincide
        PROTECTED_FIELD_CANDIDATES.forEach(target => {
            const t = norm(target);
            const byState = state.find(s => norm(s.colId) === t);
            if (byState) PROTECTED_COL_IDS.add(byState.colId);
        });
    }

    function resolveProtectedColId() {
        // 1) intenta hallar en defs por field/colId o headerName
        const defs = gridApi.getColumnDefs() || [];
        let guessed = null;

        for (const def of defs) {
            const defId = def.colId || def.field;
            const header = (def.headerName || '').toLowerCase().trim();
            const field = (def.field || '').toLowerCase().trim();

            if (PROTECTED_FIELD_CANDIDATES.includes(field)) {
                guessed = defId;
                break;
            }
            if (PROTECTED_FIELD_CANDIDATES.includes(defId?.toLowerCase?.())) {
                guessed = defId;
                break;
            }
            if (header === 'referencia') {
                guessed = defId;
                break;
            }
        }

        // 2) valida contra el estado real
        const state = gridApi.getColumnState() || [];
        const exists = state.some(s => s.colId === guessed);
        PROTECTED_COL_ID = exists ? guessed : null;

        // fallback: coge la primera del estado cuyo header o field parezca “referencia”
        if (!PROTECTED_COL_ID) {
            const byId = new Map(defs.map(d => [d.colId || d.field, d]));
            for (const s of state) {
                const d = byId.get(s.colId);
                const header = (d?.headerName || '').toLowerCase().trim();
                const field = (d?.field || '').toLowerCase().trim();
                if (header === 'referencia' || PROTECTED_FIELD_CANDIDATES.includes(field)) {
                    PROTECTED_COL_ID = s.colId;
                    break;
                }
            }
        }

        // último recurso: si no encontramos nada, deja null (no romperemos)
    }

    function recalcNuevoPrecio(node) {
        const data = node.data || {};
        const vat = (typeof data.vat === 'number' ? data.vat : 0.21);
        const ivaFactor = 1 + vat;

        const pvp = toNumber(data.nuevo_precio_con_iva);
        if (pvp > 0) {
            const sinIva = r2(pvp / ivaFactor);
            const costoN = toNumber(data.precio_costo_nuevo);
            const margenE = r2(sinIva - costoN);
            const margenP = sinIva > 0 ? r4(margenE / sinIva) : null;

            node.setDataValue('nuevo_precio_sin_iva', sinIva);
            node.setDataValue('nuevo_margen_eur', margenE);
            node.setDataValue('nuevo_margen_pct', margenP);
        } else {
            node.setDataValue('nuevo_precio_sin_iva', '');
            node.setDataValue('nuevo_margen_eur', '');
            node.setDataValue('nuevo_margen_pct', '');
        }
    }

    // —— helper para evitar duplicados por clicks/ediciones consecutivas ——
    const debounceMap = new Map();

    function debounceByKey(key, fn, wait = 500) {
        clearTimeout(debounceMap.get(key));
        const t = setTimeout(fn, wait);
        debounceMap.set(key, t);
    }

    // —— POST con fetch ——
    async function logPriceChange(payload) {
        try {
            const res = await fetch(PRICE_LOG_URL, {
                method: 'POST',
                credentials: 'same-origin', // 👈 manda cookie de sesión
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                console.error('Error registrando cambio de precio', res, err);
            }
        } catch (e) {
            console.error('Error de red registrando cambio de precio', e);
        }
    }


    // —— función para armar el payload desde la fila ——
    function buildPriceChangePayload(rowData, source = 'manual') {
        return {
            referencia: rowData.referencia,
            countryiso: rowData.countryiso,
            precio_con_iva: toNumber(rowData.precio_con_iva),
            nuevo_precio_con_iva: toNumber(rowData.nuevo_precio_con_iva),
            source,
            // opcional: guarda contexto útil para auditoría
            contexto: {
                nombre: rowData.nombre ?? null,
                proveedor_por_defecto: rowData.proveedor_por_defecto ?? null,
                mejor_precio_con_iva: toNumber(rowData.mejor_precio_con_iva ?? null),
                diferencia_pct: rowData.diferencia_pct ?? null,
            }
        };
    }

    // Determina si el valor de una celda se considera "vacío" para este comportamiento
    function isEmptyCellValue(v) {
        if (v === null || v === undefined) return true;
        if (typeof v === 'string') return v.trim() === '';
        // Los números 0 cuentan como dato
        if (typeof v === 'number') return Number.isNaN(v);
        return false;
    }

    // Restaura el estado de columnas previo a la última compactación (si existe)
    function restorePreviousColumns() {
        if (lastCompact.columnState) {
            IN_COMPACT_APPLY = true; // 👈 cambios internos
            try {
                gridApi.applyColumnState({
                    state: lastCompact.columnState,
                    applyOrder: true
                });
            } finally {
                IN_COMPACT_APPLY = false; // 👈 fin
            }
        }
        lastCompact.rowId = null;
        lastCompact.columnState = null;
        selectedRowKey = null;
        gridApi.redrawRows();
    }

    // Aplica la vista compacta a la fila clicada: esconde columnas vacías en esa fila
    function applyCompactForRow(rowNode) {
        const data = rowNode.data || {};

        // guarda estado actual de columnas para restaurar
        lastCompact.columnState = gridApi.getColumnState();

        IN_COMPACT_APPLY = true; // 👈 empezamos cambios internos
        try {
            const updates = [];
            const defs = gridApi.getColumnDefs() || [];

            for (const def of defs) {
                const colId = def.colId || def.field;
                if (!colId) continue;
                if (COLUMN_MODAL_WHITELIST.has(colId)) continue; // no tocar whitelist

                const value = data[colId];
                const hide = isEmptyCellValue(value);
                updates.push({
                    colId,
                    hide
                });
            }

            if (updates.length) {
                gridApi.applyColumnState({
                    state: updates,
                    applyOrder: false
                });
            }

            selectedRowKey = getRowKey(rowNode);
            gridApi.redrawRows();
            lastCompact.rowId = getRowKey(rowNode);
        } finally {
            IN_COMPACT_APPLY = false; // 👈 fin cambios internos
        }
    }


    // Clave única de fila (usa referencia; si alguna vez cambias, actualiza esto)
    function getRowKey(rowNode) {
        return rowNode?.data?.referencia ?? null;
    }

    function calcTooltipGetter(p) {
        const expMap = p.data?._exp || p.data?.exp; // compat si ya tienes data.exp
        const field = p.colDef?.field;
        if (!expMap || !field) return '';
        const lines = expMap[field];
        return Array.isArray(lines) && lines.length ? lines.join('\n') : '';
    }

    // Aplica estado del modal SOLO al whitelist, preservando:
    // - El orden de las NO-whitelist
    // - Las posiciones relativas (huecos) del whitelist en el orden global actual
    function applyWhitelistState(whitelistOrderedIds, visibilityById) {
        const curState = gridApi.getColumnState() || [];
        const curOrder = curState.map(s => s.colId);

        const wlSet = new Set(whitelistOrderedIds);
        const wlQueue = [...whitelistOrderedIds]; // orden elegido en el modal

        // 1) Recompone el ORDEN final ocupando los huecos del whitelist
        const finalOrder = curOrder.map(colId => {
            if (wlSet.has(colId)) {
                // ocupa el hueco con el siguiente del orden elegido
                return wlQueue.shift();
            }
            return colId; // no-whitelist se queda tal cual
        });

        // 2) Construye el STATE final: copia el actual y solo cambia 'hide' de whitelist
        const curById = new Map(curState.map(s => [s.colId, s]));
        const finalState = finalOrder.map(colId => {
            const base = curById.get(colId) || {
                colId
            };
            if (wlSet.has(colId)) {
                // respeta width/sort/etc. y solo toca 'hide' si el modal lo definió
                const hide = Object.prototype.hasOwnProperty.call(visibilityById, colId) ?
                    visibilityById[colId] :
                    base.hide;
                return {
                    ...base,
                    hide
                };
            }
            return base;
        });

        // Importante: applyOrder TRUE con el state COMPLETO preserva todo sin “empujar” columnas
        gridApi.applyColumnState({
            state: finalState,
            applyOrder: true
        });
    }

    function isCompactActive() {
        return !!(lastCompact.rowId && lastCompact.columnState);
    }

    function collapseCompactBeforeExternalChange() {
        if (!isCompactActive()) return;
        IN_COMPACT_APPLY = true;
        try {
            gridApi.applyColumnState({
                state: lastCompact.columnState, // ⬅️ restauramos baseline previa al compact
                applyOrder: true
            });
        } finally {
            IN_COMPACT_APPLY = false;
        }
        lastCompact.rowId = null;
        lastCompact.columnState = null;
        selectedRowKey = null;
        gridApi.redrawRows();
    }
</script>
@endpush