<?php

namespace App\Services\ML;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Job;
use Illuminate\Support\Arr;

/**
 * ML Feature Extractor
 * 
 * Trích xuất features từ dữ liệu ứng viên (form web) để đưa vào ML model.
 * Chuyển đổi dữ liệu thô thành features số học chuẩn hóa.
 * 
 * Features được chia thành 3 nhóm:
 * - Nhóm A: Kinh nghiệm & Dự án
 * - Nhóm B: Kỹ năng
 * - Nhóm C: Yếu tố phụ
 * 
 * @author IT Solo Leveling Team
 */
class MLFeatureExtractor
{
    /**
     * Định nghĩa các features
     */
    public const FEATURES = [
        // Nhóm A: Kinh nghiệm & Dự án
        'experience_years' => ['group' => 'A', 'type' => 'numeric', 'max' => 15],
        'projects_count' => ['group' => 'A', 'type' => 'numeric', 'max' => 10],
        'tech_match_count' => ['group' => 'A', 'type' => 'numeric', 'max' => 10],
        
        // Nhóm B: Kỹ năng
        'main_skills_count' => ['group' => 'B', 'type' => 'numeric', 'max' => 6],
        'sub_skills_count' => ['group' => 'B', 'type' => 'numeric', 'max' => 5],
        'certifications_count' => ['group' => 'B', 'type' => 'numeric', 'max' => 5],
        
        // Nhóm C: Yếu tố phụ
        'education_score' => ['group' => 'C', 'type' => 'numeric', 'max' => 10],
        'cv_quality_score' => ['group' => 'C', 'type' => 'numeric', 'max' => 10],
        'soft_skills_count' => ['group' => 'C', 'type' => 'numeric', 'max' => 6],
        'portfolio_score' => ['group' => 'C', 'type' => 'numeric', 'max' => 5],
    ];

    /**
     * Mapping học vấn sang điểm
     */
    public const EDUCATION_MAPPING = [
        'cntt' => 10,           // Đại học CNTT
        'it' => 10,
        'computer_science' => 10,
        'software_engineering' => 10,
        'lien_quan' => 6,       // Ngành liên quan (Toán, Điện tử, ...)
        'related' => 6,
        'electronics' => 6,
        'mathematics' => 6,
        'khac' => 3,            // Ngành khác
        'other' => 3,
        'none' => 1,
    ];

    /**
     * Mapping chất lượng CV
     */
    public const CV_QUALITY_MAPPING = [
        'excellent' => 10,
        'good' => 8,
        'fair' => 6,
        'average' => 5,
        'poor' => 2,
    ];

    /**
     * Mapping portfolio
     */
    public const PORTFOLIO_MAPPING = [
        'excellent' => 5,       // GitHub active, nhiều projects
        'good' => 4,
        'average' => 2,         // Có nhưng ít
        'none' => 0,            // Không có
    ];

    /**
     * Trích xuất features từ Application
     * 
     * @param Application $application
     * @param Job|null $job Job để so sánh requirements
     * @return array Features đã trích xuất
     */
    public function extractFromApplication(Application $application, ?Job $job = null): array
    {
        $candidate = $application->candidate;
        $job = $job ?? $application->job;
        
        if (!$candidate) {
            return $this->getEmptyFeatures();
        }
        
        return $this->extract($candidate, $job);
    }

    /**
     * Trích xuất features từ Candidate
     * 
     * @param Candidate $candidate
     * @param Job|null $job Job để so sánh requirements
     * @return array Features đã trích xuất
     */
    public function extract(Candidate $candidate, ?Job $job = null): array
    {
        $profileData = $candidate->profile_data ?? [];
        
        // Flatten cv_quick into profileData for easier access
        $cvQuick = $profileData['cv_quick'] ?? [];
        $flatData = array_merge($profileData, $cvQuick);
        
        $jobRequirements = $job ? $this->extractJobRequirements($job) : [];
        
        return [
            // Nhóm A: Kinh nghiệm & Dự án
            'experience_years' => $this->extractExperienceYears($flatData),
            'projects_count' => $this->extractProjectsCount($flatData),
            'tech_match_count' => $this->extractTechMatchCount($flatData, $jobRequirements),
            
            // Nhóm B: Kỹ năng
            'main_skills_count' => $this->extractMainSkillsCount($flatData, $jobRequirements),
            'sub_skills_count' => $this->extractSubSkillsCount($flatData, $jobRequirements),
            'certifications_count' => $this->extractCertificationsCount($flatData),
            
            // Nhóm C: Yếu tố phụ
            'education_score' => $this->extractEducationScore($flatData),
            'cv_quality_score' => $this->extractCvQualityScore($candidate),
            'soft_skills_count' => $this->extractSoftSkillsCount($flatData),
            'portfolio_score' => $this->extractPortfolioScore($flatData),
        ];
    }

