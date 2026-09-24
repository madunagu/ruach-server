<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class GoogleAuthController extends Controller
{
    /**
     * Exchange a Google ID token (mobile google_sign_in / web GIS) for a
     * Sanctum token. Verifies the token against Google, then finds or
     * creates the user by email (stores google_id for future logins).
     *
     * POST /api/auth/google { id_token }
     */
    public function login(Request $request)
    {
        $request->validate(['id_token' => 'required|string']);

        $payload = $this->verifyIdToken($request->input('id_token'));
        if (!$payload || empty($payload['email'])) {
            return response()->json(['success' => false, 'error' => 'Invalid Google token'], 401);
        }

        $user = User::where('email', $payload['email'])->first();
        if (!$user) {
            $user = User::create([
                'name' => $payload['name'] ?? explode('@', $payload['email'])[0],
                'email' => $payload['email'],
                'password' => Hash::make(Str::random(32)),
                'avatar' => $payload['picture'] ?? null,
                'google_id' => $payload['sub'] ?? null,
                'email_verified_at' => now(),
            ]);
        } else {
            $user->forceFill(array_filter([
                'google_id' => $payload['sub'] ?? $user->google_id,
                'avatar' => $user->avatar ?? ($payload['picture'] ?? null),
            ]))->save();
        }

        $full = User::with(['images'])->withCount([
            'following', 'likes', 'followers', 'messages',
            'followers as is_following' => function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            },
        ])->find($user->id);

        $token = $user->createToken('devotion');

        return response()->json([
            'user' => $full,
            'token' => $token->plainTextToken,
            'success' => true,
        ], 200);
    }

    /** Verify via Google tokeninfo endpoint (no extra composer deps). */
    private function verifyIdToken(string $idToken): ?array
    {
        try {
            $res = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);
            if (!$res->ok()) {
                return null;
            }
            $data = $res->json();
            // Optional audience check when GOOGLE_CLIENT_ID is configured.
            $aud = env('GOOGLE_CLIENT_ID');
            if ($aud && ($data['aud'] ?? null) !== $aud) {
                return null;
            }
            if (($data['email_verified'] ?? null) === 'false') {
                return null;
            }
            return $data;
        } catch (\Throwable) {
            return null;
        }
    }
}
