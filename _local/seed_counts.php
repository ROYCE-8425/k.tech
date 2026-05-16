<?php

require __DIR__ . '/../core/vendor/autoload.php';

$app = require __DIR__ . '/../core/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'companies=' . App\Models\Company::count() . PHP_EOL;
echo 'jobs=' . App\Models\Job::count() . PHP_EOL;
echo 'candidates=' . App\Models\Candidate::count() . PHP_EOL;
echo 'applications=' . App\Models\Application::count() . PHP_EOL;
