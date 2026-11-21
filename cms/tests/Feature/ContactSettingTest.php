<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_falls_back_to_defaults(): void
    {
        $contact = SiteSetting::contact();

        $this->assertSame(SiteSetting::CONTACT_DEFAULTS['email'], $contact['email']);
        $this->assertSame('https://wa.me/6281200000000', $contact['whatsapp_url']);
    }

    public function test_admin_can_update_contact(): void
    {
        SiteSetting::contact();

        $this->actingAs(User::factory()->create())->put('/admin/contact', [
            'address' => 'Jl. Melati No. 1, Bandung',
            'whatsapp' => '0812-3456-7890',
            'email' => 'info@pelitabahagia.org',
            'instagram' => '@pelitabahagia.id',
            'map_url' => 'https://maps.app.goo.gl/contoh',
        ])->assertRedirect('/admin/contact');

        $contact = SiteSetting::contact();

        $this->assertSame('Jl. Melati No. 1, Bandung', $contact['address']);
        $this->assertSame('pelitabahagia.id', $contact['instagram']);
        $this->assertSame('https://wa.me/6281234567890', $contact['whatsapp_url']);
        $this->assertSame('https://maps.app.goo.gl/contoh', $contact['map_url']);
    }

    public function test_tiktok_and_facebook_are_optional_and_become_links(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/admin/contact', $this->validContact())->assertSessionHasNoErrors();

        $contact = SiteSetting::contact();
        $this->assertNull($contact['tiktok_url']);
        $this->assertNull($contact['facebook_url']);

        $this->actingAs($user)->put('/admin/contact', $this->validContact() + [
            'tiktok' => '@pelitabahagia.id',
            'facebook' => 'https://www.facebook.com/pelitabahagia',
        ])->assertSessionHasNoErrors();

        $contact = SiteSetting::contact();
        $this->assertSame('https://www.tiktok.com/@pelitabahagia.id', $contact['tiktok_url']);
        $this->assertSame('https://www.facebook.com/pelitabahagia', $contact['facebook_url']);
        $this->getJson('/api/site')->assertJsonPath('data.social.tiktok', 'https://www.tiktok.com/@pelitabahagia.id')
            ->assertJsonPath('data.social.facebook', 'https://www.facebook.com/pelitabahagia');
    }

    public function test_tiktok_and_facebook_are_validated(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/contact', $this->validContact() + [
            'tiktok' => 'pelita bahagia',
            'facebook' => 'javascript:alert(1)',
        ])->assertSessionHasErrors(['tiktok', 'facebook']);
    }

    public function test_structure_image_can_be_replaced_and_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put('/admin/contact', $this->validContact() + [
            'structure_image' => UploadedFile::fake()->image('bagan-salah.png'),
        ])->assertSessionHasNoErrors();
        $wrongUpload = SiteSetting::structureImage();

        $this->actingAs($user)->put('/admin/contact', $this->validContact() + [
            'structure_image' => UploadedFile::fake()->image('bagan.png'),
        ])->assertSessionHasNoErrors();
        $replacement = SiteSetting::structureImage();

        Storage::disk('public')->assertMissing($wrongUpload);
        Storage::disk('public')->assertExists($replacement);

        $this->actingAs($user)->put('/admin/contact', $this->validContact() + ['remove_structure_image' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertNull(SiteSetting::structureImage());
        Storage::disk('public')->assertMissing($replacement);
    }

    public function test_contact_update_is_validated(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/contact', [
            'address' => '',
            'whatsapp' => 'bukan-nomor',
            'email' => 'bukan-email',
            'instagram' => 'pelita bahagia',
            'map_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors(['address', 'whatsapp', 'email', 'instagram', 'map_url']);

        $this->assertDatabaseCount(SiteSetting::class, 0);
    }

    public function test_guest_cannot_update_contact(): void
    {
        $this->put('/admin/contact', ['address' => 'Alamat palsu'])
            ->assertRedirect('/login');

        $this->assertDatabaseCount(SiteSetting::class, 0);
    }

    public function test_admin_can_save_optional_faqs_that_appear_on_contact_page(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/contact', $this->validContact() + [
            'faqs' => [
                ['question' => 'Bagaimana cara berdonasi?', 'answer' => 'Klik tombol Donasi.'],
                ['question' => '', 'answer' => ''],
            ],
        ])->assertRedirect('/admin/contact');

        $this->assertSame([['question' => 'Bagaimana cara berdonasi?', 'answer' => 'Klik tombol Donasi.']], SiteSetting::faqs());

        $this->get('/kontak')->assertOk()
            ->assertSee('Bagaimana cara berdonasi?')
            ->assertSee('Klik tombol Donasi.');
    }

    public function test_contact_can_be_saved_without_faqs(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/contact', $this->validContact())
            ->assertSessionHasNoErrors();

        $this->assertSame([], SiteSetting::faqs());
        $this->get('/kontak')->assertOk()->assertDontSee('Pertanyaan yang Sering Diajukan');
    }

    public function test_faq_requires_both_question_and_answer(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/contact', $this->validContact() + [
            'faqs' => [['question' => 'Pertanyaan tanpa jawaban?', 'answer' => '']],
        ])->assertSessionHasErrors('faqs.0.answer');
    }

    /**
     * @return array<string, string>
     */
    private function validContact(): array
    {
        return [
            'address' => 'Jl. Melati No. 1, Bandung',
            'whatsapp' => '0812-3456-7890',
            'email' => 'info@pelitabahagia.org',
            'instagram' => 'pelitabahagia',
        ];
    }
}
