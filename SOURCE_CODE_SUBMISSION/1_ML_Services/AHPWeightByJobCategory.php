<?php

namespace App\Services\ML;

/**
 * AHP Weight Initializer By Job Category
 * 
 * Tính trọng số AHP riêng cho từng loại vị trí IT:
 * - Frontend Developer
 * - Backend Developer  
 * - Fullstack Developer
 * - DevOps Engineer
 * - Data Engineer / Scientist
 * - Mobile Developer
 * 
 * Mỗi loại vị trí có ma trận so sánh cặp khác nhau vì yêu cầu kỹ năng khác nhau.
 * 
 * Tham khảo: Saaty, T. L. (1980). The Analytic Hierarchy Process. McGraw-Hill.
 * 
 * @author IT Solo Leveling Team
 */
class AHPWeightByJobCategory
{
    /**
     * Random Index từ Saaty (1980)
     */
    private const RANDOM_INDEX = [
        1 => 0.00, 2 => 0.00, 3 => 0.58, 4 => 0.90,
        5 => 1.12, 6 => 1.24, 7 => 1.32, 8 => 1.41,
    ];

    /**
     * Định nghĩa các loại vị trí
     */
    public const JOB_CATEGORIES = [
        'frontend' => 'Frontend Developer',
        'backend' => 'Backend Developer',
        'fullstack' => 'Fullstack Developer',
        'devops' => 'DevOps Engineer',
        'data' => 'Data Engineer/Scientist',
        'mobile' => 'Mobile Developer',
        'default' => 'General IT Position',
    ];

    /**
     * Ma trận so sánh nhóm chính (A, B, C) theo từng loại vị trí
     * 
     * A = Kinh nghiệm & Dự án
     * B = Kỹ năng kỹ thuật
     * C = Yếu tố phụ (học vấn, soft skills, portfolio)
     */
    private const GROUP_MATRICES = [
        // Frontend: Kỹ năng (B) > Portfolio/CV (C) > Kinh nghiệm (A)
        // Vì frontend cần show được work, portfolio rất quan trọng
        'frontend' => [
            'A' => [1,    0.5,  1],      // A vs B = 1/2 (B quan trọng hơn)
            'B' => [2,    1,    2],      // B vs A = 2, B vs C = 2
            'C' => [1,    0.5,  1],      // C ngang A
        ],
        
        // Backend: Kinh nghiệm (A) = Kỹ năng (B) > Yếu tố phụ (C)
        // Backend cần cả experience và technical depth
        'backend' => [
            'A' => [1,    1,    2],
            'B' => [1,    1,    2],
            'C' => [0.5,  0.5,  1],
        ],
        
        // Fullstack: Cân bằng cả 3
        'fullstack' => [
            'A' => [1,    1,    1.5],
            'B' => [1,    1,    1.5],
            'C' => [0.67, 0.67, 1],
        ],
        
        // DevOps: Kinh nghiệm > Kỹ năng > Yếu tố phụ
        // DevOps cần nhiều kinh nghiệm thực tế
        'devops' => [
            'A' => [1,    2,    3],      // A quan trọng nhất
            'B' => [0.5,  1,    2],
            'C' => [0.33, 0.5,  1],
        ],
        
        // Data: Học vấn quan trọng, cần background toán/thống kê
        'data' => [
            'A' => [1,    1,    0.5],    // A ít quan trọng hơn C
            'B' => [1,    1,    0.5],
            'C' => [2,    2,    1],      // C (education) quan trọng nhất
        ],
        
        // Mobile: Tương tự Frontend nhưng kinh nghiệm quan trọng hơn
        'mobile' => [
            'A' => [1,    0.5,  2],
            'B' => [2,    1,    3],      // B quan trọng nhất
            'C' => [0.5,  0.33, 1],
        ],
        
        // Default: Giống backend (cân bằng)
        'default' => [
            'A' => [1,    1,    2],
            'B' => [1,    1,    2],
            'C' => [0.5,  0.5,  1],
        ],
    ];

    /**
     * Ma trận so sánh Nhóm A theo từng loại vị trí
     * Features: experience_years, projects_count, tech_match_count
     */
    private const GROUP_A_MATRICES = [
        // Frontend: Portfolio projects > tech match > years
        'frontend' => [
            'experience_years' => [1,    0.5,   0.33],
            'projects_count'   => [2,    1,     0.5],
            'tech_match_count' => [3,    2,     1],
        ],
        
        // Backend: Experience > tech match > projects  
        'backend' => [
            'experience_years' => [1,    2,     3],
            'projects_count'   => [0.5,  1,     2],
            'tech_match_count' => [0.33, 0.5,   1],
        ],
        
        // DevOps: Experience rất quan trọng
        'devops' => [
            'experience_years' => [1,    3,     2],
            'projects_count'   => [0.33, 1,     0.5],
            'tech_match_count' => [0.5,  2,     1],
        ],
        
        // Data: Tech match (đúng tech stack) > experience > projects
        'data' => [
            'experience_years' => [1,    2,     0.5],
            'projects_count'   => [0.5,  1,     0.33],
            'tech_match_count' => [2,    3,     1],
        ],
        
        // Default
        'default' => [
            'experience_years' => [1,    2,     3],
            'projects_count'   => [0.5,  1,     2],
            'tech_match_count' => [0.33, 0.5,   1],
        ],
    ];

