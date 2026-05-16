<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CvRubricScoringService
{
    /**
     * Score a rubric by key using manual inputs (counts + selections).
     *
     * @return array{
     *   rubric: array,
     *   total: float,
     *   grade: ?array,
     *   groups: array,
     *   criteria: array,
     * }
     */
    public function score(string $rubricKey, array $inputs): array
    {
        $rubric = DB::table('cv_rubrics')->where('key', $rubricKey)->first();
        if (!$rubric) {
            throw new InvalidArgumentException("Rubric not found: {$rubricKey}");
        }

        return $this->scoreRubricId((int) $rubric->id, $inputs, null);
    }

    /**
     * Score by scoring profile key (e.g. it_dev, it_tester).
     */
    public function scoreProfile(string $profileKey, array $inputs): array
    {
        $profile = DB::table('cv_scoring_profiles')->where('key', $profileKey)->first();
        if (!$profile) {
            throw new InvalidArgumentException("Scoring profile not found: {$profileKey}");
        }

        $overrides = DB::table('cv_scoring_overrides')
            ->where('profile_id', $profile->id)
            ->get();

        $overrideByCode = [];
        foreach ($overrides as $o) {
            $overrideByCode[$o->criterion_code] = [
                'weight' => (float) $o->weight,
                'override_config' => $this->decodeConfig($o->override_config),
            ];
        }

        $result = $this->scoreRubricId((int) $profile->rubric_id, $inputs, $overrideByCode);
        $result['profile'] = [
            'key' => $profile->key,
            'name' => $profile->name,
        ];

        return $result;
    }

    private function scoreRubricId(int $rubricId, array $inputs, ?array $overrideByCode): array
    {
        $rubric = DB::table('cv_rubrics')->where('id', $rubricId)->first();
        if (!$rubric) {
            throw new InvalidArgumentException("Rubric not found by id: {$rubricId}");
        }

        $groups = DB::table('cv_rubric_groups')
            ->where('rubric_id', $rubric->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groupIds = $groups->pluck('id')->all();
        $criteriaRows = DB::table('cv_rubric_criteria')
            ->whereIn('group_id', $groupIds)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groupById = [];
        foreach ($groups as $g) {
            $groupById[$g->id] = [
                'code' => $g->code,
                'name' => $g->name,
                'max_score' => (int) $g->max_score,
                'score' => 0.0,
                'criteria' => [],
            ];
        }

        $criteriaBreakdown = [];
        $total = 0.0;

        foreach ($criteriaRows as $c) {
            $ruleConfig = $this->decodeConfig($c->rule_config);

            $weight = 1.0;
            if ($overrideByCode && isset($overrideByCode[$c->code])) {
                $weight = (float) ($overrideByCode[$c->code]['weight'] ?? 1.0);
                $overrideConfig = (array) ($overrideByCode[$c->code]['override_config'] ?? []);
                if (!empty($overrideConfig)) {
                    // Shallow merge is enough for our current configs
                    $ruleConfig = array_merge($ruleConfig, $overrideConfig);
                }
            }

            $result = $this->evaluateRule((string) $c->rule_type, $ruleConfig, $inputs, (int) $c->max_score);
            $weightedScore = round((float) $result['score'] * $weight, 2);
            $weightedScore = min((float) $c->max_score, $weightedScore);

            $criterion = [
                'code' => $c->code,
                'name' => $c->name,
                'max_score' => (int) $c->max_score,
                'score' => $weightedScore,
                'base_score' => $result['score'],
                'weight' => $weight,
                'input' => $result['input'],
                'details' => $result['details'],
            ];

            $criteriaBreakdown[$c->code] = $criterion;

            $groupById[$c->group_id]['criteria'][] = $criterion;
            $groupById[$c->group_id]['score'] += (float) $weightedScore;
            $total += (float) $weightedScore;
        }

        // Enforce group caps (defensive)
        foreach ($groupById as &$g) {
            $g['score'] = round(min($g['max_score'], max(0, $g['score'])), 2);
        }
        unset($g);

        $total = round(min((int) $rubric->total_max, max(0, $total)), 2);

        $grade = DB::table('cv_rubric_grades')
            ->where('rubric_id', $rubric->id)
            ->where('min_score', '<=', $total)
            ->where(function ($q) use ($total) {
                $q->whereNull('max_score')->orWhere('max_score', '>=', $total);
            })
            ->orderBy('sort_order')
            ->orderBy('min_score')
            ->first();

        return [
            'rubric' => [
                'key' => $rubric->key,
                'name' => $rubric->name,
                'total_max' => (int) $rubric->total_max,
            ],
            'total' => $total,
            'grade' => $grade ? [
                'label' => $grade->label,
                'min' => (int) $grade->min_score,
                'max' => $grade->max_score === null ? null : (int) $grade->max_score,
                'note' => $grade->note,
            ] : null,
            'groups' => array_values($groupById),
            'criteria' => $criteriaBreakdown,
        ];
    }

    private function decodeConfig($ruleConfig): array
    {
        if (is_array($ruleConfig)) {
            return $ruleConfig;
        }

        if (is_string($ruleConfig) && trim($ruleConfig) !== '') {
            $decoded = json_decode($ruleConfig, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * @return array{score: float, input: mixed, details: array}
     */
    private function evaluateRule(string $ruleType, array $config, array $inputs, int $maxScore): array
    {
        return match ($ruleType) {
            'per_unit_cap' => $this->rulePerUnitCap($config, $inputs, $maxScore),
            'weighted_two_inputs_cap' => $this->ruleWeightedTwoInputsCap($config, $inputs, $maxScore),
            'choice_map' => $this->ruleChoiceMap($config, $inputs, $maxScore),
            default => throw new InvalidArgumentException("Unsupported rule_type: {$ruleType}"),
        };
    }

    private function rulePerUnitCap(array $config, array $inputs, int $maxScore): array
    {
        $key = (string) Arr::get($config, 'input_key', '');
        $pointsPerUnit = (float) Arr::get($config, 'points_per_unit', 0);
        $cap = (float) Arr::get($config, 'cap', $maxScore);
        $min = (float) Arr::get($config, 'min', 0);

        $raw = Arr::get($inputs, $key);
        $value = is_numeric($raw) ? (float) $raw : 0.0;
        $value = max($min, $value);

        $score = $value * $pointsPerUnit;
        $score = min($cap, $score);
        $score = min($maxScore, $score);

        return [
            'score' => round(max(0, $score), 2),
            'input' => [$key => $raw],
            'details' => [
                'value' => $value,
                'points_per_unit' => $pointsPerUnit,
                'cap' => $cap,
            ],
        ];
    }

    private function ruleWeightedTwoInputsCap(array $config, array $inputs, int $maxScore): array
    {
        $majorKey = (string) Arr::get($config, 'major_input_key', '');
        $minorKey = (string) Arr::get($config, 'minor_input_key', '');
        $majorPoints = (float) Arr::get($config, 'major_points', 0);
        $minorPoints = (float) Arr::get($config, 'minor_points', 0);
        $cap = (float) Arr::get($config, 'cap', $maxScore);
        $min = (float) Arr::get($config, 'min', 0);

        $rawMajor = Arr::get($inputs, $majorKey);
        $rawMinor = Arr::get($inputs, $minorKey);
        $major = is_numeric($rawMajor) ? (float) $rawMajor : 0.0;
        $minor = is_numeric($rawMinor) ? (float) $rawMinor : 0.0;
        $major = max($min, $major);
        $minor = max($min, $minor);

        $score = ($major * $majorPoints) + ($minor * $minorPoints);
        $score = min($cap, $score);
        $score = min($maxScore, $score);

        return [
            'score' => round(max(0, $score), 2),
            'input' => [$majorKey => $rawMajor, $minorKey => $rawMinor],
            'details' => [
                'major' => $major,
                'minor' => $minor,
                'major_points' => $majorPoints,
                'minor_points' => $minorPoints,
                'cap' => $cap,
            ],
        ];
    }

    private function ruleChoiceMap(array $config, array $inputs, int $maxScore): array
    {
        $key = (string) Arr::get($config, 'input_key', '');
        $choices = (array) Arr::get($config, 'choices', []);
        $raw = Arr::get($inputs, $key);
        $value = is_string($raw) ? $raw : null;

        $score = 0.0;
        if ($value !== null && array_key_exists($value, $choices)) {
            $score = (float) $choices[$value];
        }
        $score = min($maxScore, $score);

        return [
            'score' => round(max(0, $score), 2),
            'input' => [$key => $raw],
            'details' => [
                'selected' => $value,
                'choices' => $choices,
            ],
        ];
    }
}
