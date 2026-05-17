<?php

namespace App\Http\Controllers;

use App\Jobs\RunAIEvaluationJob;
use App\Models\AiEvaluationRun;
use Illuminate\Http\Request;

class AIEvaluationController extends Controller
{
    public function index()
    {
        $latestRun = AiEvaluationRun::orderBy('id', 'desc')->first();
        return view('admin.ai-evaluation', compact('latestRun'));
    }

    public function triggerRun()
    {
        $run = AiEvaluationRun::create([
            'status' => 'running',
            'started_at' => now(),
        ]);

        RunAIEvaluationJob::dispatch($run->id);

        return response()->json([
            'success' => true,
            'run_id' => $run->id,
            'message' => 'Đã bắt đầu chạy AI Evaluation. Vui lòng chờ...',
        ]);
    }

    public function status($runId = null)
    {
        if ($runId === 'latest') {
            $run = AiEvaluationRun::orderBy('id', 'desc')->first();
        } else {
            $run = AiEvaluationRun::find($runId);
        }

        if (!$run) {
            return response()->json(['error' => 'No run found'], 404);
        }

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'metrics' => $run->metrics,
            'error' => $run->error_message,
        ]);
    }
}
