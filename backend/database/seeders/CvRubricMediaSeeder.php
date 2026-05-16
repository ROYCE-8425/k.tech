<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CvRubricMediaSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $rubrics = [
                [
                    'key' => 'media_digital_marketing',
                    'name' => 'Truyền thông - Digital Marketing (100 điểm)',
                    'total_max' => 100,
                    'groups' => [
                        [
                            'code' => 'A',
                            'name' => 'Kinh nghiệm & Dự án',
                            'max_score' => 40,
                            'sort' => 10,
                            'criteria' => [
                                [
                                    'code' => 'A1',
                                    'name' => 'Đơn vị công tác',
                                    'max_score' => 20,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'dm_years_experience',
                                        'label' => 'Số năm kinh nghiệm (Agency/In-house)',
                                        'points_per_unit' => 5,
                                        'cap' => 20,
                                        'min' => 0,
                                    ],
                                    'sort' => 10,
                                ],
                                [
                                    'code' => 'A2',
                                    'name' => 'Kinh nghiệm dự án',
                                    'max_score' => 20,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'dm_project_count',
                                        'label' => 'Tổng số dự án thực tế',
                                        'points_per_unit' => 5,
                                        'cap' => 20,
                                        'min' => 0,
                                    ],
                                    'sort' => 20,
                                ],
                            ],
                        ],
                        [
                            'code' => 'B',
                            'name' => 'Kỹ năng & Học vấn',
                            'max_score' => 50,
                            'sort' => 20,
                            'criteria' => [
                                [
                                    'code' => 'B1',
                                    'name' => 'Chạy quảng cáo (Ads)',
                                    'max_score' => 12,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'dm_ads_total_points',
                                        'label' => 'Tổng điểm Ads (FB 5đ + Google 4đ + TikTok 3đ)',
                                        'points_per_unit' => 1,
                                        'cap' => 12,
                                        'min' => 0,
                                    ],
                                    'sort' => 10,
                                ],
                                [
                                    'code' => 'B2',
                                    'name' => 'Công cụ (GA4/GSC/Looker Studio)',
                                    'max_score' => 10,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'dm_tools_total_points',
                                        'label' => 'Tổng điểm công cụ (GA4 4đ + GSC 3đ + Looker 3đ)',
                                        'points_per_unit' => 1,
                                        'cap' => 10,
                                        'min' => 0,
                                    ],
                                    'sort' => 20,
                                ],
                                [
                                    'code' => 'B3',
                                    'name' => 'Tối ưu chiến dịch (ROI/CPA có mô tả)',
                                    'max_score' => 8,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'dm_optimization_has_case',
                                        'label' => 'Có mô tả tối ưu ROI/CPA (case cụ thể)',
                                        'choices' => [
                                            'yes' => 8,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 30,
                                ],
                                [
                                    'code' => 'B4',
                                    'name' => 'Học vấn (Trường)',
                                    'max_score' => 15,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'dm_school_tier',
                                        'label' => 'Xếp hạng trường',
                                        'choices' => [
                                            'top1' => 15,
                                            'top2' => 10,
                                            'other' => 5,
                                        ],
                                        'choice_labels' => [
                                            'top1' => 'Top 1',
                                            'top2' => 'Top 2',
                                            'other' => 'Khác',
                                        ],
                                    ],
                                    'sort' => 40,
                                ],
                                [
                                    'code' => 'B5',
                                    'name' => 'Xếp loại bằng',
                                    'max_score' => 5,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'dm_degree_rank',
                                        'label' => 'Xếp loại',
                                        'choices' => [
                                            'excellent' => 5,
                                            'good' => 3,
                                            'fair' => 1,
                                        ],
                                        'choice_labels' => [
                                            'excellent' => 'Xuất sắc',
                                            'good' => 'Giỏi',
                                            'fair' => 'Khá',
                                        ],
                                    ],
                                    'sort' => 50,
                                ],
                            ],
                        ],
                        [
                            'code' => 'C',
                            'name' => 'Trình bày & Bonus',
                            'max_score' => 10,
                            'sort' => 30,
                            'criteria' => [
                                [
                                    'code' => 'C1',
                                    'name' => 'Chứng chỉ quốc tế',
                                    'max_score' => 10,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'dm_international_cert_count',
                                        'label' => 'Số chứng chỉ (Google/Meta/HubSpot)',
                                        'points_per_unit' => 3.3,
                                        'cap' => 10,
                                        'min' => 0,
                                    ],
                                    'sort' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'media_content_marketing',
                    'name' => 'Truyền thông - Content Marketing (100 điểm)',
                    'total_max' => 100,
                    'groups' => [
                        [
                            'code' => 'A',
                            'name' => 'Kinh nghiệm & Dự án',
                            'max_score' => 40,
                            'sort' => 10,
                            'criteria' => [
                                [
                                    'code' => 'A1',
                                    'name' => 'Đơn vị công tác',
                                    'max_score' => 20,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'cm_years_experience',
                                        'label' => 'Số năm kinh nghiệm (Agency/In-house)',
                                        'points_per_unit' => 5,
                                        'cap' => 20,
                                        'min' => 0,
                                    ],
                                    'sort' => 10,
                                ],
                                [
                                    'code' => 'A2',
                                    'name' => 'Portfolio thực tế',
                                    'max_score' => 10,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'cm_portfolio_count',
                                        'label' => 'Số sản phẩm (Articles/Scripts/Designs)',
                                        'points_per_unit' => 2,
                                        'cap' => 10,
                                        'min' => 0,
                                    ],
                                    'sort' => 20,
                                ],
                                [
                                    'code' => 'A3',
                                    'name' => 'Freelance (đo lường)',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'cm_freelance_measurable',
                                        'label' => 'Có kết quả đo lường (views/shares/traffic)',
                                        'choices' => [
                                            'yes' => 10,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 30,
                                ],
                            ],
                        ],
                        [
                            'code' => 'B',
                            'name' => 'Kỹ năng & Học vấn',
                            'max_score' => 50,
                            'sort' => 20,
                            'criteria' => [
                                [
                                    'code' => 'B1',
                                    'name' => 'Kỹ năng viết (Copywriting/Content Writing)',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'cm_writing_skill',
                                        'label' => 'Kỹ năng viết',
                                        'choices' => [
                                            'strong' => 10,
                                            'basic' => 5,
                                            'none' => 0,
                                        ],
                                        'choice_labels' => [
                                            'strong' => 'Tốt',
                                            'basic' => 'Cơ bản',
                                            'none' => 'Chưa rõ',
                                        ],
                                    ],
                                    'sort' => 10,
                                ],
                                [
                                    'code' => 'B2',
                                    'name' => 'Kỹ năng Visual',
                                    'max_score' => 8,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'cm_visual_tools_points',
                                        'label' => 'Tổng điểm công cụ (Canva 3đ + CapCut 3đ + Photoshop 2đ)',
                                        'points_per_unit' => 1,
                                        'cap' => 8,
                                        'min' => 0,
                                    ],
                                    'sort' => 20,
                                ],
                                [
                                    'code' => 'B3',
                                    'name' => 'SEO & Social',
                                    'max_score' => 12,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'cm_seo_social_points',
                                        'label' => 'Tổng điểm (Nghiên cứu từ khóa 6đ + Quản trị Page 6đ)',
                                        'points_per_unit' => 1,
                                        'cap' => 12,
                                        'min' => 0,
                                    ],
                                    'sort' => 30,
                                ],
                                [
                                    'code' => 'B4',
                                    'name' => 'Học vấn (Trường)',
                                    'max_score' => 15,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'cm_school_tier',
                                        'label' => 'Xếp hạng trường',
                                        'choices' => [
                                            'top1' => 15,
                                            'top2' => 10,
                                            'other' => 5,
                                        ],
                                        'choice_labels' => [
                                            'top1' => 'Top 1',
                                            'top2' => 'Top 2',
                                            'other' => 'Khác',
                                        ],
                                    ],
                                    'sort' => 40,
                                ],
                                [
                                    'code' => 'B5',
                                    'name' => 'Xếp loại bằng',
                                    'max_score' => 5,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'cm_degree_rank',
                                        'label' => 'Xếp loại',
                                        'choices' => [
                                            'excellent' => 5,
                                            'good' => 3,
                                            'fair' => 1,
                                        ],
                                        'choice_labels' => [
                                            'excellent' => 'Xuất sắc',
                                            'good' => 'Giỏi',
                                            'fair' => 'Khá',
                                        ],
                                    ],
                                    'sort' => 50,
                                ],
                            ],
                        ],
                        [
                            'code' => 'C',
                            'name' => 'Trình bày & Bonus',
                            'max_score' => 10,
                            'sort' => 30,
                            'criteria' => [
                                [
                                    'code' => 'C1',
                                    'name' => 'Kênh cá nhân',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'cm_personal_channel',
                                        'label' => 'Có kênh cá nhân (Blog/TikTok > 5k followers)',
                                        'choices' => [
                                            'yes' => 10,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'media_pr_event',
                    'name' => 'Truyền thông - PR & Event (100 điểm)',
                    'total_max' => 100,
                    'groups' => [
                        [
                            'code' => 'A',
                            'name' => 'Kinh nghiệm & Dự án',
                            'max_score' => 40,
                            'sort' => 10,
                            'criteria' => [
                                [
                                    'code' => 'A1',
                                    'name' => 'Đơn vị công tác',
                                    'max_score' => 20,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'pr_years_experience',
                                        'label' => 'Số năm kinh nghiệm (PR Agency/In-house)',
                                        'points_per_unit' => 5,
                                        'cap' => 20,
                                        'min' => 0,
                                    ],
                                    'sort' => 10,
                                ],
                                [
                                    'code' => 'A2',
                                    'name' => 'Quy mô sự kiện/dự án',
                                    'max_score' => 20,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'pr_event_project_count',
                                        'label' => 'Số sự kiện/dự án thực tế',
                                        'points_per_unit' => 2,
                                        'cap' => 20,
                                        'min' => 0,
                                    ],
                                    'sort' => 20,
                                ],
                            ],
                        ],
                        [
                            'code' => 'B',
                            'name' => 'Kỹ năng & Học vấn',
                            'max_score' => 50,
                            'sort' => 20,
                            'criteria' => [
                                [
                                    'code' => 'B1',
                                    'name' => 'Media Relations',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'pr_media_relations',
                                        'label' => 'Press Release / Quản lý Media List',
                                        'choices' => [
                                            'yes' => 10,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 10,
                                ],
                                [
                                    'code' => 'B2',
                                    'name' => 'Event Operation',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'pr_event_operation',
                                        'label' => 'Lập kế hoạch & vận hành sự kiện',
                                        'choices' => [
                                            'yes' => 10,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 20,
                                ],
                                [
                                    'code' => 'B3',
                                    'name' => 'Soft Skills',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'pr_soft_skills',
                                        'label' => 'Đàm phán / Networking',
                                        'choices' => [
                                            'yes' => 10,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 30,
                                ],
                                [
                                    'code' => 'B4',
                                    'name' => 'Học vấn (Trường)',
                                    'max_score' => 15,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'pr_school_tier',
                                        'label' => 'Xếp hạng trường',
                                        'choices' => [
                                            'top1' => 15,
                                            'top2' => 10,
                                            'top3' => 7,
                                            'other' => 3,
                                        ],
                                        'choice_labels' => [
                                            'top1' => 'Top 1',
                                            'top2' => 'Top 2',
                                            'top3' => 'Top 3',
                                            'other' => 'Khác',
                                        ],
                                    ],
                                    'sort' => 40,
                                ],
                                [
                                    'code' => 'B5',
                                    'name' => 'Xếp loại bằng',
                                    'max_score' => 5,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'pr_degree_rank',
                                        'label' => 'Xếp loại',
                                        'choices' => [
                                            'excellent' => 5,
                                            'good' => 3,
                                            'fair' => 1,
                                        ],
                                        'choice_labels' => [
                                            'excellent' => 'Xuất sắc',
                                            'good' => 'Giỏi',
                                            'fair' => 'Khá',
                                        ],
                                    ],
                                    'sort' => 50,
                                ],
                            ],
                        ],
                        [
                            'code' => 'C',
                            'name' => 'Trình bày & Bonus',
                            'max_score' => 10,
                            'sort' => 30,
                            'criteria' => [
                                [
                                    'code' => 'C1',
                                    'name' => 'Ngoại ngữ',
                                    'max_score' => 10,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'pr_language_cert_count',
                                        'label' => 'Số chứng chỉ (IELTS/TOEIC/...)',
                                        'points_per_unit' => 2.5,
                                        'cap' => 10,
                                        'min' => 0,
                                    ],
                                    'sort' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'media_social_media',
                    'name' => 'Truyền thông - Social Media Management (100 điểm)',
                    'total_max' => 100,
                    'groups' => [
                        [
                            'code' => 'A',
                            'name' => 'Kinh nghiệm & Dự án',
                            'max_score' => 50,
                            'sort' => 10,
                            'criteria' => [
                                [
                                    'code' => 'A1',
                                    'name' => 'Đơn vị công tác',
                                    'max_score' => 30,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'smm_years_experience',
                                        'label' => 'Số năm kinh nghiệm (Media/Agency/In-house)',
                                        'points_per_unit' => 5,
                                        'cap' => 30,
                                        'min' => 0,
                                    ],
                                    'sort' => 10,
                                ],
                                [
                                    'code' => 'A2',
                                    'name' => 'Quy mô kênh',
                                    'max_score' => 20,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'smm_channel_scale',
                                        'label' => 'Quản lý Page/Group > 200k followers',
                                        'choices' => [
                                            'yes' => 20,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 20,
                                ],
                            ],
                        ],
                        [
                            'code' => 'B',
                            'name' => 'Kỹ năng & Học vấn',
                            'max_score' => 40,
                            'sort' => 20,
                            'criteria' => [
                                [
                                    'code' => 'B1',
                                    'name' => 'Viral Content',
                                    'max_score' => 12,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'smm_viral_content',
                                        'label' => 'Tư duy bắt trend/sáng tạo nội dung',
                                        'choices' => [
                                            'yes' => 12,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 10,
                                ],
                                [
                                    'code' => 'B2',
                                    'name' => 'Community Build',
                                    'max_score' => 5,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'smm_community_build',
                                        'label' => 'Xây dựng & tương tác cộng đồng',
                                        'choices' => [
                                            'yes' => 5,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 20,
                                ],
                                [
                                    'code' => 'B3',
                                    'name' => 'Quản trị rủi ro',
                                    'max_score' => 8,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'smm_risk_management',
                                        'label' => 'Xử lý khủng hoảng trên môi trường số',
                                        'choices' => [
                                            'yes' => 8,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 30,
                                ],
                                [
                                    'code' => 'B4',
                                    'name' => 'Học vấn (Trường)',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'smm_school',
                                        'label' => 'Ngành liên quan (Xã hội/Báo chí/Marketing)',
                                        'choices' => [
                                            'yes' => 10,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 40,
                                ],
                                [
                                    'code' => 'B5',
                                    'name' => 'Xếp loại bằng',
                                    'max_score' => 5,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'smm_degree_rank',
                                        'label' => 'Xếp loại',
                                        'choices' => [
                                            'excellent' => 5,
                                            'good' => 3,
                                            'fair' => 1,
                                        ],
                                        'choice_labels' => [
                                            'excellent' => 'Xuất sắc',
                                            'good' => 'Giỏi',
                                            'fair' => 'Khá',
                                        ],
                                    ],
                                    'sort' => 50,
                                ],
                            ],
                        ],
                        [
                            'code' => 'C',
                            'name' => 'Trình bày & Bonus',
                            'max_score' => 10,
                            'sort' => 30,
                            'criteria' => [
                                [
                                    'code' => 'C1',
                                    'name' => 'Admin Experience',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'smm_admin_experience',
                                        'label' => 'Kinh nghiệm Admin cộng đồng lớn/TikTok',
                                        'choices' => [
                                            'yes' => 10,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'media_creative_design',
                    'name' => 'Truyền thông - Creative Design (100 điểm)',
                    'total_max' => 100,
                    'groups' => [
                        [
                            'code' => 'A',
                            'name' => 'Kinh nghiệm & Dự án',
                            'max_score' => 50,
                            'sort' => 10,
                            'criteria' => [
                                [
                                    'code' => 'A1',
                                    'name' => 'Đơn vị công tác',
                                    'max_score' => 30,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'cd_years_experience',
                                        'label' => 'Số năm kinh nghiệm (Creative Agency/Production House)',
                                        'points_per_unit' => 5,
                                        'cap' => 30,
                                        'min' => 0,
                                    ],
                                    'sort' => 10,
                                ],
                                [
                                    'code' => 'A2',
                                    'name' => 'Portfolio chất lượng',
                                    'max_score' => 20,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'cd_portfolio_quality',
                                        'label' => 'Link Behance/Dribbble chuyên nghiệp',
                                        'choices' => [
                                            'pro' => 20,
                                            'ok' => 10,
                                            'none' => 0,
                                        ],
                                        'choice_labels' => [
                                            'pro' => 'Tốt (chuyên nghiệp)',
                                            'ok' => 'Trung bình',
                                            'none' => 'Không có',
                                        ],
                                    ],
                                    'sort' => 20,
                                ],
                            ],
                        ],
                        [
                            'code' => 'B',
                            'name' => 'Kỹ năng & Học vấn',
                            'max_score' => 40,
                            'sort' => 20,
                            'criteria' => [
                                [
                                    'code' => 'B1',
                                    'name' => 'Tool Adobe',
                                    'max_score' => 15,
                                    'rule_type' => 'per_unit_cap',
                                    'rule_config' => [
                                        'input_key' => 'cd_adobe_tool_count',
                                        'label' => 'Số công cụ Adobe thành thạo (PS/AI/ID/Premiere)',
                                        'points_per_unit' => 5,
                                        'cap' => 15,
                                        'min' => 0,
                                    ],
                                    'sort' => 10,
                                ],
                                [
                                    'code' => 'B2',
                                    'name' => 'Design Thinking',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'cd_design_thinking',
                                        'label' => 'Layout/Branding/Visual Storytelling',
                                        'choices' => [
                                            'yes' => 10,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 20,
                                ],
                                [
                                    'code' => 'B3',
                                    'name' => 'Học vấn (Trường)',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'cd_school_tier',
                                        'label' => 'Trường (Mỹ thuật/Kiến trúc/Arena/...)',
                                        'choices' => [
                                            'top1' => 10,
                                            'top2' => 7,
                                            'other' => 3,
                                        ],
                                        'choice_labels' => [
                                            'top1' => 'Top 1',
                                            'top2' => 'Top 2/Top 3',
                                            'other' => 'Khác',
                                        ],
                                    ],
                                    'sort' => 30,
                                ],
                                [
                                    'code' => 'B4',
                                    'name' => 'Thành tích đồ án',
                                    'max_score' => 5,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'cd_project_achievement',
                                        'label' => 'Đồ án xuất sắc / Giải thưởng / Nghiên cứu khoa học',
                                        'choices' => [
                                            'yes' => 5,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 40,
                                ],
                            ],
                        ],
                        [
                            'code' => 'C',
                            'name' => 'Trình bày & Bonus',
                            'max_score' => 10,
                            'sort' => 30,
                            'criteria' => [
                                [
                                    'code' => 'C1',
                                    'name' => 'Giải thưởng lớn',
                                    'max_score' => 10,
                                    'rule_type' => 'choice_map',
                                    'rule_config' => [
                                        'input_key' => 'cd_big_award',
                                        'label' => 'Giải thưởng (Vietnam Young Lions/Golden Bell/...)',
                                        'choices' => [
                                            'yes' => 10,
                                            'no' => 0,
                                        ],
                                        'choice_labels' => [
                                            'yes' => 'Có',
                                            'no' => 'Không',
                                        ],
                                    ],
                                    'sort' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            foreach ($rubrics as $r) {
                DB::table('cv_rubrics')->updateOrInsert(
                    ['key' => $r['key']],
                    [
                        'name' => $r['name'],
                        'total_max' => $r['total_max'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $rubric = DB::table('cv_rubrics')->where('key', $r['key'])->first();
                $rubricId = $rubric->id;

                DB::table('cv_rubric_grades')->where('rubric_id', $rubricId)->delete();
                $existingGroupIds = DB::table('cv_rubric_groups')->where('rubric_id', $rubricId)->pluck('id')->all();
                if (!empty($existingGroupIds)) {
                    DB::table('cv_rubric_criteria')->whereIn('group_id', $existingGroupIds)->delete();
                }
                DB::table('cv_rubric_groups')->where('rubric_id', $rubricId)->delete();

                foreach ($r['groups'] as $g) {
                    $groupId = DB::table('cv_rubric_groups')->insertGetId([
                        'rubric_id' => $rubricId,
                        'code' => $g['code'],
                        'name' => $g['name'],
                        'max_score' => $g['max_score'],
                        'sort_order' => $g['sort'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($g['criteria'] as $c) {
                        DB::table('cv_rubric_criteria')->insert([
                            'group_id' => $groupId,
                            'code' => $c['code'],
                            'name' => $c['name'],
                            'max_score' => $c['max_score'],
                            'rule_type' => $c['rule_type'],
                            'rule_config' => json_encode($c['rule_config'], JSON_UNESCAPED_UNICODE),
                            'sort_order' => $c['sort'],
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
            }
        });
    }
}
