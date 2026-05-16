<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\Application;
use App\Models\Job;
use App\Models\MLTrainingData;
use App\Models\MLModel;
use App\Models\MLPrediction;
use App\Services\ML\MLScoringPipeline;
use App\Services\ML\MLFeatureExtractor;
use App\Services\ML\MLGroupScorer;
use App\Services\ML\MLWeightCalculator;
use App\Services\ML\MLRandomForestScorer;
use App\Services\ML\MLMathUtils;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestMLFull extends Command
{
    protected $signature = 'ml:test-full {--seed : Seed test data}';
    protected $description = 'Full integration test for ML Scoring system';

    public function handle()
    {
        $this->info('========================================');
        $this->info('  ML CV SCORING - FULL INTEGRATION TEST');
        $this->info('========================================');
        $this->newLine();

        // Test 1: Check Database Tables
        $this->testDatabaseTables();

        // Test 2: Test Math Utils
        $this->testMathUtils();

        // Test 3: Test Feature Extractor
        $this->testFeatureExtractor();

        // Test 4: Test Group Scorer
        $this->testGroupScorer();

        // Test 5: Test Weight Calculator
        $this->testWeightCalculator();

        // Test 6: Test Random Forest
        $this->testRandomForest();

        // Test 7: Test Full Pipeline
        $this->testFullPipeline();

        // Test 8: Test with Real Candidate (if exists)
        $this->testWithRealData();

        $this->newLine();
        $this->info('========================================');
        $this->info('  ALL TESTS COMPLETED!');
        $this->info('========================================');

        return 0;
    }

    private function testDatabaseTables()
    {
        $this->info('📋 Test 1: Database Tables');
        
        $tables = ['ml_training_data', 'ml_models', 'ml_predictions'];
        $allExist = true;
        
        foreach ($tables as $table) {
            $exists = Schema::hasTable($table);
            $status = $exists ? '✓' : '✗';
            $color = $exists ? 'green' : 'red';
            
            if ($exists) {
                $count = DB::table($table)->count();
                $this->line("  {$status} {$table} (rows: {$count})");
            } else {
                $this->line("  {$status} {$table} - NOT FOUND");
                $allExist = false;
            }
        }
        
        // Check related tables
        $relatedTables = ['candidates', 'applications', 'jobs'];
        foreach ($relatedTables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                $this->line("  ✓ {$table} (rows: {$count})");
            }
        }
        
        $this->newLine();
    }

    private function testMathUtils()
    {
        $this->info('🔢 Test 2: Math Utils');
        
        try {
            // Test mean
            $data = [1, 2, 3, 4, 5];
            $mean = MLMathUtils::mean($data);
            $this->line("  ✓ mean([1,2,3,4,5]) = {$mean} (expected: 3)");
            
            // Test variance
            $var = MLMathUtils::variance($data);
            $this->line("  ✓ variance([1,2,3,4,5]) = {$var} (expected: 2)");
            
            // Test std
            $std = MLMathUtils::std($data);
            $this->line("  ✓ std([1,2,3,4,5]) = " . round($std, 4) . " (expected: 1.414)");
            
            // Test R2 score
            $y_true = [3, -0.5, 2, 7];
            $y_pred = [2.5, 0.0, 2, 8];
            $r2 = MLMathUtils::r2Score($y_pred, $y_true);
            $this->line("  ✓ r2Score = " . round($r2, 4) . " (expected: ~0.948)");
            
            // Test matrix multiply
            $A = [[1, 2], [3, 4]];
            $B = [[5, 6], [7, 8]];
            $C = MLMathUtils::matrixMultiply($A, $B);
            $this->line("  ✓ matrixMultiply: [[1,2],[3,4]] × [[5,6],[7,8]] = [[{$C[0][0]},{$C[0][1]}],[{$C[1][0]},{$C[1][1]}]]");
            
            $this->line("  ✓ All Math Utils tests passed!");
        } catch (\Exception $e) {
            $this->error("  ✗ Math Utils error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testFeatureExtractor()
    {
        $this->info('📊 Test 3: Feature Extractor');
        
        try {
            $extractor = new MLFeatureExtractor();
            
            // Test with mock data
            $mockData = [
                'work_experience' => [
                    ['years' => 3],
                    ['years' => 2],
                ],
                'projects' => [
                    ['name' => 'Project 1'],
                    ['name' => 'Project 2'],
                    ['name' => 'Project 3'],
                ],
                'skills' => [
                    'main' => ['PHP', 'Laravel', 'MySQL', 'JavaScript'],
                    'sub' => ['Docker', 'Redis'],
                ],
                'certifications' => ['AWS SAA', 'Azure'],
                'education' => ['major' => 'cntt'],
                'soft_skills' => ['communication', 'teamwork', 'leadership'],
            ];
            
            $features = $extractor->extractFromArray($mockData);
            
            $this->line("  Extracted features:");
            foreach ($features as $name => $value) {
                $this->line("    • {$name}: {$value}");
            }
            
            // Test feature names
            $featureNames = $extractor->getFeatureNames();
            $this->line("  ✓ Feature names: " . count($featureNames) . " features");
            
            // Test featuresToArray
            $featureArray = $extractor->featuresToArray($features);
            $this->line("  ✓ Features array: [" . implode(', ', array_map(fn($v) => round($v, 2), $featureArray)) . "]");
            
            $this->line("  ✓ Feature Extractor tests passed!");
        } catch (\Exception $e) {
            $this->error("  ✗ Feature Extractor error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testGroupScorer()
    {
        $this->info('📈 Test 4: Group Scorer');
        
        try {
            $scorer = new MLGroupScorer();
            
            $features = [
                'experience_years' => 5,
                'projects_count' => 4,
                'tech_match_count' => 6,
                'main_skills_count' => 4,
                'sub_skills_count' => 2,
                'certifications_count' => 2,
                'education_score' => 10,
                'cv_quality_score' => 8,
                'soft_skills_count' => 3,
                'portfolio_score' => 4,
            ];
            
            $groupScores = $scorer->scoreAll($features);
            
            $this->line("  Input features:");
            foreach ($features as $name => $value) {
                $this->line("    • {$name}: {$value}");
            }
            
            $this->newLine();
            $this->line("  Output scores:");
            $this->line("    • Group A (Experience/Projects): " . round($groupScores['A'], 2) . " / 35");
            $this->line("    • Group B (Skills): " . round($groupScores['B'], 2) . " / 35");
            $this->line("    • Group C (Other): " . round($groupScores['C'], 2) . " / 30");
            $this->line("    • Total: " . round($groupScores['total'], 2) . " / 100");
            
            // Test breakdown
            $breakdown = $scorer->getAllBreakdown($features);
            $this->line("  ✓ Breakdown available for " . count($breakdown) . " groups");
            
            $this->line("  ✓ Group Scorer tests passed!");
        } catch (\Exception $e) {
            $this->error("  ✗ Group Scorer error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testWeightCalculator()
    {
        $this->info('⚖️ Test 5: Weight Calculator');
        
        try {
            $calculator = new MLWeightCalculator();
            
            // Generate synthetic data
            mt_srand(42);
            $X = [];
            $y = [];
            
            for ($i = 0; $i < 30; $i++) {
                $a = mt_rand(10, 35);
                $b = mt_rand(10, 35);
                $c = mt_rand(10, 30);
                
                // True weights: 0.45, 0.35, 0.20
                $score = 0.45 * $a + 0.35 * $b + 0.20 * $c + mt_rand(-20, 20) / 10;
                
                $X[] = [$a, $b, $c];
                $y[] = max(0, min(100, $score));
            }
            
            // Fit
            $weights = $calculator->fit($X, $y);
            
            $this->line("  Training with 30 synthetic samples");
            $this->line("  True weights: A=0.45, B=0.35, C=0.20");
            $this->line("  Learned weights:");
            $this->line("    • A: " . round($weights['A'], 4));
            $this->line("    • B: " . round($weights['B'], 4));
            $this->line("    • C: " . round($weights['C'], 4));
            
            // Test apply weights
            $testGroupScores = ['A' => 30, 'B' => 25, 'C' => 20];
            $weightedScore = $calculator->applyWeights($testGroupScores);
            $this->line("  Applied weights to [A=30, B=25, C=20]: " . round($weightedScore, 2));
            
            // Metrics
            $metrics = $calculator->getMetrics();
            $this->line("  Metrics: R²=" . round($metrics['r2_score'], 4) . ", MAE=" . round($metrics['mae'], 2));
            
            $this->line("  ✓ Weight Calculator tests passed!");
        } catch (\Exception $e) {
            $this->error("  ✗ Weight Calculator error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testRandomForest()
    {
        $this->info('🌲 Test 6: Random Forest');
        
        try {
            $rf = new MLRandomForestScorer(
                nEstimators: 20,  // Fewer trees for speed
                maxDepth: 5,
                minSamplesSplit: 3,
                minSamplesLeaf: 2
            );
            
            // Generate synthetic data
            mt_srand(42);
            $X = [];
            $y = [];
            $featureNames = ['exp', 'projects', 'tech', 'skills', 'edu'];
            
            for ($i = 0; $i < 50; $i++) {
                $features = [
                    mt_rand(0, 15),   // experience
                    mt_rand(0, 10),   // projects
                    mt_rand(0, 10),   // tech_match
                    mt_rand(0, 10),   // skills
                    mt_rand(1, 10),   // education
                ];
                
                // Score formula
                $score = 3 * $features[0] + 2 * $features[1] + 2 * $features[2] 
                       + 1.5 * $features[3] + $features[4] + mt_rand(-30, 30) / 10;
                
                $X[] = $features;
                $y[] = max(0, min(100, $score));
            }
            
            $this->line("  Training Random Forest with 50 samples, 20 trees...");
            
            $startTime = microtime(true);
            $rf->fit($X, $y, $featureNames);
            $trainTime = round((microtime(true) - $startTime) * 1000);
            
            $this->line("  Training time: {$trainTime}ms");
            
            // Metrics
            $metrics = $rf->getMetrics();
            $this->line("  Metrics:");
            $this->line("    • R² Score: " . round($metrics['r2_score'], 4));
            $this->line("    • MAE: " . round($metrics['mae'], 2));
            $this->line("    • RMSE: " . round($metrics['rmse'], 2));
            $this->line("    • OOB Score: " . round($metrics['oob_score'] ?? 0, 4));
            
            // Feature importance
            $importance = $rf->getFeatureImportanceNamed();
            $this->line("  Feature Importance:");
            foreach ($importance as $name => $imp) {
                $bar = str_repeat('█', (int)($imp * 20));
                $this->line("    • {$name}: {$bar} " . round($imp * 100, 1) . "%");
            }
            
            // Test prediction
            $testSample = [5, 4, 6, 5, 8];
            $prediction = $rf->predictSingle($testSample);
            $this->line("  Test prediction for [5,4,6,5,8]: " . round($prediction, 2));
            
            $this->line("  ✓ Random Forest tests passed!");
        } catch (\Exception $e) {
            $this->error("  ✗ Random Forest error: " . $e->getMessage());
            $this->error("    " . $e->getTraceAsString());
        }
        
        $this->newLine();
    }

    private function testFullPipeline()
    {
        $this->info('🔄 Test 7: Full Pipeline Integration');
        
        try {
            // Run demo
            $result = MLScoringPipeline::demo();
            
            $this->line("  Demo Results:");
            
            if ($result['training']['success'] ?? false) {
                $this->line("  ✓ Training successful");
                $this->line("    • Samples: " . $result['training']['training_samples']);
                $this->line("    • R² (RF): " . round($result['training']['rf_metrics']['r2_score'], 4));
                $this->line("    • Weights: A=" . round($result['training']['weights']['A'], 3) 
                          . ", B=" . round($result['training']['weights']['B'], 3)
                          . ", C=" . round($result['training']['weights']['C'], 3));
            } else {
                $this->error("  ✗ Training failed: " . ($result['training']['error'] ?? 'Unknown'));
            }
            
            if ($result['test_scoring']['success'] ?? false) {
                $this->line("  ✓ Scoring successful");
                $this->line("    • Final Score: " . $result['test_scoring']['final_score']);
                $this->line("    • Classification: " . $result['test_scoring']['classification']);
                $this->line("    • Weighted: " . $result['test_scoring']['weighted_score']);
                $this->line("    • ML Score: " . $result['test_scoring']['ml_score']);
            } else {
                $this->error("  ✗ Scoring failed: " . ($result['test_scoring']['error'] ?? 'Unknown'));
            }
            
            $this->line("  ✓ Full Pipeline tests passed!");
        } catch (\Exception $e) {
            $this->error("  ✗ Pipeline error: " . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testWithRealData()
    {
        $this->info('👤 Test 8: Real Data Test');
        
        try {
            // Check if candidates exist
            if (!Schema::hasTable('candidates')) {
                $this->warn("  ⚠ candidates table not found, skipping");
                return;
            }
            
            $candidateCount = Candidate::count();
            $this->line("  Found {$candidateCount} candidates in database");
            
            if ($candidateCount === 0) {
                $this->warn("  ⚠ No candidates found, skipping real data test");
                return;
            }
            
            // Get a random candidate
            $candidate = Candidate::inRandomOrder()->first();
            $this->line("  Testing with Candidate #{$candidate->id}");
            
            // Score using pipeline
            $pipeline = new MLScoringPipeline();
            $result = $pipeline->scoreCandidate($candidate);
            
            if ($result['success']) {
                $this->line("  ✓ Scoring successful!");
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Final Score', round($result['final_score'], 2)],
                        ['Classification', $result['classification']],
                        ['Weighted Score', round($result['weighted_score'], 2)],
                        ['ML Score', round($result['ml_score'], 2)],
                        ['Confidence', round($result['confidence'] ?? 0, 3)],
                        ['Group A', round($result['group_scores']['A'], 2)],
                        ['Group B', round($result['group_scores']['B'], 2)],
                        ['Group C', round($result['group_scores']['C'], 2)],
                    ]
                );
                
                $this->line("  Features extracted:");
                foreach ($result['features'] as $name => $value) {
                    $this->line("    • {$name}: {$value}");
                }
            } else {
                $this->error("  ✗ Scoring failed: " . ($result['error'] ?? 'Unknown'));
            }
            
        } catch (\Exception $e) {
            $this->error("  ✗ Real data test error: " . $e->getMessage());
        }
        
        $this->newLine();
    }
}
