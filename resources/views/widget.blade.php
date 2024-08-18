<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>jQWidgets Grid</title>
    
    <!-- jQWidgets CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jqwidgets-scripts@19.2.0/jqwidgets/styles/jqx.base.min.css">

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- jQWidgets JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/jqwidgets-scripts@19.2.0/jqwidgets/jqx-all.min.js"></script>
</head>
<body>
    <div id="jqxgrid"></div>

    <script type="text/javascript">
        $(document).ready(function () {
            // Example data
            var data = [
                { id: '1', name: 'John Doe', age: 30 },
                { id: '2', name: 'Jane Doe', age: 25 },
                { id: '3', name: 'Mark Smith', age: 40 },
                { id: '4', name: 'Lucy Brown', age: 22 }
            ];

            var source = {
                localdata: data,
                datatype: "array",
                datafields: [
                    { name: 'id', type: 'string' },
                    { name: 'name', type: 'string' },
                    { name: 'age', type: 'number' }
                ]
            };

            var dataAdapter = new $.jqx.dataAdapter(source);

            // Initialize jqxGrid
            $("#jqxgrid").jqxGrid({
                width: '100%',
                height: 400,
                source: dataAdapter,
                pageable: true,
                sortable: true,
                columns: [
                    { text: 'ID', datafield: 'id', width: 150 },
                    { text: 'Name', datafield: 'name', width: 250 },
                    { text: 'Age', datafield: 'age', width: 100 }
                ]
            });
        });
    </script>
</body>
</html>
