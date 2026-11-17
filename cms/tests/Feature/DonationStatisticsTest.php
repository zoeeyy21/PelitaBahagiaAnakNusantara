<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_statistics(): void
    {
        $this->get('/admin/statistics')->assertRedirect('/login');
    }

    public function test_statistics_show_totals_and_progress_per_program(): void
    {
        $program = Program::factory()->create(['title' => 'Beasiswa', 'donation_target' => 1000000]);
        Donation::factory()->for($program)->create(['amount' => 250000, 'status' => 'paid']);
        Donation::factory()->for($program)->create(['amount' => 500000, 'status' => 'pending']);
        Donation::factory()->create(['program_id' => null, 'amount' => 100000, 'status' => 'paid']);
        Program::factory()->create(['title' => 'Tanpa Donasi', 'accepts_donation' => false]);

        $response = $this->actingAs(User::factory()->create())->get('/admin/statistics')->assertOk();

        $response->assertViewHas('summary', fn (array $summary): bool => $summary['total_collected'] === 350000
            && $summary['program_collected'] === 250000
            && $summary['total_target'] === 1000000
            && $summary['total_remaining'] === 750000
            && $summary['reached_count'] === 0);

        $response->assertSee('Beasiswa')
            ->assertSee('Rp250.000')
            ->assertSee('Rp750.000')
            ->assertSee('25%')
            ->assertDontSee('Tanpa Donasi');
    }

    public function test_statistics_can_be_filtered_by_target_status_and_searched(): void
    {
        $reached = Program::factory()->create(['title' => 'Program Tercapai', 'donation_target' => 100000]);
        Donation::factory()->for($reached)->create(['amount' => 100000, 'status' => 'paid']);
        Program::factory()->create(['title' => 'Program Berjalan', 'donation_target' => 100000]);

        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/statistics?target=reached')
            ->assertSee('Program Tercapai')
            ->assertDontSee('Program Berjalan');

        $this->actingAs($admin)->get('/admin/statistics?target=unreached')
            ->assertSee('Program Berjalan')
            ->assertDontSee('Program Tercapai');

        $this->actingAs($admin)->get('/admin/statistics?search=berjalan')
            ->assertSee('Program Berjalan')
            ->assertDontSee('Program Tercapai');
    }

    public function test_statistics_sort_by_collected_amount(): void
    {
        $small = Program::factory()->create(['title' => 'Donasi Kecil']);
        $large = Program::factory()->create(['title' => 'Donasi Besar']);
        Donation::factory()->for($small)->create(['amount' => 10000, 'status' => 'paid']);
        Donation::factory()->for($large)->create(['amount' => 900000, 'status' => 'paid']);

        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/statistics')
            ->assertSeeInOrder(['Donasi Besar', 'Donasi Kecil']);

        $this->actingAs($admin)->get('/admin/statistics?sort=collected_asc')
            ->assertSeeInOrder(['Donasi Kecil', 'Donasi Besar']);
    }

    public function test_statistics_list_is_paginated_per_ten_programs(): void
    {
        Program::factory()->count(11)->create();

        $this->actingAs(User::factory()->create())->get('/admin/statistics')
            ->assertViewHas('programs', fn ($programs): bool => $programs->count() === 10 && $programs->total() === 11);
    }
}
