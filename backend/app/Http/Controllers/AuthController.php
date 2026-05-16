<?php

namespace App\Http\Controllers;

use App\Mail\TwoFactorCodeMail;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\TwoFactorCode;
use App\Models\User;
use App\Services\ITSoloLevelingSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Process login
     */
    public function login(Request $request, ITSoloLevelingSecurity $security)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');
        $throttleKey = Str::lower((string) $credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => 'Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau ' . $seconds . ' giây.',
            ])->onlyInput('email');
        }

        $user = User::where('email', $credentials['email'])->first();
        $inputPassword = (string) $credentials['password'];

        if (!$user) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors([
                'email' => 'Email hoặc mật khẩu không đúng.',
            ])->onlyInput('email');
        }

        $storedHash = (string) ($user->password ?? '');
        $authenticated = false;

        // Primary: IT Solo Leveling SHA-256 hex hash (64 chars)
        if ($storedHash !== '' && strlen($storedHash) === 64 && ctype_xdigit($storedHash)) {
            try {
                $authenticated = $security->authenticate($inputPassword, $storedHash);
            } catch (\Throwable $e) {
                $authenticated = false;
            }
        }

        // Secondary: Argon2id format
        if (!$authenticated && $storedHash !== '' && str_starts_with($storedHash, '$argon2id$')) {
            try {
                $authenticated = $security->authenticate($inputPassword, $storedHash);
            } catch (\Throwable $e) {
                $authenticated = false;
            }
        }

        // Fallback: legacy Laravel hashes (e.g., bcrypt) + upgrade on success
        if (!$authenticated && $storedHash !== '' && Hash::check($inputPassword, $storedHash)) {
            $authenticated = true;
            try {
                $user->password = $security->protect($inputPassword);
                $user->save();
            } catch (\Throwable $e) {
                // Keep legacy hash if upgrade fails.
            }
        }

        if (!$authenticated) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withErrors([
                'email' => 'Email hoặc mật khẩu không đúng.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        // ===== 2FA: Generate and send OTP if enabled =====
        if ($user->two_factor_enabled) {
            // Store user ID and remember preference in session for 2FA flow
            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:remember', $remember);

            // Generate OTP code
            $twoFactorCode = TwoFactorCode::generateFor($user);

            // Send OTP via email
            try {
                Mail::to($user->email)->send(new TwoFactorCodeMail($user, $twoFactorCode->code));
            } catch (\Throwable $e) {
                \Log::error('[2FA] Failed to send OTP email during login', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue to 2FA page anyway - user can resend
            }

            return redirect()->route('two-factor.show')
                ->with('status', 'Mã xác thực đã được gửi đến email của bạn.');
        }

        // No 2FA - login directly
        Auth::login($user, $remember);
        $request->session()->regenerate();
        session(['2fa:verified' => true]); // Mark as verified for consistency

        if ($user->role === 'admin') {
            return redirect()->intended('/admin/dashboard');
        }

        if ($user->role === 'recruiter') {
            if ($this->recruiterNeedsOnboarding($user)) {
                return redirect()->route('admin.companies.create')
                    ->with('status', 'Chào mừng bạn! Hãy tạo thông tin công ty để bắt đầu đăng tuyển.');
            }

            return redirect()->intended('/admin/dashboard');
        }

        // Candidate: ensure profile record exists and guide onboarding.
        $this->ensureCandidateRecord($user);
        if ($this->candidateNeedsOnboarding($user)) {
            return redirect()->route('candidate.profile')
                ->with('status', 'Chào mừng bạn! Hãy hoàn thiện hồ sơ để bắt đầu ứng tuyển.');
        }

        return redirect()->intended('/');
    }

    /**
     * Show register form
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Process registration (public: candidate-only)
     */
    public function register(Request $request, ITSoloLevelingSecurity $security)
    {
        $validated = $request->validate([
            'role' => 'required|in:candidate,recruiter',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ], [
            'role.required' => 'Vui lòng chọn vai trò đăng ký.',
            'role.in' => 'Vai trò không hợp lệ.',
            'name.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'terms.accepted' => 'Vui lòng đồng ý với điều khoản sử dụng.',
        ]);

        try {
            $protectedPassword = $security->protect((string) $validated['password']);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'password' => 'Hệ thống bảo mật chưa được cấu hình. Vui lòng thử lại sau.',
            ])->onlyInput('name', 'email', 'phone');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $protectedPassword,
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'company_id' => null,
        ]);

        // This project currently does not implement email verification routes/notifications.
        if (is_null($user->email_verified_at)) {
            $user->email_verified_at = now();
            $user->save();
        }

        Auth::login($user);

        if ($user->role === 'recruiter') {
            return redirect()->route('admin.companies.create')
                ->with('status', 'Đăng ký thành công! Hãy tạo thông tin công ty để bắt đầu đăng tuyển.');
        }

        $this->ensureCandidateRecord($user);
        return redirect()->route('candidate.profile')
            ->with('status', 'Đăng ký thành công! Hãy hoàn thiện hồ sơ để bắt đầu ứng tuyển.');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Đăng xuất thành công!');
    }

    private function ensureCandidateRecord(User $user): Candidate
    {
        return Candidate::updateOrCreate(
            ['email' => $user->email],
            [
                'user_id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
            ]
        );
    }

    private function candidateNeedsOnboarding(User $user): bool
    {
        $candidate = Candidate::where('user_id', $user->id)->first();
        if (!$candidate) {
            return true;
        }

        if (empty($candidate->sector)) {
            return true;
        }

        $profileData = $candidate->profile_data;
        if (!is_array($profileData) || count($profileData) === 0) {
            return true;
        }

        return false;
    }

    private function recruiterNeedsOnboarding(User $user): bool
    {
        return !Company::where('user_id', $user->id)->exists();
    }
}
