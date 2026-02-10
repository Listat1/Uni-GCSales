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
    // Only display fields User is allowed to edit
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

    // No Errors - Redirect back to dashboard
    header("Location: ../dashboard.php?msg=updated&item=Profile");
    exit;

} 
catch (PDOException $e) {
    // Error code 23000:= Integrity constraint violation (like Unique keys)
    if ($e->getCode() == 23000) {
        header("Location: ../edit_profile.php?msg=exists");
    } else {
        header("Location: ../edit_profile.php?msg=error");
    }
    exit;
}