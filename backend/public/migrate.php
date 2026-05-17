<?php
// Run this via php
$output = shell_exec("cd d:\\web\\cpanel_public_html\\backend && php artisan migrate");
echo $output;
