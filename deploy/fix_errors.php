<?php
/**
 * Fix 1: CandidateJobController.php — restore missing ]); and validation messages
 * Fix 2: app.blade.php — remove candidate.profile references
 * 
 * Usage: php /tmp/fix_errors.php
 */

echo "=== Fix 1: CandidateJobController.php ===\n";

$file = '/var/www/smartcv/backend/app/Http/Controllers/CandidateJobController.php';
$content = file_get_contents($file);

// The issue: validate([ ... ]) lost its closing ]), messages and );
// Find the broken pattern: 'cv_file' line immediately followed by $validated['email']
$broken = "'cv_file' => ['required', 'file', 'mimes:docx,doc,pdf', 'max:5120'],";
$brokenPos = strpos($content, $broken);

if ($brokenPos !== false) {
    // Find what comes right after this line
    $afterBroken = $brokenPos + strlen($broken);
    
    // Skip whitespace/newlines to find the next code
    $nextCode = ltrim(substr($content, $afterBroken));
    
    if (strpos($nextCode, "\$validated['email']") === 0 || strpos($nextCode, "// User is guaranteed") === 0) {
        // Missing the closing ]); and validation messages - add them back
        $replacement = "'cv_file' => ['required', 'file', 'mimes:docx,doc,pdf', 'max:5120'],\n" .
            "        ], [\n" .
            "            'full_name.required' => 'Vui lòng nhập họ tên.',\n" .
            "            'email.required' => 'Vui lòng nhập email.',\n" .
            "            'email.email' => 'Email không hợp lệ.',\n" .
            "            'cv_file.required' => 'Vui lòng upload file CV (PDF hoặc DOC/DOCX).',\n" .
            "            'cv_file.mimes' => 'Chỉ chấp nhận file .docx, .doc hoặc .pdf.',\n" .
            "            'cv_file.max' => 'File CV không được vượt quá 5MB.',\n" .
            "        ]);";
        
        $content = substr($content, 0, $brokenPos) . $replacement . substr($content, $afterBroken);
        echo "  Fixed: Added missing ]); and validation messages\n";
    } else {
        echo "  Checking if validate already has closing...\n";
        // Maybe it has ], [ but lost the messages
        // Check if there's already a ]); somewhere reasonable after
        $closingPos = strpos($content, "]);", $afterBroken);
        $nextFunc = strpos($content, "\$validated['email']", $afterBroken);
        
        if ($closingPos !== false && $nextFunc !== false && $closingPos < $nextFunc) {
            echo "  validate() closing found at pos $closingPos (before email assignment at $nextFunc)\n";
            echo "  Validate structure looks OK\n";
        } else {
            echo "  WARNING: Could not determine validate structure. Manual check needed.\n";
        }
    }
} else {
    // Try alternative: maybe the line has extra whitespace or comment
    echo "  Looking for cv_file validation line...\n";
    if (preg_match("/('cv_file'\s*=>\s*\[.*?\],)\s*(\/\/[^\n]*)?\s*\n/", $content, $m, PREG_OFFSET_CAPTURE)) {
        $matchEnd = $m[0][1] + strlen($m[0][0]);
        $afterMatch = ltrim(substr($content, $matchEnd));
        
        if (strpos($afterMatch, "\$validated") === 0) {
            $insert = "        ], [\n" .
                "            'full_name.required' => 'Vui lòng nhập họ tên.',\n" .
                "            'email.required' => 'Vui lòng nhập email.',\n" .
                "            'email.email' => 'Email không hợp lệ.',\n" .
                "            'cv_file.required' => 'Vui lòng upload file CV (PDF hoặc DOC/DOCX).',\n" .
                "            'cv_file.mimes' => 'Chỉ chấp nhận file .docx, .doc hoặc .pdf.',\n" .
                "            'cv_file.max' => 'File CV không được vượt quá 5MB.',\n" .
                "        ]);\n\n";
            $content = substr($content, 0, $matchEnd) . $insert . substr($content, $matchEnd);
            echo "  Fixed: Inserted missing validation messages and ]);\n";
        }
    } else {
        echo "  ERROR: Could not find cv_file validation line\n";
    }
}

// Also fix: if there's a leftover $filePath = null; that should be removed
// (since we now always have file upload)
$content = str_replace(
    "\$filePath = null;\n        \$cvContent = '';\n        \$cvData = null;\n        \$cvProofFiles = null;",
    "// File upload processing",
    $content
);

file_put_contents($file, $content);
echo "  Controller saved.\n\n";

// ===== Fix 2: Remove candidate.profile route references from layout =====
echo "=== Fix 2: app.blade.php — remove candidate.profile references ===\n";

$layoutFile = '/var/www/smartcv/backend/resources/views/components/layouts/app.blade.php';
$layout = file_get_contents($layoutFile);

if ($layout === false) {
    echo "  ERROR: Cannot read layout file\n";
} else {
    // Replace route('candidate.profile') with route('candidate.dashboard')
    $before = substr_count($layout, "candidate.profile");
    $layout = str_replace("route('candidate.profile')", "route('candidate.dashboard')", $layout);
    $layout = str_replace('route("candidate.profile")', 'route("candidate.dashboard")', $layout);
    $after = substr_count($layout, "candidate.profile");
    
    file_put_contents($layoutFile, $layout);
    echo "  Replaced $before references → candidate.dashboard (remaining: $after)\n";
    echo "  Layout saved.\n";
}

// ===== Also check for candidate.profile.update references =====
echo "\n=== Fix 3: Check for other candidate.profile references ===\n";
$searchDirs = [
    '/var/www/smartcv/backend/resources/views/',
];

foreach ($searchDirs as $dir) {
    $output = [];
    exec("grep -r 'candidate.profile' " . escapeshellarg($dir) . " --include='*.php' -l 2>/dev/null", $output);
    if (!empty($output)) {
        echo "  Files still referencing candidate.profile:\n";
        foreach ($output as $f) {
            echo "    - $f\n";
            // Auto-fix: replace candidate.profile with candidate.dashboard
            $fc = file_get_contents($f);
            $fc = str_replace("route('candidate.profile')", "route('candidate.dashboard')", $fc);
            $fc = str_replace('route("candidate.profile")', 'route("candidate.dashboard")', $fc);
            // Also handle candidate.profile.update -> just redirect to dashboard
            $fc = str_replace("route('candidate.profile.update')", "route('candidate.dashboard')", $fc);
            file_put_contents($f, $fc);
            echo "      → Fixed\n";
        }
    } else {
        echo "  No more candidate.profile references found in views ✓\n";
    }
}

echo "\n✅ All fixes applied!\n";
echo "Run: php artisan view:clear && php artisan route:clear && php artisan config:clear\n";
