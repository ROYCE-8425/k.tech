<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetCodeMail;
use App\Models\PasswordResetCode;
use App\Models\User;
use App\Services\ITSoloLevelingSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password form (enter email)
     */
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send reset code to email
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        $email = $request->email;

        // Rate limit
        $throttleKey = 'password-reset:' . $email;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => 'Bạn đã yêu cầu quá nhiều lần. Vui lòng thử lại sau ' . $seconds . ' giây.',
            ])->withInput();
        }

        // Generate reset code
        $resetCode = PasswordResetCode::generateFor($email);

        // Always show success message (don't reveal if email exists)
        if ($resetCode) {
            $user = User::where('email', $email)->first();
            
            try {
                Mail::to($email)->send(new PasswordResetCodeMail($user, $resetCode->code));
            } catch (\Throwable $e) {
                \Log::error('[Password Reset] Failed to send email', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        RateLimiter::hit($throttleKey, 300); // 5 minutes

        // Store email in session for next step
        session(['password_reset_email' => $email]);

        return redirect()->route('password.verify')
            ->with('status', 'Nếu email tồn tại trong hệ thống, mã xác nhận đã được gửi.');
    }

    /**
     * Show verify code form
     */
    public function showVerifyForm()
    {
        if (!session('password_reset_email')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.verify-reset-code');
    }

    /**
     * Verify the reset code
     */
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => 'Vui lòng nhập mã xác nhận.',
            'code.size' => 'Mã xác nhận phải có 6 chữ số.',
        ]);

        $email = session('password_reset_email');

        if (!$email) {
            return redirect()->route('password.forgot')
                ->with('error', 'Phiên đã hết hạn. Vui lòng thử lại.');
        }

        // Rate limit verification attempts
        $throttleKey = 'password-verify:' . $email;
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'code' => 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau ' . $seconds . ' giây.',
            ]);
        }

        // Verify code
        $user = PasswordResetCode::verify($email, $request->code);

        if (!$user) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors([
                'code' => 'Mã xác nhận không đúng hoặc đã hết hạn.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Mark as verified, allow password reset
        session(['password_reset_verified' => true]);
        session(['password_reset_user_id' => $user->id]);

        return redirect()->route('password.reset');
    }

    /**
     * Show reset password form
     */
    public function showResetForm()
    {
        if (!session('password_reset_verified')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset-password');
    }

    /**
     * Reset the password
     */
    public function resetPassword(Request $request, ITSoloLevelingSecurity $security)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
        ]);

        $userId = session('password_reset_user_id');
        $email = session('password_reset_email');

        if (!$userId || !session('password_reset_verified')) {
            return redirect()->route('password.forgot')
                ->with('error', 'Phiên đã hết hạn. Vui lòng thử lại.');
        }

        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('password.forgot')
                ->with('error', 'Không tìm thấy tài khoản.');
        }

        // Update password using security service
        try {
            $user->password = $security->protect($request->password);
            $user->save();
        } catch (\Throwable $e) {
            \Log::error('[Password Reset] Failed to update password', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
        }

        // Clean up
        PasswordResetCode::markAsUsed($email);
        session()->forget(['password_reset_email', 'password_reset_verified', 'password_reset_user_id']);

        return redirect()->route('login')
            ->with('status', 'Mật khẩu đã được đặt lại thành công! Vui lòng đăng nhập.');
    }

    /**
     * Resend reset code
     */
    public function resendCode(Request $request)
    {
        $email = session('password_reset_email');

        if (!$email) {
            return redirect()->route('password.forgot');
        }

        // Rate limit
        $throttleKey = 'password-resend:' . $email;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', 'Bạn đã yêu cầu gửi lại quá nhiều lần. Vui lòng thử lại sau ' . $seconds . ' giây.');
        }

        $resetCode = PasswordResetCode::generateFor($email);

        if ($resetCode) {
            $user = User::where('email', $email)->first();
            
            try {
                Mail::to($email)->send(new PasswordResetCodeMail($user, $resetCode->code));
            } catch (\Throwable $e) {
                \Log::error('[Password Reset] Failed to resend email', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
                return back()->with('error', 'Không thể gửi email. Vui lòng thử lại sau.');
            }
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->with('status', 'Mã xác nhận mới đã được gửi.');
    }

    /**
     * Cancel password reset
     */
    public function cancel()
    {
        $email = session('password_reset_email');
        
        if ($email) {
            PasswordResetCode::markAsUsed($email);
        }

        session()->forget(['password_reset_email', 'password_reset_verified', 'password_reset_user_id']);

        return redirect()->route('login');
    }
}
