<?php

namespace App\Providers;

use App\Models\Admission;
use App\Observers\AdmissionObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Admission::observe(AdmissionObserver::class);
    }
}
