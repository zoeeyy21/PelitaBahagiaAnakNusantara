<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDonationFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 11, 14)->setTime(12, 0));
    }

    public function test_donations_default_to_the_last_seven_days(): void
    {
        Donation::factory()->create(['donor_name' => 'Donatur Baru', 'created_at' => now()->subDays(6)->startOfDay()]);
        Donation::factory()->create(['donor_name' => 'Donatur Lama', 'created_at' => now()->subDays(8)]);

        $this->actingAs(User::factory()->create())->get('/admin/donations')
            ->assertOk()
            ->assertSee('Donatur Baru')
            ->assertDontSee('Donatur Lama');
    }

    public function test_empty_date_range_shows_all_donations(): void
    {
        Donation::factory()->create(['donor_name' => 'Donatur Lama', 'created_at' => now()->subMonths(3)]);

        $this->actingAs(User::factory()->create())->get('/admin/donations?start_date=&end_date=')
            ->assertSee('Donatur Lama');
    }

    public function test_donations_can_be_filtered_by_date_range_and_program(): void
    {
        $program = Program::factory()->create(['title' => 'Beasiswa']);
        Donation::factory()->for($program)->create(['donor_name' => 'Donatur Oktober', 'created_at' => '2026-10-05 10:00:00']);
        Donation::factory()->create(['donor_name' => 'Donatur Umum Oktober', 'created_at' => '2026-10-06 10:00:00']);
        Donation::factory()->for($program)->create(['donor_name' => 'Donatur September', 'created_at' => '2026-09-05 10:00:00']);

        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/donations?start_date=2026-10-01&end_date=2026-10-31&program='.$program->id)
            ->assertSee('Donatur Oktober')
            ->assertDontSee('Donatur Umum Oktober')
            ->assertDontSee('Donatur September');

        $this->actingAs($admin)->get('/admin/donations?start_date=2026-10-01&end_date=2026-10-31&program=general')
            ->assertSee('Donatur Umum Oktober')
            ->assertDontSee('Donatur Oktober<', false);
    }

    public function test_list_hides_reference_code_and_labels_program_column(): void
    {
        Donation::factory()->create(['reference_code' => 'YPB-RAHASIA']);

        $this->actingAs(User::factory()->create())->get('/admin/donations')
            ->assertDontSee('YPB-RAHASIA')
            ->assertDontSee('>Kode<', false)
            ->assertDontSee('>Tujuan<', false)
            ->assertSee('>Program<', false);
    }

    public function test_admin_can_export_filtered_donations_as_csv(): void
    {
        $program = Program::factory()->create(['title' => 'Beasiswa']);
        Donation::factory()->for($program)->create(['donor_name' => 'Budi', 'amount' => 150000, 'status' => 'paid', 'created_at' => '2026-11-10 09:00:00']);
        Donation::factory()->create(['donor_name' => 'Siti', 'created_at' => '2026-08-01 09:00:00']);

        $response = $this->actingAs(User::factory()->create())
            ->get('/admin/donations/export?start_date=2026-11-01&end_date=2026-11-14')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload();

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\u{FEFF}Tanggal,Donatur", $csv);
        $this->assertStringContainsString('Budi', $csv);
        $this->assertStringContainsString('Beasiswa,150000,Lunas', $csv);
        $this->assertStringNotContainsString('Siti', $csv);
    }

    public function test_guest_cannot_export_donations(): void
    {
        $this->get('/admin/donations/export')->assertRedirect('/login');
    }
}
