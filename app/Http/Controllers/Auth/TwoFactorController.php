<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class TwoFactorController extends Controller
{
    // Affiche la page de saisie du code OTP
    public function showVerify()
    {
        return Inertia::render('Auth/TwoFactor');
    }

    // Vérifie le code OTP saisi
    public function verify(Request $request)
    {
        $request->validate(['one_time_password' => 'required|digits:6']);

        $user = Auth::user();

        $google2fa    = app('pragmarx.google2fa');
        $valid        = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if (!$valid) {
            return back()->withErrors(['one_time_password' => 'Code invalide. Vérifiez votre application d\'authentification.']);
        }

        $request->session()->put('2fa_verified', true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    // Affiche la page de configuration 2FA (génération du QR code)
    public function showSetup()
    {
        $user      = Auth::user();
        $google2fa = app('pragmarx.google2fa');

        if (!$user->google2fa_secret) {
            $secret = $google2fa->generateSecretKey();
            $user->google2fa_secret = $secret;
            $user->save();
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        return Inertia::render('Auth/TwoFactorSetup', [
            'qrCodeUrl' => $qrCodeUrl,
            'secret'    => $user->google2fa_secret,
        ]);
    }

    // Active la 2FA après scan du QR code
    public function confirmSetup(Request $request)
    {
        $request->validate(['one_time_password' => 'required|digits:6']);

        $user      = Auth::user();
        $google2fa = app('pragmarx.google2fa');
        $valid     = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if (!$valid) {
            return back()->withErrors(['one_time_password' => 'Code invalide. Scannez à nouveau le QR code.']);
        }

        $request->session()->put('2fa_verified', true);
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Double authentification activée avec succès.');
    }
}
