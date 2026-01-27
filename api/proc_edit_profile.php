<?php
// api/proc_edit_profile.php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit;
}

require_once '../includes/dbconnection.php';
$pdo = getDatabaseConnection();
$user_id = $_SESSION['user_id'];

// Sanitise required contents of $POST
$first_name = trim($_POST['first_name']);
$last_name  = trim($_POST['last_name']);
$username   = trim($_POST['username']);
$email      = trim($_POST['email']);
$new_level  = isset($_POST['level']) ? (int)$_POST['level'] : null;

try {
    // Confirm access level of user
    $stmt = $pdo->prepare("SELECT level FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch();

if ($current_user['level'] >= 30 && $new_level !== null) {
        $sql = "UPDATE users 
            SET first_name = ?, last_name = ?, username = ?, email = ?, level = ? 
            WHERE user_id = ?";
        $params = [$first_name, $last_name, $username, $email, $new_level, $user_id];
    } else {
        $sql = "UPDATE users 
            SET first_name = ?, last_name = ?, username = ?, email = ?
            WHERE user_id = ?";
        $params = [$first_name, $last_name, $username, $email, $user_id];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // 4. Redirect with Success
    header("Location: ../dashboard.php?msg=updated");
    exit;

} catch (PDOException $e) {
    // In a production app, log $e->getMessage() and show a generic error
    header("Location: edit_profile.php?msg=error");
    exit;
}