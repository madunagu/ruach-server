<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use App\Models\User;

use Validator;

use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);


        //THIS LINE WAS COMMENTED TO ENABLE UNVERIFIED USERS
        //$credentials['is_verified'] = 1;
        if ($this->attemptLogin($request)) {
            $user = $this->guard()->user();
            $token = $user->createToken('devotion');

            return response()->json([
                'user' => $user,
                'token' => $token->plainTextToken,
                'success' => true
            ], 200);
        }

        return response()->json(['success' => false, 'error' => 'incorrect username or password'], 401);
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }
        // $request->validate([
        //     'name' => ['required', 'string', 'max:255'],
        //     // 'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        //     // 'password' => ['required', 'string', 'min:8', 'confirmed'],
        // ]);

        $data = $request->only('name', 'email', 'password');
        $data = $request->all();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('devotion');

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'success' => true
        ], 200);
    }
}
