<?php

use App\Http\Controllers\Api\AICVMatcherController;
use App\Http\Controllers\Api\MLScoringController;
use Illuminate\Support\Facades\Route;

/**
 * ML Scoring API Routes
 * 
 * Prefix: /api/ml
 * Middleware: auth:sanctum (recommended for production)
 */

Route::prefix('ml')->group(function () {
    
    // ========================================================================
    // SCORING ENDPOINTS
    // ========================================================================
    
    /**
     * Score single candidate
     * 
     * POST /api/ml/score
     * Body: { candidate_id: int, job_id?: int }
     * Response: { final_score, classification, confidence, details }
     */
    Route::post('/score', [MLScoringController::class, 'score'])
        ->name('ml.score');

    /**
     * Score multiple candidates (batch)
     * 
     * POST /api/ml/score/batch
     * Body: { candidate_ids: int[], job_id?: int }
     * Response: { total, results[], processing_time_ms }
     */
    Route::post('/score/batch', [MLScoringController::class, 'scoreBatch'])
        ->name('ml.score.batch');

    // ========================================================================
    // TRAINING ENDPOINTS
    // ========================================================================
    
    /**
     * Train model với data hiện có
     * 
     * POST /api/ml/train
     * Body: { n_estimators?: int, max_depth?: int, ... }
     * Response: { model_id, version, metrics, feature_importance }
     */
    Route::post('/train', [MLScoringController::class, 'train'])
        ->name('ml.train');

    /**
     * Thêm training data
     * 
     * POST /api/ml/training-data
     * Body: { candidate_id: int, job_id?: int, score: float, source?: string }
     */
    Route::post('/training-data', [MLScoringController::class, 'addTrainingData'])
        ->name('ml.training-data.add');

    /**
     * Import training data từ predictions có feedback
     * 
     * POST /api/ml/training-data/import-from-feedback
     */
    Route::post('/training-data/import-from-feedback', [MLScoringController::class, 'importFromFeedback'])
        ->name('ml.training-data.import');

    /**
     * Lấy training data stats
     * 
     * GET /api/ml/training-data/stats
     */
    Route::get('/training-data/stats', [MLScoringController::class, 'getTrainingDataStats'])
        ->name('ml.training-data.stats');

    // ========================================================================
    // FEEDBACK ENDPOINTS
    // ========================================================================
    
    /**
     * Thêm human feedback cho prediction
     * 
     * POST /api/ml/feedback
     * Body: { prediction_id: int, human_score: float, feedback?: string }
     */
    Route::post('/feedback', [MLScoringController::class, 'addFeedback'])
        ->name('ml.feedback');

    // ========================================================================
    // MODEL INFO ENDPOINTS
    // ========================================================================
    
    /**
     * Lấy thông tin model hiện tại
     * 
     * GET /api/ml/model
     */
    Route::get('/model', [MLScoringController::class, 'getModelInfo'])
        ->name('ml.model.info');

    /**
     * Lấy model performance metrics
     * 
     * GET /api/ml/model/performance
     */
    Route::get('/model/performance', [MLScoringController::class, 'getModelPerformance'])
        ->name('ml.model.performance');

    /**
     * Multi-agent AI CV matcher (Level 5 architecture entrypoint)
     *
     * POST /api/ml/ai-match
     * Body: { candidate_id: int, job_id: int, include_reasoning?: bool }
     */
    Route::post('/ai-match', [AICVMatcherController::class, 'match'])
        ->name('ml.ai.match');
});
