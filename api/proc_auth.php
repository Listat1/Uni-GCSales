<?php
// api/proc_auth.php
session_start();
header('Content-Type: application/json');

require_once '../includes/dbconnection.php';
$db = getDatabaseConnection();

$action = $_POST['action'] ?? '';

// --- REGISTRATION LOGIC ---
if ($action === 'register') {
    $firstName = htmlspecialchars(trim($_POST['reg_first_name'] ?? ''));
    $lastName  = htmlspecialchars(trim($_POST['reg_last_name'] ?? ''));
    $username  = htmlspecialchars(trim($_POST['reg_username'] ?? ''));
    $email     = filter_var($_POST['reg_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password  = $_POST['reg_password'] ?? '';

    $hashed_pw = password_hash($password, PASSWORD_DEFAULT);

    try {
        $db->beginTransaction();

        // Matches your MariaDB columns exactly
        $stmt = $db->prepare("
            INSERT INTO users (email, username, first_name, last_name, role, is_active) 
            VALUES (?, ?, ?, ?, 'buyer', 1)
        ");
        $stmt->execute([$email, $username, $firstName, $lastName]);
        
        $newUserId = $db->lastInsertId();

        $stmtPw = $db->prepare("INSERT INTO passwords (user_id, password_hash) VALUES (?, ?)");
        $stmtPw->execute([$newUserId, $hashed_pw]);

        $db->commit();

        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = $firstName;
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        
        if ($e->getCode() == 23000) { // Unique constraint violation (username)
            echo json_encode(['success' => false, 'message' => 'That username is already taken.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
        }
    }
    exit;
}

// --- LOGIN LOGIC ---
// Test credentials, confirm if successful and assign a user level set $_SESSION[user_id,user_name,user_level]
if ($action === 'login') {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Join with our new roles table to get the numeric level
    $stmt = $db->prepare("
        SELECT u.user_id, u.first_name, u.level, p.password_hash 
        FROM users u 
        JOIN passwords p ON u.user_id = p.user_id 
        WHERE u.username = ? AND u.is_active = 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['first_name'];
        $_SESSION['user_level'] = $user['level'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    }
    exit;
}