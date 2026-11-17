<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_image_becomes_thumbnail_and_the_rest_become_gallery(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->post('/admin/activities', [
            'title' => 'Bakti Sosial',
            'body' => 'Isi.',
            'images' => [
                UploadedFile::fake()->image('satu.jpg'),
                UploadedFile::fake()->image('dua.jpg'),
                UploadedFile::fake()->image('tiga.jpg'),
            ],
        ])->assertRedirect('/admin/activities');

        $activity = Activity::with('galleryItems')->firstOrFail();

        $this->assertNotNull($activity->cover_image);
        $this->assertCount(2, $activity->galleryItems);
        $this->assertNotContains($activity->cover_image, $activity->galleryItems->pluck('image_path'));
        $this->assertCount(3, $activity->imagePaths());

        $this->get("/kegiatan/{$activity->slug}")->assertOk()
            ->assertSee(Storage::url($activity->galleryItems->first()->image_path), false);
    }

    public function test_activity_accepts_at_most_ten_images(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->post('/admin/activities', [
            'title' => 'Kegiatan',
            'body' => 'Isi.',
            'images' => array_map(fn (int $index) => UploadedFile::fake()->image("foto-{$index}.jpg"), range(1, 11)),
        ])->assertSessionHasErrors('images');

        $this->assertDatabaseCount(Activity::class, 0);
    }

    public function test_kept_images_count_towards_the_limit_when_updating(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/activities', [
            'title' => 'Kegiatan',
            'body' => 'Isi.',
            'images' => array_map(fn (int $index) => UploadedFile::fake()->image("foto-{$index}.jpg"), range(1, 9)),
        ]);

        $activity = Activity::firstOrFail();

        $this->actingAs($user)->put("/admin/activities/{$activity->slug}", [
            'title' => $activity->title,
            'slug' => $activity->slug,
            'body' => 'Isi.',
            'manage_images' => '1',
            'keep_images' => $activity->imagePaths(),
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasErrors('images');
    }

    public function test_removing_the_thumbnail_promotes_the_next_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/activities', [
            'title' => 'Kegiatan',
            'body' => 'Isi.',
            'images' => [UploadedFile::fake()->image('satu.jpg'), UploadedFile::fake()->image('dua.jpg')],
        ]);

        $activity = Activity::firstOrFail();
        [$thumbnail, $secondImage] = $activity->imagePaths();

        $this->actingAs($user)->put("/admin/activities/{$activity->slug}", [
            'title' => $activity->title,
            'slug' => $activity->slug,
            'body' => 'Isi.',
            'manage_images' => '1',
            'keep_images' => [$secondImage],
        ])->assertRedirect('/admin/activities');

        $activity->refresh();
        $this->assertSame($secondImage, $activity->cover_image);
        $this->assertCount(0, $activity->galleryItems);
        Storage::disk('public')->assertMissing($thumbnail);
        Storage::disk('public')->assertExists($secondImage);
    }

    public function test_deleting_activity_removes_all_its_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/activities', [
            'title' => 'Kegiatan',
            'body' => 'Isi.',
            'images' => [UploadedFile::fake()->image('satu.jpg'), UploadedFile::fake()->image('dua.jpg')],
        ]);

        $activity = Activity::firstOrFail();
        $paths = $activity->imagePaths();

        $this->actingAs($user)->delete("/admin/activities/{$activity->slug}")->assertRedirect('/admin/activities');

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }

        $this->assertDatabaseCount('gallery_items', 0);
    }
}
