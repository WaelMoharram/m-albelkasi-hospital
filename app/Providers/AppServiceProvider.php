<?php

namespace App\Providers;

use App\Models\Admission;
use App\Observers\AdmissionObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Admission::observe(AdmissionObserver::class);
    }
}
