<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\MLModel;
use App\Models\MLPrediction;
use App\Models\MLTrainingData;
use App\Services\ML\MLScoringPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * ML Scoring API Controller
 * 
 * Cung cấp các endpoints cho hệ thống ML Scoring
 */
class MLScoringController extends Controller
{
    private MLScoringPipeline $pipeline;

    public function __construct()
    {
        $this->pipeline = new MLScoringPipeline();
    }

    // ========================================================================
    // SCORING ENDPOINTS
    // ========================================================================

    /**
     * Score single candidate
     * 
     * @OA\Post(
     *     path="/ml/score",
     *     operationId="scoreCandidate",
     *     tags={"ML Scoring"},
     *     summary="Chấm điểm một ứng viên",
     *     description="Sử dụng ML pipeline để chấm điểm một ứng viên dựa trên các features được trích xuất từ hồ sơ.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"candidate_id"},
     *             @OA\Property(property="candidate_id", type="integer", example=1, description="ID của ứng viên"),
     *             @OA\Property(property="job_id", type="integer", nullable=true, example=5, description="ID công việc (tuỳ chọn)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chấm điểm thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="prediction_id", type="integer", example=42),
     *                 @OA\Property(property="candidate_id", type="integer", example=1),
     *                 @OA\Property(property="final_score", type="number", format="float", example=78.5),
     *                 @OA\Property(property="classification", type="string", example="A"),
     *                 @OA\Property(property="confidence", type="number", format="float", example=0.85),
     *                 @OA\Property(
     *                     property="details",
     *                     type="object",
     *                     @OA\Property(property="weighted_score", type="number", format="float", example=75.0),
     *                     @OA\Property(property="ml_score", type="number", format="float", example=82.0),
     *                     @OA\Property(property="group_scores", type="object")
     *                 ),
     *                 @OA\Property(property="processing_time_ms", type="integer", example=120)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Scoring failed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function score(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'job_id' => 'integer|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $startTime = microtime(true);

            $candidate = Candidate::findOrFail($request->candidate_id);
            $result = $this->pipeline->scoreCandidate($candidate);
            
            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            // Lưu prediction history
            $prediction = MLPrediction::create([
                'candidate_id' => $candidate->id,
                'job_id' => $request->job_id,
                'ml_model_id' => $this->getActiveModelId(),
                'input_features' => $result['features'],
                'group_scores' => $result['group_scores'],
                'weighted_score' => $result['weighted_score'],
                'ml_score' => $result['ml_score'],
                'final_score' => $result['final_score'],
                'classification' => $result['classification'],
                'confidence' => $result['confidence'],
                'processing_time_ms' => $processingTime,
                'feature_contributions' => $result['feature_importance'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'prediction_id' => $prediction->id,
                    'candidate_id' => $candidate->id,
                    'final_score' => round($result['final_score'], 2),
                    'classification' => $result['classification'],
                    'confidence' => round($result['confidence'], 3),
                    'details' => [
                        'weighted_score' => round($result['weighted_score'], 2),
                        'ml_score' => round($result['ml_score'], 2),
                        'group_scores' => $result['group_scores'],
                    ],
                    'processing_time_ms' => $processingTime,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ML Scoring error', [
                'candidate_id' => $request->candidate_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Scoring failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Score multiple candidates (batch)
     * 
     * @OA\Post(
     *     path="/ml/score/batch",
     *     operationId="scoreBatch",
     *     tags={"ML Scoring"},
     *     summary="Chấm điểm hàng loạt ứng viên",
     *     description="Chấm điểm nhiều ứng viên cùng lúc (tối đa 100). Kết quả được sắp xếp theo điểm giảm dần.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"candidate_ids"},
     *             @OA\Property(property="candidate_ids", type="array", @OA\Items(type="integer"), example={1,2,3}, description="Danh sách ID ứng viên (max 100)"),
     *             @OA\Property(property="job_id", type="integer", nullable=true, example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Batch scoring thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=3),
     *                 @OA\Property(property="results", type="array", @OA\Items(
     *                     @OA\Property(property="candidate_id", type="integer"),
     *                     @OA\Property(property="final_score", type="number", format="float"),
     *                     @OA\Property(property="classification", type="string"),
     *                     @OA\Property(property="confidence", type="number", format="float")
     *                 )),
     *                 @OA\Property(property="processing_time_ms", type="integer", example=350),
     *                 @OA\Property(property="avg_time_per_candidate", type="number", format="float", example=116.67)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Batch scoring failed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function scoreBatch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'candidate_ids' => 'required|array|max:100',
            'candidate_ids.*' => 'integer|exists:candidates,id',
            'job_id' => 'integer|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $startTime = microtime(true);
            $candidates = Candidate::whereIn('id', $request->candidate_ids)->get();
            $results = $this->pipeline->scoreMultipleCandidates($candidates);
            $processingTime = (int)((microtime(true) - $startTime) * 1000);

            // Lưu predictions
            $predictions = [];
            $activeModelId = $this->getActiveModelId();
            
            foreach ($results as $result) {
                $predictions[] = MLPrediction::create([
                    'candidate_id' => $result['candidate_id'],
                    'job_id' => $request->job_id,
                    'ml_model_id' => $activeModelId,
                    'input_features' => $result['features'],
                    'group_scores' => $result['group_scores'],
                    'weighted_score' => $result['weighted_score'],
                    'ml_score' => $result['ml_score'],
                    'final_score' => $result['final_score'],
                    'classification' => $result['classification'],
                    'confidence' => $result['confidence'],
                    'processing_time_ms' => $processingTime / count($results),
                ]);
            }

            // Format response
            $formattedResults = array_map(function($result) {
                return [
                    'candidate_id' => $result['candidate_id'],
                    'final_score' => round($result['final_score'], 2),
                    'classification' => $result['classification'],
                    'confidence' => round($result['confidence'], 3),
                ];
            }, $results);

            // Sort by score (descending)
            usort($formattedResults, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => count($formattedResults),
                    'results' => $formattedResults,
                    'processing_time_ms' => $processingTime,
                    'avg_time_per_candidate' => round($processingTime / count($results), 2),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ML Batch Scoring error', [
                'candidate_ids' => $request->candidate_ids,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Batch scoring failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // TRAINING ENDPOINTS
    // ========================================================================

    /**
     * Train model với data hiện có
     * 
     * @OA\Post(
     *     path="/ml/train",
     *     operationId="trainModel",
     *     tags={"ML Training"},
     *     summary="Huấn luyện model ML",
     *     description="Train Random Forest model với training data hiện có trong database. Cần ít nhất 100 labeled samples.",
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="n_estimators", type="integer", minimum=1, maximum=200, example=100, description="Số lượng decision trees"),
     *             @OA\Property(property="max_depth", type="integer", minimum=1, maximum=20, example=10, description="Độ sâu tối đa của tree"),
     *             @OA\Property(property="min_samples_split", type="integer", minimum=2, maximum=50, example=5),
     *             @OA\Property(property="max_features_ratio", type="number", format="float", minimum=0.1, maximum=1.0, example=0.8)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Training thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="model_id", type="integer", example=3),
     *                 @OA\Property(property="version", type="string", example="v1.2.0"),
     *                 @OA\Property(property="training_samples", type="integer", example=500),
     *                 @OA\Property(
     *                     property="metrics",
     *                     type="object",
     *                     @OA\Property(property="r2_score", type="number", format="float", example=0.92),
     *                     @OA\Property(property="mae", type="number", format="float", example=3.5),
     *                     @OA\Property(property="rmse", type="number", format="float", example=5.2),
     *                     @OA\Property(property="oob_score", type="number", format="float", example=0.88)
     *                 ),
     *                 @OA\Property(property="feature_importance", type="object"),
     *                 @OA\Property(property="training_time_ms", type="integer", example=2500)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Không đủ training data", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Training failed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function train(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'n_estimators' => 'integer|min:1|max:200',
            'max_depth' => 'integer|min:1|max:20',
            'min_samples_split' => 'integer|min:2|max:50',
            'max_features_ratio' => 'numeric|min:0.1|max:1.0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Lấy training data từ database
            $trainingData = MLTrainingData::labeled()
                ->orderBy('created_at', 'desc')
                ->limit(5000) // Giới hạn 5000 samples
                ->get();

            if ($trainingData->count() < 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cần ít nhất 100 labeled samples để train. Hiện có: ' . $trainingData->count(),
                ], 400);
            }

            // Prepare training data
            $X = [];
            $y = [];
            
            foreach ($trainingData as $data) {
                $X[] = $data->features_array;
                $y[] = $data->score;
            }

            // Set hyperparameters if provided
            if ($request->filled('n_estimators')) {
                $this->pipeline->setConfig(['n_estimators' => $request->n_estimators]);
            }

            $startTime = microtime(true);
            $metrics = $this->pipeline->train($X, $y);
            $trainingTime = (int)((microtime(true) - $startTime) * 1000);

            // Lưu model vào database
            $version = MLModel::generateVersion();
            $model = MLModel::create([
                'model_type' => 'random_forest',
                'version' => $version,
                'model_data' => json_encode($this->pipeline->getModelData()),
                'feature_names' => $this->pipeline->getFeatureNames(),
                'hyperparameters' => $this->pipeline->getHyperparameters(),
                'metrics' => $metrics,
                'feature_importance' => $this->pipeline->getFeatureImportance(),
                'training_samples' => count($X),
                'training_started_at' => now()->subMilliseconds($trainingTime),
                'training_completed_at' => now(),
                'is_active' => true,
            ]);

            // Deactivate old models
            $model->activate();

            return response()->json([
                'success' => true,
                'data' => [
                    'model_id' => $model->id,
                    'version' => $version,
                    'training_samples' => count($X),
                    'metrics' => [
                        'r2_score' => round($metrics['r2_score'], 4),
                        'mae' => round($metrics['mae'], 2),
                        'rmse' => round($metrics['rmse'], 2),
                        'oob_score' => round($metrics['oob_score'] ?? 0, 4),
                    ],
                    'feature_importance' => $this->pipeline->getFeatureImportance(),
                    'training_time_ms' => $trainingTime,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('ML Training error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Training failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Thêm training data
     * 
     * @OA\Post(
     *     path="/ml/training-data",
     *     operationId="addTrainingData",
     *     tags={"ML Training"},
     *     summary="Thêm dữ liệu huấn luyện",
     *     description="Thêm một labeled sample vào training dataset. Nếu đã tồn tại (candidate_id + job_id) sẽ được cập nhật.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"candidate_id", "score"},
     *             @OA\Property(property="candidate_id", type="integer", example=1),
     *             @OA\Property(property="job_id", type="integer", nullable=true, example=5),
     *             @OA\Property(property="score", type="number", format="float", minimum=0, maximum=100, example=85.0, description="Điểm đánh giá (0-100)"),
     *             @OA\Property(property="source", type="string", nullable=true, example="manual", description="Nguồn dữ liệu")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Thêm training data thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="message", type="string", example="Training data added successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Failed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function addTrainingData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'candidate_id' => 'required|integer|exists:candidates,id',
            'job_id' => 'integer|nullable',
            'score' => 'required|numeric|min:0|max:100',
            'source' => 'string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $candidate = Candidate::findOrFail($request->candidate_id);
            
            // Extract features từ candidate
            $features = $this->pipeline->extractFeatures($candidate);
            $groupScores = $this->pipeline->calculateGroupScores($features);

            $trainingData = MLTrainingData::updateOrCreate(
                [
                    'candidate_id' => $request->candidate_id,
                    'job_id' => $request->job_id,
                ],
                [
                    'features' => json_encode($features),
                    'group_scores' => json_encode($groupScores),
                    'score' => $request->score,
                    'source' => $request->source ?? 'manual',
                ]
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $trainingData->id,
                    'message' => 'Training data added successfully',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Add training data error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add training data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import training data từ predictions có feedback
     * 
     * @OA\Post(
     *     path="/ml/training-data/import-from-feedback",
     *     operationId="importFromFeedback",
     *     tags={"ML Training"},
     *     summary="Import training data từ feedback",
     *     description="Tự động import các predictions đã có human feedback thành training data để cải thiện model.",
     *     @OA\Response(
     *         response=200,
     *         description="Import thành công",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="imported", type="integer", example=15),
     *                 @OA\Property(property="message", type="string", example="Imported 15 training samples from feedback")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Import failed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function importFromFeedback(): JsonResponse
    {
        try {
            $predictions = MLPrediction::withFeedback()
                ->get();

            $imported = 0;
            foreach ($predictions as $prediction) {
                $data = $prediction->toTrainingData();
                if (!empty($data)) {
                    MLTrainingData::updateOrCreate(
                        [
                            'candidate_id' => $data['candidate_id'],
                            'job_id' => $data['job_id'],
                        ],
                        [
                            'features' => json_encode($data['features']),
                            'group_scores' => json_encode($data['group_scores']),
                            'score' => $data['score'],
                            'source' => $data['source'],
                        ]
                    );
                    $imported++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'imported' => $imported,
                    'message' => "Imported {$imported} training samples from feedback",
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // FEEDBACK ENDPOINTS
    // ========================================================================

    /**
     * Thêm human feedback cho prediction
     * 
     * @OA\Post(
     *     path="/ml/feedback",
     *     operationId="addFeedback",
     *     tags={"ML Feedback"},
     *     summary="Thêm human feedback cho prediction",
     *     description="Gửi đánh giá của con người cho một kết quả prediction. Giúp cải thiện model qua active learning loop.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"prediction_id", "human_score"},
     *             @OA\Property(property="prediction_id", type="integer", example=42, description="ID của prediction cần feedback"),
     *             @OA\Property(property="human_score", type="number", format="float", minimum=0, maximum=100, example=80.0, description="Điểm đánh giá của con người"),
     *             @OA\Property(property="feedback", type="string", nullable=true, maxLength=1000, example="Ứng viên có kỹ năng tốt nhưng thiếu kinh nghiệm", description="Nhận xét bổ sung")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Feedback đã được ghi nhận",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="prediction_id", type="integer", example=42),
     *                 @OA\Property(property="ml_score", type="number", format="float", example=78.5),
     *                 @OA\Property(property="human_score", type="number", format="float", example=80.0),
     *                 @OA\Property(property="error", type="number", format="float", example=1.5),
     *                 @OA\Property(property="is_accurate", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=500, description="Failed", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function addFeedback(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prediction_id' => 'required|integer|exists:ml_predictions,id',
            'human_score' => 'required|numeric|min:0|max:100',
            'feedback' => 'string|nullable|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $prediction = MLPrediction::findOrFail($request->prediction_id);
            $prediction->addFeedback($request->human_score, $request->feedback);

            $error = abs($prediction->final_score - $request->human_score);

            return response()->json([
                'success' => true,
                'data' => [
                    'prediction_id' => $prediction->id,
                    'ml_score' => round($prediction->final_score, 2),
                    'human_score' => round($request->human_score, 2),
                    'error' => round($error, 2),
                    'is_accurate' => $error <= 5.0,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add feedback: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ========================================================================
    // ANALYTICS ENDPOINTS
    // ========================================================================

    /**
     * Lấy model info
     * 
     * @OA\Get(
     *     path="/ml/model",
     *     operationId="getModelInfo",
     *     tags={"ML Analytics"},
     *     summary="Lấy thông tin model hiện tại",
     *     description="Trả về thông tin chi tiết về model ML đang active, bao gồm version, metrics, feature importance.",
     *     @OA\Response(
     *         response=200,
     *         description="Thông tin model",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="has_model", type="boolean", example=true),
     *                 @OA\Property(property="model_id", type="integer", example=3),
     *                 @OA\Property(property="version", type="string", example="v1.2.0"),
     *                 @OA\Property(property="model_type", type="string", example="random_forest"),
     *                 @OA\Property(property="metrics", type="object"),
     *                 @OA\Property(property="feature_importance", type="object"),
     *                 @OA\Property(property="training_samples", type="integer", example=500),
     *                 @OA\Property(property="training_duration_seconds", type="number", format="float"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function getModelInfo(): JsonResponse
    {
        $activeModel = MLModel::getActive('random_forest');

        if (!$activeModel) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_model' => false,
                    'message' => 'No trained model available',
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_model' => true,
                'model_id' => $activeModel->id,
                'version' => $activeModel->version,
                'model_type' => $activeModel->model_type,
                'metrics' => $activeModel->metrics,
                'feature_importance' => $activeModel->feature_importance,
                'training_samples' => $activeModel->training_samples,
                'training_duration_seconds' => $activeModel->getTrainingDuration(),
                'created_at' => $activeModel->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Lấy model performance metrics
     * 
     * @OA\Get(
     *     path="/ml/model/performance",
     *     operationId="getModelPerformance",
     *     tags={"ML Analytics"},
     *     summary="Lấy hiệu suất model trên production",
     *     description="So sánh metrics khi training vs production, bao gồm accuracy từ human feedback và phân bố classification.",
     *     @OA\Response(
     *         response=200,
     *         description="Performance metrics",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="model_id", type="integer"),
     *                 @OA\Property(property="version", type="string"),
     *                 @OA\Property(property="training_metrics", type="object"),
     *                 @OA\Property(
     *                     property="production_metrics",
     *                     type="object",
     *                     @OA\Property(property="predictions_with_feedback", type="integer"),
     *                     @OA\Property(property="accuracy_percent", type="number", format="float", nullable=true),
     *                     @OA\Property(property="mae", type="number", format="float", nullable=true)
     *                 ),
     *                 @OA\Property(property="classification_distribution", type="object"),
     *                 @OA\Property(property="predictions_last_7_days", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="No active model found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getModelPerformance(): JsonResponse
    {
        $activeModel = MLModel::getActive('random_forest');
        
        if (!$activeModel) {
            return response()->json([
                'success' => false,
                'message' => 'No active model found',
            ], 404);
        }

        // Tính accuracy từ feedback
        $accuracy = MLPrediction::calculateModelAccuracy($activeModel->id);

        // Classification distribution
        $classificationStats = MLPrediction::where('ml_model_id', $activeModel->id)
            ->getClassificationStats();

        // Recent predictions stats
        $recentPredictions = MLPrediction::where('ml_model_id', $activeModel->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'model_id' => $activeModel->id,
                'version' => $activeModel->version,
                'training_metrics' => $activeModel->metrics,
                'production_metrics' => [
                    'predictions_with_feedback' => $accuracy['count'],
                    'accuracy_percent' => $accuracy['accuracy'] ? round($accuracy['accuracy'], 2) : null,
                    'mae' => $accuracy['mae'] ? round($accuracy['mae'], 2) : null,
                ],
                'classification_distribution' => $classificationStats,
                'predictions_last_7_days' => $recentPredictions,
            ],
        ]);
    }

    /**
     * Lấy training data stats
     * 
     * @OA\Get(
     *     path="/ml/training-data/stats",
     *     operationId="getTrainingDataStats",
     *     tags={"ML Training"},
     *     summary="Thống kê training data",
     *     description="Trả về thống kê tổng quan về training data: số lượng labeled/unlabeled, phân bố theo nguồn và điểm.",
     *     @OA\Response(
     *         response=200,
     *         description="Training data statistics",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=500),
     *                 @OA\Property(property="labeled", type="integer", example=350),
     *                 @OA\Property(property="unlabeled", type="integer", example=150),
     *                 @OA\Property(property="by_source", type="object", example={"manual": 200, "feedback": 150}),
     *                 @OA\Property(property="score_distribution", type="object", example={"A (80-100)": 100, "B (60-79)": 120, "C (40-59)": 80, "D (0-39)": 50}),
     *                 @OA\Property(property="ready_for_training", type="boolean", example=true)
     *             )
     *         )
     *     )
     * )
     */
    public function getTrainingDataStats(): JsonResponse
    {
        $total = MLTrainingData::count();
        $labeled = MLTrainingData::labeled()->count();
        $unlabeled = MLTrainingData::unlabeled()->count();

        $bySource = MLTrainingData::selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        $scoreDistribution = MLTrainingData::labeled()
            ->selectRaw("
                CASE 
                    WHEN score >= 80 THEN 'A (80-100)'
                    WHEN score >= 60 THEN 'B (60-79)'
                    WHEN score >= 40 THEN 'C (40-59)'
                    ELSE 'D (0-39)'
                END as grade,
                COUNT(*) as count
            ")
            ->groupBy('grade')
            ->pluck('count', 'grade')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'labeled' => $labeled,
                'unlabeled' => $unlabeled,
                'by_source' => $bySource,
                'score_distribution' => $scoreDistribution,
                'ready_for_training' => $labeled >= 100,
            ],
        ]);
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    /**
     * Lấy active model ID
     */
    private function getActiveModelId(): ?int
    {
        $model = MLModel::getActive('random_forest');
        return $model?->id;
    }
}
