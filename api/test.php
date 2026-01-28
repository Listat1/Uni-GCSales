<?php
// api/test.php
header('Content-Type: text/plain');

echo "--- Process Info ---\n";
echo "PHP User: " . exec('whoami') . "\n";
echo "Current Script: " . __DIR__ . "\n";

// Define the target path
$targetDir = __DIR__ . '/../assets/graphics/products/';

echo "\n--- Directory Check ---\n";
echo "Target Path: " . $targetDir . "\n";

if (is_dir($targetDir)) {
    echo "Directory exists: YES\n";
    echo "Is writable: " . (is_writable($targetDir) ? "YES" : "NO") . "\n";
    echo "Permissions (Octal): " . substr(sprintf('%o', fileperms($targetDir)), -4) . "\n";
    
    echo "\n--- Folder Contents ---\n";
    $files = scandir($targetDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo " - $file\n";
        }
    }
} else {
    echo "Directory exists: NO (Path is incorrect or inaccessible)\n";
}

// Final Test: Attempt to touch a dummy file
$testFile = $targetDir . 'write_test.txt';
if (@file_put_contents($testFile, "PHP was here: " . date('Y-m-d H:i:s'))) {
    echo "\nWrite Test: SUCCESS\n";
    unlink($testFile); // Clean up
} else {
    echo "\nWrite Test: FAILED (Permission Denied)\n";
}