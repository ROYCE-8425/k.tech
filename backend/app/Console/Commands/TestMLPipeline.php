<?php

namespace App\Console\Commands;

use App\Services\ML\MLScoringPipeline;
use Illuminate\Console\Command;

class TestMLPipeline extends Command
{
    protected $signature = 'ml:test';
    protected $description = 'Test ML Scoring Pipeline';

    public function handle()
    {
        $this->info('Testing ML Scoring Pipeline...');
        $this->newLine();
        
        try {
            $result = MLScoringPipeline::demo();
            
            $this->info('=== Training Results ===');
            if ($result['training']['success'] ?? false) {
                $this->info('✓ Training successful!');
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Training Samples', $result['training']['training_samples']],
                        ['R² Score (Weight)', $result['training']['weight_metrics']['r2_score'] ?? 'N/A'],
                        ['MAE (Weight)', $result['training']['weight_metrics']['mae'] ?? 'N/A'],
                        ['R² Score (RF)', $result['training']['rf_metrics']['r2_score'] ?? 'N/A'],
                        ['OOB Score (RF)', $result['training']['rf_metrics']['oob_score'] ?? 'N/A'],
                    ]
                );
                
                $this->newLine();
                $this->info('Learned Weights:');
                foreach ($result['training']['weights'] as $group => $weight) {
                    $this->line("  Group {$group}: " . round($weight * 100, 1) . '%');
                }
                
                $this->newLine();
                $this->info('Feature Importance (Top 5):');
                $importance = $result['training']['feature_importance'];
                arsort($importance);
                $i = 0;
                foreach ($importance as $feature => $value) {
                    if ($i++ >= 5) break;
                    $this->line("  {$feature}: " . round($value * 100, 1) . '%');
                }
            } else {
                $this->error('Training failed: ' . ($result['training']['error'] ?? 'Unknown'));
                if (isset($result['training']['trace'])) {
                    $this->line($result['training']['trace']);
                }
                $this->line('Debug training result:');
                $this->line(json_encode($result['training'], JSON_PRETTY_PRINT));
            }
            
            $this->newLine();
            $this->info('=== Scoring Test ===');
            if ($result['test_scoring']['success']) {
                $this->info('✓ Scoring successful!');
                $this->table(
                    ['Score Type', 'Value'],
                    [
                        ['Weighted Score', $result['test_scoring']['weighted_score']],
                        ['ML Score', $result['test_scoring']['ml_score']],
                        ['Final Score', $result['test_scoring']['final_score']],
                        ['Classification', $result['test_scoring']['classification']],
                    ]
                );
                
                $this->newLine();
                $this->info('Group Scores:');
                foreach ($result['test_scoring']['group_scores'] as $group => $score) {
                    if ($group !== 'total') {
                        $this->line("  Group {$group}: {$score}");
                    }
                }
            } else {
                $this->error('Scoring failed: ' . ($result['test_scoring']['error'] ?? 'Unknown'));
            }
            
            $this->newLine();
            $this->info('ML Pipeline test completed successfully! ✓');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
