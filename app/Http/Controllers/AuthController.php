<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showForgot()
    {
        return redirect('/login')->withErrors(['email' => 'Pembaruan kata sandi hanya dapat dilakukan oleh Administrator Sekolah.']);
    }

    public function showVerifyEmail()
    {
        return view('auth.verify');
    }

    public function login(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = Str::lower($data['email']).'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam '.RateLimiter::availableIn($key).' detik.']);
        }

        $user = User::where('email', $data['email'])->where('status', 'active')->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['email' => 'Email atau kata sandi salah, atau akun belum aktif.'])->onlyInput('email');
        }

        RateLimiter::clear($key);

        if ($user->two_factor_enabled) {
            $r->session()->regenerate();
            $this->issueTwoFactorCode($user);
            $r->session()->put('login.2fa_user_id', $user->id);
            $r->session()->put('login.2fa_remember', $r->boolean('remember'));
            $r->session()->put('login.2fa_expires_at', now()->addMinutes(10)->getTimestamp());

            return redirect()->route('two-factor.challenge')->with('info', 'Kode verifikasi 2FA telah dikirim ke email Anda. Masukkan kode untuk melanjutkan.');
        }

        Auth::login($user, $r->boolean('remember'));
        $r->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function showTwoFactorChallenge(Request $r)
    {
        if (! $this->hasValidTwoFactorSession($r)) {
            $r->session()->forget(['login.2fa_user_id', 'login.2fa_remember', 'login.2fa_expires_at']);
            abort(403);
        }

        return view('auth.two-factor-challenge');
    }

    public function resendTwoFactorChallenge(Request $r)
    {
        if (! $this->hasValidTwoFactorSession($r)) {
            $r->session()->forget(['login.2fa_user_id', 'login.2fa_remember', 'login.2fa_expires_at']);
            abort(403);
        }

        $user = User::whereKey($r->session()->get('login.2fa_user_id'))->where('status', 'active')->firstOrFail();
        $this->issueTwoFactorCode($user);
        $r->session()->put('login.2fa_expires_at', now()->addMinutes(10)->getTimestamp());

        return back()->with('success', 'Kode verifikasi 2FA baru telah dikirim ke email Anda.');
    }

    protected function issueTwoFactorCode(User $user): void
    {
        $code = sprintf('%06d', random_int(100000, 999999));
        $user->update([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ]);
        $user->notify(new TwoFactorCode($code));
    }

    public function verifyTwoFactorChallenge(Request $r)
    {
        if (! $this->hasValidTwoFactorSession($r)) {
            $r->session()->forget(['login.2fa_user_id', 'login.2fa_remember', 'login.2fa_expires_at']);
            abort(403);
        }

        $r->validate(['code' => 'required|string|max:12']);
        $user = User::whereKey($r->session()->get('login.2fa_user_id'))->where('status', 'active')->firstOrFail();

        $ipKey = '2fa|'.$user->id.'|'.$r->ip();
        $userKey = '2fa-user|'.$user->id;
        if (RateLimiter::tooManyAttempts($ipKey, 5) || RateLimiter::tooManyAttempts($userKey, 5)) {
            $wait = max(RateLimiter::availableIn($ipKey), RateLimiter::availableIn($userKey));

            return back()->withErrors(['code' => 'Terlalu banyak percobaan. Silakan coba lagi dalam '.$wait.' detik.']);
        }

        if (! $user->two_factor_expires_at || $user->two_factor_expires_at->isPast() || ! $user->two_factor_code || ! Hash::check($r->code, $user->two_factor_code)) {
            RateLimiter::hit($ipKey, 60);
            RateLimiter::hit($userKey, 600);
            if (RateLimiter::tooManyAttempts($userKey, 5)) {
                $user->update(['two_factor_code' => null, 'two_factor_expires_at' => null]);
            }

            return back()->withErrors(['code' => 'Kode verifikasi 2FA tidak valid atau sudah kedaluwarsa.']);
        }

        RateLimiter::clear($ipKey);
        RateLimiter::clear($userKey);
        $user->update(['two_factor_code' => null, 'two_factor_expires_at' => null]);
        $remember = $r->session()->get('login.2fa_remember', false);
        $r->session()->forget(['login.2fa_user_id', 'login.2fa_remember', 'login.2fa_expires_at']);

        Auth::login($user, $remember);
        $r->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    protected function hasValidTwoFactorSession(Request $r): bool
    {
        if (! $r->session()->has('login.2fa_user_id')) {
            return false;
        }

        $expiresAt = $r->session()->get('login.2fa_expires_at');

        return $expiresAt === null || (int) $expiresAt >= now()->getTimestamp();
    }

    public function toggleTwoFactor(Request $r)
    {
        $user = $r->user();
        $user->update([
            'two_factor_enabled' => ! $user->two_factor_enabled,
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ]);

        $status = $user->two_factor_enabled ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Autentikasi Dua Faktor (2FA) berhasil {$status}.");
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/login');
    }

    public function forgot(Request $r)
    {
        return redirect('/login')->withErrors(['email' => 'Pembaruan kata sandi hanya dapat dilakukan oleh Administrator Sekolah.']);
    }

    public function resetForm(string $token)
    {
        return redirect('/login')->withErrors(['email' => 'Pembaruan kata sandi hanya dapat dilakukan oleh Administrator Sekolah.']);
    }

    public function reset(Request $r)
    {
        return redirect('/login')->withErrors(['email' => 'Pembaruan kata sandi hanya dapat dilakukan oleh Administrator Sekolah.']);
    }

    public function activation(Request $r, User $user)
    {
        if ($user->status !== 'inactive' || $user->email_verified_at || ! Password::broker()->tokenExists($user, (string) $r->query('token'))) {
            return response()->view('auth.activation-link-invalid', [], 410);
        }

        return view('auth.activate', compact('user'));
    }

    public function activate(Request $r, User $user)
    {
        if ($user->status !== 'inactive' || $user->email_verified_at || ! Password::broker()->tokenExists($user, (string) $r->query('token'))) {
            return response()->view('auth.activation-link-invalid', [], 410);
        }
        $r->validate(['password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()]]);
        $user->update(['password' => $r->password, 'email_verified_at' => now(), 'status' => 'active']);
        $user->saveAdminVisiblePassword((string) $r->password);
        Password::broker()->deleteToken($user);
        event(new Verified($user));

        if (Auth::check()) {
            Auth::logout();
            $r->session()->invalidate();
            $r->session()->regenerateToken();
        }

        return redirect('/login')->with('success', 'Email berhasil diverifikasi dan akun telah aktif. Silakan masuk dengan kata sandi baru Anda.');
    }

    public function verifyEmail(EmailVerificationRequest $r)
    {
        $r->fulfill();

        return redirect('/dashboard');
    }

    public function resendVerificationEmail(Request $r)
    {
        $r->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Tautan verifikasi email telah dikirim ulang.');
    }

    public function profile(Request $r)
    {
        $user = $r->user()->load(['student.block.program', 'student.block.yearLevel', 'teacher']);

        return view('auth.profile', ['user' => $user]);
    }

    public function updateProfile(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:120', 'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048']);
        if ($r->hasFile('profile_picture')) {
            if ($r->user()->profile_picture) {
                Storage::disk('local')->delete($r->user()->profile_picture);
            }
            $data['profile_picture'] = $r->file('profile_picture')->store('profiles', 'local');
        }
        $r->user()->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function photo(Request $r)
    {
        abort_unless($r->user()->profile_picture && Storage::disk('local')->exists($r->user()->profile_picture), 404);

        return Storage::disk('local')->response($r->user()->profile_picture, null, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function changePassword(Request $r)
    {
        abort_unless($r->user()->role === 'admin', 403);
        $r->validate(['current_password' => 'required|current_password', 'password' => ['required', 'confirmed', PasswordRule::min(12)->mixedCase()->numbers()]]);
        $r->user()->update(['password' => $r->password, 'remember_token' => Str::random(60)]);
        DB::table('sessions')->where('user_id', $r->user()->id)->where('id', '!=', $r->session()->getId())->delete();
        $r->session()->regenerate();

        return back()->with('success', 'Kata sandi berhasil diperbarui. Sesi masuk di perangkat lain telah dikeluarkan.');
    }
}
