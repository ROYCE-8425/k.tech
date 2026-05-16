<?php

namespace App\Services\ML;

/**
 * ML Random Forest Regressor
 * 
 * Implementation của Random Forest cho Regression task.
 * Ensemble của nhiều Decision Trees với Bootstrap Sampling và Random Feature Selection.
 * 
 * Thuật toán:
 * 1. Bootstrap Sampling: Tạo T subsets từ training data (sampling with replacement)
 * 2. Random Feature Selection: Mỗi tree chỉ xét subset của features tại mỗi split
 * 3. Build T independent Decision Trees
 * 4. Aggregate predictions bằng averaging (cho regression)
 * 
 * @author IT Solo Leveling Team
 */
class MLRandomForestScorer
{
    /**
     * Hyperparameters
     */
    private int $nEstimators;       // Số lượng trees
    private int $maxDepth;          // Độ sâu tối đa mỗi tree
    private int $minSamplesSplit;   // Min samples để split
    private int $minSamplesLeaf;    // Min samples ở leaf
    private int|string|null $maxFeatures;  // Số features mỗi split: int, 'sqrt', 'log2', null
    private bool $bootstrap;        // Có dùng bootstrap sampling
    private bool $oobScore;         // Có tính OOB score
    private int $randomState;       // Random seed

    /**
     * Trained trees
     * @var MLDecisionTree[]
     */
    private array $trees = [];

    /**
     * Feature names
     */
    private array $featureNames = [];

    /**
     * Aggregated feature importance
     */
    private array $featureImportance = [];

    /**
     * OOB samples cho mỗi tree
     */
    private array $oobIndices = [];

    /**
     * Training metrics
     */
    private array $metrics = [];

    /**
     * Đã fit chưa
     */
    private bool $isFitted = false;

    /**
     * Constructor
     * 
     * @param int $nEstimators Số lượng trees (mặc định 100)
     * @param int $maxDepth Độ sâu tối đa (mặc định 10)
     * @param int $minSamplesSplit Min samples để split (mặc định 5)
     * @param int $minSamplesLeaf Min samples ở leaf (mặc định 3)
     * @param int|string|null $maxFeatures Số features: int, 'sqrt', 'log2', null (mặc định 'sqrt')
     * @param bool $bootstrap Sử dụng bootstrap sampling (mặc định true)
     * @param bool $oobScore Tính OOB score (mặc định true)
     * @param int $randomState Random seed (mặc định 42)
     */
    public function __construct(
        int $nEstimators = 100,
        int $maxDepth = 10,
        int $minSamplesSplit = 5,
        int $minSamplesLeaf = 3,
        int|string|null $maxFeatures = 'sqrt',
        bool $bootstrap = true,
        bool $oobScore = true,
        int $randomState = 42
    ) {
        $this->nEstimators = $nEstimators;
        $this->maxDepth = $maxDepth;
        $this->minSamplesSplit = $minSamplesSplit;
        $this->minSamplesLeaf = $minSamplesLeaf;
        $this->maxFeatures = $maxFeatures;
        $this->bootstrap = $bootstrap;
        $this->oobScore = $oobScore;
        $this->randomState = $randomState;
    }

