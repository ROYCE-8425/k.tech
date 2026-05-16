<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Smart CV Matcher API",
 *     description="API documentation cho hệ thống Smart CV Matcher - Nền tảng AI tuyển dụng thông minh với Multi-Agent Architecture",
 *     @OA\Contact(
 *         email="admin@smartcvmatcher.com",
 *         name="Smart CV Matcher Team"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 *
 * @OA\Tag(
 *     name="ML Scoring",
 *     description="Machine Learning scoring endpoints - Chấm điểm ứng viên bằng ML"
 * )
 * @OA\Tag(
 *     name="ML Training",
 *     description="Model training endpoints - Huấn luyện model ML"
 * )
 * @OA\Tag(
 *     name="ML Feedback",
 *     description="Human feedback endpoints - Phản hồi của con người cho ML"
 * )
 * @OA\Tag(
 *     name="ML Analytics",
 *     description="Model analytics & performance endpoints - Thống kê model"
 * )
 * @OA\Tag(
 *     name="AI Matching",
 *     description="Multi-agent AI CV matching endpoints - So khớp CV bằng AI đa tác tử"
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="An error occurred"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Validation errors (if applicable)"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="data", type="object")
 * )
 */
abstract class Controller
{
    //
}
