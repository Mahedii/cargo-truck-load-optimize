<?php

namespace App\Providers;

use App\Models\Page\Web\WebAssets;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class WebAssetsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Fetch the meta content from the database
        $webAssets = WebAssets::first();

        // Share the $webAssets variable with all views
        View::share('webAssetsContent', $webAssets);
    }
}
