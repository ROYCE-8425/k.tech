<?php

namespace App\Services\ML;

/**
 * ML Group Scorer
 * 
 * Tính điểm cho từng nhóm tiêu chí (A, B, C) dựa trên features đã trích xuất.
 * Điểm được tính dựa trên công thức có trọng số cho từng feature trong nhóm.
 * 
 * TRỌNG SỐ ĐƯỢC TÍNH BẰNG THUẬT TOÁN AHP (Analytic Hierarchy Process)
 * Tham khảo: Saaty, T. L. (1980). The Analytic Hierarchy Process. McGraw-Hill.
 * 
 * Nhóm A: Kinh nghiệm & Dự án (max 40 điểm) - AHP weight: 0.4000
 * Nhóm B: Kỹ năng (max 40 điểm) - AHP weight: 0.4000
 * Nhóm C: Yếu tố phụ (max 20 điểm) - AHP weight: 0.2000
 * 
 * Consistency Ratio (CR) < 0.1 cho tất cả ma trận so sánh → Hợp lệ
 * 
 * @author IT Solo Leveling Team
 * @see AHPWeightInitializer - Class tính trọng số AHP
 * @reference Saaty, T. L. (1980). The Analytic Hierarchy Process
 */
class MLGroupScorer
{
    /**
     * Cấu hình scoring cho từng nhóm
     * 
     * TRỌNG SỐ TỪ THUẬT TOÁN AHP (Analytic Hierarchy Process):
     * - Saaty, T. L. (1980). The Analytic Hierarchy Process. McGraw-Hill, New York.
     * - Ma trận so sánh cặp dựa trên nghiên cứu tuyển dụng IT
     * - Tất cả CR < 0.1 (passed consistency check)
     * 
     * Mỗi feature có:
     * - weight: Trọng số local trong nhóm (từ AHP)
     * - max: Giá trị tối đa của feature
     * - points: Số điểm tối đa đóng góp (global weight × 100)
     */
    public const GROUP_CONFIG = [
        // Nhóm A: AHP Group Weight = 0.4000, CR = 0.0076 (PASSED)
        'A' => [
            'name' => 'Kinh nghiệm & Dự án',
            'max_score' => 40,
            'ahp_weight' => 0.4000,
            'ahp_cr' => 0.0076,
            'features' => [
                // AHP local weight: 0.5390, global: 0.2156
                'experience_years' => ['weight' => 0.5390, 'max' => 15, 'points' => 21.6],
                // AHP local weight: 0.2973, global: 0.1189
                'projects_count' => ['weight' => 0.2973, 'max' => 10, 'points' => 11.9],
                // AHP local weight: 0.1637, global: 0.0655
                'tech_match_count' => ['weight' => 0.1637, 'max' => 10, 'points' => 6.5],
            ],
        ],
        // Nhóm B: AHP Group Weight = 0.4000, CR = 0.0155 (PASSED)
        'B' => [
            'name' => 'Kỹ năng',
            'max_score' => 40,
            'ahp_weight' => 0.4000,
            'ahp_cr' => 0.0155,
            'features' => [
                // AHP local weight: 0.5485, global: 0.2194
                'main_skills_count' => ['weight' => 0.5485, 'max' => 6, 'points' => 21.9],
                // AHP local weight: 0.2106, global: 0.0842
                'sub_skills_count' => ['weight' => 0.2106, 'max' => 5, 'points' => 8.4],
                // AHP local weight: 0.2409, global: 0.0964
                'certifications_count' => ['weight' => 0.2409, 'max' => 5, 'points' => 9.7],
            ],
        ],
        // Nhóm C: AHP Group Weight = 0.2000, CR = 0.0169 (PASSED)
        'C' => [
            'name' => 'Yếu tố phụ',
            'max_score' => 20,
            'ahp_weight' => 0.2000,
            'ahp_cr' => 0.0169,
            'features' => [
                // AHP local weight: 0.3562, global: 0.0712
                'education_score' => ['weight' => 0.3562, 'max' => 10, 'points' => 7.1],
                // AHP local weight: 0.3250, global: 0.0650
                'cv_quality_score' => ['weight' => 0.3250, 'max' => 10, 'points' => 6.5],
                // AHP local weight: 0.1937, global: 0.0387
                'soft_skills_count' => ['weight' => 0.1937, 'max' => 6, 'points' => 3.9],
                // AHP local weight: 0.1250, global: 0.0250
                'portfolio_score' => ['weight' => 0.1250, 'max' => 5, 'points' => 2.5],
            ],
        ],
    ];

