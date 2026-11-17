<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
