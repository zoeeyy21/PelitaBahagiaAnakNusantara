<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Donation;
use App\Models\GalleryItem;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Pemetaan perilaku CMS ke requirement SRS v0.3.
 */
class CmsRequirementsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * NFR 20.3 — seluruh route CMS terlindungi autentikasi.
     */
    public function test_every_cms_route_requires_authentication(): void
    {
        $unprotectedRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_starts_with($route->uri(), 'admin') || $route->uri() === 'dashboard')
            ->reject(fn ($route): bool => in_array('auth', $route->gatherMiddleware(), true))
            ->map(fn ($route): string => implode('|', $route->methods()).' '.$route->uri())
            ->values()
            ->all();

        $this->assertSame([], $unprotectedRoutes);

        $program = Program::factory()->create();

        $this->delete("/admin/programs/{$program->slug}")->assertRedirect('/login');
        $this->assertModelExists($program);
    }

    /**
     * FR-ADMIN-003 — dashboard menampilkan jumlah konten.
     */
    public function test_dashboard_shows_content_counts(): void
    {
        Program::factory()->count(3)->create();
        Activity::factory()->count(2)->create();
        Donation::factory()->count(5)->create();

        $html = $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertDontSee('Statistik Dampak')
            ->getContent();

        $this->assertMatchesRegularExpression('/Program<\/p>\s*<p[^>]*>3</', $html);
        $this->assertMatchesRegularExpression('/Kegiatan<\/p>\s*<p[^>]*>2</', $html);
        $this->assertMatchesRegularExpression('/Donasi<\/p>\s*<p[^>]*>5</', $html);
    }

    /**
     * FR-PROGRAM-003 — status program menentukan tampil atau tidak di publik.
     */
    public function test_program_status_controls_public_visibility(): void
    {
        Program::factory()->create(['title' => 'Program Aktif', 'slug' => 'program-aktif', 'status' => 'active']);
        Program::factory()->create(['title' => 'Program Nonaktif', 'slug' => 'program-nonaktif', 'status' => 'inactive', 'published_at' => null]);

        $this->get('/program')->assertOk()
            ->assertSee('Program Aktif')
            ->assertDontSee('Program Nonaktif');

        $this->get('/program/program-aktif')->assertOk();
        $this->get('/program/program-nonaktif')->assertNotFound();
    }

    /**
     * FR-PROGRAM-002 — detail program memuat tujuan, sasaran, kegiatan, dan dokumentasi.
     */
    public function test_program_detail_shows_goal_activities_and_documentation(): void
    {
        $program = Program::factory()->create(['goal' => 'Tujuan program ini', 'beneficiaries' => 'Anak usia sekolah']);
        Activity::factory()->create(['program_id' => $program->id, 'title' => 'Kegiatan Terkait Program']);
        Activity::factory()->create(['program_id' => $program->id, 'title' => 'Kegiatan Draft Program', 'status' => 'draft']);
        GalleryItem::factory()->create(['program_id' => $program->id, 'title' => 'Foto Program']);
        GalleryItem::factory()->create(['program_id' => $program->id, 'title' => 'Foto Program Tersembunyi', 'is_published' => false]);

        $this->get("/program/{$program->slug}")->assertOk()
            ->assertSee('Tujuan program ini')
            ->assertSee('Anak usia sekolah')
            ->assertSee('Kegiatan Terkait Program')
            ->assertDontSee('Kegiatan Draft Program')
            ->assertSee('Foto Program')
            ->assertDontSee('Foto Program Tersembunyi');
    }

    /**
     * Kegiatan tidak lagi memiliki status atau tanggal publikasi, sehingga yang dibuat dari CMS langsung tampil.
     */
    public function test_activity_created_in_cms_is_immediately_public(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/activities', [
            'title' => 'Kegiatan Baru',
            'body' => 'Isi kegiatan baru.',
        ])->assertRedirect('/admin/activities');

        $this->get('/kegiatan')->assertOk()->assertSee('Kegiatan Baru');
        $this->get('/kegiatan/kegiatan-baru')->assertOk();
    }

    /**
     * FR-ACT-002 / FR-GALLERY-004 — detail kegiatan memuat program terkait dan dokumentasi.
     */
    public function test_activity_detail_shows_related_program_and_documentation(): void
    {
        $program = Program::factory()->create(['title' => 'Program Induk']);
        $activity = Activity::factory()->create(['program_id' => $program->id]);
        GalleryItem::factory()->create(['activity_id' => $activity->id, 'title' => 'Foto Kegiatan']);

        $this->get("/kegiatan/{$activity->slug}")->assertOk()
            ->assertSee($activity->title)
            ->assertSee('Program Induk')
            ->assertSee(route('programs.show', $program), false)
            ->assertSee('Foto Kegiatan');
    }

    public function test_activity_detail_hides_link_to_unpublished_program(): void
    {
        $program = Program::factory()->create(['title' => 'Program Masih Draft', 'status' => 'inactive']);
        $activity = Activity::factory()->create(['program_id' => $program->id]);

        $this->get("/kegiatan/{$activity->slug}")->assertOk()
            ->assertDontSee('Program Masih Draft');
    }

    /**
     * FR-GALLERY-003 — media non-aktif tidak tampil dan media terbaru tampil lebih dulu.
     */
    public function test_gallery_respects_visibility_and_shows_newest_first(): void
    {
        GalleryItem::factory()->create(['title' => 'Foto Lama', 'created_at' => now()->subDay()]);
        GalleryItem::factory()->create(['title' => 'Foto Baru', 'created_at' => now()]);
        GalleryItem::factory()->create(['title' => 'Foto Tersembunyi', 'is_published' => false]);

        $this->get('/galeri')->assertOk()
            ->assertSeeInOrder(['Foto Baru', 'Foto Lama'])
            ->assertDontSee('Foto Tersembunyi');
    }

    /**
     * FR-ADMIN-008 / FR-CONTACT-001..003 — kontak dari CMS tampil di halaman Kontak dan footer.
     */
    public function test_contact_managed_in_cms_appears_publicly(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/contact', [
            'address' => 'Jl. Kenanga 99, Bandung',
            'whatsapp' => '0811-2222-3333',
            'email' => 'kontak@pelitabahagia.org',
            'instagram' => 'pelitabahagia',
            'map_url' => 'https://maps.app.goo.gl/contoh',
        ])->assertRedirect('/admin/contact');

        $html = $this->get('/kontak')->assertOk()
            ->assertSee('Jl. Kenanga 99, Bandung')
            ->assertSee('https://wa.me/6281122223333', false)
            ->assertSee('kontak@pelitabahagia.org')
            ->assertSee('https://maps.app.goo.gl/contoh', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, '<form'), 'Halaman kontak hanya boleh memuat form dialog donasi.');
        $this->assertStringContainsString('action="'.route('donations.store').'"', $html);

        $this->get('/program')->assertOk()->assertSee('0811-2222-3333');
    }

    /**
     * FR-ADMIN-009 — gambar kegiatan bisa diganti; file lama yang dihapus ikut terhapus dari storage.
     */
    public function test_activity_cover_can_be_replaced(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/activities', [
            'title' => 'Kegiatan Bergambar',
            'body' => 'Isi.',
            'images' => [UploadedFile::fake()->image('lama.jpg')],
        ])->assertRedirect('/admin/activities');

        $activity = Activity::firstOrFail();
        $oldCover = $activity->cover_image;

        $this->actingAs($user)->put("/admin/activities/{$activity->slug}", [
            'title' => $activity->title,
            'slug' => $activity->slug,
            'body' => 'Isi.',
            'manage_images' => '1',
            'images' => [UploadedFile::fake()->image('baru.png')],
        ])->assertRedirect('/admin/activities');

        Storage::disk('public')->assertMissing($oldCover);
        Storage::disk('public')->assertExists($activity->refresh()->cover_image);
    }

    /**
     * NFR 20.3 — upload dibatasi: SVG, skrip berekstensi gambar, dan file > 2 MB ditolak.
     */
    public function test_activity_image_upload_rejects_unsafe_files(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $disguisedScript = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($disguisedScript, '<?php system($_GET["c"]); ?>');

        $files = [
            'svg' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'),
            'php-berekstensi-jpg' => new UploadedFile($disguisedScript, 'foto.jpg', null, null, true),
            'lebih-dari-2mb' => UploadedFile::fake()->image('besar.jpg')->size(3000),
        ];

        foreach ($files as $file) {
            $this->actingAs($user)
                ->post('/admin/activities', ['title' => 'Kegiatan', 'body' => 'Isi.', 'images' => [$file]])
                ->assertSessionHasErrors('images.0');
        }

        $this->assertDatabaseCount(Activity::class, 0);
    }

    /**
     * NFR 20.3 — konten dari CMS di-escape di halaman publik (XSS).
     */
    public function test_cms_content_is_escaped_on_public_pages(): void
    {
        $activity = Activity::factory()->create(['body' => '<script>alert("xss")</script>']);

        $this->get("/kegiatan/{$activity->slug}")->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    /**
     * FR-SEO-002 — slug ramah pengguna dibuat otomatis dari judul.
     */
    public function test_program_slug_is_generated_from_title(): void
    {
        $this->actingAs(User::factory()->create())->post('/admin/programs', [
            'title' => 'Sekolah Ceria & Sehat 2026!',
            'summary' => 'Ringkasan.',
            'description' => 'Deskripsi.',
            'status' => 'active',
            'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        ])->assertRedirect('/admin/programs');

        $this->get('/program/sekolah-ceria-sehat-2026')->assertOk();
    }
}
