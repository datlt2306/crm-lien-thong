<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use App\Models\Collaborator;
use App\Policies\CollaboratorPolicy;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Collaborator::class, CollaboratorPolicy::class);
    }
}
