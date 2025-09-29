<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PaymentVerifiedListener implements ShouldQueue {
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentVerified $event): void {
        $this->notificationService->notifyPaymentVerified($event->payment);
    }
}
