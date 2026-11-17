<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class BankProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_bank_profile_with_qris(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('profile.update-bank-information-form')
            ->set('bank_name', 'BCA')
            ->set('bank_account_number', '1234567890')
            ->set('bank_account_holder', 'Yayasan Pelita Bahagia')
            ->set('qris_image', UploadedFile::fake()->image('qris.png'))
            ->call('updateBankInformation')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('BCA', $user->bank_name);
        $this->assertTrue($user->hasCompletedBankProfile());
        Storage::disk('public')->assertExists($user->qris_image);
    }

    public function test_all_bank_fields_and_qris_are_required(): void
    {
        $this->actingAs(User::factory()->create());

        Volt::test('profile.update-bank-information-form')
            ->set('bank_account_number', 'bukan-angka!')
            ->call('updateBankInformation')
            ->assertHasErrors(['bank_name' => 'required', 'bank_account_number' => 'regex', 'bank_account_holder' => 'required', 'qris_image' => 'required']);
    }

    public function test_reminder_dialog_is_shown_until_bank_profile_is_complete(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertSee('id="bank-profile-dialog"', false);
        $this->actingAs($user)->get('/profile')->assertDontSee('id="bank-profile-dialog"', false);

        $user->update([
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Yayasan',
            'qris_image' => 'qris/qris.png',
        ]);

        $this->actingAs($user)->get('/dashboard')->assertDontSee('id="bank-profile-dialog"', false);
    }
}
