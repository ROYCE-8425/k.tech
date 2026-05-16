<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Candidate;
use App\Models\Application;
use App\Mail\ApplicationSubmitted;
use App\Services\CvAutoScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\IOFactory;

class CandidateJobController extends Controller
{
    protected $cvAutoScoringService;

    private function profileOptions(): array
    {
        return [
            'it_roles' => [
                'Backend Developer',
                'Frontend Developer',
                'Fullstack Developer',
                'Mobile Developer',
                'QA/Tester',
                'DevOps Engineer',
                'Data Analyst',
                'ML Engineer',
                'Product/Business Analyst',
            ],
            'it_skills' => [
                'PHP',
                'Laravel',
                'JavaScript',
                'TypeScript',
                'React',
                'Vue.js',
                'Node.js',
                'HTML/CSS',
                'Tailwind CSS',
                'MySQL',
                'PostgreSQL',
                'Redis',
                'Git',
                'Linux',
                'Docker',
                'Kubernetes',
                'CI/CD',
                'AWS',
                'Azure',
                'GCP',
                'Python',
                'Django',
                'Java',
                'Spring',
                'C#/.NET',
                'QA/Testing',
                'Selenium/Playwright',
            ],
            'experience' => [
                'Fresher (0-1 năm)',
                'Junior (1-2 năm)',
                'Middle (2-4 năm)',
                'Senior (4+ năm)',
                'Lead/Manager',
            ],
            'education' => [
                'THPT',
                'Trung cấp',
                'Cao đẳng',
                'Đại học',
                'Thạc sĩ',
                'Tiến sĩ',
                'Bootcamp/Tự học',
            ],
        ];
    }

