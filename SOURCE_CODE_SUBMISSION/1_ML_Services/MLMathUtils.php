<?php

namespace App\Services\ML;

/**
 * ML Math Utilities
 * 
 * Cung cấp các hàm toán học cần thiết cho Machine Learning:
 * - Matrix operations (multiply, transpose, inverse)
 * - Statistical functions (mean, variance, std, correlation)
 * - Normalization (StandardScaler, MinMaxScaler)
 * 
 * @author IT Solo Leveling Team
 */
class MLMathUtils
{
    /**
     * Tính mean (trung bình) của array
     */
    public static function mean(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }
        return array_sum($values) / count($values);
    }

    /**
     * Tính variance (phương sai) của array
     */
    public static function variance(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }
        
        $mean = self::mean($values);
        $sumSquares = 0;
        
        foreach ($values as $value) {
            $sumSquares += pow($value - $mean, 2);
        }
        
        return $sumSquares / (count($values) - 1); // Sample variance
    }

    /**
     * Tính standard deviation (độ lệch chuẩn)
     */
    public static function std(array $values): float
    {
        return sqrt(self::variance($values));
    }

    /**
     * Tính Sum of Squared Errors
     */
    public static function sumSquaredErrors(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }
        
        $mean = self::mean($values);
        $sse = 0;
        
        foreach ($values as $value) {
            $sse += pow($value - $mean, 2);
        }
        
        return $sse;
    }

    /**
     * Tính Mean Squared Error giữa predicted và actual
     */
    public static function mse(array $predicted, array $actual): float
    {
        if (count($predicted) !== count($actual) || empty($predicted)) {
            return 0.0;
        }
        
        $sum = 0;
        for ($i = 0; $i < count($predicted); $i++) {
            $sum += pow($predicted[$i] - $actual[$i], 2);
        }
        
        return $sum / count($predicted);
    }

    /**
     * Tính Root Mean Squared Error
     */
    public static function rmse(array $predicted, array $actual): float
    {
        return sqrt(self::mse($predicted, $actual));
    }

    /**
     * Tính Mean Absolute Error
     */
    public static function mae(array $predicted, array $actual): float
    {
        if (count($predicted) !== count($actual) || empty($predicted)) {
            return 0.0;
        }
        
        $sum = 0;
        for ($i = 0; $i < count($predicted); $i++) {
            $sum += abs($predicted[$i] - $actual[$i]);
        }
        
        return $sum / count($predicted);
    }

    /**
     * Tính R² Score (Coefficient of Determination)
     */
    public static function r2Score(array $predicted, array $actual): float
    {
        if (count($predicted) !== count($actual) || empty($predicted)) {
            return 0.0;
        }
        
        $actualMean = self::mean($actual);
        
        $ssRes = 0; // Residual sum of squares
        $ssTot = 0; // Total sum of squares
        
        for ($i = 0; $i < count($actual); $i++) {
            $ssRes += pow($actual[$i] - $predicted[$i], 2);
            $ssTot += pow($actual[$i] - $actualMean, 2);
        }
        
        if ($ssTot == 0) {
            return 0.0;
        }
        
        return 1 - ($ssRes / $ssTot);
    }

    /**
     * Nhân 2 ma trận
     * 
     * @param array $a Ma trận A (m x n)
     * @param array $b Ma trận B (n x p)
     * @return array Ma trận kết quả (m x p)
     */
    public static function matrixMultiply(array $a, array $b): array
    {
        $m = count($a);
        $n = count($a[0]);
        $p = count($b[0]);
        
        $result = [];
        
        for ($i = 0; $i < $m; $i++) {
            $result[$i] = [];
            for ($j = 0; $j < $p; $j++) {
                $sum = 0;
                for ($k = 0; $k < $n; $k++) {
                    $sum += $a[$i][$k] * $b[$k][$j];
                }
                $result[$i][$j] = $sum;
            }
        }
        
        return $result;
    }

    /**
     * Chuyển vị ma trận
     */
    public static function matrixTranspose(array $matrix): array
    {
        if (empty($matrix)) {
            return [];
        }
        
        $rows = count($matrix);
        $cols = count($matrix[0]);
        
        $result = [];
        for ($j = 0; $j < $cols; $j++) {
            $result[$j] = [];
            for ($i = 0; $i < $rows; $i++) {
                $result[$j][$i] = $matrix[$i][$j];
            }
        }
        
        return $result;
    }

    /**
     * Tính ma trận nghịch đảo (sử dụng Gauss-Jordan elimination)
     */
    public static function matrixInverse(array $matrix): array
    {
        $n = count($matrix);
        
        // Tạo augmented matrix [A|I]
        $augmented = [];
        for ($i = 0; $i < $n; $i++) {
            $augmented[$i] = array_merge($matrix[$i], array_fill(0, $n, 0));
            $augmented[$i][$n + $i] = 1;
        }
        
        // Gauss-Jordan elimination
        for ($i = 0; $i < $n; $i++) {
            // Tìm pivot
            $maxRow = $i;
            for ($k = $i + 1; $k < $n; $k++) {
                if (abs($augmented[$k][$i]) > abs($augmented[$maxRow][$i])) {
                    $maxRow = $k;
                }
            }
            
            // Swap rows
            $temp = $augmented[$i];
            $augmented[$i] = $augmented[$maxRow];
            $augmented[$maxRow] = $temp;
            
            // Check for singular matrix
            if (abs($augmented[$i][$i]) < 1e-10) {
                // Add regularization (pseudo-inverse)
                $augmented[$i][$i] = 1e-6;
            }
            
            // Scale pivot row
            $scale = $augmented[$i][$i];
            for ($j = 0; $j < 2 * $n; $j++) {
                $augmented[$i][$j] /= $scale;
            }
            
            // Eliminate column
            for ($k = 0; $k < $n; $k++) {
                if ($k != $i) {
                    $factor = $augmented[$k][$i];
                    for ($j = 0; $j < 2 * $n; $j++) {
                        $augmented[$k][$j] -= $factor * $augmented[$i][$j];
                    }
                }
            }
        }
        
        // Extract inverse
        $inverse = [];
        for ($i = 0; $i < $n; $i++) {
            $inverse[$i] = array_slice($augmented[$i], $n);
        }
        
        return $inverse;
    }

    /**
     * Tạo ma trận đơn vị
     */
    public static function identityMatrix(int $n): array
    {
        $result = [];
        for ($i = 0; $i < $n; $i++) {
            $result[$i] = array_fill(0, $n, 0);
            $result[$i][$i] = 1;
        }
        return $result;
    }

    /**
     * Cộng 2 ma trận
     */
    public static function matrixAdd(array $a, array $b): array
    {
        $m = count($a);
        $n = count($a[0]);
        
        $result = [];
        for ($i = 0; $i < $m; $i++) {
            $result[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                $result[$i][$j] = $a[$i][$j] + $b[$i][$j];
            }
        }
        
        return $result;
    }

    /**
     * Nhân ma trận với scalar
     */
    public static function matrixScalarMultiply(array $matrix, float $scalar): array
    {
        $m = count($matrix);
        $n = count($matrix[0]);
        
        $result = [];
        for ($i = 0; $i < $m; $i++) {
            $result[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                $result[$i][$j] = $matrix[$i][$j] * $scalar;
            }
        }
        
        return $result;
    }

    /**
     * Nhân ma trận với vector (Ax)
     */
    public static function matrixVectorMultiply(array $matrix, array $vector): array
    {
        $m = count($matrix);
        $n = count($matrix[0]);
        
        $result = [];
        for ($i = 0; $i < $m; $i++) {
            $sum = 0;
            for ($j = 0; $j < $n; $j++) {
                $sum += $matrix[$i][$j] * $vector[$j];
            }
            $result[$i] = $sum;
        }
        
        return $result;
    }

    /**
     * StandardScaler: (x - mean) / std
     * 
     * @param array $data 2D array [samples x features]
     * @return array ['scaled' => data, 'means' => [...], 'stds' => [...]]
     */
    public static function standardScale(array $data): array
    {
        if (empty($data)) {
            return ['scaled' => [], 'means' => [], 'stds' => []];
        }
        
        $nSamples = count($data);
        $nFeatures = count($data[0]);
        
        // Tính mean và std cho từng feature
        $means = [];
        $stds = [];
        
        for ($j = 0; $j < $nFeatures; $j++) {
            $column = array_column($data, $j);
            $means[$j] = self::mean($column);
            $stds[$j] = self::std($column);
            
            // Tránh chia cho 0
            if ($stds[$j] < 1e-10) {
                $stds[$j] = 1.0;
            }
        }
        
        // Scale data
        $scaled = [];
        for ($i = 0; $i < $nSamples; $i++) {
            $scaled[$i] = [];
            for ($j = 0; $j < $nFeatures; $j++) {
                $scaled[$i][$j] = ($data[$i][$j] - $means[$j]) / $stds[$j];
            }
        }
        
        return [
            'scaled' => $scaled,
            'means' => $means,
            'stds' => $stds
        ];
    }

    /**
     * Áp dụng StandardScaler với means và stds đã có
     */
    public static function applyStandardScale(array $data, array $means, array $stds): array
    {
        $scaled = [];
        foreach ($data as $i => $row) {
            $scaled[$i] = [];
            foreach ($row as $j => $value) {
                $scaled[$i][$j] = ($value - $means[$j]) / $stds[$j];
            }
        }
        return $scaled;
    }

    /**
     * MinMaxScaler: (x - min) / (max - min)
     */
    public static function minMaxScale(array $data): array
    {
        if (empty($data)) {
            return ['scaled' => [], 'mins' => [], 'maxs' => []];
        }
        
        $nSamples = count($data);
        $nFeatures = count($data[0]);
        
        $mins = [];
        $maxs = [];
        
        for ($j = 0; $j < $nFeatures; $j++) {
            $column = array_column($data, $j);
            $mins[$j] = min($column);
            $maxs[$j] = max($column);
            
            // Tránh chia cho 0
            if ($maxs[$j] - $mins[$j] < 1e-10) {
                $maxs[$j] = $mins[$j] + 1;
            }
        }
        
        $scaled = [];
        for ($i = 0; $i < $nSamples; $i++) {
            $scaled[$i] = [];
            for ($j = 0; $j < $nFeatures; $j++) {
                $scaled[$i][$j] = ($data[$i][$j] - $mins[$j]) / ($maxs[$j] - $mins[$j]);
            }
        }
        
        return [
            'scaled' => $scaled,
            'mins' => $mins,
            'maxs' => $maxs
        ];
    }

    /**
     * Tính correlation giữa 2 arrays
     */
    public static function correlation(array $x, array $y): float
    {
        if (count($x) !== count($y) || count($x) < 2) {
            return 0.0;
        }
        
        $n = count($x);
        $meanX = self::mean($x);
        $meanY = self::mean($y);
        
        $numerator = 0;
        $denomX = 0;
        $denomY = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            $numerator += $dx * $dy;
            $denomX += $dx * $dx;
            $denomY += $dy * $dy;
        }
        
        $denominator = sqrt($denomX * $denomY);
        
        if ($denominator < 1e-10) {
            return 0.0;
        }
        
        return $numerator / $denominator;
    }

    /**
     * Lấy mẫu ngẫu nhiên với hoàn lại (Bootstrap sampling)
     * 
     * @param array $indices Mảng indices [0, 1, 2, ..., n-1]
     * @param int $sampleSize Số lượng samples cần lấy
     * @return array Bootstrap sample indices
     */
    public static function bootstrapSample(array $indices, int $sampleSize): array
    {
        $n = count($indices);
        $sample = [];
        
        for ($i = 0; $i < $sampleSize; $i++) {
            $sample[] = $indices[mt_rand(0, $n - 1)];
        }
        
        return $sample;
    }

    /**
     * Lấy random subset của features
     * 
     * @param int $nFeatures Tổng số features
     * @param int $maxFeatures Số features cần lấy
     * @return array Indices của features được chọn
     */
    public static function randomFeatureSubset(int $nFeatures, int $maxFeatures): array
    {
        $indices = range(0, $nFeatures - 1);
        shuffle($indices);
        return array_slice($indices, 0, $maxFeatures);
    }

    /**
     * Clip value vào khoảng [min, max]
     */
    public static function clip(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    /**
     * Softmax function
     */
    public static function softmax(array $values): array
    {
        $maxVal = max($values);
        $expValues = array_map(fn($v) => exp($v - $maxVal), $values);
        $sumExp = array_sum($expValues);
        
        return array_map(fn($v) => $v / $sumExp, $expValues);
    }

    /**
     * Argmax - trả về index của giá trị lớn nhất
     */
    public static function argmax(array $values): int
    {
        $maxIndex = 0;
        $maxValue = $values[0];
        
        foreach ($values as $i => $value) {
            if ($value > $maxValue) {
                $maxValue = $value;
                $maxIndex = $i;
            }
        }
        
        return $maxIndex;
    }

    /**
     * Unique values với count
     */
    public static function valueCounts(array $values): array
    {
        $counts = [];
        foreach ($values as $value) {
            $key = (string) $value;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        arsort($counts);
        return $counts;
    }

    /**
     * Weighted mean
     */
    public static function weightedMean(array $values, array $weights): float
    {
        if (empty($values) || count($values) !== count($weights)) {
            return 0.0;
        }
        
        $sum = 0;
        $weightSum = 0;
        
        for ($i = 0; $i < count($values); $i++) {
            $sum += $values[$i] * $weights[$i];
            $weightSum += $weights[$i];
        }
        
        return $weightSum > 0 ? $sum / $weightSum : 0.0;
    }

    /**
     * Percentile
     */
    public static function percentile(array $values, float $percentile): float
    {
        if (empty($values)) {
            return 0.0;
        }
        
        sort($values);
        $n = count($values);
        $index = ($percentile / 100) * ($n - 1);
        
        $lower = (int) floor($index);
        $upper = (int) ceil($index);
        $fraction = $index - $lower;
        
        if ($upper >= $n) {
            return $values[$n - 1];
        }
        
        return $values[$lower] + $fraction * ($values[$upper] - $values[$lower]);
    }

    /**
     * Median
     */
    public static function median(array $values): float
    {
        return self::percentile($values, 50);
    }

    /**
     * Gini impurity cho classification
     */
    public static function giniImpurity(array $labels): float
    {
        if (empty($labels)) {
            return 0.0;
        }
        
        $counts = self::valueCounts($labels);
        $n = count($labels);
        $gini = 1.0;
        
        foreach ($counts as $count) {
            $p = $count / $n;
            $gini -= $p * $p;
        }
        
        return $gini;
    }

    /**
     * Entropy cho classification
     */
    public static function entropy(array $labels): float
    {
        if (empty($labels)) {
            return 0.0;
        }
        
        $counts = self::valueCounts($labels);
        $n = count($labels);
        $entropy = 0.0;
        
        foreach ($counts as $count) {
            $p = $count / $n;
            if ($p > 0) {
                $entropy -= $p * log($p);
            }
        }
        
        return $entropy;
    }
}
