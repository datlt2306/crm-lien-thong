<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicStudentController;
use App\Http\Controllers\PublicCollaboratorController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\CollaboratorRegistrationController;

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
    return redirect('/admin/login');
});

// Notification demo page (removed)



Route::get('/ref/{ref_id}', [PublicStudentController::class, 'showForm'])->name('public.ref.form');
Route::post('/ref/{ref_id}', [PublicStudentController::class, 'submitForm'])->middleware('throttle:10,1')->name('public.ref.submit');
// Alias rõ ràng cho luồng học viên
Route::get('/ref/{ref_id}/student', [PublicStudentController::class, 'showForm'])->name('public.ref.student.form');
Route::post('/ref/{ref_id}/student', [PublicStudentController::class, 'submitForm'])->middleware('throttle:10,1')->name('public.ref.student.submit');

// Upload bill/payment
Route::get('/ref/{ref_id}/payment', [PublicStudentController::class, 'showPaymentForm'])->name('public.ref.payment.form');
Route::post('/ref/{ref_id}/payment', [PublicStudentController::class, 'submitPayment'])->middleware('throttle:10,1')->name('public.ref.payment.submit');

// Tra cứu hồ sơ sinh viên theo mã hồ sơ
Route::get('/track-profile', [PublicStudentController::class, 'showProfileTracking'])->name('public.profile.track.form');
Route::get('/track/{profile_code}', [PublicStudentController::class, 'showProfileTracking'])->name('public.profile.track');

// Đã loại bỏ hoàn toàn tính năng tự đăng ký cộng tác viên
// Route::get('/collaborator/register', ...);
// Route::post('/collaborator/register', ...);
// Route::post('/collaborator/check-status', ...);
// Route::get('/collaborators/register', ...);
// Route::post('/collaborators/register', ...);
// Route::get('/ctv/register', ...);
// Route::post('/ctv/register', ...);
// Đã loại bỏ route tuyển CTV tuyến dưới - hệ thống chỉ còn 1 cấp

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
