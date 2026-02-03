<?php
/**
 * Master Diagnostics Script
 * This file forces PHP to reveal why it is crashing.
 */

// 1. Force error reporting (Bypasses php.ini settings)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<div style='font-family:sans-serif; padding:20px; border:5px solid #333;'>";
echo "<h1>🔧 GCSales Manual Diagnostic</h1>";

// 2. Test File Paths
echo "<h3>1. Path Integrity</h3>";
$includes_path = __DIR__ . '/includes/dbconnection.php';
echo "Checking for: <code>$includes_path</code> ... ";
if (file_exists($includes_path)) {
    echo "<b style='color:green;'>[OK]</b><br>";
} else {
    echo "<b style='color:red;'>[MISSING]</b> - Your relative paths are broken.<br>";
}

// 3. Test Database Connectivity
echo "<h3>2. Database Connectivity</h3>";
try {
    require_once $includes_path;
    $pdo = getDatabaseConnection();
    echo "<b style='color:green;'>[CONNECTED]</b> MariaDB is responding.<br>";
} catch (Throwable $e) {
    echo "<b style='color:red;'>[DB ERROR]</b> " . htmlspecialchars($e->getMessage()) . "<br>";
}

// 4. Test Session & Redirect Loops
echo "<h3>3. Session & State</h3>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "User ID in Session: " . ($_SESSION['user_id'] ?? '<i>None (Logged Out)</i>') . "<br>";

// 5. Checking for the "White Screen" culprit
echo "<h3>4. Potential Crashes</h3>";
$test_file = __DIR__ . '/api/fetch_dashboard_data.php';
if (file_exists($test_file)) {
    echo "Checking <code>fetch_dashboard_data.php</code> syntax... ";
    // This executes a 'lint' check without running the code
    $output = shell_exec("php -l " . escapeshellarg($test_file));
    echo "<b>$output</b>";
}

echo "</div>";