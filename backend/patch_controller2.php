<?php
/**
 * Patch 2: Fix CV review flow
 * 1. extractCvPreview() reads from actual CV text, not demo profile
 * 2. apply() does NOT call AI scoring - just saves + extracts
 * 3. AI scoring moves to after user confirmation
 */

$file = __DIR__ . '/app/Http/Controllers/CandidateJobController.php';
$content = file_get_contents($file);
$contentNorm = str_replace("\r\n", "\n", $content);

// ══ PATCH A: Remove AI scoring from apply() ══
// Find and replace the AI matching block (lines ~825-856 area)
$oldAiBlock = <<<'PHP'
        // ── Immediate AI match for candidate-facing advisory (DEMO_MODE only) ──
        // In demo mode: calls AI service synchronously for immediate candidate advisory.
        // In non-demo mode: skips synchronous AI call, preserving prior apply behavior.
        $aiAdvisory = null;
        if (config('app.demo_mode')) {
            try {
                $orchestratorClient = app(\App\Services\AI\AIOrchestratorClient::class);
                $aiResponse = $orchestratorClient->matchCandidateToJob(
                    $this->buildAiMatchPayload($candidate, $job, $application)
                );

                // Persist sanitized result (OpenSpec: allowed when application_id is explicit)
                $sanitizedKeys = [
                    'fit_score', 'rank_label', 'confidence_label',
                    'score_breakdown', 'matched_skills', 'missing_skills',
                    'missing_preferred_skills', 'risk_flags',
                    'retrieval_method', 'pipeline_version', 'generated_at',
                ];
                $sanitized = array_intersect_key($aiResponse, array_flip($sanitizedKeys));
                $application->update(['ai_match_result' => $sanitized]);
                $aiAdvisory = $sanitized;

                \Log::info('Immediate AI match completed for candidate advisory', [
                    'application_id' => $application->id,
                    'fit_score'      => $aiAdvisory['fit_score'] ?? null,
                    'risk_flags'     => $aiAdvisory['risk_flags'] ?? [],
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Immediate AI match failed for application ' . $application->id . ': ' . $e->getMessage());
                // $aiAdvisory remains null — candidate sees fallback status
            }
        }

        $message = 'Nộp đơn ứng tuyển thành công! Chúng tôi sẽ liên hệ với bạn sớm nhất.';
        
        // Use AI advisory score if available, fallback to legacy auto-score
        $displayScore = $aiAdvisory['fit_score'] ?? $aiScore;
        if ($displayScore !== null) {
            $message .= sprintf(' 🤖 Điểm phù hợp: %.1f/10', $displayScore);
        }

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

$newAiBlock = <<<'PHP'
        // ── DO NOT score yet — wait for candidate to confirm CV info first ──
        // AI scoring will happen after candidate confirms extracted info (submitFollowup)
        $aiAdvisory = null;

        $message = 'Nộp đơn thành công! Vui lòng xác nhận thông tin CV để AI chấm điểm.';

        // Extract CV preview from actual file content (not profile data)
        $cvExtractedInfo = $this->extractCvPreview($cvContent, $candidate, null);

        // Compute follow-up fields based on what's missing in the CV
        $candidate->refresh();
        $followupFields = $this->detectMissingFollowupFields([], $candidate, $job);

        return redirect()->route('jobs.show', $job->id)
            ->with('status', $message)
            ->with('ai_score', null)
            ->with('ai_advisory', null)
            ->with('ai_followup_fields', $followupFields)
            ->with('cv_extracted_info', $cvExtractedInfo)
            ->withFragment('apply-form');
PHP;

$oldNorm = str_replace("\r\n", "\n", $oldAiBlock);
$newNorm = str_replace("\r\n", "\n", $newAiBlock);

if (str_contains($contentNorm, $oldNorm)) {
    $contentNorm = str_replace($oldNorm, $newNorm, $contentNorm);
    echo "✅ PATCH A: Removed immediate AI scoring from apply()\n";
} else {
    echo "❌ PATCH A: target not found\n";
}

// ══ PATCH B: Fix extractCvPreview() to parse actual CV text ══
$oldExtract = <<<'PHP'
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
PHP;

$newExtract = <<<'PHP'
    private function extractCvPreview(string $cvContent, Candidate $candidate, ?array $aiAdvisory): array
    {
        $preview = [
            'name' => null,
            'email' => null,
            'phone' => null,
            'skills' => [],
            'experience_years' => null,
            'education' => null,
            'summary' => null,
            'missing_skills' => [],
        ];

        if (empty($cvContent) || str_starts_with($cvContent, '[PDF uploaded')) {
            // No content extracted — return minimal info
            $preview['name'] = $candidate->name;
            $preview['email'] = $candidate->email;
            $preview['summary'] = 'Không thể đọc nội dung CV. Vui lòng kiểm tra file.';
            return $preview;
        }

        $text = $cvContent;

        // ── Extract name: first non-empty line or known pattern ──
        if (preg_match('/^([A-ZÀ-Ỹ][A-ZÀ-Ỹa-zà-ỹ\s]{2,50})$/mu', $text, $m)) {
            $preview['name'] = trim($m[1]);
        }
        if (!$preview['name']) {
            // Try: "Họ tên: ..." or "Name: ..."
            if (preg_match('/(?:họ\s*(?:và\s*)?tên|full\s*name|name)\s*[:\-]\s*(.+)/iu', $text, $m)) {
                $preview['name'] = trim($m[1]);
            }
        }
        if (!$preview['name']) {
            $preview['name'] = $candidate->name; // final fallback
        }

        // ── Extract email from CV text ──
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            $preview['email'] = $m[0];
        } else {
            $preview['email'] = $candidate->email;
        }

        // ── Extract phone from CV text ──
        if (preg_match('/(?:0\d{9,10}|\+84\d{9,10}|(?:\d{3,4}[\s\-\.]\d{3,4}[\s\-\.]\d{3,4}))/', $text, $m)) {
            $preview['phone'] = trim($m[0]);
        }

        // ── Extract skills: look for tech keywords ──
        $knownSkills = ['PHP', 'Laravel', 'JavaScript', 'TypeScript', 'React', 'Vue.js', 'Vue',
            'Node.js', 'Angular', 'Python', 'Django', 'Flask', 'Java', 'Spring', 'C#', '.NET',
            'Go', 'Golang', 'Rust', 'Ruby', 'Rails', 'Swift', 'Kotlin',
            'HTML', 'CSS', 'SASS', 'Tailwind', 'Bootstrap',
            'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'SQLite', 'Oracle', 'SQL Server',
            'Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP', 'Linux', 'Git', 'CI/CD',
            'REST', 'GraphQL', 'API', 'Microservices',
            'TensorFlow', 'PyTorch', 'Scikit-learn', 'Pandas', 'NumPy',
            'Machine Learning', 'Deep Learning', 'NLP', 'Computer Vision',
            'Data Analysis', 'Power BI', 'Tableau', 'Excel',
            'Selenium', 'Playwright', 'JUnit', 'Testing',
            'Agile', 'Scrum', 'Jira', 'Figma', 'Photoshop'];
        $foundSkills = [];
        foreach ($knownSkills as $skill) {
            if (mb_stripos($text, $skill) !== false) {
                $foundSkills[] = $skill;
            }
        }
        $preview['skills'] = array_slice(array_unique($foundSkills), 0, 12);

        // ── Extract experience years ──
        if (preg_match('/(\d+)\s*(?:\+\s*)?(?:năm|years?)\s*(?:kinh nghiệm|experience|of experience)/iu', $text, $m)) {
            $preview['experience_years'] = (int)$m[1];
        }
        if (!$preview['experience_years'] && preg_match('/(?:kinh nghiệm|experience)\s*[:\-]?\s*(\d+)/iu', $text, $m)) {
            $preview['experience_years'] = (int)$m[1];
        }

        // ── Extract education ──
        $eduKeywords = [
            'Tiến sĩ' => 'Tiến sĩ', 'Thạc sĩ' => 'Thạc sĩ',
            'Đại học' => 'Đại học', 'Cao đẳng' => 'Cao đẳng', 'Trung cấp' => 'Trung cấp',
            'PhD' => 'Tiến sĩ', 'Master' => 'Thạc sĩ', 'Bachelor' => 'Đại học',
            'University' => 'Đại học', 'College' => 'Cao đẳng',
            'UNIVERSITY' => 'Đại học', 'EDUCATION' => 'Đại học',
        ];
        foreach ($eduKeywords as $kw => $label) {
            if (mb_stripos($text, $kw) !== false) {
                $preview['education'] = $label;
                break;
            }
        }

        // Try to extract school name
        if (preg_match('/(?:EDUCATION|HỌC VẤN|TRƯỜNG)\s*[:\-]?\s*(.{5,80})/iu', $text, $m)) {
            $schoolInfo = trim($m[1]);
            if ($preview['education']) {
                $preview['education'] = $preview['education'] . ' — ' . $schoolInfo;
            } else {
                $preview['education'] = $schoolInfo;
            }
        }

        // ── Summary: first meaningful chunk of CV ──
        $preview['summary'] = mb_substr(trim($text), 0, 300);

        // ── Missing skills from AI ──
        if ($aiAdvisory) {
            $preview['missing_skills'] = $aiAdvisory['missing_skills'] ?? [];
        }

        return $preview;
    }
PHP;

$oldExtractNorm = str_replace("\r\n", "\n", $oldExtract);
$newExtractNorm = str_replace("\r\n", "\n", $newExtract);

if (str_contains($contentNorm, $oldExtractNorm)) {
    $contentNorm = str_replace($oldExtractNorm, $newExtractNorm, $contentNorm);
    echo "✅ PATCH B: Fixed extractCvPreview() to parse actual CV text\n";
} else {
    echo "❌ PATCH B: target not found\n";
}

// ══ PATCH C: Update submitFollowup() to always run AI scoring ══
// Add AI scoring to confirmation step even without follow-up fields
$oldFollowupMsg = <<<'PHP'
        $message = $aiAdvisory
            ? sprintf('✅ Đã cập nhật! AI đã đánh giá lại — Điểm phù hợp: %.1f/10', $aiAdvisory['fit_score'] ?? 0)
            : '✅ Thông tin đã được bổ sung thành công. AI tạm thời chưa khả dụng — kết quả sẽ tự động cập nhật khi AI service hoạt động lại.';

        return redirect()->route('jobs.show', $job->id)
            ->with('status', $message)
            ->with('ai_score', $aiAdvisory['fit_score'] ?? null)
            ->with('ai_advisory', $aiAdvisory)
            ->withFragment('apply-form');
PHP;

$newFollowupMsg = <<<'PHP'
        // Also run rule-based scoring if AI service was unavailable
        if (!$aiAdvisory) {
            try {
                $cvContent = '';
                if ($application->cv_data && isset($application->cv_data['_raw_text'])) {
                    $cvContent = $application->cv_data['_raw_text'];
                }
                $this->cvAutoScoringService->scoreAndPersist($application, $cvContent);
                $application->refresh();
            } catch (\Throwable $e) {
                \Log::warning('Fallback scoring failed: ' . $e->getMessage());
            }
        }

        $displayScore = $aiAdvisory['fit_score'] ?? ($application->ai_score ?? null);
        $message = $displayScore !== null
            ? sprintf('✅ AI đã chấm điểm! Điểm phù hợp: %.1f/10', $displayScore)
            : '✅ Thông tin đã được bổ sung. Nhà tuyển dụng sẽ xem xét sớm.';

        return redirect()->route('jobs.show', $job->id)
            ->with('status', $message)
            ->with('ai_score', $displayScore)
            ->with('ai_advisory', $aiAdvisory)
            ->withFragment('apply-form');
PHP;

$oldFollowupNorm = str_replace("\r\n", "\n", $oldFollowupMsg);
$newFollowupNorm = str_replace("\r\n", "\n", $newFollowupMsg);

if (str_contains($contentNorm, $oldFollowupNorm)) {
    $contentNorm = str_replace($oldFollowupNorm, $newFollowupNorm, $contentNorm);
    echo "✅ PATCH C: Updated submitFollowup() return with AI scoring\n";
} else {
    echo "❌ PATCH C: target not found\n";
}

// Write back
$contentFinal = str_replace("\n", "\r\n", $contentNorm);
$contentFinal = str_replace("\r\r\n", "\r\n", $contentFinal);
file_put_contents($file, $contentFinal);
echo "\n✅ Controller saved\n";
