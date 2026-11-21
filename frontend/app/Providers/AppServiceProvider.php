<?php

namespace App\Providers;

use App\Services\CmsClient;
use Illuminate\Support\Facades\Blade;
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

        Blade::directive('cms_asset', function ($expression) {
            return "<?php echo \App\Services\CmsClient::assetUrl({$expression}); ?>";
        });

        View::composer('*', function ($view) {
            $view->with('site', cciSite());
        });
    }
}
