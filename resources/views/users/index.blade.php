@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">Manage Users</div>
    <div class="card-body">
        @can('create-user')
            <a href="{{ route('users.create') }}" class="btn btn-success btn-sm my-2"><i class="bi bi-plus-circle"></i> Add New User</a>
        @endcan

        <table id="users-table" class="table table-striped table-bordered" style="width: 100%">
            <thead>
                <tr>
                    <th>S#</th>
					<th>Name</th>
					<th>Email</th>
					<th>Roles</th>
					<th>Status</th>
					<th>Toggle Status</th>
					<th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#users-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('users.list') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', title: 'S#' },
            { data: 'name', name: 'name', title: 'Name' },
            { data: 'email', name: 'email', title: 'Email' },
            { data: 'roles', name: 'roles', title: 'Roles', orderable: false, searchable: false },
            { data: 'status', name: 'status', title: 'Status', orderable: false, searchable: false },
            { data: 'toggle_status', name: 'toggle_status', title: 'Toggle Status', orderable: false, searchable: false },
            { data: 'action', name: 'action', title: 'Action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']]
    });
});

</script>
@endsection