    /**
     * Fit Random Forest với training data
     * 
     * @param array $X Features 2D array [n_samples x n_features]
     * @param array $y Target 1D array [n_samples]
     * @param array $featureNames Tên các features (optional)
     * @return self
     */
    public function fit(array $X, array $y, array $featureNames = []): self
    {
        $nSamples = count($X);
        $nFeatures = count($X[0] ?? []);
        
        if ($nSamples < 5) {
            throw new \RuntimeException("Cần ít nhất 5 samples để train Random Forest");
        }
        
        // Set feature names
        if (empty($featureNames)) {
            $this->featureNames = array_map(fn($i) => "feature_{$i}", range(0, $nFeatures - 1));
        } else {
            $this->featureNames = $featureNames;
        }
        
        // Calculate max_features
        $maxFeaturesInt = $this->calculateMaxFeatures($nFeatures);
        
        // Set random seed
        mt_srand($this->randomState);
        
        // Initialize
        $this->trees = [];
        $this->oobIndices = [];
        $this->featureImportance = array_fill(0, $nFeatures, 0.0);
        
        $oobPredictions = array_fill(0, $nSamples, []);
        
        // Build trees
        for ($t = 0; $t < $this->nEstimators; $t++) {
            // Bootstrap sampling
            if ($this->bootstrap) {
                $sampleIndices = MLMathUtils::bootstrapSample(range(0, $nSamples - 1), $nSamples);
                
                // Track OOB indices
                $inBag = array_unique($sampleIndices);
                $oobIdx = array_diff(range(0, $nSamples - 1), $inBag);
                $this->oobIndices[$t] = array_values($oobIdx);
            } else {
                $sampleIndices = range(0, $nSamples - 1);
                $this->oobIndices[$t] = [];
            }
            
            // Get bootstrap sample
            $XSample = array_map(fn($i) => $X[$i], $sampleIndices);
            $ySample = array_map(fn($i) => $y[$i], $sampleIndices);
            
            // Create and fit tree
            $tree = new MLDecisionTree(
                maxDepth: $this->maxDepth,
                minSamplesSplit: $this->minSamplesSplit,
                minSamplesLeaf: $this->minSamplesLeaf,
                maxFeatures: $maxFeaturesInt,
                randomState: $this->randomState + $t
            );
            
            $tree->fit($XSample, $ySample, $this->featureNames);
            $this->trees[] = $tree;
            
            // Accumulate feature importance
            $treeImportance = $tree->getFeatureImportance();
            foreach ($treeImportance as $i => $imp) {
                $this->featureImportance[$i] += $imp;
            }
            
            // Collect OOB predictions
            if ($this->oobScore && $this->bootstrap) {
                foreach ($this->oobIndices[$t] as $oobIdx) {
                    $pred = $tree->predictSingle($X[$oobIdx]);
                    $oobPredictions[$oobIdx][] = $pred;
                }
            }
        }
        
        // Normalize feature importance
        $totalImportance = array_sum($this->featureImportance);
        if ($totalImportance > 0) {
            $this->featureImportance = array_map(
                fn($imp) => $imp / $totalImportance,
                $this->featureImportance
            );
        }
        
        // Mark as fitted trước khi predict (để predict không throw)
        $this->isFitted = true;
        
        // Calculate metrics
        $predictions = $this->predict($X);
        
        $this->metrics = [
            'r2_score' => MLMathUtils::r2Score($predictions, $y),
            'mae' => MLMathUtils::mae($predictions, $y),
            'rmse' => MLMathUtils::rmse($predictions, $y),
            'n_samples' => $nSamples,
            'n_features' => $nFeatures,
            'n_estimators' => $this->nEstimators,
        ];
        
        // Calculate OOB score
        if ($this->oobScore && $this->bootstrap) {
            $oobActual = [];
            $oobPred = [];
            
            for ($i = 0; $i < $nSamples; $i++) {
                if (!empty($oobPredictions[$i])) {
                    $oobActual[] = $y[$i];
                    $oobPred[] = MLMathUtils::mean($oobPredictions[$i]);
                }
            }
            
            if (count($oobActual) > 0) {
                $this->metrics['oob_score'] = MLMathUtils::r2Score($oobPred, $oobActual);
                $this->metrics['oob_samples'] = count($oobActual);
            }
        }
        
        return $this;
    }

    /**
     * Predict cho nhiều samples
     * 
     * @param array $X Features 2D array [n_samples x n_features]
     * @return array Predictions
     */
    public function predict(array $X): array
    {
        if (!$this->isFitted) {
            throw new \RuntimeException("Model chưa được fit. Gọi fit() trước.");
        }
        
        $nSamples = count($X);
        $predictions = array_fill(0, $nSamples, 0.0);
        
        // Aggregate predictions từ tất cả trees
        foreach ($this->trees as $tree) {
            $treePredictions = $tree->predict($X);
            for ($i = 0; $i < $nSamples; $i++) {
                $predictions[$i] += $treePredictions[$i];
            }
        }
        
        // Average
        $nTrees = count($this->trees);
        return array_map(fn($p) => $p / $nTrees, $predictions);
    }

