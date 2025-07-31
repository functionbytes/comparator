<!doctype html>
<html lang="en">

<head>
    <title>JavaScript Example - Theming Tool Panels - Tool Panel Tabs</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex" />
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;700&amp;display=swap"
        rel="stylesheet" />
    <style media="only screen">
        :root,
        body {
            height: 100%;
            width: 100%;
            margin: 0;
            box-sizing: border-box;
            -webkit-overflow-scrolling: touch;
        }

        html {
            position: absolute;
            top: 0;
            left: 0;
            padding: 0;
            overflow: auto;
            font-family: -apple-system, "system-ui", "Segoe UI", Roboto,
                "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif,
                "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol",
                "Noto Color Emoji";
        }

        body {
            padding: 16px;
            overflow: auto;
            background-color: transparent;
        }

        /* Hide codesandbox highlighter */
        body>#highlighter {
            display: none;
        }

        .ag-side-button.ag-selected {
            text-shadow: 0 0 8px #039;
            font-weight: 500;
        }

        .rag-red {
            background-color: #cc222244;
        }

        .rag-green {
            background-color: #33cc3344;
        }
    </style>
</head>

<body>
    <div id="myGrid" style="height: 100%"></div>
    <script>
        (function() {
            const appLocation = "";

            window.__basePath = appLocation;
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise@34.0.2/dist/ag-grid-enterprise.min.js?t=1753268240539"></script>
    <!-- <script src="main.js"></script> -->
    <!-- <link rel="stylesheet" href="styles.css" /> -->
</body>

</html>

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