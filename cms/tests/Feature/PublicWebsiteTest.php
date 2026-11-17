<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\Program;
use App\Services\MidtransService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_program_is_not_publicly_visible(): void
    {
        Program::factory()->create([
            'title' => 'Program Nonaktif',
            'slug' => 'program-nonaktif',
            'status' => 'inactive',
        ]);

        $this->get('/program')
            ->assertOk()
            ->assertDontSee('Program Nonaktif');
    }

    public function test_guest_donation_starts_midtrans_checkout(): void
    {
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createSnapTransaction')->once()->andReturn((object) [
                'token' => 'snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-123',
            ]);
        });

        $this->postJson('/donasi', [
            'amount' => 100000,
            'donor_name' => 'Donatur Baik',
            'donor_email' => 'donatur@example.com',
            'donor_phone' => '08123456789',
        ])->assertOk()->assertJson([
            'success' => true,
            'snap_token' => 'snap-token-123',
        ]);

        $this->assertDatabaseHas(Donation::class, [
            'amount' => 100000,
            'donor_name' => 'Donatur Baik',
            'payment_method' => 'midtrans',
            'status' => 'pending',
        ]);
    }

    public function test_donation_is_marked_failed_when_midtrans_is_unavailable(): void
    {
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createSnapTransaction')->once()->andThrow(new Exception('Server key kosong'));
        });

        $this->postJson('/donasi', [
            'amount' => 100000,
            'donor_name' => 'Donatur Baik',
            'donor_phone' => '08123456789',
        ])->assertStatus(500)->assertJson(['success' => false]);

        $this->assertDatabaseHas(Donation::class, [
            'donor_name' => 'Donatur Baik',
            'status' => 'failed',
        ]);
    }

    public function test_donation_requires_donor_name_and_phone(): void
    {
        $this->post('/donasi', [
            'amount' => 100000,
            'is_anonymous' => '1',
        ])->assertSessionHasErrors(['donor_name', 'donor_phone']);

        $this->assertDatabaseCount(Donation::class, 0);
    }

    public function test_anonymous_donation_still_records_donor_name(): void
    {
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createSnapTransaction')->once()->andReturn((object) ['token' => 'snap-token-123']);
        });

        $this->post('/donasi', [
            'amount' => 100000,
            'donor_name' => 'Donatur Rahasia',
            'donor_phone' => '08123456789',
            'is_anonymous' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas(Donation::class, [
            'donor_name' => 'Donatur Rahasia',
            'is_anonymous' => true,
        ]);
    }

    public function test_active_program_page_opens_donation_dialog(): void
    {
        $program = Program::factory()->create(['accepts_donation' => true]);

        $this->get("/program/{$program->slug}")
            ->assertOk()
            ->assertSee('Donasi untuk program ini')
            ->assertSee("\$dispatch('open-donation', {$program->id})", false)
            ->assertSee('action="'.route('donations.store').'"', false);
    }

    public function test_donation_rejects_inactive_program(): void
    {
        $program = Program::factory()->create(['status' => 'inactive']);

        $this->post('/donasi', [
            'program_id' => $program->id,
            'amount' => 100000,
            'donor_name' => 'Donatur Baik',
            'donor_phone' => '08123456789',
        ])->assertSessionHasErrors('program_id');

        $this->assertDatabaseCount(Donation::class, 0);
    }
}
