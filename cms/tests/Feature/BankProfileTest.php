<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BankProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array{bank_name: string, bank_account_number: string, bank_account_holder: string, qris_image: string}
     */
    private array $completeBank = [
        'bank_name' => 'BCA',
        'bank_account_number' => '1234567890',
        'bank_account_holder' => 'Yayasan',
        'qris_image' => 'qris/qris.png',
    ];

    public function test_admin_can_save_bank_profile_with_qris(): void
    {
        Storage::fake('public');

        Volt::actingAs(User::factory()->create())->test('profile.update-bank-information-form')
            ->set('bank_name', 'BCA')
            ->set('bank_account_number', '1234567890')
            ->set('bank_account_holder', 'Yayasan Pelita Bahagia')
            ->set('qris_image', UploadedFile::fake()->image('qris.png'))
            ->call('updateBankInformation')
            ->assertHasNoErrors();

        $bank = SiteSetting::bank();
        $this->assertSame('BCA', $bank['bank_name']);
        $this->assertTrue(SiteSetting::hasCompleteBankProfile());
        Storage::disk('public')->assertExists($bank['qris_image']);
    }

    public function test_bank_profile_is_shared_by_all_admin_accounts(): void
    {
        Storage::fake('public');

        Volt::actingAs(User::factory()->create())->test('profile.update-bank-information-form')
            ->set('bank_name', 'BRI')
            ->set('bank_account_number', '9876543210')
            ->set('bank_account_holder', 'Yayasan Pelita Bahagia')
            ->set('qris_image', UploadedFile::fake()->image('qris.png'))
            ->call('updateBankInformation')
            ->assertHasNoErrors();

        $otherAdmin = User::factory()->create();

        Volt::actingAs($otherAdmin)->test('profile.update-bank-information-form')
            ->assertSet('bank_name', 'BRI')
            ->assertSet('bank_account_number', '9876543210')
            ->assertSet('current_qris_image', SiteSetting::bank()['qris_image']);

        $this->actingAs($otherAdmin)->get('/dashboard')->assertDontSee('id="bank-profile-dialog"', false);
        $this->getJson('/api/site')->assertJsonPath('data.qris_image', SiteSetting::bank()['qris_image']);
    }

    public function test_all_bank_fields_and_qris_are_required(): void
    {
        Volt::actingAs(User::factory()->create())->test('profile.update-bank-information-form')
            ->set('bank_account_number', 'bukan-angka!')
            ->call('updateBankInformation')
            ->assertHasErrors(['bank_name' => 'required', 'bank_account_number' => 'regex', 'bank_account_holder' => 'required', 'qris_image' => 'required']);
    }

    public function test_removed_qris_must_be_replaced_before_saving(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('qris/qris-salah.png', 'gambar');
        SiteSetting::updateBank([...$this->completeBank, 'qris_image' => 'qris/qris-salah.png']);

        $component = Volt::actingAs(User::factory()->create())->test('profile.update-bank-information-form')
            ->call('removeQrisImage')
            ->call('updateBankInformation')
            ->assertHasErrors(['qris_image' => 'required']);

        $this->assertSame('qris/qris-salah.png', SiteSetting::bank()['qris_image']);

        $component->set('qris_image', UploadedFile::fake()->image('qris-benar.png'))
            ->call('updateBankInformation')
            ->assertHasNoErrors();

        Storage::disk('public')->assertMissing('qris/qris-salah.png');
        Storage::disk('public')->assertExists(SiteSetting::bank()['qris_image']);
    }

    public function test_newly_selected_qris_can_be_cancelled(): void
    {
        Storage::fake('public');

        Volt::actingAs(User::factory()->create())->test('profile.update-bank-information-form')
            ->set('qris_image', UploadedFile::fake()->image('qris-salah.png'))
            ->call('removeQrisImage')
            ->assertSet('qris_image', null)
            ->assertSet('remove_qris_image', false);
    }

    public function test_reminder_dialog_is_shown_until_bank_profile_is_complete(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertSee('id="bank-profile-dialog"', false)->assertDontSee('Nanti saja');
        $this->actingAs($user)->get('/admin/programs')->assertSee('id="bank-profile-dialog"', false);
        $this->actingAs($user)->get('/profile')->assertDontSee('id="bank-profile-dialog"', false);

        SiteSetting::updateBank($this->completeBank);

        $this->actingAs($user)->get('/dashboard')->assertDontSee('id="bank-profile-dialog"', false);
    }
}
