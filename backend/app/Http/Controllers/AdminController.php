<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use App\Models\AiFeedback;
use App\Models\CvScoringProfile;
use App\Mail\ApplicationStatusChanged;
use App\Services\AI\AIOrchestratorClient;
use App\Services\CvAutoScoringService;
use App\Services\CvRubricScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    protected $cvRubricScoringService;
    protected $cvAutoScoringService;

    public function __construct(CvRubricScoringService $cvRubricScoringService, CvAutoScoringService $cvAutoScoringService)
    {
        $this->cvRubricScoringService = $cvRubricScoringService;
        $this->cvAutoScoringService = $cvAutoScoringService;
    }

    private function isAdmin(): bool
    {
        $user = Auth::user();
        return (bool) ($user && $user->role === 'admin');
    }

    private function recruiterCompanyIds(): array
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'recruiter') {
            return [];
        }

        return Company::query()->where('user_id', $user->id)->pluck('id')->all();
    }

    private function authorizeJob(Job $job): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $user = Auth::user();
        if (!$user || $user->role !== 'recruiter') {
            abort(403);
        }

        $owns = Company::query()
            ->where('id', $job->company_id)
            ->where('user_id', $user->id)
            ->exists();

        if (!$owns) {
            abort(403);
        }
    }

    private function authorizeApplication(Application $application): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $user = Auth::user();
        if (!$user || $user->role !== 'recruiter') {
            abort(403);
        }

        $job = $application->relationLoaded('job') ? $application->job : $application->job()->first();
        if (!$job) {
            abort(403);
        }

        $this->authorizeJob($job);
    }

    private function authorizeInterview(Interview $interview): void
    {
        if ($this->isAdmin()) {
            return;
        }

        $application = $interview->relationLoaded('application')
            ? $interview->application
            : $interview->application()->with('job')->first();

        if (!$application) {
            abort(403);
        }

        $this->authorizeApplication($application);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $companyIds = (!$this->isAdmin() && $user?->role === 'recruiter') ? $this->recruiterCompanyIds() : [];

        $jobBase = Job::query();
        $applicationBase = Application::query();
        $interviewBase = Interview::query();

        if (!$this->isAdmin() && $user?->role === 'recruiter') {
            $jobBase->whereIn('company_id', $companyIds);
            $applicationBase->whereHas('job', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
            $interviewBase->whereHas('application.job', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        }

        $jobCount = (clone $jobBase)->count();
        $newApplicationsCount = (clone $applicationBase)->whereDate('created_at', now()->toDateString())->count();
        $jobs = (clone $jobBase)->withCount('applications')->orderBy('created_at', 'desc')->take(10)->get();
        
        // Thống kê mở rộng
        $totalApplications = (clone $applicationBase)->count();
        $pendingApplications = (clone $applicationBase)->whereIn('status', ['submitted', 'reviewing'])->count();
        $reviewedApplications = (clone $applicationBase)->whereNotIn('status', ['submitted', 'reviewing'])->count();
        $acceptedApplications = (clone $applicationBase)->where('status', 'hired')->count();
        $rejectedApplications = (clone $applicationBase)->where('status', 'rejected')->count();
        
        // Thống kê theo ngày (7 ngày gần nhất)
        $applicationsByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $applicationsByDay[] = [
                'date' => $date->format('d/m'),
                'count' => (clone $applicationBase)->whereDate('created_at', $date->toDateString())->count()
            ];
        }
        
        // Thống kê theo tháng (6 tháng gần nhất)
        $applicationsByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $applicationsByMonth[] = [
                'month' => $date->format('m/Y'),
                'count' => (clone $applicationBase)->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count()
            ];
        }
        
        // Top jobs có nhiều ứng viên nhất
        $topJobsQuery = Job::query()->withCount('applications');
        if (!$this->isAdmin() && $user?->role === 'recruiter') {
            $topJobsQuery->whereIn('company_id', $companyIds);
        }
        $topJobs = $topJobsQuery
            ->orderByDesc('applications_count')
            ->take(5)
            ->get();
        
        // Phỏng vấn sắp tới
        $upcomingInterviewsQuery = Interview::with(['application.candidate', 'application.job'])
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ;
        if (!$this->isAdmin() && $user?->role === 'recruiter') {
            $upcomingInterviewsQuery->whereHas('application.job', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        }
        $upcomingInterviews = $upcomingInterviewsQuery
            ->orderBy('scheduled_at')
            ->take(5)
            ->get();
        
        // Ứng viên mới nhất
        $recentApplicationsQuery = Application::with(['candidate', 'job']);
        if (!$this->isAdmin() && $user?->role === 'recruiter') {
            $recentApplicationsQuery->whereHas('job', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        }
        $recentApplications = $recentApplicationsQuery
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // ── Demo seed-awareness data ──────────────────────────────────────
        $demoJobSeedInfo = [];
        if (config('app.demo_mode')) {
            $jobIds = $jobs->pluck('id')->all();
            if (!empty($jobIds)) {
                $appStats = Application::whereIn('job_id', $jobIds)
                    ->selectRaw('job_id, COUNT(*) as app_count, SUM(CASE WHEN ai_match_result IS NOT NULL THEN 1 ELSE 0 END) as ai_count')
                    ->groupBy('job_id')
                    ->get()
                    ->keyBy('job_id');

                foreach ($jobIds as $jid) {
                    $stat = $appStats->get($jid);
                    $demoJobSeedInfo[$jid] = [
                        'app_count' => $stat ? (int) $stat->app_count : 0,
                        'ai_count'  => $stat ? (int) $stat->ai_count : 0,
                    ];
                }
            }
        }

        return view('admin.dashboard', [
            'jobCount' => $jobCount,
            'newApplicationsCount' => $newApplicationsCount,
            'jobs' => $jobs,
            'totalApplications' => $totalApplications,
            'pendingApplications' => $pendingApplications,
            'reviewedApplications' => $reviewedApplications,
            'acceptedApplications' => $acceptedApplications,
            'rejectedApplications' => $rejectedApplications,
            'applicationsByDay' => $applicationsByDay,
            'applicationsByMonth' => $applicationsByMonth,
            'topJobs' => $topJobs,
            'upcomingInterviews' => $upcomingInterviews,
            'recentApplications' => $recentApplications,
            'demoJobSeedInfo' => $demoJobSeedInfo,
        ]);
    }

    public function createJob()
    {
        $user = auth()->user();
        $companies = $user?->role === 'recruiter'
            ? Company::query()->where('user_id', $user->id)->get()
            : Company::all();
        $scoringProfiles = CvScoringProfile::query()
            ->where('is_active', true)
            ->with('rubric')
            ->orderBy('rubric_id')
            ->orderBy('name')
            ->get();

        return view('admin.post-job', compact('companies', 'scoringProfiles'));
    }

    /**
     * AJAX: Check JD quality from form data (before saving).
     */
    public function checkJdQuality(Request $request)
    {
        // Build a temporary Job model from form data (not persisted)
        $job = new Job([
            'title'                => $request->input('title', ''),
            'description'          => $request->input('description', ''),
            'requirements'         => $request->input('requirements', ''),
            'required_skills'      => $request->input('required_skills', []),
            'preferred_skills'     => $request->input('preferred_skills', []),
            'seniority'            => $request->input('seniority'),
            'min_experience_years' => $request->input('min_experience_years'),
            'max_experience_years' => $request->input('max_experience_years'),
        ]);

        $result = \App\Services\AI\JdQualityChecker::analyze($job);

        return response()->json($result);
    }

    /**
     * GET: View JD quality for an existing job.
     */
    public function jobQuality($id)
    {
        $job = Job::findOrFail($id);
        $this->authorizeJob($job);

        $quality = \App\Services\AI\JdQualityChecker::analyze($job);

        return response()->json([
            'job_id'  => $job->id,
            'title'   => $job->title,
            'quality' => $quality,
        ]);
    }

    public function storeJob(Request $request)
    {
        $validated = $request->validate([
            'company_id' => ['required','exists:companies,id'],
            'cv_scoring_profile_id' => ['nullable', 'exists:cv_scoring_profiles,id'],
            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],
            'requirements' => ['nullable','string'],
            // Phase 1: structured AI matching inputs
            'required_skills' => ['nullable','array'],
            'required_skills.*' => ['string','max:100'],
            'preferred_skills' => ['nullable','array'],
            'preferred_skills.*' => ['string','max:100'],
            'seniority' => ['nullable','string','in:intern,fresher,junior,mid,senior,lead,principal'],
            'min_experience_years' => ['nullable','integer','min:0','max:30'],
            'max_experience_years' => ['nullable','integer','min:0','max:30'],
            'ai_recruiter_notes' => ['nullable','string','max:2000'],
            // Legacy fields
            'salary_min' => ['nullable','numeric'],
            'salary_max' => ['nullable','numeric'],
            'currency' => ['nullable','string','max:3'],
            'location' => ['nullable','string','max:255'],
            'status' => ['nullable','in:draft,published,closed'],
        ]);

        $user = $request->user();
        if ($user?->role === 'recruiter') {
            $ownsCompany = Company::query()
                ->where('id', $validated['company_id'])
                ->where('user_id', $user->id)
                ->exists();
            if (!$ownsCompany) {
                abort(403);
            }
        }

        // Extract structured skill arrays (will be stored as JSONB)
        $requiredSkills = $request->input('required_skills', []);
        $preferredSkills = $request->input('preferred_skills', []);

        // Build legacy requirements text from structured data for backwards compatibility
        $autoRequirements = [];
        if (!empty($requiredSkills)) {
            $autoRequirements[] = "Kỹ năng bắt buộc: " . implode(', ', $requiredSkills);
        }
        if (!empty($preferredSkills)) {
            $autoRequirements[] = "Kỹ năng ưu tiên: " . implode(', ', $preferredSkills);
        }
        $minExp = $request->input('min_experience_years');
        if ($minExp !== null && $minExp !== '') {
            $expText = $minExp == 0 ? 'Fresher' : $minExp . '+ năm kinh nghiệm';
            $autoRequirements[] = "Kinh nghiệm: " . $expText;
        }

        $finalRequirements = implode("\n", $autoRequirements);
        if (!empty($validated['requirements'])) {
            $finalRequirements .= "\n\n" . $validated['requirements'];
        }
        $validated['requirements'] = trim($finalRequirements);

        // Store structured skills as JSONB (Phase 1 schema columns)
        $validated['required_skills'] = !empty($requiredSkills) ? $requiredSkills : null;
        $validated['preferred_skills'] = !empty($preferredSkills) ? $preferredSkills : null;

        $job = Job::create(array_merge([
            'status' => $request->input('status', 'draft'),
        ], $validated));

        return redirect()->route('admin.dashboard')->with('status', 'Đăng tuyển thành công! AI sẽ tự động phân tích và xếp hạng CV ứng viên.');
    }

    public function showCvScore(Request $request, $applicationId)
    {
        // ── AI-first soft-disable ────────────────────────────────────────
        // Legacy manual rubric scoring page is no longer the primary flow.
        // Redirect to AI Shortlist instead of showing the old scoring UI.
        $application = Application::with('job')->findOrFail($applicationId);
        $this->authorizeApplication($application);

        return redirect()
            ->route('admin.jobs.ai-shortlist', $application->job_id)
            ->with('status', 'Hệ thống đã chuyển sang AI Shortlist — xếp hạng ứng viên bằng AI.');
        $this->authorizeApplication($application);

        $allActiveProfiles = CvScoringProfile::query()
            ->where('is_active', true)
            ->with('rubric')
            ->orderBy('name')
            ->get();

        $jobRubricId = $application->job->cvScoringProfile?->rubric_id;

        $preferredProfileId = $request->integer('profile_id') ?: ($application->job->cv_scoring_profile_id ?: null);
        $preferredProfile = $preferredProfileId ? $allActiveProfiles->firstWhere('id', $preferredProfileId) : null;
        if (!$preferredProfile) {
            $preferredProfile = $allActiveProfiles->first();
        }

        if (!$preferredProfile) {
            return back()->with('error', 'Chưa có scoring profile nào (cv_scoring_profiles). Hãy seed dữ liệu trước.');
        }

        $rubricIdForFiltering = $jobRubricId ?: $preferredProfile->rubric_id;
        $activeProfiles = $rubricIdForFiltering
            ? $allActiveProfiles->where('rubric_id', $rubricIdForFiltering)->values()
            : $allActiveProfiles;

        $selectedProfile = $preferredProfileId ? $activeProfiles->firstWhere('id', $preferredProfileId) : null;
        if (!$selectedProfile) {
            $selectedProfile = $activeProfiles->first();
        }

        $rubric = DB::table('cv_rubrics')->where('id', $selectedProfile->rubric_id)->first();
        if (!$rubric) {
            return back()->with('error', 'Rubric không tồn tại cho scoring profile hiện tại.');
        }

        $groups = DB::table('cv_rubric_groups')
            ->where('rubric_id', $rubric->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groupIds = $groups->pluck('id')->all();
        $criteria = DB::table('cv_rubric_criteria')
            ->whereIn('group_id', $groupIds)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $criteriaByGroup = [];
        foreach ($criteria as $c) {
            $criteriaByGroup[$c->group_id][] = [
                'code' => $c->code,
                'name' => $c->name,
                'max_score' => (int) $c->max_score,
                'rule_type' => (string) $c->rule_type,
                'rule_config' => is_string($c->rule_config) ? (json_decode($c->rule_config, true) ?: []) : (is_array($c->rule_config) ? $c->rule_config : []),
            ];
        }

        $structuredGroups = [];
        foreach ($groups as $g) {
            $structuredGroups[] = [
                'id' => $g->id,
                'code' => $g->code,
                'name' => $g->name,
                'max_score' => (int) $g->max_score,
                'criteria' => $criteriaByGroup[$g->id] ?? [],
            ];
        }

        $existingInputs = is_array($application->cv_manual_inputs) ? $application->cv_manual_inputs : [];

        return view('admin.application-score', [
            'application' => $application,
            'profiles' => $activeProfiles,
            'selectedProfile' => $selectedProfile,
            'rubric' => $rubric,
            'groups' => $structuredGroups,
            'existingInputs' => $existingInputs,
            'existingBreakdown' => is_array($application->cv_manual_breakdown) ? $application->cv_manual_breakdown : null,
        ]);
    }

    public function storeCvScore(Request $request, $applicationId)
    {
        $application = Application::with(['candidate', 'job.company'])->findOrFail($applicationId);
        $this->authorizeApplication($application);

        $profileId = $request->integer('profile_id') ?: ($application->job->cv_scoring_profile_id ?: null);
        if (!$profileId) {
            return back()->with('error', 'Vui lòng chọn scoring profile.');
        }

        $profile = CvScoringProfile::query()->where('is_active', true)->find($profileId);
        if (!$profile) {
            return back()->with('error', 'Scoring profile không hợp lệ.');
        }

        $groups = DB::table('cv_rubric_groups')
            ->where('rubric_id', $profile->rubric_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groupIds = $groups->pluck('id')->all();
        $criteriaRows = DB::table('cv_rubric_criteria')
            ->whereIn('group_id', $groupIds)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $rules = [];
        $inputKeys = [];

        foreach ($criteriaRows as $c) {
            $cfg = is_string($c->rule_config) ? (json_decode($c->rule_config, true) ?: []) : (is_array($c->rule_config) ? $c->rule_config : []);
            $type = (string) $c->rule_type;

            if ($type === 'per_unit_cap') {
                $key = (string) Arr::get($cfg, 'input_key', '');
                if ($key !== '') {
                    $inputKeys[] = $key;
                    $rules[$key] = ['nullable', 'numeric', 'min:0'];
                }
            } elseif ($type === 'weighted_two_inputs_cap') {
                $major = (string) Arr::get($cfg, 'major_input_key', '');
                $minor = (string) Arr::get($cfg, 'minor_input_key', '');
                if ($major !== '') {
                    $inputKeys[] = $major;
                    $rules[$major] = ['nullable', 'numeric', 'min:0'];
                }
                if ($minor !== '') {
                    $inputKeys[] = $minor;
                    $rules[$minor] = ['nullable', 'numeric', 'min:0'];
                }
            } elseif ($type === 'choice_map') {
                $key = (string) Arr::get($cfg, 'input_key', '');
                $choices = (array) Arr::get($cfg, 'choices', []);
                $allowed = array_keys($choices);
                if ($key !== '') {
                    $inputKeys[] = $key;
                    $rules[$key] = ['nullable', 'in:' . implode(',', $allowed)];
                }
            }
        }

        $inputKeys = array_values(array_unique(array_filter($inputKeys)));
        $validated = $request->validate($rules);

        $inputs = [];
        foreach ($inputKeys as $k) {
            $inputs[$k] = $validated[$k] ?? null;
        }

        $result = $this->cvRubricScoringService->scoreProfile($profile->key, $inputs);

        $application->cv_manual_inputs = $inputs;
        $application->cv_manual_breakdown = $result;
        $application->cv_manual_score = (float) ($result['total'] ?? 0);
        $application->cv_manual_grade = (string) ($result['grade']['label'] ?? '');
        $application->cv_manual_scored_at = now();
        $application->cv_manual_scored_by = Auth::id();
        $application->save();

        return redirect()->route('admin.applications.score', ['id' => $application->id, 'profile_id' => $profile->id])
            ->with('status', 'Đã lưu điểm chấm CV.');
    }

    /**
     * Cập nhật trạng thái job (draft/published/closed)
     */
    public function updateJobStatus(Request $request, $jobId)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:draft,published,closed'],
        ]);

        $job = Job::findOrFail($jobId);
        $this->authorizeJob($job);
        $newStatus = $validated['status'];

        $job->status = $newStatus;

        if ($newStatus === 'published' && !$job->published_at) {
            $job->published_at = now();
        }
        if ($newStatus === 'draft') {
            $job->published_at = null;
        }

        $job->save();

        return back()->with('status', 'Đã cập nhật trạng thái việc làm.');
    }

    /**
     * Xem danh sách ứng viên của một công việc
     * Sắp xếp theo cv_manual_score từ cao đến thấp
     */
    public function jobApplications($jobId)
    {
        $job = Job::findOrFail($jobId);
        $this->authorizeJob($job);
        
        $applications = Application::where('job_id', $jobId)
            ->with('candidate')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.job-applications', compact('job', 'applications'));
    }

    /**
     * Tải xuống file CV của ứng viên
     */
    public function downloadCV($applicationId)
    {
        $application = Application::with('job')->findOrFail($applicationId);
        $this->authorizeApplication($application);
        
        if (!$application->cv_file_path || !Storage::exists($application->cv_file_path)) {
            return back()->with('error', 'File CV không tồn tại.');
        }

        return Storage::download($application->cv_file_path);
    }

    /**
     * Tải xuống file minh chứng học vấn (CV nhanh)
     */
    public function downloadCvProof($applicationId, $index)
    {
        $application = Application::with('job')->findOrFail($applicationId);
        $this->authorizeApplication($application);

        $proofs = $application->cv_proof_files;
        if (!is_array($proofs)) {
            $proofs = [];
        }

        $proofPath = $proofs[(int) $index] ?? null;
        if (!$proofPath || !Storage::exists($proofPath)) {
            return back()->with('error', 'File minh chứng không tồn tại.');
        }

        return Storage::download($proofPath);
    }

    /**
     * Cập nhật trạng thái ứng viên
     */
    public function updateApplicationStatus(Request $request, $applicationId)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:submitted,reviewing,shortlisted,interviewed,rejected,hired,offered'],
            'send_email' => ['nullable', 'boolean'],
        ]);

        $application = Application::with(['candidate', 'job.company'])->findOrFail($applicationId);
        $this->authorizeApplication($application);
        $oldStatus = $application->status;
        $newStatus = $validated['status'];
        
        $application->status = $newStatus;
        $application->save();

        // Send email notification if requested and status actually changed
        if ($request->boolean('send_email', true) && $oldStatus !== $newStatus && $application->candidate?->email) {
            try {
                Mail::to($application->candidate->email)
                    ->send(new ApplicationStatusChanged($application, $oldStatus, $newStatus));
            } catch (\Exception $e) {
                \Log::warning('Failed to send status change email: ' . $e->getMessage());
            }
        }

        return back()->with('status', 'Cập nhật trạng thái thành công.');
    }

    /**
     * Recalculate CV rubric scores cho tất cả applications của một job
     */
    public function recalculateScores($jobId)
    {
        $job = Job::findOrFail($jobId);
        $this->authorizeJob($job);
        $applications = Application::where('job_id', $jobId)->get();
        $updatedCv = 0;

        foreach ($applications as $application) {
            // Auto CV rubric score (SQL-based) - always attempted
            try {
                $res = $this->cvAutoScoringService->scoreAndPersist($application);
                if ($res) {
                    $updatedCv++;
                }
            } catch (\Throwable $e) {
                \Log::warning("Failed to auto-score CV for application {$application->id}: " . $e->getMessage());
            }
        }

        return back()->with('status', "Đã cập nhật chấm CV cho {$updatedCv} ứng viên.");
    }

    /**
     * Cập nhật ghi chú cho ứng viên
     */
    public function updateNotes(Request $request, $applicationId)
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $application = Application::with('job')->findOrFail($applicationId);
        $this->authorizeApplication($application);
        $application->notes = $validated['notes'];
        $application->save();

        return back()->with('status', 'Đã lưu ghi chú.');
    }

    /**
     * So sánh các ứng viên của một công việc
     */
    public function compareCandidates(Request $request, $jobId)
    {
        // ── AI-first soft-disable ────────────────────────────────────────
        // Legacy comparison page used cv_manual_score. AI Shortlist now
        // provides ranked candidate comparison with explainability.
        $job = Job::findOrFail($jobId);
        $this->authorizeJob($job);

        return redirect()
            ->route('admin.jobs.ai-shortlist', $jobId)
            ->with('status', 'So sánh ứng viên đã được tích hợp vào AI Shortlist.');
        $this->authorizeJob($job);
        
        // Get selected application IDs from query string
        $selectedIds = $request->input('ids', []);
        if (is_string($selectedIds)) {
            $selectedIds = explode(',', $selectedIds);
        }
        
        // If no IDs provided, get top 3 by CV rubric score
        if (empty($selectedIds)) {
            $applications = Application::where('job_id', $jobId)
                ->with('candidate')
            ->orderByDesc('cv_manual_score')
                ->limit(3)
                ->get();
        } else {
            $applications = Application::where('job_id', $jobId)
                ->whereIn('id', $selectedIds)
                ->with('candidate')
            ->orderByDesc('cv_manual_score')
                ->get();
        }

        // Get all applications for selection
        $allApplications = Application::where('job_id', $jobId)
            ->with('candidate')
            ->orderByDesc('cv_manual_score')
            ->get();

        return view('admin.compare-candidates', compact('job', 'applications', 'allApplications', 'selectedIds'));
    }

    /**
     * Lên lịch phỏng vấn cho ứng viên
     */
    public function scheduleInterview(Request $request, $applicationId)
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'type' => ['required', 'in:online,onsite,phone'],
            'location' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'scheduled_at.required' => 'Vui lòng chọn thời gian phỏng vấn.',
            'scheduled_at.after' => 'Thời gian phỏng vấn phải trong tương lai.',
            'duration_minutes.required' => 'Vui lòng chọn thời lượng.',
        ]);

        $application = Application::with(['candidate', 'job'])->findOrFail($applicationId);
        $this->authorizeApplication($application);

        $interview = Interview::create([
            'application_id' => $application->id,
            'scheduled_by' => Auth::id(),
            'scheduled_at' => $validated['scheduled_at'],
            'duration_minutes' => $validated['duration_minutes'],
            'type' => $validated['type'],
            'location' => $validated['location'],
            'notes' => $validated['notes'],
            'status' => 'scheduled',
        ]);

        // Update application status to shortlisted or interviewed
        if ($application->status === 'submitted' || $application->status === 'reviewing') {
            $application->status = 'shortlisted';
            $application->save();
        }

        return back()->with('status', 'Đã lên lịch phỏng vấn thành công!');
    }

    /**
     * Danh sách tất cả lịch phỏng vấn
     */
    public function interviews(Request $request)
    {
        $query = Interview::with(['application.candidate', 'application.job', 'scheduler']);

        $user = Auth::user();
        if (!$this->isAdmin() && $user?->role === 'recruiter') {
            $companyIds = $this->recruiterCompanyIds();
            $query->whereHas('application.job', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('scheduled_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('scheduled_at', '<=', $request->to_date);
        }

        $interviews = $query->orderBy('scheduled_at', 'asc')->paginate(20);

        return view('admin.interviews', compact('interviews'));
    }

    /**
     * Cập nhật trạng thái/feedback phỏng vấn
     */
    public function updateInterview(Request $request, $interviewId)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:scheduled,completed,cancelled,rescheduled'],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $interview = Interview::with('application.job')->findOrFail($interviewId);
        $this->authorizeInterview($interview);
        
        if (isset($validated['status'])) {
            $interview->status = $validated['status'];
        }
        if (isset($validated['feedback'])) {
            $interview->feedback = $validated['feedback'];
        }
        if (isset($validated['rating'])) {
            $interview->rating = $validated['rating'];
        }
        
        $interview->save();

        // If completed, update application status
        if ($interview->status === 'completed') {
            $interview->application->update(['status' => 'interviewed']);
        }

        return back()->with('status', 'Đã cập nhật thông tin phỏng vấn.');
    }

    /**
     * Xuất danh sách ứng viên của job ra PDF
     */
    public function exportApplicationsPdf($jobId)
    {
        $job = Job::with('company')->findOrFail($jobId);
        $this->authorizeJob($job);
        
        $applications = Application::where('job_id', $jobId)
            ->with('candidate')
            ->orderByDesc('cv_manual_score')
            ->orderByDesc('created_at')
            ->get();

        $pdf = Pdf::loadView('admin.pdf.applications-list', [
            'job' => $job,
            'applications' => $applications,
            'generatedAt' => now()
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('ung-vien-' . Str::slug($job->title) . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Xuất chi tiết một ứng viên ra PDF
     */
    public function exportApplicationDetailPdf($applicationId)
    {
        $application = Application::with(['candidate', 'job.company', 'interviews'])
            ->findOrFail($applicationId);
        $this->authorizeApplication($application);

        $pdf = Pdf::loadView('admin.pdf.application-detail', [
            'application' => $application,
            'generatedAt' => now()
        ]);

        $pdf->setPaper('A4', 'portrait');

        $candidateName = $application->candidate->name ?? 'ung-vien';
        return $pdf->download('cv-' . Str::slug($candidateName) . '-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Xuất báo cáo tổng hợp tuyển dụng ra PDF
     */
    public function exportReportPdf(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subMonth()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $user = Auth::user();
        $companyIds = (!$this->isAdmin() && $user?->role === 'recruiter') ? $this->recruiterCompanyIds() : [];

        // Thống kê tổng quan
        $stats = [
            'totalJobs' => Job::whereBetween('created_at', [$fromDate, $toDate])
                ->when(!$this->isAdmin() && $user?->role === 'recruiter', function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds);
                })->count(),
            'totalApplications' => Application::whereBetween('created_at', [$fromDate, $toDate])
                ->when(!$this->isAdmin() && $user?->role === 'recruiter', function ($q) use ($companyIds) {
                    $q->whereHas('job', function ($q2) use ($companyIds) {
                        $q2->whereIn('company_id', $companyIds);
                    });
                })->count(),
            'pendingApplications' => Application::whereBetween('created_at', [$fromDate, $toDate])->whereIn('status', ['submitted', 'reviewing'])
                ->when(!$this->isAdmin() && $user?->role === 'recruiter', function ($q) use ($companyIds) {
                    $q->whereHas('job', function ($q2) use ($companyIds) {
                        $q2->whereIn('company_id', $companyIds);
                    });
                })->count(),
            'acceptedApplications' => Application::whereBetween('created_at', [$fromDate, $toDate])->where('status', 'hired')
                ->when(!$this->isAdmin() && $user?->role === 'recruiter', function ($q) use ($companyIds) {
                    $q->whereHas('job', function ($q2) use ($companyIds) {
                        $q2->whereIn('company_id', $companyIds);
                    });
                })->count(),
            'rejectedApplications' => Application::whereBetween('created_at', [$fromDate, $toDate])->where('status', 'rejected')
                ->when(!$this->isAdmin() && $user?->role === 'recruiter', function ($q) use ($companyIds) {
                    $q->whereHas('job', function ($q2) use ($companyIds) {
                        $q2->whereIn('company_id', $companyIds);
                    });
                })->count(),
            'interviewsScheduled' => Interview::whereBetween('created_at', [$fromDate, $toDate])
                ->when(!$this->isAdmin() && $user?->role === 'recruiter', function ($q) use ($companyIds) {
                    $q->whereHas('application.job', function ($q2) use ($companyIds) {
                        $q2->whereIn('company_id', $companyIds);
                    });
                })->count(),
            'interviewsCompleted' => Interview::whereBetween('created_at', [$fromDate, $toDate])->where('status', 'completed')
                ->when(!$this->isAdmin() && $user?->role === 'recruiter', function ($q) use ($companyIds) {
                    $q->whereHas('application.job', function ($q2) use ($companyIds) {
                        $q2->whereIn('company_id', $companyIds);
                    });
                })->count(),
        ];

        // Top jobs
        $topJobs = Job::query()
            ->when(!$this->isAdmin() && $user?->role === 'recruiter', function ($q) use ($companyIds) {
                $q->whereIn('company_id', $companyIds);
            })
            ->withCount(['applications' => function($q) use ($fromDate, $toDate, $companyIds, $user) {
                $q->whereBetween('created_at', [$fromDate, $toDate]);
                if ($user?->role === 'recruiter') {
                    $q->whereHas('job', function ($q2) use ($companyIds) {
                        $q2->whereIn('company_id', $companyIds);
                    });
                }
            }])
            ->orderByDesc('applications_count')
            ->take(10)
            ->get();

        // Ứng tuyển theo ngày
        $applicationsByDay = [];
        $currentDate = \Carbon\Carbon::parse($fromDate);
        $endDate = \Carbon\Carbon::parse($toDate);
        
        while ($currentDate <= $endDate) {
            $applicationsByDay[] = [
                'date' => $currentDate->format('d/m'),
                'count' => Application::whereDate('created_at', $currentDate->toDateString())
                    ->when(!$this->isAdmin() && $user?->role === 'recruiter', function ($q) use ($companyIds) {
                        $q->whereHas('job', function ($q2) use ($companyIds) {
                            $q2->whereIn('company_id', $companyIds);
                        });
                    })->count()
            ];
            $currentDate->addDay();
        }

        $pdf = Pdf::loadView('admin.pdf.recruitment-report', [
            'stats' => $stats,
            'topJobs' => $topJobs,
            'applicationsByDay' => $applicationsByDay,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'generatedAt' => now()
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('bao-cao-tuyen-dung-' . $fromDate . '-' . $toDate . '.pdf');
    }

    // =========================================================================
    // Phase 4 — AI Shortlist
    // =========================================================================

    /**
     * AI-powered shortlist for a job: ranked candidates with explainability.
     *
     * - Reuses persisted ai_match_result when available
     * - Computes on-demand for applications that have no persisted result
     * - Sorts by fit_score descending
     * - Marks each row as persisted vs fresh, and flags stale results (>7 days)
     */
    public function aiShortlist($jobId)
    {
        $job = Job::with('company')->findOrFail($jobId);
        $this->authorizeJob($job);

        $applications = Application::where('job_id', $jobId)
            ->with('candidate')
            ->get();

        $aiClient = app(AIOrchestratorClient::class);
        $shortlist = [];
        $staleThreshold = now()->subDays(7);

        foreach ($applications as $application) {
            $candidate = $application->candidate;
            if (!$candidate) {
                continue;
            }

            $aiResult = $application->ai_match_result;
            $persisted = !empty($aiResult);
            $freshlyComputed = false;

            // If no persisted result, compute on-demand
            if (!$persisted) {
                try {
                    $payload = $this->buildAiMatchPayload($candidate, $job, $application);
                    $result = $aiClient->matchCandidateToJob($payload);

                    // Persist the sanitized subset
                    $sanitized = $this->buildSanitizedAuditRecord($result);
                    $application->update(['ai_match_result' => $sanitized]);
                    $aiResult = $sanitized;
                    $persisted = true;
                    $freshlyComputed = true;
                } catch (\Throwable $e) {
                    Log::warning('AI match failed for shortlist', [
                        'application_id' => $application->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Include a clean error row — full error is already logged above
                    $shortlist[] = [
                        'application_id' => $application->id,
                        'candidate_id' => $candidate->id,
                        'candidate_name' => $candidate->name ?? 'Ứng viên #' . $candidate->id,
                        'fit_score' => null,
                        'rank_label' => 'error',
                        'confidence_label' => 'low',
                        'matched_skills' => [],
                        'missing_skills' => [],
                        'missing_preferred_skills' => [],
                        'risk_flags' => ['Không thể kết nối AI — vui lòng thử lại hoặc kiểm tra AI service'],
                        'score_breakdown' => [],
                        'retrieval_method' => 'unknown',
                        'pipeline_version' => 'unknown',
                        'generated_at' => null,
                        'persisted' => false,
                        'fresh' => false,
                        'stale' => true,
                        'error' => true,
                    ];
                    continue;
                }
            }

            $generatedAt = $aiResult['generated_at'] ?? null;
            $isStale = false;
            if ($generatedAt) {
                try {
                    $isStale = \Carbon\Carbon::parse($generatedAt)->lt($staleThreshold);
                } catch (\Throwable $e) {
                    $isStale = true;
                }
            }

            $shortlist[] = [
                'application_id' => $application->id,
                'candidate_id' => $candidate->id,
                'candidate_name' => $candidate->name ?? 'Ứng viên #' . $candidate->id,
                'fit_score' => $aiResult['fit_score'] ?? null,
                'rank_label' => $aiResult['rank_label'] ?? 'unknown',
                'confidence_label' => $aiResult['confidence_label'] ?? 'unknown',
                'matched_skills' => $aiResult['matched_skills'] ?? [],
                'missing_skills' => $aiResult['missing_skills'] ?? [],
                'missing_preferred_skills' => $aiResult['missing_preferred_skills'] ?? [],
                'risk_flags' => $aiResult['risk_flags'] ?? [],
                'score_breakdown' => $aiResult['score_breakdown'] ?? [],
                'retrieval_method' => $aiResult['retrieval_method'] ?? 'unknown',
                'pipeline_version' => $aiResult['pipeline_version'] ?? 'unknown',
                'generated_at' => $generatedAt,
                'persisted' => $persisted,
                'fresh' => $freshlyComputed,
                'stale' => $isStale,
                'error' => false,
            ];
        }

        // Sort by fit_score descending (nulls last)
        usort($shortlist, function ($a, $b) {
            if ($a['fit_score'] === null && $b['fit_score'] === null) return 0;
            if ($a['fit_score'] === null) return 1;
            if ($b['fit_score'] === null) return -1;
            return $b['fit_score'] <=> $a['fit_score'];
        });

        // Load existing recruiter feedback for this job's applications
        $feedbackMap = [];
        if (Auth::check()) {
            $appIds = collect($shortlist)->pluck('application_id')->filter()->values()->toArray();
            $feedbacks = AiFeedback::where('recruiter_id', Auth::id())
                ->whereIn('application_id', $appIds)
                ->get()
                ->keyBy('application_id');
            foreach ($feedbacks as $appId => $fb) {
                $feedbackMap[$appId] = [
                    'type' => $fb->feedback_type,
                    'note' => $fb->feedback_note,
                    'updated_at' => $fb->updated_at->format('d/m/Y H:i'),
                ];
            }
        }

        return view('admin.ai-shortlist', compact('job', 'shortlist', 'feedbackMap'));
    }

    /**
     * Refresh/re-compute AI match result for a single application.
     */
    public function refreshAiMatch($applicationId)
    {
        $application = Application::with(['candidate', 'job.company'])->findOrFail($applicationId);
        $this->authorizeApplication($application);

        $candidate = $application->candidate;
        $job = $application->job;

        if (!$candidate || !$job) {
            return back()->with('error', 'Không tìm thấy ứng viên hoặc công việc.');
        }

        try {
            $aiClient = app(AIOrchestratorClient::class);
            $payload = $this->buildAiMatchPayload($candidate, $job, $application);
            $result = $aiClient->matchCandidateToJob($payload);

            $sanitized = $this->buildSanitizedAuditRecord($result);
            $application->update(['ai_match_result' => $sanitized]);

            return back()->with('status', 'Đã cập nhật kết quả AI cho ' . ($candidate->name ?? 'ứng viên') . '.');
        } catch (\Throwable $e) {
            Log::warning('AI refresh failed', [
                'application_id' => $application->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'AI tạm thời chưa khả dụng — vui lòng kiểm tra AI service (port 8001) và thử lại. Dữ liệu hiện có vẫn được giữ nguyên.');
        }
    }

    /**
     * Build AI match payload for a candidate + job + application context.
     */
    private function buildAiMatchPayload(Candidate $candidate, Job $job, Application $application): array
    {
        $cvData = $application->cv_data ?: null;

        return [
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'summary' => $candidate->summary,
                'about_me' => $candidate->about_me,
                'skills' => $candidate->skills_json ?: $candidate->skills,
                'skills_json' => $candidate->skills_json,
                'experience' => $candidate->experience,
                'education' => $candidate->education,
                'work_experiences' => $candidate->work_experiences,
                'profile_data' => $candidate->profile_data ?: (object) [],
                'cv_data' => $cvData,
            ],
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'description' => $job->description,
                'requirements' => $job->requirements,
                'location' => $job->location,
                // Phase 1 structured AI matching inputs
                'required_skills' => $job->required_skills,
                'preferred_skills' => $job->preferred_skills,
                'seniority' => $job->seniority,
                'min_experience_years' => $job->min_experience_years,
                'max_experience_years' => $job->max_experience_years,
                'scoring_config' => $job->scoring_config,
                'ai_recruiter_notes' => $job->ai_recruiter_notes,
            ],
            'options' => [
                'include_reasoning' => true,
            ],
            'application_id' => $application->id,
        ];
    }

    /**
     * Build a sanitized audit-safe subset of the match result.
     *
     * Whitelist approach: only known-safe fields are included.
     * Excluded: candidate_profile, job_profile, reasoning, evidence excerpts, agent_trace.
     */
    private function buildSanitizedAuditRecord(array $result): array
    {
        return [
            'fit_score' => $result['fit_score'] ?? null,
            'rank_label' => $result['rank_label'] ?? null,
            'confidence_label' => $result['confidence_label'] ?? null,
            'score_breakdown' => $result['score_breakdown'] ?? [],
            'matched_skills' => $result['matched_skills'] ?? [],
            'missing_skills' => $result['missing_skills'] ?? [],
            'missing_preferred_skills' => $result['missing_preferred_skills'] ?? [],
            'related_matches' => $result['related_matches'] ?? [],
            'risk_flags' => $result['risk_flags'] ?? [],
            'retrieval_method' => $result['retrieval_method'] ?? 'unknown',
            'pipeline_version' => $result['pipeline_version'] ?? 'unknown',
            'generated_at' => $result['generated_at'] ?? now()->toIso8601String(),
        ];
    }

    /**
     * Phase 12: Store recruiter feedback on an AI shortlist result.
     *
     * Supports: agree, disagree, note, flag.
     * Uses updateOrCreate so each recruiter has at most one feedback per application.
     * Returns JSON for inline fetch() calls from the shortlist page.
     */
    public function storeAiFeedback(Request $request, $applicationId)
    {
        $request->validate([
            'feedback_type' => 'required|string|in:agree,disagree,note,flag',
            'feedback_note' => 'nullable|string|max:500',
        ]);

        $application = Application::findOrFail($applicationId);

        $feedback = AiFeedback::updateOrCreate(
            [
                'application_id' => $application->id,
                'recruiter_id'   => Auth::id(),
            ],
            [
                'job_id'        => $application->job_id,
                'feedback_type' => $request->input('feedback_type'),
                'feedback_note' => $request->input('feedback_note'),
            ]
        );

        return response()->json([
            'success' => true,
            'feedback' => [
                'type'       => $feedback->feedback_type,
                'note'       => $feedback->feedback_note,
                'updated_at' => $feedback->updated_at->format('d/m/Y H:i'),
            ],
        ]);
    }
}
