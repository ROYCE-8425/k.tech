<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\CvScoringProfile;
use App\Models\Job;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CvAutoScoringService
{
    public function __construct(private readonly CvRubricScoringService $rubricScoring)
    {
    }

    /**
     * Auto-score an application using rubric/profile data stored in SQL.
     * Writes results into Application cv_manual_* fields (system-generated; cv_manual_scored_by = null).
     */
    public function scoreAndPersist(Application $application, ?string $cvText = null): ?array
    {
        $application->loadMissing(['job.cvScoringProfile.rubric', 'candidate']);

        $job = $application->job;
        $candidate = $application->candidate;

        if (!$job || !$candidate) {
            return null;
        }

        $profile = $this->resolveProfile($job, $candidate);
        if (!$profile) {
            return null;
        }

        $required = $this->requiredInputSchemaForRubricId((int) $profile->rubric_id);
        if (empty($required)) {
            return null;
        }

        $corpus = $this->buildTextCorpus($application, $candidate, $job, $cvText);
        $signals = $this->buildSignals($application, $candidate, $job, $corpus);

        $inputs = [];
        foreach ($required as $key => $schema) {
            $value = $this->guessInputValue($key, $schema, $signals);
            $inputs[$key] = $value;
        }

        $result = $this->rubricScoring->scoreProfile($profile->key, $inputs);

        $application->cv_manual_inputs = array_merge($inputs, [
            '_auto' => true,
            '_profile_key' => $profile->key,
            '_scored_at' => now()->toISOString(),
        ]);
        $application->cv_manual_breakdown = $result;
        $application->cv_manual_score = (float) ($result['total'] ?? 0);
        $application->cv_manual_grade = (string) ($result['grade']['label'] ?? '');
        $application->cv_manual_scored_at = now();
        $application->cv_manual_scored_by = null;
        $application->save();

        return $result;
    }

    private function resolveProfile(Job $job, Candidate $candidate): ?CvScoringProfile
    {
        if ($job->cv_scoring_profile_id) {
            $p = CvScoringProfile::query()->where('is_active', true)->find($job->cv_scoring_profile_id);
            if ($p) {
                return $p;
            }
        }

        // IT-only system: resolve profile based on IT role
        $sector = 'it';

        // Try new structure (primary_role) first, then legacy (it_role)
        $role = (string) Arr::get($candidate->profile_data ?? [], 'primary_role', '');
        if ($role === '') {
            $role = (string) Arr::get($candidate->profile_data ?? [], 'it_role', '');
        }
        $roleLower = Str::lower($role);
        $key = Str::contains($roleLower, ['qa', 'tester', 'test']) ? 'it_tester' : 'it_dev';
        return CvScoringProfile::query()->where('is_active', true)->where('key', $key)->first();
    }

    /**
     * Returns a map of input_key => schema
     * schema: ['type' => 'number'|'choice', 'choices' => array<string,float>|null]
     */
    private function requiredInputSchemaForRubricId(int $rubricId): array
    {
        $groups = DB::table('cv_rubric_groups')->where('rubric_id', $rubricId)->pluck('id')->all();
        if (empty($groups)) {
            return [];
        }

        $criteria = DB::table('cv_rubric_criteria')->whereIn('group_id', $groups)->get();

        $required = [];
        foreach ($criteria as $c) {
            $cfg = is_string($c->rule_config) ? (json_decode($c->rule_config, true) ?: []) : (is_array($c->rule_config) ? $c->rule_config : []);
            $type = (string) $c->rule_type;

            if ($type === 'per_unit_cap') {
                $key = (string) Arr::get($cfg, 'input_key', '');
                if ($key !== '') {
                    $required[$key] = ['type' => 'number', 'choices' => null];
                }
            } elseif ($type === 'weighted_two_inputs_cap') {
                $major = (string) Arr::get($cfg, 'major_input_key', '');
                $minor = (string) Arr::get($cfg, 'minor_input_key', '');
                if ($major !== '') {
                    $required[$major] = ['type' => 'number', 'choices' => null];
                }
                if ($minor !== '') {
                    $required[$minor] = ['type' => 'number', 'choices' => null];
                }
            } elseif ($type === 'choice_map') {
                $key = (string) Arr::get($cfg, 'input_key', '');
                $choices = (array) Arr::get($cfg, 'choices', []);
                if ($key !== '') {
                    $required[$key] = ['type' => 'choice', 'choices' => $choices];
                }
            }
        }

        return $required;
    }

    private function buildTextCorpus(Application $application, Candidate $candidate, Job $job, ?string $cvText): string
    {
        $parts = [];

        if (is_string($cvText) && trim($cvText) !== '') {
            $parts[] = $cvText;
        }

        $cvData = is_array($application->cv_data) ? $application->cv_data : [];
        $raw = Arr::get($cvData, '_raw_text');
        if (is_string($raw) && trim($raw) !== '') {
            $parts[] = $raw;
        }

        $parts[] = (string) ($candidate->summary ?? '');
        $parts[] = (string) ($candidate->about_me ?? '');

        // CV nhanh fields
        $parts[] = (string) Arr::get($cvData, 'self_description', '');

        $education = Arr::get($cvData, 'education', []);
        if (is_array($education)) {
            foreach ($education as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $school = (string) ($row['school'] ?? '');
                $degree = (string) ($row['degree_level'] ?? '');
                $major = (string) ($row['major'] ?? '');
                
                $parts[] = $school;
                
                // Map degree_level codes to readable text
                $degreeMap = [
                    'trung_cap' => 'trung cấp',
                    'cao_dang' => 'cao đẳng',
                    'cu_nhan' => 'cử nhân bachelor',
                    'ky_su' => 'kỹ sư engineer bachelor',
                    'thac_si' => 'thạc sĩ master',
                    'tien_si' => 'tiến sĩ phd doctor',
                    'bootcamp' => 'bootcamp',
                ];
                $parts[] = $degreeMap[$degree] ?? $degree;
                
                // Add major with CS keywords
                $majorLower = Str::lower($major);
                $parts[] = $major;
                if (Str::contains($majorLower, ['cntt', 'thông tin'])) {
                    $parts[] = 'công nghệ thông tin it computer science software engineering';
                }
                if (Str::contains($majorLower, ['máy tính', 'khoa học'])) {
                    $parts[] = 'khoa học máy tính computer science cs';
                }
            }
        }

        $work = Arr::get($cvData, 'work_experiences', []);
        if (is_array($work)) {
            foreach ($work as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $company = (string) ($row['company_name'] ?? '');
                $position = (string) ($row['position_title'] ?? '');
                $desc = (string) ($row['description'] ?? '');
                
                $parts[] = $company;
                $parts[] = $position;
                $parts[] = $desc;
                
                // Enhance position detection
                $posLower = Str::lower($position);
                if (Str::contains($posLower, ['senior', 'lead', 'architect', 'principal'])) {
                    $parts[] = 'senior experienced expert advanced';
                }
                if (Str::contains($posLower, ['backend', 'back-end'])) {
                    $parts[] = 'backend server-side api database';
                }
                if (Str::contains($posLower, ['developer', 'engineer', 'programmer'])) {
                    $parts[] = 'developer engineer programmer coder software';
                }
            }
        }

        // Job text
        $parts[] = (string) ($job->title ?? '');
        $parts[] = (string) ($job->description ?? '');
        $parts[] = (string) ($job->requirements ?? '');

        return Str::lower(trim(implode("\n", array_filter($parts, fn ($p) => is_string($p) && trim($p) !== ''))));
    }

    private function buildSignals(Application $application, Candidate $candidate, Job $job, string $corpus): array
    {
        $skills = [];

        $profileData = is_array($candidate->profile_data) ? $candidate->profile_data : [];
        
        // New structure: skills is a simple array at root level
        $rootSkills = Arr::get($profileData, 'skills', []);
        if (is_array($rootSkills)) {
            foreach ($rootSkills as $s) {
                $s = trim((string) $s);
                if ($s !== '') {
                    $skills[] = $s;
                }
            }
        }
        
        // New structure: cv_quick.skills.hard/soft
        $cvQuick = Arr::get($profileData, 'cv_quick', []);
        if (is_array($cvQuick)) {
            $cvQuickSkills = Arr::get($cvQuick, 'skills', []);
            if (is_array($cvQuickSkills)) {
                foreach (['hard', 'soft'] as $kind) {
                    $items = $cvQuickSkills[$kind] ?? [];
                    if (is_array($items)) {
                        foreach ($items as $it) {
                            if (is_array($it)) {
                                $name = trim((string) ($it['name'] ?? ''));
                                if ($name !== '') {
                                    $skills[] = $name;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Legacy structure: it_skills only (IT-only system)
        $arr = Arr::get($profileData, 'it_skills', []);
        if (is_array($arr)) {
            foreach ($arr as $s) {
                $s = trim((string) $s);
                if ($s !== '') {
                    $skills[] = $s;
                }
            }
        }

        $cvData = is_array($application->cv_data) ? $application->cv_data : [];
        $skillsData = Arr::get($cvData, 'skills', []);
        if (is_array($skillsData)) {
            foreach (['hard', 'soft'] as $kind) {
                $items = $skillsData[$kind] ?? [];
                if (!is_array($items)) {
                    continue;
                }
                foreach ($items as $it) {
                    if (!is_array($it)) {
                        continue;
                    }
                    $name = trim((string) ($it['name'] ?? ''));
                    if ($name !== '') {
                        $skills[] = $name;
                    }
                }
            }
        }

        // legacy candidate fields - split comma-separated strings
        $legacySkillsText = (string) ($candidate->skills ?? '');
        if (trim($legacySkillsText) !== '') {
            $parts = preg_split('/[,;]+/u', $legacySkillsText);
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $skills[] = $part;
                }
            }
        }

        $skills = array_values(array_unique(array_filter(array_map(fn ($s) => trim($s), $skills))));

        $years = $this->inferYearsExperience($application, $candidate);

        return [
            'candidate' => $candidate,
            'application' => $application,
            'job' => $job,
            'corpus' => $corpus,
            'skills' => $skills,
            'skills_lower' => array_map(fn ($s) => Str::lower($s), $skills),
            'years_experience' => $years,
        ];
    }

    private function inferYearsExperience(Application $application, Candidate $candidate): float
    {
        $cvData = is_array($application->cv_data) ? $application->cv_data : [];
        
        // Priority 1: Check certifications.years_experience field (explicit from CV dialog)
        $certifications = Arr::get($cvData, 'certifications', null);
        if (is_array($certifications)) {
            // Try both field names for compatibility
            $years = $certifications['years_experience'] ?? $certifications['years'] ?? null;
            if ($years !== null && $years > 0) {
                return (float) $years;
            }
        }
        
        // Priority 2: Calculate from work experiences
        $work = Arr::get($cvData, 'work_experiences', null);
        if (is_array($work)) {
            $years = $this->yearsFromWorkExperiences($work);
            if ($years > 0) {
                return $years;
            }
        }

        if (is_array($candidate->work_experiences)) {
            $years = $this->yearsFromWorkExperiences($candidate->work_experiences);
            if ($years > 0) {
                return $years;
            }
        }

        $label = (string) ($candidate->experience ?? '');
        $labelLower = Str::lower($label);
        if (Str::contains($labelLower, 'fresher')) {
            return 0.5;
        }
        if (preg_match('/(\d+)\s*[-–]\s*(\d+)/u', $labelLower, $m)) {
            $a = (float) $m[1];
            $b = (float) $m[2];
            return max(0.0, ($a + $b) / 2.0);
        }
        if (preg_match('/(\d+)\s*\+/u', $labelLower, $m)) {
            return (float) $m[1];
        }

        return 0.0;
    }

    private function yearsFromWorkExperiences(array $rows): float
    {
        $earliest = null;
        $latest = null;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $start = (string) ($row['start_date'] ?? '');
            $end = (string) ($row['end_date'] ?? '');
            $isCurrent = (bool) ($row['is_current'] ?? false);

            $startDt = $this->parseDate($start);
            if (!$startDt) {
                continue;
            }

            $endDt = null;
            if ($isCurrent) {
                $endDt = new \DateTimeImmutable('now');
            } else {
                $endDt = $this->parseDate($end);
                // Some UIs store month-only (YYYY-MM) or omit end_date for current roles.
                if (!$endDt) {
                    $endDt = new \DateTimeImmutable('now');
                }
            }

            if (!$endDt) {
                continue;
            }

            if ($earliest === null || $startDt < $earliest) {
                $earliest = $startDt;
            }
            if ($latest === null || $endDt > $latest) {
                $latest = $endDt;
            }
        }

        if (!$earliest || !$latest || $latest < $earliest) {
            return 0.0;
        }

        $days = (float) $latest->diff($earliest)->days;
        return round($days / 365.0, 2);
    }

    private function parseDate(string $ymd): ?\DateTimeImmutable
    {
        $ymd = trim($ymd);
        if ($ymd === '' || $ymd === 'null') {
            return null;
        }

        // Normalize separators: 2021/01 -> 2021-01
        $s = str_replace(['/', '.'], '-', $ymd);
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = trim($s);

        // Support common stored formats: YYYY-MM (month only), YYYY (year only)
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $s, $m)) {
            $s = sprintf('%04d-%02d-01', (int) $m[1], (int) $m[2]);
        } elseif (preg_match('/^(\d{1,2})-(\d{4})$/', $s, $m)) {
            // MM-YYYY
            $s = sprintf('%04d-%02d-01', (int) $m[2], (int) $m[1]);
        } elseif (preg_match('/^(\d{4})$/', $s, $m)) {
            $s = sprintf('%04d-01-01', (int) $m[1]);
        }

        foreach (['Y-m-d', 'd-m-Y', 'm-d-Y'] as $fmt) {
            $dt = \DateTimeImmutable::createFromFormat($fmt, $s);
            if (!$dt) {
                continue;
            }
            $errors = \DateTimeImmutable::getLastErrors();
            if (is_array($errors) && ($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0) {
                return $dt;
            }
        }

        // Last resort: DateTime parser (best-effort)
        try {
            return new \DateTimeImmutable($s);
        } catch (\Throwable) {
            return null;
        }
    }

    private function guessInputValue(string $key, array $schema, array $signals)
    {
        $corpus = (string) ($signals['corpus'] ?? '');
        $skillsLower = (array) ($signals['skills_lower'] ?? []);

        // Common numeric patterns
        if ($schema['type'] === 'number') {
            return match (true) {
                $key === 'years_experience' => (float) ($signals['years_experience'] ?? 0),
                Str::endsWith($key, 'years_experience') => (float) ($signals['years_experience'] ?? 0),

                // IT heuristics
                $key === 'matching_projects' => $this->inferProjectCount($signals),
                $key === 'matching_technologies' => $this->inferMatchingTechCount($signals),
                $key === 'major_skill_matches' => $this->inferSkillMatchesInJobText($signals, true),
                $key === 'minor_skill_matches' => $this->inferSkillMatchesInJobText($signals, false),
                $key === 'professional_cert_count' => $this->inferCertCount($signals),
                $key === 'soft_skills_with_examples' => $this->inferSoftSkillCount($signals),

                // IT project and portfolio counts
                Str::endsWith($key, '_project_count') => $this->inferProjectCount($signals),
                Str::endsWith($key, '_portfolio_count') => $this->inferPortfolioCount($signals),
                Str::endsWith($key, '_cert_count') => $this->inferCertCount($signals),

                default => 0,
            };
        }

        // choice_map
        $choices = is_array($schema['choices'] ?? null) ? $schema['choices'] : [];

        $value = match ($key) {
            // IT
            'education_level' => $this->inferItEducationLevel($corpus),
            'it_school_tier' => $this->inferItSchoolTier($corpus),
            'cv_structure' => $this->inferCvStructure($signals),
            'portfolio_quality' => $this->inferPortfolioQuality($signals),

            // Digital marketing / content / PR / etc
            default => $this->inferGenericYesNoOrDefault($key, $corpus),
        };

        if (is_string($value) && $value !== '' && array_key_exists($value, $choices)) {
            return $value;
        }

        // fallback: choose the lowest-scoring option (defensive, avoids over-scoring)
        if (!empty($choices)) {
            $minKey = null;
            $minScore = null;
            foreach ($choices as $k => $score) {
                $s = is_numeric($score) ? (float) $score : 0.0;
                if ($minScore === null || $s < $minScore) {
                    $minScore = $s;
                    $minKey = (string) $k;
                }
            }
            return $minKey;
        }

        return null;
    }

    private function inferProjectCount(array $signals): float
    {
        $application = $signals['application'];
        $candidate = $signals['candidate'];

        $cvData = is_array($application->cv_data) ? $application->cv_data : [];
        $work = Arr::get($cvData, 'work_experiences', null);
        if (is_array($work) && count($work) > 0) {
            return (float) min(5, count($work));
        }

        if (is_array($candidate->work_experiences) && count($candidate->work_experiences) > 0) {
            return (float) min(5, count($candidate->work_experiences));
        }

        return 0.0;
    }

    private function inferMatchingTechCount(array $signals): float
    {
        $job = $signals['job'];
        $skills = (array) ($signals['skills'] ?? []);

        $req = Str::lower((string) ($job->requirements ?? ''));
        $desc = Str::lower((string) ($job->description ?? ''));
        $title = Str::lower((string) ($job->title ?? ''));
        $text = trim($req . ' ' . $desc . ' ' . $title);
        $textNorm = preg_replace('/[\\s\\.\\-\\/_\\\\]+/u', '', $text);

        $count = 0;
        foreach ($skills as $s) {
            $needle = Str::lower((string) $s);
            if ($needle === '' || $text === '') {
                continue;
            }

            if (Str::contains($text, $needle)) {
                $count++;
                continue;
            }

            // Tolerate punctuation differences: "node.js" vs "nodejs"
            $needleNorm = preg_replace('/[\\s\\.\\-\\/_\\\\]+/u', '', $needle);
            if ($needleNorm !== '' && $textNorm !== null && Str::contains((string) $textNorm, (string) $needleNorm)) {
                $count++;
            }
        }

        return (float) min(10, $count);
    }

    private function inferSkillMatchesInJobText(array $signals, bool $major): float
    {
        $job = $signals['job'];
        $skills = (array) ($signals['skills'] ?? []);

        // Check both requirements AND description for better matching
        $requirements = Str::lower((string) ($job->requirements ?? ''));
        $description = Str::lower((string) ($job->description ?? ''));
        $title = Str::lower((string) ($job->title ?? ''));
        $text = $requirements . ' ' . $description . ' ' . $title;

        $textNorm = preg_replace('/[\\s\\.\\-\\/_\\\\]+/u', '', $text);

        if (trim($text) === '') {
            return 0.0;
        }

        $count = 0;
        foreach ($skills as $s) {
            $needle = Str::lower((string) $s);
            if ($needle === '') {
                continue;
            }

            if (Str::contains($text, $needle)) {
                $count++;
                continue;
            }

            $needleNorm = preg_replace('/[\\s\\.\\-\\/_\\\\]+/u', '', $needle);
            if ($needleNorm !== '' && $textNorm !== null && Str::contains((string) $textNorm, (string) $needleNorm)) {
                $count++;
            }
        }
        
        // Give bonus for comprehensive skill match
        if ($major && $count >= 5) {
            $count = (int)($count * 1.2); // 20% bonus
        }

        return (float) min(20, $count);
    }

    private function inferCertCount(array $signals): float
    {
        $application = $signals['application'];
        $candidate = $signals['candidate'];
        $cvData = is_array($application->cv_data) ? $application->cv_data : [];
        
        // Priority 1: Count from cv_data certifications array
        $certifications = Arr::get($cvData, 'certifications', null);
        if (is_array($certifications)) {
            $certs = Arr::get($certifications, 'certifications', []);
            if (is_array($certs) && count($certs) > 0) {
                return (float) min(10, count($certs));
            }
        }
        
        // Priority 2: Count from profile_data
        if (is_array($candidate->profile_data)) {
            $cvQuick = Arr::get($candidate->profile_data, 'cv_quick', []);
            $quickCerts = Arr::get($cvQuick, 'certifications.certifications', []);
            if (is_array($quickCerts) && count($quickCerts) > 0) {
                return (float) min(10, count($quickCerts));
            }
        }
        
        // Fallback: Parse text
        $text = (string) ($candidate->certifications ?? '');
        if (trim($text) === '') {
            return 0.0;
        }

        $parts = preg_split('/[\r\n,;]+/u', $text) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
        return (float) min(10, count($parts));
    }

    private function inferSoftSkillCount(array $signals): float
    {
        $application = $signals['application'];
        $cvData = is_array($application->cv_data) ? $application->cv_data : [];
        $skillsData = Arr::get($cvData, 'skills.soft', []);
        if (is_array($skillsData) && count($skillsData) > 0) {
            return (float) min(4, count($skillsData));
        }

        $corpus = (string) ($signals['corpus'] ?? '');
        $soft = [
            'giao tiếp', 'communication', 'teamwork', 'làm việc nhóm',
            'problem solving', 'giải quyết vấn đề', 'tư duy', 'chủ động',
            'leadership', 'lãnh đạo', 'time management', 'quản lý thời gian',
        ];

        $hit = 0;
        foreach ($soft as $w) {
            if (Str::contains($corpus, Str::lower($w))) {
                $hit++;
            }
        }

        return (float) min(4, $hit);
    }

    private function inferItSchoolTier(string $corpus): string
    {
        // Top tier schools in Vietnam (top1)
        $top1 = [
            'bách khoa', 'đại học bách khoa',
            'rmit', 'fpt', 'đại học fpt',
            'khoa học tự nhiên', 'đại học khoa học tự nhiên',
            'công nghệ', 'đại học công nghệ',
            'nus', 'singapore', 'mit', 'stanford', 'berkeley', 'cambridge', 'oxford',
            'melbourne', 'sydney', 'tokyo', 'seoul national',
        ];
        foreach ($top1 as $k) {
            if (Str::contains($corpus, Str::lower($k))) {
                return 'top1';
            }
        }

        // Mid tier - decent universities (top2)
        $top2 = [
            'kinh tế', 'thương mại', 'ngoại thương',
            'sư phạm', 'duy tân', 'tôn đức thắng',
            'hutech', 'đại học mở', 'greenwich',
        ];
        foreach ($top2 as $k) {
            if (Str::contains($corpus, Str::lower($k))) {
                return 'top2';
            }
        }

        return 'other';
    }

    private function inferItEducationLevel(string $corpus): string
    {
        $cs = ['cntt', 'công nghệ thông tin', 'khoa học máy tính', 'computer science', 'software engineering', 'information technology', 'it'];
        foreach ($cs as $k) {
            if (Str::contains($corpus, Str::lower($k))) {
                return 'cs';
            }
        }

        $related = ['hệ thống thông tin', 'mạng máy tính', 'data', 'toán', 'điện', 'điện tử', 'viễn thông', 'tự động hóa'];
        foreach ($related as $k) {
            if (Str::contains($corpus, Str::lower($k))) {
                return 'related';
            }
        }

        return 'other';
    }

    private function inferCvStructure(array $signals): string
    {
        $application = $signals['application'];
        $cvData = is_array($application->cv_data) ? $application->cv_data : [];

        $hasSummary = (string) Arr::get($cvData, 'self_description', '') !== '';
        $eduCount = is_array(Arr::get($cvData, 'education')) ? count((array) Arr::get($cvData, 'education')) : 0;
        $workCount = is_array(Arr::get($cvData, 'work_experiences')) ? count((array) Arr::get($cvData, 'work_experiences')) : 0;
        $skillsHard = is_array(Arr::get($cvData, 'skills.hard')) ? count((array) Arr::get($cvData, 'skills.hard')) : 0;

        $score = 0;
        if ($hasSummary) $score++;
        if ($eduCount >= 1) $score++;
        if ($workCount >= 1) $score++;
        if ($skillsHard >= 3) $score++;

        if ($score >= 3) {
            return 'good';
        }
        if ($score >= 2) {
            return 'fair';
        }

        $raw = (string) Arr::get($cvData, '_raw_text', '');
        $len = mb_strlen($raw);
        if ($len >= 800) {
            return 'good';
        }
        if ($len >= 300) {
            return 'fair';
        }

        return 'poor';
    }

    private function inferPortfolioQuality(array $signals): string
    {
        $candidate = $signals['candidate'];

        $github = trim((string) ($candidate->github_url ?? ''));
        $portfolio = trim((string) ($candidate->portfolio_url ?? ''));
        $linkedin = trim((string) ($candidate->linkedin_url ?? ''));

        if ($github !== '' || $portfolio !== '') {
            return 'good';
        }
        if ($linkedin !== '') {
            return 'weak';
        }

        return 'none';
    }

    private function inferGenericYesNoOrDefault(string $key, string $corpus): ?string
    {
        // IT rubrics may use yes/no for certain criteria
        if (Str::endsWith($key, ['_has_case', '_measurable', '_viral_content', '_community_build', '_risk_management', '_design_thinking', '_big_award'])) {
            return Str::contains($corpus, ['case', 'roi', 'cpa', 'kpi', 'views', 'share', 'traffic', 'conversion', 'tăng', 'giảm']) ? 'yes' : 'no';
        }

        // Degree rank heuristics
        if (Str::endsWith($key, 'degree_rank')) {
            if (Str::contains($corpus, ['xuất sắc', 'excellent'])) {
                return 'excellent';
            }
            if (Str::contains($corpus, ['giỏi', 'good'])) {
                return 'good';
            }
            return 'fair';
        }

        // School tier heuristics (very rough)
        if (Str::endsWith($key, 'school_tier') || $key === 'smm_school') {
            if (Str::contains($corpus, ['bách khoa', 'rmit'])) {
                return 'top1';
            }
            if (Str::contains($corpus, ['khoa học tự nhiên', 'fpt', 'ueh', 'ftu'])) {
                return 'top2';
            }
            return 'other';
        }

        return null;
    }

    private function inferPortfolioCount(array $signals): float
    {
        $candidate = $signals['candidate'];
        $portfolio = trim((string) ($candidate->portfolio_url ?? ''));
        $github = trim((string) ($candidate->github_url ?? ''));
        if ($portfolio !== '' || $github !== '') {
            return 3.0;
        }
        return 0.0;
    }

    private function hasAnySkill(array $skillsLower, array $needles): bool
    {
        foreach ($needles as $n) {
            $n = Str::lower((string) $n);
            foreach ($skillsLower as $s) {
                if ($s === $n || Str::contains($s, $n)) {
                    return true;
                }
            }
        }
        return false;
    }
}
