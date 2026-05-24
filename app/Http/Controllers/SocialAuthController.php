<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // REDIRECT A GOOGLE
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // CALLBACK DE GOOGLE
    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = User::firstOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name'     => $googleUser->getName() ?? $googleUser->getNickname(),
                'password' => bcrypt(str()->random(16)),
                'role'     => User::ROLE_TRABAJADOR,
                'activo'   => true,
                'plan'     => User::PLAN_NORMAL,
            ]
        );

        // Si la cuenta está desactivada, no permitir acceso
        if (!$user->activo) {
            return redirect()->route('login')
                ->with('error', 'Tu cuenta ha sido desactivada.');
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    // REDIRECT A FACEBOOK
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    // CALLBACK DE FACEBOOK
    public function handleFacebookCallback()
    {
        $fbUser = Socialite::driver('facebook')->user();

        $user = User::firstOrCreate(
            ['email' => $fbUser->getEmail()],
            [
                'name'     => $fbUser->getName() ?? $fbUser->getNickname(),
                'password' => bcrypt(str()->random(16)),
            ]
        );

        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