    /**
     * Trích xuất features từ array data (cho batch processing)
     * 
     * @param array $data Raw data từ form
     * @param array $jobRequirements Requirements từ job
     * @return array Features
     */
    public function extractFromArray(array $data, array $jobRequirements = []): array
    {
        return [
            'experience_years' => $this->extractExperienceYears($data),
            'projects_count' => $this->extractProjectsCount($data),
            'tech_match_count' => $this->extractTechMatchCount($data, $jobRequirements),
            'main_skills_count' => $this->extractMainSkillsCount($data, $jobRequirements),
            'sub_skills_count' => $this->extractSubSkillsCount($data, $jobRequirements),
            'certifications_count' => $this->extractCertificationsCount($data),
            'education_score' => $this->extractEducationScore($data),
            'cv_quality_score' => (float) Arr::get($data, 'cv_quality_score', 5),
            'soft_skills_count' => $this->extractSoftSkillsCount($data),
            'portfolio_score' => $this->extractPortfolioScore($data),
        ];
    }

    /**
     * Lấy feature names theo thứ tự
     */
    public function getFeatureNames(): array
    {
        return array_keys(self::FEATURES);
    }

    /**
     * Lấy features theo nhóm
     */
    public function getFeaturesByGroup(string $group): array
    {
        return array_keys(array_filter(
            self::FEATURES,
            fn($config) => $config['group'] === $group
        ));
    }

    /**
     * Trả về empty features
     */
    public function getEmptyFeatures(): array
    {
        return array_fill_keys(array_keys(self::FEATURES), 0);
    }

    /**
     * Chuẩn hóa features về khoảng [0, 1]
     */
    public function normalizeFeatures(array $features): array
    {
        $normalized = [];
        
        foreach ($features as $name => $value) {
            if (isset(self::FEATURES[$name])) {
                $max = self::FEATURES[$name]['max'];
                $normalized[$name] = min(1.0, max(0.0, $value / $max));
            } else {
                $normalized[$name] = $value;
            }
        }
        
        return $normalized;
    }

    /**
     * Chuyển features thành array 1D để đưa vào model
     */
    public function featuresToArray(array $features): array
    {
        $result = [];
        foreach ($this->getFeatureNames() as $name) {
            $result[] = (float) ($features[$name] ?? 0);
        }
        return $result;
    }

    // ========================================================================
    // PRIVATE METHODS - Trích xuất từng feature
    // ========================================================================

    /**
     * Trích xuất số năm kinh nghiệm
     */
    private function extractExperienceYears(array $data): float
    {
        // Thử lấy trực tiếp từ certifications
        $directYears = Arr::get($data, 'certifications.years_experience')
            ?? Arr::get($data, 'experience_years')
            ?? Arr::get($data, 'years_of_experience')
            ?? null;
        
        if (is_numeric($directYears)) {
            return min(15, max(0, (float) $directYears));
        }
        
        // Tính từ work_experiences
        $workExp = Arr::get($data, 'work_experiences') ?? Arr::get($data, 'work_experience') ?? [];
        
        if (is_array($workExp) && !empty($workExp)) {
            $totalMonths = 0;
            $now = new \DateTime();
            
            foreach ($workExp as $exp) {
                if (!is_array($exp)) continue;
                
                $startDate = $exp['start_date'] ?? null;
                $endDate = $exp['end_date'] ?? null;
                $isCurrent = $exp['is_current'] ?? false;
                
                if ($startDate) {
                    try {
                        $start = new \DateTime($startDate);
                        $end = ($isCurrent || !$endDate) ? $now : new \DateTime($endDate);
                        $diff = $start->diff($end);
                        $totalMonths += ($diff->y * 12) + $diff->m;
                    } catch (\Exception $e) {
                        // Invalid date format
                    }
                }
            }
            
            if ($totalMonths > 0) {
                return min(15, round($totalMonths / 12, 1));
            }
        }
        
        // Fallback: parse string
        $experience = Arr::get($data, 'experience') ?? '';
        if (is_string($experience)) {
            return min(15, max(0, $this->parseExperienceString($experience)));
        }
        
        return 0;
    }

