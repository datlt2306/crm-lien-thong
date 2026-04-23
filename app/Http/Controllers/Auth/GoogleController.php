<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user exists with this email
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // Update Google-specific fields and sync name/avatar
                $user->update([
                    'name' => $googleUser->name, // Cập nhật tên từ Google
                    'google_id' => $googleUser->id,
                    'google_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                    'google_avatar' => $googleUser->avatar,
                    'avatar' => $googleUser->avatar, // Cập nhật luôn ảnh đại diện hiển thị
                ]);

                // Log the user in with explicit guard
                Auth::guard('web')->login($user, true);

                // Clear session intended to avoid loops
                session()->forget('url.intended');

                return redirect('/admin');
            }

            return redirect('/admin/login')->withErrors([
                'email' => 'Tài khoản ' . $googleUser->email . ' chưa được cấp quyền trên hệ thống.',
            ]);

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google Login Error: ' . $e->getMessage());
            return redirect('/admin/login')->withErrors([
                'email' => 'Lỗi kết nối Google: ' . $e->getMessage(),
            ]);
        }
    }
}
