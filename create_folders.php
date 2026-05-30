<?php
// upload_debug.php - Check why uploads fail

echo "<h2>Upload Diagnostic</h2>";

// 1. Check if uploads folder exists
$uploads = __DIR__ . '/uploads/';
$reports_folder = __DIR__ . '/uploads/reports/';

echo "<h3>Folder Status:</h3>";
echo "Uploads folder exists: " . (file_exists($uploads) ? '✅ Yes' : '❌ No') . "<br>";
echo "Reports folder exists: " . (file_exists($reports_folder) ? '✅ Yes' : '❌ No') . "<br>";

// 2. Check permissions
if (file_exists($reports_folder)) {
    echo "Reports folder writable: " . (is_writable($reports_folder) ? '✅ Yes' : '❌ No') . "<br>";
}

// 3. Check PHP settings
echo "<h3>PHP Settings:</h3>";
echo "file_uploads: " . (ini_get('file_uploads') ? '✅ On' : '❌ Off') . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . " seconds<br>";

// 4. Check if any files are in the folder
if (file_exists($reports_folder)) {
    $files = scandir($reports_folder);
    $files = array_diff($files, ['.', '..']);
    echo "<h3>Files in uploads/reports/: " . count($files) . "</h3>";
    foreach ($files as $file) {
        echo "• $file<br>";
    }
}
?>