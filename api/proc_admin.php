<?php
// api/proc_admin.php
session_start();
require_once '../includes/dbconnection.php';
$pdo = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?msg=auth");
    exit;
}

// 1. Get current admin details
$current_user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT level FROM users WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$admin_level = (int)$stmt->fetchColumn();

// Gatekeeper: Min level 30
if ($admin_level < 30) {
    die("Unauthorized access.");
}

$target_user_id = $_POST['user_id'] ?? null;
$action         = $_POST['action'] ?? null;

// Validation: Don't let them accidentally delete or demote themselves
if (!$target_user_id || $target_user_id == $current_user_id) {
    header("Location: ../dashboard.php?msg=error&item=Invalid Target (Self-Action)");
    exit;
}

// ... (Top of file stays the same) ...

try {
    switch ($action) {
        case 'promote':
            $new_level = (int)$_POST['level'];
            if ($new_level > $admin_level) {
                header("Location: ../dashboard.php?msg=error");
                exit;
            }
            $stmt = $pdo->prepare("UPDATE users SET level = ? WHERE user_id = ?");
            $stmt->execute([$new_level, $target_user_id]);
            header("Location: ../dashboard.php?msg=updated&item=User Level");
            exit; // Stop here!

        case 'reset':
            $passwordHash = password_hash('PASSWORD', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE passwords SET password_hash = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$passwordHash, $target_user_id]);
            header("Location: ../dashboard.php?msg=updated&item=Password Reset");
            exit;

        case 'delete':
            if ($admin_level < 40) {
                header("Location: ../dashboard.php?msg=error");
                exit;
            }

            $pdo->beginTransaction();

            try {
                $timestamp = date('d-m-Y_Hi'); 
                $anonUsername = "deleted_" . $timestamp . "_" . $target_user_id;

                // 1. Anonymise
                $stmt = $pdo->prepare("UPDATE users SET email = 'N/A', username = ?, first_name = 'Deleted', last_name = 'User', level = 0, is_active = 0 WHERE user_id = ?");
                $stmt->execute([$anonUsername, $target_user_id]);

                // 2. Purge Addresses
                $stmt = $pdo->prepare("DELETE FROM addresses WHERE user_id = ?");
                $stmt->execute([$target_user_id]);

                // 3. Out of Cheese Error (Sell & Zero)
                $systemNote = "Deleted User, system update, Out of cheese error";
                $stmt = $pdo->prepare("UPDATE products SET status_id = 3, price = 0.00, description = ? WHERE seller_id = ? AND status_id = 1");
                $stmt->execute([$systemNote, $target_user_id]);

                $pdo->commit();
                header("Location: ../dashboard.php?msg=updated&item=User Purge");
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                header("Location: ../dashboard.php?msg=error");
                exit;
            }
            // No break needed because we exit inside the try/catch

        default:
            header("Location: ../dashboard.php?msg=error");
            exit;
    }
} catch (PDOException $e) {
    header("Location: ../dashboard.php?msg=error");
    exit;
}
