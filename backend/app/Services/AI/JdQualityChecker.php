<?php

namespace App\Services\AI;

use App\Models\Job;

/**
 * Rule-based JD quality analysis for recruiter feedback.
 *
 * Analyzes a job's structured fields and produces actionable
 * quality assessment without requiring AI/LLM calls.
 * Uses domain classification heuristics to suggest improvements.
 *
 * Output contract: see docs/AI_MATCH_CONTRACTS.md §8
 */
class JdQualityChecker
{
    /**
     * Domain keyword → job family mapping (mirrors ai-service/app/knowledge/domain_keywords.json).
     */
    private const TITLE_FAMILIES = [
        'backend'    => ['backend', 'api', 'server', 'php', 'node', 'java', 'python', 'go ', 'golang', 'laravel', 'django', 'spring'],
        'frontend'   => ['frontend', 'front-end', 'ui ', 'ux ', 'react', 'vue', 'angular', 'javascript', 'typescript', 'giao diện'],
        'fullstack'  => ['fullstack', 'full-stack', 'full stack'],
        'data'       => ['data', 'analytics', 'analyst', 'phân tích', 'bi ', 'business intelligence'],
        'ai_ml'      => ['ai ', 'ml ', 'machine learning', 'deep learning', 'nlp', 'trí tuệ nhân tạo'],
        'devops'     => ['devops', 'sre', 'infrastructure', 'cloud', 'platform', 'hạ tầng'],
        'mobile'     => ['mobile', 'ios', 'android', 'flutter', 'react native', 'di động'],
        'qa'         => ['qa ', 'testing', 'test ', 'quality', 'kiểm thử', 'tester'],
    ];

    /**
     * Related skills commonly expected per family.
     */
    private const FAMILY_SKILLS = [
        'backend'   => ['required' => ['PHP', 'Node.js', 'Python', 'Java', 'Go'], 'preferred' => ['Docker', 'REST API', 'PostgreSQL', 'Redis', 'CI/CD']],
        'frontend'  => ['required' => ['JavaScript', 'TypeScript', 'React', 'Vue.js', 'Angular'], 'preferred' => ['HTML/CSS', 'Tailwind CSS', 'Next.js', 'Git']],
        'fullstack' => ['required' => ['JavaScript', 'Node.js', 'React', 'PostgreSQL'], 'preferred' => ['Docker', 'TypeScript', 'REST API', 'Git']],
        'data'      => ['required' => ['SQL', 'Python', 'Excel'], 'preferred' => ['Tableau', 'Power BI', 'Data Analysis', 'Pandas']],
        'ai_ml'     => ['required' => ['Python', 'Machine Learning'], 'preferred' => ['PyTorch', 'TensorFlow', 'Docker', 'NLP', 'Scikit-learn']],
        'devops'    => ['required' => ['Docker', 'Linux', 'AWS'], 'preferred' => ['Kubernetes', 'Terraform', 'CI/CD', 'Bash/Shell']],
        'mobile'    => ['required' => ['React Native', 'Flutter', 'Swift', 'Kotlin'], 'preferred' => ['Git', 'REST API', 'Firebase']],
        'qa'        => ['required' => ['Testing', 'JavaScript', 'Python'], 'preferred' => ['Docker', 'CI/CD', 'Agile/Scrum']],
    ];

    /**
     * Seniority → experience range hints.
     */
    private const SENIORITY_EXPERIENCE = [
        'intern'    => ['min' => 0, 'max' => 1],
        'fresher'   => ['min' => 0, 'max' => 1],
        'junior'    => ['min' => 1, 'max' => 2],
        'mid'       => ['min' => 2, 'max' => 5],
        'senior'    => ['min' => 5, 'max' => 8],
        'lead'      => ['min' => 5, 'max' => 10],
        'principal' => ['min' => 8, 'max' => 15],
    ];

