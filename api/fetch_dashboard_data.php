<?php
session_start();
require_once '../db_connect.php'; // Your XAMPP DB connection

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = [];

// Get Profile Info
$stmt = $pdo->prepare("SELECT name, email, created_at, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$data['profile'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Get Items the user is selling
$stmt = $pdo->prepare("SELECT id, name, price, created_at FROM products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$data['products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Return as JSON
header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $data]);