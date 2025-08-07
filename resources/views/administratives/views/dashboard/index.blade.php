@extends('layouts.administratives')

@section('content')
<div class="container-fluid">
    <div class="row">


    </div>

</div>
@endsection



@push('scripts')
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
                enableRowGroup: true,
                enableValue: true,
            },
            sideBar: {
                toolPanels: ['columns', 'filters']
            },
            pagination: true,
            paginationPageSize: 10,
            paginationPageSizeSelector: [10, 25, 50],
            rowClassRules: {
                // apply red to Ford cars
                "rag-red": (params) => params.data.year >= "2010",
            },
        };


            $(document).ready(function() {
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
