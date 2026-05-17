<?php
/**
 * Patch show.blade.php to simplify apply form
 * Usage: php /tmp/patch_view.php
 */

$file = '/var/www/smartcv/backend/resources/views/jobs/show.blade.php';
$content = file_get_contents($file);
file_put_contents($file . '.bak', $content);
echo "Backup created\n";

// 1. Update form header text
$content = str_replace(
    'Chọn tải CV hoặc tạo CV nhanh bằng hộp thoại',
    'Upload CV của bạn (PDF, DOC, DOCX) — AI sẽ tự động đọc và phân tích',
    $content
);
echo "1. Updated form header\n";

// 2. Remove cv_mode hidden input
$content = preg_replace(
    '/<input type="hidden" name="cv_mode"[^>]+>\s*\n/',
    '',
    $content
);
echo "2. Removed cv_mode hidden input\n";

// 3. Remove Cover Letter section
$coverStart = strpos($content, '<!-- Cover Letter -->');
if ($coverStart !== false) {
    // Find the closing </div> of the cover letter block
    $coverEnd = strpos($content, '</div>', strpos($content, '@enderror', $coverStart));
    if ($coverEnd !== false) {
        $coverEnd = strpos($content, "\n", $coverEnd) + 1;
        // Also remove the empty line after
        $nextLine = $coverEnd;
        while ($nextLine < strlen($content) && ($content[$nextLine] === "\r" || $content[$nextLine] === "\n" || $content[$nextLine] === " ")) {
            if ($content[$nextLine] === "\n") { $coverEnd = $nextLine + 1; break; }
            $nextLine++;
        }
        $content = substr($content, 0, $coverStart) . substr($content, $coverEnd);
        echo "3. Removed Cover Letter section\n";
    }
} else {
    echo "3. Cover Letter section not found\n";
}

// 4. Remove CV Mode radio section  
$cvModeStart = strpos($content, '<!-- CV Mode -->');
if ($cvModeStart !== false) {
    // Find the end of this div block
    // The CV Mode section ends with </div> after the radio buttons
    $endSearch = $cvModeStart;
    $divCount = 0;
    $found = false;
    while ($endSearch < strlen($content)) {
        if (substr($content, $endSearch, 4) === '<div') { $divCount++; }
        if (substr($content, $endSearch, 6) === '</div>') {
            $divCount--;
            if ($divCount <= 0) {
                $endSearch += 6;
                // Skip past any newlines
                while ($endSearch < strlen($content) && ($content[$endSearch] === "\r" || $content[$endSearch] === "\n")) {
                    $endSearch++;
                }
                $content = substr($content, 0, $cvModeStart) . substr($content, $endSearch);
                echo "4. Removed CV Mode section\n";
                $found = true;
                break;
            }
        }
        $endSearch++;
    }
    if (!$found) echo "4. Could not find end of CV Mode section\n";
} else {
    echo "4. CV Mode section not found\n";
}

// 5. Remove CV Form Data section (hidden inputs for form mode)
$cvFormStart = strpos($content, '<!-- CV Form Data');
if ($cvFormStart !== false) {
    $cvFormEnd = strpos($content, '</div>', strpos($content, '@enderror', strpos($content, 'education_proofs', $cvFormStart)));
    if ($cvFormEnd !== false) {
        $cvFormEnd = strpos($content, "\n", $cvFormEnd) + 1;
        while ($cvFormEnd < strlen($content) && ($content[$cvFormEnd] === "\r" || $content[$cvFormEnd] === "\n" || $content[$cvFormEnd] === " ")) {
            if ($content[$cvFormEnd] === "\n") { $cvFormEnd++; break; }
            $cvFormEnd++;
        }
        $content = substr($content, 0, $cvFormStart) . substr($content, $cvFormEnd);
        echo "5. Removed CV Form Data section\n";
    }
} else {
    echo "5. CV Form Data section not found\n";
}

// 6. Update upload hint text 
$content = str_replace(
    'DOC, DOCX, PDF (Max 5MB)',
    'PDF, DOC, DOCX (Max 5MB) — AI sẽ tự đọc nội dung',
    $content
);
echo "6. Updated upload hint text\n";

file_put_contents($file, $content);
echo "\n✅ View patched successfully!\n";