    public function __construct(CvAutoScoringService $cvAutoScoringService)
    {
        $this->cvAutoScoringService = $cvAutoScoringService;
    }
    /**
     * Hiển thị danh sách tất cả công việc đang tuyển
     */
    public function index(Request $request)
    {
        // Demo mode: redirect unauthenticated visitors to demo landing
        if (!Auth::check() && config('app.demo_mode')) {
            return redirect()->route('demo.landing');
        }

        $query = Job::where('status', 'published');

        // Chỉ hiển thị jobs CNTT
        $sector = 'it';

        // IT jobs include those with no rubric linkage (null) or rubric key == 'it'
        $query->where(function ($q) {
            $q->whereDoesntHave('cvScoringProfile.rubric')
                ->orWhereHas('cvScoringProfile.rubric', function ($q2) {
                    $q2->where('key', '=', 'it');
                });
        });

        // Search by keyword
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhere('requirements', 'like', "%{$keyword}%");
            });
        }

        // Filter by location
        if ($request->filled('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        // Filter by salary range
        if ($request->filled('salary_min')) {
            $query->where('salary_max', '>=', $request->salary_min);
        }
        if ($request->filled('salary_max')) {
            $query->where('salary_min', '<=', $request->salary_max);
        }

        // Filter by job type
        if ($request->filled('job_type')) {
            $query->where('type', $request->job_type);
        }

        $jobs = $query->with(['company', 'cvScoringProfile.rubric'])
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        // Get unique locations for filter dropdown
        $locations = Job::where('status', 'published')
            ->distinct()
            ->pluck('location')
            ->filter()
            ->values();

        // ── Demo seed-awareness data ──────────────────────────────────────
        // Build a map of job_id → seed state for the current user
        $demoSeedInfo = [];
        if (config('app.demo_mode') && Auth::check()) {
            $user = Auth::user();
            $jobIds = $jobs->pluck('id')->all();

            if ($user->role === 'candidate') {
                $candidate = Candidate::where('user_id', $user->id)->first();
                if ($candidate) {
                    $myApps = Application::where('candidate_id', $candidate->id)
                        ->whereIn('job_id', $jobIds)
                        ->get(['job_id', 'ai_match_result'])
                        ->keyBy('job_id');

                    foreach ($jobIds as $jid) {
                        $app = $myApps->get($jid);
                        $demoSeedInfo[$jid] = [
                            'applied'       => $app !== null,
                            'has_ai_result' => $app && !empty($app->ai_match_result),
                        ];
                    }
                }
            } elseif (in_array($user->role, ['recruiter', 'admin'])) {
                // For recruiter: load application counts + AI result stats per job
                $appStats = Application::whereIn('job_id', $jobIds)
                    ->selectRaw('job_id, COUNT(*) as app_count, SUM(CASE WHEN ai_match_result IS NOT NULL THEN 1 ELSE 0 END) as ai_count')
                    ->groupBy('job_id')
                    ->get()
                    ->keyBy('job_id');

                foreach ($jobIds as $jid) {
                    $stat = $appStats->get($jid);
                    $demoSeedInfo[$jid] = [
                        'app_count' => $stat ? (int) $stat->app_count : 0,
                        'ai_count'  => $stat ? (int) $stat->ai_count : 0,
                    ];
                }
            }
        }

        return view('welcome', compact('jobs', 'locations', 'sector', 'demoSeedInfo'));
    }

    /**
     * Hiển thị dashboard của candidate
     */
    public function dashboard()
    {
        $user = Auth::user();
        $candidate = Candidate::where('user_id', $user->id)->first();
        
        if (!$candidate) {
            // Nếu chưa có candidate profile, chuyển về trang tạo profile
            return redirect()->route('candidate.profile')->with('warning', 'Vui lòng hoàn thiện hồ sơ của bạn');
        }

        // Thống kê đơn ứng tuyển
        $applications = Application::where('candidate_id', $candidate->id)->get();
        $stats = [
            'total' => $applications->count(),
            'pending' => $applications->where('status', 'pending')->count(),
            'reviewing' => $applications->where('status', 'reviewing')->count(),
            'interview' => $applications->where('status', 'interview')->count(),
            'accepted' => $applications->where('status', 'accepted')->count(),
            'rejected' => $applications->where('status', 'rejected')->count(),
        ];

        // Đơn ứng tuyển gần đây
        $recentApplications = $applications->sortByDesc('applied_at')->take(5);

        // Lịch phỏng vấn sắp tới
        $upcomingInterviews = \App\Models\Interview::whereHas('application', function($q) use ($candidate) {
            $q->where('candidate_id', $candidate->id);
        })->where('scheduled_at', '>=', now())
          ->where('status', 'scheduled')
          ->orderBy('scheduled_at', 'asc')
          ->take(3)
          ->get();

        // Việc làm đề xuất CNTT
        $recommendedJobs = Job::where('status', 'published')
            ->where('deadline', '>=', now())
            ->where(function ($q2) {
                $q2->whereDoesntHave('cvScoringProfile.rubric')
                    ->orWhereHas('cvScoringProfile.rubric', function ($q3) {
                        $q3->where('key', '=', 'it');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        return view('candidate.dashboard', compact('candidate', 'stats', 'recentApplications', 'upcomingInterviews', 'recommendedJobs'));
    }

    /**
     * Hiển thị danh sách đơn ứng tuyển của candidate đang đăng nhập
     */
    public function myApplications()
    {
        $user = Auth::user();
        
        // Tìm candidate liên kết với user này (theo user_id)
        $candidate = Candidate::where('user_id', $user->id)->first();
        
        if (!$candidate) {
            $applications = collect([]);
        } else {
            $applications = Application::where('candidate_id', $candidate->id)
                ->with(['job.company'])
                ->orderBy('applied_at', 'desc')
                ->get();
        }

        return view('candidate.applications', compact('applications'));
    }

    /**
     * Hiển thị profile của candidate
     */
    public function profile()
    {
        $user = Auth::user();
        $candidate = Candidate::where('user_id', $user->id)->first();

        $options = $this->profileOptions();

        return view('candidate.profile', [
            'user' => $user,
            'candidate' => $candidate,
            'itRoleOptions' => $options['it_roles'],
            'itSkillsOptions' => $options['it_skills'],
            'experienceOptions' => $options['experience'],
            'educationOptions' => $options['education'],
        ]);
    }

    /**
     * Cập nhật profile của candidate
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $options = $this->profileOptions();

        // Existing candidate (for merging profile_data and appending proof files)
        // Use user_id instead of email because email is encrypted
        $existingCandidate = Candidate::where('user_id', $user->id)->first();
        $existingProfileData = [];
        if ($existingCandidate && is_array($existingCandidate->profile_data)) {
            $existingProfileData = $existingCandidate->profile_data;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',

            // IT only - không cần sector nữa
            'it_role' => ['required', 'nullable', 'string', Rule::in($options['it_roles'])],
            'it_skills' => ['required', 'nullable', 'array', 'max:30'],
            'it_skills.*' => ['string', Rule::in($options['it_skills'])],

            'experience' => ['nullable', 'string', Rule::in($options['experience'])],
            'education' => ['nullable', 'string', Rule::in($options['education'])],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'github_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'cv_file' => 'nullable|file|mimes:docx,doc,pdf|max:5120',
            'proof_files' => ['nullable', 'array', 'max:10'],
            'proof_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            // CV nhanh (tùy chọn) - lưu vào profile để dùng lại khi ứng tuyển
            'cv_quick_self_description' => ['nullable', 'string', 'max:5000'],
            'cv_quick_education_json' => ['nullable', 'string', 'max:20000'],
            'cv_quick_work_experiences_json' => ['nullable', 'string', 'max:60000'],
            'cv_quick_skills_json' => ['nullable', 'string', 'max:20000'],
            'cv_quick_certifications_json' => ['nullable', 'string', 'max:5000'],
        ]);

        // Update user
        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
        ]);

        // Chỉ có CNTT
        $sector = 'it';
        $primaryRole = $validated['it_role'] ?? null;
        $skills = [];
        if (!empty($validated['it_skills']) && is_array($validated['it_skills'])) {
            $skills = $validated['it_skills'];
        }

        $skills = array_values(array_unique(array_filter(array_map(static fn ($v) => trim((string) $v), $skills))));

        // Normalize CV nhanh (optional)
        $cvQuickSelf = trim((string) ($validated['cv_quick_self_description'] ?? ''));
        $cvQuickEducation = [];
        $cvQuickWork = [];
        $cvQuickSkills = ['hard' => [], 'soft' => []];

        $educationJsonRaw = trim((string) ($validated['cv_quick_education_json'] ?? ''));
        if ($educationJsonRaw !== '') {
            $decoded = json_decode($educationJsonRaw, true);
            if (!is_array($decoded)) {
                throw ValidationException::withMessages([
                    'cv_quick_education_json' => 'Dữ liệu học vấn (CV nhanh) không hợp lệ.',
                ]);
            }

            $decoded = array_values(array_filter($decoded, static fn ($row) => is_array($row)));
            if (count($decoded) > 10) {
                throw ValidationException::withMessages([
                    'cv_quick_education_json' => 'Tối đa 10 học vấn (CV nhanh).',
                ]);
            }

            foreach ($decoded as $row) {
                $school = trim((string) ($row['school'] ?? ''));
                $degreeLevel = trim((string) ($row['degree_level'] ?? ''));
                $major = trim((string) ($row['major'] ?? ''));
                $graduationYear = trim((string) ($row['graduation_year'] ?? ''));

                if ($school === '' && $degreeLevel === '' && $major === '' && $graduationYear === '') {
                    continue;
                }

                if ($school === '' || $degreeLevel === '' || $graduationYear === '') {
                    throw ValidationException::withMessages([
                        'cv_quick_education_json' => 'Vui lòng nhập đầy đủ Trường, Loại bằng và Năm tốt nghiệp cho mỗi học vấn (CV nhanh).',
                    ]);
                }

                $graduationYearInt = (int) $graduationYear;
                $currentYear = (int) date('Y');
                if ($graduationYearInt < 1950 || $graduationYearInt > ($currentYear + 10)) {
                    throw ValidationException::withMessages([
                        'cv_quick_education_json' => 'Năm tốt nghiệp (CV nhanh) không hợp lệ.',
                    ]);
                }

                $cvQuickEducation[] = [
                    'school' => $school,
                    'degree_level' => $degreeLevel,
                    'major' => $major !== '' ? $major : null,
                    'graduation_year' => $graduationYearInt,
                ];
            }
        }

        $workJsonRaw = trim((string) ($validated['cv_quick_work_experiences_json'] ?? ''));
        if ($workJsonRaw !== '') {
            $decoded = json_decode($workJsonRaw, true);
            if (!is_array($decoded)) {
                throw ValidationException::withMessages([
                    'cv_quick_work_experiences_json' => 'Dữ liệu kinh nghiệm làm việc (CV nhanh) không hợp lệ.',
                ]);
            }

            $decoded = array_values(array_filter($decoded, static fn ($row) => is_array($row)));
            if (count($decoded) > 20) {
                throw ValidationException::withMessages([
                    'cv_quick_work_experiences_json' => 'Tối đa 20 kinh nghiệm làm việc (CV nhanh).',
                ]);
            }

            foreach ($decoded as $row) {
                $companyName = trim((string) ($row['company_name'] ?? ''));
                $positionTitle = trim((string) ($row['position_title'] ?? ''));
                $startDate = trim((string) ($row['start_date'] ?? ''));
                $endDate = trim((string) ($row['end_date'] ?? ''));
                $isCurrent = (bool) ($row['is_current'] ?? false);
                $description = trim((string) ($row['description'] ?? ''));

                if ($companyName === '' && $positionTitle === '' && $startDate === '' && $endDate === '' && $description === '') {
                    continue;
                }

                if ($companyName === '' || $positionTitle === '' || $startDate === '') {
                    throw ValidationException::withMessages([
                        'cv_quick_work_experiences_json' => 'Vui lòng nhập Tên công ty, Vị trí và Ngày bắt đầu cho mỗi kinh nghiệm (CV nhanh).',
                    ]);
                }

                $start = \DateTime::createFromFormat('Y-m-d', $startDate);
                if (!$start || $start->format('Y-m-d') !== $startDate) {
                    throw ValidationException::withMessages([
                        'cv_quick_work_experiences_json' => 'Ngày bắt đầu (CV nhanh) không hợp lệ.',
                    ]);
                }

                if ($isCurrent) {
                    $endDate = null;
                } else {
                    if ($endDate === '') {
                        throw ValidationException::withMessages([
                            'cv_quick_work_experiences_json' => 'Vui lòng nhập Ngày kết thúc hoặc chọn “Đang làm việc tại đây” (CV nhanh).',
                        ]);
                    }
                    $end = \DateTime::createFromFormat('Y-m-d', $endDate);
                    if (!$end || $end->format('Y-m-d') !== $endDate) {
                        throw ValidationException::withMessages([
                            'cv_quick_work_experiences_json' => 'Ngày kết thúc (CV nhanh) không hợp lệ.',
                        ]);
                    }
                    if ($end < $start) {
                        throw ValidationException::withMessages([
                            'cv_quick_work_experiences_json' => 'Ngày kết thúc (CV nhanh) phải sau hoặc bằng ngày bắt đầu.',
                        ]);
                    }
                }

                $cvQuickWork[] = [
                    'company_name' => $companyName,
                    'position_title' => $positionTitle,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_current' => $isCurrent,
                    'description' => $description !== '' ? $description : null,
                ];
            }
        }

        $skillsJsonRaw = trim((string) ($validated['cv_quick_skills_json'] ?? ''));
        if ($skillsJsonRaw !== '') {
            $decoded = json_decode($skillsJsonRaw, true);
            if (!is_array($decoded)) {
                throw ValidationException::withMessages([
                    'cv_quick_skills_json' => 'Dữ liệu kỹ năng (CV nhanh) không hợp lệ.',
                ]);
            }

            foreach (['hard', 'soft'] as $kind) {
                $items = $decoded[$kind] ?? [];
                if (!is_array($items)) {
                    $items = [];
                }
                if (count($items) > 30) {
                    throw ValidationException::withMessages([
                        'cv_quick_skills_json' => 'Tối đa 30 kỹ năng cho mỗi nhóm (Hard/Soft) (CV nhanh).',
                    ]);
                }

                $seen = [];
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $name = trim((string) ($item['name'] ?? ''));
                    $level = (int) ($item['level'] ?? 0);
                    if ($name === '') {
                        continue;
                    }
                    if (mb_strlen($name) > 60) {
                        throw ValidationException::withMessages([
                            'cv_quick_skills_json' => 'Tên kỹ năng (CV nhanh) quá dài (tối đa 60 ký tự).',
                        ]);
                    }
                    if ($level < 1 || $level > 5) {
                        throw ValidationException::withMessages([
                            'cv_quick_skills_json' => 'Mức độ kỹ năng (CV nhanh) phải từ 1 đến 5.',
                        ]);
                    }
                    $key = mb_strtolower($name);
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $cvQuickSkills[$kind][] = [
                        'name' => $name,
                        'level' => $level,
                    ];
                }
            }
        }

        // Certifications (CV nhanh)
        $cvQuickCertifications = [];
        $certificationsJsonRaw = trim((string) ($validated['cv_quick_certifications_json'] ?? ''));
        if ($certificationsJsonRaw !== '') {
            $decoded = json_decode($certificationsJsonRaw, true);
            if (is_array($decoded)) {
                $cvQuickCertifications = [
                    'english_level' => !empty($decoded['english_level']) ? trim($decoded['english_level']) : null,
                    'toeic_score' => isset($decoded['toeic_score']) && is_numeric($decoded['toeic_score']) ? (float) $decoded['toeic_score'] : null,
                    'ielts_score' => isset($decoded['ielts_score']) && is_numeric($decoded['ielts_score']) ? (float) $decoded['ielts_score'] : null,
                    'years_experience' => isset($decoded['years_experience']) && is_numeric($decoded['years_experience']) ? (float) $decoded['years_experience'] : null,
                    'certifications' => is_array($decoded['certifications'] ?? null) ? $decoded['certifications'] : [],
                ];
            }
        }

        $hasCvQuick = ($cvQuickSelf !== '') || !empty($cvQuickEducation) || !empty($cvQuickWork) || !empty($cvQuickSkills['hard']) || !empty($cvQuickSkills['soft']) || !empty($cvQuickCertifications);

        // Preserve existing profile data values when new values are empty
        $newProfileData = [
            'sector' => $sector,
            'primary_role' => $primaryRole ?: ($existingProfileData['primary_role'] ?? null),
            'skills' => !empty($skills) ? $skills : ($existingProfileData['skills'] ?? []),
        ];

        $profileData = array_merge($existingProfileData, $newProfileData);

        // Chỉ cập nhật cv_quick nếu có data mới, giữ nguyên cv_quick cũ nếu không có data
        if ($hasCvQuick) {
            $profileData['cv_quick'] = [
                'self_description' => $cvQuickSelf !== '' ? $cvQuickSelf : null,
                'education' => $cvQuickEducation,
                'work_experiences' => $cvQuickWork,
                'skills' => $cvQuickSkills,
                'certifications' => $cvQuickCertifications,
            ];
        }
        // KHÔNG xóa cv_quick nếu không có data mới - giữ nguyên data cũ

        // Build candidate data, preserving existing values when form fields are empty
        $candidateData = [
            'name' => $validated['name'],
            'phone' => !empty($validated['phone']) ? $validated['phone'] : ($existingCandidate?->phone ?? null),
            'skills' => !empty($skills) ? implode(', ', $skills) : ($existingCandidate?->skills ?? null),
            'experience' => !empty($validated['experience']) ? $validated['experience'] : ($existingCandidate?->experience ?? null),
            'education' => !empty($validated['education']) ? $validated['education'] : ($existingCandidate?->education ?? null),
            'sector' => $sector,
            'profile_data' => $profileData,
            'portfolio_url' => !empty($validated['portfolio_url']) ? $validated['portfolio_url'] : ($existingCandidate?->portfolio_url ?? null),
            'github_url' => !empty($validated['github_url']) ? $validated['github_url'] : ($existingCandidate?->github_url ?? null),
            'linkedin_url' => !empty($validated['linkedin_url']) ? $validated['linkedin_url'] : ($existingCandidate?->linkedin_url ?? null),
        ];

        // Existing candidate (to append proof files)
        $existingProofs = [];
        if ($existingCandidate && is_array($existingCandidate->proof_files)) {
            $existingProofs = $existingCandidate->proof_files;
        }

        // Handle CV upload
        if ($request->hasFile('cv_file')) {
            $file = $request->file('cv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('cvs', $filename);
            $candidateData['file_path_cv'] = $filePath;

            // Extract text
            $extension = strtolower($file->getClientOriginalExtension());
            if (in_array($extension, ['docx', 'doc'])) {
                $cvContent = $this->extractTextFromDocx(Storage::path($filePath));
                $candidateData['summary'] = mb_substr($cvContent, 0, 500);
            }
        }

        // Handle proof uploads (append)
        if ($request->hasFile('proof_files')) {
            $stored = [];
            foreach ((array) $request->file('proof_files') as $proofFile) {
                if (!$proofFile) {
                    continue;
                }
                $proofName = time() . '_' . $proofFile->getClientOriginalName();
                $proofPath = $proofFile->storeAs('candidate_proofs/' . $user->id, $proofName);
                $stored[] = $proofPath;
            }

            $merged = array_values(array_unique(array_merge($existingProofs, $stored)));
            $candidateData['proof_files'] = $merged;
        } else {
            // Preserve existing proof files if none uploaded in this request
            if (!empty($existingProofs)) {
                $candidateData['proof_files'] = $existingProofs;
            }
        }

        // Use user_id as unique key (email is encrypted, can't be used for where clause)
        Candidate::updateOrCreate(
            ['user_id' => $user->id],
            $candidateData
        );

        return redirect()->back()->with('status', 'Cập nhật hồ sơ thành công!');
    }

    /**
     * Hiển thị chi tiết một công việc
     */
    public function show($id)
    {
        $job = Job::with('company')->findOrFail($id);

        $currentCandidate = null;
        $alreadyApplied = false;
        $existingApplication = null;
        $followupFields = [];
        $persistedAdvisory = null;

        if (Auth::check() && Auth::user()->role === 'candidate') {
            $currentCandidate = Candidate::where('user_id', Auth::user()->id)->first();
            if ($currentCandidate) {
                $existingApplication = Application::where('job_id', $job->id)
                    ->where('candidate_id', $currentCandidate->id)
                    ->first();
                $alreadyApplied = $existingApplication !== null;

                // In demo mode: compute follow-up fields from persisted AI result + candidate data
                // This survives page reloads — not session-dependent.
                if ($alreadyApplied && config('app.demo_mode')) {
                    $aiResult = $existingApplication->ai_match_result;
                    if (is_array($aiResult) && !empty($aiResult)) {
                        $persistedAdvisory = \App\Services\AI\CandidateAdvisory::fromMatchResult($aiResult);
                        $followupFields = $this->detectMissingFollowupFields($aiResult, $currentCandidate, $job);
                    }
                }
            }
        }

        return view('jobs.show', compact(
            'job', 'currentCandidate', 'alreadyApplied',
            'followupFields', 'persistedAdvisory', 'existingApplication'
        ));
    }

    /**
     * Xử lý nộp đơn ứng tuyển
     * - Validate thông tin
     * - Lưu file CV
     * - Extract text từ file .docx bằng PHPWord
     * - Tạo bản ghi Application
     */
    public function apply(Request $request, $id)
    {
        // Debug logging
        \Log::info('[Apply] Request received', [
            'job_id' => $id,
            'has_auth' => Auth::check(),
            'user_id' => Auth::id(),
            'session_id' => session()->getId(),
            'cv_mode' => $request->input('cv_mode'),
        ]);

        // Only logged-in candidates can apply (prevent guest email abuse)
        $user = Auth::user();
        if (!$user || $user->role !== 'candidate') {
            \Log::warning('[Apply] Auth failed, redirecting to login', [
                'user' => $user ? $user->toArray() : null,
                'session_id' => session()->getId(),
            ]);
            return redirect()->route('login')
                ->with('error', 'Vui lòng đăng nhập bằng tài khoản Ứng tuyển để nộp đơn.');
        }

        if ($this->candidateNeedsOnboarding($user->email)) {
            return redirect()->route('candidate.profile')
                ->with('error', 'Vui lòng hoàn thiện hồ sơ trước khi ứng tuyển.');
        }

        $cvMode = $request->input('cv_mode', 'upload');

        // Validate input
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'cv_mode' => ['nullable', Rule::in(['upload', 'form'])],
            'cv_file' => ['required_if:cv_mode,upload', 'nullable', 'file', 'mimes:docx,doc,pdf', 'max:5120'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],

            // Form CV mode
            'self_description' => ['required_if:cv_mode,form', 'nullable', 'string', 'max:5000'],
            'education_json' => ['nullable', 'string', 'max:20000'],
            'work_experiences_json' => ['nullable', 'string', 'max:60000'],
            'skills_json' => ['nullable', 'string', 'max:20000'],
            'certifications_json' => ['nullable', 'string', 'max:5000'],
            'education_proofs' => ['nullable', 'array', 'max:10'],
            'education_proofs.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'cv_file.required_if' => 'Vui lòng upload file CV.',
            'cv_file.mimes' => 'Chỉ chấp nhận file .docx, .doc hoặc .pdf.',
            'cv_file.max' => 'File CV không được vượt quá 5MB.',
            'self_description.required_if' => 'Vui lòng nhập mô tả bản thân.',
            'education_proofs.array' => 'Dữ liệu minh chứng không hợp lệ.',
        ]);

        // User is guaranteed to be candidate at this point
        $validated['email'] = $user->email;
        $validated['full_name'] = $user->name;

        $job = Job::findOrFail($id);

        $filePath = null;
        $cvContent = '';
        $cvData = null;
        $cvProofFiles = null;

        if ($cvMode === 'upload') {
            // Lưu file CV vào storage
            $file = $request->file('cv_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('cvs', $filename);

            // Extract text từ file CV
            $extension = strtolower($file->getClientOriginalExtension());

            if (in_array($extension, ['docx', 'doc'])) {
                $cvContent = $this->extractTextFromDocx(Storage::path($filePath));
            } elseif ($extension === 'pdf') {
                // TODO: Xử lý PDF sau (có thể dùng smalot/pdfparser hoặc spatie/pdf-to-text)
                $cvContent = '[PDF content - chưa hỗ trợ extract]';
            }
        }

        if ($cvMode === 'form') {
            $storedProofs = [];
            $educationNormalized = [];

            $educationJsonRaw = trim((string) ($validated['education_json'] ?? ''));
            $education = [];
            if ($educationJsonRaw !== '') {
                $education = json_decode($educationJsonRaw, true);
                if (!is_array($education)) {
                    throw ValidationException::withMessages([
                        'education_json' => 'Dữ liệu học vấn không hợp lệ.',
                    ]);
                }

                $education = array_values(array_filter($education, static fn ($row) => is_array($row)));
                // Education is optional, but if provided must have valid entries
                if (count($education) > 10) {
                    throw ValidationException::withMessages([
                        'education_json' => 'Tối đa 10 học vấn.',
                    ]);
                }
            }

            $proofUploads = (array) $request->file('education_proofs', []);
            // Proofs are optional; if provided, allow any count (not strictly matching education rows)

            $proofFolder = null;
            if ($user && $user->id) {
                $proofFolder = 'application_cv_proofs/u' . $user->id;
            } else {
                $proofFolder = 'application_cv_proofs/' . md5((string) ($validated['email'] ?? 'guest'));
            }

            foreach ($education as $index => $row) {
                $school = trim((string) ($row['school'] ?? ''));
                $degreeLevel = trim((string) ($row['degree_level'] ?? ''));
                $major = trim((string) ($row['major'] ?? ''));
                $graduationYear = (string) ($row['graduation_year'] ?? '');

                if ($school === '' || $degreeLevel === '' || $graduationYear === '') {
                    throw ValidationException::withMessages([
                        'education_json' => 'Vui lòng nhập đầy đủ trường, bậc học và năm tốt nghiệp.',
                    ]);
                }

                $graduationYearInt = (int) $graduationYear;
                $currentYear = (int) date('Y');
                if ($graduationYearInt < 1950 || $graduationYearInt > ($currentYear + 10)) {
                    throw ValidationException::withMessages([
                        'education_json' => 'Năm tốt nghiệp không hợp lệ.',
                    ]);
                }

                $proofFile = $proofUploads[$index] ?? null;
                if ($proofFile) {
                    $proofName = time() . '_' . ($index + 1) . '_' . $proofFile->getClientOriginalName();
                    $proofPath = $proofFile->storeAs($proofFolder, $proofName);
                    $storedProofs[] = $proofPath;
                }

                $educationNormalized[] = [
                    'school' => $school,
                    'degree_level' => $degreeLevel,
                    'major' => $major !== '' ? $major : null,
                    'graduation_year' => $graduationYearInt,
                ];
            }

            // Work experiences (optional)
            $workExperiences = [];
            if (!empty($validated['work_experiences_json'])) {
                $workDecoded = json_decode((string) $validated['work_experiences_json'], true);
                if (!is_array($workDecoded)) {
                    throw ValidationException::withMessages([
                        'work_experiences_json' => 'Dữ liệu kinh nghiệm làm việc không hợp lệ.',
                    ]);
                }

                $workDecoded = array_values(array_filter($workDecoded, static fn ($row) => is_array($row)));
                if (count($workDecoded) > 20) {
                    throw ValidationException::withMessages([
                        'work_experiences_json' => 'Tối đa 20 kinh nghiệm làm việc.',
                    ]);
                }

                foreach ($workDecoded as $row) {
                    $companyName = trim((string) ($row['company_name'] ?? ''));
                    $positionTitle = trim((string) ($row['position_title'] ?? ''));
                    $startDate = trim((string) ($row['start_date'] ?? ''));
                    $endDate = trim((string) ($row['end_date'] ?? ''));
                    $isCurrent = (bool) ($row['is_current'] ?? false);
                    $description = trim((string) ($row['description'] ?? ''));

                    if ($companyName === '' && $positionTitle === '' && $startDate === '' && $endDate === '' && $description === '') {
                        continue;
                    }

                    if ($companyName === '' || $positionTitle === '' || $startDate === '') {
                        throw ValidationException::withMessages([
                            'work_experiences_json' => 'Vui lòng nhập Tên công ty, Vị trí và Ngày bắt đầu cho mỗi kinh nghiệm.',
                        ]);
                    }

                    $start = \DateTime::createFromFormat('Y-m-d', $startDate);
                    if (!$start || $start->format('Y-m-d') !== $startDate) {
                        throw ValidationException::withMessages([
                            'work_experiences_json' => 'Ngày bắt đầu không hợp lệ.',
                        ]);
                    }

                    if ($isCurrent) {
                        $endDate = null;
                    } else {
                        if ($endDate === '') {
                            throw ValidationException::withMessages([
                                'work_experiences_json' => 'Vui lòng nhập Ngày kết thúc hoặc chọn “Đang làm việc tại đây”.',
                            ]);
                        }
                        $end = \DateTime::createFromFormat('Y-m-d', $endDate);
                        if (!$end || $end->format('Y-m-d') !== $endDate) {
                            throw ValidationException::withMessages([
                                'work_experiences_json' => 'Ngày kết thúc không hợp lệ.',
                            ]);
                        }
                        if ($end < $start) {
                            throw ValidationException::withMessages([
                                'work_experiences_json' => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
                            ]);
                        }
                    }

                    $workExperiences[] = [
                        'company_name' => $companyName,
                        'position_title' => $positionTitle,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'is_current' => $isCurrent,
                        'description' => $description !== '' ? $description : null,
                    ];
                }
            }

            // Certifications (optional): english, toeic, ielts, years_experience, certifications
            $certifications = [];
            if (!empty($validated['certifications_json'])) {
                $certsDecoded = json_decode((string) $validated['certifications_json'], true);
                if (is_array($certsDecoded)) {
                    $certifications = [
                        'english_level' => !empty($certsDecoded['english_level']) ? trim($certsDecoded['english_level']) : null,
                        'toeic_score' => isset($certsDecoded['toeic_score']) && is_numeric($certsDecoded['toeic_score']) ? (float) $certsDecoded['toeic_score'] : null,
                        'ielts_score' => isset($certsDecoded['ielts_score']) && is_numeric($certsDecoded['ielts_score']) ? (float) $certsDecoded['ielts_score'] : null,
                        'years_experience' => isset($certsDecoded['years_experience']) && is_numeric($certsDecoded['years_experience']) ? (float) $certsDecoded['years_experience'] : null,
                        'certifications' => is_array($certsDecoded['certifications'] ?? null) ? $certsDecoded['certifications'] : [],
                    ];
                }
            }

            // Skills (optional): hard/soft with level 1-5
            $skills = ['hard' => [], 'soft' => []];
            if (!empty($validated['skills_json'])) {
                $skillsDecoded = json_decode((string) $validated['skills_json'], true);
                if (!is_array($skillsDecoded)) {
                    throw ValidationException::withMessages([
                        'skills_json' => 'Dữ liệu kỹ năng không hợp lệ.',
                    ]);
                }

                foreach (['hard', 'soft'] as $kind) {
                    $items = $skillsDecoded[$kind] ?? [];
                    if (!is_array($items)) {
                        $items = [];
                    }
                    if (count($items) > 30) {
                        throw ValidationException::withMessages([
                            'skills_json' => 'Tối đa 30 kỹ năng cho mỗi nhóm (Hard/Soft).',
                        ]);
                    }

                    $seen = [];
                    foreach ($items as $item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        $name = trim((string) ($item['name'] ?? ''));
                        $level = (int) ($item['level'] ?? 0);
                        if ($name === '') {
                            continue;
                        }
                        if (mb_strlen($name) > 60) {
                            throw ValidationException::withMessages([
                                'skills_json' => 'Tên kỹ năng quá dài (tối đa 60 ký tự).',
                            ]);
                        }
                        if ($level < 1 || $level > 5) {
                            throw ValidationException::withMessages([
                                'skills_json' => 'Mức độ kỹ năng phải từ 1 đến 5.',
                            ]);
                        }
                        $key = mb_strtolower($name);
                        if (isset($seen[$key])) {
                            continue;
                        }
                        $seen[$key] = true;
                        $skills[$kind][] = [
                            'name' => $name,
                            'level' => $level,
                        ];
                    }
                }
            }

            $cvData = [
                'self_description' => $validated['self_description'] ?? null,
                'education' => $educationNormalized,
                'work_experiences' => $workExperiences,
                'skills' => $skills,
                'certifications' => $certifications,
            ];
            
            // DEBUG: Log received cv_data structure
            \Log::info('CV Form Submission Received', [
                'candidate_email' => $validated['email'],
                'cv_mode' => $cvMode,
                'education_count' => count($educationNormalized),
                'work_count' => count($workExperiences),
                'hard_skills_count' => count($skills['hard'] ?? []),
                'soft_skills_count' => count($skills['soft'] ?? []),
                'has_self_description' => !empty($validated['self_description']),
                'raw_education_json' => $validated['education_json'] ?? 'EMPTY',
                'raw_work_json' => substr($validated['work_experiences_json'] ?? 'EMPTY', 0, 100),
            ]);
            
            $cvProofFiles = $storedProofs;

            $cvContentParts = [];
            if (!empty($cvData['self_description'])) {
                $cvContentParts[] = (string) $cvData['self_description'];
            }
            foreach ($educationNormalized as $row) {
                $line = $row['school'] . ' - ' . $row['degree_level'];
                if (!empty($row['major'])) {
                    $line .= ' - ' . $row['major'];
                }
                $line .= ' - ' . $row['graduation_year'];
                $cvContentParts[] = $line;
            }

            if (!empty($workExperiences)) {
                foreach ($workExperiences as $row) {
                    $line = ($row['company_name'] ?? '') . ' - ' . ($row['position_title'] ?? '') . ' - ' . ($row['start_date'] ?? '');
                    if (!empty($row['is_current'])) {
                        $line .= ' - hiện tại';
                    } elseif (!empty($row['end_date'])) {
                        $line .= ' - ' . $row['end_date'];
                    }
                    if (!empty($row['description'])) {
                        $line .= "\n" . $row['description'];
                    }
                    $cvContentParts[] = $line;
                }
            }

            $skillLines = [];
            foreach (['hard' => 'Hard Skills', 'soft' => 'Soft Skills'] as $kind => $label) {
                $items = $skills[$kind] ?? [];
                if (!empty($items)) {
                    $skillLines[] = $label . ': ' . implode(', ', array_map(static fn ($s) => ($s['name'] ?? '') . ' (' . ($s['level'] ?? '') . '/5)', $items));
                }
            }
            if (!empty($skillLines)) {
                $cvContentParts[] = implode("\n", $skillLines);
            }
            
            // Add certifications info
            if (!empty($certifications)) {
                $certLines = [];
                if (!empty($certifications['english_level'])) {
                    $certLines[] = 'English Level: ' . $certifications['english_level'];
                }
                if (!empty($certifications['toeic_score'])) {
                    $certLines[] = 'TOEIC: ' . $certifications['toeic_score'];
                }
                if (!empty($certifications['ielts_score'])) {
                    $certLines[] = 'IELTS: ' . $certifications['ielts_score'];
                }
                if (!empty($certifications['years_experience'])) {
                    $certLines[] = 'Years of Experience: ' . $certifications['years_experience'];
                }
                if (!empty($certifications['certifications'])) {
                    $certLines[] = 'Certifications: ' . implode(', ', $certifications['certifications']);
                }
                if (!empty($certLines)) {
                    $cvContentParts[] = implode("\n", $certLines);
                }
            }
            
            $cvContent = implode("\n", $cvContentParts);
        }

        // Tìm hoặc tạo Candidate
        $candidateUpdate = [
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ];
        if (!empty($cvContent)) {
            $candidateUpdate['summary'] = mb_substr($cvContent, 0, 500);
        }
        if (!empty($filePath)) {
            $candidateUpdate['file_path_cv'] = $filePath;
        }
        
        // Don't include profile_data in updateOrCreate - we'll handle it separately
        // Use user_id for lookup instead of email (email is encrypted, can't match)
        $candidate = Candidate::updateOrCreate(
            ['user_id' => $user->id],
            $candidateUpdate
        );
        
        // Refresh to get latest data from DB
        $candidate->refresh();

        // Nếu user là candidate và dùng CV nhanh: lưu lại vào hồ sơ để lần sau sửa/dùng lại
        if ($user && $user->role === 'candidate' && $cvMode === 'form' && is_array($cvData)) {
            // Check if we have meaningful data (not all empty arrays)
            $hasEducation = !empty($cvData['education']);
            $hasWork = !empty($cvData['work_experiences']);
            $hasSkills = !empty($cvData['skills']['hard'] ?? []) || !empty($cvData['skills']['soft'] ?? []);
            $hasCerts = !empty($cvData['certifications']['english_level']) 
                     || !empty($cvData['certifications']['toeic_score'])
                     || !empty($cvData['certifications']['ielts_score'])
                     || !empty($cvData['certifications']['years_experience'])
                     || !empty($cvData['certifications']['certifications']);
            
            $hasAnyData = $hasEducation || $hasWork || $hasSkills || $hasCerts;
            
            // ONLY update profile_data if we have actual data from form
            // This prevents overwriting existing data with empty form submission
            if ($hasAnyData) {
                $profileData = is_array($candidate->profile_data) ? $candidate->profile_data : [];
                
                // Merge new cv_quick with existing profile_data
                $newCvQuick = [
                    'self_description' => $cvData['self_description'] ?? null,
                    'education' => is_array($cvData['education'] ?? null) ? $cvData['education'] : [],
                    'work_experiences' => is_array($cvData['work_experiences'] ?? null) ? $cvData['work_experiences'] : [],
                    'skills' => is_array($cvData['skills'] ?? null) ? $cvData['skills'] : ['hard' => [], 'soft' => []],
                    'certifications' => is_array($cvData['certifications'] ?? null) ? $cvData['certifications'] : [],
                ];
                
                $profileData['cv_quick'] = $newCvQuick;
                $candidate->profile_data = $profileData;
                $candidate->save();
                
                \Log::info('Saved cv_quick to candidate profile', [
                    'candidate_id' => $candidate->id,
                    'education_count' => count($newCvQuick['education']),
                    'work_count' => count($newCvQuick['work_experiences']),
                    'hard_skills_count' => count($newCvQuick['skills']['hard'] ?? []),
                    'soft_skills_count' => count($newCvQuick['skills']['soft'] ?? []),
                ]);
            } else {
                // Form data is empty, don't overwrite existing profile
                \Log::warning('CV form submission has no data - preserving existing profile_data', [
                    'candidate_id' => $candidate->id,
                    'existing_education' => count($candidate->profile_data['cv_quick']['education'] ?? []),
                    'existing_work' => count($candidate->profile_data['cv_quick']['work_experiences'] ?? []),
                ]);
            }
        }

        // Kiểm tra đã ứng tuyển chưa
        $existingApplication = Application::where('job_id', $job->id)
            ->where('candidate_id', $candidate->id)
            ->first();

        if ($existingApplication) {
            return redirect()->route('jobs.show', $job->id)
                ->with('error', 'Bạn đã ứng tuyển vị trí này rồi.')
                ->withFragment('apply-form');
        }

        // Persist extracted/raw CV text into cv_data for later (SQL-based) scoring.
        $cvDataToStore = is_array($cvData) ? $cvData : [];
        if (!empty($cvContent)) {
            $cvDataToStore['_raw_text'] = mb_substr($cvContent, 0, 50000);
        }

        // Tạo Application
        $application = Application::create([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
            'status' => 'submitted',
            'cv_file_path' => $filePath,
            'cover_letter' => $validated['cover_letter'] ?? null,
            'cv_data' => !empty($cvDataToStore) ? $cvDataToStore : null,
            'cv_proof_files' => $cvProofFiles,
            'applied_at' => now(),
        ]);

        // Auto-score CV rubric (SQL-based) if possible. Do not block apply flow.
        $aiScore = null;
        try {
            $this->cvAutoScoringService->scoreAndPersist($application, $cvContent);
            // Refresh application to get updated cv_manual_score
            $application->refresh();
            // ai_score accessor converts cv_manual_score from 100 scale to 10 scale
            $aiScore = $application->ai_score;
            
            \Log::info('Scoring completed', [
                'application_id' => $application->id,
                'cv_manual_score' => $application->cv_manual_score,
                'ai_score_converted' => $aiScore,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Auto CV scoring failed for application ' . $application->id . ': ' . $e->getMessage());
        }

        // Load relations for email
        $application->load(['job.company', 'candidate']);

        // Send confirmation email
        try {
            Mail::to($candidate->email)->send(new ApplicationSubmitted($application));
        } catch (\Exception $e) {
            \Log::warning('Failed to send application confirmation email: ' . $e->getMessage());
        }

        // ── Immediate AI match for candidate-facing advisory (DEMO_MODE only) ──
        // In demo mode: calls AI service synchronously for immediate candidate advisory.
        // In non-demo mode: skips synchronous AI call, preserving prior apply behavior.
        $aiAdvisory = null;
        if (config('app.demo_mode')) {
            try {
                $orchestratorClient = app(\App\Services\AI\AIOrchestratorClient::class);
                $aiResponse = $orchestratorClient->matchCandidateToJob(
                    $this->buildAiMatchPayload($candidate, $job, $application)
                );

                // Persist sanitized result (OpenSpec: allowed when application_id is explicit)
                $sanitizedKeys = [
                    'fit_score', 'rank_label', 'confidence_label',
                    'score_breakdown', 'matched_skills', 'missing_skills',
                    'missing_preferred_skills', 'risk_flags',
                    'retrieval_method', 'pipeline_version', 'generated_at',
                ];
                $sanitized = array_intersect_key($aiResponse, array_flip($sanitizedKeys));
                $application->update(['ai_match_result' => $sanitized]);
                $aiAdvisory = $sanitized;

                \Log::info('Immediate AI match completed for candidate advisory', [
                    'application_id' => $application->id,
                    'fit_score'      => $aiAdvisory['fit_score'] ?? null,
                    'risk_flags'     => $aiAdvisory['risk_flags'] ?? [],
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Immediate AI match failed for application ' . $application->id . ': ' . $e->getMessage());
                // $aiAdvisory remains null — candidate sees fallback status
            }
        }

        $message = 'Nộp đơn ứng tuyển thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.';
        
        // Use AI advisory score if available, fallback to legacy auto-score
        $displayScore = $aiAdvisory['fit_score'] ?? $aiScore;
        if ($displayScore !== null) {
            $message .= sprintf(' 🤖 Điểm phù hợp: %.1f/10', $displayScore);
        }

        // Compute follow-up fields from AI result (flash for immediate display after apply)
        $followupFields = [];
        if ($aiAdvisory && is_array($aiAdvisory)) {
            $candidate->refresh();
            $followupFields = $this->detectMissingFollowupFields($aiAdvisory, $candidate, $job);
        }

        return redirect()->route('jobs.show', $job->id)
            ->with('status', $message)
            ->with('ai_score', $displayScore)
            ->with('ai_advisory', $aiAdvisory)
            ->with('ai_followup_fields', $followupFields)
            ->withFragment('apply-form');
    }

    /**
     * Submit AI follow-up: candidate provides missing info, system re-runs AI match.
     * Scoped to DEMO_MODE — in non-demo mode, this route returns 404-equivalent.
     */
    public function submitFollowup(Request $request, $id)
    {
        // Route is behind RoleMiddleware::candidate, but DEMO_MODE gate remains.
        if (!config('app.demo_mode')) {
            abort(404);
        }

        $user = Auth::user();
        $job = Job::findOrFail($id);
        $candidate = Candidate::where('user_id', $user->id)->first();
        if (!$candidate) {
            return redirect()->route('jobs.show', $id)->with('error', 'Không tìm thấy hồ sơ ứng viên.');
        }

        $application = Application::where('job_id', $job->id)
            ->where('candidate_id', $candidate->id)
            ->first();
        if (!$application) {
            return redirect()->route('jobs.show', $id)->with('error', 'Bạn chưa ứng tuyển vị trí này.');
        }

        $options = $this->profileOptions();

        // Validate only the fields that were submitted (all optional at the form level)
        $validated = $request->validate([
            'followup_phone'            => ['nullable', 'string', 'max:20'],
            'followup_years_experience' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'followup_primary_role'     => ['nullable', 'string', Rule::in($options['it_roles'])],
            'followup_key_skills'       => ['nullable', 'string', 'max:500'],
            'followup_education_level'  => ['nullable', 'string', Rule::in($options['education'])],
            'followup_english_level'    => ['nullable', 'string', Rule::in(['basic', 'intermediate', 'advanced', 'native'])],
            'followup_portfolio_url'    => ['nullable', 'url', 'max:255'],
            'followup_github_url'       => ['nullable', 'url', 'max:255'],
        ]);

        // --- Update candidate profile (reusable enrichment, not application-specific) ---
        $profileData = is_array($candidate->profile_data) ? $candidate->profile_data : [];
        $changed = false;

        if (!empty($validated['followup_phone'])) {
            $candidate->phone = $validated['followup_phone'];
            // Keep User.phone in sync (same pattern as updateProfile)
            $user->update(['phone' => $validated['followup_phone']]);
            $changed = true;
        }
        if (!empty($validated['followup_primary_role'])) {
            $profileData['primary_role'] = $validated['followup_primary_role'];
            $changed = true;
        }
        if (!empty($validated['followup_key_skills'])) {
            // Merge with existing skills
            $newSkills = array_map('trim', explode(',', $validated['followup_key_skills']));
            $newSkills = array_filter($newSkills, fn($s) => $s !== '');
            $existingSkills = $candidate->skills ? array_map('trim', explode(',', $candidate->skills)) : [];
            $merged = array_values(array_unique(array_merge($existingSkills, $newSkills)));
            $candidate->skills = implode(', ', $merged);
            $changed = true;
        }
        if (!empty($validated['followup_education_level'])) {
            $candidate->education = $validated['followup_education_level'];
            $changed = true;
        }
        if (!empty($validated['followup_portfolio_url'])) {
            $candidate->portfolio_url = $validated['followup_portfolio_url'];
            $changed = true;
        }
        if (!empty($validated['followup_github_url'])) {
            $candidate->github_url = $validated['followup_github_url'];
            $changed = true;
        }
        if (isset($validated['followup_years_experience']) && $validated['followup_years_experience'] !== null) {
            $profileData['cv_quick'] = $profileData['cv_quick'] ?? [];
            $profileData['cv_quick']['certifications'] = $profileData['cv_quick']['certifications'] ?? [];
            $profileData['cv_quick']['certifications']['years_experience'] = (float) $validated['followup_years_experience'];
            // Also set candidate.experience if empty
            if (empty($candidate->experience)) {
                $years = (float) $validated['followup_years_experience'];
                if ($years < 1) $candidate->experience = 'Fresher (0-1 năm)';
                elseif ($years < 2) $candidate->experience = 'Junior (1-2 năm)';
                elseif ($years < 4) $candidate->experience = 'Middle (2-4 năm)';
                else $candidate->experience = 'Senior (4+ năm)';
            }
            $changed = true;
        }
        if (!empty($validated['followup_english_level'])) {
            $profileData['cv_quick'] = $profileData['cv_quick'] ?? [];
            $profileData['cv_quick']['certifications'] = $profileData['cv_quick']['certifications'] ?? [];
            $profileData['cv_quick']['certifications']['english_level'] = $validated['followup_english_level'];
            $changed = true;
        }

        if ($changed) {
            $candidate->profile_data = $profileData;
            $candidate->save();
            $candidate->refresh();

            \Log::info('AI follow-up: candidate profile enriched', [
                'candidate_id' => $candidate->id,
                'application_id' => $application->id,
                'fields_updated' => array_keys(array_filter($validated, fn($v) => $v !== null && $v !== '')),
            ]);
        }

        // --- Re-run AI match with enriched data ---
        $aiAdvisory = null;
        try {
            $orchestratorClient = app(\App\Services\AI\AIOrchestratorClient::class);
            $payload = $this->buildAiMatchPayload($candidate, $job, $application);
            $aiResponse = $orchestratorClient->matchCandidateToJob($payload);

            // Persist sanitized result (same OpenSpec rules as initial match)
            $sanitizedKeys = [
                'fit_score', 'rank_label', 'confidence_label',
                'score_breakdown', 'matched_skills', 'missing_skills',
                'missing_preferred_skills', 'risk_flags',
                'retrieval_method', 'pipeline_version', 'generated_at',
            ];
            $sanitized = array_intersect_key($aiResponse, array_flip($sanitizedKeys));
            $application->update(['ai_match_result' => $sanitized]);
            $aiAdvisory = $sanitized;

            \Log::info('AI follow-up: re-run completed', [
                'application_id' => $application->id,
                'fit_score'      => $aiAdvisory['fit_score'] ?? null,
                'confidence'     => $aiAdvisory['confidence_label'] ?? null,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('AI follow-up: re-run failed for application ' . $application->id . ': ' . $e->getMessage());
        }

        $message = $aiAdvisory
            ? sprintf('✅ Đã cập nhật! AI đã đánh giá lại — Điểm phù hợp: %.1f/10', $aiAdvisory['fit_score'] ?? 0)
            : '✅ Thông tin đã được bổ sung thành công. AI tạm thời chưa khả dụng — kết quả sẽ tự động cập nhật khi AI service hoạt động lại.';

        return redirect()->route('jobs.show', $job->id)
            ->with('status', $message)
            ->with('ai_score', $aiAdvisory['fit_score'] ?? null)
            ->with('ai_advisory', $aiAdvisory)
            ->withFragment('apply-form');
    }

    /**
     * Detect which follow-up fields should be asked based on AI result + candidate profile.
     *
     * Focus: missing/unclear data signals, NOT general match quality.
     * Returns array of field keys from the supported follow-up field set.
     */
    private function detectMissingFollowupFields(array $aiResult, Candidate $candidate, Job $job): array
    {
        $missing = [];
        $profileData = is_array($candidate->profile_data) ? $candidate->profile_data : [];
        $confidenceLow = ($aiResult['confidence_label'] ?? '') === 'low';

        // 1. Empty candidate profile fields → always ask regardless of confidence
        if (!$candidate->phone) {
            $missing[] = 'phone';
        }
        if (!$candidate->education) {
            $missing[] = 'education_level';
        }
        if (empty($profileData['primary_role'] ?? null)) {
            $missing[] = 'primary_role';
        }

        // 2. Low confidence → AI couldn't extract enough, ask core data fields
        if ($confidenceLow) {
            if (!$candidate->experience && !in_array('years_experience', $missing)) {
                $missing[] = 'years_experience';
            }
            if (empty($candidate->skills) && !in_array('key_skills', $missing)) {
                $missing[] = 'key_skills';
            }
        }

        // 3. Risk flags mentioning missing/unclear information
        foreach ($aiResult['risk_flags'] ?? [] as $flag) {
            $lower = mb_strtolower($flag);
            if ((str_contains($lower, 'experience') || str_contains($lower, 'kinh nghiệm'))
                && !in_array('years_experience', $missing)) {
                $missing[] = 'years_experience';
            }
            if ((str_contains($lower, 'education') || str_contains($lower, 'học vấn') || str_contains($lower, 'trình độ'))
                && !in_array('education_level', $missing)) {
                $missing[] = 'education_level';
            }
            if ((str_contains($lower, 'english') || str_contains($lower, 'language') || str_contains($lower, 'tiếng anh'))
                && !in_array('english_level', $missing)) {
                $missing[] = 'english_level';
            }
        }

        // 4. Missing portfolio/GitHub for technical roles — only when there is also
        //    a low-confidence signal (not by default on every dev-role application).
        if ($confidenceLow) {
            $jobTitle = mb_strtolower($job->title ?? '');
            $isDevRole = str_contains($jobTitle, 'developer') || str_contains($jobTitle, 'engineer')
                      || str_contains($jobTitle, 'backend')   || str_contains($jobTitle, 'frontend')
                      || str_contains($jobTitle, 'fullstack')  || str_contains($jobTitle, 'devops');
            if ($isDevRole) {
                if (!$candidate->github_url && !in_array('github_url', $missing)) {
                    $missing[] = 'github_url';
                }
                if (!$candidate->portfolio_url && !in_array('portfolio_url', $missing)) {
                    $missing[] = 'portfolio_url';
                }
            }
        }

        // english_level is only asked when triggered by risk flags (rule #3 above)
        // — not asked unconditionally.

        // Cap at 6 questions to keep the form compact
        return array_slice(array_unique($missing), 0, 6);
    }

    /**
     * Build the AI match payload for AIOrchestratorClient.
     * Used by both apply() and submitFollowup() to avoid duplication.
     */
    private function buildAiMatchPayload(Candidate $candidate, Job $job, Application $application): array
    {
        return [
            'candidate' => [
                'id'               => $candidate->id,
                'name'             => $candidate->name,
                'summary'          => $candidate->summary ?? null,
                'about_me'         => $candidate->about_me ?? null,
                'skills'           => $candidate->skills ?? null,
                'skills_json'      => $candidate->skills_json ?? null,
                'experience'       => $candidate->experience ?? null,
                'education'        => $candidate->education ?? null,
                'work_experiences' => $candidate->work_experiences ?? null,
                'profile_data'     => $candidate->profile_data ?? null,
                'cv_data'          => $application->cv_data ?? null,
            ],
            'job' => [
                'id'           => $job->id,
                'title'        => $job->title,
                'description'  => $job->description ?? null,
                'requirements' => $job->requirements ?? null,
                'location'     => $job->location ?? null,
            ],
            'options'        => ['include_reasoning' => false],
            'application_id' => $application->id,
        ];
    }

    private function candidateNeedsOnboarding(string $email): bool
    {
        // Find by email (plain text comparison after decryption)
        $candidate = Candidate::all()->first(function($c) use ($email) {
            return $c->email === $email;
        });
        
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

    /**
     * Extract text từ file .docx sử dụng PHPWord
     */
    private function extractTextFromDocx(string $filePath): string
    {
        try {
            $phpWord = IOFactory::load($filePath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text .= $this->extractTextFromElement($element);
                }
            }

            // Clean up text
            $text = preg_replace('/\s+/', ' ', $text); // Loại bỏ khoảng trắng thừa
            $text = trim($text);

            return $text;
        } catch (\Throwable $e) {
            // Common local issue on Windows/XAMPP: PHP zip extension not enabled (ZipArchive missing).
            \Log::warning('Error extracting text from docx: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Đệ quy extract text từ các element của PHPWord
     */
    private function extractTextFromElement($element): string
    {
        $text = '';

        // TextRun chứa nhiều Text elements
        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            foreach ($element->getElements() as $childElement) {
                $text .= $this->extractTextFromElement($childElement);
            }
            $text .= "\n";
        }
        // Text element đơn lẻ
        elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $text .= $element->getText() . ' ';
        }
        // Paragraph
        elseif (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $childElement) {
                $text .= $this->extractTextFromElement($childElement);
            }
            $text .= "\n";
        }
        // Fallback cho các element có getText()
        elseif (method_exists($element, 'getText')) {
            $text .= $element->getText() . ' ';
        }
        // Table
        elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $text .= $this->extractTextFromElement($cellElement);
                    }
                }
            }
        }
        return $text;
    }
}