    /**
     * Predict cho một sample
     * 
     * @param array $sample Feature array 1D
     * @return float Prediction
     */
    public function predictSingle(array $sample): float
    {
        if (!$this->isFitted) {
            throw new \RuntimeException("Model chưa được fit. Gọi fit() trước.");
        }
        
        $predictions = [];
        foreach ($this->trees as $tree) {
            $predictions[] = $tree->predictSingle($sample);
        }
        
        return MLMathUtils::mean($predictions);
    }

    /**
     * Predict với confidence interval
     * 
     * @param array $sample Feature array 1D
     * @param float $confidence Confidence level (0.95 = 95%)
     * @return array ['prediction' => val, 'lower' => val, 'upper' => val]
     */
    public function predictWithConfidence(array $sample, float $confidence = 0.95): array
    {
        $predictions = [];
        foreach ($this->trees as $tree) {
            $predictions[] = $tree->predictSingle($sample);
        }
        
        $mean = MLMathUtils::mean($predictions);
        $std = MLMathUtils::std($predictions);
        
        // Z-score for confidence level
        $zScores = [
            0.90 => 1.645,
            0.95 => 1.96,
            0.99 => 2.576,
        ];
        $z = $zScores[$confidence] ?? 1.96;
        
        $margin = $z * $std / sqrt(count($predictions));
        
        return [
            'prediction' => $mean,
            'std' => $std,
            'lower' => $mean - $margin,
            'upper' => $mean + $margin,
            'confidence' => $confidence,
        ];
    }

    /**
     * Lấy feature importance
     * 
     * @return array [feature_index => importance]
     */
    public function getFeatureImportance(): array
    {
        return $this->featureImportance;
    }

    /**
     * Lấy feature importance với tên, sắp xếp giảm dần
     * 
     * @return array [feature_name => importance]
     */
    public function getFeatureImportanceNamed(): array
    {
        $result = [];
        foreach ($this->featureImportance as $i => $importance) {
            $name = $this->featureNames[$i] ?? "feature_{$i}";
            $result[$name] = round($importance, 4);
        }
        arsort($result);
        return $result;
    }

    /**
     * Lấy metrics sau khi fit
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Lấy OOB score (nếu có)
     */
    public function getOobScore(): ?float
    {
        return $this->metrics['oob_r2'] ?? null;
    }

    /**
     * Kiểm tra đã fit chưa
     */
    public function isFitted(): bool
    {
        return $this->isFitted;
    }

    /**
     * Lấy số lượng trees
     */
    public function getNumTrees(): int
    {
        return count($this->trees);
    }

    /**
     * Lấy config/hyperparameters
     */
    public function getConfig(): array
    {
        return [
            'n_estimators' => $this->nEstimators,
            'max_depth' => $this->maxDepth,
            'min_samples_split' => $this->minSamplesSplit,
            'min_samples_leaf' => $this->minSamplesLeaf,
            'max_features' => $this->maxFeatures,
            'bootstrap' => $this->bootstrap,
            'oob_score' => $this->oobScore,
            'random_state' => $this->randomState,
        ];
    }

    /**
     * Serialize model để lưu trữ
     */
    public function serialize(): array
    {
        return [
            'params' => [
                'n_estimators' => $this->nEstimators,
                'max_depth' => $this->maxDepth,
                'min_samples_split' => $this->minSamplesSplit,
                'min_samples_leaf' => $this->minSamplesLeaf,
                'max_features' => $this->maxFeatures,
                'bootstrap' => $this->bootstrap,
                'oob_score' => $this->oobScore,
                'random_state' => $this->randomState,
            ],
            'trees' => array_map(fn($tree) => $tree->serialize(), $this->trees),
            'feature_names' => $this->featureNames,
            'feature_importance' => $this->featureImportance,
            'metrics' => $this->metrics,
            'is_fitted' => $this->isFitted,
        ];
    }

