@extends('layouts.administratives')

@section('content')
    <div id="myGrid" style="height: 100%"></div>

@endsection


@push('scripts')

    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@34.0.2/dist/ag-grid-community.min.js"></script>

    <script>
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
        console.log(result);

        const columnDefs = [{
            field: "reference",
            minWidth: 170,
            editable: false
        },
            {
                field: "Matchs"
            },
            {
                field: "name"
            },
            {
                field: "estado_gestion"
            },
            {
                field: "preciofijo"
            },
            {
                field: "etiqueta"
            },
            {
                field: "visible_web_mas_portes"
            },
            {
                field: "externo"
            },
            {
                field: "bronze"
            },
            {
                field: "total"
            },
        ];

        let gridApi;

        const gridOptions = {
            theme: myTheme,
            columnDefs: columnDefs,
            defaultColDef: {
                editable: true,
                filter: true,
                floatingFilter: true,
            },
            pagination: true,
            paginationPageSize: 10,
            paginationPageSizeSelector: [10, 25, 50],
            rowClassRules: {
                // apply red to Ford cars
                "rag-red": (params) => params.data.year >= "2010",
            },
        };

        // setup the grid after the page has finished loading
        document.addEventListener("DOMContentLoaded", function() {
            const gridDiv = document.querySelector("#myGrid");
            gridApi = agGrid.createGrid(gridDiv, gridOptions);
            gridApi.setGridOption("rowData", result);
            // console.log(result);
            // fetch("https://www.ag-grid.com/example-assets/olympic-winners.json")
            //     .then((response) => response.json())
            //     .then((data) => gridApi.setGridOption("rowData", data));
        });
    </script>
@endpush
