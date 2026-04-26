<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Payment;
use App\Models\Student;
use App\Observers\PaymentObserver;
use App\Observers\StudentObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use App\Models\Collaborator;
use App\Policies\CollaboratorPolicy;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Policies\PaymentPolicy;
use App\Events\PaymentVerified;
use App\Listeners\PaymentVerifiedListener;
use Illuminate\Support\Facades\Storage;
use Masbug\Flysystem\GoogleDriveAdapter;
use League\Flysystem\Filesystem;

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
        Gate::policy(Collaborator::class, CollaboratorPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);

        // Define Gates for permissions
        Gate::define('view_finance', function ($user) {
            // Super admin và organization_owner có thể xem finance
            if (in_array($user->role, ['super_admin', ])) {
                return true;
            }

            // CTV có thể xem finance của mình
            if ($user->role === 'collaborator') {
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

        Gate::define('manage_collaborator', function ($user) {
            return $user->hasPermissionTo('manage_collaborator');
        });


        Gate::define('manage_student', function ($user) {
            return $user->hasPermissionTo('manage_student');
        });

        // Đăng ký event listeners
        Event::listen(PaymentVerified::class, PaymentVerifiedListener::class);


        // Bust cache khi Payment thay đổi
        Payment::observe(PaymentObserver::class);
        Student::observe(StudentObserver::class);

        // Tự động tạo mối quan hệ khi User thay đổi
        User::observe(UserObserver::class);

        // Register Google Drive Storage Driver
        Storage::extend('google', function ($app, $config) {
            try {
                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                $service = new \Google\Service\Drive($client);
                $adapter = new GoogleDriveAdapter($service, '/', ['sharedFolderId' => $config['folderId'] ?? null]);
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter, $config);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Google Drive Storage Error within Driver: ' . $e->getMessage());
                throw $e;
            }
        });
    }
}
