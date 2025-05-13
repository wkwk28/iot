<?php
// PostgreSQL database configuration
$host = 'localhost';
$dbname = 'iot';
$user = 'root';
$password = ''; // Add your password if set

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Optional: Set fetch mode
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
