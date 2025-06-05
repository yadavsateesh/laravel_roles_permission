<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request; 
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     */
  public function __construct()
	{
		$this->middleware('auth');
		$this->middleware('permission:create-user|edit-user|delete-user', ['only' => ['index','show']]);
		$this->middleware('permission:create-user', ['only' => ['create','store']]);
		$this->middleware('permission:edit-user', ['only' => ['edit','update']]);
		$this->middleware('permission:delete-user', ['only' => ['destroy']]);
	}

  public function userList(Request $request)
  {
	if ($request->ajax()) {
		$data = User::with('roles')->orderBy('id', 'DESC')->get();

	 return DataTables::of($data)
		->addIndexColumn()
		->addColumn('roles', function ($user) {
			if ($user->roles->isEmpty()) {
				return '<span class="text-muted">No role</span>';
			}
			return $user->getRoleNames()->map(function ($role) {
				return '<span class="badge bg-primary">' . $role . '</span>';
			})->implode(' ');
		})
		->addColumn('status', function ($user) {
			return $user->status
				? '<span class="badge bg-success">Active</span>'
				: '<span class="badge bg-danger">Inactive</span>';
		})
		->addColumn('toggle_status', function ($user) {
			// Disable toggle button if user is Super Admin
			$disabled = $user->hasRole('Super Admin') ? 'disabled' : '';

			$btnClass = $user->status ? 'btn-success' : 'btn-danger';
			$btnText = $user->status ? 'Inactivate' : 'Activate';

			$form = '<form method="POST" action="' . route('users.toggleStatus', $user->id) . '" class="d-inline">'
				. csrf_field()
				. method_field('PATCH')
				. '<button type="submit" class="btn btn-sm ' . $btnClass . ' ' . $disabled . '" '
				. 'onclick="return confirm(\'Are you sure you want to ' . strtolower($btnText) . ' this user?\')">'
				. $btnText
				. '</button>'
				. '</form>';

			return $form;
		})
		->addColumn('action', function ($user) {
			$view = '<a href="' . route('users.show', $user->id) . '" class="btn btn-warning btn-sm me-1"><i class="bi bi-eye"></i></a>';

			$edit = '';
			if ($user->hasRole('Super Admin')) {
				if (auth()->user()->hasRole('Super Admin')) {
					$edit = '<a href="' . route('users.edit', $user->id) . '" class="btn btn-primary btn-sm me-1"><i class="bi bi-pencil-square"></i></a>';
				}
			} elseif (auth()->user()->can('edit-user')) {
				$edit = '<a href="' . route('users.edit', $user->id) . '" class="btn btn-primary btn-sm me-1"><i class="bi bi-pencil-square"></i></a>';
			}

		  $delete = '';
		  if (!($user->hasRole('Super Admin')) && $user->id != auth()->id() && auth()->user()->can('delete-user')) {
			$delete = '<form method="POST" action="' . route('users.destroy', $user->id) . '" class="d-inline">'
				. csrf_field()
				. method_field('DELETE')
				. '<button type="submit" class="btn btn-danger btn-sm" onclick="return confirm(\'Delete this user?\')"><i class="bi bi-trash"></i></button>'
				. '</form>';
			}

			return $view . $edit . $delete;
		})
		->rawColumns(['roles', 'status', 'toggle_status', 'action'])
		->make(true);
		}
	} 
  
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('users.index', [
            'users' => User::latest('id')->paginate(3)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::pluck('name')->all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $input = $request->all();
        $input['password'] = Hash::make($request->password);

        $user = User::create($input);
        $user->assignRole($request->roles);

        return redirect()->route('users.index')
                ->withSuccess('New user is added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        return view('users.show', [
            'user' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        // Check Only Super Admin can update his own Profile
        if ($user->hasRole('Super Admin')){
            if($user->id != auth()->user()->id){
                abort(403, 'USER DOES NOT HAVE THE RIGHT PERMISSIONS');
            }
        }

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::pluck('name')->all(),
            'userRoles' => $user->roles->pluck('name')->all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $input = $request->all();
 
        if(!empty($request->password)){
            $input['password'] = Hash::make($request->password);
        }else{
            $input = $request->except('password');
        }
        
        $user->update($input);

        $user->syncRoles($request->roles);

        return redirect()->back()
                ->withSuccess('User is updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        // About if user is Super Admin or User ID belongs to Auth User
        if ($user->hasRole('Super Admin') || $user->id == auth()->user()->id)
        {
            abort(403, 'USER DOES NOT HAVE THE RIGHT PERMISSIONS');
        }

        $user->syncRoles([]);
        $user->delete();
        return redirect()->route('users.index')
                ->withSuccess('User is deleted successfully.');
    }
	
   public function toggleStatus($id)
	{
		$user = User::findOrFail($id);
		
		if ($user->hasRole('Super Admin') && $user->status == 1) {
			return redirect()->back()->withSuccess('You cannot deactivate a Super Admin user.');
		}
		
		$user->status = !$user->status; 
		$user->save();

		return redirect()->back()->withSuccess('User status updated successfully.');
	}	
	

	public function checkStatus()
	{		
		if (Auth::check() && Auth::user()->status == 0) {
			Auth::logout();
			// Important: sirf JSON response return karo
			return response()->json(['logout' => true, 'message' => 'User is inactive'], 200);
		}
		return response()->json(['logout' => false, 'message' => 'User is active'], 200);
	}
}