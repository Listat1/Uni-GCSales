<?php
// api/proc_logout.php
// Session check is not performed here in case a user who was logged in has been timed out so does not have a valid session state,
// even though the browser might consider them logged in still.
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
// Return logoiut status
header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit;