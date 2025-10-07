<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Payment;
use App\Observers\PaymentObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use App\Models\Collaborator;
use App\Policies\CollaboratorPolicy;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Policies\PaymentPolicy;
use App\Events\PaymentVerified;
use App\Listeners\PaymentVerifiedListener;

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
        Gate::policy(Payment::class, PaymentPolicy::class);

        // Define Gates for permissions
        Gate::define('view_finance', function ($user) {
            // Super admin và organization_owner có thể xem finance
            if (in_array($user->role, ['super_admin', 'organization_owner'])) {
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

        // Đăng ký event listeners
        Event::listen(PaymentVerified::class, PaymentVerifiedListener::class);

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

        // Bust cache khi Payment thay đổi
        Payment::observe(PaymentObserver::class);
    }
}
