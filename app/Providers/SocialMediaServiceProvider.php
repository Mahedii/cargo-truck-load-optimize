<?php

namespace App\Providers;

use App\Models\Page\Web\SocialMedia;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SocialMediaServiceProvider extends ServiceProvider
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
        $socialMedia = SocialMedia::first();

        // Share the $socialMedia variable with all views
        View::share('socialMediaContent', $socialMedia);
    }
}
