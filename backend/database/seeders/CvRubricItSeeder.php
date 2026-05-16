<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CvRubricItSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $rubricId = DB::table('cv_rubrics')->updateOrInsert(
                ['key' => 'it'],
                [
                    'name' => 'CNTT (IT) - Bảng chấm CV (100 điểm)',
                    'total_max' => 100,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            // In MySQL, updateOrInsert returns bool. Fetch ID reliably.
            $rubric = DB::table('cv_rubrics')->where('key', 'it')->first();
            $rubricId = $rubric->id;

            // Clear existing children for idempotent seeding.
            DB::table('cv_rubric_grades')->where('rubric_id', $rubricId)->delete();
            $groupIds = DB::table('cv_rubric_groups')->where('rubric_id', $rubricId)->pluck('id')->all();
            if (!empty($groupIds)) {
                DB::table('cv_rubric_criteria')->whereIn('group_id', $groupIds)->delete();
            }
            DB::table('cv_rubric_groups')->where('rubric_id', $rubricId)->delete();

            $groups = [
                [
                    'code' => 'A',
                    'name' => 'Kinh nghiệm & Dự án',
                    'max_score' => 40,
                    'sort_order' => 10,
                    'criteria' => [
                        [
                            'code' => 'A1',
                            'name' => 'Số năm kinh nghiệm',
                            'max_score' => 15,
                            'rule_type' => 'per_unit_cap',
                            'rule_config' => [
                                'input_key' => 'years_experience',
                                'label' => 'Số năm kinh nghiệm (năm)',
                                'points_per_unit' => 3,
                                'cap' => 15,
                                'unit' => 'year',
                                'min' => 0,
                            ],
                            'sort_order' => 10,
                        ],
                        [
                            'code' => 'A2',
                            'name' => 'Dự án tiêu biểu',
                            'max_score' => 15,
                            'rule_type' => 'per_unit_cap',
                            'rule_config' => [
                                'input_key' => 'matching_projects',
                                'label' => 'Số dự án tiêu biểu phù hợp',
                                'points_per_unit' => 5,
                                'cap' => 15,
                                'unit' => 'project',
                                'min' => 0,
                            ],
                            'sort_order' => 20,
                        ],
                        [
                            'code' => 'A3',
                            'name' => 'Công nghệ phù hợp',
                            'max_score' => 10,
                            'rule_type' => 'per_unit_cap',
                            'rule_config' => [
                                'input_key' => 'matching_technologies',
                                'label' => 'Số công nghệ/stack phù hợp',
                                'points_per_unit' => 2,
                                'cap' => 10,
                                'unit' => 'technology',
                                'min' => 0,
                            ],
                            'sort_order' => 30,
                        ],
                    ],
                ],
                [
                    'code' => 'B',
                    'name' => 'Kỹ năng & Học vấn',
                    'max_score' => 35,
                    'sort_order' => 20,
                    'criteria' => [
                        [
                            'code' => 'B1',
                            'name' => 'Kỹ năng cứng (Technical Skills)',
                            'max_score' => 20,
                            'rule_type' => 'weighted_two_inputs_cap',
                            'rule_config' => [
                                'major_input_key' => 'major_skill_matches',
                                'minor_input_key' => 'minor_skill_matches',
                                'major_label' => 'Số kỹ năng chính phù hợp',
                                'minor_label' => 'Số kỹ năng phụ phù hợp',
                                'major_points' => 4,
                                'minor_points' => 2,
                                'cap' => 20,
                                'min' => 0,
                            ],
                            'sort_order' => 10,
                        ],
                        [
                            'code' => 'B2',
                            'name' => 'Học vấn',
                            'max_score' => 10,
                            'rule_type' => 'choice_map',
                            'rule_config' => [
                                'input_key' => 'education_level',
                                'label' => 'Trình độ/Ngành học',
                                'choices' => [
                                    'cs' => 10,
                                    'related' => 6,
                                    'other' => 3,
                                ],
                                'choice_labels' => [
                                    'cs' => 'CNTT / Khoa học máy tính',
                                    'related' => 'Ngành liên quan',
                                    'other' => 'Ngành khác',
                                ],
                            ],
                            'sort_order' => 20,
                        ],
                        [
                            'code' => 'B3',
                            'name' => 'Chứng chỉ',
                            'max_score' => 5,
                            'rule_type' => 'per_unit_cap',
                            'rule_config' => [
                                'input_key' => 'professional_cert_count',
                                'label' => 'Số chứng chỉ chuyên môn',
                                'points_per_unit' => 2.5,
                                'cap' => 5,
                                'unit' => 'certificate',
                                'min' => 0,
                            ],
                            'sort_order' => 30,
                        ],
                    ],
                ],
                [
                    'code' => 'C',
                    'name' => 'Trình bày & Kỹ năng mềm',
                    'max_score' => 25,
                    'sort_order' => 30,
                    'criteria' => [
                        [
                            'code' => 'C1',
                            'name' => 'Cấu trúc CV',
                            'max_score' => 10,
                            'rule_type' => 'choice_map',
                            'rule_config' => [
                                'input_key' => 'cv_structure',
                                'label' => 'Cấu trúc CV',
                                'choices' => [
                                    'good' => 10,
                                    'fair' => 6,
                                    'poor' => 2,
                                ],
                                'choice_labels' => [
                                    'good' => 'Tốt (rõ ràng, logic)',
                                    'fair' => 'Trung bình',
                                    'poor' => 'Kém (rối/thiếu mục)',
                                ],
                            ],
                            'sort_order' => 10,
                        ],
                        [
                            'code' => 'C2',
                            'name' => 'Kỹ năng mềm thể hiện (có ví dụ)',
                            'max_score' => 10,
                            'rule_type' => 'per_unit_cap',
                            'rule_config' => [
                                'input_key' => 'soft_skills_with_examples',
                                'label' => 'Số kỹ năng mềm có ví dụ minh hoạ',
                                'points_per_unit' => 3,
                                'cap' => 10,
                                'unit' => 'skill',
                                'min' => 0,
                            ],
                            'sort_order' => 20,
                        ],
                        [
                            'code' => 'C3',
                            'name' => 'Portfolio/GitHub',
                            'max_score' => 5,
                            'rule_type' => 'choice_map',
                            'rule_config' => [
                                'input_key' => 'portfolio_quality',
                                'label' => 'Portfolio/GitHub',
                                'choices' => [
                                    'good' => 5,
                                    'weak' => 2,
                                    'none' => 0,
                                ],
                                'choice_labels' => [
                                    'good' => 'Tốt (có project rõ ràng)',
                                    'weak' => 'Yếu (ít/khó đánh giá)',
                                    'none' => 'Không có',
                                ],
                            ],
                            'sort_order' => 30,
                        ],
                    ],
                ],
            ];

            foreach ($groups as $group) {
                $groupId = DB::table('cv_rubric_groups')->insertGetId([
                    'rubric_id' => $rubricId,
                    'code' => $group['code'],
                    'name' => $group['name'],
                    'max_score' => $group['max_score'],
                    'sort_order' => $group['sort_order'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($group['criteria'] as $criterion) {
                    DB::table('cv_rubric_criteria')->insert([
                        'group_id' => $groupId,
                        'code' => $criterion['code'],
                        'name' => $criterion['name'],
                        'max_score' => $criterion['max_score'],
                        'rule_type' => $criterion['rule_type'],
                        'rule_config' => json_encode($criterion['rule_config'], JSON_UNESCAPED_UNICODE),
                        'sort_order' => $criterion['sort_order'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $grades = [
                ['label' => 'Xuất sắc', 'min' => 90, 'max' => 100, 'note' => 'Phỏng vấn ngay', 'sort' => 10],
                ['label' => 'Tốt', 'min' => 75, 'max' => 89, 'note' => 'Ưu tiên phỏng vấn', 'sort' => 20],
                ['label' => 'Khá', 'min' => 60, 'max' => 74, 'note' => 'Xem xét thêm', 'sort' => 30],
                ['label' => 'Không phù hợp', 'min' => 0, 'max' => 59, 'note' => null, 'sort' => 40],
            ];

            foreach ($grades as $grade) {
                DB::table('cv_rubric_grades')->insert([
                    'rubric_id' => $rubricId,
                    'label' => $grade['label'],
                    'min_score' => $grade['min'],
                    'max_score' => $grade['max'],
                    'note' => $grade['note'],
                    'sort_order' => $grade['sort'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
