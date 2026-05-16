<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Job;
use App\Models\CvScoringProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class JobSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::query()->orderBy('id')->get();
        if ($companies->isEmpty()) {
            return;
        }

        $now = Carbon::now();

        $jobTemplates = [
            [
                'title' => 'Senior Frontend Developer (React/Next.js)',
                'location' => 'TP. Hồ Chí Minh (Hybrid)',
                'salary_min' => 30000000,
                'salary_max' => 60000000,
                'currency' => 'VND',
                'description' => "Tham gia phát triển các tính năng frontend của nền tảng web/app, tối ưu hiệu năng, làm việc với team UX/UI và Backend để mang đến trải nghiệm người dùng tốt nhất.",
                'requirements' => "3+ năm kinh nghiệm React; Thành thạo Next.js, TypeScript; Hiểu sâu về State Management (Redux/Zustand); Có kinh nghiệm với Tailwind CSS, REST API; Ưu tiên biết GraphQL.",
            ],
            [
                'title' => 'Backend Developer (Laravel/PHP)',
                'location' => 'Hà Nội (Onsite)',
                'salary_min' => 25000000,
                'salary_max' => 50000000,
                'currency' => 'VND',
                'description' => "Xây dựng và phát triển API backend cho các hệ thống web application, xử lý logic nghiệp vụ phức tạp, tối ưu database, đảm bảo bảo mật và hiệu năng hệ thống.",
                'requirements' => "2+ năm Laravel; MySQL nâng cao; Redis/Queue; RESTful API; Biết Docker/CI-CD; Ưu tiên có kinh nghiệm Microservices và Testing.",
            ],
            [
                'title' => 'DevOps Engineer (AWS/GCP)',
                'location' => 'Remote (Vietnam)',
                'salary_min' => 35000000,
                'salary_max' => 80000000,
                'currency' => 'VND',
                'description' => "Thiết kế và quản lý hạ tầng cloud, xây dựng CI/CD pipeline, monitoring và logging, đảm bảo hệ thống vận hành ổn định 24/7, tự động hóa các quy trình deployment.",
                'requirements' => "Linux administration; Docker & Kubernetes; AWS hoặc GCP; CI/CD (GitLab CI/GitHub Actions); Terraform; Monitoring tools (Prometheus/Grafana); Scripting (Bash/Python).",
            ],
            [
                'title' => 'Mobile Developer (Flutter/React Native)',
                'location' => 'TP. Hồ Chí Minh (Hybrid)',
                'salary_min' => 28000000,
                'salary_max' => 55000000,
                'currency' => 'VND',
                'description' => "Phát triển ứng dụng mobile đa nền tảng (iOS/Android), tích hợp API, tối ưu UX/UI, đảm bảo hiệu năng và trải nghiệm mượt mà cho người dùng.",
                'requirements' => "2+ năm Flutter hoặc React Native; Dart/JavaScript; REST API; State management; Publishing apps (App Store/Play Store); Ưu tiên biết native iOS/Android.",
            ],
            [
                'title' => 'QA Engineer (Manual & Automation)',
                'location' => 'Đà Nẵng (Onsite)',
                'salary_min' => 15000000,
                'salary_max' => 35000000,
                'currency' => 'VND',
                'description' => "Thiết kế test plan và test case, thực hiện testing manual và automation, phối hợp với dev để phát hiện và fix bug, đảm bảo chất lượng sản phẩm trước khi release.",
                'requirements' => "Kinh nghiệm QA manual; Viết test case tốt; API testing (Postman); Ưu tiên biết Selenium/Playwright/Cypress cho automation testing.",
            ],
            [
                'title' => 'Data Analyst (SQL/Python)',
                'location' => 'Hà Nội (Hybrid)',
                'salary_min' => 20000000,
                'salary_max' => 45000000,
                'currency' => 'VND',
                'description' => "Phân tích dữ liệu kinh doanh, xây dựng dashboard KPI, báo cáo insight để hỗ trợ quyết định sản phẩm và chiến lược, làm việc với team Product/Marketing.",
                'requirements' => "SQL nâng cao; Excel/Google Sheets; Power BI hoặc Tableau; Tư duy phân tích; Ưu tiên biết Python (pandas, numpy) và có kinh nghiệm A/B testing.",
            ],
            [
                'title' => 'ML Engineer (NLP)',
                'location' => 'TP. Hồ Chí Minh (Hybrid)',
                'salary_min' => 40000000,
                'salary_max' => 100000000,
                'currency' => 'VND',
                'description' => "Nghiên cứu và phát triển các mô hình ML cho bài toán NLP, CV matching, xây dựng pipeline training/deployment, tối ưu model performance và tích hợp vào production.",
                'requirements' => "Python; Machine Learning/Deep Learning; NLP (transformers, BERT, GPT); Vector search; TensorFlow/PyTorch; MLOps; Ưu tiên có paper/project ML.",
            ],
            [
                'title' => 'Fullstack Developer (MERN Stack)',
                'location' => 'Remote (Vietnam)',
                'salary_min' => 25000000,
                'salary_max' => 55000000,
                'currency' => 'VND',
                'description' => "Phát triển end-to-end web application, từ frontend UI đến backend API và database, tham gia toàn bộ vòng đời sản phẩm từ thiết kế đến deployment.",
                'requirements' => "MongoDB, Express.js, React, Node.js; REST API; TypeScript; Git; Docker cơ bản; Ưu tiên biết Next.js và cloud deployment.",
            ],
            [
                'title' => 'Product Designer (UI/UX)',
                'location' => 'TP. Hồ Chí Minh (Onsite)',
                'salary_min' => 22000000,
                'salary_max' => 50000000,
                'currency' => 'VND',
                'description' => "Thiết kế giao diện và trải nghiệm người dùng cho web/mobile app, nghiên cứu user behavior, tạo wireframe/prototype, phối hợp với dev để implement design.",
                'requirements' => "Figma thành thạo; User research; Wireframe/Prototype; Design system; Portfolio mạnh; Ưu tiên có kinh nghiệm design app phức tạp.",
            ],
            [
                'title' => 'Security Engineer (AppSec/Pentest)',
                'location' => 'Hà Nội (Hybrid)',
                'salary_min' => 30000000,
                'salary_max' => 70000000,
                'currency' => 'VND',
                'description' => "Đánh giá và nâng cao bảo mật hệ thống, thực hiện penetration testing, code review bảo mật, xây dựng quy trình secure SDLC, response security incidents.",
                'requirements' => "Web security (OWASP Top 10); Penetration testing; Security tools (Burp Suite, Metasploit); Secure coding; Network security; Ưu tiên có cert (CEH, OSCP).",
            ],
        ];

        foreach ($companies as $index => $company) {
            // Mỗi công ty sẽ có 4-6 jobs
            foreach ($jobTemplates as $tIndex => $tpl) {
                // Phân bổ đều jobs cho các công ty
                if (($tIndex + $index) % 3 === 2) {
                    continue; // Skip 1/3 để tạo sự đa dạng
                }

                $profileId = null;
                $devProfile = CvScoringProfile::query()->where('key', 'it_dev')->first();
                $testerProfile = CvScoringProfile::query()->where('key', 'it_tester')->first();
                if (stripos($tpl['title'], 'qa') !== false || stripos($tpl['title'], 'tester') !== false) {
                    $profileId = $testerProfile?->id;
                } else {
                    $profileId = $devProfile?->id;
                }

                Job::query()->updateOrCreate(
                    ['company_id' => $company->id, 'title' => $tpl['title']],
                    [
                        'cv_scoring_profile_id' => $profileId,
                        'description' => $tpl['description'],
                        'requirements' => $tpl['requirements'],
                        'salary_min' => $tpl['salary_min'],
                        'salary_max' => $tpl['salary_max'],
                        'currency' => $tpl['currency'],
                        'location' => $tpl['location'],
                        'status' => 'published',
                        'published_at' => $now,
                    ]
                );
            }
        }

        // Media jobs (Truyền thông)
        $mediaTemplates = [
            [
                'title' => 'Digital Marketing Manager',
                'profile_key' => 'media_digital_marketing_default',
                'location' => 'TP. Hồ Chí Minh (Hybrid)',
                'salary_min' => 20000000,
                'salary_max' => 40000000,
                'currency' => 'VND',
                'description' => 'Xây dựng và triển khai chiến lược Digital Marketing đa kênh, quản lý ngân sách quảng cáo, phân tích hiệu quả campaign, tối ưu ROI và lead generation.',
                'requirements' => '3+ năm kinh nghiệm Digital Marketing; Thành thạo Facebook Ads, Google Ads, TikTok Ads; Phân tích dữ liệu tốt; Quản lý team; Ưu tiên có kinh nghiệm e-commerce.',
            ],
            [
                'title' => 'Content Marketing Specialist',
                'profile_key' => 'media_content_marketing_default',
                'location' => 'Hà Nội (Hybrid)',
                'salary_min' => 15000000,
                'salary_max' => 30000000,
                'currency' => 'VND',
                'description' => 'Lập kế hoạch content marketing, sáng tạo nội dung cho blog/social/email, viết bài SEO-friendly, phối hợp với design và social team để thực thi chiến dịch.',
                'requirements' => '2+ năm Content Marketing; Viết content chuyên nghiệp; SEO cơ bản; Content planning; Portfolio nội dung; Ưu tiên hiểu về storytelling và brand voice.',
            ],
            [
                'title' => 'Social Media Manager',
                'profile_key' => 'media_social_media_default',
                'location' => 'TP. Hồ Chí Minh (Onsite)',
                'salary_min' => 16000000,
                'salary_max' => 35000000,
                'currency' => 'VND',
                'description' => 'Quản trị các kênh social media (Facebook, Instagram, TikTok, LinkedIn), xây dựng chiến lược nội dung, tương tác cộng đồng, phân tích metrics và tăng trưởng followers.',
                'requirements' => '2+ năm Social Media Management; Hiểu insight và trend; Community management; Content calendar; Analytics; Ưu tiên có case study tăng trưởng fanpage.',
            ],
            [
                'title' => 'PR & Communication Executive',
                'profile_key' => 'media_pr_event_default',
                'location' => 'Hà Nội (Onsite)',
                'salary_min' => 14000000,
                'salary_max' => 28000000,
                'currency' => 'VND',
                'description' => 'Xây dựng và thực thi kế hoạch PR, viết press release, làm việc với media/KOL, tổ chức sự kiện, quản lý khủng hoảng truyền thông.',
                'requirements' => 'Kinh nghiệm PR/Communications; Kỹ năng viết tốt; Media relations; Event management; Crisis communication; Ưu tiên có network media rộng.',
            ],
            [
                'title' => 'Graphic Designer (Social Media)',
                'profile_key' => 'media_creative_design_default',
                'location' => 'Đà Nẵng (Hybrid)',
                'salary_min' => 12000000,
                'salary_max' => 28000000,
                'currency' => 'VND',
                'description' => 'Thiết kế visual cho social media posts, banner ads, key visual campaigns, infographic, GIF/motion graphics đơn giản, đảm bảo brand identity.',
                'requirements' => 'Photoshop & Illustrator thành thạo; Tư duy visual storytelling; Portfolio đa dạng; Ưu tiên biết After Effects/Premiere cho motion design.',
            ],
            [
                'title' => 'SEO Specialist',
                'profile_key' => 'media_digital_marketing_default',
                'location' => 'Remote (Vietnam)',
                'salary_min' => 15000000,
                'salary_max' => 32000000,
                'currency' => 'VND',
                'description' => 'Nghiên cứu keyword, tối ưu on-page/off-page SEO, phân tích đối thủ, theo dõi ranking, xây dựng backlink, báo cáo hiệu quả SEO hàng tháng.',
                'requirements' => '2+ năm SEO; Google Analytics & Search Console; Keyword research; On-page/Off-page SEO; Link building; Technical SEO cơ bản; Ưu tiên có case study top ranking.',
            ],
            [
                'title' => 'Video Editor (Content Creator)',
                'profile_key' => 'media_creative_design_default',
                'location' => 'TP. Hồ Chí Minh (Onsite)',
                'salary_min' => 13000000,
                'salary_max' => 30000000,
                'currency' => 'VND',
                'description' => 'Dựng video content cho social media, TikTok, YouTube, Reels, cắt ghép footage, thêm effect/subtitle/music, tối ưu cho từng platform.',
                'requirements' => 'Premiere Pro & After Effects; Hiểu trend video social; Color grading; Sound mixing; Portfolio video; Ưu tiên biết quay/dựng cho TikTok/Reels.',
            ],
        ];

        // Phân bổ media jobs cho 3 công ty đầu tiên
        foreach ($companies->take(3) as $companyIndex => $company) {
            foreach ($mediaTemplates as $mediaIndex => $tpl) {
                // Mỗi công ty có 3-4 media jobs
                if (($mediaIndex + $companyIndex) % 2 === 1) {
                    continue;
                }
                
                $profileId = CvScoringProfile::query()->where('key', $tpl['profile_key'])->value('id');

                Job::query()->updateOrCreate(
                    ['company_id' => $company->id, 'title' => $tpl['title']],
                    [
                        'cv_scoring_profile_id' => $profileId,
                        'description' => $tpl['description'],
                        'requirements' => $tpl['requirements'],
                        'salary_min' => $tpl['salary_min'],
                        'salary_max' => $tpl['salary_max'],
                        'currency' => $tpl['currency'],
                        'location' => $tpl['location'],
                        'status' => 'published',
                        'published_at' => $now,
                    ]
                );
            }
        }
    }
}
