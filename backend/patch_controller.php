<?php
/**
 * Patch script: Modify CandidateJobController to implement 3-step CV review flow.
 * Run: php patch_controller.php
 */

$file = __DIR__ . '/app/Http/Controllers/CandidateJobController.php';
$content = file_get_contents($file);

// ── PATCH 1: apply() — always compute followup + flash cv_extracted_info ──
$oldApplyReturn = <<<'PHP'
        // Compute follow-up fields from AI result (flash for immediate display after apply)
        $followupFields = [];
        $candidate->refresh();
        if ($aiAdvisory && is_array($aiAdvisory)) {
            $candidate->refresh();
            $followupFields = $this->detectMissingFollowupFields($aiAdvisory, $candidate, $job);
        }

        return redirect()->route('jobs.show', $job->id)
            ->with('status', $message)
            ->with('ai_score', $displayScore)
            ->with('ai_advisory', $aiAdvisory)
            ->with('ai_followup_fields', $followupFields)
            ->withFragment('apply-form');
PHP;

$newApplyReturn = <<<'PHP'
        // Compute follow-up fields — always check (not just when AI advisory exists)
        $followupFields = [];
        $candidate->refresh();
        if ($aiAdvisory && is_array($aiAdvisory)) {
            $followupFields = $this->detectMissingFollowupFields($aiAdvisory, $candidate, $job);
        } else {
            $followupFields = $this->detectMissingFollowupFields([], $candidate, $job);
        }

        // Extract CV preview for candidate confirmation step
        $cvExtractedInfo = $this->extractCvPreview($cvContent, $candidate, $aiAdvisory);

        return redirect()->route('jobs.show', $job->id)
            ->with('status', $message)
            ->with('ai_score', $displayScore)
            ->with('ai_advisory', $aiAdvisory)
            ->with('ai_followup_fields', $followupFields)
            ->with('cv_extracted_info', $cvExtractedInfo)
            ->withFragment('apply-form');
PHP;

// Normalize line endings for matching
$contentNorm = str_replace("\r\n", "\n", $content);
$oldNorm = str_replace("\r\n", "\n", $oldApplyReturn);
$newNorm = str_replace("\r\n", "\n", $newApplyReturn);

if (str_contains($contentNorm, $oldNorm)) {
    $contentNorm = str_replace($oldNorm, $newNorm, $contentNorm);
    echo "✅ PATCH 1 applied (apply return block)\n";
} else {
    echo "❌ PATCH 1 target not found\n";
}

