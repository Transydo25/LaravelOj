<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use Illuminate\Auth\Events\Registered;



class AuthController extends BaseController
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function index(Request $request)
    {
        $sort = $request->input('sort');
        $sort_types = ['desc', 'asc'];
        $sort_option = ['title', 'created_at', 'updated_at'];
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

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return $this->handleResponse([], 'Unauthorized')->setStatusCode(401);
        }

        return $this->createNewToken($token);
    }


    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        $user = new User;

        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();
        event(new Registered($user));
        Auth::login($user);

        return $this->handleResponse($user, 'User successfully registered')->setStatusCode(201);
    }



    public function logout()
    {
        auth()->logout();

        return $this->handleResponse([], 'User successfully signed out');
    }


    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }


    public function userProfile()
    {
        return response()->json(auth()->user());
    }


    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|between:2,100',
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|confirmed|min:6',
        ]);

        $user = auth()->user();
        $user->name = $request->name;
        $user->password = bcrypt($request->new_password);
        $user->save();

        return $this->handleResponse($user, 'User successfully updated')->setStatusCode(201);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return $this->handleResponse([], 'User successfully deleted');
    }
}
