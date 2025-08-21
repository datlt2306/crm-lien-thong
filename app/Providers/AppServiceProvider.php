<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use App\Models\Collaborator;
use App\Policies\CollaboratorPolicy;
use App\Models\User;
use App\Policies\UserPolicy;

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
        Gate::policy(User::class, UserPolicy::class);

        // Define Gates for permissions
        Gate::define('view_finance', function ($user) {
            return $user->hasPermissionTo('view_finance');
        });

        Gate::define('verify_payment', function ($user) {
            return $user->hasPermissionTo('verify_payment');
        });

        Gate::define('manage_commission', function ($user) {
            return $user->hasPermissionTo('manage_commission');
        });

        Gate::define('manage_ctv', function ($user) {
            return $user->hasPermissionTo('manage_ctv');
        });

        Gate::define('manage_org', function ($user) {
            return $user->hasPermissionTo('manage_org');
        });

        Gate::define('manage_student', function ($user) {
            return $user->hasPermissionTo('manage_student');
        });
    }
}
