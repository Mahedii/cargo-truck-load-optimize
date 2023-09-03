<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\Page\Contact\ContactUsInformations;

class ContactUsInformationServiceProvider extends ServiceProvider
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
        $contactUsInformationsContent = ContactUsInformations::first();

        // Share the $contactUsInformationsContent variable with all views
        View::share('contactUsInformationsContent', $contactUsInformationsContent);
    }
}
