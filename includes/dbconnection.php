<?php
// includes/dbconnection.php
function getDatabaseConnection() {
    // Load the .env file
    $envPath = __DIR__ . '/../.env';    // Check .env exists
    if (!file_exists($envPath)) {
        die("System Error: Configuration file missing.");
    }
    // Load EVIRONMENT_VARS
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $config = [];
    // Assign Value to each ENVAR listed into a list
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $config[trim($name)] = trim($value);
        }
    }
    // Set to local variables
    $host = $config['DB_HOST'] ?? null;
    $db   = $config['DB_NAME'] ?? null;
    $user = $config['DB_USER'] ?? null;
    $pass = $config['DB_PASS'] ?? ''; // XAMPP default is empty

    // Establish Connection
    try {
        if (!$host || !$db || !$user) {
            throw new Exception("Incomplete configuration details.");
        }
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Force Exceptions on error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // [ASSOC, NUM, or BOTH]
            PDO::ATTR_EMULATE_PREPARES   => false, // Do not use php PDO assistant
        ];

        return new PDO($dsn, $user, $pass, $options);

    } catch (Exception $e) {
        // Log error and display Eroor message for user
        error_log($e->getMessage());
        exit("The database is currently unavailable. Please try again later.");
    }
}