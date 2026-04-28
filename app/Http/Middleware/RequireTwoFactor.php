<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Si l'utilisateur n'a pas encore configuré la 2FA, le forcer à le faire
        if (!$user->google2fa_secret) {
            if ($request->routeIs('2fa.setup', '2fa.setup.confirm')) {
                return $next($request);
            }
            return redirect()->route('2fa.setup');
        }

        // Si la 2FA est configurée mais pas encore vérifiée pour cette session
        $verifiedAt = $request->session()->get('2fa_verified_at');
        $lifetime   = config('session.lifetime') * 60; // secondes

        if (!$verifiedAt || (now()->timestamp - $verifiedAt) > $lifetime) {
            if ($request->routeIs('2fa.verify', '2fa.verify.submit', 'logout')) {
                return $next($request);
            }
            return redirect()->route('2fa.verify');
        }

        // Fenêtre glissante : on rafraîchit le timestamp à chaque requête
        $request->session()->put('2fa_verified_at', now()->timestamp);

        return $next($request);
    }
}