    /**
     * Analyze a Job model and return quality assessment.
     *
     * @param  Job  $job
     * @return array  Quality assessment matching §8 contract
     */
    public static function analyze(Job $job): array
    {
        $issues = [];
        $suggestions = [];
        $score = 100;

        $title = trim($job->title ?? '');
        $description = trim($job->description ?? '');
        $requirements = trim($job->requirements ?? '');
        $requiredSkills = is_array($job->required_skills) ? $job->required_skills : [];
        $preferredSkills = is_array($job->preferred_skills) ? $job->preferred_skills : [];
        $seniority = $job->seniority;
        $minExp = $job->min_experience_years;
        $maxExp = $job->max_experience_years;

        // ── Check 1: Title quality ──────────────────────────────────
        if (strlen($title) < 5) {
            $issues[] = ['field' => 'title', 'severity' => 'error', 'message' => 'Tiêu đề quá ngắn hoặc chưa có — cần ít nhất 5 ký tự.'];
            $score -= 20;
        } elseif (strlen($title) < 10) {
            $issues[] = ['field' => 'title', 'severity' => 'warning', 'message' => 'Tiêu đề quá ngắn — nên mô tả rõ vị trí (VD: "Senior Backend Developer").'];
            $score -= 5;
        }

        // ── Check 2: Required skills ────────────────────────────────
        if (empty($requiredSkills)) {
            $issues[] = ['field' => 'required_skills', 'severity' => 'error', 'message' => 'Chưa chọn kỹ năng bắt buộc — AI sẽ không thể đánh giá chính xác (chiếm 40% điểm).'];
            $suggestions[] = 'Thêm ít nhất 3 kỹ năng bắt buộc để AI so khớp CV chính xác.';
            $score -= 30;
        } elseif (count($requiredSkills) < 3) {
            $issues[] = ['field' => 'required_skills', 'severity' => 'warning', 'message' => 'Chỉ có ' . count($requiredSkills) . ' kỹ năng bắt buộc — nên có ít nhất 3 để AI đánh giá tốt hơn.'];
            $suggestions[] = 'Thêm kỹ năng bắt buộc để tăng độ chính xác AI matching.';
            $score -= 10;
        } elseif (count($requiredSkills) > 12) {
            $issues[] = ['field' => 'required_skills', 'severity' => 'info', 'message' => 'Có quá nhiều kỹ năng bắt buộc (' . count($requiredSkills) . ') — ứng viên khó đạt điểm cao.'];
            $score -= 5;
        }

        // ── Check 3: Preferred skills ───────────────────────────────
        if (empty($preferredSkills)) {
            $issues[] = ['field' => 'preferred_skills', 'severity' => 'info', 'message' => 'Chưa có kỹ năng ưu tiên — AI sẽ dùng điểm trung lập (15% trọng số).'];
            $score -= 5;
        }

        // ── Check 4: Seniority ──────────────────────────────────────
        if (empty($seniority)) {
            $issues[] = ['field' => 'seniority', 'severity' => 'warning', 'message' => 'Chưa chọn cấp bậc — AI sẽ không thể đánh giá seniority fit (10% trọng số).'];
            $score -= 10;
        }

        // ── Check 5: Experience range ───────────────────────────────
        if ($minExp === null && $maxExp === null) {
            $issues[] = ['field' => 'experience', 'severity' => 'warning', 'message' => 'Chưa chỉ định kinh nghiệm — AI sẽ dùng điểm trung lập (15% trọng số).'];
            $score -= 10;
        } elseif ($minExp !== null && $maxExp !== null && $minExp > $maxExp) {
            $issues[] = ['field' => 'experience', 'severity' => 'error', 'message' => 'Kinh nghiệm tối thiểu lớn hơn tối đa — cần sửa lại.'];
            $score -= 15;
        }

        // ── Check 6: Seniority vs experience consistency ────────────
        if (!empty($seniority) && $minExp !== null) {
            $expected = self::SENIORITY_EXPERIENCE[$seniority] ?? null;
            if ($expected) {
                if ($minExp > $expected['max'] + 2) {
                    $issues[] = [
                        'field' => 'experience',
                        'severity' => 'warning',
                        'message' => "Kinh nghiệm tối thiểu ({$minExp} năm) có vẻ cao hơn mức thường thấy cho cấp {$seniority} ({$expected['min']}-{$expected['max']} năm).",
                    ];
                    $score -= 5;
                }
            }
        }

        // ── Check 7: Description quality ────────────────────────────
        if (strlen($description) < 20) {
            $issues[] = ['field' => 'description', 'severity' => 'warning', 'message' => 'Mô tả công việc quá ngắn hoặc chưa có — nên mô tả rõ vai trò và trách nhiệm.'];
            $suggestions[] = 'Viết mô tả công việc chi tiết (ít nhất 50 từ) giúp AI hiểu ngữ cảnh tốt hơn.';
            $score -= 10;
        } elseif (str_word_count($description) < 15) {
            $issues[] = ['field' => 'description', 'severity' => 'info', 'message' => 'Mô tả công việc ngắn — thêm chi tiết về vai trò sẽ cải thiện matching.'];
            $score -= 3;
        }

        // ── Check 8: Title-skills consistency ───────────────────────
        $detectedFamily = self::detectFamily($title);
        if ($detectedFamily && !empty($requiredSkills)) {
            $familySkills = self::FAMILY_SKILLS[$detectedFamily]['required'] ?? [];
            $overlap = array_intersect(
                array_map('strtolower', $requiredSkills),
                array_map('strtolower', $familySkills)
            );
            if (empty($overlap) && !empty($familySkills)) {
                $familyLabel = self::familyLabel($detectedFamily);
                $issues[] = [
                    'field' => 'required_skills',
                    'severity' => 'info',
                    'message' => "Tiêu đề gợi ý vị trí {$familyLabel} nhưng kỹ năng bắt buộc không trùng khớp — hãy kiểm tra lại.",
                ];
                $score -= 5;
            }
        }

        // ── Build suggestions ───────────────────────────────────────
        $suggestedSkills = self::suggestSkills($title, $requiredSkills, $preferredSkills);
        $suggestedSeniority = null;
        $suggestedExperience = null;

        if (empty($seniority)) {
            $suggestedSeniority = self::inferSeniority($title, $description);
        }

        if ($minExp === null && !empty($seniority)) {
            $suggestedExperience = self::SENIORITY_EXPERIENCE[$seniority] ?? null;
        } elseif ($minExp === null && $suggestedSeniority) {
            $suggestedExperience = self::SENIORITY_EXPERIENCE[$suggestedSeniority] ?? null;
        }

        // Clamp score
        $score = max(0, min(100, $score));

        // Quality label
        if ($score >= 85) {
            $qualityLabel = 'excellent';
        } elseif ($score >= 65) {
            $qualityLabel = 'good';
        } elseif ($score >= 40) {
            $qualityLabel = 'needs_improvement';
        } else {
            $qualityLabel = 'poor';
        }

        return [
            'quality_score'        => $score,
            'quality_label'        => $qualityLabel,
            'issues'               => $issues,
            'suggestions'          => $suggestions,
            'suggested_skills'     => $suggestedSkills,
            'suggested_seniority'  => $suggestedSeniority,
            'suggested_experience' => $suggestedExperience,
        ];
    }

