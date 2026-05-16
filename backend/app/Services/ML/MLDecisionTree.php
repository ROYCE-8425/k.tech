<?php

namespace App\Services\ML;

/**
 * ML Decision Tree (Regressor)
 * 
 * Implementation của Decision Tree cho Regression task.
 * Được sử dụng làm base learner cho Random Forest.
 * 
 * Thuật toán CART (Classification and Regression Trees):
 * 1. Tại mỗi node, tìm split tốt nhất (minimize MSE)
 * 2. Recursive split cho đến khi đạt stopping criteria
 * 3. Leaf nodes chứa mean của targets
 * 
 * @author IT Solo Leveling Team
 */
class MLDecisionTree
{
    /**
     * Cấu hình hyperparameters
     */
    private int $maxDepth;
    private int $minSamplesSplit;
    private int $minSamplesLeaf;
    private ?int $maxFeatures;
    private int $randomState;

    /**
     * Tree structure (root node)
     */
    private ?array $tree = null;

    /**
     * Feature names
     */
    private array $featureNames = [];

    /**
     * Feature importance (accumulated during fit)
     */
    private array $featureImportance = [];

    /**
     * Total samples (for importance calculation)
     */
    private int $nSamples = 0;

    /**
     * Constructor
     * 
     * @param int $maxDepth Độ sâu tối đa của tree
     * @param int $minSamplesSplit Số samples tối thiểu để split
     * @param int $minSamplesLeaf Số samples tối thiểu ở leaf
     * @param int|null $maxFeatures Số features xét mỗi split (null = all)
     * @param int $randomState Random seed
     */
    public function __construct(
        int $maxDepth = 10,
        int $minSamplesSplit = 2,
        int $minSamplesLeaf = 1,
        ?int $maxFeatures = null,
        int $randomState = 42
    ) {
        $this->maxDepth = $maxDepth;
        $this->minSamplesSplit = $minSamplesSplit;
        $this->minSamplesLeaf = $minSamplesLeaf;
        $this->maxFeatures = $maxFeatures;
        $this->randomState = $randomState;
    }

