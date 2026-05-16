<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Candidate;
use Illuminate\Support\Facades\Hash;

class PerfectCandidateSeeder extends Seeder
{
    public function run(): void
    {
        // Update or create user ungvien2
        $user = User::updateOrCreate(
            ['email' => 'ungvien2@test.com'],
            [
                'name' => 'Nguyễn Văn Minh',
                'phone' => '0912345678',
                'role' => 'candidate',
                'password' => Hash::make('123456'),
                'email_verified_at' => now(),
            ]
        );

        // Perfect candidate profile with all standardized fields
        $profileData = [
            'sector' => 'it',
            'primary_role' => 'Senior Backend Developer',
            'skills' => [
                'PHP',
                'Laravel',
                'REST API',
                'MySQL',
                'Redis',
                'Docker',
                'Git',
                'AWS',
                'Microservices',
                'TDD'
            ],
            'cv_quick' => [
                'self_description' => 'Senior Backend Developer với 5 năm kinh nghiệm phát triển hệ thống quy mô lớn. Chuyên sâu về PHP/Laravel, thiết kế microservices và tối ưu hóa database. Đã xây dựng và maintain hệ thống xử lý hơn 10 triệu requests/ngày. Đam mê công nghệ mới, luôn cập nhật best practices và chia sẻ kiến thức cho team.',
                
                'education' => [
                    [
                        'school' => 'ĐH Bách Khoa Hà Nội',
                        'degree_level' => 'ky_su',
                        'major' => 'Công nghệ thông tin',
                        'graduation_year' => 2018
                    ],
                    [
                        'school' => 'ĐH Bách Khoa Hà Nội',
                        'degree_level' => 'thac_si',
                        'major' => 'Khoa học máy tính',
                        'graduation_year' => 2020
                    ]
                ],
                
                'work_experiences' => [
                    [
                        'company_name' => 'FPT Software',
                        'position_title' => 'Senior Backend Developer',
                        'start_date' => '2021-01-01',
                        'end_date' => null,
                        'is_current' => true,
                        'description' => 'Phụ trách phát triển và maintain backend cho dự án E-commerce quy mô 5M+ users. Thiết kế kiến trúc microservices, tối ưu hóa API performance (giảm 60% response time). Implement Redis caching, queue system với Laravel. Mentor 3 junior developers, code review và đảm bảo code quality. Tech stack: Laravel 10, MySQL, Redis, Docker, AWS.'
                    ],
                    [
                        'company_name' => 'VNG Corporation',
                        'position_title' => 'Backend Developer',
                        'start_date' => '2019-06-01',
                        'end_date' => '2020-12-31',
                        'is_current' => false,
                        'description' => 'Phát triển RESTful API cho ứng dụng social network. Xây dựng real-time notification system với Laravel Echo và Redis. Implement authentication với JWT, OAuth2. Optimize database queries, tăng 40% performance. Collaborate với frontend team và mobile team để deliver features on time.'
                    ],
                    [
                        'company_name' => 'Tiki Corporation',
                        'position_title' => 'Junior Backend Developer',
                        'start_date' => '2018-03-01',
                        'end_date' => '2019-05-31',
                        'is_current' => false,
                        'description' => 'Phát triển tính năng cart, order management cho nền tảng e-commerce. Viết API endpoints, integrate với payment gateway. Fix bugs, improve code quality. Học được Laravel framework, MySQL optimization, và agile workflow.'
                    ]
                ],
                
                'skills' => [
                    'hard' => [
                        ['name' => 'PHP', 'level' => 5],
                        ['name' => 'Laravel', 'level' => 5],
                        ['name' => 'REST API', 'level' => 5],
                        ['name' => 'MySQL', 'level' => 5],
                        ['name' => 'PostgreSQL', 'level' => 4],
                        ['name' => 'Redis', 'level' => 5],
                        ['name' => 'Docker', 'level' => 4],
                        ['name' => 'Git', 'level' => 5],
                        ['name' => 'AWS (EC2, S3, RDS)', 'level' => 4],
                        ['name' => 'Microservices Architecture', 'level' => 4],
                        ['name' => 'Queue System (Laravel Queue)', 'level' => 5],
                        ['name' => 'TDD/Unit Testing', 'level' => 4],
                        ['name' => 'Linux/Ubuntu', 'level' => 4],
                        ['name' => 'Nginx', 'level' => 4],
                        ['name' => 'CI/CD', 'level' => 3]
                    ],
                    'soft' => [
                        ['name' => 'Tư duy phân tích', 'level' => 5],
                        ['name' => 'Tư duy hệ thống', 'level' => 5],
                        ['name' => 'Giao tiếp', 'level' => 5],
                        ['name' => 'Làm việc nhóm', 'level' => 5],
                        ['name' => 'Giải quyết vấn đề', 'level' => 5],
                        ['name' => 'Tự học', 'level' => 5],
                        ['name' => 'Quản lý thời gian', 'level' => 4],
                        ['name' => 'Mentoring', 'level' => 4],
                        ['name' => 'Code Review', 'level' => 5],
                        ['name' => 'Làm việc áp lực cao', 'level' => 4]
                    ]
                ],
                
                'certifications' => [
                    'english_level' => 'advanced',
                    'toeic_score' => 890,
                    'ielts_score' => 7.5,
                    'years_experience' => 5.5,
                    'certifications' => [
                        'aws_certified',
                        'oracle_java',
                        'pmp'
                    ]
                ]
            ]
        ];

        // Create/update candidate
        $candidate = Candidate::updateOrCreate(
            ['email' => 'ungvien2@test.com'],
            [
                'user_id' => $user->id,
                'name' => 'Nguyễn Văn Minh',
                'phone' => '0912345678',
                'sector' => 'it',
                'skills' => 'PHP, Laravel, REST API, MySQL, Redis, Docker, Git, AWS, Microservices, TDD',
                'experience' => '3-5 năm',
                'education' => 'Thạc sĩ',
                'summary' => 'Senior Backend Developer với 5 năm kinh nghiệm. Chuyên sâu về PHP/Laravel, thiết kế microservices và tối ưu hóa database. TOEIC 890, IELTS 7.5, AWS Certified.',
                'profile_data' => $profileData,
                'portfolio_url' => 'https://github.com/nguyenvanminh',
                'github_url' => 'https://github.com/nguyenvanminh',
                'linkedin_url' => 'https://www.linkedin.com/in/nguyenvanminh',
            ]
        );

        $this->command->info('✅ Đã tạo ứng viên hoàn hảo: ungvien2@test.com / 123456');
        $this->command->info('📊 User ID: ' . $user->id . ', Candidate ID: ' . $candidate->id);
        $this->command->info('📊 Profile: Senior Backend Developer, 5.5 năm kinh nghiệm');
        $this->command->info('🎓 Học vấn: Thạc sĩ CNTT từ ĐH Bách Khoa HN');
        $this->command->info('💼 Kinh nghiệm: FPT (hiện tại), VNG, Tiki');
        $this->command->info('🔧 Skills: 15 Hard Skills (5/5), 10 Soft Skills');
        $this->command->info('🌐 English: Advanced, TOEIC 890, IELTS 7.5');
        $this->command->info('🎓 Certs: AWS, Oracle Java, PMP');
        
        // Verify data was saved
        $candidate->refresh();
        $cvQuickData = $candidate->profile_data['cv_quick'] ?? null;
        if ($cvQuickData) {
            $this->command->info('✓ CV Quick data saved successfully!');
            $this->command->info('  - Education: ' . count($cvQuickData['education'] ?? []));
            $this->command->info('  - Work: ' . count($cvQuickData['work_experiences'] ?? []));
            $this->command->info('  - Hard Skills: ' . count($cvQuickData['skills']['hard'] ?? []));
            $this->command->info('  - Certifications: ' . ($cvQuickData['certifications']['english_level'] ?? 'none'));
        } else {
            $this->command->error('⚠️ WARNING: profile_data[cv_quick] is EMPTY after save!');
        }
    }
}
