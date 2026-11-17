<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Donation;
use App\Models\GalleryItem;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminCmsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function adminPages(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'program' => ['/admin/programs'],
            'kegiatan' => ['/admin/activities'],
            'kontak' => ['/admin/contact'],
            'donasi' => ['/admin/donations'],
            'statistik' => ['/admin/statistics'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_guest_is_redirected_from_cms(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }

    #[DataProvider('adminPages')]
    public function test_admin_can_open_cms_pages(string $uri): void
    {
        Program::factory()->create();
        Activity::factory()->create();
        GalleryItem::factory()->create();
        Donation::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get($uri)
            ->assertOk();
    }

    public function test_admin_can_create_program_with_cover(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->post('/admin/programs', [
            'title' => 'Program Baru',
            'description' => 'Deskripsi program baru.',
            'status' => 'active',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ])->assertRedirect('/admin/programs');

        $program = Program::where('slug', 'program-baru')->firstOrFail();

        $this->assertSame('active', $program->status);
        $this->assertSame('Deskripsi program baru.', $program->summary);
        $this->assertNotNull($program->published_at);
        Storage::disk('public')->assertExists($program->cover_image);
    }

    public function test_program_slug_is_made_unique(): void
    {
        Program::factory()->create(['title' => 'Program Baru', 'slug' => 'program-baru']);

        $this->actingAs(User::factory()->create())->post('/admin/programs', [
            'title' => 'Program Baru',
            'description' => 'Deskripsi.',
            'status' => 'inactive',
        ])->assertRedirect('/admin/programs');

        $this->assertDatabaseHas(Program::class, ['slug' => 'program-baru-2']);
    }

    public function test_admin_can_update_and_delete_program(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create();

        $this->actingAs($user)->put("/admin/programs/{$program->slug}", [
            'title' => 'Judul Diubah',
            'slug' => $program->slug,
            'description' => 'Deskripsi diubah.',
            'status' => 'inactive',
        ])->assertRedirect('/admin/programs');

        $program->refresh();
        $this->assertSame('Judul Diubah', $program->title);
        $this->assertSame('inactive', $program->status);
        $this->assertSame('Deskripsi diubah.', $program->summary);

        $this->actingAs($user)->delete("/admin/programs/{$program->slug}")
            ->assertRedirect('/admin/programs');

        $this->assertModelMissing($program);
    }

    public function test_program_cover_must_be_an_image(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->post('/admin/programs', [
            'title' => 'Program Baru',
            'description' => 'Deskripsi.',
            'status' => 'active',
            'cover_image' => UploadedFile::fake()->create('malware.php', 10, 'application/x-php'),
        ])->assertSessionHasErrors('cover_image');

        $this->assertDatabaseCount(Program::class, 0);
    }

    public function test_admin_can_manage_activity_lifecycle(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create();

        $this->actingAs($user)->post('/admin/activities', [
            'program_id' => $program->id,
            'title' => 'Bakti Sosial Ramadan',
            'body' => 'Isi kegiatan.',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-03',
        ])->assertRedirect('/admin/activities');

        $activity = Activity::where('slug', 'bakti-sosial-ramadan')->firstOrFail();
        $this->assertSame($program->id, $activity->program_id);
        $this->assertSame('Isi kegiatan.', $activity->excerpt);
        $this->assertNotNull($activity->published_at);
        $this->assertSame('2026-10-01', $activity->start_date->toDateString());
        $this->assertSame('2026-10-03', $activity->end_date->toDateString());

        $this->actingAs($user)->put("/admin/activities/{$activity->slug}", [
            'title' => $activity->title,
            'slug' => $activity->slug,
            'body' => 'Isi kegiatan diubah.',
        ])->assertRedirect('/admin/activities');

        $this->assertSame('Isi kegiatan diubah.', $activity->refresh()->excerpt);

        $this->actingAs($user)->delete("/admin/activities/{$activity->slug}")
            ->assertRedirect('/admin/activities');

        $this->assertModelMissing($activity);
    }

    public function test_manual_activity_slug_is_normalized(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs(User::factory()->create())->put("/admin/activities/{$activity->slug}", [
            'title' => $activity->title,
            'slug' => 'Bakti Sosial Ramadan',
            'body' => $activity->body,
        ])->assertRedirect('/admin/activities');

        $this->assertSame('bakti-sosial-ramadan', $activity->refresh()->slug);
    }

    public function test_program_rejects_unknown_status(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/programs', [
            'title' => 'Program',
            'description' => 'Deskripsi.',
            'status' => 'draft',
        ])->assertSessionHasErrors('status');
    }

    public function test_admin_can_search_donations(): void
    {
        $program = Program::factory()->create(['title' => 'Beasiswa Yatim']);
        Donation::factory()->create(['donor_name' => 'Budi Santoso', 'reference_code' => 'YPB-AAA']);
        Donation::factory()->create(['donor_name' => 'Siti Aminah', 'reference_code' => 'YPB-BBB', 'program_id' => $program->id]);

        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/donations?search=budi')
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Siti Aminah');

        $this->actingAs($admin)->get('/admin/donations?search=Beasiswa')
            ->assertOk()
            ->assertSee('Siti Aminah')
            ->assertDontSee('Budi Santoso');
    }
}
