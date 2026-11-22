<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Buat akun CMS. Aman dijalankan ulang: akun yang emailnya sudah ada dibiarkan apa adanya,
     * sehingga password yang sudah diganti lewat CMS tidak ter-reset.
     */
    public function run(): void
    {
        foreach ($this->accounts() as $account) {
            $user = User::query()->firstOrNew(['email' => $account['email']]);

            if ($user->exists) {
                continue;
            }

            $user->forceFill([
                'name' => $account['name'],
                'password' => $account['password'],
                'email_verified_at' => now(),
            ])->save();
        }
    }

    /**
     * @return list<array{name: string, email: string, password: string}>
     */
    private function accounts(): array
    {
        return [
            [
                'name' => 'Admin Pelita Bahagia',
                'email' => env('SEED_ADMIN_EMAIL', 'admin@pelitabahagia.test'),
                'password' => env('SEED_ADMIN_PASSWORD', 'password'),
            ],
            [
                'name' => 'Pengurus Pelita Bahagia',
                'email' => env('SEED_PENGURUS_EMAIL', 'pengurus@pelitabahagia.test'),
                'password' => env('SEED_PENGURUS_PASSWORD', 'password'),
            ],
            [
                'name' => 'Staf Pelita Bahagia',
                'email' => env('SEED_STAF_EMAIL', 'staf@pelitabahagia.test'),
                'password' => env('SEED_STAF_PASSWORD', 'password'),
            ],
        ];
    }
}