    /**
     * Tính điểm cho tất cả các nhóm
     * 
     * @param array $features Features đã trích xuất từ MLFeatureExtractor
     * @return array ['A' => score, 'B' => score, 'C' => score, 'total' => score]
     */
    public function scoreAll(array $features): array
    {
        $scores = [
            'A' => $this->scoreGroup('A', $features),
            'B' => $this->scoreGroup('B', $features),
            'C' => $this->scoreGroup('C', $features),
        ];
        
        $scores['total'] = array_sum($scores);
        
        return $scores;
    }

    /**
     * Tính điểm cho một nhóm cụ thể
     * 
     * @param string $group Tên nhóm ('A', 'B', 'C')
     * @param array $features Features đã trích xuất
     * @return float Điểm của nhóm
     */
    public function scoreGroup(string $group, array $features): float
    {
        if (!isset(self::GROUP_CONFIG[$group])) {
            return 0.0;
        }
        
        $config = self::GROUP_CONFIG[$group];
        $score = 0.0;
        
        foreach ($config['features'] as $featureName => $featureConfig) {
            $value = (float) ($features[$featureName] ?? 0);
            $maxValue = $featureConfig['max'];
            $points = $featureConfig['points'];
            
            // Tính điểm theo tỷ lệ
            $ratio = min(1.0, $value / $maxValue);
            $featureScore = $ratio * $points;
            
            $score += $featureScore;
        }
        
        // Đảm bảo không vượt quá max
        return min($config['max_score'], round($score, 2));
    }

    /**
     * Lấy chi tiết điểm từng feature trong nhóm
     * 
     * @param string $group Tên nhóm
     * @param array $features Features
     * @return array Chi tiết điểm
     */
    public function getGroupBreakdown(string $group, array $features): array
    {
        if (!isset(self::GROUP_CONFIG[$group])) {
            return [];
        }
        
        $config = self::GROUP_CONFIG[$group];
        $breakdown = [
            'group' => $group,
            'name' => $config['name'],
            'max_score' => $config['max_score'],
            'features' => [],
            'total' => 0,
        ];
        
        foreach ($config['features'] as $featureName => $featureConfig) {
            $value = (float) ($features[$featureName] ?? 0);
            $maxValue = $featureConfig['max'];
            $points = $featureConfig['points'];
            
            $ratio = min(1.0, $value / $maxValue);
            $featureScore = $ratio * $points;
            
            $breakdown['features'][$featureName] = [
                'value' => $value,
                'max_value' => $maxValue,
                'ratio' => round($ratio, 3),
                'max_points' => $points,
                'score' => round($featureScore, 2),
                'weight' => $featureConfig['weight'],
            ];
            
            $breakdown['total'] += $featureScore;
        }
        
        $breakdown['total'] = min($config['max_score'], round($breakdown['total'], 2));
        
        return $breakdown;
    }

    /**
     * Lấy chi tiết điểm tất cả các nhóm
     */
    public function getAllBreakdown(array $features): array
    {
        return [
            'A' => $this->getGroupBreakdown('A', $features),
            'B' => $this->getGroupBreakdown('B', $features),
            'C' => $this->getGroupBreakdown('C', $features),
        ];
    }

    /**
     * Chuyển group scores thành array để đưa vào ML
     * 
     * @param array $groupScores ['A' => 30, 'B' => 28, 'C' => 22]
     * @return array [30, 28, 22]
     */
    public function scoresToArray(array $groupScores): array
    {
        return [
            (float) ($groupScores['A'] ?? 0),
            (float) ($groupScores['B'] ?? 0),
            (float) ($groupScores['C'] ?? 0),
        ];
    }

    /**
     * Lấy danh sách tên các nhóm
     */
    public function getGroupNames(): array
    {
        return array_keys(self::GROUP_CONFIG);
    }

    /**
     * Lấy max score của từng nhóm
     */
    public function getMaxScores(): array
    {
        return [
            'A' => self::GROUP_CONFIG['A']['max_score'],
            'B' => self::GROUP_CONFIG['B']['max_score'],
            'C' => self::GROUP_CONFIG['C']['max_score'],
            'total' => 100,
        ];
    }

    /**
     * Tính tổng điểm (không có trọng số)
     */
    public function calculateTotalScore(array $groupScores): float
    {
        return round(
            ($groupScores['A'] ?? 0) + ($groupScores['B'] ?? 0) + ($groupScores['C'] ?? 0),
            2
        );
    }

    /**
     * Chuẩn hóa group scores về tỷ lệ [0, 1]
     */
    public function normalizeScores(array $groupScores): array
    {
        return [
            'A' => ($groupScores['A'] ?? 0) / self::GROUP_CONFIG['A']['max_score'],
            'B' => ($groupScores['B'] ?? 0) / self::GROUP_CONFIG['B']['max_score'],
            'C' => ($groupScores['C'] ?? 0) / self::GROUP_CONFIG['C']['max_score'],
        ];
    }
}
