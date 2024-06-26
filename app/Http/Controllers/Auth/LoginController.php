<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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
            $session = $this->guard()->user();
            $user = User::with(['images'])->withCount([
                'following',
                'likes',
                'followers',
                'messages',
                'followers as is_following' => function (Builder $query) use ($session) {
                    $query->where('user_id', $session->id);
                },
            ])->find($session->id);
            $token = $user->createToken('devotion');

            return response()->json([
                'user' => $user,
                'token' => $token->plainTextToken,
                'success' => true
            ], 200);
        }

        return response()->json(['success' => false, 'error' => 'incorrect username or password'], 401);
    }

}
