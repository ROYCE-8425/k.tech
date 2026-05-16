<?php

namespace App\Services\AI;

/**
 * Transforms recruiter-facing AI match result into candidate-safe advisory.
 *
 * Candidate should see:
 * - match level (soft label, not raw score)
 * - matched skills
 * - suggested skills to improve
 * - missing information hints
 * - gentle CV improvement guidance
 *
 * Candidate MUST NOT see:
 * - raw fit_score number
 * - score_breakdown with weights
 * - confidence_label raw value
 * - recruiter-tone risk flags
 * - pipeline metadata
 */
class CandidateAdvisory
{
    /**
     * Build candidate-facing advisory from a persisted ai_match_result.
     *
     * @param  array|null  $aiResult  The persisted ai_match_result JSONB
     * @return array|null  Candidate-safe advisory, or null if no result available
     */
    public static function fromMatchResult(?array $aiResult): ?array
    {
        if (empty($aiResult) || !isset($aiResult['rank_label'])) {
            return null;
        }

        $rankLabel = $aiResult['rank_label'] ?? 'unknown';
        $matchedSkills = $aiResult['matched_skills'] ?? [];
        $missingSkills = $aiResult['missing_skills'] ?? [];
        $missingPreferred = $aiResult['missing_preferred_skills'] ?? [];
        $confidenceLabel = $aiResult['confidence_label'] ?? 'unknown';

        // Candidate-facing match level (soft language)
        $matchLevel = self::mapRankToAdvisory($rankLabel);

        // Skills to improve = missing required + missing preferred (combined, no harsh framing)
        $suggestedSkills = array_values(array_unique(array_merge($missingSkills, $missingPreferred)));

        // Missing information hints (based on confidence)
        $missingInfoHints = self::buildMissingInfoHints($confidenceLabel, $aiResult);

        // CV improvement suggestions (reworded from risk_flags as positive actions)
        $improvementTips = self::buildImprovementTips($aiResult);

        // Related strengths — soft phrasing for related-skill partial matches
        $relatedStrengths = self::buildRelatedStrengths($aiResult);

        return [
            'match_level'        => $matchLevel,
            'matched_skills'     => $matchedSkills,
            'related_strengths'  => $relatedStrengths,
            'suggested_skills'   => $suggestedSkills,
            'missing_info'       => $missingInfoHints,
            'improvement_tips'   => $improvementTips,
        ];
    }

    /**
     * Map rank_label to candidate-friendly Vietnamese labels.
     */
    private static function mapRankToAdvisory(string $rankLabel): array
    {
        return match ($rankLabel) {
            'high_fit'   => ['label' => 'Phù hợp cao', 'icon' => '✅', 'color' => 'emerald'],
            'medium_fit' => ['label' => 'Phù hợp vừa — có thể cải thiện', 'icon' => '🔶', 'color' => 'amber'],
            'low_fit'    => ['label' => 'Cần bổ sung thêm kỹ năng', 'icon' => '💡', 'color' => 'orange'],
            'error'      => ['label' => 'Đang xử lý', 'icon' => '⏳', 'color' => 'gray'],
            default      => ['label' => 'Đang xử lý', 'icon' => '⏳', 'color' => 'gray'],
        };
    }

    /**
     * Build hints about missing profile information.
     */
    private static function buildMissingInfoHints(string $confidenceLabel, array $aiResult): array
    {
        $hints = [];

        if ($confidenceLabel === 'low') {
            $hints[] = 'Hãy bổ sung thêm thông tin vào hồ sơ để AI đánh giá chính xác hơn.';
        }

        // Check if score_breakdown suggests missing data
        $breakdown = $aiResult['score_breakdown'] ?? [];

        if (isset($breakdown['experience_fit'])) {
            $detail = $breakdown['experience_fit']['detail'] ?? '';
            if (str_contains($detail, 'không rõ') || str_contains($detail, 'unknown')) {
                $hints[] = 'Hãy bổ sung số năm kinh nghiệm vào hồ sơ.';
            }
        }

        if (isset($breakdown['seniority_fit'])) {
            $detail = $breakdown['seniority_fit']['detail'] ?? '';
            if (str_contains($detail, 'không rõ') || str_contains($detail, 'unknown')) {
                $hints[] = 'Hãy cập nhật cấp bậc hiện tại (Junior, Mid, Senior, ...) trong hồ sơ.';
            }
        }

        return $hints;
    }

    /**
     * Transform risk_flags into positive improvement tips for candidates.
     */
    private static function buildImprovementTips(array $aiResult): array
    {
        $tips = [];
        $riskFlags = $aiResult['risk_flags'] ?? [];

        foreach ($riskFlags as $flag) {
            // Skip extraction-quality flags (not relevant to candidate)
            if (str_contains($flag, 'heuristic') || str_contains($flag, 'trích xuất')) {
                continue;
            }

            // Reword common patterns as positive suggestions
            if (str_contains($flag, 'kỹ năng bắt buộc') || str_contains($flag, 'required skills')) {
                $tips[] = 'Hãy tập trung phát triển thêm các kỹ năng bắt buộc của vị trí này.';
            } elseif (str_contains($flag, 'kinh nghiệm') || str_contains($flag, 'experience')) {
                $tips[] = 'Cân nhắc bổ sung thêm dự án hoặc kinh nghiệm thực tế vào CV.';
            } elseif (str_contains($flag, 'cấp bậc') || str_contains($flag, 'seniority')) {
                $tips[] = 'Vị trí này có thể yêu cầu cấp bậc khác — hãy xem xét kỹ mô tả công việc.';
            } else {
                // Generic tip for unrecognized flags
                $tips[] = 'Hãy cập nhật CV với thông tin mới nhất để tăng cơ hội phù hợp.';
            }
        }

        // Deduplicate
        return array_values(array_unique($tips));
    }

    /**
     * Build candidate-friendly related-skill strength messages.
     *
     * Translates recruiter "related_matches" into soft phrasing:
     * "Bạn đã có nền tảng gần với [target] thông qua kinh nghiệm [source]"
     */
    private static function buildRelatedStrengths(array $aiResult): array
    {
        $related = $aiResult['related_matches'] ?? [];
        if (empty($related)) {
            return [];
        }

        $strengths = [];
        foreach (array_slice($related, 0, 4) as $match) {
            $src = $match['candidate_skill'] ?? '';
            $tgt = $match['target_skill'] ?? '';
            if ($src && $tgt) {
                $strengths[] = "Bạn đã có nền tảng gần với {$tgt} thông qua kinh nghiệm {$src}.";
            }
        }

        return $strengths;
    }
}
