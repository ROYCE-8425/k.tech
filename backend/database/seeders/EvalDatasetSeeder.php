<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Job;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Phase 5: Curated evaluation dataset for AI Shortlist benchmarking.
 *
 * Creates additional candidate–job pairs with explicit expected-fit labels.
 * Ground truth is derived from skill overlap analysis against job requirements.
 *
 * IMPORTANT: This is a small eval set (~12 pairs). Claims about system
 * accuracy should be qualified: "on this demo eval set", not "in production".
 */
class EvalDatasetSeeder extends Seeder
{
    /**
     * Curated candidates designed to span high/medium/low fit for different jobs.
     */
    private array $evalCandidates = [
        [
            'name'       => 'Eval - Phạm Backend Senior',
            'email'      => 'eval.backend.senior@example.com',
            'phone'      => '0900100001',
            'summary'    => 'Senior Backend Developer 5+ năm Laravel, MySQL, Redis, Docker, Kubernetes, CI/CD. Đã lead team 4 người.',
            'skills_json' => ['Laravel', 'PHP', 'MySQL', 'Redis', 'Docker', 'Kubernetes', 'CI/CD', 'REST API', 'Queue', 'Testing'],
        ],
        [
            'name'       => 'Eval - Vũ Frontend Junior',
            'email'      => 'eval.frontend.junior@example.com',
            'phone'      => '0900100002',
            'summary'    => 'Frontend developer 1 năm kinh nghiệm React cơ bản. Đang học TypeScript.',
            'skills_json' => ['React', 'JavaScript', 'HTML', 'CSS', 'Git'],
        ],
        [
            'name'       => 'Eval - Hoàng Fullstack',
            'email'      => 'eval.fullstack@example.com',
            'phone'      => '0900100003',
            'summary'    => 'Fullstack developer MERN stack 3 năm. Có kinh nghiệm Next.js, MongoDB, Docker.',
            'skills_json' => ['MongoDB', 'Express.js', 'React', 'Node.js', 'TypeScript', 'Next.js', 'Docker', 'REST API', 'Git'],
        ],
        [
            'name'       => 'Eval - Linh Data Analyst',
            'email'      => 'eval.data.analyst@example.com',
            'phone'      => '0900100004',
            'summary'    => 'Data analyst 2 năm, mạnh SQL, Python pandas, Power BI dashboard. Kinh nghiệm A/B testing.',
            'skills_json' => ['SQL', 'Python', 'Power BI', 'Excel', 'Data Visualization', 'A/B Testing', 'pandas', 'numpy'],
        ],
        [
            'name'       => 'Eval - Đức Marketing',
            'email'      => 'eval.marketing@example.com',
            'phone'      => '0900100005',
            'summary'    => 'Digital marketing 3 năm. Facebook Ads, Google Ads, content strategy. Không có kỹ năng kỹ thuật IT.',
            'skills_json' => ['Facebook Ads', 'Google Ads', 'Content Marketing', 'SEO', 'Social Media'],
        ],
    ];

    /**
     * Eval pairs: [candidate_index, job_title_substr, expected_fit, expected_score_range]
     *
     * expected_fit: high_fit (>= 75), medium_fit (55-74), low_fit (< 55)
     */
    private array $evalPairs = [
        // Backend Senior → Backend Developer (Laravel/PHP) → high fit
        [0, 'Backend Developer', 'high_fit', [75, 95]],
        // Backend Senior → DevOps Engineer → medium fit (Docker/K8s overlap but missing AWS/GCP core)
        [0, 'DevOps Engineer', 'medium_fit', [55, 74]],
        // Backend Senior → Frontend Developer → low fit (no React/Next.js)
        [0, 'Senior Frontend Developer', 'low_fit', [20, 54]],

        // Frontend Junior → Frontend Developer → medium fit (React match but lacks seniority/depth)
        [1, 'Senior Frontend Developer', 'medium_fit', [45, 70]],
        // Frontend Junior → Backend Developer → low fit (no Laravel/PHP)
        [1, 'Backend Developer', 'low_fit', [15, 45]],

        // Fullstack → Fullstack Developer (MERN) → high fit
        [2, 'Fullstack Developer', 'high_fit', [75, 95]],
        // Fullstack → Frontend Developer → medium fit (React/TypeScript match, missing depth)
        [2, 'Senior Frontend Developer', 'medium_fit', [55, 74]],

        // Data Analyst → Data Analyst (SQL/Python) → high fit
        [3, 'Data Analyst', 'high_fit', [75, 95]],
        // Data Analyst → ML Engineer (NLP) → medium fit (Python overlap, missing ML/NLP core)
        [3, 'ML Engineer', 'medium_fit', [40, 65]],

        // Marketing → Backend Developer → low fit (completely wrong domain)
        [4, 'Backend Developer', 'low_fit', [5, 30]],
        // Marketing → Digital Marketing Manager → high fit
        [4, 'Digital Marketing', 'high_fit', [70, 95]],
        // Marketing → Data Analyst → low fit (no SQL/Python)
        [4, 'Data Analyst', 'low_fit', [10, 40]],
    ];

    public function run(): void
    {
        $now = Carbon::now();

        // Upsert eval candidates
        $candidates = [];
        foreach ($this->evalCandidates as $c) {
            $candidates[] = Candidate::query()->updateOrCreate(
                ['email' => $c['email']],
                [
                    'name'       => $c['name'],
                    'phone'      => $c['phone'],
                    'file_path_cv' => null,
                    'summary'    => $c['summary'],
                    'skills_json' => $c['skills_json'],
                ]
            );
        }

        // Create eval application pairs
        foreach ($this->evalPairs as [$candIdx, $jobSubstr, $expectedFit, $scoreRange]) {
            $candidate = $candidates[$candIdx] ?? null;
            if (!$candidate) continue;

            // Find the first published job matching the title substring
            $job = Job::query()
                ->where('status', 'published')
                ->where('title', 'LIKE', "%{$jobSubstr}%")
                ->first();

            if (!$job) {
                $this->command?->warn("No published job matching '{$jobSubstr}' — skipping eval pair.");
                continue;
            }

            // Convert expected_fit to a weak manual score (midpoint of range)
            $midScore = ($scoreRange[0] + $scoreRange[1]) / 2;

            Application::query()->updateOrCreate(
                ['job_id' => $job->id, 'candidate_id' => $candidate->id],
                [
                    'status'          => 'reviewing',
                    'cv_manual_score' => round($midScore, 1),
                    'cv_file_path'    => null,
                    'cover_letter'    => 'Eval dataset — expected: ' . $expectedFit,
                    'notes'           => json_encode([
                        'eval_set'       => true,
                        'expected_fit'   => $expectedFit,
                        'score_range'    => $scoreRange,
                    ]),
                    'applied_at'      => $now,
                ]
            );
        }
    }
}
