<?php
// PostgreSQL database connection
$host = 'localhost';
$port = '5432';
$dbname = 'classwall';
$username = 'postgres';
$password = 'YOUR_POSTGRES_PASSWORD'; // Change this to your PostgreSQL password

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // Show a friendly message instead of exposing database details.
    die('Database connection failed. Check db.php settings and make sure PostgreSQL is running.');
}