    /**
     * Detect job family from title.
     */
    private static function detectFamily(string $title): ?string
    {
        $lower = mb_strtolower($title);
        foreach (self::TITLE_FAMILIES as $family => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, $kw)) {
                    return $family;
                }
            }
        }
        return null;
    }

    /**
     * Suggest skills based on title detection.
     */
    private static function suggestSkills(string $title, array $existing, array $existingPreferred): ?array
    {
        $family = self::detectFamily($title);
        if (!$family) {
            return null;
        }

        $familyData = self::FAMILY_SKILLS[$family] ?? null;
        if (!$familyData) {
            return null;
        }

        $existingLower = array_map('strtolower', array_merge($existing, $existingPreferred));

        $sugRequired = [];
        foreach ($familyData['required'] as $skill) {
            if (!in_array(strtolower($skill), $existingLower)) {
                $sugRequired[] = $skill;
            }
        }

        $sugPreferred = [];
        foreach ($familyData['preferred'] as $skill) {
            if (!in_array(strtolower($skill), $existingLower)) {
                $sugPreferred[] = $skill;
            }
        }

        if (empty($sugRequired) && empty($sugPreferred)) {
            return null;
        }

        return [
            'required'  => array_slice($sugRequired, 0, 5),
            'preferred' => array_slice($sugPreferred, 0, 5),
        ];
    }

    /**
     * Infer seniority from title/description heuristics.
     */
    private static function inferSeniority(string $title, string $description): ?string
    {
        $text = mb_strtolower($title . ' ' . $description);

        $patterns = [
            'principal' => ['principal', 'architect', 'distinguished', 'fellow'],
            'lead'      => ['lead', 'team lead', 'tech lead', 'trưởng nhóm', 'manager'],
            'senior'    => ['senior', 'sr.', 'sr ', '5+ năm', '5-8 năm'],
            'mid'       => ['mid-level', 'mid level', 'middle', 'intermediate', '3-5 năm', '2-5 năm'],
            'junior'    => ['junior', 'jr.', 'jr ', '1-2 năm'],
            'fresher'   => ['fresher', 'fresh graduate', 'entry-level', 'entry level', 'new grad'],
            'intern'    => ['intern', 'thực tập', 'trainee'],
        ];

        foreach ($patterns as $level => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    return $level;
                }
            }
        }

        return null;
    }

    /**
     * Human-readable label for job family.
     */
    private static function familyLabel(string $family): string
    {
        return match ($family) {
            'backend'   => 'Backend',
            'frontend'  => 'Frontend',
            'fullstack' => 'Fullstack',
            'data'      => 'Data/Analytics',
            'ai_ml'     => 'AI/ML',
            'devops'    => 'DevOps',
            'mobile'    => 'Mobile',
            'qa'        => 'QA/Testing',
            default     => $family,
        };
    }
}
