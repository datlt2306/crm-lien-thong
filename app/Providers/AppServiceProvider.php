<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            // Super admin và chủ đơn vị có thể xem finance
            if (in_array($user->role, ['super_admin', 'chủ đơn vị'])) {
                return true;
            }

            // CTV có thể xem finance của mình
            if ($user->role === 'ctv') {
                return true;
            }

            return false;
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

        // SQL debug cho các bảng pivot liên quan đến đào tạo
        DB::listen(function ($query) {
            if (Str::contains($query->sql, ['major_organization', 'organization_program'])) {
                logger()->info('SQL', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                ]);
            }
        });
    }
}
