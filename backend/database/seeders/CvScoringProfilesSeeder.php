<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CvScoringProfilesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $itRubric = DB::table('cv_rubrics')->where('key', 'it')->first();

            $profiles = [];

            if ($itRubric) {
                $profiles = array_merge($profiles, [
                [
                    'key' => 'it_dev',
                    'name' => 'CNTT - Dev (ưu tiên kinh nghiệm & kỹ năng code)',
                    'rubric_key' => 'it',
                    // weights by criterion code
                    'weights' => [
                        'A1' => 1.20,
                        'A2' => 1.10,
                        'A3' => 1.20,
                        'B1' => 1.25,
                        'B2' => 1.00,
                        'B3' => 1.00,
                        'C1' => 0.90,
                        'C2' => 0.90,
                        'C3' => 0.95,
                    ],
                ],
                [
                    'key' => 'it_tester',
                    'name' => 'CNTT - Tester (ưu tiên rõ ràng, soft skills, ít nặng code)',
                    'rubric_key' => 'it',
                    'weights' => [
                        'A1' => 1.00,
                        'A2' => 1.10,
                        'A3' => 0.95,
                        'B1' => 0.75,
                        'B2' => 1.00,
                        'B3' => 1.00,
                        'C1' => 1.25,
                        'C2' => 1.20,
                        'C3' => 1.05,
                    ],
                ],
                ]);
            }

            // Truyền thông (mỗi rubric 1 profile mặc định, weight=1.0)
            $mediaRubricKeys = [
                'media_digital_marketing' => 'Truyền thông - Digital Marketing',
                'media_content_marketing' => 'Truyền thông - Content Marketing',
                'media_pr_event' => 'Truyền thông - PR & Event',
                'media_social_media' => 'Truyền thông - Social Media',
                'media_creative_design' => 'Truyền thông - Creative Design',
            ];

            foreach ($mediaRubricKeys as $rk => $label) {
                $rubric = DB::table('cv_rubrics')->where('key', $rk)->first();
                if (!$rubric) {
                    continue;
                }

                $profiles[] = [
                    'key' => $rk . '_default',
                    'name' => $label . ' (mặc định)',
                    'rubric_key' => $rk,
                    'weights' => [],
                ];
            }

            foreach ($profiles as $profile) {
                $rubric = DB::table('cv_rubrics')->where('key', $profile['rubric_key'])->first();
                if (!$rubric) {
                    continue;
                }

                $groupIds = DB::table('cv_rubric_groups')
                    ->where('rubric_id', $rubric->id)
                    ->pluck('id')
                    ->all();

                $allCriterionCodes = [];
                if (!empty($groupIds)) {
                    $allCriterionCodes = DB::table('cv_rubric_criteria')
                        ->whereIn('group_id', $groupIds)
                        ->pluck('code')
                        ->unique()
                        ->values()
                        ->all();
                }

                DB::table('cv_scoring_profiles')->updateOrInsert(
                    ['key' => $profile['key']],
                    [
                        'rubric_id' => $rubric->id,
                        'name' => $profile['name'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $p = DB::table('cv_scoring_profiles')->where('key', $profile['key'])->first();
                DB::table('cv_scoring_overrides')->where('profile_id', $p->id)->delete();

                // Seed override rows for ALL criteria so analytics/trend has enough data.
                // If a criterion has no specific weight in profile config, keep default 1.000.
                $weights = is_array($profile['weights'] ?? null) ? $profile['weights'] : [];
                foreach ($allCriterionCodes as $criterionCode) {
                    $weight = array_key_exists($criterionCode, $weights) ? (float) $weights[$criterionCode] : 1.000;

                    DB::table('cv_scoring_overrides')->insert([
                        'profile_id' => $p->id,
                        'criterion_code' => $criterionCode,
                        'weight' => $weight,
                        'override_config' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}
