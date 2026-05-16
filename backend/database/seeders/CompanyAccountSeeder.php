<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Job;
use App\Models\Candidate;
use App\Models\Application;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanyAccountSeeder extends Seeder
{
    public function run(): void
    {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "🏢 CREATING COMPANY ACCOUNT & JOBS\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        // Create company user account
        $user = User::updateOrCreate(
            ['email' => 'doanhnghiep1@test.com'],
            [
                'name' => 'Công ty TNHH ABC Tech',
                'role' => 'recruiter',
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );

        echo "✅ User created: doanhnghiep1@test.com / 12345678\n";
        echo "   Role: recruiter (doanh nghiệp)\n\n";

        // Create company profile
        $company = Company::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'ABC Tech Solutions',
                'description' => 'Công ty công nghệ hàng đầu chuyên phát triển giải pháp phần mềm cho doanh nghiệp. Chúng tôi tập trung vào các sản phẩm SaaS, ML và Cloud Computing. Quy mô: 100-500 nhân viên, thành lập năm 2018.',
                'website' => 'https://abctech.vn',
                'address' => 'Tầng 15, Tòa nhà Bitexco, 2 Hải Triều, Quận 1, TP. Hồ Chí Minh',
            ]
        );

        echo "🏢 Company created: {$company->name}\n";
        echo "   ID: {$company->id}\n\n";

        // Create jobs
        $jobs = [
            [
                'title' => 'Senior Backend Developer (PHP/Laravel)',
                'description' => 'Chúng tôi đang tìm kiếm Senior Backend Developer có kinh nghiệm với PHP/Laravel để phát triển hệ thống SaaS quy mô lớn.',
                'requirements' => '• 3+ năm kinh nghiệm PHP/Laravel
• Thành thạo MySQL, Redis, Docker
• Kinh nghiệm với microservices
• Biết Git, CI/CD
• Tiếng Anh tốt (TOEIC 750+)

💰 Lương: 25-40 triệu (thỏa thuận theo năng lực)
🎁 Quyền lợi:
• Thưởng theo dự án + KPI
• Bảo hiểm đầy đủ
• Laptop MacBook Pro
• Team building, du lịch hàng năm
• Được đào tạo công nghệ mới',
                'location' => 'Hồ Chí Minh',
                'salary_min' => 25000000,
                'salary_max' => 40000000,
                'status' => 'published',
            ],
            [
                'title' => 'Frontend Developer (React/Vue)',
                'description' => 'Tìm Frontend Developer có passion về UI/UX để xây dựng web applications hiện đại.',
                'requirements' => '• 2+ năm kinh nghiệm React hoặc Vue.js
• Thành thạo HTML5, CSS3, JavaScript ES6+
• Biết TypeScript là lợi thế
• Có kinh nghiệm với Tailwind CSS, Material UI
• Responsive design, performance optimization

💰 Lương: 18-30 triệu
🎁 Quyền lợi:
• Thưởng dự án
• Bảo hiểm xã hội đầy đủ
• Laptop cao cấp
• Môi trường năng động, sáng tạo',
                'location' => 'Hồ Chí Minh',
                'salary_min' => 18000000,
                'salary_max' => 30000000,
                'status' => 'published',
            ],
            [
                'title' => 'DevOps Engineer',
                'description' => 'Cần DevOps Engineer để xây dựng và vận hành hạ tầng cloud, CI/CD pipeline cho các dự án của công ty.',
                'requirements' => '• 2+ năm kinh nghiệm DevOps
• Thành thạo AWS/GCP
• Docker, Kubernetes
• CI/CD: Jenkins, GitLab CI
• Linux system admin
• Monitoring: Prometheus, Grafana

💰 Lương: 22-35 triệu
🎁 Quyền lợi:
• Bonus theo performance
• Được đào tạo AWS/GCP certification
• Laptop + màn hình phụ
• WFH linh hoạt',
                'location' => 'Hồ Chí Minh',
                'salary_min' => 22000000,
                'salary_max' => 35000000,
                'status' => 'published',
            ],
            [
                'title' => 'Fullstack Developer (MERN Stack)',
                'description' => 'Tuyển Fullstack Developer có kinh nghiệm MERN stack để phát triển ứng dụng web end-to-end.',
                'requirements' => '• 2+ năm kinh nghiệm MERN (MongoDB, Express, React, Node.js)
• Biết Next.js là lợi thế
• REST API, GraphQL
• Git, Agile workflow
• Tiếng Anh đọc hiểu tốt

💰 Lương: 20-32 triệu
🎁 Quyền lợi:
• Thưởng theo quý
• Bảo hiểm, phụ cấp
• Remote 2 ngày/tuần
• Cơ hội thăng tiến',
                'location' => 'Hồ Chí Minh',
                'salary_min' => 20000000,
                'salary_max' => 32000000,
                'status' => 'published',
            ],
        ];

        echo "📋 Creating jobs...\n\n";

        $createdJobs = [];
        foreach ($jobs as $index => $jobData) {
            $job = Job::create([
                'company_id' => $company->id,
                'title' => $jobData['title'],
                'description' => $jobData['description'],
                'requirements' => $jobData['requirements'],
                'location' => $jobData['location'],
                'salary_min' => $jobData['salary_min'],
                'salary_max' => $jobData['salary_max'],
                'status' => $jobData['status'],
                'published_at' => now(),
            ]);

            $createdJobs[] = $job;
            echo "   ✓ Job #{$job->id}: {$job->title}\n";
        }

        echo "\n";

        // Get ungvien2
        $candidate = Candidate::where('email', 'ungvien2@test.com')->first();
        
        if (!$candidate) {
            echo "❌ ungvien2 not found! Run PerfectCandidateSeeder first.\n";
            return;
        }

        echo "👤 Candidate: {$candidate->name} (ungvien2@test.com)\n\n";

        // Get CV data from profile
        $cvData = $candidate->profile_data['cv_quick'] ?? null;
        
        if (!$cvData) {
            echo "❌ CV Quick data not found!\n";
            return;
        }

        echo "📊 CV Data:\n";
        echo "   - Education: " . count($cvData['education'] ?? []) . " records\n";
        echo "   - Work: " . count($cvData['work_experiences'] ?? []) . " records\n";
        echo "   - Hard Skills: " . count($cvData['skills']['hard'] ?? []) . " items\n";
        echo "   - Soft Skills: " . count($cvData['skills']['soft'] ?? []) . " items\n";
        echo "   - Certifications: " . ($cvData['certifications']['english_level'] ?? 'N/A') . "\n\n";

        // Apply to all jobs
        echo "📝 Creating applications...\n\n";

        foreach ($createdJobs as $job) {
            // Check if already applied
            $existingApp = Application::where('job_id', $job->id)
                ->where('candidate_id', $candidate->id)
                ->first();

            if ($existingApp) {
                echo "   ⚠️ Already applied to: {$job->title}\n";
                continue;
            }

            $application = Application::create([
                'job_id' => $job->id,
                'candidate_id' => $candidate->id,
                'cv_data' => $cvData,
                'status' => 'submitted',
            ]);

            echo "   ✅ Applied to: {$job->title} (App ID: {$application->id})\n";
        }

        echo "\n═══════════════════════════════════════════════════════════\n";
        echo "✅ DONE!\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        echo "📋 LOGIN INFO:\n";
        echo "   Company Account: doanhnghiep1@test.com / 12345678\n";
        echo "   Candidate Account: ungvien2@test.com / 123456\n\n";
        
        echo "📊 CREATED:\n";
        echo "   - Company: {$company->name}\n";
        echo "   - Jobs: " . count($createdJobs) . " positions\n";
        echo "   - Applications: " . count($createdJobs) . " (ungvien2 applied to all)\n\n";
        
        echo "🎯 NEXT STEPS:\n";
        echo "   1. Login as doanhnghiep1@test.com / 12345678\n";
        echo "   2. Go to 'Quản lý ứng viên' or 'Tin tuyển dụng'\n";
        echo "   3. View applications from ungvien2\n";
        echo "   4. Check CV scores and details\n\n";
    }
}
