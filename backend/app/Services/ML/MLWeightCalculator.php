<?php

namespace App\Services\ML;

use Exception;

/**
 * ML Weight Calculator
 * 
 * Tính trọng số tự động cho các nhóm tiêu chí (A, B, C) bằng Ridge Regression.
 * 
 * Thuật toán:
 * 1. Input: Điểm các nhóm [score_A, score_B, score_C] và điểm thực tế (actual_score)
 * 2. Fit Ridge Regression: actual_score = w_A * score_A + w_B * score_B + w_C * score_C
 * 3. Áp dụng L2 regularization để tránh overfitting
 * 4. Chuẩn hóa trọng số về tổng = 1 và đảm bảo >= 0
 * 
 * @author IT Solo Leveling Team
 */
class MLWeightCalculator
{
    /**
     * Regularization strength (alpha)
     * Giá trị cao hơn = regularization mạnh hơn = trọng số nhỏ hơn
     */
    private float $alpha;

    /**
     * Trọng số đã học được
     * @var array|null ['A' => w_A, 'B' => w_B, 'C' => w_C]
     */
    private ?array $weights = null;

    /**
     * Thông tin scaling
     */
    private array $scalerInfo = [];

    /**
     * Metrics sau khi fit
     */
    private array $metrics = [];

    /**
     * Đã fit chưa
     */
    private bool $isFitted = false;

    /**
     * Constructor
     * 
     * @param float $alpha Regularization strength (mặc định 1.0)
     */
    public function __construct(float $alpha = 1.0)
    {
        $this->alpha = $alpha;
    }

    /**
     * Fit model - Học trọng số từ dữ liệu
     * 
     * @param array $groupScores 2D array [[score_A, score_B, score_C], ...]
     * @param array $actualScores 1D array [actual_score_1, actual_score_2, ...]
     * @return array Trọng số đã học ['A' => w, 'B' => w, 'C' => w]
     * @throws Exception
     */
    public function fit(array $groupScores, array $actualScores): array
    {
        $nSamples = count($groupScores);
        $nFeatures = 3; // A, B, C
        
        if ($nSamples < 3) {
            throw new Exception("Cần ít nhất 3 samples để train. Hiện có: {$nSamples}");
        }
        
        if ($nSamples !== count($actualScores)) {
            throw new Exception("Số lượng samples và labels không khớp");
        }

        // Bước 1: Chuẩn hóa dữ liệu (StandardScaler)
        $scaleResult = MLMathUtils::standardScale($groupScores);
        $XScaled = $scaleResult['scaled'];
        $this->scalerInfo = [
            'means' => $scaleResult['means'],
            'stds' => $scaleResult['stds'],
        ];

        // Bước 2: Tính Ridge Regression closed-form
        // w = (X'X + αI)^(-1) X'y
        
        // X'X (transpose X * X)
        $XT = MLMathUtils::matrixTranspose($XScaled);
        $XTX = MLMathUtils::matrixMultiply($XT, $this->toMatrix($XScaled));
        
        // Thêm regularization: X'X + αI
        $identity = MLMathUtils::identityMatrix($nFeatures);
        $regularized = MLMathUtils::matrixAdd(
            $XTX,
            MLMathUtils::matrixScalarMultiply($identity, $this->alpha)
        );
        
        // (X'X + αI)^(-1)
        $inverse = MLMathUtils::matrixInverse($regularized);
        
        // X'y
        $y = $actualScores;
        $XTy = [];
        for ($j = 0; $j < $nFeatures; $j++) {
            $sum = 0;
            for ($i = 0; $i < $nSamples; $i++) {
                $sum += $XScaled[$i][$j] * $y[$i];
            }
            $XTy[$j] = $sum;
        }
        
        // w = (X'X + αI)^(-1) X'y
        $rawWeights = MLMathUtils::matrixVectorMultiply($inverse, $XTy);
        
        // Bước 3: Đảm bảo trọng số không âm (project to positive)
        $positiveWeights = array_map(fn($w) => max(0, $w), $rawWeights);
        
        // Bước 4: Chuẩn hóa về tổng = 1
        $sum = array_sum($positiveWeights);
        if ($sum < 1e-10) {
            // Nếu tất cả weights = 0, dùng uniform
            $normalizedWeights = [1/3, 1/3, 1/3];
        } else {
            $normalizedWeights = array_map(fn($w) => $w / $sum, $positiveWeights);
        }
        
        $this->weights = [
            'A' => round($normalizedWeights[0], 4),
            'B' => round($normalizedWeights[1], 4),
            'C' => round($normalizedWeights[2], 4),
        ];
        
        // Bước 5: Tính metrics
        $predictions = $this->predictInternal($groupScores);
        
        $this->metrics = [
            'r2_score' => MLMathUtils::r2Score($predictions, $actualScores),
            'mae' => MLMathUtils::mae($predictions, $actualScores),
            'rmse' => MLMathUtils::rmse($predictions, $actualScores),
            'n_samples' => $nSamples,
            'alpha' => $this->alpha,
            'raw_weights' => $rawWeights,
        ];
        
        $this->isFitted = true;
        
        return $this->weights;
    }

