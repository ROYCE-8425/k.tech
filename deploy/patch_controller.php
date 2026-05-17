<?php
/**
 * Patch CandidateJobController.php on VPS
 * Usage: php /tmp/patch_controller.php
 */

$file = '/var/www/smartcv/backend/app/Http/Controllers/CandidateJobController.php';
$content = file_get_contents($file);

if ($content === false) {
    echo "ERROR: Cannot read file\n";
    exit(1);
}

// Backup
file_put_contents($file . '.bak', $content);
echo "Backup created\n";

// 1. Remove candidateNeedsOnboarding check in apply()
$content = preg_replace(
    '/\s*if \(\$this->candidateNeedsOnboarding\(\$user->email\)\) \{[^}]+\}\s*/s',
    "\n",
    $content,
    1
);
echo "1. Removed candidateNeedsOnboarding check\n";

// 2. Remove cv_mode variable and form-mode validation rules
$content = str_replace(
    "\$cvMode = \$request->input('cv_mode', 'upload');",
    "// CV mode removed - upload only",
    $content
);
echo "2. Removed cv_mode variable\n";

// 3. Simplify validation - remove form-specific rules
$content = preg_replace(
    "/'cv_mode'\s*=>\s*\['nullable',\s*Rule::in\(\['upload',\s*'form'\]\)\],\s*\n/",
    "",
    $content
);
$content = preg_replace(
    "/'cv_file'\s*=>\s*\['required_if:cv_mode,upload',\s*'nullable',/",
    "'cv_file' => ['required',",
    $content
);
$content = preg_replace(
    "/'cover_letter'\s*=>\s*\['nullable',\s*'string',\s*'max:5000'\],\s*\n/",
    "",
    $content
);
// Remove form CV mode validation rules
$content = preg_replace(
    "/\s*\/\/ Form CV mode\s*\n\s*'self_description'[^;]+;\s*\n/s",
    "",
    $content
);
$content = preg_replace(
    "/'education_json'\s*=>\s*\['nullable'[^\]]+\],\s*\n/", "", $content
);
$content = preg_replace(
    "/'work_experiences_json'\s*=>\s*\['nullable'[^\]]+\],\s*\n/", "", $content
);
$content = preg_replace(
    "/'skills_json'\s*=>\s*\['nullable'[^\]]+\],\s*\n/", "", $content
);
$content = preg_replace(
    "/'certifications_json'\s*=>\s*\['nullable'[^\]]+\],\s*\n/", "", $content
);
$content = preg_replace(
    "/'education_proofs'\s*=>\s*\['nullable'[^\]]+\],\s*\n/", "", $content
);
$content = preg_replace(
    "/'education_proofs\.\*'\s*=>\s*\['file'[^\]]+\],\s*\n/", "", $content
);
// Fix validation messages
$content = str_replace(
    "'cv_file.required_if' => 'Vui lòng upload file CV.',",
    "'cv_file.required' => 'Vui lòng upload file CV (PDF hoặc DOC/DOCX).',",
    $content
);
$content = preg_replace(
    "/'self_description\.required_if'[^,]+,\s*\n/", "", $content
);
$content = preg_replace(
    "/'education_proofs\.array'[^,]+,\s*\n/", "", $content
);
echo "3. Simplified validation rules\n";

// 4. Fix PDF extraction
$content = str_replace(
    "\$cvContent = '[PDF content - chưa hỗ trợ extract]';",
    "\$cvContent = \$this->extractTextFromPdf(Storage::path(\$filePath));",
    $content
);
// Remove the TODO comment
$content = str_replace(
    "                // TODO: Xử lý PDF sau (có thể dùng smalot/pdfparser hoặc spatie/pdf-to-text)\n",
    "",
    $content
);
echo "4. Fixed PDF extraction\n";

// 5. Remove if ($cvMode === 'upload') wrapper (make upload the only path)
// Replace: if ($cvMode === 'upload') { ... }
$content = str_replace(
    "if (\$cvMode === 'upload') {",
    "// File upload processing (always)",
    $content
);
echo "5. Removed cvMode upload check\n";

