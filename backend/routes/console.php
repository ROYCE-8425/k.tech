<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\ITSoloLevelingSecurity;
use App\Services\CvRubricScoringService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('it:security-demo {password : Plain password to protect} {--hash= : Optional existing hash to verify against}', function () {
    /** @var ITSoloLevelingSecurity $security */
    $security = app(ITSoloLevelingSecurity::class);

    $password = (string) $this->argument('password');
    $providedHash = $this->option('hash');

    if (is_string($providedHash) && $providedHash !== '') {
        $ok = $security->authenticate($password, $providedHash);
        $this->line('Verify: ' . ($ok ? 'OK' : 'FAIL'));
        return;
    }

    $hash = $security->protect($password);
    $this->line('Hash: ' . $hash);
    $this->line('Verify self-check: ' . ($security->authenticate($password, $hash) ? 'OK' : 'FAIL'));
})->purpose('Demo IT Solo Leveling Argon2id + peppering hashing');

Artisan::command('cv:score-demo {rubricKey : Rubric key (e.g. it)} {jsonInputs? : JSON object of inputs, or "-" to read from STDIN} {--profile= : Optional scoring profile key (e.g. it_dev)}', function () {
    /** @var CvRubricScoringService $scoring */
    $scoring = app(CvRubricScoringService::class);

    $rubricKey = (string) $this->argument('rubricKey');
    $jsonInputsArg = $this->argument('jsonInputs');
    $jsonInputs = is_string($jsonInputsArg) ? $jsonInputsArg : '';

    if ($jsonInputs === '' || $jsonInputs === '-') {
        $jsonInputs = (string) stream_get_contents(STDIN);
    }

    $jsonInputs = trim($jsonInputs);
    if (strlen($jsonInputs) >= 2) {
        $first = $jsonInputs[0];
        $last = $jsonInputs[strlen($jsonInputs) - 1];
        if (($first === "'" && $last === "'") || ($first === '"' && $last === '"')) {
            $jsonInputs = substr($jsonInputs, 1, -1);
        }
    }
    // Some shells may escape quotes; try to undo common escaping.
    $jsonInputs = str_replace(['\\"', "\\'"], ['"', "'"], $jsonInputs);

    $inputs = json_decode($jsonInputs, true);
    if (!is_array($inputs)) {
        $this->error('jsonInputs must be a JSON object');
        return 1;
    }

    $profileKey = $this->option('profile');
    if (is_string($profileKey) && $profileKey !== '') {
        $result = $scoring->scoreProfile($profileKey, $inputs);
    } else {
        $result = $scoring->score($rubricKey, $inputs);
    }

    $title = "Rubric: {$result['rubric']['name']} ({$result['rubric']['key']})";
    if (isset($result['profile'])) {
        $title .= " | Profile: {$result['profile']['name']} ({$result['profile']['key']})";
    }
    $this->info($title);
    $this->line('Total: ' . $result['total']);
    if ($result['grade']) {
        $note = $result['grade']['note'] ? (' - ' . $result['grade']['note']) : '';
        $this->line('Grade: ' . $result['grade']['label'] . $note);
    }

    foreach ($result['groups'] as $group) {
        $this->line("- {$group['code']}. {$group['name']}: {$group['score']}/{$group['max_score']}");
    }

    return 0;
})->purpose('Demo CV rubric scoring (manual inputs -> total score)');