    /**
     * Deserialize model từ storage
     */
    public function deserialize(array $data): self
    {
        if (isset($data['params'])) {
            $this->nEstimators = $data['params']['n_estimators'];
            $this->maxDepth = $data['params']['max_depth'];
            $this->minSamplesSplit = $data['params']['min_samples_split'];
            $this->minSamplesLeaf = $data['params']['min_samples_leaf'];
            $this->maxFeatures = $data['params']['max_features'];
            $this->bootstrap = $data['params']['bootstrap'];
            $this->oobScore = $data['params']['oob_score'];
            $this->randomState = $data['params']['random_state'];
        }
        
        $this->trees = [];
        foreach ($data['trees'] ?? [] as $treeData) {
            $tree = new MLDecisionTree();
            $tree->deserialize($treeData);
            $this->trees[] = $tree;
        }
        
        $this->featureNames = $data['feature_names'] ?? [];
        $this->featureImportance = $data['feature_importance'] ?? [];
        $this->metrics = $data['metrics'] ?? [];
        $this->isFitted = $data['is_fitted'] ?? false;
        
        return $this;
    }

    /**
     * Calculate max_features từ config
     */
    private function calculateMaxFeatures(int $nFeatures): int
    {
        if ($this->maxFeatures === null || $this->maxFeatures === 'sqrt') {
            return (int) ceil(sqrt($nFeatures));
        }
        
        if ($this->maxFeatures === 'log2') {
            return max(1, (int) ceil(log($nFeatures, 2)));
        }
        
        if (is_int($this->maxFeatures)) {
            return min($nFeatures, $this->maxFeatures);
        }
        
        return $nFeatures;
    }

    /**
     * Demo function
     */
    public static function demo(): array
    {
        // Generate synthetic data
        mt_srand(42);
        $nSamples = 50;
        
        $X = [];
        $y = [];
        
        for ($i = 0; $i < $nSamples; $i++) {
            // Features
            $experience = mt_rand(0, 150) / 10;  // 0-15 years
            $projects = mt_rand(0, 10);
            $techMatch = mt_rand(2, 10);
            $mainSkills = mt_rand(1, 6);
            $subSkills = mt_rand(0, 5);
            $certs = mt_rand(0, 5);
            $education = mt_rand(3, 10);
            $cvQuality = mt_rand(2, 10);
            $softSkills = mt_rand(0, 6);
            $portfolio = mt_rand(0, 5);
            
            $X[] = [
                $experience, $projects, $techMatch,
                $mainSkills, $subSkills, $certs,
                $education, $cvQuality, $softSkills, $portfolio
            ];
            
            // Target: weighted sum + noise
            $score = 0.25 * $experience * 2 +
                     0.15 * $projects * 3 +
                     0.10 * $techMatch * 2 +
                     0.15 * $mainSkills * 5 +
                     0.10 * $subSkills * 4 +
                     0.05 * $certs * 4 +
                     0.05 * $education +
                     0.05 * $cvQuality +
                     0.05 * $softSkills * 3 +
                     0.05 * $portfolio * 4;
            
            $score += (mt_rand(-50, 50) / 10); // Add noise
            $y[] = max(0, min(100, $score));
        }
        
        $featureNames = [
            'experience_years', 'projects_count', 'tech_match_count',
            'main_skills_count', 'sub_skills_count', 'certifications_count',
            'education_score', 'cv_quality_score', 'soft_skills_count', 'portfolio_score'
        ];
        
        // Train Random Forest
        $rf = new self(
            nEstimators: 50,
            maxDepth: 8,
            minSamplesSplit: 3,
            minSamplesLeaf: 2
        );
        
        $rf->fit($X, $y, $featureNames);
        
        // Test prediction
        $testSample = [5, 4, 7, 4, 3, 2, 10, 8, 4, 3];
        $prediction = $rf->predictSingle($testSample);
        $predWithConf = $rf->predictWithConfidence($testSample);
        
        return [
            'metrics' => $rf->getMetrics(),
            'feature_importance' => $rf->getFeatureImportanceNamed(),
            'test_prediction' => [
                'features' => array_combine($featureNames, $testSample),
                'prediction' => round($prediction, 2),
                'confidence_interval' => $predWithConf,
            ],
        ];
    }
}