    /**
     * Tính điểm có trọng số cho một sample
     * 
     * @param array $groupScores ['A' => score, 'B' => score, 'C' => score]
     * @return float Điểm có trọng số
     */
    public function applyWeights(array $groupScores): float
    {
        $weights = $this->getWeights();
        
        $weightedScore = 0;
        foreach (['A', 'B', 'C'] as $group) {
            $score = $groupScores[$group] ?? 0;
            $weight = $weights[$group] ?? 0;
            $weightedScore += $score * $weight;
        }
        
        return round($weightedScore, 2);
    }

    /**
     * Tính điểm có trọng số cho nhiều samples
     * 
     * @param array $groupScoresArray Array of ['A' => score, 'B' => score, 'C' => score]
     * @return array Array of weighted scores
     */
    public function applyWeightsBatch(array $groupScoresArray): array
    {
        return array_map(fn($scores) => $this->applyWeights($scores), $groupScoresArray);
    }

    /**
     * Lấy trọng số đã học
     * 
     * @return array ['A' => w, 'B' => w, 'C' => w]
     */
    public function getWeights(): array
    {
        if (!$this->isFitted) {
            // Trả về default weights nếu chưa fit
            return [
                'A' => 0.40,
                'B' => 0.35,
                'C' => 0.25,
            ];
        }
        
        return $this->weights;
    }

    /**
     * Set trọng số thủ công (không cần fit)
     * 
     * @param array $weights ['A' => w, 'B' => w, 'C' => w]
     * @return self
     */
    public function setWeights(array $weights): self
    {
        // Chuẩn hóa về tổng = 1
        $sum = ($weights['A'] ?? 0) + ($weights['B'] ?? 0) + ($weights['C'] ?? 0);
        
        if ($sum > 0) {
            $this->weights = [
                'A' => round(($weights['A'] ?? 0) / $sum, 4),
                'B' => round(($weights['B'] ?? 0) / $sum, 4),
                'C' => round(($weights['C'] ?? 0) / $sum, 4),
            ];
        } else {
            $this->weights = ['A' => 0.40, 'B' => 0.35, 'C' => 0.25];
        }
        
        $this->isFitted = true;
        
        return $this;
    }

    /**
     * Lấy metrics sau khi fit
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Kiểm tra đã fit chưa
     */
    public function isFitted(): bool
    {
        return $this->isFitted;
    }

    /**
     * Serialize model để lưu trữ
     */
    public function serialize(): array
    {
        return [
            'alpha' => $this->alpha,
            'weights' => $this->weights,
            'scaler_info' => $this->scalerInfo,
            'metrics' => $this->metrics,
            'is_fitted' => $this->isFitted,
        ];
    }

    /**
     * Deserialize model từ storage
     */
    public function deserialize(array $data): self
    {
        $this->alpha = $data['alpha'] ?? 1.0;
        $this->weights = $data['weights'] ?? null;
        $this->scalerInfo = $data['scaler_info'] ?? [];
        $this->metrics = $data['metrics'] ?? [];
        $this->isFitted = $data['is_fitted'] ?? false;
        
        return $this;
    }

    /**
     * Predict internal (dùng cho tính metrics)
     */
    private function predictInternal(array $groupScores): array
    {
        $predictions = [];
        
        foreach ($groupScores as $scores) {
            $pred = 0;
            $groups = ['A', 'B', 'C'];
            foreach ($groups as $i => $group) {
                $pred += $scores[$i] * $this->weights[$group];
            }
            $predictions[] = $pred;
        }
        
        return $predictions;
    }

    /**
     * Chuyển array thành matrix format
     */
    private function toMatrix(array $arr): array
    {
        // Nếu đã là 2D
        if (isset($arr[0]) && is_array($arr[0])) {
            return $arr;
        }
        
        // Chuyển 1D thành column vector
        return array_map(fn($v) => [$v], $arr);
    }

    /**
     * Demo function
     */
    public static function demo(): array
    {
        // Dữ liệu giả lập
        $groupScores = [
            [30, 28, 22],  // [score_A, score_B, score_C]
            [25, 30, 18],
            [28, 25, 20],
            [32, 27, 24],
            [20, 22, 15],
            [35, 32, 26],
            [22, 20, 18],
            [28, 28, 22],
            [30, 25, 20],
            [26, 26, 19],
        ];
        
        // Điểm thực tế sau phỏng vấn
        // Ground truth: 0.45*A + 0.35*B + 0.20*C (+ noise)
        $actualScores = [82, 75, 73, 85, 58, 92, 62, 80, 76, 72];
        
        $calculator = new self(alpha: 1.0);
        $weights = $calculator->fit($groupScores, $actualScores);
        
        return [
            'weights' => $weights,
            'metrics' => $calculator->getMetrics(),
            'example' => [
                'input' => ['A' => 30, 'B' => 28, 'C' => 22],
                'weighted_score' => $calculator->applyWeights(['A' => 30, 'B' => 28, 'C' => 22]),
            ],
        ];
    }
}
