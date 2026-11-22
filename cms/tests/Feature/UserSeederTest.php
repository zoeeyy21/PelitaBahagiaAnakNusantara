<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_three_cms_accounts_that_can_log_in(): void
    {
        $this->seed(UserSeeder::class);

        $this->assertSame(3, User::count());

        foreach (['admin@pelitabahagia.test', 'pengurus@pelitabahagia.test', 'staf@pelitabahagia.test'] as $email) {
            $user = User::where('email', $email)->firstOrFail();

            $this->assertTrue(Hash::check('password', $user->password));
            $this->assertNotNull($user->email_verified_at);
        }
    }

    public function test_seeder_can_run_again_without_duplicating_accounts_or_resetting_passwords(): void
    {
        $this->seed(UserSeeder::class);
        User::where('email', 'staf@pelitabahagia.test')->firstOrFail()->update(['password' => 'password-baru']);

        $this->seed(UserSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertTrue(Hash::check('password-baru', User::where('email', 'staf@pelitabahagia.test')->firstOrFail()->password));
    }
}