    /**
     * Ma trận so sánh Nhóm B theo từng loại vị trí
     * Features: main_skills_count, sub_skills_count, certifications_count
     */
    private const GROUP_B_MATRICES = [
        // Frontend: Main skills (React/Vue/Angular) > Sub > Certs
        'frontend' => [
            'main_skills_count'    => [1,    3,     4],
            'sub_skills_count'     => [0.33, 1,     2],
            'certifications_count' => [0.25, 0.5,   1],
        ],
        
        // Backend: Certs (AWS, etc) quan trọng hơn frontend
        'backend' => [
            'main_skills_count'    => [1,    2,     2],
            'sub_skills_count'     => [0.5,  1,     1],
            'certifications_count' => [0.5,  1,     1],
        ],
        
        // DevOps: Certs rất quan trọng (AWS, K8s, etc)
        'devops' => [
            'main_skills_count'    => [1,    2,     0.5],
            'sub_skills_count'     => [0.5,  1,     0.33],
            'certifications_count' => [2,    3,     1],      // Certs quan trọng nhất
        ],
        
        // Data: Skills Python/SQL > Certs > Sub skills
        'data' => [
            'main_skills_count'    => [1,    3,     2],
            'sub_skills_count'     => [0.33, 1,     0.5],
            'certifications_count' => [0.5,  2,     1],
        ],
        
        // Default
        'default' => [
            'main_skills_count'    => [1,    3,     2],
            'sub_skills_count'     => [0.33, 1,     1],
            'certifications_count' => [0.5,  1,     1],
        ],
    ];

    /**
     * Ma trận so sánh Nhóm C theo từng loại vị trí
     * Features: education_score, cv_quality_score, soft_skills_count, portfolio_score
     */
    private const GROUP_C_MATRICES = [
        // Frontend: Portfolio > CV quality > Soft skills > Education
        'frontend' => [
            'education_score'   => [1,    0.5,   0.5,   0.25],
            'cv_quality_score'  => [2,    1,     1,     0.5],
            'soft_skills_count' => [2,    1,     1,     0.5],
            'portfolio_score'   => [4,    2,     2,     1],      // Portfolio quan trọng nhất!
        ],
        
        // Backend: Education = CV > Soft skills > Portfolio
        'backend' => [
            'education_score'   => [1,    1,     2,     3],
            'cv_quality_score'  => [1,    1,     2,     2],
            'soft_skills_count' => [0.5,  0.5,   1,     2],
            'portfolio_score'   => [0.33, 0.5,   0.5,   1],
        ],
        
        // DevOps: CV quality > Certs mentioned > Education
        'devops' => [
            'education_score'   => [1,    0.5,   1,     2],
            'cv_quality_score'  => [2,    1,     2,     3],
            'soft_skills_count' => [1,    0.5,   1,     2],
            'portfolio_score'   => [0.5,  0.33,  0.5,   1],
        ],
        
        // Data: Education quan trọng nhất (cần nền tảng toán/thống kê)
        'data' => [
            'education_score'   => [1,    2,     3,     4],      // Education quan trọng nhất
            'cv_quality_score'  => [0.5,  1,     2,     2],
            'soft_skills_count' => [0.33, 0.5,   1,     1],
            'portfolio_score'   => [0.25, 0.5,   1,     1],
        ],
        
        // Default
        'default' => [
            'education_score'   => [1,    1,     2,     3],
            'cv_quality_score'  => [1,    1,     2,     2],
            'soft_skills_count' => [0.5,  0.5,   1,     2],
            'portfolio_score'   => [0.33, 0.5,   0.5,   1],
        ],
    ];

    /**
     * Cache kết quả
     */
    private array $cache = [];

