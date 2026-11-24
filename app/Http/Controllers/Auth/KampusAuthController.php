<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Kampus\KampusAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KampusAuthController extends Controller
{
    protected KampusAuthService $kampusAuth;

    public function __construct(KampusAuthService $kampusAuth)
    {
        $this->kampusAuth = $kampusAuth;
    }

    /**
     * Menangani proses login menggunakan akun portal kampus (NIP/NIDN & password portal).
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'kampus_username' => ['required', 'string'],
            'kampus_password' => ['required', 'string'],
        ], [
            'kampus_username.required' => 'Username portal kampus wajib diisi.',
            'kampus_password.required' => 'Password portal kampus wajib diisi.',
        ]);

        // Rate limiting untuk mencegah brute-force ke API kampus
        $throttleKey = 'kampus-login|' . Str::lower($request->input('kampus_username')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'kampus_username' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        try {
            $user = $this->kampusAuth->authenticateAndSyncUser(
                $request->input('kampus_username'),
                $request->input('kampus_password')
            );

            RateLimiter::clear($throttleKey);

            Auth::guard('web')->login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            // Redirect berdasarkan role seperti alur login lokal
            if ($user->is_admin) {
                return redirect()->intended(route('admin.dashboard', absolute: false));
            }
            if ($user->is_penyusun) {
                return redirect()->intended(route('penyusun.dashboard', absolute: false));
            }
            if ($user->is_lpm) {
                return redirect()->intended(route('lpm.dashboard', absolute: false));
            }
            if ($user->is_reviewer) {
                return redirect()->intended(route('reviewer.dashboard', absolute: false));
            }

            return redirect()->intended(route('dashboard', absolute: false));

        } catch (\RuntimeException $e) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'kampus_username' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'kampus_username' => 'Terjadi kesalahan saat menghubungi server kampus. Silakan coba beberapa saat lagi.',
            ]);
        }
    }
}