// ── PATCH 2: detectMissingFollowupFields() — remove confidenceLow gate, cap at 2 ──
$oldDetect = <<<'PHP'
    private function detectMissingFollowupFields(array $aiResult, Candidate $candidate, Job $job): array
    {
        $missing = [];
        $profileData = is_array($candidate->profile_data) ? $candidate->profile_data : [];
        $confidenceLow = ($aiResult['confidence_label'] ?? '') === 'low';

        // 1. Empty candidate profile fields → always ask regardless of confidence
        if (!$candidate->phone) {
            $missing[] = 'phone';
        }
        if (!$candidate->education) {
            $missing[] = 'education_level';
        }
        if (empty($profileData['primary_role'] ?? null)) {
            $missing[] = 'primary_role';
        }

        // 2. Low confidence → AI couldn't extract enough, ask core data fields
        if ($confidenceLow) {
            if (!$candidate->experience && !in_array('years_experience', $missing)) {
                $missing[] = 'years_experience';
            }
            if (empty($candidate->skills) && !in_array('key_skills', $missing)) {
                $missing[] = 'key_skills';
            }
        }

        // 3. Risk flags mentioning missing/unclear information
        foreach ($aiResult['risk_flags'] ?? [] as $flag) {
            $lower = mb_strtolower($flag);
            if ((str_contains($lower, 'experience') || str_contains($lower, 'kinh nghiệm'))
                && !in_array('years_experience', $missing)) {
                $missing[] = 'years_experience';
            }
            if ((str_contains($lower, 'education') || str_contains($lower, 'học vấn') || str_contains($lower, 'trình độ'))
                && !in_array('education_level', $missing)) {
                $missing[] = 'education_level';
            }
            if ((str_contains($lower, 'english') || str_contains($lower, 'language') || str_contains($lower, 'tiếng anh'))
                && !in_array('english_level', $missing)) {
                $missing[] = 'english_level';
            }
        }

        // 4. Missing portfolio/GitHub for technical roles — only when there is also
        //    a low-confidence signal (not by default on every dev-role application).
        if ($confidenceLow) {
            $jobTitle = mb_strtolower($job->title ?? '');
            $isDevRole = str_contains($jobTitle, 'developer') || str_contains($jobTitle, 'engineer')
                      || str_contains($jobTitle, 'backend')   || str_contains($jobTitle, 'frontend')
                      || str_contains($jobTitle, 'fullstack')  || str_contains($jobTitle, 'devops');
            if ($isDevRole) {
                if (!$candidate->github_url && !in_array('github_url', $missing)) {
                    $missing[] = 'github_url';
                }
                if (!$candidate->portfolio_url && !in_array('portfolio_url', $missing)) {
                    $missing[] = 'portfolio_url';
                }
            }
        }

        // english_level is only asked when triggered by risk flags (rule #3 above)
        // — not asked unconditionally.

        // Cap at 6 questions to keep the form compact
        return array_slice(array_unique($missing), 0, 6);
    }
PHP;

$newDetect = <<<'PHP'
    private function detectMissingFollowupFields(array $aiResult, Candidate $candidate, Job $job): array
    {
        $missing = [];
        $profileData = is_array($candidate->profile_data) ? $candidate->profile_data : [];

        // Priority-ordered checks: most impactful fields first
        // Always check all fields (not gated by confidence level)

        // 1. Core fields that heavily impact AI scoring
        if (!$candidate->experience && !isset($profileData['cv_quick']['certifications']['years_experience'])) {
            $missing[] = 'years_experience';
        }
        if (empty($candidate->skills) && empty($profileData['skills'] ?? [])) {
            $missing[] = 'key_skills';
        }
        if (!$candidate->education) {
            $missing[] = 'education_level';
        }
        if (empty($profileData['primary_role'] ?? null)) {
            $missing[] = 'primary_role';
        }

        // 2. Risk flags from AI mentioning missing info
        foreach ($aiResult['risk_flags'] ?? [] as $flag) {
            $lower = mb_strtolower($flag);
            if ((str_contains($lower, 'experience') || str_contains($lower, 'kinh nghiệm'))
                && !in_array('years_experience', $missing)) {
                $missing[] = 'years_experience';
            }
            if ((str_contains($lower, 'english') || str_contains($lower, 'language') || str_contains($lower, 'tiếng anh'))
                && !in_array('english_level', $missing)) {
                $missing[] = 'english_level';
            }
        }

        // Cap at 2 questions for concise, focused UX
        return array_slice(array_unique($missing), 0, 2);
    }
PHP;

$oldDetectNorm = str_replace("\r\n", "\n", $oldDetect);
$newDetectNorm = str_replace("\r\n", "\n", $newDetect);

if (str_contains($contentNorm, $oldDetectNorm)) {
    $contentNorm = str_replace($oldDetectNorm, $newDetectNorm, $contentNorm);
    echo "✅ PATCH 2 applied (detectMissingFollowupFields)\n";
} else {
    echo "❌ PATCH 2 target not found\n";
}

// ── PATCH 3: Add extractCvPreview() method before buildAiMatchPayload() ──
$oldBuildPayloadDoc = <<<'PHP'
    /**
     * Build the AI match payload for AIOrchestratorClient.
     * Used by both apply() and submitFollowup() to avoid duplication.
     */
