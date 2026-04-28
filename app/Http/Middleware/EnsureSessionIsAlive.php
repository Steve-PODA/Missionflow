<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSessionIsAlive
{
    // Délai en secondes : en dessous = refresh de page (pas une fermeture réelle)
    const REFRESH_GRACE_SECONDS = 5;

    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $closingAt = $request->session()->get('closing_at');

        if ($closingAt) {
            $elapsed = now()->timestamp - $closingAt;

            if ($elapsed > self::REFRESH_GRACE_SECONDS) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login');
            }

            // C'était un refresh de page, on nettoie le flag
            $request->session()->forget('closing_at');
        }

        return $next($request);
    }
}