    /**
     * Tính trọng số cho một loại vị trí cụ thể
     */
    public function calculateWeightsForCategory(string $category): array
    {
        $category = strtolower($category);
        
        // Kiểm tra category có hợp lệ không
        if (!isset(self::JOB_CATEGORIES[$category])) {
            $category = 'default';
        }
        
        // Check cache
        if (isset($this->cache[$category])) {
            return $this->cache[$category];
        }
        
        $result = [
            'category' => $category,
            'category_name' => self::JOB_CATEGORIES[$category],
            'method' => 'AHP (Saaty 1980)',
        ];
        
        // Tính trọng số nhóm chính
        $groupMatrix = self::GROUP_MATRICES[$category] ?? self::GROUP_MATRICES['default'];
        $result['groups'] = $this->computeAHP(
            array_values(array_map('array_values', $groupMatrix)),
            ['A', 'B', 'C']
        );
        
        // Tính trọng số Nhóm A
        $groupAMatrix = self::GROUP_A_MATRICES[$category] ?? self::GROUP_A_MATRICES['default'];
        $result['group_A'] = $this->computeAHP(
            array_values(array_map('array_values', $groupAMatrix)),
            ['experience_years', 'projects_count', 'tech_match_count']
        );
        
        // Tính trọng số Nhóm B
        $groupBMatrix = self::GROUP_B_MATRICES[$category] ?? self::GROUP_B_MATRICES['default'];
        $result['group_B'] = $this->computeAHP(
            array_values(array_map('array_values', $groupBMatrix)),
            ['main_skills_count', 'sub_skills_count', 'certifications_count']
        );
        
        // Tính trọng số Nhóm C
        $groupCMatrix = self::GROUP_C_MATRICES[$category] ?? self::GROUP_C_MATRICES['default'];
        $result['group_C'] = $this->computeAHP(
            array_values(array_map('array_values', $groupCMatrix)),
            ['education_score', 'cv_quality_score', 'soft_skills_count', 'portfolio_score']
        );
        
        // Tính global weights
        $result['global_weights'] = $this->calculateGlobalWeights($result);
        
        // Tính max points (tổng 100)
        $result['max_points'] = [];
        foreach ($result['global_weights'] as $feature => $weight) {
            $result['max_points'][$feature] = round($weight * 100, 1);
        }
        
        // Tính max score cho mỗi nhóm
        $result['group_max_scores'] = [
            'A' => round($result['groups']['weights']['A'] * 100, 1),
            'B' => round($result['groups']['weights']['B'] * 100, 1),
            'C' => round($result['groups']['weights']['C'] * 100, 1),
        ];
        
        // Cache
        $this->cache[$category] = $result;
        
        return $result;
    }

