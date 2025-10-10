<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\CaseCreated;
use App\Events\CaseStatusChanged;
use App\Events\InvestmentCreated;
use App\Listeners\SendCaseCreatedNotification;
use App\Listeners\SendCaseStatusChangedNotification;
use App\Listeners\SendInvestmentCreatedNotification;

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
        // Registrar listeners para los eventos
        Event::listen(
            CaseCreated::class,
            SendCaseCreatedNotification::class,
        );

        Event::listen(
            CaseStatusChanged::class,
            SendCaseStatusChangedNotification::class,
        );

        Event::listen(
            InvestmentCreated::class,
            SendInvestmentCreatedNotification::class,
        );
    }
}
