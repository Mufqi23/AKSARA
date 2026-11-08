<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Pemasangan awal untuk hosting tanpa terminal (misalnya hosting gratis).
 * Aktif hanya jika SETUP_TOKEN diisi di .env, dan harus dihapus setelah selesai.
 */
class SetupController extends Controller
{
    public function __invoke(string $token)
    {
        $expected = (string) config('lms.setup_token');

        abort_if($expected === '' || ! hash_equals($expected, $token), 404);

        @set_time_limit(180);

        try {
            DB::connection()->getPdo();
            Artisan::call('migrate', ['--force' => true]);
            $log = trim(Artisan::output());
            $log .= "\n\n".$this->createAdmin();
        } catch (\Throwable $e) {
            report($e);

            return response('Pemasangan gagal: '.e($e->getMessage()), 500)
                ->header('Content-Type', 'text/plain; charset=utf-8');
        }

        return response(
            $log."\n\nSelesai. Hapus SETUP_TOKEN dari file .env agar halaman ini tertutup kembali.",
            200,
            ['Content-Type' => 'text/plain; charset=utf-8']
        );
    }

    private function createAdmin(): string
    {
        if (User::where('role', 'admin')->exists()) {
            return 'Akun administrator sudah ada, tidak dibuat ulang.';
        }

        $data = [
            'name' => (string) config('lms.setup_admin.name'),
            'email' => (string) config('lms.setup_admin.email'),
            'password' => (string) config('lms.setup_admin.password'),
        ];

        $validation = Validator::make($data, [
            'name' => 'required|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);

        if ($validation->fails()) {
            return 'Akun administrator belum dibuat: '.implode(' ', $validation->errors()->all());
        }

        User::create($data + [
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        return 'Akun administrator berhasil dibuat: '.$data['email'];
    }
}

