<?php
// api/proc_logout.php
// Connect to current session
session_start();
// Clear all session variables
$_SESSION = [];
// Kill the session cookie in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
// Kill session on server
session_destroy();
// Inform calling routine logout is complete
header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit;