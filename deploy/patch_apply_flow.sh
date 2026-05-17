#!/bin/bash
# =====================================================
# PATCH SCRIPT: Simplify Apply Flow — Upload CV Only
# Run this on VPS: bash /tmp/patch_apply.sh
# =====================================================

set -e
cd /var/www/smartcv/backend

echo "=== Step 1: Backup files ==="
cp app/Http/Controllers/CandidateJobController.php app/Http/Controllers/CandidateJobController.php.bak.$(date +%s)

echo "=== Step 2: Install smalot/pdfparser ==="
composer require smalot/pdfparser --no-interaction 2>&1 | tail -5

echo "=== Step 3: Add extractTextFromPdf method ==="
# Check if method already exists
if grep -q 'function extractTextFromPdf' app/Http/Controllers/CandidateJobController.php; then
    echo "extractTextFromPdf already exists, skipping"
else
    # Insert before extractTextFromElement method
    sed -i '/private function extractTextFromElement/i\
    /**\
     * Extract text from PDF using Smalot\\PdfParser or pdftotext fallback\
     */\
    private function extractTextFromPdf(string $filePath): string\
    {\
        try {\
            if (class_exists(\\Smalot\\PdfParser\\Parser::class)) {\
                $parser = new \\Smalot\\PdfParser\\Parser();\
                $pdf = $parser->parseFile($filePath);\
                $text = $pdf->getText();\
                $text = preg_replace('\''\/\\s+\/'\'' , '\'' '\'' , $text);\
                return trim($text);\
            }\
            $output = [];\
            $returnCode = 0;\
            exec('\''pdftotext '\'' . escapeshellarg($filePath) . '\'' -'\'', $output, $returnCode);\
            if ($returnCode === 0 \&\& !empty($output)) {\
                return trim(implode("\\n", $output));\
            }\
            \\Log::warning('\''No PDF parser available.'\'');\
            return '\''[PDF uploaded - extraction unavailable]'\'';\
        } catch (\\Throwable $e) {\
            \\Log::warning('\''PDF extract error: '\'' . $e->getMessage());\
            return '\'''\'';\
        }\
    }\
' app/Http/Controllers/CandidateJobController.php
    echo "extractTextFromPdf added"
fi

echo "=== Step 4: Fix PDF extraction in apply method ==="
# Replace the old PDF placeholder with real extraction call
sed -i "s/\$cvContent = '\[PDF content - chưa hỗ trợ extract\]'/\$cvContent = \$this->extractTextFromPdf(Storage::path(\$filePath))/" app/Http/Controllers/CandidateJobController.php

echo "=== Step 5: Remove candidateNeedsOnboarding check in apply() ==="
# Remove the onboarding redirect block (3 lines)
sed -i '/candidateNeedsOnboarding.*email/,/hoàn thiện hồ sơ/d' app/Http/Controllers/CandidateJobController.php

echo "=== Step 6: Remove cover_letter from Application::create ==="
sed -i "/cover_letter.*validated\['cover_letter'\]/d" app/Http/Controllers/CandidateJobController.php

echo "=== Step 7: Clear caches ==="
php artisan config:clear
php artisan route:clear  
php artisan view:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

echo "=== Step 8: Fix permissions ==="
chown -R www-data:www-data /var/www/smartcv/backend/vendor 2>/dev/null || true
chown -R www-data:www-data /var/www/smartcv/backend/storage 2>/dev/null || true

echo ""
echo "✅ DONE! Changes applied:"
echo "  - smalot/pdfparser installed for PDF text extraction"
echo "  - extractTextFromPdf method added"  
echo "  - PDF placeholder replaced with real extraction"
echo "  - candidateNeedsOnboarding check removed"
echo "  - cover_letter removed from Application::create"
echo ""
echo "Test: Upload a PDF CV and verify AI reads the content."
