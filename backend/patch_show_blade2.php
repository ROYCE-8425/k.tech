<?php
$file = __DIR__ . '/resources/views/jobs/show.blade.php';
$content = file_get_contents($file);
$contentNorm = str_replace("\r\n", "\n", $content);

$oldStep1Start = '{{-- ═══ STEP 1: CV Confirmation ═══ --}}';
$oldStep1End = '{{-- ═══ STEP 2: AI Chat Follow-up ═══ --}}';

$pos1 = strpos($contentNorm, $oldStep1Start);
$pos2 = strpos($contentNorm, $oldStep1End);

if ($pos1 === false || $pos2 === false) {
    echo "❌ Could not find Step 1 boundaries\n";
    exit(1);
}

$newStep1 = file_get_contents(__DIR__ . '/patch_step1_template.blade.php');
$newStep1 = str_replace("\r\n", "\n", $newStep1);

$contentNorm = substr($contentNorm, 0, $pos1) . $newStep1 . "\n\n                                        " . substr($contentNorm, $pos2);

$contentFinal = str_replace("\n", "\r\n", $contentNorm);
$contentFinal = str_replace("\r\r\n", "\r\n", $contentFinal);
file_put_contents($file, $contentFinal);
echo "✅ show.blade.php: Step 1 converted to popup modal\n";
