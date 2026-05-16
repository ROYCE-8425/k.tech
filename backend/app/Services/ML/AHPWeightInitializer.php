<?php

namespace App\Services\ML;

/**
 * AHP Weight Initializer
 * 
 * Áp dụng thuật toán Analytic Hierarchy Process (AHP) của Thomas L. Saaty (1980)
 * để xác định trọng số ban đầu cho các tiêu chí chấm CV một cách có cơ sở khoa học.
 * 
 * Tài liệu tham khảo:
 * - Saaty, T. L. (1980). The Analytic Hierarchy Process. McGraw-Hill, New York.
 * - Saaty, T. L. (2008). Decision making with the analytic hierarchy process. 
 *   Int. J. Services Sciences, Vol. 1, No. 1, pp.83-98.
 * 
 * Thuật toán AHP:
 * 1. Xây dựng ma trận so sánh cặp (Pairwise Comparison Matrix)
 * 2. Tính vector riêng (Eigenvector) - chính là trọng số
 * 3. Kiểm tra tính nhất quán (Consistency Ratio - CR)
 * 4. CR < 0.1 thì ma trận so sánh được chấp nhận
 * 
 * Thang đo Saaty (1-9):
 * 1 = Quan trọng bằng nhau
 * 3 = Quan trọng hơn một chút
 * 5 = Quan trọng hơn rõ ràng
 * 7 = Quan trọng hơn nhiều
 * 9 = Quan trọng hơn tuyệt đối
 * 2,4,6,8 = Giá trị trung gian
 * 
 * @author IT Solo Leveling Team
 * @reference Saaty, T. L. (1980). The Analytic Hierarchy Process
 */
class AHPWeightInitializer
{
    /**
     * Random Index (RI) cho tính Consistency Ratio
     * Giá trị từ Saaty (1980) cho ma trận n x n
     */
    private const RANDOM_INDEX = [
        1 => 0.00,
        2 => 0.00,
        3 => 0.58,
        4 => 0.90,
        5 => 1.12,
        6 => 1.24,
        7 => 1.32,
        8 => 1.41,
        9 => 1.45,
        10 => 1.49,
    ];

    /**
     * Ma trận so sánh cặp cho 3 nhóm chính (A, B, C)
     * 
     * Dựa trên khảo sát/nghiên cứu về tiêu chí tuyển dụng IT:
     * - Nhóm A (Kinh nghiệm) vs Nhóm B (Kỹ năng): Quan trọng bằng nhau (1)
     * - Nhóm A (Kinh nghiệm) vs Nhóm C (Yếu tố phụ): Quan trọng hơn một chút (2)
     * - Nhóm B (Kỹ năng) vs Nhóm C (Yếu tố phụ): Quan trọng hơn một chút (2)
     * 
     * Nguồn tham khảo cho ma trận so sánh:
     * - LinkedIn Talent Solutions (2023). "Global Talent Trends"
     * - Stack Overflow Developer Survey (2023)
     * - IEEE Software Engineering Body of Knowledge (SWEBOK)
     */
    private const GROUP_COMPARISON_MATRIX = [
        //       A     B     C
        'A' => [1,    1,    2],    // A so với A=1, A so với B=1, A so với C=2
        'B' => [1,    1,    2],    // B so với A=1, B so với B=1, B so với C=2
        'C' => [0.5,  0.5,  1],    // C so với A=1/2, C so với B=1/2, C so với C=1
    ];

    /**
     * Ma trận so sánh cặp cho các tiêu chí trong Nhóm A (Kinh nghiệm & Dự án)
     * 
     * Tiêu chí:
     * 1. experience_years - Số năm kinh nghiệm
     * 2. projects_count - Số dự án đã làm
     * 3. tech_match_count - Số công nghệ phù hợp với job
     * 
     * Cơ sở so sánh (dựa trên nghiên cứu tuyển dụng IT):
     * - Kinh nghiệm vs Dự án: Kinh nghiệm quan trọng hơn một chút (2)
     * - Kinh nghiệm vs Tech match: Kinh nghiệm quan trọng hơn (3)
     * - Dự án vs Tech match: Dự án quan trọng hơn một chút (2)
     */
    private const GROUP_A_COMPARISON = [
        //                  exp    proj   tech
        'experience_years' => [1,     2,     3],
        'projects_count'   => [0.5,   1,     2],
        'tech_match_count' => [0.333, 0.5,   1],
    ];

