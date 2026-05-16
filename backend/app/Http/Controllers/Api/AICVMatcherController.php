<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Job;
use App\Services\AI\AIOrchestratorClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * AI CV Matcher Controller
 * 
 * Multi-agent AI architecture cho việc so khớp CV với JD
 */
class AICVMatcherController extends Controller
{
    public function __construct(
        private readonly AIOrchestratorClient $aiClient
    ) {}

    /**
     * Multi-agent AI CV matcher
     *
     * @OA\Post(
     *     path="/ml/ai-match",
     *     operationId="aiMatch",
     *     tags={"AI Matching"},
     *     summary="So khớp CV với Job Description bằng AI đa tác tử",
     *     description="Sử dụng kiến trúc Multi-Agent (Extractor, RAG, Matcher, Explainer, Critic) để phân tích CV ứng viên so với yêu cầu công việc. Trả về fit score, matched/missing skills, reasoning chain và evidence.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"candidate_id", "job_id"},
     *             @OA\Property(property="candidate_id", type="integer", example=1, description="ID của ứng viên"),
     *             @OA\Property(property="job_id", type="integer", example=5, description="ID của công việc"),
     *             @OA\Property(property="application_id", type="integer", nullable=true, example=10, description="ID application (nếu cung cấp, kết quả sẽ được lưu vào application)"),
     *             @OA\Property(property="include_reasoning", type="boolean", nullable=true, example=true, description="Bao gồm reasoning chain trong kết quả")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Matching thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="fit_score", type="number", format="float", example=82.5, description="Điểm phù hợp tổng thể (0-100)"),
     *                 @OA\Property(property="rank_label", type="string", example="Strong Match", description="Nhãn xếp hạng"),
     *                 @OA\Property(property="confidence_label", type="string", example="High", description="Mức độ tin cậy"),
     *                 @OA\Property(property="score_breakdown", type="object", description="Phân tích điểm chi tiết theo từng tiêu chí"),
     *                 @OA\Property(property="matched_skills", type="array", @OA\Items(type="string"), example={"Python", "Docker", "AWS"}, description="Kỹ năng khớp"),
     *                 @OA\Property(property="missing_skills", type="array", @OA\Items(type="string"), example={"Kubernetes"}, description="Kỹ năng thiếu (bắt buộc)"),
     *                 @OA\Property(property="missing_preferred_skills", type="array", @OA\Items(type="string"), description="Kỹ năng thiếu (ưu tiên)"),
     *                 @OA\Property(property="reasoning", type="array", @OA\Items(type="string"), description="Chuỗi lập luận của AI"),
     *                 @OA\Property(property="evidence", type="array", @OA\Items(type="string"), description="Bằng chứng hỗ trợ"),
     *                 @OA\Property(property="risk_flags", type="array", @OA\Items(type="string"), description="Cảnh báo rủi ro"),
     *                 @OA\Property(property="agent_trace", type="object", description="Nhật ký thực thi của từng agent"),
     *                 @OA\Property(property="retrieval_method", type="string", example="openai_embedding"),
     *                 @OA\Property(property="pipeline_version", type="string", example="v2.1.0")
     *             ),
     *             @OA\Property(property="persisted", type="boolean", example=true, description="Kết quả đã được lưu vào application hay chưa"),
     *             @OA\Property(property="persistence_reason", type="string", example="persisted to application 10")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=502, description="AI Service Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function match(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'candidate_id'   => ['required', 'integer', 'exists:candidates,id'],
            'job_id'         => ['required', 'integer', 'exists:jobs,id'],
            // Optional explicit application pin; validated against candidate/job after load
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'include_reasoning' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $candidate = Candidate::findOrFail((int) $request->input('candidate_id'));
        $job = Job::findOrFail((int) $request->input('job_id'));

        // -------------------------------------------------------------------
        // Resolve Application row
        //
        // Phase 3 persistence rules:
        //   - Write target: ONLY when application_id is explicitly provided and validated.
        //   - Read-only fallback: latest()->first() may still be used for cv_data enrichment,
        //     but this row is NEVER used as a write target.
        // -------------------------------------------------------------------

        $explicitApplicationId = $request->filled('application_id')
            ? (int) $request->input('application_id')
            : null;

        $writeTarget = null;   // Application row we are allowed to persist to
        $application = null;    // Application row for reading cv_data

        if ($explicitApplicationId !== null) {
            // Explicit application_id — cross-validate ownership
            $application = Application::where('id', $explicitApplicationId)
                ->where('candidate_id', $candidate->id)
                ->where('job_id', $job->id)
                ->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'application_id' => [
                            'The specified application does not belong to the given candidate and job.',
                        ],
                    ],
                ], 422);
            }

            // Explicit + validated → this row is both read source and write target
            $writeTarget = $application;
        } else {
            // No explicit application_id — fallback lookup for cv_data read ONLY
            $application = Application::where('candidate_id', $candidate->id)
                ->where('job_id', $job->id)
                ->latest()
                ->first();
            // $writeTarget remains null — no persistence allowed
        }

        $cvData = $application?->cv_data ?: null;

        $payload = [
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'summary' => $candidate->summary,
                'about_me' => $candidate->about_me,
                'skills' => $candidate->skills_json ?: $candidate->skills,
                'skills_json' => $candidate->skills_json,
                'experience' => $candidate->experience,
                'education' => $candidate->education,
                'work_experiences' => $candidate->work_experiences,
                'profile_data' => $candidate->profile_data ?: (object) [],
                'cv_data' => $cvData,
            ],
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'description' => $job->description,
                'requirements' => $job->requirements,
                'location' => $job->location,
            ],
            'options' => [
                'include_reasoning' => (bool) $request->boolean('include_reasoning', true),
            ],
            // Phase 3: pass application_id context to AI service
            'application_id' => $explicitApplicationId,
        ];

        try {
            $result = $this->aiClient->matchCandidateToJob($payload);

            // ---------------------------------------------------------------
            // Phase 3: Persist sanitized audit record
            //
            // Only if $writeTarget is set (explicit application_id, validated).
            // We persist a sanitized subset — no free-text candidate-derived content.
            // ---------------------------------------------------------------
            $persisted = false;
            $persistenceReason = 'no explicit application_id provided';

            if ($writeTarget !== null) {
                try {
                    $sanitized = $this->buildSanitizedAuditRecord($result);
                    $writeTarget->update(['ai_match_result' => $sanitized]);
                    $persisted = true;
                    $persistenceReason = 'persisted to application ' . $writeTarget->id;
                } catch (\Throwable $e) {
                    Log::warning('Failed to persist AI match result', [
                        'application_id' => $writeTarget->id,
                        'error' => $e->getMessage(),
                    ]);
                    $persistenceReason = 'persistence failed: ' . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'persisted' => $persisted,
                'persistence_reason' => $persistenceReason,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * Build a sanitized audit-safe subset of the match result.
     *
     * Whitelist approach: only known-safe fields are included.
     * Excluded: candidate_profile, job_profile, reasoning, evidence excerpts, agent_trace.
     */
    private function buildSanitizedAuditRecord(array $result): array
    {
        return [
            'fit_score' => $result['fit_score'] ?? null,
            'rank_label' => $result['rank_label'] ?? null,
            'confidence_label' => $result['confidence_label'] ?? null,
            'score_breakdown' => $result['score_breakdown'] ?? [],
            'matched_skills' => $result['matched_skills'] ?? [],
            'missing_skills' => $result['missing_skills'] ?? [],
            'missing_preferred_skills' => $result['missing_preferred_skills'] ?? [],
            'risk_flags' => $result['risk_flags'] ?? [],
            'retrieval_method' => $result['retrieval_method'] ?? 'unknown',
            'pipeline_version' => $result['pipeline_version'] ?? 'unknown',
            'generated_at' => $result['generated_at'] ?? now()->toIso8601String(),
        ];
    }
}
