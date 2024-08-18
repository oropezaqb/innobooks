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
                                <br>
                                <button class="btn btn-primary" type="submit">Save</button>
                                @error('')
                                    <p class="help is-danger">{{ $message }}</p>
                                @enderror
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function () {
            // Convert PHP roles variable to JavaScript array
            var rolesData = @json($roles);

            // Transform data to fit jqxGrid requirements
            var data = rolesData.map(function(role) {
                return {
                    id: role.id,
                    name: role.name
                };
            });

            var source = {
                localdata: data,
                datatype: "array",
                datafields: [
                    { name: 'id', type: 'string' },
                    { name: 'name', type: 'string' }
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
                    { text: 'Role Name', datafield: 'name', width: 450 }
                ]
            });
        });
    </script>
@endsection