    /**
     * Ma trận so sánh cặp cho các tiêu chí trong Nhóm B (Kỹ năng)
     * 
     * Tiêu chí:
     * 1. main_skills_count - Số kỹ năng chính
     * 2. sub_skills_count - Số kỹ năng phụ
     * 3. certifications_count - Số chứng chỉ
     * 
     * Cơ sở so sánh:
     * - Main skills vs Sub skills: Main quan trọng hơn (3)
     * - Main skills vs Certs: Main quan trọng hơn một chút (2)
     * - Sub skills vs Certs: Bằng nhau (1)
     */
    private const GROUP_B_COMPARISON = [
        //                      main   sub    cert
        'main_skills_count'     => [1,     3,     2],
        'sub_skills_count'      => [0.333, 1,     1],
        'certifications_count'  => [0.5,   1,     1],
    ];

    /**
     * Ma trận so sánh cặp cho các tiêu chí trong Nhóm C (Yếu tố phụ)
     * 
     * Tiêu chí:
     * 1. education_score - Điểm học vấn
     * 2. cv_quality_score - Chất lượng CV
     * 3. soft_skills_count - Kỹ năng mềm
     * 4. portfolio_score - Portfolio
     * 
     * Cơ sở so sánh:
     * - Education vs CV quality: Bằng nhau (1)
     * - Education vs Soft skills: Quan trọng hơn một chút (2)
     * - Education vs Portfolio: Quan trọng hơn (3)
     */
    private const GROUP_C_COMPARISON = [
        //                   edu    cv     soft   port
        'education_score'   => [1,     1,     2,     3],
        'cv_quality_score'  => [1,     1,     2,     2],
        'soft_skills_count' => [0.5,   0.5,   1,     2],
        'portfolio_score'   => [0.333, 0.5,   0.5,   1],
    ];

    /**
     * Kết quả tính toán
     */
    private array $results = [];

    /**
     * Tính tất cả trọng số bằng AHP
     * 
     * @return array Kết quả bao gồm trọng số và CR
     */
    public function calculateAllWeights(): array
    {
        $this->results = [
            'method' => 'Analytic Hierarchy Process (AHP)',
            'reference' => 'Saaty, T. L. (1980). The Analytic Hierarchy Process. McGraw-Hill.',
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Tính trọng số cho các nhóm chính
        $this->results['groups'] = $this->calculateGroupWeights();

        // Tính trọng số cho từng tiêu chí trong mỗi nhóm
        $this->results['group_A'] = $this->calculateCriteriaWeights(
            self::GROUP_A_COMPARISON,
            'Nhóm A - Kinh nghiệm & Dự án'
        );

        $this->results['group_B'] = $this->calculateCriteriaWeights(
            self::GROUP_B_COMPARISON,
            'Nhóm B - Kỹ năng'
        );

        $this->results['group_C'] = $this->calculateCriteriaWeights(
            self::GROUP_C_COMPARISON,
            'Nhóm C - Yếu tố phụ'
        );

        // Tính global weights (trọng số toàn cục)
        $this->results['global_weights'] = $this->calculateGlobalWeights();

        // Tính max points dựa trên global weights (tổng 100 điểm)
        $this->results['max_points'] = $this->calculateMaxPoints();

        return $this->results;
    }

    /**
     * Tính trọng số cho các nhóm chính (A, B, C)
     */
    private function calculateGroupWeights(): array
    {
        $matrix = array_values(array_map('array_values', self::GROUP_COMPARISON_MATRIX));
        $labels = array_keys(self::GROUP_COMPARISON_MATRIX);
        
        return $this->computeAHP($matrix, $labels, 'Nhóm chính');
    }

    /**
     * Tính trọng số cho các tiêu chí trong một nhóm
     */
    private function calculateCriteriaWeights(array $comparisonMatrix, string $name): array
    {
        $matrix = array_values(array_map('array_values', $comparisonMatrix));
        $labels = array_keys($comparisonMatrix);
        
        return $this->computeAHP($matrix, $labels, $name);
    }

    /**
     * Thuật toán AHP chính
     * 
     * @param array $matrix Ma trận so sánh cặp
     * @param array $labels Tên các tiêu chí
     * @param string $name Tên nhóm
     * @return array Kết quả tính toán
     */
    private function computeAHP(array $matrix, array $labels, string $name): array
    {
        $n = count($matrix);
        
        // Bước 1: Chuẩn hóa ma trận (chia mỗi phần tử cho tổng cột)
        $columnSums = array_fill(0, $n, 0);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $columnSums[$j] += $matrix[$i][$j];
            }
        }

