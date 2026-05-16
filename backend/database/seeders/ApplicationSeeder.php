<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobs = Job::query()->where('status', 'published')->orderBy('id')->take(6)->get();
        $candidates = Candidate::query()->orderBy('id')->take(3)->get();

        if ($jobs->isEmpty() || $candidates->isEmpty()) {
            return;
        }

        $admin = User::query()->first();
        $now = Carbon::now();

        $pairs = [
            // candidate 0 applies to job 0, 1
            [0, 0, 'submitted', 72],
            [0, 1, 'reviewing', 81],
            // candidate 1 applies to job 2, 3
            [1, 2, 'shortlisted', 86],
            [1, 3, 'submitted', 68],
            // candidate 2 applies to job 4
            [2, 4, 'reviewing', 74],
        ];

        foreach ($pairs as [$candidateIndex, $jobIndex, $status, $score]) {
            $candidate = $candidates->get($candidateIndex);
            $job = $jobs->get($jobIndex);
            if (!$candidate || !$job) {
                continue;
            }

            $attrs = [
                'status' => $status,
                'cv_manual_score' => (float) $score,
                'cv_file_path' => null,
                'cover_letter' => 'Em mong muốn ứng tuyển vị trí này vì phù hợp với kỹ năng và định hướng phát triển của em.',
                'notes' => $status === 'shortlisted' ? 'Ứng viên phù hợp, đề xuất phỏng vấn vòng 1.' : null,
                'applied_at' => $now,
            ];

            if ($status === 'shortlisted') {
                $attrs['interviewed_at'] = $now->copy()->addDays(2);
                $attrs['interviewed_by'] = $admin?->id;
            }

            Application::query()->updateOrCreate(
                ['job_id' => $job->id, 'candidate_id' => $candidate->id],
                $attrs
            );
        }
    }
}
