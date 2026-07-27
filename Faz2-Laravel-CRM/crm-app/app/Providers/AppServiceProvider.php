<?php

namespace App\Providers;

use App\Actions\Company\CreateCompanyAction;
use App\Actions\Company\UpdateCompanyAction;
use App\Contracts\Company\CreatesCompany;
use App\Contracts\Company\UpdatesCompany;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CreatesCompany::class, CreateCompanyAction::class);
        $this->app->bind(UpdatesCompany::class, UpdateCompanyAction::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