// 6. Remove the entire form mode block: if ($cvMode === 'form') { ... }
// This is complex - find and remove the entire block
$formStart = strpos($content, "if (\$cvMode === 'form') {");
if ($formStart !== false) {
    // Find matching closing brace
    $braceCount = 0;
    $i = $formStart;
    $started = false;
    while ($i < strlen($content)) {
        if ($content[$i] === '{') {
            $braceCount++;
            $started = true;
        } elseif ($content[$i] === '}') {
            $braceCount--;
            if ($started && $braceCount === 0) {
                // Found the closing brace
                $formEnd = $i + 1;
                // Remove the block
                $content = substr($content, 0, $formStart) . substr($content, $formEnd);
                echo "6. Removed form mode block\n";
                break;
            }
        }
        $i++;
    }
} else {
    echo "6. Form mode block not found (already removed?)\n";
}

// 7. Remove cover_letter from Application::create
$content = preg_replace(
    "/\s*'cover_letter'\s*=>\s*\\\$validated\['cover_letter'\]\s*\?\?\s*null,\s*\n/",
    "\n",
    $content
);
$content = preg_replace(
    "/\s*'cv_proof_files'\s*=>\s*\\\$cvProofFiles,\s*\n/",
    "\n",
    $content
);
echo "7. Removed cover_letter and cv_proof_files from Application::create\n";

// 8. Remove the cv_quick profile save block (if cvMode === 'form')
$cvQuickStart = strpos($content, "if (\$user && \$user->role === 'candidate' && \$cvMode === 'form'");
if ($cvQuickStart !== false) {
    $braceCount = 0;
    $i = $cvQuickStart;
    $started = false;
    while ($i < strlen($content)) {
        if ($content[$i] === '{') { $braceCount++; $started = true; }
        elseif ($content[$i] === '}') {
            $braceCount--;
            if ($started && $braceCount === 0) {
                $content = substr($content, 0, $cvQuickStart) . substr($content, $i + 1);
                echo "8. Removed cv_quick profile save block\n";
                break;
            }
        }
        $i++;
    }
} else {
    echo "8. cv_quick profile save block not found (already removed?)\n";
}

// 9. Add extractTextFromPdf method (before extractTextFromElement)
if (strpos($content, 'function extractTextFromPdf') === false) {
    $pdfMethod = <<<'PHP'

    /**
     * Extract text từ file PDF sử dụng Smalot\PdfParser
     */
    private function extractTextFromPdf(string $filePath): string
    {
        try {
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($filePath);
                $text = $pdf->getText();
                $text = preg_replace('/\s+/', ' ', $text);
                return trim($text);
            }

            // Fallback: pdftotext (poppler-utils)
            $output = [];
            $returnCode = 0;
            exec('pdftotext ' . escapeshellarg($filePath) . ' -', $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                return trim(implode("\n", $output));
            }

            \Log::warning('No PDF parser available.');
            return '[PDF uploaded - extraction unavailable]';
        } catch (\Throwable $e) {
            \Log::warning('PDF extract error: ' . $e->getMessage());
            return '';
        }
    }

PHP;
    $content = str_replace(
        "    /**\n     * Đệ quy extract text từ các element của PHPWord",
        $pdfMethod . "    /**\n     * Đệ quy extract text từ các element của PHPWord",
        $content
    );
    // Also try with \r\n line endings
    $content = str_replace(
        "    /**\r\n     * Đệ quy extract text từ các element của PHPWord",
        $pdfMethod . "    /**\r\n     * Đệ quy extract text từ các element của PHPWord",
        $content
    );
    echo "9. Added extractTextFromPdf method\n";
} else {
    echo "9. extractTextFromPdf already exists\n";
}

// Write the patched file
file_put_contents($file, $content);
echo "\n✅ File patched successfully!\n";
echo "Total size: " . strlen($content) . " bytes\n";
