<?php
$host = 'db';
$dbname = 'btc_prices';
$username = 'satoshi';
$password = 'S3nh-a';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Criar tabela se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS prices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        price DECIMAL(10, 2) NOT NULL
    )");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>