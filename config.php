<?php
// config.php - Enhanced Database Configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dashboard";


// Load Composer autoload if present (provides PHPMailer and other deps)
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Log a helpful message; don't exit so the app can still run without mailing features
    error_log("Composer autoload not found at " . $autoloadPath . ". If you need emailing, run `composer install`.");
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '');
define('GOOGLE_CLIENT_SECRET', '');
?>