<?php

namespace App\Providers;

use App\Http\Controllers\DonationController;
use App\Models\Program;
use App\Models\SiteSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production' || str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        $this->registerDateFormats();

        View::composer(['public.contact', 'layouts.public'], function (\Illuminate\View\View $view): void {
            $view->with('contact', SiteSetting::contact());
        });

        View::composer('public.contact', function (\Illuminate\View\View $view): void {
            $view->with('faqs', SiteSetting::faqs());
        });

        View::composer('public.donations.partials.form', function (\Illuminate\View\View $view): void {
            $donationPrograms = Program::query()->acceptingDonations()->with('donationPackages')->orderBy('title')->get();

            $view->with([
                'donationPrograms' => $donationPrograms,
                'donationOptions' => [
                    '' => [
                        'packages' => collect(DonationController::GENERAL_PRESET_AMOUNTS)->map(fn (int $amount): array => ['name' => null, 'amount' => $amount])->all(),
                        'min' => DonationController::GENERAL_MIN_AMOUNT,
                        'allowsCustom' => true,
                    ],
                ] + $donationPrograms->mapWithKeys(fn (Program $program): array => [
                    (string) $program->id => [
                        'packages' => $program->donationPackages->map(fn ($package): array => ['name' => $package->name, 'amount' => $package->amount])->all(),
                        'min' => $program->min_donation_amount,
                        'allowsCustom' => $program->allowsCustomAmount(),
                    ],
                ])->all(),
            ]);
        });
    }

    /**
     * Format tanggal admin, disamakan dengan label kalender filter (mis. "5 Agu 2026").
     * Singkatan bulan sengaja ditulis tetap agar identik dengan formatter di resources/js/app.js.
     */
    private function registerDateFormats(): void
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        Carbon::macro('toIndonesianDate', function () use ($months): string {
            /** @var Carbon $this */
            return $this->day.' '.$months[$this->month - 1].' '.$this->year;
        });

        Carbon::macro('toIndonesianDateTime', function () use ($months): string {
            /** @var Carbon $this */
            $local = $this->copy()->setTimezone(config('app.display_timezone'));

            return $local->day.' '.$months[$local->month - 1].' '.$local->year.', '.$local->format('H:i T');
        });
    }
}
