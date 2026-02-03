<?php
// api/proc_auth.php
// Session check is not performed here because a user cannot log if a criteria of logging in is thast they are logged in.
session_start();
header('Content-Type: application/json');

require_once '../includes/dbconnection.php';
$db = getDatabaseConnection();

$action = $_POST['action'] ?? '';

// REGISTRATION LOGIC
if ($action === 'register') {
    require_once '../includes/geo_calc.php'; 

    // Sanitise basic user info
    $firstName = htmlspecialchars(trim($_POST['reg_first_name'] ?? ''));
    $lastName  = htmlspecialchars(trim($_POST['reg_last_name'] ?? ''));
    $username  = htmlspecialchars(trim($_POST['reg_username'] ?? ''));
    $email     = filter_var($_POST['reg_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password  = $_POST['reg_password'] ?? '';
    
    // Address Fields from Modal (populated by your JS Geo-tool)
    $houseNum  = htmlspecialchars(trim($_POST['reg_house_num'] ?? ''));
    $street    = htmlspecialchars(trim($_POST['reg_address_1'] ?? ''));
    $city      = htmlspecialchars(trim($_POST['reg_city'] ?? 'Grimsby')); 
    $postcode  = strtoupper(trim($_POST['reg_postcode'] ?? ''));

    // Combine House + Street for MariaDB 'address_line_1'
    $addressLine1 = trim($houseNum . ' ' . $street);

    // 1. Geo-Fence Check (The 10-mile rule)
    if (!isLocalPostcode($postcode)) {
        echo json_encode(['success' => false, 'message' => 'Sorry, we only accept members within 10 miles of Grimsby/Cleethorpes.']);
        exit;
    }

    $hashed_pw = password_hash($password, PASSWORD_DEFAULT);

    try {
        // START TRANSACTION (ACID Compliance)
        $db->beginTransaction();

        // A. Insert User Record
        $sqlUser = "INSERT INTO users (email, username, first_name, last_name, level, is_active) 
                    VALUES (?, ?, ?, ?, 10, 1)";
        $stmt = $db->prepare($sqlUser);
        $stmt->execute([$email, $username, $firstName, $lastName]);
        
        // Capture the New ID immediately (Safe in MariaDB/InnoDB)
        $newUserId = $db->lastInsertId();

        // B. Insert Password Record (Linked by user_id)
        $sqlPw = "INSERT INTO passwords (user_id, password_hash) VALUES (?, ?)";
        $stmtPw = $db->prepare($sqlPw);
        $stmtPw->execute([$newUserId, $hashed_pw]);

        // C. Insert Address Record (Linked by user_id)
        $sqlAddr = "INSERT INTO addresses (user_id, address_type, address_line_1, city, postcode, is_default) 
                    VALUES (?, 'Home', ?, ?, ?, 1)";
        $stmtAddr = $db->prepare($sqlAddr);
        $stmtAddr->execute([$newUserId, $addressLine1, $city, $postcode]);
        // Get the record id of the Address just added to database
        $addressId = $db->lastInsertId(); // Capture the ID from the addresses table

        // COMMIT: Everything is written at once
        $db->commit();

        // Setup Session
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = $firstName;
        $_SESSION['user_level'] = 10;
        $_SESSION['user_address_id'] = $addressId;
        
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        // ROLLBACK: If any of the 3 inserts fail, undo EVERYTHING
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        // Log the actual error for your eyes, but show a clean message to Graham
        error_log("DB Error during registration: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Registration failed. Username or email may already be in use.']);
    }
    exit;
}

// LOGIN LOGIC
if ($action === 'login') {
    $username = htmlspecialchars(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

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
        $addrStmt = $db->prepare(
            query: "SELECT address_id
            FROM addresses 
            WHERE user_id = ? AND is_default = 1 
            LIMIT 1");
        $addrStmt->execute([$user['user_id']]);
        $_SESSION['user_address_id'] = $addrStmt->fetchColumn();    
    
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    }
    exit;
}