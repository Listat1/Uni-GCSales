The development platform highlighted issues where the libraries supplied with XAMPP
caused conflicts with the system libraries when using convert for graphics so decision
was made to use GD intead to improve cross-platform compatibilty.

// --- Defensive System Check ---
// We now know WebP is a 'No-Go' on this specific XAMPP build
if (function_exists('imagewebp')) {
    $saveFunc = 'imagewebp';
    $ext      = '.webp';
} elseif (function_exists('imagejpeg')) {
    $saveFunc = 'imagejpeg';
    $ext      = '.jpg';
} else {
    // This would only happen if the server was basically a toaster
    die("Error: Server has no viable image export formats.");
}

Used test.php to help debug.