PHP;

$newExtractCvPreview = <<<'PHP'
    /**
     * Extract a structured preview of CV data for candidate confirmation.
     * Uses heuristic regex + AI match result to present extracted info.
     */
    private function extractCvPreview(string $cvContent, Candidate $candidate, ?array $aiAdvisory): array
    {
        $preview = [
            'name' => $candidate->name,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'skills' => [],
            'experience_years' => null,
            'education' => null,
            'summary' => null,
            'missing_skills' => [],
        ];

        // Extract skills from AI matched_skills or from candidate record
        if (!empty($aiAdvisory['matched_skills'])) {
            $preview['skills'] = $aiAdvisory['matched_skills'];
        } elseif (!empty($candidate->skills)) {
            $preview['skills'] = array_map('trim', explode(',', $candidate->skills));
        }

        // Try to detect experience years from CV text
        if (!empty($cvContent)) {
            if (preg_match('/(\d+)\s*(?:năm|years?)\s*(?:kinh nghiệm|experience)/iu', $cvContent, $m)) {
                $preview['experience_years'] = (int)$m[1];
            }
            if (!$preview['experience_years'] && preg_match('/(?:kinh nghiệm|experience)[:\s]*(\d+)/iu', $cvContent, $m)) {
                $preview['experience_years'] = (int)$m[1];
            }
        }
        // Fallback to candidate record
        if (!$preview['experience_years'] && $candidate->experience) {
            if (preg_match('/(\d+)/', $candidate->experience, $m)) {
                $preview['experience_years'] = (int)$m[1];
            }
        }

        // Education from candidate record or CV text
        $preview['education'] = $candidate->education;
        if (!$preview['education'] && !empty($cvContent)) {
            $eduKeywords = [
                'Tiến sĩ' => 'Tiến sĩ', 'Thạc sĩ' => 'Thạc sĩ',
                'Đại học' => 'Đại học', 'Cao đẳng' => 'Cao đẳng',
                'Trung cấp' => 'Trung cấp',
                'PhD' => 'Tiến sĩ', 'Master' => 'Thạc sĩ',
                'Bachelor' => 'Đại học', 'University' => 'Đại học',
            ];
            foreach ($eduKeywords as $kw => $label) {
                if (mb_stripos($cvContent, $kw) !== false) {
                    $preview['education'] = $label;
                    break;
                }
            }
        }

        // Summary: first 200 chars of CV or candidate summary
        if (!empty($candidate->summary)) {
            $preview['summary'] = mb_substr($candidate->summary, 0, 200);
        } elseif (!empty($cvContent)) {
            $preview['summary'] = mb_substr(trim($cvContent), 0, 200);
        }

        // Add missing skills from AI result
        $preview['missing_skills'] = $aiAdvisory['missing_skills'] ?? [];

        return $preview;
    }

    /**
     * Build the AI match payload for AIOrchestratorClient.
     * Used by both apply() and submitFollowup() to avoid duplication.
     */
PHP;

$oldBuildNorm = str_replace("\r\n", "\n", $oldBuildPayloadDoc);
$newExtractNorm = str_replace("\r\n", "\n", $newExtractCvPreview);

if (str_contains($contentNorm, $oldBuildNorm)) {
    $contentNorm = str_replace($oldBuildNorm, $newExtractNorm, $contentNorm);
    echo "✅ PATCH 3 applied (extractCvPreview + buildAiMatchPayload doc)\n";
} else {
    echo "❌ PATCH 3 target not found\n";
}

// Write back with original CRLF
$contentFinal = str_replace("\n", "\r\n", $contentNorm);
// Avoid double CRLF
$contentFinal = str_replace("\r\r\n", "\r\n", $contentFinal);

file_put_contents($file, $contentFinal);
echo "\n✅ File saved: $file\n";
echo "Lines: " . count(explode("\n", $contentNorm)) . "\n";
