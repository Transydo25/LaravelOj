<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Traits\HasPermission;
use Illuminate\Support\Facades\Auth;


class UserController extends BaseController
{
    use HasPermission;

    public function index(Request $request)
    {
        if (!Auth::user()->hasPermission('read')) {
            abort(403, 'Unauthorized');
        }

        $sort = $request->input('sort');
        $sort_types = ['desc', 'asc'];
        $sort_option = ['name', 'created_at', 'updated_at'];
        $sort_by = $request->input('sort_by');
        $sort = in_array($sort, $sort_types) ? $sort : 'desc';
        $sort_by = in_array($sort_by, $sort_option) ? $sort_by : 'created_at';
        $search = $request->input('query');
        $limit = request()->input('limit') ?? config('app.paginate');
        $query = User::select('*');

        if ($search) {
            $query = $query->where('title', 'LIKE', '%' . $search . '%');
        }
        $users = $query->orderBy($sort_by, $sort)->paginate($limit);

        return $this->handleResponse($users, 'users data');
    }

    public function create(Request $request)
    {
        if (!Auth::user()->hasPermission('create')) {
            abort(403, 'Unauthorized');
        }
        $request->validate([
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'roles' => 'required|array',
        ]);

        $user = new User;
        $roleIds = $request->roles;

        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();
        $user->roles()->sync($roleIds);

        return $this->handleResponse($user, 'User successfully registered')->setStatusCode(201);
    }

    public function show(User $user)
    {
        if (!Auth::user()->hasPermission('read')) {
            abort(403, 'Unauthorized');
        }
        return $this->handleResponse($user, 'User data details');
    }

    public function update(Request $request, User $user)
    {
        if (!$request->user()->hasPermission('update')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'name' => 'required|string|between:2,100',
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|confirmed|min:6',
            'roles' => 'required|array',
        ]);

        $user->name = $request->name;
        $roleIds = $request->roles;
        $user->roles()->sync($roleIds);
        $user->password = bcrypt($request->new_password);
        $user->save();

        return $this->handleResponse($user, 'User successfully updated')->setStatusCode(201);
    }

    public function destroy(User $user)
    {
        if (!Auth::user()->hasPermission('delete')) {
            abort(403, 'Unauthorized');
        }
        $user->delete();

        return $this->handleResponse([], 'User successfully deleted');
    }
}
