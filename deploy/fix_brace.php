<?php
/**
 * Fix the orphaned closing brace that ends the apply() method too early.
 * The patch_controller.php removed the form block's body but left its closing }.
 * 
 * Usage: php /tmp/fix_brace.php
 */

$file = '/var/www/smartcv/backend/app/Http/Controllers/CandidateJobController.php';
$lines = file($file);
$totalLines = count($lines);
echo "Total lines: $totalLines\n";

// Find the problematic area: "// Tìm hoặc tạo Candidate" should be inside apply()
// The } on line before it is the orphaned closing brace
$candidateUpdateLine = null;
for ($i = 0; $i < $totalLines; $i++) {
    if (strpos($lines[$i], '// Tìm hoặc tạo Candidate') !== false) {
        $candidateUpdateLine = $i;
        break;
    }
}

if ($candidateUpdateLine === null) {
    echo "ERROR: Could not find '// Tìm hoặc tạo Candidate'\n";
    exit(1);
}

echo "Found '// Tìm hoặc tạo Candidate' at line " . ($candidateUpdateLine + 1) . "\n";

// Look backwards from candidateUpdateLine for the orphaned }
// It should be a line containing only } (with whitespace) between the validate/extract code and candidateUpdate
$orphanedBrace = null;
for ($i = $candidateUpdateLine - 1; $i >= max(0, $candidateUpdateLine - 10); $i--) {
    $trimmed = trim($lines[$i]);
    if ($trimmed === '}') {
        // Check what's before this brace - should be part of PDF extraction code or validate
        // Look further back to confirm this is orphaned
        $prevCode = '';
        for ($j = $i - 1; $j >= max(0, $i - 5); $j--) {
            $prevTrimmed = trim($lines[$j]);
            if ($prevTrimmed !== '') {
                $prevCode = $prevTrimmed;
                break;
            }
        }
        
        echo "Found } at line " . ($i + 1) . " (prev code: '$prevCode')\n";
        
        // If the prev code is from extraction block or validate, this } is orphaned
        if (strpos($prevCode, 'extractTextFromPdf') !== false ||
            strpos($prevCode, 'extractTextFromDocx') !== false ||
            strpos($prevCode, "cvContent = ''") !== false ||
            strpos($prevCode, '$filePath') !== false ||
            $prevCode === '}' ||
            strpos($prevCode, '// File upload') !== false ||
            strpos($prevCode, '// CV mode removed') !== false) {
            $orphanedBrace = $i;
            echo "  -> This looks like an orphaned brace, removing it\n";
            break;
        }
    }
}

if ($orphanedBrace !== null) {
    // Remove the orphaned brace line
    unset($lines[$orphanedBrace]);
    $lines = array_values($lines);
    echo "Removed orphaned } at line " . ($orphanedBrace + 1) . "\n";
} else {
    echo "No obvious orphaned brace found. Let me try another approach...\n";
    
    // Alternative: count braces in apply() method to find imbalance
    $applyStart = null;
    for ($i = 0; $i < $totalLines; $i++) {
        if (preg_match('/public function apply\(/', $lines[$i])) {
            $applyStart = $i;
            break;
        }
    }
    
    if ($applyStart === null) {
        echo "ERROR: Could not find apply() method\n";
        exit(1);
    }
    
    echo "apply() starts at line " . ($applyStart + 1) . "\n";
    
    // Walk through from apply start, counting braces
    $braceCount = 0;
    $applyBody = false;
    $extraCloses = [];
    
    for ($i = $applyStart; $i < $totalLines; $i++) {
        $line = $lines[$i];
        // Count { and }
        $opens = substr_count($line, '{');
        $closes = substr_count($line, '}');
        
        // Don't count braces in strings/comments
        // Simple heuristic: skip lines that are purely comments
        if (preg_match('/^\s*\/\//', $line) || preg_match('/^\s*\*/', $line)) {
            continue;
        }
        
        $braceCount += $opens;
        $braceCount -= $closes;
        
        if ($braceCount <= 0 && $applyBody) {
            // This is where the method ends
            echo "apply() method appears to close at line " . ($i + 1) . "\n";
            
            // Check if there's more code that should be inside
            $nextCodeLine = null;
            for ($j = $i + 1; $j < min($totalLines, $i + 10); $j++) {
                if (trim($lines[$j]) !== '' && !preg_match('/^\s*\/\//', $lines[$j])) {
                    $nextCodeLine = $j;
                    break;
                }
            }
            
            if ($nextCodeLine !== null) {
                $nextCode = trim($lines[$nextCodeLine]);
                if (strpos($nextCode, '$candidateUpdate') !== false ||
                    strpos($nextCode, '$validated') !== false ||
                    strpos($nextCode, '// Tìm') !== false) {
                    echo "Code after method close should be inside method!\n";
                    echo "Removing orphaned } at line " . ($i + 1) . "\n";
                    unset($lines[$i]);
                    $lines = array_values($lines);
                    break;
                }
            }
            break;
        }
        
        if ($opens > 0) $applyBody = true;
    }
}

// Write fixed file
file_put_contents($file, implode('', $lines));
echo "\n✅ File fixed! Size: " . filesize($file) . " bytes\n";

// Quick syntax check
exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $ret);
echo "Syntax check: " . implode("\n", $output) . "\n";
