<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI CV Scoring Service - uses FREE models:
 * Priority: Gemini Flash (free) → Grok (free tier) → OpenAI (paid fallback)
 */
class GptScoringService
{
    public function generateCouncilAdvisory(array $payload, array $canonicalResult): array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($payload['candidate'] ?? [], $payload['job'] ?? [], $canonicalResult);

        // Try Gemini first (FREE)
        $geminiKey = env('GEMINI_API_KEY', '');
        if (!empty($geminiKey)) {
            try {
                return $this->callGemini($geminiKey, $systemPrompt, $userPrompt);
            } catch (\Throwable $e) {
                Log::warning('Gemini advisory failed: ' . $e->getMessage());
            }
        }

        // Try Grok/xAI (FREE tier)
        $grokKey = env('GROK_API_KEY', '');
        if (!empty($grokKey)) {
            try {
                return $this->callGrok($grokKey, $systemPrompt, $userPrompt);
            } catch (\Throwable $e) {
                Log::warning('Grok advisory failed: ' . $e->getMessage());
            }
        }

        // Fallback: OpenAI (paid)
        $openaiKey = env('OPENAI_API_KEY', '');
        if (!empty($openaiKey)) {
            return $this->callOpenAI($openaiKey, $systemPrompt, $userPrompt);
        }

        throw new \RuntimeException('No AI API key configured for advisory');
    }

    private function callGemini(string $apiKey, string $system, string $user): array
    {
        $model = 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(30)->post($url, [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $system . "\n\n" . $user]]],
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
            ],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini API error: HTTP ' . $response->status() . ' - ' . $response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text', '');
        return $this->parseAndValidate($text, 'gemini-flash');
    }

    private function callGrok(string $apiKey, string $system, string $user): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.x.ai/v1/chat/completions', [
            'model' => 'grok-3-mini-fast',
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Grok API error: HTTP ' . $response->status());
        }

        $text = $response->json('choices.0.message.content', '{}');
        return $this->parseAndValidate($text, 'grok-mini');
    }

    private function callOpenAI(string $apiKey, string $system, string $user): array
    {
        $model = env('OPENAI_MODEL', 'gpt-4o-mini');
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI API error: HTTP ' . $response->status());
        }

        $text = $response->json('choices.0.message.content', '{}');
        return $this->parseAndValidate($text, 'openai-' . $model);
    }

    private function parseAndValidate(string $content, string $method): array
    {
        // Clean markdown code fences if present
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);

        $result = json_decode($content, true);
        if (!is_array($result) || !isset($result['multi_agent_council'])) {
            Log::warning("AI returned invalid format via {$method}", ['content' => mb_substr($content, 0, 500)]);
            throw new \RuntimeException("AI returned invalid format via {$method}");
        }

        return $result['multi_agent_council'];
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Bạn là Hệ thống AI Chuyên gia Tuyển dụng (Multi-Agent Council), bao gồm 5 tác nhân (agents) đóng vai trò tư vấn cho Recruiter:
1. SkillGraphAgent: Đánh giá độ phủ kỹ năng và các kỹ năng liên quan.
2. ExperienceFitAgent: Đánh giá số năm kinh nghiệm và level thực tế.
3. DomainTrendAgent: Đánh giá mức độ phù hợp với xu hướng công nghệ hiện tại của mảng chuyên môn.
4. RiskCriticAgent: Phát hiện rủi ro, điểm yếu hoặc những điểm cần phỏng vấn kỹ.
5. ConsensusAgent: Tổng hợp ý kiến thành một lời khuyên cho Recruiter.

## NHIỆM VỤ CỦA BẠN:
Hệ thống AI chính (Deterministic Scorer) đã tính toán ra `fit_score` và các thông tin khớp/thiếu. Bạn KHÔNG tính lại điểm này. 
Nhiệm vụ của bạn là dựa vào CV của ứng viên, Yêu cầu công việc, và Kết quả hệ thống (Canonical Result) để viết ra lời tư vấn sâu sắc, dễ hiểu cho Recruiter.

