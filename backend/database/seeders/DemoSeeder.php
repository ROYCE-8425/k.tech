<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Job;
use App\Models\Candidate;
use App\Models\Application;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "🤖 DEMO SEEDER — Smart CV Matcher\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        // ── 1. Demo Recruiter ────────────────────────────────────────
        $recruiterUser = User::updateOrCreate(
            ['email' => 'demo-recruiter@smartcv.demo'],
            [
                'name'              => 'Demo Recruiter',
                'role'              => 'recruiter',
                'password'          => bcrypt('demo1234'),
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
            ]
        );
        echo "✅ Recruiter: demo-recruiter@smartcv.demo / demo1234\n";

        // ── 2. Demo Company ──────────────────────────────────────────
        $company = Company::updateOrCreate(
            ['user_id' => $recruiterUser->id],
            [
                'name'        => 'KTC Demo Corp',
                'description' => 'Công ty công nghệ chuyên tuyển dụng nhân sự IT tại Việt Nam.',
                'address'     => 'Tầng 8, Tòa nhà ABC Tower, Quận 1, TP.HCM',
                'website'     => 'https://ktc-demo.example.com',
            ]
        );
        echo "🏢 Company: {$company->name}\n\n";

        // ── 3. Demo Candidate 1 (primary) ────────────────────────────
        $candidateUser = User::updateOrCreate(
            ['email' => 'demo-candidate@smartcv.demo'],
            [
                'name'              => 'Nguyễn Văn Demo',
                'role'              => 'candidate',
                'password'          => bcrypt('demo1234'),
                'phone'             => '0901234567',
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
            ]
        );

        $candidate1 = Candidate::updateOrCreate(
            ['user_id' => $candidateUser->id],
            [
                'name'       => 'Nguyễn Văn Demo',
                'email'      => 'demo-candidate@smartcv.demo',
                'phone'      => '0901234567',
                'sector'     => 'it',
                'summary'    => 'Backend developer 2 năm kinh nghiệm, chuyên PHP/Laravel. Quan tâm clean architecture và automated testing.',
                'about_me'   => 'Tôi là backend developer với 2 năm kinh nghiệm phát triển hệ thống e-commerce và CRM bằng PHP/Laravel. Đam mê clean code, CI/CD và containerization.',
                'experience' => 'Junior (1-2 năm)',
                'education'  => 'Đại học Bách Khoa TP.HCM — Khoa học Máy tính (2019-2023)',
                'skills'     => 'PHP, Laravel, JavaScript, MySQL, Git, Docker, Redis',
                'skills_json' => ['PHP', 'Laravel', 'JavaScript', 'MySQL', 'Git', 'Docker', 'Redis'],
                'work_experiences' => [
                    ['company_name' => 'TechStartup VN', 'position_title' => 'Junior Backend Developer', 'start_date' => '2023-06-01', 'end_date' => null, 'is_current' => true, 'description' => 'Phát triển REST API bằng Laravel, tích hợp thanh toán VNPay/MoMo, quản lý MySQL, deploy Docker/AWS.'],
                ],
                'profile_data' => [
                    'sector' => 'it', 'primary_role' => 'Backend Developer',
                    'skills' => ['PHP', 'Laravel', 'JavaScript', 'MySQL', 'Git', 'Docker'],
                ],
                'github_url'    => 'https://github.com/nguyenvandemo',
                'linkedin_url'  => 'https://linkedin.com/in/nguyenvandemo',
            ]
        );
        echo "✅ Candidate 1: {$candidate1->name}\n";

        // ── 4. Demo Candidate 2 (extra for richer shortlist) ─────────
        $candidate2User = User::updateOrCreate(
            ['email' => 'demo-candidate2@smartcv.demo'],
            [
                'name'              => 'Trần Thị Mai',
                'role'              => 'candidate',
                'password'          => bcrypt('demo1234'),
                'email_verified_at' => now(),
                'two_factor_enabled' => false,
            ]
        );

        $candidate2 = Candidate::updateOrCreate(
            ['user_id' => $candidate2User->id],
            [
                'name'       => 'Trần Thị Mai',
                'email'      => 'demo-candidate2@smartcv.demo',
                'phone'      => '0912345678',
                'sector'     => 'it',
                'summary'    => 'Fullstack developer 3 năm kinh nghiệm, mạnh React/Node.js. Có kinh nghiệm Python và data pipeline.',
                'about_me'   => 'Fullstack developer với background mạnh về JavaScript ecosystem. Đã xây dựng nhiều SPA và REST API cho startup fintech.',
                'experience' => 'Mid (3 năm)',
                'education'  => 'Đại học Công nghệ Thông tin — CNTT (2018-2022)',
                'skills'     => 'JavaScript, TypeScript, React, Node.js, Python, PostgreSQL, Docker, AWS',
                'skills_json' => ['JavaScript', 'TypeScript', 'React', 'Node.js', 'Python', 'PostgreSQL', 'Docker', 'AWS'],
                'work_experiences' => [
                    ['company_name' => 'FinTech Solutions', 'position_title' => 'Fullstack Developer', 'start_date' => '2022-03-01', 'end_date' => null, 'is_current' => true, 'description' => 'Xây dựng React SPA + Node.js API cho nền tảng thanh toán. Quản lý PostgreSQL, deploy AWS ECS.'],
                    ['company_name' => 'WebAgency VN', 'position_title' => 'Junior Developer', 'start_date' => '2021-06-01', 'end_date' => '2022-02-28', 'is_current' => false, 'description' => 'Phát triển website khách hàng bằng React, WordPress. Tích hợp API bên thứ ba.'],
                ],
                'profile_data' => [
                    'sector' => 'it', 'primary_role' => 'Fullstack Developer',
                    'skills' => ['JavaScript', 'TypeScript', 'React', 'Node.js', 'Python', 'PostgreSQL', 'Docker', 'AWS'],
                ],
            ]
        );
        echo "✅ Candidate 2: {$candidate2->name}\n\n";

        // ── 5. Demo Jobs (4 positions with Phase 1 structured fields) ─
        $jobsData = [
            [
                'title'       => 'Backend Developer',
                'seniority'   => 'mid',
                'min_experience_years' => 2,
                'max_experience_years' => 5,
                'required_skills'  => ['Node.js', 'Java', 'PostgreSQL', 'Docker', 'REST API', 'Git'],
                'preferred_skills' => ['Kubernetes', 'CI/CD', 'GraphQL', 'Redis', 'Microservices'],
                'ai_recruiter_notes' => 'Ưu tiên ứng viên có kinh nghiệm microservices và system design. Team hiện tại dùng Node.js là chính, Java cho legacy service. Cần viết được integration test.',
                'description' => "Vị trí: Backend Developer (Mid-level)\nCông ty: KTC Demo Corp — Sản phẩm SaaS quản lý tuyển dụng\n\nVai trò:\nBạn sẽ tham gia team Platform (6-10 engineers) xây dựng và vận hành hệ thống backend phục vụ hàng nghìn nhà tuyển dụng. Công việc tập trung vào thiết kế API, tối ưu database, và đảm bảo hệ thống ổn định ở quy mô lớn.\n\nTrách nhiệm chính:\n• Thiết kế và phát triển REST API / GraphQL cho các tính năng tuyển dụng\n• Thiết kế schema PostgreSQL, viết migration, tối ưu query performance\n• Containerize ứng dụng với Docker, deploy lên cloud (AWS/GCP)\n• Viết unit test, integration test, đảm bảo code coverage > 80%\n• Code review, pair programming, và chia sẻ kiến thức trong team\n• Tham gia on-call rotation xử lý incident production\n\nCông nghệ đang dùng: Node.js (NestJS), Java (Spring Boot cho legacy), PostgreSQL, Redis, Docker, GitHub Actions CI/CD\n\nMôi trường làm việc:\n• Team 6-10 engineers, Agile/Scrum sprint 2 tuần\n• Office tại Quận 1, TP.HCM — hybrid 3 ngày/tuần\n• Budget học tập 5 triệu/năm, conference sponsorship",
                'requirements' => "Yêu cầu bắt buộc:\n• 2-5 năm kinh nghiệm backend development\n• Thành thạo Node.js hoặc Java, có khả năng làm việc với cả hai\n• Kinh nghiệm thiết kế và tối ưu PostgreSQL / SQL database\n• Sử dụng Docker trong development và deployment\n• Hiểu rõ RESTful API design principles\n• Sử dụng Git workflow (branching, PR review)\n\nƯu tiên:\n• Kinh nghiệm Kubernetes, container orchestration\n• Thiết lập CI/CD pipeline (GitHub Actions, GitLab CI)\n• Kinh nghiệm GraphQL\n• Sử dụng Redis cho caching/queue\n• Kinh nghiệm microservices architecture",
                'salary_min' => 18000000, 'salary_max' => 35000000, 'location' => 'TP. Hồ Chí Minh',
            ],
            [
                'title'       => 'Frontend Developer',
                'seniority'   => 'junior',
                'min_experience_years' => 1,
                'max_experience_years' => 3,
                'required_skills'  => ['React', 'JavaScript', 'TypeScript', 'HTML/CSS', 'Git', 'Responsive Design'],
                'preferred_skills' => ['Next.js', 'Tailwind CSS', 'Figma', 'Storybook', 'Jest'],
                'ai_recruiter_notes' => 'Cần portfolio thể hiện responsive design thực tế. Ứng viên sẽ làm việc trực tiếp với UI/UX Designer, cần khả năng giao tiếp visual tốt. Không cần backend experience.',
                'description' => "Vị trí: Frontend Developer (Junior)\nCông ty: KTC Demo Corp — Sản phẩm SaaS quản lý tuyển dụng\n\nVai trò:\nBạn sẽ tham gia team Product UI (5-8 developers) xây dựng giao diện người dùng cho nền tảng tuyển dụng. Công việc tập trung vào triển khai design system, đảm bảo trải nghiệm người dùng mượt mà trên mọi thiết bị.\n\nTrách nhiệm chính:\n• Phát triển UI components với React và TypeScript\n• Triển khai pixel-perfect design từ Figma mockup\n• Đảm bảo responsive design cho mobile, tablet, desktop\n• Tối ưu Core Web Vitals (LCP, FID, CLS)\n• Tích hợp API backend qua REST/GraphQL\n• Viết component test với Jest và React Testing Library\n• Maintain design system và Storybook documentation\n\nCông nghệ đang dùng: React 18, TypeScript, Next.js, Tailwind CSS, Storybook, Figma\n\nMôi trường làm việc:\n• Team 5-8 developers, làm việc sát với 2 UI/UX Designers\n• Sprint 2 tuần, design review hàng tuần\n• Office tại Quận 1, TP.HCM — hybrid 3 ngày/tuần",
                'requirements' => "Yêu cầu bắt buộc:\n• 1-3 năm kinh nghiệm frontend development\n• Thành thạo React (hooks, context, component patterns)\n• JavaScript ES6+ và TypeScript cơ bản\n• HTML5 semantic, CSS3, responsive design\n• Sử dụng Git, biết quy trình PR review\n• Có portfolio hoặc project cá nhân thể hiện UI implementation\n\nƯu tiên:\n• Kinh nghiệm Next.js (SSR/SSG)\n• Sử dụng Tailwind CSS hoặc CSS-in-JS\n• Biết đọc và triển khai design từ Figma\n• Kinh nghiệm Storybook cho component documentation\n• Viết test với Jest / React Testing Library",
                'salary_min' => 12000000, 'salary_max' => 25000000, 'location' => 'TP. Hồ Chí Minh',
            ],
            [
                'title'       => 'Data Analyst',
                'seniority'   => 'junior',
                'min_experience_years' => 1,
                'max_experience_years' => 3,
                'required_skills'  => ['SQL', 'Python', 'Excel', 'Data Visualization', 'Statistics'],
                'preferred_skills' => ['Power BI', 'Tableau', 'Pandas', 'Google Analytics', 'A/B Testing'],
                'ai_recruiter_notes' => 'Cần kỹ năng Data Storytelling — trình bày insight cho non-technical stakeholder. Làm việc với dữ liệu tuyển dụng Việt-Hàn, ưu tiên hiểu business context HR/recruitment.',
                'description' => "Vị trí: Data Analyst (Junior)\nCông ty: KTC Demo Corp — Sản phẩm SaaS quản lý tuyển dụng\n\nVai trò:\nBạn sẽ tham gia team Business Intelligence (3-5 người) phân tích dữ liệu tuyển dụng từ nền tảng. Công việc tập trung vào khai thác data để đưa ra insight giúp cải thiện sản phẩm và hỗ trợ quyết định kinh doanh.\n\nTrách nhiệm chính:\n• Viết SQL query phức tạp trên PostgreSQL để trích xuất dữ liệu\n• Phân tích hành vi người dùng, funnel tuyển dụng, conversion rate\n• Xây dựng dashboard tự động với Power BI hoặc Tableau\n• Thực hiện A/B testing và đo lường impact của feature mới\n• Làm sạch, chuẩn hóa dữ liệu từ nhiều nguồn khác nhau\n• Trình bày báo cáo insight hàng tuần cho Product Manager và CEO\n• Hỗ trợ team Marketing phân tích campaign performance\n\nCông nghệ đang dùng: PostgreSQL, Python (Pandas, Matplotlib), Power BI, Google Analytics, Metabase\n\nMôi trường làm việc:\n• Team BI 3-5 người, báo cáo trực tiếp cho VP Product\n• Làm việc cross-functional với Product, Marketing, Sales\n• Office tại Quận 1, TP.HCM",
                'requirements' => "Yêu cầu bắt buộc:\n• 1-3 năm kinh nghiệm data analysis hoặc business intelligence\n• Thành thạo SQL (JOIN, subquery, window functions, CTE)\n• Python cơ bản cho data manipulation (Pandas, NumPy)\n• Excel nâng cao (pivot table, VLOOKUP, macro cơ bản)\n• Hiểu biết cơ bản về statistics (mean, median, correlation, hypothesis testing)\n• Kỹ năng trình bày và data storytelling\n\nƯu tiên:\n• Kinh nghiệm Power BI hoặc Tableau\n• Sử dụng Pandas cho data processing\n• Kinh nghiệm Google Analytics\n• Hiểu biết về A/B testing methodology\n• Background hoặc quan tâm đến HR/recruitment domain",
                'salary_min' => 14000000, 'salary_max' => 25000000, 'location' => 'TP. Hồ Chí Minh',
            ],
            [
                'title'       => 'AI/ML Engineer',
                'seniority'   => 'mid',
                'min_experience_years' => 2,
                'max_experience_years' => 6,
                'required_skills'  => ['Python', 'PyTorch', 'Machine Learning', 'NLP', 'Docker', 'SQL'],
                'preferred_skills' => ['LLM', 'MLOps', 'AWS', 'Scikit-learn', 'FastAPI'],
                'ai_recruiter_notes' => 'Đòi hỏi tư duy nghiên cứu + engineering. Ưu tiên ứng viên có kinh nghiệm NLP/LLM thực tế (không chỉ coursework). Có publication hoặc Kaggle top 10% là điểm cộng lớn.',
                'description' => "Vị trí: AI/ML Engineer (Mid-level)\nCông ty: KTC Demo Corp — Sản phẩm SaaS quản lý tuyển dụng\n\nVai trò:\nBạn sẽ tham gia team AI (3-5 researchers/engineers) xây dựng hệ thống AI matching CV-JD — sản phẩm cốt lõi của công ty. Công việc kết hợp nghiên cứu ML và engineering để đưa mô hình vào production phục vụ hàng nghìn lượt matching mỗi ngày.\n\nTrách nhiệm chính:\n• Nghiên cứu và phát triển mô hình NLP cho semantic matching CV-JD\n• Xây dựng pipeline xử lý dữ liệu văn bản tiếng Việt\n• Fine-tune và deploy LLM cho extraction và reasoning tasks\n• Thiết kế experiment framework, tracking metrics (precision, recall, NDCG)\n• Xây dựng inference API với FastAPI, đóng gói Docker\n• Tối ưu model performance (latency, throughput, cost)\n• Viết technical documentation và chia sẻ research findings\n\nCông nghệ đang dùng: Python, PyTorch, Hugging Face Transformers, FastAPI, Docker, PostgreSQL, AWS (EC2 GPU instances)\n\nMôi trường làm việc:\n• Team AI 3-5 người, có GPU cluster riêng\n• Research paper reading club hàng tuần\n• Budget conference 10 triệu/năm (NeurIPS, ACL, EMNLP)\n• Office tại Quận 1, TP.HCM",
                'requirements' => "Yêu cầu bắt buộc:\n• 2-6 năm kinh nghiệm ML/AI engineering\n• Thành thạo Python, có production-level coding ability\n• Kinh nghiệm PyTorch — training, fine-tuning, inference optimization\n• Hiểu sâu Machine Learning fundamentals (supervised, unsupervised, evaluation)\n• Kinh nghiệm NLP thực tế (text classification, NER, semantic similarity)\n• Sử dụng Docker cho ML pipeline packaging\n• SQL cơ bản cho data exploration\n\nƯu tiên:\n• Kinh nghiệm LLM (GPT, Llama) — prompting, fine-tuning, RAG\n• MLOps experience (model versioning, monitoring, A/B testing models)\n• Kinh nghiệm AWS (SageMaker, EC2 GPU)\n• Sử dụng Scikit-learn cho classical ML\n• Kinh nghiệm FastAPI cho serving ML models\n• Publication tại NeurIPS, ICML, ACL, EMNLP hoặc Kaggle top 10%",
                'salary_min' => 25000000, 'salary_max' => 50000000, 'location' => 'TP. Hồ Chí Minh',
            ],
        ];

        $createdJobs = [];
        $createdJobIds = [];
        echo "📋 Jobs:\n";
        foreach ($jobsData as $jd) {
            $job = Job::updateOrCreate(
                ['company_id' => $company->id, 'title' => $jd['title']],
                [
                    'description'  => $jd['description'],
                    'requirements' => $jd['requirements'],
                    'location'     => $jd['location'],
                    'salary_min'   => $jd['salary_min'],
                    'salary_max'   => $jd['salary_max'],
                    'seniority'    => $jd['seniority'],
                    'min_experience_years' => $jd['min_experience_years'],
                    'max_experience_years' => $jd['max_experience_years'],
                    'required_skills'  => $jd['required_skills'],
                    'preferred_skills' => $jd['preferred_skills'],
                    'ai_recruiter_notes' => $jd['ai_recruiter_notes'],
                    'status'       => 'published',
                    'published_at' => now(),
                ]
            );
            $createdJobs[] = $job;
            $createdJobIds[] = $job->id;
            echo "   ✓ #{$job->id}: {$job->title} [{$jd['seniority']}]\n";
        }

        // Clean stale demo jobs
        Job::where('company_id', $company->id)->whereNotIn('id', $createdJobIds)->delete();
        echo "\n";

        // ── 6. Demo Applications ─────────────────────────────────────
        $demoAppIds = [];

        // App 1: Candidate 1 → Backend (partial match with full AI result)
        $app1 = Application::updateOrCreate(
            ['job_id' => $createdJobs[0]->id, 'candidate_id' => $candidate1->id],
            [
                'status'     => 'submitted',
                'applied_at' => now()->subDays(2),
                'cv_data'    => [
                    'self_description' => 'Backend developer 2 năm kinh nghiệm, chuyên PHP/Laravel.',
                    'education' => [['school' => 'ĐH Bách Khoa TP.HCM', 'major' => 'Khoa học Máy tính', 'graduation_year' => 2023]],
                    'work_experiences' => [['company_name' => 'TechStartup VN', 'position_title' => 'Junior Backend Developer', 'start_date' => '2023-06', 'is_current' => true]],
                    'skills' => ['hard' => [['name' => 'PHP', 'level' => 4], ['name' => 'Laravel', 'level' => 4], ['name' => 'MySQL', 'level' => 3], ['name' => 'JavaScript', 'level' => 3], ['name' => 'Docker', 'level' => 2], ['name' => 'Git', 'level' => 4]]],
                ],
                'cover_letter' => 'Em quan tâm đến vị trí Backend Developer tại KTC Demo Corp. Với 2 năm kinh nghiệm Laravel, em tin mình có thể đóng góp tốt cho team.',
                'ai_match_result' => [
                    'fit_score'   => 62,
                    'rank_label'  => 'medium_fit',
                    'confidence_label' => 'medium',
                    'matched_skills'   => ['Docker', 'Git', 'REST API'],
                    'missing_skills'   => ['Node.js', 'Java', 'PostgreSQL'],
                    'missing_preferred_skills' => ['Kubernetes', 'CI/CD', 'GraphQL', 'Microservices'],
                    'related_matches' => [
                        [
                            'candidate_skill' => 'Laravel',
                            'target_skill'    => 'Node.js',
                            'relation_type'   => 'alternative_to',
                            'similarity'      => 0.55,
                            'hop_count'       => 1,
                            'via_skill'       => null,
                        ],
                        [
                            'candidate_skill' => 'MySQL',
                            'target_skill'    => 'PostgreSQL',
                            'relation_type'   => 'alternative_to',
                            'similarity'      => 0.75,
                            'hop_count'       => 1,
                            'via_skill'       => null,
                        ],
                    ],
                    'risk_flags' => [
                        'Ứng viên chuyên PHP/Laravel — chưa có kinh nghiệm Node.js hoặc Java (yêu cầu chính của role)',
                        'Kinh nghiệm 2 năm — đạt mức tối thiểu, chưa có system design experience',
                    ],
                    'score_breakdown' => [
                        'required_skill_coverage' => ['score' => 0.5, 'weight' => 0.4, 'weighted' => 20, 'detail' => '3/6 required skills (Docker, Git, REST API)'],
                        'preferred_skill_coverage' => ['score' => 0.2, 'weight' => 0.15, 'weighted' => 3, 'detail' => '1/5 preferred (Redis qua related skill)'],
                        'experience_fit' => ['score' => 0.7, 'weight' => 0.2, 'weighted' => 14, 'detail' => '2 năm / yêu cầu 2-5 năm — lower bound'],
                        'seniority_fit' => ['score' => 0.5, 'weight' => 0.1, 'weighted' => 5, 'detail' => 'Junior applying for Mid — stretch'],
                        'domain_relevance' => ['score' => 0.85, 'weight' => 0.15, 'weighted' => 12.75, 'detail' => 'Backend dev — cùng domain, khác stack'],
                    ],
                    'agent_trace' => [
                        'ExtractorAgent: CandidateProfile (llm, conf=medium), JobProfile (structured, conf=high), provider=gemini',
                        'RAGAgent: retrieved 2 evidence docs via static_corpus',
                        'MatcherAgent: fit_score=62, matched=3, related=2×1hop+0×2hop, missing=3, confidence=medium',
                        'ExplainerAgent: generated citation-aware rationale',
                        'CriticAgent: validated confidence and adjusted edge cases',
                        'FeedbackReranker: no adjustment (insufficient feedback signal)',
                    ],
                    'retrieval_method' => 'demo_seed',
                    'pipeline_version' => 'v1.1-demo',
                    'generated_at'     => now()->subHours(6)->toIso8601String(),
                ],
            ]
        );
        $demoAppIds[] = $app1->id;
        echo "📝 App 1: {$candidate1->name} → Backend (AI scored: 62)\n";

        // App 2: Candidate 2 → Backend (strong match with full AI result)
        $app2 = Application::updateOrCreate(
            ['job_id' => $createdJobs[0]->id, 'candidate_id' => $candidate2->id],
            [
                'status'     => 'submitted',
                'applied_at' => now()->subDays(1),
                'cv_data'    => [
                    'self_description' => 'Fullstack developer 3 năm, mạnh Node.js/React. Có kinh nghiệm PostgreSQL, Docker, AWS.',
                    'education' => [['school' => 'ĐH Công nghệ Thông tin', 'major' => 'CNTT', 'graduation_year' => 2022]],
                    'work_experiences' => [
                        ['company_name' => 'FinTech Solutions', 'position_title' => 'Fullstack Developer', 'start_date' => '2022-03', 'is_current' => true],
                        ['company_name' => 'WebAgency VN', 'position_title' => 'Junior Developer', 'start_date' => '2021-06', 'end_date' => '2022-02'],
                    ],
                    'skills' => ['hard' => [['name' => 'Node.js', 'level' => 4], ['name' => 'JavaScript', 'level' => 5], ['name' => 'TypeScript', 'level' => 4], ['name' => 'React', 'level' => 4], ['name' => 'PostgreSQL', 'level' => 3], ['name' => 'Docker', 'level' => 3], ['name' => 'AWS', 'level' => 3], ['name' => 'Git', 'level' => 4]]],
                ],
                'ai_match_result' => [
                    'fit_score'   => 81,
                    'rank_label'  => 'high_fit',
                    'confidence_label' => 'high',
                    'matched_skills'   => ['Node.js', 'PostgreSQL', 'Docker', 'REST API', 'Git'],
                    'missing_skills'   => ['Java'],
                    'missing_preferred_skills' => ['Kubernetes', 'GraphQL', 'Microservices'],
                    'related_matches' => [
                        [
                            'candidate_skill' => 'TypeScript',
                            'target_skill'    => 'Java',
                            'relation_type'   => 'same_ecosystem',
                            'similarity'      => 0.35,
                            'hop_count'       => 1,
                            'via_skill'       => null,
                        ],
                    ],
                    'risk_flags' => ['Chưa có kinh nghiệm Java/Spring Boot — team cần maintain legacy service'],
                    'score_breakdown' => [
                        'required_skill_coverage' => ['score' => 0.83, 'weight' => 0.4, 'weighted' => 33.2, 'detail' => '5/6 required skills matched'],
                        'preferred_skill_coverage' => ['score' => 0.4, 'weight' => 0.15, 'weighted' => 6, 'detail' => '2/5 preferred (Redis, CI/CD)'],
                        'experience_fit' => ['score' => 0.9, 'weight' => 0.2, 'weighted' => 18, 'detail' => '3 năm / yêu cầu 2-5 — solid mid'],
                        'seniority_fit' => ['score' => 0.85, 'weight' => 0.1, 'weighted' => 8.5, 'detail' => 'Mid applying for Mid — phù hợp'],
                        'domain_relevance' => ['score' => 0.9, 'weight' => 0.15, 'weighted' => 13.5, 'detail' => 'Fullstack → Backend — strong overlap'],
                    ],
                    'agent_trace' => [
                        'ExtractorAgent: CandidateProfile (llm, conf=high), JobProfile (structured, conf=high), provider=gemini',
                        'RAGAgent: retrieved 3 evidence docs via static_corpus',
                        'MatcherAgent: fit_score=81, matched=5, related=1×1hop+0×2hop, missing=1, confidence=high',
                        'ExplainerAgent: generated citation-aware rationale',
                        'CriticAgent: validated confidence and adjusted edge cases',
                        'FeedbackReranker: no adjustment (insufficient feedback signal)',
                    ],
                    'retrieval_method' => 'demo_seed',
                    'pipeline_version' => 'v1.1-demo',
                    'generated_at'     => now()->subHours(3)->toIso8601String(),
                ],
            ]
        );
        $demoAppIds[] = $app2->id;
        echo "📝 App 2: {$candidate2->name} → Backend (AI scored: 81)\n";

        // App 3: Candidate 1 → Frontend (no AI result — for fresh testing)
        $app3 = Application::updateOrCreate(
            ['job_id' => $createdJobs[1]->id, 'candidate_id' => $candidate1->id],
            [
                'status'     => 'submitted',
                'applied_at' => now()->subHours(12),
                'cv_data'    => [
                    'self_description' => 'Backend developer quan tâm fullstack, có JavaScript cơ bản.',
                    'skills' => ['hard' => [['name' => 'JavaScript', 'level' => 3], ['name' => 'HTML/CSS', 'level' => 2], ['name' => 'Git', 'level' => 4]]],
                ],
                'ai_match_result' => null,
            ]
        );
        $demoAppIds[] = $app3->id;
        echo "📝 App 3: {$candidate1->name} → Frontend (no AI — fresh test)\n";

        // App 4: Candidate 2 → Frontend (no AI result)
        $app4 = Application::updateOrCreate(
            ['job_id' => $createdJobs[1]->id, 'candidate_id' => $candidate2->id],
            [
                'status'     => 'submitted',
                'applied_at' => now()->subHours(8),
                'cv_data'    => [
                    'self_description' => 'Fullstack developer mạnh React/TypeScript, có portfolio responsive design. Kinh nghiệm Storybook và component documentation.',
                    'skills' => ['hard' => [['name' => 'React', 'level' => 4], ['name' => 'JavaScript', 'level' => 5], ['name' => 'TypeScript', 'level' => 4], ['name' => 'HTML/CSS', 'level' => 4], ['name' => 'Responsive Design', 'level' => 4], ['name' => 'Next.js', 'level' => 3], ['name' => 'Tailwind CSS', 'level' => 3], ['name' => 'Git', 'level' => 4]]],
                ],
                'ai_match_result' => null,
            ]
        );
        $demoAppIds[] = $app4->id;
        echo "📝 App 4: {$candidate2->name} → Frontend (no AI — fresh test)\n";

        // Clean stale demo applications
        Application::where('candidate_id', $candidate1->id)->whereNotIn('id', $demoAppIds)->delete();
        Application::where('candidate_id', $candidate2->id)->whereNotIn('id', $demoAppIds)->delete();

        echo "\n═══════════════════════════════════════════════════════════\n";
        echo "✅ DEMO SEEDER COMPLETE\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        echo "📋 ACCOUNTS:\n";
        echo "   Candidate: demo-candidate@smartcv.demo / demo1234\n";
        echo "   Recruiter: demo-recruiter@smartcv.demo / demo1234\n\n";
        echo "📋 DATA: {$company->name} — " . count($createdJobs) . " jobs, " . count($demoAppIds) . " applications\n";
        echo "   Backend job: 2 apps (AI scored: 62 + 81 — visible ranking gap)\n";
        echo "   Frontend job: 2 apps (no AI — ready for fresh scoring test)\n";
        echo "   Data Analyst + AI/ML: 0 apps (for new apply flow testing)\n\n";
        echo "🎯 Visit /demo to start testing\n\n";
    }
}
