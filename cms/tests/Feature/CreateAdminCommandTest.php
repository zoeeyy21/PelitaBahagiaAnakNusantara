<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_admin(): void
    {
        $this->artisan('admin:create', ['email' => 'pic@pelitabahagia.org', '--name' => 'PIC Yayasan'])
            ->expectsQuestion('Password admin (minimal 8 karakter)', 'rahasia123')
            ->assertSuccessful();

        $admin = User::where('email', 'pic@pelitabahagia.org')->firstOrFail();

        $this->assertSame('PIC Yayasan', $admin->name);
        $this->assertTrue(Hash::check('rahasia123', $admin->password));
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_command_rejects_short_password(): void
    {
        $this->artisan('admin:create', ['email' => 'pic@pelitabahagia.org'])
            ->expectsQuestion('Password admin (minimal 8 karakter)', 'pendek')
            ->assertFailed();

        $this->assertDatabaseCount(User::class, 0);
    }
}
