<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateJobController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Middleware\EnsureRecruiterHasCompany;
use App\Http\Middleware\RoleMiddleware;

// Language Switcher Route
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'vi', 'ko'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');

Route::get('/', function () {
    return redirect()->route('home');
});

// TEMPORARY DEBUG ROUTE
Route::get('/debug/logs', function () {
    $logFile = storage_path('logs/laravel.log');
    if (!file_exists($logFile)) return 'No log file';
    
    // Read last 100 lines
    $lines = file($logFile);
    return response()->json(array_slice($lines, -100));
});

// Demo routes — active only when DEMO_MODE=true (gated inside controller)
Route::get('/demo', [DemoController::class, 'landing'])->name('demo.landing');
Route::post('/demo/enter-candidate', [DemoController::class, 'enterAsCandidate'])->name('demo.enter-candidate');
Route::post('/demo/enter-recruiter', [DemoController::class, 'enterAsRecruiter'])->name('demo.enter-recruiter');
Route::post('/demo/reset', [DemoController::class, 'resetDemo'])->name('demo.reset');

// Auth routes (Guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    
    // Forgot Password routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.forgot');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetCode'])->name('password.send-code');
    Route::get('/forgot-password/verify', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.verify');
    Route::post('/forgot-password/verify', [ForgotPasswordController::class, 'verifyCode'])->name('password.verify-code');
    Route::post('/forgot-password/resend', [ForgotPasswordController::class, 'resendCode'])->name('password.resend-code');
    Route::get('/forgot-password/reset', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
    Route::get('/forgot-password/cancel', [ForgotPasswordController::class, 'cancel'])->name('password.cancel');
});