## OUTPUT FORMAT (JSON strictly):
{
  "multi_agent_council": {
    "summary": "Tóm tắt ngắn gọn",
    "consensus_label": "Khuyên tiến hành phỏng vấn",
    "reviewer_guidance": "Cần kiểm tra sâu về X",
    "agent_opinions": [
      {
        "agent_name": "SkillGraphAgent",
        "focus_area": "Technical Skills",
        "verdict": "Tốt",
        "confidence": "high",
        "strengths": ["Biết X, Y"],
        "concerns": ["Thiếu Z"],
        "notes": "Có thể học nhanh Z vì đã biết Y",
        "trend_source": null
      }
      // Cần có đủ 4 ý kiến của 4 agent còn lại
    ]
  }
}
PROMPT;
    }

    private function getTrendData(string $jobTitle): array
    {
        $title = strtolower($jobTitle);
        $file = 'backend_trends.json';

        if (str_contains($title, 'front') || str_contains($title, 'react') || str_contains($title, 'vue')) {
            $file = 'frontend_trends.json';
        } elseif (str_contains($title, 'data') || str_contains($title, 'sql')) {
            $file = 'data_roles.json';
        } elseif (str_contains($title, 'ai') || str_contains($title, 'ml') || str_contains($title, 'machine learning')) {
            $file = 'ai_ml_roles.json';
        }

        $path = storage_path('app/ai_trends/' . $file);
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            return is_array($data) ? $data : [];
        }

        return [];
    }

    private function buildUserPrompt(array $candidate, array $job, array $canonicalResult): string
    {
        $info = "## KẾT QUẢ TỪ HỆ THỐNG (CANONICAL RESULT):\n";
        $info .= "- Fit Score: " . ($canonicalResult['fit_score'] ?? 'N/A') . "\n";
        $info .= "- Kỹ năng khớp: " . implode(', ', $canonicalResult['matched_skills'] ?? []) . "\n";
        $info .= "- Kỹ năng thiếu: " . implode(', ', $canonicalResult['missing_skills'] ?? []) . "\n";
        if (!empty($canonicalResult['risk_flags'])) {
            $info .= "- Cảnh báo rủi ro: " . implode('; ', $canonicalResult['risk_flags']) . "\n";
        }

        $info .= "\n## ỨNG VIÊN:\n";
        $info .= "- Tên: " . ($candidate['name'] ?? 'N/A') . "\n";
        $info .= "- Skills: " . ($candidate['skills'] ?? 'Không rõ') . "\n";
        $info .= "- Kinh nghiệm: " . ($candidate['experience'] ?? 'Không rõ') . "\n";
        $info .= "- Học vấn: " . ($candidate['education'] ?? 'Không rõ') . "\n";

        if (!empty($candidate['summary'])) {
            $info .= "- CV tóm tắt: " . mb_substr($candidate['summary'], 0, 1500) . "\n";
        }
        if (!empty($candidate['cv_data']['_raw_text'])) {
            $info .= "\n### NỘI DUNG CV:\n" . mb_substr($candidate['cv_data']['_raw_text'], 0, 3000) . "\n";
        }

        $info .= "\n## VỊ TRÍ TUYỂN:\n";
        $info .= "- Tên: " . ($job['title'] ?? 'N/A') . "\n";
        $info .= "- Mô tả: " . mb_substr($job['description'] ?? '', 0, 1500) . "\n";
        $info .= "- Yêu cầu: " . mb_substr($job['requirements'] ?? '', 0, 1500) . "\n";

        $trends = $this->getTrendData($job['title'] ?? '');
        if (!empty($trends)) {
            $info .= "\n## TREND DATA & LENS (Dành cho DomainTrendAgent):\n";
            $info .= json_encode($trends, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        }

        return $info . "\nTừ những dữ liệu trên, hãy phân tích bằng Multi-Agent Council và trả về JSON chứa object `multi_agent_council`. KHÔNG TẠO LẠI điểm số.";
    }
}