        $normalizedMatrix = [];
        for ($i = 0; $i < $n; $i++) {
            $normalizedMatrix[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                $normalizedMatrix[$i][$j] = $matrix[$i][$j] / $columnSums[$j];
            }
        }

        // Bước 2: Tính trọng số (trung bình mỗi hàng của ma trận chuẩn hóa)
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $weights[$i] = array_sum($normalizedMatrix[$i]) / $n;
        }

        // Bước 3: Tính λmax (eigenvalue lớn nhất)
        $weightedSum = [];
        for ($i = 0; $i < $n; $i++) {
            $sum = 0;
            for ($j = 0; $j < $n; $j++) {
                $sum += $matrix[$i][$j] * $weights[$j];
            }
            $weightedSum[$i] = $sum;
        }

        $lambdaMax = 0;
        for ($i = 0; $i < $n; $i++) {
            $lambdaMax += $weightedSum[$i] / $weights[$i];
        }
        $lambdaMax /= $n;

        // Bước 4: Tính Consistency Index (CI)
        $CI = ($lambdaMax - $n) / ($n - 1);

        // Bước 5: Tính Consistency Ratio (CR)
        $RI = self::RANDOM_INDEX[$n] ?? 1.49;
        $CR = $RI > 0 ? $CI / $RI : 0;

        // Tạo kết quả
        $result = [
            'name' => $name,
            'n' => $n,
            'weights' => [],
            'lambda_max' => round($lambdaMax, 4),
            'CI' => round($CI, 4),
            'RI' => $RI,
            'CR' => round($CR, 4),
            'is_consistent' => $CR < 0.1,
            'consistency_status' => $CR < 0.1 ? 'PASSED (CR < 0.1)' : 'FAILED (CR >= 0.1)',
        ];

        for ($i = 0; $i < $n; $i++) {
            $result['weights'][$labels[$i]] = round($weights[$i], 4);
        }

        return $result;
    }

    /**
     * Tính global weights (trọng số toàn cục cho mỗi tiêu chí)
     */
    private function calculateGlobalWeights(): array
    {
        $groupWeights = $this->results['groups']['weights'];
        
        $globalWeights = [];

        // Nhóm A
        foreach ($this->results['group_A']['weights'] as $criteria => $localWeight) {
            $globalWeights[$criteria] = round($groupWeights['A'] * $localWeight, 4);
        }

        // Nhóm B
        foreach ($this->results['group_B']['weights'] as $criteria => $localWeight) {
            $globalWeights[$criteria] = round($groupWeights['B'] * $localWeight, 4);
        }

        // Nhóm C
        foreach ($this->results['group_C']['weights'] as $criteria => $localWeight) {
            $globalWeights[$criteria] = round($groupWeights['C'] * $localWeight, 4);
        }

        return $globalWeights;
    }

    /**
     * Tính max points cho mỗi tiêu chí (tổng 100 điểm)
     */
    private function calculateMaxPoints(): array
    {
        $globalWeights = $this->results['global_weights'];
        $totalPoints = 100;
        
        $maxPoints = [];
        foreach ($globalWeights as $criteria => $weight) {
            $maxPoints[$criteria] = round($weight * $totalPoints, 1);
        }

        return $maxPoints;
    }

    /**
     * Xuất kết quả dưới dạng cấu hình cho MLGroupScorer
     */
    public function exportAsGroupScorerConfig(): array
    {
        if (empty($this->results)) {
            $this->calculateAllWeights();
        }

        $groupWeights = $this->results['groups']['weights'];
        
        return [
            'A' => [
                'name' => 'Kinh nghiệm & Dự án',
                'max_score' => round($groupWeights['A'] * 100, 1),
                'features' => [
                    'experience_years' => [
                        'weight' => $this->results['group_A']['weights']['experience_years'],
                        'max' => 15,
                        'points' => $this->results['max_points']['experience_years'],
                    ],
                    'projects_count' => [
                        'weight' => $this->results['group_A']['weights']['projects_count'],
                        'max' => 10,
                        'points' => $this->results['max_points']['projects_count'],
                    ],
                    'tech_match_count' => [
                        'weight' => $this->results['group_A']['weights']['tech_match_count'],
                        'max' => 10,
                        'points' => $this->results['max_points']['tech_match_count'],
                    ],
                ],
            ],
            'B' => [
                'name' => 'Kỹ năng',
                'max_score' => round($groupWeights['B'] * 100, 1),
                'features' => [
                    'main_skills_count' => [
                        'weight' => $this->results['group_B']['weights']['main_skills_count'],
                        'max' => 6,
                        'points' => $this->results['max_points']['main_skills_count'],
                    ],
                    'sub_skills_count' => [
                        'weight' => $this->results['group_B']['weights']['sub_skills_count'],
                        'max' => 5,
                        'points' => $this->results['max_points']['sub_skills_count'],
                    ],
                    'certifications_count' => [
                        'weight' => $this->results['group_B']['weights']['certifications_count'],
                        'max' => 5,
                        'points' => $this->results['max_points']['certifications_count'],
                    ],
                ],
            ],
            'C' => [
                'name' => 'Yếu tố phụ',
                'max_score' => round($groupWeights['C'] * 100, 1),
                'features' => [
                    'education_score' => [
                        'weight' => $this->results['group_C']['weights']['education_score'],
                        'max' => 10,
                        'points' => $this->results['max_points']['education_score'],
                    ],
                    'cv_quality_score' => [
                        'weight' => $this->results['group_C']['weights']['cv_quality_score'],
                        'max' => 10,
                        'points' => $this->results['max_points']['cv_quality_score'],
                    ],
                    'soft_skills_count' => [
                        'weight' => $this->results['group_C']['weights']['soft_skills_count'],
                        'max' => 6,
                        'points' => $this->results['max_points']['soft_skills_count'],
                    ],
                    'portfolio_score' => [
                        'weight' => $this->results['group_C']['weights']['portfolio_score'],
                        'max' => 5,
                        'points' => $this->results['max_points']['portfolio_score'],
                    ],
                ],
            ],
        ];
    }

    /**
     * In báo cáo AHP đầy đủ
     */
    public function printReport(): string
    {
        if (empty($this->results)) {
            $this->calculateAllWeights();
        }

        $report = "=== BÁO CÁO TÍNH TRỌNG SỐ BẰNG AHP ===\n\n";
        $report .= "Phương pháp: {$this->results['method']}\n";
        $report .= "Tham khảo: {$this->results['reference']}\n";
        $report .= "Thời gian: {$this->results['timestamp']}\n\n";

        // Nhóm chính
        $report .= "--- TRỌNG SỐ NHÓM CHÍNH ---\n";
        $groups = $this->results['groups'];
        foreach ($groups['weights'] as $group => $weight) {
            $report .= sprintf("  %s: %.4f (%.1f%%)\n", $group, $weight, $weight * 100);
        }
        $report .= "  CR = {$groups['CR']} - {$groups['consistency_status']}\n\n";

        // Chi tiết từng nhóm
        foreach (['A', 'B', 'C'] as $g) {
            $key = "group_{$g}";
            $data = $this->results[$key];
            $report .= "--- {$data['name']} ---\n";
            foreach ($data['weights'] as $criteria => $weight) {
                $globalWeight = $this->results['global_weights'][$criteria];
                $points = $this->results['max_points'][$criteria];
                $report .= sprintf("  %s: %.4f (global: %.4f, %.1f điểm)\n", 
                    $criteria, $weight, $globalWeight, $points);
            }
            $report .= "  CR = {$data['CR']} - {$data['consistency_status']}\n\n";
        }

        // Tổng kết
        $report .= "--- TRỌNG SỐ TOÀN CỤC ---\n";
        $total = 0;
        foreach ($this->results['global_weights'] as $criteria => $weight) {
            $points = $this->results['max_points'][$criteria];
            $report .= sprintf("  %s: %.4f (%.1f điểm)\n", $criteria, $weight, $points);
            $total += $points;
        }
        $report .= sprintf("\n  TỔNG: %.1f điểm\n", $total);

        return $report;
    }
}
