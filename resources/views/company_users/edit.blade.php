@extends('layouts.app2')
@section('content')
    <div class="col-md-12">
        <div class="card">
            <div class="card-header font-weight-bold">Company: {{ \Auth::user()->currentCompany->company->name }} (Edit Company User Details)</div>
            <div class="card-body">
                <div id="wrapper">
                    <div id="page" class="container">
                        <div id="content">
                            <form method="POST" action="/company_users/{{ $user->id }}">
                                @csrf
                                @method('PUT')
                                @if (!empty($message))
                                    <p>{{ $message }}</p>
                                @endif
                                <p>User Name: {!! $user->name !!}</p>
                                <p>Roles:</p>

                                <input type="hidden" id="id" name="id" value="{{ $user->id }}">
    <div id="jqxgrid"></div>
                                <button class="btn btn-primary" type="submit">Save</button>
                                @error('')
                                    <p class="help is-danger">{{ $message }}</p>
                                @enderror
                            </form>
    <script type="text/javascript">
        $(document).ready(function () {
            // Define the data source
            var data = [
                { "id": "1", "name": "John Doe", "age": "30" },
                { "id": "2", "name": "Jane Doe", "age": "25" },
                { "id": "3", "name": "Mark Smith", "age": "40" },
                { "id": "4", "name": "Lucy Brown", "age": "22" }
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
                width: 600,
                height: 400,
                source: dataAdapter,
                pageable: true,
                sortable: true,
                columns: [
                    { text: 'ID', datafield: 'id', width: 100 },
                    { text: 'Name', datafield: 'name', width: 250 },
                    { text: 'Age', datafield: 'age', width: 100 }
                ]
            });
        });
    </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
