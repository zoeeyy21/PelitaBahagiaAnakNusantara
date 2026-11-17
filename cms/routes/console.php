<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create {email : Email login admin} {--name=Admin Pelita Bahagia : Nama yang ditampilkan di CMS}', function (string $email): int {
    $password = $this->secret('Password admin (minimal 8 karakter)');

    $validator = Validator::make(
        ['email' => $email, 'name' => $this->option('name'), 'password' => $password],
        [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ],
    );

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }

        return 1;
    }

    User::create([
        'name' => $this->option('name'),
        'email' => $email,
        'password' => $password,
    ])->forceFill(['email_verified_at' => now()])->save();

    $this->info("Akun admin {$email} berhasil dibuat.");

    return 0;
})->purpose('Buat akun admin CMS (registrasi publik dinonaktifkan)');
