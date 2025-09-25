<?php
// user-dashboard.php - Enhanced User Dashboard
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user data
try {
    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone, dob, profile_picture, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        session_destroy();
        header("Location: index.php");
        exit();
    }
} catch (Exception $e) {
    error_log("User data fetch error: " . $e->getMessage());
    header("Location: index.php?error=system");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Admin Panel</title>
    <script>
        // Redirect to the main admin dashboard
        window.location.href = 'admin-dashboard.html';
    </script>
</head>
<body>
    <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-family: Arial, sans-serif;">
        <div style="text-align: center;">
            <h2>Redirecting to Admin Dashboard...</h2>
            <p>If you're not redirected automatically, <a href="admin-dashboard.html" style="color: #4f46e5;">click here</a>.</p>
        </div>
    </div>
</body>
</html>