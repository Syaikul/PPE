<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function redirect(): RedirectResponse
    {
        if (! config('services.google.client_id')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Login Google belum dikonfigurasi. Isi GOOGLE_CLIENT_ID di file .env.',
            ]);
        }

        config(['services.google.redirect' => url('/auth/google/callback')]);

        $driver = Socialite::driver('google')->scopes(['openid', 'profile', 'email']);

        $hd = config('services.google.hd');
        if ($hd) {
            $driver = $driver->with(['hd' => $hd, 'prompt' => 'select_account']);
        } else {
            $driver = $driver->with(['prompt' => 'select_account']);
        }

        return $driver->redirect();
    }

    public function callback(): RedirectResponse
    {
        config(['services.google.redirect' => url('/auth/google/callback')]);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Login Google gagal. Silakan coba lagi.',
            ]);
        }

        $email = strtolower((string) $googleUser->getEmail());
        $hd = config('services.google.hd');
        if ($hd && ! str_ends_with($email, '@'.strtolower($hd))) {
            return redirect()->route('login')->withErrors([
                'email' => 'Hanya akun Google Workspace @'.$hd.' yang diizinkan.',
            ]);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => 'Email '.$email.' belum terdaftar. Minta SuperAdmin menambahkan akun Anda terlebih dahulu.',
            ]);
        }

        if (! $user->is_active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun ini dinonaktifkan. Hubungi SuperAdmin.',
            ]);
        }

        $user->forceFill([
            'google_id'         => $googleUser->getId(),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'name'              => $user->name ?: ($googleUser->getName() ?: $email),
        ])->save();

        Auth::login($user, true);

        return redirect()->intended('/home');
    }
}
