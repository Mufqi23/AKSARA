<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'lms:create-admin';

    protected $description = 'Membuat akun administrator secara interaktif tanpa menyimpan kata sandi di riwayat shell';

    public function handle(): int
    {
        $name = $this->ask('Nama administrator');
        $email = $this->ask('Email');
        $password = $this->secret('Kata sandi (minimal 12 karakter, huruf besar-kecil, dan angka)');

        $validation = Validator::make(compact('name', 'email', 'password'), [
            'name' => 'required|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(12)->mixedCase()->numbers()],
        ]);

        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create(compact('name', 'email', 'password') + [
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->info('Administrator berhasil dibuat.');

        return self::SUCCESS;
    }
}