    /**
     * Fit tree với training data
     * 
     * @param array $X Features 2D array [n_samples x n_features]
     * @param array $y Target 1D array [n_samples]
     * @param array $featureNames Tên các features (optional)
     * @return self
     */
    public function fit(array $X, array $y, array $featureNames = []): self
    {
        $this->nSamples = count($X);
        $nFeatures = count($X[0] ?? []);
        
        // Set feature names
        if (empty($featureNames)) {
            $this->featureNames = array_map(fn($i) => "feature_{$i}", range(0, $nFeatures - 1));
        } else {
            $this->featureNames = $featureNames;
        }
        
        // Initialize feature importance
        $this->featureImportance = array_fill(0, $nFeatures, 0.0);
        
        // Set random seed
        mt_srand($this->randomState);
        
        // Max features for random selection
        if ($this->maxFeatures === null) {
            $this->maxFeatures = $nFeatures;
        }
        
        // Build tree recursively
        $indices = range(0, $this->nSamples - 1);
        $this->tree = $this->buildTree($X, $y, $indices, depth: 0);
        
        // Normalize feature importance
        $totalImportance = array_sum($this->featureImportance);
        if ($totalImportance > 0) {
            $this->featureImportance = array_map(
                fn($imp) => $imp / $totalImportance,
                $this->featureImportance
            );
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
        if ($this->tree === null) {
            throw new \RuntimeException("Tree chưa được fit. Gọi fit() trước.");
        }
        
        return array_map(fn($sample) => $this->predictSingle($sample), $X);
    }

    /**
     * Predict cho một sample
     */
    public function predictSingle(array $sample): float
    {
        return $this->traverseTree($this->tree, $sample);
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
     * Lấy feature importance với tên
     * 
     * @return array [feature_name => importance]
     */
    public function getFeatureImportanceNamed(): array
    {
        $result = [];
        foreach ($this->featureImportance as $i => $importance) {
            $name = $this->featureNames[$i] ?? "feature_{$i}";
            $result[$name] = $importance;
        }
        arsort($result);
        return $result;
    }

    /**
     * Serialize tree để lưu trữ
     */
    public function serialize(): array
    {
        return [
            'tree' => $this->tree,
            'feature_names' => $this->featureNames,
            'feature_importance' => $this->featureImportance,
            'n_samples' => $this->nSamples,
            'params' => [
                'max_depth' => $this->maxDepth,
                'min_samples_split' => $this->minSamplesSplit,
                'min_samples_leaf' => $this->minSamplesLeaf,
                'max_features' => $this->maxFeatures,
                'random_state' => $this->randomState,
            ],
        ];
    }

    /**
     * Deserialize tree
     */
    public function deserialize(array $data): self
    {
        $this->tree = $data['tree'];
        $this->featureNames = $data['feature_names'];
        $this->featureImportance = $data['feature_importance'];
        $this->nSamples = $data['n_samples'];
        
        if (isset($data['params'])) {
            $this->maxDepth = $data['params']['max_depth'];
            $this->minSamplesSplit = $data['params']['min_samples_split'];
            $this->minSamplesLeaf = $data['params']['min_samples_leaf'];
            $this->maxFeatures = $data['params']['max_features'];
            $this->randomState = $data['params']['random_state'];
        }
        
        return $this;
    }

    // ========================================================================
    // PRIVATE METHODS
    // ========================================================================

    /**
     * Build tree recursively
     * 
     * @param array $X Full features array
     * @param array $y Full targets array
     * @param array $indices Indices of samples at this node
     * @param int $depth Current depth
     * @return array Node structure
     */
    private function buildTree(array $X, array $y, array $indices, int $depth): array
    {
        $nSamplesNode = count($indices);
        
        // Lấy targets của node này
        $yNode = array_map(fn($i) => $y[$i], $indices);
        $meanValue = MLMathUtils::mean($yNode);
        
        // Stopping criteria
        if ($depth >= $this->maxDepth ||
            $nSamplesNode < $this->minSamplesSplit ||
            $nSamplesNode < 2 * $this->minSamplesLeaf) {
            return $this->createLeafNode($meanValue, $nSamplesNode);
        }
        
        // Check if all y values are the same
        if (count(array_unique($yNode)) === 1) {
            return $this->createLeafNode($meanValue, $nSamplesNode);
        }
        
        // Find best split
        $bestSplit = $this->findBestSplit($X, $y, $indices);
        
        if ($bestSplit === null) {
            return $this->createLeafNode($meanValue, $nSamplesNode);
        }
        
        // Update feature importance
        $this->updateFeatureImportance(
            $bestSplit['feature'],
            $nSamplesNode,
            $bestSplit['mse_reduction']
        );
        
        // Split indices
        $leftIndices = [];
        $rightIndices = [];
        
        foreach ($indices as $i) {
            if ($X[$i][$bestSplit['feature']] <= $bestSplit['threshold']) {
                $leftIndices[] = $i;
            } else {
                $rightIndices[] = $i;
            }
        }
        
        // Check min_samples_leaf constraint
        if (count($leftIndices) < $this->minSamplesLeaf ||
            count($rightIndices) < $this->minSamplesLeaf) {
            return $this->createLeafNode($meanValue, $nSamplesNode);
        }
        
        // Build child nodes recursively
        $leftChild = $this->buildTree($X, $y, $leftIndices, $depth + 1);
        $rightChild = $this->buildTree($X, $y, $rightIndices, $depth + 1);
        
        return [
            'type' => 'split',
            'feature' => $bestSplit['feature'],
            'threshold' => $bestSplit['threshold'],
            'n_samples' => $nSamplesNode,
            'value' => $meanValue,
            'mse' => $bestSplit['mse'],
            'left' => $leftChild,
            'right' => $rightChild,
        ];
    }

    /**
     * Find best split for a node
     * 
     * @param array $X Full features
     * @param array $y Full targets
     * @param array $indices Indices at this node
     * @return array|null Best split info or null if no valid split
     */
    private function findBestSplit(array $X, array $y, array $indices): ?array
    {
        $nFeatures = count($X[0]);
        $nSamplesNode = count($indices);
        
        // Calculate MSE of current node
        $yNode = array_map(fn($i) => $y[$i], $indices);
        $currentMse = MLMathUtils::variance($yNode) * ($nSamplesNode - 1) / $nSamplesNode;
        
        if ($currentMse < 1e-10) {
            return null; // Pure node
        }
        
        $bestSplit = null;
        $bestMseReduction = 0;
        
        // Random feature selection (for Random Forest)
        $featureIndices = range(0, $nFeatures - 1);
        shuffle($featureIndices);
        $selectedFeatures = array_slice($featureIndices, 0, min($this->maxFeatures, $nFeatures));
        
        foreach ($selectedFeatures as $featureIdx) {
            // Get unique values for this feature
            $featureValues = array_map(fn($i) => $X[$i][$featureIdx], $indices);
            $uniqueValues = array_unique($featureValues);
            sort($uniqueValues);
            
            // Try thresholds between unique values
            for ($i = 0; $i < count($uniqueValues) - 1; $i++) {
                $threshold = ($uniqueValues[$i] + $uniqueValues[$i + 1]) / 2;
                
                // Split samples
                $leftY = [];
                $rightY = [];
                
                foreach ($indices as $idx) {
                    if ($X[$idx][$featureIdx] <= $threshold) {
                        $leftY[] = $y[$idx];
                    } else {
                        $rightY[] = $y[$idx];
                    }
                }
                
                // Check min_samples_leaf
                if (count($leftY) < $this->minSamplesLeaf ||
                    count($rightY) < $this->minSamplesLeaf) {
                    continue;
                }
                
                // Calculate weighted MSE
                $leftMse = $this->calculateMse($leftY);
                $rightMse = $this->calculateMse($rightY);
                
                $weightedMse = (count($leftY) * $leftMse + count($rightY) * $rightMse) / $nSamplesNode;
                $mseReduction = $currentMse - $weightedMse;
                
                if ($mseReduction > $bestMseReduction) {
                    $bestMseReduction = $mseReduction;
                    $bestSplit = [
                        'feature' => $featureIdx,
                        'threshold' => $threshold,
                        'mse' => $weightedMse,
                        'mse_reduction' => $mseReduction,
                    ];
                }
            }
        }
        
        return $bestSplit;
    }

    /**
     * Calculate MSE for a set of values
     */
    private function calculateMse(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }
        
        $mean = MLMathUtils::mean($values);
        $sse = 0;
        
        foreach ($values as $v) {
            $sse += pow($v - $mean, 2);
        }
        
        return $sse / count($values);
    }

    /**
     * Create leaf node
     */
    private function createLeafNode(float $value, int $nSamples): array
    {
        return [
            'type' => 'leaf',
            'value' => $value,
            'n_samples' => $nSamples,
        ];
    }

    /**
     * Update feature importance
     */
    private function updateFeatureImportance(int $featureIdx, int $nSamples, float $mseReduction): void
    {
        // Weighted by number of samples reaching this node
        $weight = $nSamples / $this->nSamples;
        $this->featureImportance[$featureIdx] += $weight * $mseReduction;
    }

    /**
     * Traverse tree to get prediction
     */
    private function traverseTree(array $node, array $sample): float
    {
        if ($node['type'] === 'leaf') {
            return $node['value'];
        }
        
        $featureValue = $sample[$node['feature']];
        
        if ($featureValue <= $node['threshold']) {
            return $this->traverseTree($node['left'], $sample);
        } else {
            return $this->traverseTree($node['right'], $sample);
        }
    }

    /**
     * Get tree depth
     */
    public function getDepth(): int
    {
        return $this->calculateDepth($this->tree);
    }

    private function calculateDepth(?array $node): int
    {
        if ($node === null || $node['type'] === 'leaf') {
            return 0;
        }
        
        return 1 + max(
            $this->calculateDepth($node['left']),
            $this->calculateDepth($node['right'])
        );
    }

    /**
     * Get number of leaves
     */
    public function getNumLeaves(): int
    {
        return $this->countLeaves($this->tree);
    }

    private function countLeaves(?array $node): int
    {
        if ($node === null) {
            return 0;
        }
        
        if ($node['type'] === 'leaf') {
            return 1;
        }
        
        return $this->countLeaves($node['left']) + $this->countLeaves($node['right']);
    }
}
