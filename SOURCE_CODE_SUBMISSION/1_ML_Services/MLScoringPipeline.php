<?php

namespace App\Services\ML;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Job;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ML Scoring Pipeline
 * 
 * Pipeline hoàn chỉnh cho CV Scoring bằng Machine Learning.
 * Kết hợp tất cả components: Feature Extraction → Group Scoring → Weight Calculation → Random Forest
 * 
 * Quy trình:
 * 1. Trích xuất features từ Candidate data
 * 2. Tính điểm các nhóm A, B, C
 * 3. Áp dụng trọng số đã học (Regression-based)
 * 4. Dùng Random Forest dự đoán điểm ML
 * 5. Kết hợp: final_score = weighted_score × 0.4 + ml_score × 0.6
 * 6. Xếp loại và đưa ra khuyến nghị
 * 
 * @author IT Solo Leveling Team
 */
class MLScoringPipeline
{
    /**
     * Các thresholds xếp loại
     */
    public const CLASSIFICATION_THRESHOLDS = [
        'excellent' => ['min' => 90, 'max' => 100, 'label' => 'Xuất sắc', 'action' => 'Phỏng vấn ngay'],
        'good' => ['min' => 75, 'max' => 89, 'label' => 'Tốt', 'action' => 'Ưu tiên phỏng vấn'],
        'fair' => ['min' => 60, 'max' => 74, 'label' => 'Khá', 'action' => 'Xem xét thêm'],
        'poor' => ['min' => 0, 'max' => 59, 'label' => 'Không phù hợp', 'action' => 'Loại'],
    ];

    /**
     * Tỷ lệ kết hợp điểm mặc định
     * weighted = group scoring (rule-based), ml = Random Forest
     * Khi RF được train tốt với data thực, có thể tăng ml lên
     */
    public const DEFAULT_COMBINATION_RATIO = [
        'weighted' => 0.5,
        'ml' => 0.5,
    ];

    /**
     * Components
     */
    private MLFeatureExtractor $featureExtractor;
    private MLGroupScorer $groupScorer;
    private MLWeightCalculator $weightCalculator;
    private MLRandomForestScorer $randomForest;

    /**
     * Tỷ lệ kết hợp điểm
     */
    private array $combinationRatio;

    /**
     * Trạng thái models
     */
    private bool $isWeightModelFitted = false;
    private bool $isRFModelFitted = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->featureExtractor = new MLFeatureExtractor();
        $this->groupScorer = new MLGroupScorer();
        $this->weightCalculator = new MLWeightCalculator();
        $this->randomForest = new MLRandomForestScorer(
            nEstimators: 100,
            maxDepth: 10,
            minSamplesSplit: 5,
            minSamplesLeaf: 3
        );
        
        $this->combinationRatio = self::DEFAULT_COMBINATION_RATIO;
        
