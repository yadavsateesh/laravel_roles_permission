@extends('layouts.app')

@section('content')

<div class="card">
    <div class="card-header">Manage Users</div>
    <div class="card-body">
        @can('create-user')
            <a href="{{ route('users.create') }}" class="btn btn-success btn-sm my-2"><i class="bi bi-plus-circle"></i> Add New User</a>
        @endcan
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                <th scope="col">S#</th>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Roles</th>
                <th scope="col">Action</th>
                <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                <tr>
                    <th scope="row">{{ $loop->iteration }}</th>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        @forelse ($user->getRoleNames() as $role)
                            <span class="badge bg-primary">{{ $role }}</span>
                        @empty
                        @endforelse
                    </td>
                    <td>
                        <form action="{{ route('users.destroy', $user->id) }}" method="post"class="d-inline">
                            @csrf
                            @method('DELETE')

                            <a href="{{ route('users.show', $user->id) }}" class="btn btn-warning btn-sm"><i class="bi bi-eye"></i> Show</a>

                            @if (in_array('Super Admin', $user->getRoleNames()->toArray() ?? []) )
                                @if (Auth::user()->hasRole('Super Admin'))
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
                                @endif
                            @else
                                @can('edit-user')
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>   
                                @endcan

                                @can('delete-user')
                                    @if (Auth::user()->id!=$user->id)
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Do you want to delete this user?');"><i class="bi bi-trash"></i> Delete</button>
                                    @endif
                                @endcan
                            @endif

                        </form>
						<form action="{{ route('users.toggleStatus', $user->id) }}" method="POST" class="d-inline">
							@csrf
							@method('PATCH')
							@php
								$btnClass = 'btn btn-sm btn-warning ';
								$btnClass .= $user->status ? 'bg-success' : 'bg-danger';
								$btnClass .= $user->hasRole('Super Admin') ? ' disabled' : '';
							@endphp
							<button class="{{ $btnClass }}" onclick="return confirm('Do you want to {{ $user->status ? 'inactivate' : 'activate' }} this user?');">
								{{ $user->status ? 'Inactivate' : 'Activate' }}
							</button>
						</form>
                    </td>
					 <td>
						@if($user->status)
							<span class="badge bg-success">Active</span>
						@else
							<span class="badge bg-danger">Inactive</span>
						@endif
					</td>
                </tr>
                @empty
                    <td colspan="5">
                        <span class="text-danger">
                            <strong>No User Found!</strong>
                        </span>
                    </td>
                @endforelse
            </tbody>
        </table>

        {{ $users->links() }}

    </div>
</div>
    
@endsection