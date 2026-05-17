<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIOrchestratorClient
{
    public function matchCandidateToJob(array $payload): array
    {
        // Priority 1: External AI orchestrator (Canonical python backend)
        $baseUrl = rtrim((string) config('services.ai_orchestrator.base_url'), '/');
        $timeout = (int) config('services.ai_orchestrator.timeout_seconds', 25);

        try {
            $response = Http::connectTimeout(5)
                ->timeout($timeout)
                ->acceptJson()
                ->post($baseUrl . '/api/v1/match', $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('AI orchestrator unreachable', [
                'base_url' => $baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('AI service không khả dụng — kiểm tra kết nối đến ' . $baseUrl);
        }

        if (!$response->successful()) {
            Log::warning('AI orchestrator returned non-success response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('AI orchestrator error: HTTP ' . $response->status());
        }

        $canonicalResult = $response->json();

        // Optional enrichment via Multi-Agent Council
        try {
            $gptService = new GptScoringService();
            $councilAdvisory = $gptService->generateCouncilAdvisory($payload, $canonicalResult);
            $canonicalResult['multi_agent_council'] = $councilAdvisory;
        } catch (\Throwable $e) {
            Log::warning('Council advisory generation failed, skipping: ' . $e->getMessage());
        }

        return $canonicalResult;
    }

    /**
     * Phase 19: AI Decision Lab — compare reasoning modes for one candidate-job pair.
     */
    public function compareModes(array $payload): array
    {
        $baseUrl = rtrim((string) config('services.ai_orchestrator.base_url'), '/');
        $timeout = (int) config('services.ai_orchestrator.timeout_seconds', 25);

        try {
            $response = Http::connectTimeout(5)
                ->timeout($timeout)
                ->acceptJson()
                ->post($baseUrl . '/api/v1/compare', $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('AI compare endpoint unreachable', [
                'base_url' => $baseUrl,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('AI service không khả dụng — kiểm tra kết nối đến ' . $baseUrl);
        }

        if (!$response->successful()) {
            Log::warning('AI compare returned non-success response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('AI compare error: HTTP ' . $response->status());
        }

        return $response->json();
    }
}
