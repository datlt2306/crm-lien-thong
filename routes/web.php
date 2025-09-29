<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicStudentController;
use App\Http\Controllers\PublicCollaboratorController;
use App\Http\Controllers\FileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Notification demo page
Route::get('/notification-demo', function () {
    return view('notification-demo');
});



Route::get('/ref/{ref_id}', [PublicStudentController::class, 'showForm'])->name('public.ref.form');
Route::post('/ref/{ref_id}', [PublicStudentController::class, 'submitForm'])->name('public.ref.submit');
// Alias rõ ràng cho luồng học viên
Route::get('/ref/{ref_id}/student', [PublicStudentController::class, 'showForm'])->name('public.ref.student.form');
Route::post('/ref/{ref_id}/student', [PublicStudentController::class, 'submitForm'])->name('public.ref.student.submit');

// Upload bill/payment
Route::get('/ref/{ref_id}/payment', [PublicStudentController::class, 'showPaymentForm'])->name('public.ref.payment.form');
Route::post('/ref/{ref_id}/payment', [PublicStudentController::class, 'submitPayment'])->name('public.ref.payment.submit');

// Đăng ký tài khoản Cộng tác viên (public)
Route::get('/ctv/register', [PublicCollaboratorController::class, 'showRegisterForm'])->name('public.ctv.register.form');
Route::post('/ctv/register', [PublicCollaboratorController::class, 'submitRegister'])->name('public.ctv.register.submit');
// Link ref dành cho tuyển CTV tuyến dưới
Route::get('/ref/{ref_id}/ctv', [PublicCollaboratorController::class, 'showRefRegister'])->name('public.ref.ctv.form');
Route::post('/ref/{ref_id}/ctv', [PublicCollaboratorController::class, 'submitRefRegister'])->name('public.ref.ctv.submit');

// Route để xem bill (cần authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/files/bill/{paymentId}', [FileController::class, 'viewBill'])->name('files.bill.view');
    Route::get('/files/receipt/{paymentId}', [FileController::class, 'viewReceipt'])->name('files.receipt.view');
    Route::get('/files/commission-bill/{commissionItemId}', [FileController::class, 'viewCommissionBill'])->name('files.commission-bill.view');

    // Notification routes
    Route::post('/admin/notifications/{id}/mark-read', function ($id) {
        $user = auth()->user();
        $notification = $user->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 404);
    })->name('notifications.mark-read');

    Route::post('/admin/notifications/mark-all-read', function () {
        $user = auth()->user();
        $user->unreadNotifications()->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    })->name('notifications.mark-all-read');
});
