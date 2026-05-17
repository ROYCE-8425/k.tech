<?php

namespace App\Jobs;

use App\Models\BulkUploadItem;
use App\Models\Candidate;
use App\Models\Application;
use App\Models\Job as JobModel;
use App\Services\CvAutoScoringService;
use App\Services\AI\AIOrchestratorClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ParseAndMatchBulkCvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $itemId;

    public function __construct(int $itemId)
    {
        $this->itemId = $itemId;
    }

    public function handle(CvAutoScoringService $scoringService, AIOrchestratorClient $aiClient): void
    {
        $item = BulkUploadItem::find($this->itemId);
        if (!$item || $item->status !== 'pending') {
            return;
        }

        try {
            $item->update(['status' => 'parsing']);
            
            $job = JobModel::findOrFail($item->job_id);
            $cvContent = $this->extractText($item->stored_path, $item->mime_type);
            
            if (empty(trim($cvContent))) {
                throw new \Exception('Cannot extract text from CV. File might be scanned or empty.');
            }

            $item->update([
                'status' => 'parsed',
                'parsed_cv_data' => ['raw_text_length' => strlen($cvContent)]
            ]);

            // Attempt to find or create candidate
            $candidateInfo = $this->extractBasicInfo($cvContent, $item->original_filename);
            
            $candidate = Candidate::firstOrCreate(
                ['email' => $candidateInfo['email']],
                [
                    'name' => $candidateInfo['name'],
                    'file_path_cv' => $item->stored_path,
                    'summary' => 'Imported via Bulk Upload. Needs review.',
                ]
            );

            // Update candidate CV if existing but no CV
            if (empty($candidate->file_path_cv)) {
                $candidate->update(['file_path_cv' => $item->stored_path]);
            }

            $item->update(['candidate_id' => $candidate->id, 'status' => 'matching']);

            // Create Application
            $application = Application::firstOrCreate(
                [
                    'job_id' => $job->id,
                    'candidate_id' => $candidate->id,
                ],
                [
                    'status' => 'applied',
                    'applied_at' => now(),
                    'source' => 'bulk_upload',
                    'cv_file_path' => $item->stored_path,
                ]
            );

            $cvDataForAi = [
                '_raw_text' => $cvContent,
                'self_description' => mb_substr($cvContent, 0, 6000),
                'source' => 'bulk_upload',
                'original_filename' => $item->original_filename,
            ];

            $existingCvData = is_array($application->cv_data) ? $application->cv_data : [];
            $application->cv_data = array_merge($existingCvData, $cvDataForAi);
            if (empty($application->cv_file_path)) {
                $application->cv_file_path = $item->stored_path;
            }
            $application->save();

            $item->update(['application_id' => $application->id]);

            // 1. Run Legacy Auto Scoring
            $scoringService->scoreAndPersist($application, $cvContent);

            // 2. Run AI Orchestrator Match (Optional, but highly recommended for demo)
            try {
                $applicationCvData = $application->cv_data;
                $cvDataPayload = is_array($applicationCvData) && !Arr::isList($applicationCvData)
                    ? $applicationCvData
                    : $cvDataForAi;

                $scoringConfigPayload = is_array($job->scoring_config) && !Arr::isList($job->scoring_config)
                    ? $job->scoring_config
                    : null;

                // We use the same payload builder logic from AdminController
                // For simplicity in the job, we build a basic payload or rely on the Python backend to extract
                $payload = [
                    'candidate' => [
                        'id' => $candidate->id,
                        'name' => $candidate->name,
                        'summary' => $candidate->summary,
                        'about_me' => $candidate->about_me,
                        'skills' => $candidate->skills,
                        'skills_json' => $candidate->skills_json,
                        'experience' => $candidate->experience,
                        'education' => $candidate->education,
                        'work_experiences' => [],
                        'profile_data' => $candidate->profile_data ?? [],
                        'cv_data' => $cvDataPayload,
                    ],
                    'job' => [
                        'id' => $job->id,
                        'title' => $job->title,
                        'description' => $job->description,
                        'requirements' => $job->requirements,
                        'location' => $job->location,
                        'required_skills' => is_array($job->required_skills) ? $job->required_skills : [],
                        'preferred_skills' => is_array($job->preferred_skills) ? $job->preferred_skills : [],
                        'seniority' => $job->seniority,
                        'min_experience_years' => $job->min_experience_years,
                        'max_experience_years' => $job->max_experience_years,
                        'scoring_config' => $scoringConfigPayload,
                        'ai_recruiter_notes' => $job->ai_recruiter_notes,
                    ],
                    'options' => [
                        'include_reasoning' => true,
                    ],
                    'application_id' => $application->id,
                ];
                
                $aiResult = $aiClient->matchCandidateToJob($payload);
                
                // Keep the generated_at and persisted flags consistent with AdminController
                $aiResult['generated_at'] = now()->toIso8601String();
                
                $application->update(['ai_match_result' => $aiResult]);
                $item->update(['ai_match_result' => $aiResult]);
                
            } catch (\Throwable $aiError) {
                Log::warning('Bulk Upload AI Match Failed for App ID ' . $application->id . ': ' . $aiError->getMessage());
                // Non-fatal, application is still created and legacy scored
            }

            $item->update(['status' => 'completed']);

            // Update Batch counters
            $this->incrementBatchProcessed($item->batch_id);

        } catch (Throwable $e) {
            Log::error('Bulk Upload Job Failed for Item ' . $this->itemId . ': ' . $e->getMessage());
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            $this->incrementBatchFailed($item->batch_id);
        }
    }

    private function incrementBatchProcessed(int $batchId)
    {
        $batch = \App\Models\BulkUploadBatch::find($batchId);
        if ($batch) {
            $batch->increment('processed_files');
            $this->checkBatchCompleted($batch);
        }
    }

    private function incrementBatchFailed(int $batchId)
    {
        $batch = \App\Models\BulkUploadBatch::find($batchId);
        if ($batch) {
            $batch->increment('failed_files');
            $this->checkBatchCompleted($batch);
        }
    }

    private function checkBatchCompleted($batch)
    {
        if ($batch->processed_files + $batch->failed_files >= $batch->total_files) {
            $batch->update(['status' => 'completed']);
        } else {
            $batch->update(['status' => 'processing']);
        }
    }

    private function extractText(string $path, ?string $mimeType): string
    {
        $fullPath = storage_path('app/public/' . $path);
        if (!file_exists($fullPath)) {
            $fullPath = storage_path('app/' . $path);
        }
        
        if (!file_exists($fullPath)) {
            return '';
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if ($ext === 'pdf' || str_contains((string) $mimeType, 'pdf')) {
            $text = $this->extractTextFromPdf($fullPath);
            if (!empty($text)) {
                return $text;
            }
        }

        if ($ext === 'docx' || str_contains((string) $mimeType, 'officedocument.wordprocessingml.document')) {
            $text = $this->extractTextFromDocx($fullPath);
            if (!empty($text)) {
                return $text;
            }
        }

        if ($ext === 'txt' || str_contains((string) $mimeType, 'text/plain')) {
            $txt = @file_get_contents($fullPath);
            if (is_string($txt) && trim($txt) !== '') {
                return $this->normalizeWhitespace($txt);
            }
        }

        // Last-resort marker for unsupported files.
        return "[CV uploaded: " . basename($path) . "]\nText extraction unavailable.";
    }

    private function extractBasicInfo(string $text, string $filename): array
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        // clean up name
        $name = str_replace(['_', '-'], ' ', $name);
        $name = ucwords(strtolower($name));

        $email = null;
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            $email = $m[0];
        } else {
            $email = 'candidate_' . Str::random(6) . '@demo.local';
        }

        return [
            'name' => $name,
            'email' => $email,
        ];
    }

    private function extractTextFromPdf(string $filePath): string
    {
        try {
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $text = (string) $pdf->getText();
                if (trim($text) !== '') {
                    return $this->normalizeWhitespace($text);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Bulk PDF parse via Smalot failed: ' . $e->getMessage());
        }

        try {
            $output = [];
            $returnCode = 0;
            @exec('pdftotext ' . escapeshellarg($filePath) . ' -', $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                return $this->normalizeWhitespace(implode("\n", $output));
            }
        } catch (\Throwable $e) {
            Log::warning('Bulk PDF parse via pdftotext failed: ' . $e->getMessage());
        }

        return '';
    }

    private function extractTextFromDocx(string $filePath): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();

                if (is_string($xml) && trim($xml) !== '') {
                    $text = strip_tags($xml);
                    if (trim($text) !== '') {
                        return $this->normalizeWhitespace($text);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Bulk DOCX parse failed: ' . $e->getMessage());
        }

        return '';
    }

    private function normalizeWhitespace(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }
}
