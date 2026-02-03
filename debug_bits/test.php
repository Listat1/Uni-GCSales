<?php
// api/test.php
header('Content-Type: text/html; charset=utf-8');
echo "<style>body { font-family: monospace; line-height: 1.5; background: #f4f4f4; padding: 20px; } h1 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 5px; }</style>";

echo "<h1>--- TEST 1: Process Info ---</h1>";
echo "PHP User: " . exec('whoami') . "<br>";
echo "Current Script: " . __DIR__ . "<br>";

$targetDir = __DIR__ . '/../assets/graphics/products/';

echo "<h1>--- TEST 2: Directory Check ---</h1>";
if (is_dir($targetDir)) {
    echo "Directory exists: YES<br>";
    echo "Is writable: " . (is_writable($targetDir) ? "YES" : "<strong>NO</strong>") . "<br>";
}

echo "<h1>--- TEST 3: Folder Contents ---</h1>";
$files = scandir($targetDir);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') echo " - $file<br>";
}

echo "<h1>--- TEST 4: File System Write Test ---</h1>";
$testFile = $targetDir . 'write_test.txt';
// Clear previous errors
error_clear_last();
if (@file_put_contents($testFile, "PHP was here: " . date('Y-m-d H:i:s'))) {
    echo "Write Test: SUCCESS<br>";
    unlink($testFile); 
} else {
    $err = error_get_last();
    echo "Write Test: <strong>FAILED</strong><br>";
    echo "System Error: " . ($err['message'] ?? 'No error message returned') . "<br>";
}

echo "<h1>--- TEST 5: ImageMagick (Imagick) Check ---</h1>";
if (extension_loaded('imagick')) {
    echo "Imagick Extension: INSTALLED<br>";
} else {
    echo "Imagick Extension: <strong>NOT FOUND</strong><br>";
}

echo "<h1>--- The Definitive Proof ---</h1>";
if (function_exists('imagewebp')) {
    echo "Function imagewebp() exists.<br>";
} else {
    echo "Function imagewebp() <strong>DOES NOT EXIST</strong>.<br>";
    echo "Reason: Your XAMPP PHP binary was compiled without WebP support.";
}

if (function_exists('imagejpeg')) {
    echo "Function imagejpeg() exists. (Use this as your fallback).<br>";
}

echo "<h1>--- TEST 6: GD Library Check ---</h1>";
if (extension_loaded('gd')) {
    echo "GD Extension: INSTALLED<br>";
    
    // -- TEST WEBP DECODING/ENCODING --
    error_clear_last();
    echo "<strong>Attempting WebP operation:</strong><br>";
    $img = @imagecreatetruecolor(10, 10);
    if ($img) {
        // Attempting to output WebP to a buffer to see if the driver is actually there
        ob_start();
        $webp_success = @imagewebp($img);
        ob_end_clean();
        imagedestroy($img);

        if ($webp_success) {
            echo "WebP Support: YES<br>";
        } else {
            $err = error_get_last();
            echo "WebP Support: <strong>NO</strong><br>";
            echo "System Error: " . ($err['message'] ?? 'The imagewebp() function returned false without a message (likely missing driver).') . "<br>";
        }
    }

    // -- TEST JPEG DECODING/ENCODING --
    error_clear_last();
    echo "<br><strong>Attempting JPEG operation:</strong><br>";
    $img = @imagecreatetruecolor(10, 10);
    if ($img) {
        ob_start();
        $jpeg_success = @imagejpeg($img);
        ob_end_clean();
        imagedestroy($img);

        if ($jpeg_success) {
            echo "JPEG Support: YES<br>";
        } else {
            $err = error_get_last();
            echo "JPEG Support: <strong>NO</strong><br>";
            echo "System Error: " . ($err['message'] ?? 'The imagejpeg() function failed to initialize.') . "<br>";
        }
    }
} else {
    echo "GD Extension: <strong>NOT FOUND</strong><br>";
}
?>