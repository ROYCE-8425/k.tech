<?php

namespace Database\Seeders;

use App\Models\Candidate;
use Illuminate\Database\Seeder;

class CandidateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $candidates = [
            [
                'name' => 'Nguyễn Minh Anh',
                'email' => 'minhanh.candidate@example.com',
                'phone' => '0900000001',
                'summary' => 'Frontend developer tập trung React/TypeScript, quan tâm hiệu năng và UI/UX.',
                'skills_json' => ['React', 'TypeScript', 'Tailwind', 'REST', 'Git'],
            ],
            [
                'name' => 'Trần Quốc Huy',
                'email' => 'quochuy.candidate@example.com',
                'phone' => '0900000002',
                'summary' => 'Backend developer Laravel, có kinh nghiệm MySQL, Queue, caching và triển khai Docker.',
                'skills_json' => ['Laravel', 'PHP', 'MySQL', 'Redis', 'Docker'],
            ],
            [
                'name' => 'Lê Thu Trang',
                'email' => 'thutrang.candidate@example.com',
                'phone' => '0900000003',
                'summary' => 'Data analyst, mạnh SQL và dashboard KPI, thích làm việc với sản phẩm và dữ liệu.',
                'skills_json' => ['SQL', 'Power BI', 'Excel', 'KPI', 'Data Visualization'],
            ],
        ];

        foreach ($candidates as $c) {
            Candidate::query()->updateOrCreate(
                ['email' => $c['email']],
                [
                    'name' => $c['name'],
                    'phone' => $c['phone'],
                    'file_path_cv' => null,
                    'summary' => $c['summary'],
                    'skills_json' => $c['skills_json'],
                ]
            );
        }
    }
}