    /**
     * Parse experience string như "3-5 years"
     */
    private function parseExperienceString(string $str): float
    {
        // Pattern: "5+ years", "3-5 years", "5 năm", etc.
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $str, $matches)) {
            return ((float) $matches[1] + (float) $matches[2]) / 2;
        }
        
        if (preg_match('/(\d+)\+/', $str, $matches)) {
            return (float) $matches[1];
        }
        
        if (preg_match('/(\d+)/', $str, $matches)) {
            return (float) $matches[1];
        }
        
        return 0;
    }

    /**
     * Trích xuất số dự án tiêu biểu
     */
    private function extractProjectsCount(array $data): int
    {
        // Nếu có danh sách projects
        $projects = Arr::get($data, 'projects')
            ?? Arr::get($data, 'notable_projects')
            ?? Arr::get($data, 'work_experience.projects')
            ?? [];
        
        if (is_array($projects) && !empty($projects)) {
            // Đếm projects - nếu mỗi item là array, đếm tất cả
            $validProjects = 0;
            foreach ($projects as $project) {
                if (is_array($project)) {
                    // Có thông tin nào cũng tính
                    if (!empty($project)) {
                        $validProjects++;
                    }
                } elseif (!empty($project)) {
                    // String project name
                    $validProjects++;
                }
            }
            return min(10, max(1, $validProjects)); // Ít nhất 1 nếu có projects
        }
        
        // Nếu là số
        return min(10, max(0, (int) ($projects ?: Arr::get($data, 'projects_count', 0))));
    }

    /**
     * Trích xuất số công nghệ match với job
     */
    private function extractTechMatchCount(array $data, array $jobRequirements): int
    {
        $candidateTech = $this->extractTechnologies($data);
        $requiredTech = $jobRequirements['technologies'] ?? [];
        
        if (empty($requiredTech)) {
            // Không có job requirements, đếm tổng số tech
            return min(10, count($candidateTech));
        }
        
        // Đếm số tech trùng khớp
        $matchCount = 0;
        foreach ($candidateTech as $tech) {
            $techLower = strtolower(trim($tech));
            foreach ($requiredTech as $required) {
                if (str_contains($techLower, strtolower($required)) ||
                    str_contains(strtolower($required), $techLower)) {
                    $matchCount++;
                    break;
                }
            }
        }
        
        return min(10, $matchCount);
    }

    /**
     * Trích xuất danh sách technologies từ data
     */
    private function extractTechnologies(array $data): array
    {
        $technologies = Arr::get($data, 'technologies')
            ?? Arr::get($data, 'tech_stack')
            ?? Arr::get($data, 'skills.technical')
            ?? [];
        
        if (is_string($technologies)) {
            $technologies = array_map('trim', explode(',', $technologies));
        }
        
        return array_filter((array) $technologies);
    }

    /**
     * Trích xuất số kỹ năng chính match với job
     */
    private function extractMainSkillsCount(array $data, array $jobRequirements): int
    {
        // Try various paths to get skills
        $candidateSkills = Arr::get($data, 'main_skills')
            ?? Arr::get($data, 'skills.primary')
            ?? Arr::get($data, 'skills.main')
            ?? Arr::get($data, 'primary_skills')
            ?? [];
        
        // Handle cv_quick structure: skills.hard is array of {name, level}
        $hardSkills = Arr::get($data, 'skills.hard') ?? [];
        if (!empty($hardSkills) && is_array($hardSkills)) {
            $candidateSkills = array_map(function($skill) {
                return is_array($skill) ? ($skill['name'] ?? '') : $skill;
            }, $hardSkills);
        }
        
        // Also check top-level 'skills' array (simple string array)
        if (empty($candidateSkills)) {
            $topSkills = Arr::get($data, 'skills');
            if (is_array($topSkills) && !isset($topSkills['hard'])) {
                $candidateSkills = $topSkills;
            }
        }
        
        if (is_string($candidateSkills)) {
            $candidateSkills = array_map('trim', explode(',', $candidateSkills));
        }
        
        // Ensure it's an array
        $candidateSkills = array_filter((array) $candidateSkills);
        
        $requiredSkills = $jobRequirements['main_skills'] ?? [];
        
        if (empty($requiredSkills)) {
            return min(6, count($candidateSkills));
        }
        
        $matchCount = 0;
        foreach ($candidateSkills as $skill) {
            foreach ($requiredSkills as $required) {
                if (str_contains(strtolower((string) $skill), strtolower((string) $required)) ||
                    str_contains(strtolower((string) $required), strtolower((string) $skill))) {
                    $matchCount++;
                    break;
                }
            }
        }
        
        return min(6, $matchCount);
    }

    /**
     * Trích xuất số kỹ năng phụ
     */
    private function extractSubSkillsCount(array $data, array $jobRequirements): int
    {
        $subSkills = Arr::get($data, 'sub_skills')
            ?? Arr::get($data, 'skills.secondary')
            ?? Arr::get($data, 'skills.sub')
            ?? Arr::get($data, 'secondary_skills')
            ?? [];
        
        if (is_string($subSkills)) {
            $subSkills = array_map('trim', explode(',', $subSkills));
        }
        
        return min(5, count(array_filter((array) $subSkills)));
    }

    /**
     * Trích xuất số chứng chỉ
     */
    private function extractCertificationsCount(array $data): int
    {
        // Handle cv_quick structure: certifications.certifications is array of strings
        $certifications = Arr::get($data, 'certifications.certifications')
            ?? Arr::get($data, 'certifications')
            ?? Arr::get($data, 'certificates')
            ?? Arr::get($data, 'credentials')
            ?? [];
        
        // If certifications is an object with certifications key
        if (is_array($certifications) && isset($certifications['certifications'])) {
            $certifications = $certifications['certifications'];
        }
        
        if (is_string($certifications)) {
            $certifications = array_map('trim', explode(',', $certifications));
        }
        
        return min(5, count(array_filter((array) $certifications)));
    }

    /**
     * Trích xuất điểm học vấn
     */
    private function extractEducationScore(array $data): float
    {
        $education = Arr::get($data, 'education')
            ?? Arr::get($data, 'degree')
            ?? '';
        
        $field = '';
        $degreeLevel = '';
        
        // Handle cv_quick structure: education is array of education objects
        if (is_array($education) && !empty($education)) {
            // If it's an array of education records, get the highest degree
            if (isset($education[0]) && is_array($education[0])) {
                // Find the best education (prefer IT-related, higher degree)
                $bestScore = 0;
                foreach ($education as $edu) {
                    $major = strtolower($edu['major'] ?? '');
                    $degree = $edu['degree_level'] ?? '';
                    
                    $score = 0;
                    // Check if IT-related
                    foreach (['cntt', 'it', 'computer', 'software', 'thông tin'] as $itKey) {
                        if (str_contains($major, $itKey)) {
                            $score += 5;
                            break;
                        }
                    }
                    // Higher degree = higher score
                    if (in_array($degree, ['thac_si', 'tien_si', 'master', 'phd'])) {
                        $score += 3;
                    } elseif (in_array($degree, ['ky_su', 'cu_nhan', 'bachelor'])) {
                        $score += 2;
                    }
                    
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $field = $major;
                        $degreeLevel = $degree;
                    }
                }
            } else {
                // Single education object
                $field = $education['major'] ?? $education['field'] ?? '';
                $degreeLevel = $education['degree_level'] ?? $education['degree'] ?? '';
            }
        }
        
        // Try other paths for field
        if (empty($field)) {
            $field = Arr::get($data, 'education_field')
                ?? Arr::get($data, 'field_of_study')
                ?? '';
        }
        
        // Nếu đã có score
        if (is_numeric($education)) {
            return min(10, max(0, (float) $education));
        }
        
        // Map theo field
        $combined = strtolower((string) $degreeLevel . ' ' . (string) $field);
        
        foreach (self::EDUCATION_MAPPING as $key => $score) {
            if (str_contains($combined, $key)) {
                return (float) $score;
            }
        }
        
        // If we found education data but no match, give partial score
        if (!empty($field) || !empty($degreeLevel)) {
            return 5.0;
        }
        
        return 3.0; // Default
    }

    /**
     * Trích xuất điểm chất lượng CV
     */
    private function extractCvQualityScore(Candidate $candidate): float
    {
        // Nếu đã được đánh giá
        $quality = $candidate->cv_quality ?? null;
        
        if ($quality && isset(self::CV_QUALITY_MAPPING[$quality])) {
            return (float) self::CV_QUALITY_MAPPING[$quality];
        }
        
        // Tự động đánh giá dựa trên completeness
        $profileData = $candidate->profile_data ?? [];
        $completeness = $this->calculateProfileCompleteness($profileData);
        
        if ($completeness >= 0.9) return 10.0;
        if ($completeness >= 0.75) return 8.0;
        if ($completeness >= 0.6) return 6.0;
        if ($completeness >= 0.4) return 4.0;
        return 2.0;
    }

    /**
     * Tính độ hoàn thiện của profile
     */
    private function calculateProfileCompleteness(array $data): float
    {
        $importantFields = [
            'experience_years', 'years_of_experience', 'experience',
            'projects', 'notable_projects',
            'skills', 'main_skills', 'technologies',
            'education', 'degree',
            'certifications',
        ];
        
        $filledCount = 0;
        foreach ($importantFields as $field) {
            $value = Arr::get($data, $field);
            if (!empty($value)) {
                $filledCount++;
            }
        }
        
        return $filledCount / count($importantFields);
    }

    /**
     * Trích xuất số kỹ năng mềm
     */
    private function extractSoftSkillsCount(array $data): int
    {
        $softSkills = Arr::get($data, 'soft_skills')
            ?? Arr::get($data, 'interpersonal_skills')
            ?? [];
        
        // Handle cv_quick structure: skills.soft is array of {name, level}
        $cvSoftSkills = Arr::get($data, 'skills.soft') ?? [];
        if (!empty($cvSoftSkills) && is_array($cvSoftSkills)) {
            $softSkills = array_map(function($skill) {
                return is_array($skill) ? ($skill['name'] ?? '') : $skill;
            }, $cvSoftSkills);
        }
        
        if (is_string($softSkills)) {
            $softSkills = array_map('trim', explode(',', $softSkills));
        }
        
        return min(6, count(array_filter((array) $softSkills)));
    }

    /**
     * Trích xuất điểm portfolio
     */
    private function extractPortfolioScore(array $data): float
    {
        $github = Arr::get($data, 'github')
            ?? Arr::get($data, 'github_url')
            ?? '';
        
        $portfolio = Arr::get($data, 'portfolio')
            ?? Arr::get($data, 'portfolio_url')
            ?? Arr::get($data, 'website')
            ?? '';
        
        // Nếu đã có score
        $score = Arr::get($data, 'portfolio_score');
        if (is_numeric($score)) {
            return min(5, max(0, (float) $score));
        }
        
        // Đánh giá dựa trên có/không có links
        $hasGithub = !empty($github) && filter_var($github, FILTER_VALIDATE_URL);
        $hasPortfolio = !empty($portfolio) && filter_var($portfolio, FILTER_VALIDATE_URL);
        
        if ($hasGithub && $hasPortfolio) {
            return 5.0;
        }
        if ($hasGithub || $hasPortfolio) {
            return 3.0;
        }
        
        return 0.0;
    }

    /**
     * Trích xuất requirements từ Job
     */
    private function extractJobRequirements(Job $job): array
    {
        $requirements = $job->requirements ?? [];
        
        if (is_string($requirements)) {
            $requirements = json_decode($requirements, true) ?? [];
        }
        
        return [
            'technologies' => $this->extractFromJobField($job, 'technologies', 'tech_requirements'),
            'main_skills' => $this->extractFromJobField($job, 'required_skills', 'main_skills'),
            'experience_years' => Arr::get($requirements, 'experience_years', 0),
        ];
    }

    /**
     * Helper để extract từ job
     */
    private function extractFromJobField(Job $job, string ...$fields): array
    {
        $requirements = $job->requirements ?? [];
        if (is_string($requirements)) {
            $requirements = json_decode($requirements, true) ?? [];
        }
        
        foreach ($fields as $field) {
            $value = Arr::get($requirements, $field);
            if (!empty($value)) {
                if (is_string($value)) {
                    return array_map('trim', explode(',', $value));
                }
                return (array) $value;
            }
        }
        
        return [];
    }
}