    /**
     * Thuật toán AHP
     */
    private function computeAHP(array $matrix, array $labels): array
    {
        $n = count($matrix);
        
        // Chuẩn hóa ma trận
        $columnSums = array_fill(0, $n, 0);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $columnSums[$j] += $matrix[$i][$j];
            }
        }

        $normalized = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $normalized[$i][$j] = $matrix[$i][$j] / $columnSums[$j];
            }
        }

        // Trọng số = trung bình hàng
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $weights[$i] = array_sum($normalized[$i]) / $n;
        }

        // λmax và CR
        $lambdaMax = 0;
        for ($i = 0; $i < $n; $i++) {
            $sum = 0;
            for ($j = 0; $j < $n; $j++) {
                $sum += $matrix[$i][$j] * $weights[$j];
            }
            $lambdaMax += $sum / $weights[$i];
        }
        $lambdaMax /= $n;

        $CI = ($n > 1) ? ($lambdaMax - $n) / ($n - 1) : 0;
        $RI = self::RANDOM_INDEX[$n] ?? 1.49;
        $CR = ($RI > 0) ? $CI / $RI : 0;

        $result = [
            'weights' => [],
            'CR' => round($CR, 4),
            'is_consistent' => $CR < 0.1,
        ];

        for ($i = 0; $i < $n; $i++) {
            $result['weights'][$labels[$i]] = round($weights[$i], 4);
        }

        return $result;
    }

    /**
     * Tính global weights
     */
    private function calculateGlobalWeights(array $result): array
    {
        $groupWeights = $result['groups']['weights'];
        $global = [];

        foreach ($result['group_A']['weights'] as $feature => $weight) {
            $global[$feature] = round($groupWeights['A'] * $weight, 4);
        }
        foreach ($result['group_B']['weights'] as $feature => $weight) {
            $global[$feature] = round($groupWeights['B'] * $weight, 4);
        }
        foreach ($result['group_C']['weights'] as $feature => $weight) {
            $global[$feature] = round($groupWeights['C'] * $weight, 4);
        }

        return $global;
    }

    /**
     * So sánh trọng số giữa các loại vị trí
     */
    public function compareCategories(): array
    {
        $comparison = [];
        
        foreach (array_keys(self::JOB_CATEGORIES) as $category) {
            $weights = $this->calculateWeightsForCategory($category);
            $comparison[$category] = [
                'name' => self::JOB_CATEGORIES[$category],
                'group_weights' => $weights['groups']['weights'],
                'group_max_scores' => $weights['group_max_scores'],
                'top_features' => $this->getTopFeatures($weights['global_weights']),
            ];
        }
        
        return $comparison;
    }

    /**
     * Lấy top features quan trọng nhất
     */
    private function getTopFeatures(array $weights, int $top = 3): array
    {
        arsort($weights);
        return array_slice($weights, 0, $top, true);
    }

    /**
     * In báo cáo so sánh
     */
    public function printComparisonReport(): string
    {
        $report = "=== SO SÁNH TRỌNG SỐ AHP THEO LOẠI VỊ TRÍ ===\n\n";
        
        $comparison = $this->compareCategories();
        
        // Header
        $report .= str_pad("Vị trí", 25) . " | ";
        $report .= str_pad("A", 6) . " | ";
        $report .= str_pad("B", 6) . " | ";
        $report .= str_pad("C", 6) . " | Top Features\n";
        $report .= str_repeat("-", 80) . "\n";
        
        foreach ($comparison as $category => $data) {
            $report .= str_pad($data['name'], 25) . " | ";
            $report .= str_pad($data['group_max_scores']['A'], 6) . " | ";
            $report .= str_pad($data['group_max_scores']['B'], 6) . " | ";
            $report .= str_pad($data['group_max_scores']['C'], 6) . " | ";
            
            $topNames = array_keys($data['top_features']);
            $report .= implode(', ', array_slice($topNames, 0, 2)) . "\n";
        }
        
        return $report;
    }

    /**
     * Xuất config cho MLGroupScorer dựa trên category
     */
    public function exportGroupScorerConfig(string $category): array
    {
        $weights = $this->calculateWeightsForCategory($category);
        
        return [
            'A' => [
                'name' => 'Kinh nghiệm & Dự án',
                'max_score' => $weights['group_max_scores']['A'],
                'ahp_weight' => $weights['groups']['weights']['A'],
                'ahp_cr' => $weights['group_A']['CR'],
                'features' => [
                    'experience_years' => [
                        'weight' => $weights['group_A']['weights']['experience_years'],
                        'max' => 15,
                        'points' => $weights['max_points']['experience_years'],
                    ],
                    'projects_count' => [
                        'weight' => $weights['group_A']['weights']['projects_count'],
                        'max' => 10,
                        'points' => $weights['max_points']['projects_count'],
                    ],
                    'tech_match_count' => [
                        'weight' => $weights['group_A']['weights']['tech_match_count'],
                        'max' => 10,
                        'points' => $weights['max_points']['tech_match_count'],
                    ],
                ],
            ],
            'B' => [
                'name' => 'Kỹ năng',
                'max_score' => $weights['group_max_scores']['B'],
                'ahp_weight' => $weights['groups']['weights']['B'],
                'ahp_cr' => $weights['group_B']['CR'],
                'features' => [
                    'main_skills_count' => [
                        'weight' => $weights['group_B']['weights']['main_skills_count'],
                        'max' => 6,
                        'points' => $weights['max_points']['main_skills_count'],
                    ],
                    'sub_skills_count' => [
                        'weight' => $weights['group_B']['weights']['sub_skills_count'],
                        'max' => 5,
                        'points' => $weights['max_points']['sub_skills_count'],
                    ],
                    'certifications_count' => [
                        'weight' => $weights['group_B']['weights']['certifications_count'],
                        'max' => 5,
                        'points' => $weights['max_points']['certifications_count'],
                    ],
                ],
            ],
            'C' => [
                'name' => 'Yếu tố phụ',
                'max_score' => $weights['group_max_scores']['C'],
                'ahp_weight' => $weights['groups']['weights']['C'],
                'ahp_cr' => $weights['group_C']['CR'],
                'features' => [
                    'education_score' => [
                        'weight' => $weights['group_C']['weights']['education_score'],
                        'max' => 10,
                        'points' => $weights['max_points']['education_score'],
                    ],
                    'cv_quality_score' => [
                        'weight' => $weights['group_C']['weights']['cv_quality_score'],
                        'max' => 10,
                        'points' => $weights['max_points']['cv_quality_score'],
                    ],
                    'soft_skills_count' => [
                        'weight' => $weights['group_C']['weights']['soft_skills_count'],
                        'max' => 6,
                        'points' => $weights['max_points']['soft_skills_count'],
                    ],
                    'portfolio_score' => [
                        'weight' => $weights['group_C']['weights']['portfolio_score'],
                        'max' => 5,
                        'points' => $weights['max_points']['portfolio_score'],
                    ],
                ],
            ],
        ];
    }
}
