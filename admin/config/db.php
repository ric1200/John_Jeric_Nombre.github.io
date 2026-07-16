<?php
// config/db.php

// 1. Hanapin ang .env file sa labas ng config folder (sa root directory ng project)
$envPath = __DIR__ . '/../.env'; 

if (file_exists($envPath)) {
    // Basahin ang credentials mula sa .env file
    $envVariables = parse_ini_file($envPath);
    
    $host     = $envVariables['DB_HOST'] ?? '';
    $port     = $envVariables['DB_PORT'] ?? '6543';
    $dbname   = $envVariables['DB_NAME'] ?? 'postgres';
    $user     = $envVariables['DB_USER'] ?? '';
    $password = $envVariables['DB_PASSWORD'] ?? '';
} else {
    // Kung nasa live server na (tulad ng Render, Heroku, etc.), kunin sa Server Environment
    $host     = getenv('DB_HOST');
    $port     = getenv('DB_PORT');
    $dbname   = getenv('DB_NAME');
    $user     = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
}

// 2. I-setup ang PostgreSQL Data Source Name (DSN) para sa Supabase
// Format: pgsql:host=...;port=...;dbname=...
$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    // 3. Gumawa ng PDO connection
    $pdo = new PDO($dsn, $user, $password);
    
    // 4. I-set ang error mode sa Exception para madaling ma-detect kung may mali sa SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 5. I-set ang default fetch mode sa Associative Array (para malinis ang pagkuha ng data)
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Kung mag-fail ang connection, itigil ang script at ipakita ang error
    die("Database Connection Failed: " . $e->getMessage());
}
?>