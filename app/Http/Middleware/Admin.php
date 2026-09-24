<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Admin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !(bool) ($user->is_admin ?? false)) {
            return response()->json(['error' => 'Forbidden — admin only'], 403);
        }
        return $next($request);
    }
}
