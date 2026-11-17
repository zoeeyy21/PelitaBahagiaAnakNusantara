<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\DonationPackage;
use App\Models\Program;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class ProgramDonationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_donation_with_packages_target_qris_and_period(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->post('/admin/programs', [
            'title' => 'Beasiswa',
            'description' => 'Deskripsi.',
            'status' => 'active',
            'start_date' => '2026-11-01',
            'end_date' => '2026-12-31',
            'accepts_donation' => '1',
            'donation_target' => 5000000,
            'packages' => [
                ['name' => 'Paket Buku', 'amount' => 50000],
                ['name' => '', 'amount' => ''],
                ['name' => 'Paket Seragam', 'amount' => 150000],
            ],
            'qris_image' => UploadedFile::fake()->image('qris.png'),
        ])->assertRedirect('/admin/programs');

        $program = Program::firstOrFail();

        $this->assertTrue($program->accepts_donation);
        $this->assertSame(5000000, $program->donation_target);
        $this->assertNull($program->min_donation_amount);
        $this->assertSame('2026-11-01', $program->start_date->toDateString());
        $this->assertSame('2026-12-31', $program->end_date->toDateString());
        $this->assertSame([['Paket Buku', 50000], ['Paket Seragam', 150000]], $program->donationPackages->map(fn ($package) => [$package->name, $package->amount])->all());
        Storage::disk('public')->assertExists($program->qris_image);
    }

    public function test_minimum_amount_is_required_when_program_has_no_packages(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/programs', [
            'title' => 'Program',
            'description' => 'Deskripsi.',
            'status' => 'active',
            'accepts_donation' => '1',
            'donation_target' => 1000000,
        ])->assertSessionHasErrors('min_donation_amount');

        $this->assertDatabaseCount(Program::class, 0);
    }

    public function test_target_is_required_when_donation_is_opened(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/programs', [
            'title' => 'Program',
            'description' => 'Deskripsi.',
            'status' => 'active',
            'accepts_donation' => '1',
            'min_donation_amount' => 10000,
        ])->assertSessionHasErrors('donation_target');
    }

    public function test_end_date_cannot_precede_start_date(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/programs', [
            'title' => 'Program',
            'description' => 'Deskripsi.',
            'status' => 'active',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-01',
        ])->assertSessionHasErrors('end_date');
    }

    public function test_closing_donation_leaves_existing_donation_settings_untouched(): void
    {
        $program = Program::factory()->create(['donation_target' => 2000000]);
        DonationPackage::factory()->for($program)->create(['amount' => 75000]);

        $this->actingAs(User::factory()->create())->put("/admin/programs/{$program->slug}", [
            'title' => $program->title,
            'slug' => $program->slug,
            'description' => 'Deskripsi.',
            'status' => 'active',
            'donation_target' => '',
            'packages' => [],
        ])->assertSessionHasNoErrors();

        $program->refresh();
        $this->assertFalse($program->accepts_donation);
        $this->assertSame(2000000, $program->donation_target);
        $this->assertSame(1, $program->donationPackages()->count());
    }

    public function test_program_without_donation_hides_donate_button_and_rejects_donations(): void
    {
        $program = Program::factory()->create(['accepts_donation' => false]);

        $this->get("/program/{$program->slug}")->assertOk()
            ->assertDontSee('Donasi untuk program ini');

        $this->post('/donasi', [
            'program_id' => $program->id,
            'amount' => 100000,
            'donor_name' => 'Donatur',
            'donor_phone' => '08123456789',
        ])->assertSessionHasErrors('program_id');

        $this->assertDatabaseCount(Donation::class, 0);
    }

    public function test_package_only_program_rejects_custom_amount(): void
    {
        $program = Program::factory()->create(['min_donation_amount' => null]);
        DonationPackage::factory()->for($program)->create(['amount' => 75000]);

        $this->post('/donasi', [
            'program_id' => $program->id,
            'amount' => 80000,
            'donor_name' => 'Donatur',
            'donor_phone' => '08123456789',
        ])->assertSessionHasErrors('amount');

        $this->mockMidtrans();

        $this->post('/donasi', [
            'program_id' => $program->id,
            'amount' => 75000,
            'donor_name' => 'Donatur',
            'donor_phone' => '08123456789',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas(Donation::class, ['program_id' => $program->id, 'amount' => 75000]);
    }

    public function test_custom_amount_must_meet_program_minimum(): void
    {
        $program = Program::factory()->create(['min_donation_amount' => 50000]);

        $this->post('/donasi', [
            'program_id' => $program->id,
            'amount' => 49000,
            'donor_name' => 'Donatur',
            'donor_phone' => '08123456789',
        ])->assertSessionHasErrors('amount');

        $this->mockMidtrans();

        $this->post('/donasi', [
            'program_id' => $program->id,
            'amount' => 50000,
            'donor_name' => 'Donatur',
            'donor_phone' => '08123456789',
        ])->assertSessionHasNoErrors();
    }

    private function mockMidtrans(): void
    {
        $this->mock(MidtransService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createSnapTransaction')->andReturn((object) ['token' => 'snap-token', 'redirect_url' => null]);
        });
    }
}
