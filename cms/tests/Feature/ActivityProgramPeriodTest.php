<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityProgramPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_dates_must_fall_within_program_period(): void
    {
        $program = Program::factory()->create(['title' => 'Beasiswa', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31']);

        $this->actingAs(User::factory()->create())->post('/admin/activities', [
            'program_id' => $program->id,
            'title' => 'Kegiatan Di Luar Periode',
            'body' => 'Isi kegiatan.',
            'start_date' => '2026-09-28',
            'end_date' => '2026-11-02',
        ])->assertSessionHasErrors([
            'start_date' => 'Tanggal kegiatan harus di dalam periode program "Beasiswa" (1 Okt 2026 – 31 Okt 2026).',
            'end_date',
        ]);

        $this->assertDatabaseMissing('activities', ['title' => 'Kegiatan Di Luar Periode']);
    }

    public function test_activity_within_program_period_is_saved(): void
    {
        $program = Program::factory()->create(['start_date' => '2026-10-01', 'end_date' => '2026-10-31']);

        $this->actingAs(User::factory()->create())->post('/admin/activities', [
            'program_id' => $program->id,
            'title' => 'Kegiatan Tepat Waktu',
            'body' => 'Isi kegiatan.',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ])->assertSessionHasNoErrors()->assertRedirect('/admin/activities');
    }

    public function test_program_without_period_does_not_restrict_activity_dates(): void
    {
        $program = Program::factory()->create(['start_date' => null, 'end_date' => null]);

        $this->actingAs(User::factory()->create())->post('/admin/activities', [
            'program_id' => $program->id,
            'title' => 'Kegiatan Bebas',
            'body' => 'Isi kegiatan.',
            'start_date' => '2020-01-01',
            'end_date' => '2020-01-02',
        ])->assertSessionHasNoErrors();
    }

    public function test_activity_dates_are_required(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/activities', [
            'title' => 'Kegiatan Tanpa Tanggal',
            'body' => 'Isi kegiatan.',
        ])->assertSessionHasErrors([
            'start_date' => 'Tanggal mulai kegiatan wajib diisi.',
            'end_date' => 'Tanggal selesai kegiatan wajib diisi.',
        ]);
    }

    public function test_activity_form_passes_program_periods_to_date_picker(): void
    {
        Program::factory()->create(['start_date' => '2026-10-01', 'end_date' => '2026-10-31']);

        $this->actingAs(User::factory()->create())->get('/admin/activities/create')
            ->assertOk()
            ->assertSee('activity-program-period', false)
            ->assertSee('2026-10-31', false);
    }
}
