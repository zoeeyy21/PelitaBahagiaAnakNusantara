<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Donation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminViewModeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, class-string}>
     */
    public static function cardDefaultPages(): array
    {
        return [
            'program' => ['/admin/programs', Program::class],
            'kegiatan' => ['/admin/activities', Activity::class],
        ];
    }

    #[DataProvider('cardDefaultPages')]
    public function test_pages_default_to_card_view(string $uri, string $model): void
    {
        $model::factory()->create();

        $this->actingAs(User::factory()->create())->get($uri)
            ->assertOk()
            ->assertViewHas('viewMode', 'card')
            ->assertDontSee('<table', false);
    }

    #[DataProvider('cardDefaultPages')]
    public function test_list_view_is_remembered_for_later_visits(string $uri, string $model): void
    {
        $user = User::factory()->create();
        $model::factory()->create();

        $this->actingAs($user)->get("{$uri}?view=list")
            ->assertOk()
            ->assertSee('<table', false);

        $this->actingAs($user)->get($uri)
            ->assertViewHas('viewMode', 'list');
    }

    public function test_view_choice_is_remembered_per_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/programs?view=list');

        $this->actingAs($user)->get('/admin/activities')
            ->assertViewHas('viewMode', 'card');
    }

    public function test_donations_page_always_uses_list_view(): void
    {
        Donation::factory()->create();

        $this->actingAs(User::factory()->create())->get('/admin/donations?view=card')
            ->assertOk()
            ->assertSee('<table', false);
    }

    public function test_unknown_view_mode_falls_back_to_page_default(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/programs?view=grid')
            ->assertViewHas('viewMode', 'card');
    }
}
