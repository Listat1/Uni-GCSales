<?php
// api/proc_logout.php
session_start();

// 1. Clear all session variables
$_SESSION = [];

// 2. Kill the session cookie in the browser
// This ensures the browser doesn't try to send the ID back later
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session on the server
session_destroy();

// 4. Send back the success signal
header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit;