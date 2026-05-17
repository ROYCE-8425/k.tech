<?php

namespace App\Http\Controllers;

use App\Jobs\ParseAndMatchBulkCvJob;
use App\Models\BulkUploadBatch;
use App\Models\BulkUploadItem;
use App\Models\Job;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BulkUploadController extends Controller
{
    public function store(Request $request, $jobId)
    {
        $request->validate([
            'cv_files' => 'required|array',
            'cv_files.*' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $job = Job::findOrFail($jobId);
        $files = $request->file('cv_files');

        $batch = BulkUploadBatch::create([
            'job_id' => $job->id,
            'recruiter_id' => Auth::id(),
            'total_files' => count($files),
            'status' => 'pending',
        ]);

        $usedSyncFallback = false;

        foreach ($files as $file) {
            $path = $file->store('cvs', 'public');

            $item = BulkUploadItem::create([
                'batch_id' => $batch->id,
                'job_id' => $job->id,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'mime_type' => $file->getMimeType(),
                'status' => 'pending',
            ]);

            try {
                ParseAndMatchBulkCvJob::dispatch($item->id);
            } catch (QueryException $e) {
                if (!$this->isQueueTableSchemaIssue($e)) {
                    throw $e;
                }

                $usedSyncFallback = true;
                Log::warning('Queue schema issue detected; fallback to sync bulk processing.', [
                    'batch_id' => $batch->id,
                    'item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);

                ParseAndMatchBulkCvJob::dispatchSync($item->id);
            }
        }

        if (!$usedSyncFallback) {
            $batch->update(['status' => 'processing']);
        } else {
            $batch->refresh();
            if ($batch->status === 'pending') {
                $batch->update(['status' => 'processing']);
            }
        }

        return response()->json([
            'success' => true,
            'batch_id' => $batch->id,
            'message' => $usedSyncFallback
                ? 'Da xu ly truc tiep ' . count($files) . ' CV (khong can queue).'
                : 'Da dua ' . count($files) . ' CV vao hang doi xu ly.',
        ]);
    }

    public function status($batchId)
    {
        $batch = BulkUploadBatch::with('items')->findOrFail($batchId);

        return response()->json([
            'batch' => [
                'id' => $batch->id,
                'total_files' => $batch->total_files,
                'processed_files' => $batch->processed_files,
                'failed_files' => $batch->failed_files,
                'status' => $batch->status,
            ],
            'items' => $batch->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'filename' => $item->original_filename,
                    'status' => $item->status,
                    'candidate_id' => $item->candidate_id,
                    'application_id' => $item->application_id,
                    'error' => $item->error_message,
                ];
            }),
        ]);
    }

    private function isQueueTableSchemaIssue(QueryException $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'table jobs has no column named queue')
            || (str_contains($message, 'insert into "jobs"') && str_contains($message, '"queue"'));
    }
}
