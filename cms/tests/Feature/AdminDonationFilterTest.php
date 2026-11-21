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
        $this->assertStringContainsString('"2026-11-10 16:00",Budi', $csv);
        $this->assertStringContainsString('Beasiswa,150000,', $csv);
        $this->assertStringNotContainsString('Status', $csv);
        $this->assertStringNotContainsString('Lunas', $csv);
        $this->assertStringNotContainsString('Siti', $csv);
    }

    public function test_list_shows_full_timestamp_in_wib_without_status(): void
    {
        $donation = Donation::factory()->create(['donor_name' => 'Rina', 'status' => 'paid', 'created_at' => '2026-08-05 02:07:00']);

        $this->actingAs(User::factory()->create())->get('/admin/donations?start_date=2026-08-01&end_date=2026-08-31')
            ->assertSee('5 Agu 2026, 09:07 WIB')
            ->assertSee('1 Agu 2026')
            ->assertDontSee('>Status<', false)
            ->assertDontSee('Lunas');

        $this->actingAs(User::factory()->create())->get("/admin/donations/{$donation->id}")
            ->assertSee('5 Agu 2026, 09:07 WIB')
            ->assertDontSee('Ubah Status Manual')
            ->assertDontSee('Lunas');
    }

    public function test_date_filter_uses_wib_day_boundaries(): void
    {
        // 31 Okt 2026 18:00 UTC = 1 Nov 2026 01:00 WIB.
        Donation::factory()->create(['donor_name' => 'Donatur Dini Hari', 'created_at' => '2026-10-31 18:00:00']);

        $this->actingAs(User::factory()->create())->get('/admin/donations?start_date=2026-11-01&end_date=2026-11-01')
            ->assertSee('Donatur Dini Hari');
    }

    public function test_manual_status_update_route_is_removed(): void
    {
        $donation = Donation::factory()->create(['status' => 'pending']);

        $this->actingAs(User::factory()->create())->put("/admin/donations/{$donation->id}", ['status' => 'paid'])
            ->assertMethodNotAllowed();

        $this->assertSame('pending', $donation->refresh()->status);
    }

    public function test_guest_cannot_export_donations(): void
    {
        $this->get('/admin/donations/export')->assertRedirect('/login');
    }
}