// Two-Factor Authentication routes
Route::get('/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
Route::post('/two-factor/verify', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
Route::post('/two-factor/resend', [TwoFactorController::class, 'resend'])->name('two-factor.resend');
Route::get('/two-factor/cancel', [TwoFactorController::class, 'cancel'])->name('two-factor.cancel');

// Logout (Authenticated only)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Account Settings (Authenticated users)
Route::middleware('auth')->prefix('account')->group(function () {
    Route::get('/settings', [AccountController::class, 'index'])->name('account.settings');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('account.update-profile');
    Route::put('/password', [AccountController::class, 'changePassword'])->name('account.change-password');
    Route::post('/2fa/toggle', [AccountController::class, 'toggle2FA'])->name('account.toggle-2fa');
    Route::post('/2fa/verify', [AccountController::class, 'verify2FA'])->name('account.verify-2fa');
    Route::post('/2fa/resend', [AccountController::class, 'resend2FACode'])->name('account.resend-2fa');
    Route::get('/2fa/cancel', [AccountController::class, 'cancel2FA'])->name('account.cancel-2fa');
});

// Candidate routes (Public)
Route::get('/', [CandidateJobController::class, 'index'])->name('home');
Route::get('/jobs/{id}', [CandidateJobController::class, 'show'])->name('jobs.show');
Route::post('/jobs/{id}/apply', [CandidateJobController::class, 'apply'])->name('jobs.apply');

// Candidate dashboard (Authenticated candidate)
Route::middleware([RoleMiddleware::class . ':candidate'])->prefix('candidate')->group(function () {
    Route::get('/dashboard', [CandidateJobController::class, 'dashboard'])->name('candidate.dashboard');
    Route::get('/applications', [CandidateJobController::class, 'myApplications'])->name('candidate.applications');
    // Profile removed — candidates now only upload CV files
    // Route::get('/profile', [CandidateJobController::class, 'profile'])->name('candidate.profile');
    // Route::post('/profile', [CandidateJobController::class, 'updateProfile'])->name('candidate.profile.update');

    // AI follow-up: candidate provides missing info after apply (DEMO_MODE gated in controller)
    Route::post('/jobs/{id}/ai-followup', [CandidateJobController::class, 'submitFollowup'])->name('jobs.ai-followup');
});

// Admin routes (Recruiter/Admin only)
Route::middleware([RoleMiddleware::class . ':recruiter,admin', EnsureRecruiterHasCompany::class])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');

    // Company management
    Route::get('/companies', [CompanyController::class, 'index'])->name('admin.companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('admin.companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('admin.companies.store');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->name('admin.companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->name('admin.companies.update');

    Route::get('/jobs/create', [AdminController::class, 'createJob'])->name('admin.jobs.create');
    Route::post('/jobs', [AdminController::class, 'storeJob'])->name('admin.jobs.store');
    Route::patch('/jobs/{id}/status', [AdminController::class, 'updateJobStatus'])->name('admin.jobs.update-status');
    
    // View applications for a job
    Route::get('/jobs/{id}/applications', [AdminController::class, 'jobApplications'])->name('admin.jobs.applications');
    Route::post('/jobs/{id}/recalculate', [AdminController::class, 'recalculateScores'])->name('admin.jobs.recalculate');
    
    // Bulk Upload CVs
    Route::post('/jobs/{id}/bulk-upload', [\App\Http\Controllers\BulkUploadController::class, 'store'])->name('admin.jobs.bulk-upload');
    Route::get('/bulk-upload/{batchId}/status', [\App\Http\Controllers\BulkUploadController::class, 'status'])->name('admin.bulk-upload.status');
    
    // Application actions
    Route::get('/applications/{id}/download-cv', [AdminController::class, 'downloadCV'])->name('admin.applications.download-cv');
    Route::get('/applications/{id}/download-cv-proof/{index}', [AdminController::class, 'downloadCvProof'])->whereNumber('index')->name('admin.applications.download-cv-proof');
    Route::get('/applications/{id}/score', [AdminController::class, 'showCvScore'])->name('admin.applications.score');
    Route::post('/applications/{id}/score', [AdminController::class, 'storeCvScore'])->name('admin.applications.score.store');
    Route::patch('/applications/{id}/status', [AdminController::class, 'updateApplicationStatus'])->name('admin.applications.update-status');
    Route::patch('/applications/{id}/notes', [AdminController::class, 'updateNotes'])->name('admin.applications.update-notes');
    
    // Compare candidates
    Route::get('/jobs/{id}/compare', [AdminController::class, 'compareCandidates'])->name('admin.jobs.compare');

    // Phase 4: AI Shortlist
    Route::get('/jobs/{id}/ai-shortlist', [AdminController::class, 'aiShortlist'])->name('admin.jobs.ai-shortlist');
    Route::get('/applications/{id}/ai-xray', [AdminController::class, 'aiXray'])->name('admin.applications.ai-xray');
    Route::post('/applications/{id}/ai-refresh', [AdminController::class, 'refreshAiMatch'])->name('admin.applications.ai-refresh');
    Route::post('/jobs/{id}/ai-refresh-selected', [AdminController::class, 'refreshAiMatchSelected'])->name('admin.jobs.ai-refresh-selected');
    Route::post('/applications/{id}/ai-feedback', [AdminController::class, 'storeAiFeedback'])->name('admin.applications.ai-feedback');

    // Phase 19: AI Decision Lab
    Route::get('/applications/{id}/ai-decision-lab', [AdminController::class, 'aiDecisionLab'])->name('admin.applications.ai-decision-lab');

    // AI Evaluation Dashboard
    Route::get('/ai/evaluation', [\App\Http\Controllers\AIEvaluationController::class, 'index'])->name('admin.ai-evaluation');
    Route::post('/ai/evaluation/run', [\App\Http\Controllers\AIEvaluationController::class, 'triggerRun'])->name('admin.ai-evaluation.run');
    Route::get('/ai/evaluation/status/{runId?}', [\App\Http\Controllers\AIEvaluationController::class, 'status'])->name('admin.ai-evaluation.status');

    // Phase 7: JD Quality Checker
    Route::post('/jobs/check-quality', [AdminController::class, 'checkJdQuality'])->name('admin.jobs.check-quality');
    Route::get('/jobs/{id}/quality', [AdminController::class, 'jobQuality'])->name('admin.jobs.quality');

    // Interview management
    Route::post('/applications/{id}/schedule-interview', [AdminController::class, 'scheduleInterview'])->name('admin.applications.schedule-interview');
    Route::get('/interviews', [AdminController::class, 'interviews'])->name('admin.interviews');
    Route::patch('/interviews/{id}', [AdminController::class, 'updateInterview'])->name('admin.interviews.update');
    
    // PDF Export
    Route::get('/jobs/{id}/export-pdf', [AdminController::class, 'exportApplicationsPdf'])->name('admin.jobs.export-pdf');
    Route::get('/applications/{id}/export-pdf', [AdminController::class, 'exportApplicationDetailPdf'])->name('admin.applications.export-pdf');
    Route::get('/reports/export-pdf', [AdminController::class, 'exportReportPdf'])->name('admin.reports.export-pdf');
});