        // Load saved models nếu có
        $this->loadModels();
    }

    /**
     * Score một Application
     * 
     * @param Application $application
     * @param Job|null $job
     * @return array Kết quả scoring đầy đủ
     */
    public function score(Application $application, ?Job $job = null): array
    {
        $candidate = $application->candidate;
        $job = $job ?? $application->job;
        
        if (!$candidate) {
            return $this->createErrorResult('Không tìm thấy thông tin ứng viên');
        }
        
        return $this->scoreCandidate($candidate, $job);
    }

    /**
     * Score một Candidate
     * 
     * @param Candidate $candidate
     * @param Job|null $job
     * @return array Kết quả scoring đầy đủ
     */
    public function scoreCandidate(Candidate $candidate, ?Job $job = null): array
    {
        try {
            // Bước 1: Trích xuất features
            $features = $this->featureExtractor->extract($candidate, $job);
            
            // Bước 2: Tính điểm các nhóm
            $groupScores = $this->groupScorer->scoreAll($features);
            
            // Bước 3: Weighted score = tổng điểm các nhóm (đã scale 0-100)
            $weights = $this->weightCalculator->getWeights();
            $weightedScore = $groupScores['total']; // Total đã tính đúng: A(max 35) + B(max 35) + C(max 30) = 100
            
            // Bước 4: Dự đoán bằng Random Forest
            $mlScore = $this->predictWithRandomForest($features);
            
            // Bước 5: Kết hợp điểm
            $finalScore = $this->combineScores($weightedScore, $mlScore);
            
            // Bước 6: Xếp loại
            $classification = $this->classify($finalScore);
            
            // Tính confidence (dựa trên sự đồng thuận giữa weighted và ML score)
            $scoreDiff = abs($weightedScore - $mlScore);
            $confidence = max(0, min(1, 1 - ($scoreDiff / 50)));
            
            return [
                'success' => true,
                'candidate_id' => $candidate->id,
                'features' => $features,
                'group_scores' => [
                    'A' => round($groupScores['A'], 2),
                    'B' => round($groupScores['B'], 2),
                    'C' => round($groupScores['C'], 2),
                    'total' => round($groupScores['total'], 2),
                ],
                'weights' => $weights,
                'weighted_score' => round($weightedScore, 2),
                'ml_score' => round($mlScore, 2),
                'final_score' => round($finalScore, 2),
                'confidence' => round($confidence, 3),
                'classification' => $classification['label'],
                'recommended_action' => $classification['action'],
                'combination_ratio' => $this->combinationRatio,
                'breakdown' => [
                    'weighted_contribution' => round($weightedScore * $this->combinationRatio['weighted'], 2),
                    'ml_contribution' => round($mlScore * $this->combinationRatio['ml'], 2),
                ],
                'group_breakdown' => $this->groupScorer->getAllBreakdown($features),
                'feature_importance' => $this->getFeatureImportance(),
                'model_info' => [
                    'weight_model_fitted' => $this->isWeightModelFitted,
                    'rf_model_fitted' => $this->isRFModelFitted,
                ],
            ];
        } catch (\Exception $e) {
            Log::error("ML Scoring error: " . $e->getMessage(), [
                'candidate_id' => $candidate->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return $this->createErrorResult($e->getMessage());
        }
    }

    /**
     * Score nhiều Applications
     * 
     * @param array $applications Array of Application
     * @param Job|null $job
     * @return array Array of results
     */
    public function scoreBatch(array $applications, ?Job $job = null): array
    {
        return array_map(fn($app) => $this->score($app, $job), $applications);
    }

    /**
     * Score từ raw data (cho API hoặc testing)
     * 
     * @param array $data Raw data từ form
     * @param array $jobRequirements Requirements từ job
     * @return array Kết quả scoring
     */
    public function scoreFromData(array $data, array $jobRequirements = []): array
    {
        try {
            // Trích xuất features từ array
            $features = $this->featureExtractor->extractFromArray($data, $jobRequirements);
            
            // Tính điểm các nhóm
            $groupScores = $this->groupScorer->scoreAll($features);
            
            // Weighted score = tổng điểm các nhóm (đã scale 0-100)
            $weights = $this->weightCalculator->getWeights();
            $weightedScore = $groupScores['total'];
            
            // Dự đoán bằng Random Forest
            $mlScore = $this->predictWithRandomForest($features);
            
            // Kết hợp điểm
            $finalScore = $this->combineScores($weightedScore, $mlScore);
            
            // Xếp loại
            $classification = $this->classify($finalScore);
            
            return [
                'success' => true,
                'features' => $features,
                'group_scores' => $groupScores,
                'weights' => $weights,
                'weighted_score' => round($weightedScore, 2),
                'ml_score' => round($mlScore, 2),
                'final_score' => round($finalScore, 2),
                'classification' => $classification['label'],
                'recommended_action' => $classification['action'],
            ];
        } catch (\Exception $e) {
            return $this->createErrorResult($e->getMessage());
        }
    }

    /**
     * Train toàn bộ pipeline với training data
     * 
     * @param array $trainingData Array of ['features' => [...], 'group_scores' => [...], 'actual_score' => float]
     * @return array Training results
     */
    public function train(array $trainingData): array
    {
        if (count($trainingData) < 10) {
            return ['success' => false, 'error' => 'Cần ít nhất 10 samples để train'];
        }
        
        try {
            // Chuẩn bị data cho Weight Calculator
            $groupScoresArray = [];
            $actualScores = [];
            
            // Chuẩn bị data cho Random Forest
            $X = [];
            $y = [];
            
            foreach ($trainingData as $sample) {
                // Group scores
                $groupScoresArray[] = [
                    $sample['group_scores']['A'] ?? 0,
                    $sample['group_scores']['B'] ?? 0,
                    $sample['group_scores']['C'] ?? 0,
                ];
                
                $actualScores[] = $sample['actual_score'];
                
                // Features
                $X[] = $this->featureExtractor->featuresToArray($sample['features']);
                $y[] = $sample['actual_score'];
            }
            
            // Train Weight Calculator
            $weights = $this->weightCalculator->fit($groupScoresArray, $actualScores);
            $this->isWeightModelFitted = true;
            
            // Train Random Forest
            $this->randomForest->fit($X, $y, $this->featureExtractor->getFeatureNames());
            $this->isRFModelFitted = true;
            
            // Lưu models
            $this->saveModels();
            
            return [
                'success' => true,
                'weights' => $weights,
                'weight_metrics' => $this->weightCalculator->getMetrics(),
                'rf_metrics' => $this->randomForest->getMetrics(),
                'feature_importance' => $this->randomForest->getFeatureImportanceNamed(),
                'training_samples' => count($trainingData),
            ];
        } catch (\Exception $e) {
            Log::error("ML Training error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Train từ raw features array và target scores
     * Format: $X = [[f1, f2, ...], ...], $y = [score1, score2, ...]
     * 
     * @param array $X 2D array of features
     * @param array $y 1D array of target scores
     * @return array
     */
    public function trainFromFeatures(array $X, array $y): array
    {
        if (count($X) < 10 || count($X) !== count($y)) {
            return ['success' => false, 'error' => 'Cần ít nhất 10 samples, và X/y phải cùng size'];
        }

        try {
            // Tính group scores cho mỗi sample để train Weight Calculator
            $groupScoresArray = [];
            
            foreach ($X as $i => $featureArray) {
                // Convert array to named features
                $featureNames = $this->featureExtractor->getFeatureNames();
                $features = [];
                foreach ($featureNames as $idx => $name) {
                    $features[$name] = $featureArray[$idx] ?? 0;
                }
                
                // Calculate group scores
                $groupScores = $this->groupScorer->scoreAll($features);
                $groupScoresArray[] = [
                    $groupScores['A'],
                    $groupScores['B'],
                    $groupScores['C'],
                ];
            }
            
            // Train Weight Calculator với Ridge Regression
            $weights = $this->weightCalculator->fit($groupScoresArray, $y);
            $this->isWeightModelFitted = true;
            
            // Train Random Forest
            $this->randomForest->fit($X, $y, $this->featureExtractor->getFeatureNames());
            $this->isRFModelFitted = true;
            
            // Lưu models
            $this->saveModels();
            
            return [
                'success' => true,
                'weights' => $weights,
                'weight_metrics' => $this->weightCalculator->getMetrics(),
                'rf_metrics' => $this->randomForest->getMetrics(),
                'feature_importance' => $this->randomForest->getFeatureImportanceNamed(),
                'training_samples' => count($X),
            ];
        } catch (\Exception $e) {
            Log::error("ML Training error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Label một sample (sau phỏng vấn)
     * 
     * @param int $applicationId
     * @param float $actualScore Điểm thực tế
     * @param string|null $interviewResult 'pass', 'fail', 'pending'
     * @return bool
     */
    public function labelSample(int $applicationId, float $actualScore, ?string $interviewResult = null): bool
    {
        try {
            DB::table('ml_training_data')
                ->where('application_id', $applicationId)
                ->update([
                    'actual_score' => $actualScore,
                    'interview_result' => $interviewResult,
                    'labeled_at' => now(),
                ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("ML Label error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrain models với dữ liệu mới từ database
     */
    public function retrain(): array
    {
        try {
            // Lấy dữ liệu đã label từ database
            $labeledData = DB::table('ml_training_data')
                ->whereNotNull('actual_score')
                ->get();
            
            if ($labeledData->count() < 10) {
                return ['success' => false, 'error' => 'Chưa đủ dữ liệu để retrain (cần ít nhất 10 samples)'];
            }
            
            $trainingData = [];
            foreach ($labeledData as $row) {
                $trainingData[] = [
                    'features' => [
                        'experience_years' => $row->experience_years,
                        'projects_count' => $row->projects_count,
                        'tech_match_count' => $row->tech_match_count,
                        'main_skills_count' => $row->main_skills_count,
                        'sub_skills_count' => $row->sub_skills_count,
                        'certifications_count' => $row->certifications_count,
                        'education_score' => $row->education_score,
                        'cv_quality_score' => $row->cv_quality_score,
                        'soft_skills_count' => $row->soft_skills_count,
                        'portfolio_score' => $row->portfolio_score,
                    ],
                    'group_scores' => [
                        'A' => $row->score_group_a,
                        'B' => $row->score_group_b,
                        'C' => $row->score_group_c,
                    ],
                    'actual_score' => $row->actual_score,
                ];
            }
            
            return $this->train($trainingData);
        } catch (\Exception $e) {
            Log::error("ML Retrain error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lưu kết quả scoring vào database (cho training sau này)
     */
    public function saveToTrainingData(Application $application, array $scoringResult): bool
    {
        try {
            $candidate = $application->candidate;
            
            DB::table('ml_training_data')->updateOrInsert(
                ['application_id' => $application->id],
                [
                    'candidate_id' => $candidate->id ?? null,
                    'job_id' => $application->job_id,
                    
                    // Features
                    'experience_years' => $scoringResult['features']['experience_years'] ?? 0,
                    'projects_count' => $scoringResult['features']['projects_count'] ?? 0,
                    'tech_match_count' => $scoringResult['features']['tech_match_count'] ?? 0,
                    'main_skills_count' => $scoringResult['features']['main_skills_count'] ?? 0,
                    'sub_skills_count' => $scoringResult['features']['sub_skills_count'] ?? 0,
                    'certifications_count' => $scoringResult['features']['certifications_count'] ?? 0,
                    'education_score' => $scoringResult['features']['education_score'] ?? 0,
                    'cv_quality_score' => $scoringResult['features']['cv_quality_score'] ?? 0,
                    'soft_skills_count' => $scoringResult['features']['soft_skills_count'] ?? 0,
                    'portfolio_score' => $scoringResult['features']['portfolio_score'] ?? 0,
                    
                    // Group scores
                    'score_group_a' => $scoringResult['group_scores']['A'] ?? 0,
                    'score_group_b' => $scoringResult['group_scores']['B'] ?? 0,
                    'score_group_c' => $scoringResult['group_scores']['C'] ?? 0,
                    
                    // Predictions
                    'weighted_score' => $scoringResult['weighted_score'] ?? 0,
                    'ml_score' => $scoringResult['ml_score'] ?? 0,
                    'final_score' => $scoringResult['final_score'] ?? 0,
                    'classification' => $scoringResult['classification'] ?? '',
                    
                    'scored_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            return true;
        } catch (\Exception $e) {
            Log::error("Save to training data error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cập nhật tỷ lệ kết hợp điểm
     * 
     * @param float $weightedRatio Tỷ lệ điểm có trọng số (0-1)
     * @param float $mlRatio Tỷ lệ điểm ML (0-1)
     */
    public function setCombinationRatio(float $weightedRatio, float $mlRatio): self
    {
        $total = $weightedRatio + $mlRatio;
        
        if ($total > 0) {
            $this->combinationRatio = [
                'weighted' => $weightedRatio / $total,
                'ml' => $mlRatio / $total,
            ];
        }
        
        return $this;
    }

    /**
     * Lấy feature importance
     */
    public function getFeatureImportance(): array
    {
        if ($this->isRFModelFitted) {
            return $this->randomForest->getFeatureImportanceNamed();
        }
        
        return [];
    }

    /**
     * Lấy weights hiện tại
     */
    public function getWeights(): array
    {
        return $this->weightCalculator->getWeights();
    }

    /**
     * Score nhiều Candidates
     * 
     * @param \Illuminate\Support\Collection $candidates
     * @param Job|null $job
     * @return array Array of results
     */
    public function scoreMultipleCandidates($candidates, ?Job $job = null): array
    {
        $results = [];
        foreach ($candidates as $candidate) {
            $results[] = $this->scoreCandidate($candidate, $job);
        }
        return $results;
    }

    /**
     * Trích xuất features từ Candidate (public wrapper)
     */
    public function extractFeatures(Candidate $candidate, ?Job $job = null): array
    {
        return $this->featureExtractor->extract($candidate, $job);
    }

    /**
     * Tính group scores từ features (public wrapper)
     */
    public function calculateGroupScores(array $features): array
    {
        return $this->groupScorer->scoreAll($features);
    }

    /**
     * Set config parameters
     */
    public function setConfig(array $config): self
    {
        if (isset($config['n_estimators'])) {
            $this->randomForest = new MLRandomForestScorer(
                nEstimators: $config['n_estimators'],
                maxDepth: $config['max_depth'] ?? 10,
                minSamplesSplit: $config['min_samples_split'] ?? 5,
                minSamplesLeaf: $config['min_samples_leaf'] ?? 3
            );
        }
        return $this;
    }

    /**
     * Lấy model data để serialize
     */
    public function getModelData(): array
    {
        return [
            'weight_calculator' => $this->weightCalculator->serialize(),
            'random_forest' => $this->randomForest->serialize(),
            'combination_ratio' => $this->combinationRatio,
        ];
    }

    /**
     * Lấy hyperparameters
     */
    public function getHyperparameters(): array
    {
        return [
            'n_estimators' => $this->randomForest->getConfig()['n_estimators'] ?? 100,
            'max_depth' => $this->randomForest->getConfig()['max_depth'] ?? 10,
            'min_samples_split' => $this->randomForest->getConfig()['min_samples_split'] ?? 5,
            'min_samples_leaf' => $this->randomForest->getConfig()['min_samples_leaf'] ?? 3,
            'combination_ratio' => $this->combinationRatio,
        ];
    }

    /**
     * Lấy danh sách feature names
     */
    public function getFeatureNames(): array
    {
        return $this->featureExtractor->getFeatureNames();
    }

    /**
     * Lấy metrics của models
     */
    public function getModelMetrics(): array
    {
        return [
            'weight_calculator' => $this->isWeightModelFitted 
                ? $this->weightCalculator->getMetrics() 
                : null,
            'random_forest' => $this->isRFModelFitted 
                ? $this->randomForest->getMetrics() 
                : null,
        ];
    }

    // ========================================================================
    // PRIVATE METHODS
    // ========================================================================

    /**
     * Predict với Random Forest
     */
    private function predictWithRandomForest(array $features): float
    {
        $fallback = $this->fallbackPredict($features);
        
        if (!$this->isRFModelFitted) {
            // Nếu chưa có model, dùng fallback
            return $fallback;
        }
        
        $featureArray = $this->featureExtractor->featuresToArray($features);
        $rawPrediction = $this->randomForest->predictSingle($featureArray);
        
        // Clip prediction to valid range [0, 100]
        $prediction = MLMathUtils::clip($rawPrediction, 0, 100);
        
        // Blend RF prediction với fallback (group total)
        // RF weight tăng dần theo confidence của model
        $rfWeight = min(0.7, $this->randomForest->getOobScore() ?? 0.5);
        $blendedPrediction = $prediction * $rfWeight + $fallback * (1 - $rfWeight);
        
        return round($blendedPrediction, 2);
    }

    /**
     * Fallback predict khi chưa có model
     */
    private function fallbackPredict(array $features): float
    {
        // Dùng weighted sum đơn giản
        $groupScores = $this->groupScorer->scoreAll($features);
        return $groupScores['total'];
    }

    /**
     * Kết hợp điểm weighted và ML
     */
    private function combineScores(float $weightedScore, float $mlScore): float
    {
        $combined = $weightedScore * $this->combinationRatio['weighted'] +
                    $mlScore * $this->combinationRatio['ml'];
        
        return MLMathUtils::clip($combined, 0, 100);
    }

    /**
     * Xếp loại theo điểm
     */
    private function classify(float $score): array
    {
        foreach (self::CLASSIFICATION_THRESHOLDS as $level => $info) {
            if ($score >= $info['min'] && $score <= $info['max']) {
                return $info;
            }
        }
        
        return ['label' => 'Không xác định', 'action' => 'Xem xét'];
    }

    /**
     * Tạo error result
     */
    private function createErrorResult(string $error): array
    {
        return [
            'success' => false,
            'error' => $error,
            'final_score' => 0,
            'classification' => 'Lỗi',
            'recommended_action' => 'Kiểm tra lại dữ liệu',
        ];
    }

    /**
     * Lưu models vào cache/storage
     */
    private function saveModels(): void
    {
        try {
            $modelData = [
                'weight_calculator' => $this->weightCalculator->serialize(),
                'random_forest' => $this->randomForest->serialize(),
                'combination_ratio' => $this->combinationRatio,
                'saved_at' => now()->toISOString(),
            ];
            
            Cache::forever('ml_scoring_models', $modelData);
            
            // Cũng lưu vào database
            DB::table('ml_models')->updateOrInsert(
                ['model_type' => 'scoring_pipeline'],
                [
                    'version' => 'v' . date('Y.m.d.His'),
                    'model_data' => json_encode($modelData),
                    'feature_names' => json_encode($this->featureExtractor->getFeatureNames()),
                    'metrics' => json_encode($this->getModelMetrics()),
                    'feature_importance' => json_encode($this->getFeatureImportance()),
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::warning("Could not save ML models: " . $e->getMessage());
        }
    }

    /**
     * Load models từ cache/storage
     */
    private function loadModels(): void
    {
        try {
            // Thử load từ cache trước
            $modelData = Cache::get('ml_scoring_models');
            
            // Nếu không có trong cache, load từ database
            if (!$modelData) {
                $dbModel = DB::table('ml_models')
                    ->where('model_type', 'scoring_pipeline')
                    ->where('is_active', true)
                    ->orderBy('updated_at', 'desc')
                    ->first();
                
                if ($dbModel) {
                    $modelData = json_decode($dbModel->model_data, true);
                    Cache::forever('ml_scoring_models', $modelData);
                }
            }
            
            if ($modelData) {
                if (isset($modelData['weight_calculator'])) {
                    $this->weightCalculator->deserialize($modelData['weight_calculator']);
                    $this->isWeightModelFitted = $modelData['weight_calculator']['is_fitted'] ?? false;
                }
                
                if (isset($modelData['random_forest'])) {
                    $this->randomForest->deserialize($modelData['random_forest']);
                    $this->isRFModelFitted = $modelData['random_forest']['is_fitted'] ?? false;
                }
                
                if (isset($modelData['combination_ratio'])) {
                    $this->combinationRatio = $modelData['combination_ratio'];
                }
            }
        } catch (\Exception $e) {
            Log::warning("Could not load ML models: " . $e->getMessage());
        }
    }

    /**
     * Demo function
     */
    public static function demo(): array
    {
        $pipeline = new self();
        
        // Tạo dữ liệu training giả lập
        mt_srand(42);
        $trainingData = [];
        
        for ($i = 0; $i < 50; $i++) {
            $features = [
                'experience_years' => mt_rand(0, 150) / 10,
                'projects_count' => mt_rand(0, 10),
                'tech_match_count' => mt_rand(2, 10),
                'main_skills_count' => mt_rand(1, 6),
                'sub_skills_count' => mt_rand(0, 5),
                'certifications_count' => mt_rand(0, 5),
                'education_score' => mt_rand(3, 10),
                'cv_quality_score' => mt_rand(2, 10),
                'soft_skills_count' => mt_rand(0, 6),
                'portfolio_score' => mt_rand(0, 5),
            ];
            
            $groupScores = $pipeline->calculateGroupScores($features);
            
            // Simulate actual score
            $actualScore = 0.45 * $groupScores['A'] + 
                          0.35 * $groupScores['B'] + 
                          0.20 * $groupScores['C'] +
                          mt_rand(-50, 50) / 10;
            
            $trainingData[] = [
                'features' => $features,
                'group_scores' => $groupScores,
                'actual_score' => max(0, min(100, $actualScore)),
            ];
        }
        
        // Train
        $trainResult = $pipeline->train($trainingData);
        
        // Test scoring
        $testData = [
            'experience_years' => 5,
            'projects_count' => 4,
            'tech_match_count' => 7,
            'main_skills_count' => 4,
            'sub_skills_count' => 3,
            'certifications_count' => 2,
            'education_score' => 10,
            'cv_quality_score' => 8,
            'soft_skills_count' => 4,
            'portfolio_score' => 4,
        ];
        
        $scoreResult = $pipeline->scoreFromData($testData);
        
        return [
            'training' => $trainResult,
            'test_scoring' => $scoreResult,
        ];
    }
}
