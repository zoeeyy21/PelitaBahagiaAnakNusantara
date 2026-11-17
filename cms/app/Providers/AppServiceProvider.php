<?php

namespace App\Providers;

use App\Http\Controllers\DonationController;
use App\Models\Program;
use App\Models\SiteSetting;
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
}
