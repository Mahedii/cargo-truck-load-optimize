<?php

namespace App\Providers;

use App\Models\Page\Web\MetaTags;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MetaTagsServiceProvider extends ServiceProvider
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
        $metaTagsContent = MetaTags::first();

        // Share the $metaTagsContent variable with all views
        View::share('metaTagsContent', $metaTagsContent);
    }
}
