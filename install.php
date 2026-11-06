<?php
try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS qms_db");
    echo "Database created successfully<br>";
    
    $pdo->exec("USE qms_db");
    $sql = file_get_contents(__DIR__ . '/database.sql');
    // Remove CREATE DATABASE and USE statements
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE.*?;/i', '', $sql);
    $pdo->exec($sql);
    echo "Database imported successfully<br>";
    echo "<strong>Default Login:</strong><br>Email: admin@qms.com<br>Password: admin123<br><br>";
    echo "<a href='login.php'>Go to Login</a>";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
