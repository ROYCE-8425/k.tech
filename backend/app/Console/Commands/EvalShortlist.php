<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Job;
use App\Services\AI\AIOrchestratorClient;
use App\Services\ML\MLScoringPipeline;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Phase 5: Evaluation command for AI Shortlist.
 *
 * Compares the current hybrid AI matcher against two baselines:
 *   1. MLScoringPipeline (Laravel rule-based + RF fallback)
 *   2. Simple keyword-overlap heuristic (Jaccard)
 *
 * Usage:
 *   php artisan eval:shortlist              # Run full evaluation
 *   php artisan eval:shortlist --no-ai      # Skip AI service calls (use persisted results only)
 *   php artisan eval:shortlist --verbose     # Show per-pair details
 *
 * IMPORTANT: This eval set is small (~12 pairs). Claims are qualified as
 * "on this demo eval set", not production accuracy claims.
 */
class EvalShortlist extends Command
{
    protected $signature = 'eval:shortlist
        {--no-ai : Skip live AI service calls, use only persisted ai_match_result}
        {--seed : Run EvalDatasetSeeder before evaluation}';

    protected $description = 'Run Phase 5 evaluation: compare AI hybrid matcher vs baselines on the eval dataset';

    /**
     * Job requirements keywords extracted from JobSeeder — used for keyword baseline.
     * Keyed by substring of job title for lookup convenience.
     */
    private array $jobKeywordCache = [];

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║   Smart CV Matcher — Phase 5 Evaluation                 ║');
        $this->info('║   Comparing: AI Hybrid vs ML Pipeline vs Keyword Overlap ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->info('');

        // Optionally seed first
        if ($this->option('seed')) {
            $this->info('🌱 Running EvalDatasetSeeder...');
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\EvalDatasetSeeder']);
            $this->info('');
        }

        // Load eval pairs — applications with eval_set marker in notes
        $evalApplications = Application::with(['candidate', 'job'])
            ->whereNotNull('notes')
            ->get()
            ->filter(function ($app) {
                $notes = $app->notes;
                if (is_string($notes)) {
                    $decoded = json_decode($notes, true);
                    return is_array($decoded) && ($decoded['eval_set'] ?? false) === true;
                }
                if (is_array($notes)) {
                    return ($notes['eval_set'] ?? false) === true;
                }
                return false;
            });

        if ($evalApplications->isEmpty()) {
            $this->error('No eval dataset found. Run with --seed to create it first:');
            $this->line('  php artisan eval:shortlist --seed');
            return self::FAILURE;
        }

        $this->info("📊 Found {$evalApplications->count()} eval pairs");
        $this->info('');

        // Run evaluation
        $results = [];
        $skipAi = $this->option('no-ai');
        $aiClient = $skipAi ? null : app(AIOrchestratorClient::class);
        $mlPipeline = new MLScoringPipeline();

        $bar = $this->output->createProgressBar($evalApplications->count());
        $bar->setFormat(' %current%/%max% [%bar%] %message%');
        $bar->start();

        foreach ($evalApplications as $app) {
            $candidate = $app->candidate;
            $job = $app->job;

            if (!$candidate || !$job) {
                $bar->advance();
                continue;
            }

            $notes = is_string($app->notes) ? json_decode($app->notes, true) : $app->notes;
            $expectedFit = $notes['expected_fit'] ?? 'unknown';
            $scoreRange = $notes['score_range'] ?? [0, 100];

            $bar->setMessage($candidate->name . ' → ' . $job->title);

            // --- Baseline A: ML Pipeline ---
            $mlStart = microtime(true);
            $mlResult = $mlPipeline->scoreCandidate($candidate, $job);
            $mlLatency = (microtime(true) - $mlStart) * 1000;
            $mlScore = $mlResult['final_score'] ?? 0;

            // --- Baseline B: Keyword overlap (Jaccard) ---
            $kwStart = microtime(true);
            $kwScore = $this->keywordOverlapScore($candidate, $job);
            $kwLatency = (microtime(true) - $kwStart) * 1000;

            // --- AI Hybrid Matcher ---
            $aiScore = null;
            $aiRank = null;
            $aiLatency = null;

            // Try persisted result first
            $aiResult = $app->ai_match_result;
            if (!empty($aiResult)) {
                $aiScore = $aiResult['fit_score'] ?? null;
                $aiRank = $aiResult['rank_label'] ?? null;
                $aiLatency = 0; // Already persisted
            }

            // If no persisted result and AI is enabled, call live
            if ($aiScore === null && $aiClient) {
                try {
                    $aiStart = microtime(true);
                    $payload = $this->buildPayload($candidate, $job, $app);
                    $rawResult = $aiClient->matchCandidateToJob($payload);
                    $aiLatency = (microtime(true) - $aiStart) * 1000;
                    $aiScore = $rawResult['fit_score'] ?? null;
                    $aiRank = $rawResult['rank_label'] ?? null;

                    // Persist for future runs
                    $sanitized = $this->sanitize($rawResult);
                    $app->update(['ai_match_result' => $sanitized]);
                } catch (\Throwable $e) {
                    $aiScore = null;
                    $aiRank = 'error';
                    $aiLatency = null;
                }
            }

            $results[] = [
                'application_id' => $app->id,
                'candidate'      => $candidate->name,
                'job'            => $job->title,
                'expected_fit'   => $expectedFit,
                'score_range'    => $scoreRange,
                'ml_score'       => round($mlScore, 1),
                'ml_latency_ms'  => round($mlLatency, 1),
                'kw_score'       => round($kwScore, 1),
                'kw_latency_ms'  => round($kwLatency, 1),
                'ai_score'       => $aiScore !== null ? round($aiScore, 1) : null,
                'ai_rank'        => $aiRank,
                'ai_latency_ms'  => $aiLatency !== null ? round($aiLatency, 1) : null,
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->info('');
        $this->info('');

        // Print results
        $this->printDetailTable($results);
        $this->printMetricsSummary($results);
        $this->printRepresentativeExamples($results);

        return self::SUCCESS;
    }

    /**
     * Keyword overlap baseline: Jaccard similarity between candidate skills and job requirements keywords.
     */
    private function keywordOverlapScore(Candidate $candidate, Job $job): float
    {
        $candidateSkills = collect($candidate->skills_json ?? [])
            ->map(fn($s) => mb_strtolower(trim($s)))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $jobKeywords = $this->extractJobKeywords($job);

        if (empty($candidateSkills) || empty($jobKeywords)) {
            return 0.0;
        }

        $intersection = count(array_intersect($candidateSkills, $jobKeywords));
        $union = count(array_unique(array_merge($candidateSkills, $jobKeywords)));

        $jaccard = $union > 0 ? $intersection / $union : 0;

        // Scale Jaccard (0-1) to 0-100 with slight boost for partial matches
        return min(98, $jaccard * 100 * 1.5);
    }

    /**
     * Extract keywords from job requirements text.
     */
    private function extractJobKeywords(Job $job): array
    {
        $cacheKey = $job->id;
        if (isset($this->jobKeywordCache[$cacheKey])) {
            return $this->jobKeywordCache[$cacheKey];
        }

        $text = mb_strtolower(($job->requirements ?? '') . ' ' . ($job->description ?? ''));

        // Known tech keywords to extract
        $techKeywords = [
            'react', 'next.js', 'typescript', 'javascript', 'vue', 'angular',
            'laravel', 'php', 'mysql', 'redis', 'docker', 'kubernetes', 'ci/cd',
            'node.js', 'express.js', 'mongodb', 'rest api', 'graphql',
            'python', 'sql', 'power bi', 'tableau', 'excel', 'pandas', 'numpy',
            'aws', 'gcp', 'terraform', 'linux', 'git', 'testing',
            'flutter', 'react native', 'dart', 'swift', 'kotlin',
            'selenium', 'playwright', 'cypress', 'postman',
            'machine learning', 'deep learning', 'nlp', 'bert', 'gpt',
            'tensorflow', 'pytorch', 'mlops',
            'figma', 'photoshop', 'illustrator', 'after effects', 'premiere',
            'facebook ads', 'google ads', 'tiktok ads', 'seo', 'content marketing',
            'tailwind', 'tailwind css', 'state management', 'redux', 'zustand',
            'queue', 'microservices', 'a/b testing', 'data visualization',
            'social media', 'kpi',
        ];

        $found = [];
        foreach ($techKeywords as $kw) {
            if (str_contains($text, $kw)) {
                $found[] = $kw;
            }
        }

        $this->jobKeywordCache[$cacheKey] = $found;
        return $found;
    }

    /**
     * Print detailed per-pair table.
     */
    private function printDetailTable(array $results): void
    {
        $this->info('┌────────────────────────────────────────────────────────────────────────────────────────────────┐');
        $this->info('│ Detailed Results Per Pair                                                                      │');
        $this->info('├────────────────────────────────────────────────────────────────────────────────────────────────┤');

        $headers = ['Candidate', 'Job', 'Expected', 'ML Score', 'KW Score', 'AI Score', 'AI Rank'];
        $rows = [];

        foreach ($results as $r) {
            $candidateShort = mb_substr($r['candidate'], 0, 25);
            $jobShort = mb_substr($r['job'], 0, 30);
            $rows[] = [
                $candidateShort,
                $jobShort,
                $r['expected_fit'],
                $r['ml_score'],
                $r['kw_score'],
                $r['ai_score'] ?? '—',
                $r['ai_rank'] ?? '—',
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Print aggregate metrics summary.
     */
    private function printMetricsSummary(array $results): void
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║         Aggregate Metrics Summary         ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        // Group by expected fit
        $byFit = ['high_fit' => [], 'medium_fit' => [], 'low_fit' => []];
        foreach ($results as $r) {
            $fit = $r['expected_fit'];
            if (isset($byFit[$fit])) {
                $byFit[$fit][] = $r;
            }
        }

        // --- Metric 1: Mean score by expected fit label ---
        $this->info('📈 Mean Score by Expected Fit Label');
        $this->info('  (Higher is better for high_fit, lower is better for low_fit)');
        $this->info('');

        $fitHeaders = ['Expected Fit', 'N', 'ML Mean', 'KW Mean', 'AI Mean'];
        $fitRows = [];
        foreach ($byFit as $label => $items) {
            $n = count($items);
            if ($n === 0) continue;

            $mlMean = round(collect($items)->avg('ml_score'), 1);
            $kwMean = round(collect($items)->avg('kw_score'), 1);
            $aiItems = collect($items)->filter(fn($r) => $r['ai_score'] !== null);
            $aiMean = $aiItems->isNotEmpty() ? round($aiItems->avg('ai_score'), 1) : '—';

            $fitRows[] = [$label, $n, $mlMean, $kwMean, $aiMean];
        }
        $this->table($fitHeaders, $fitRows);

        // --- Metric 2: Score separation (high_fit mean - low_fit mean) ---
        $highMean = count($byFit['high_fit']) > 0 ? collect($byFit['high_fit'])->avg('ml_score') : null;
        $lowMean = count($byFit['low_fit']) > 0 ? collect($byFit['low_fit'])->avg('ml_score') : null;

        $this->info('📐 Score Separation (high_fit mean − low_fit mean)');
        if ($highMean !== null && $lowMean !== null) {
            $mlSep = round($highMean - $lowMean, 1);
            $kwHighMean = collect($byFit['high_fit'])->avg('kw_score');
            $kwLowMean = collect($byFit['low_fit'])->avg('kw_score');
            $kwSep = round($kwHighMean - $kwLowMean, 1);

            $aiHighItems = collect($byFit['high_fit'])->filter(fn($r) => $r['ai_score'] !== null);
            $aiLowItems = collect($byFit['low_fit'])->filter(fn($r) => $r['ai_score'] !== null);
            $aiSep = ($aiHighItems->isNotEmpty() && $aiLowItems->isNotEmpty())
                ? round($aiHighItems->avg('ai_score') - $aiLowItems->avg('ai_score'), 1)
                : '—';

            $this->table(
                ['Metric', 'ML Pipeline', 'Keyword', 'AI Hybrid'],
                [['Separation', $mlSep, $kwSep, $aiSep]]
            );
            $this->info('  (Higher separation = better discriminative power)');
        }
        $this->info('');

        // --- Metric 3: Precision@3 (per job that has ≥3 eval pairs) ---
        // Precision@K = |relevant ∩ top-K| / K
        // Denominator is the shortlist size (K=3), NOT the number of relevant items.
        $this->info('🎯 Precision@3 (per job with ≥3 eval candidates)');
        $this->info('  "Of the top 3 ranked candidates, what fraction are truly high_fit?"');
        $this->info('');

        $jobGroups = collect($results)->groupBy('job');
        $p3Results = [];

        foreach ($jobGroups as $jobTitle => $group) {
            if ($group->count() < 3) continue;

            $expectedGood = $group->filter(fn($r) => $r['expected_fit'] === 'high_fit')->pluck('candidate')->toArray();
            if (empty($expectedGood)) continue;

            // ML top 3
            $mlTop3 = $group->sortByDesc('ml_score')->take(3)->pluck('candidate')->toArray();
            $mlK = count($mlTop3);  // actual shortlist size (may be < 3 if fewer candidates)
            $mlP3 = $mlK > 0 ? count(array_intersect($mlTop3, $expectedGood)) / $mlK : 0;

            // KW top 3
            $kwTop3 = $group->sortByDesc('kw_score')->take(3)->pluck('candidate')->toArray();
            $kwK = count($kwTop3);
            $kwP3 = $kwK > 0 ? count(array_intersect($kwTop3, $expectedGood)) / $kwK : 0;

            // AI top 3
            $aiGroup = $group->filter(fn($r) => $r['ai_score'] !== null);
            $aiP3 = '—';
            if ($aiGroup->count() >= 3) {
                $aiTop3 = $aiGroup->sortByDesc('ai_score')->take(3)->pluck('candidate')->toArray();
                $aiK = count($aiTop3);
                $aiP3 = $aiK > 0 ? round(count(array_intersect($aiTop3, $expectedGood)) / $aiK, 2) : 0;
            }

            $p3Results[] = [mb_substr($jobTitle, 0, 40), round($mlP3, 2), round($kwP3, 2), $aiP3];
        }

        if (!empty($p3Results)) {
            $this->table(['Job', 'ML P@3', 'KW P@3', 'AI P@3'], $p3Results);
        } else {
            $this->warn('  Not enough data for Precision@3 (need ≥3 candidates per job with high_fit labels)');
        }

        // --- Metric 4: Latency ---
        $this->info('');
        $this->info('⚡ Latency (ms)');

        $mlLatencies = collect($results)->pluck('ml_latency_ms')->filter()->sort()->values();
        $kwLatencies = collect($results)->pluck('kw_latency_ms')->filter()->sort()->values();
        $aiLatencies = collect($results)->pluck('ai_latency_ms')->filter()->reject(fn($v) => $v === 0)->sort()->values();

        $latencyRows = [];
        foreach ([['ML Pipeline', $mlLatencies], ['Keyword', $kwLatencies], ['AI Hybrid', $aiLatencies]] as [$name, $lat]) {
            if ($lat->isEmpty()) {
                $latencyRows[] = [$name, '—', '—', '—'];
                continue;
            }
            $p50Idx = (int) floor($lat->count() * 0.5);
            $p95Idx = min($lat->count() - 1, (int) floor($lat->count() * 0.95));
            $latencyRows[] = [
                $name,
                round($lat->avg(), 1),
                round($lat->get($p50Idx, 0), 1),
                round($lat->get($p95Idx, 0), 1),
            ];
        }
        $this->table(['Method', 'Mean', 'p50', 'p95'], $latencyRows);

        // --- Overall ---
        $this->info('');
        $this->info('📋 Dataset Info');
        $this->info("  Total eval pairs: " . count($results));
        $this->info("  High fit: " . count($byFit['high_fit']));
        $this->info("  Medium fit: " . count($byFit['medium_fit']));
        $this->info("  Low fit: " . count($byFit['low_fit']));
        $aiAvailable = collect($results)->filter(fn($r) => $r['ai_score'] !== null)->count();
        $this->info("  AI scores available: {$aiAvailable}/" . count($results));
        $this->info('');

        $this->warn('⚠️  This is a small eval set. Results should be interpreted as');
        $this->warn('   "on this demo dataset", not as production accuracy claims.');
        $this->info('');
    }

    /**
     * Print a few representative examples.
     */
    private function printRepresentativeExamples(array $results): void
    {
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║       Representative Examples             ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        // Pick one from each category
        $examples = [];
        foreach (['high_fit', 'medium_fit', 'low_fit'] as $fit) {
            $item = collect($results)->firstWhere('expected_fit', $fit);
            if ($item) $examples[] = $item;
        }

        foreach ($examples as $ex) {
            $icon = match ($ex['expected_fit']) {
                'high_fit' => '🟢',
                'medium_fit' => '🟡',
                'low_fit' => '🔴',
                default => '⚪',
            };

            $this->info("  {$icon} [{$ex['expected_fit']}] {$ex['candidate']}");
            $this->info("     → {$ex['job']}");
            $this->info("     ML: {$ex['ml_score']}  |  Keyword: {$ex['kw_score']}  |  AI: " . ($ex['ai_score'] ?? '—'));
            if ($ex['ai_rank']) {
                $this->info("     AI rank label: {$ex['ai_rank']}");
            }
            $this->info('');
        }
    }

    /**
     * Build AI match payload (same structure as AdminController).
     */
    private function buildPayload(Candidate $candidate, Job $job, Application $application): array
    {
        return [
            'candidate' => [
                'id'               => $candidate->id,
                'name'             => $candidate->name,
                'summary'          => $candidate->summary,
                'about_me'         => $candidate->about_me,
                'skills'           => $candidate->skills_json ?: $candidate->skills,
                'skills_json'      => $candidate->skills_json,
                'experience'       => $candidate->experience,
                'education'        => $candidate->education,
                'work_experiences' => $candidate->work_experiences,
                'profile_data'     => $candidate->profile_data ?: (object) [],
                'cv_data'          => $application->cv_data ?: null,
            ],
            'job' => [
                'id'           => $job->id,
                'title'        => $job->title,
                'description'  => $job->description,
                'requirements' => $job->requirements,
                'location'     => $job->location,
            ],
            'options' => [
                'include_reasoning' => true,
            ],
            'application_id' => $application->id,
        ];
    }

    /**
     * Sanitize AI result (same whitelist as Phase 3/4).
     */
    private function sanitize(array $result): array
    {
        return [
            'fit_score'               => $result['fit_score'] ?? null,
            'rank_label'              => $result['rank_label'] ?? null,
            'confidence_label'        => $result['confidence_label'] ?? null,
            'score_breakdown'         => $result['score_breakdown'] ?? [],
            'matched_skills'          => $result['matched_skills'] ?? [],
            'missing_skills'          => $result['missing_skills'] ?? [],
            'missing_preferred_skills' => $result['missing_preferred_skills'] ?? [],
            'risk_flags'              => $result['risk_flags'] ?? [],
            'retrieval_method'        => $result['retrieval_method'] ?? 'unknown',
            'pipeline_version'        => $result['pipeline_version'] ?? 'unknown',
            'generated_at'            => $result['generated_at'] ?? now()->toIso8601String(),
        ];
    }
}
