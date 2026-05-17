<?php

namespace App\Jobs;

use App\Models\AiEvaluationRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Throwable;

class RunAIEvaluationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $runId;

    public function __construct(int $runId)
    {
        $this->runId = $runId;
    }

    public function handle(): void
    {
        $run = AiEvaluationRun::find($this->runId);
        if (!$run || $run->status !== 'running') {
            return;
        }

        try {
            $pythonPath = config('app.python_path', 'python'); // or 'python3'
            
            // Path to ai-service
            $aiServicePath = base_path('../ai-service');
            $runnerPath = $aiServicePath . '/evals/runner.py';
            $outputPath = storage_path('app/ai_eval_results_' . $this->runId . '.json');

            // Set up the process
            $process = new Process([
                $pythonPath,
                $runnerPath,
                '--compare',
                '--output',
                $outputPath
            ]);

            $process->setWorkingDirectory($aiServicePath);
            $process->setTimeout(300); // 5 minutes max

            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Read the results
            if (file_exists($outputPath)) {
                $resultsJson = file_get_contents($outputPath);
                $metrics = json_decode($resultsJson, true);
                
                $run->update([
                    'status' => 'completed',
                    'finished_at' => now(),
                    'results_path' => $outputPath,
                    'metrics' => $metrics,
                ]);
            } else {
                throw new \Exception('Runner completed but output file was not found.');
            }

        } catch (Throwable $e) {
            Log::error('AI Evaluation Run Failed: ' . $e->getMessage());
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
