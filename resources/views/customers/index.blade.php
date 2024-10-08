@extends ('layouts.app2')
@section ('content')
    <div class="col-md-12">
        <div class="card">
            <div class="card-header font-weight-bold">Customers</div>
            <div class="card-body">
                @if (session('status'))
                    <div class="alert alert-success" role="alert">
                        {{ session('status') }}
                    </div>
                @endif
                <div id="wrapper">
                    <div
                        id="page"
                        class="container"
                    >
                        <h6 class="font-weight-bold">Search</h6>
                        <form method="GET" action="/customers">
                            @csrf
                            <div class="form-group">
                                <label for="supplier_name">Customer Name: </label>
                                <input
                                    class="form-control @error('supplier_name') is-danger @enderror"
                                    type="text"
                                    name="customer_name"
                                    id="customer_name" required
                                    value="{{ old('customer_name') }}">
                                @error('customer_name')
                                    <p class="help is-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <button class="btn btn-primary" type="submit">Search</button>
                        </form>
                        <br>
                        <h6 class="font-weight-bold">Add</h6>
                        <p>Want to add a new customer? Click <a href="{{ url('/customers/create') }}">here</a>!</p>
                        <p></p>
                        <h6 class="font-weight-bold">List</h6>
                        @forelse ($customers as $customer)
                            <div id="content">
                                <div id="name">
                                    <div style="display:inline-block;"><button class="btn btn-link" onclick="location.href = '{{ $customer->path() }}';">View</button></div>
                                    <div style="display:inline-block;"><button class="btn btn-link" onclick="location.href = '/customers/{{ $customer->id }}/edit';">Edit</button></div>
                                    <div style="display:inline-block;"><form method="POST" action="/customers/{{ $customer->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-link" type="submit">Delete</button>
                                    </form></div><div style="display:inline-block;">&nbsp;&nbsp;{{ $customer->name }}</div>
                                </div>
                            </div>
                        @empty
                            <p>No customers recorded yet.</p>
                        @endforelse
                        @if (!empty($customers))
                            {{ $customers->links() }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
