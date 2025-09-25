<?php
// debug-db.php - simple DB prepare diagnostic (development only)
header('Content-Type: application/json');
require_once 'config.php';

$response = ['ok' => false, 'message' => '', 'mysqli_error' => ''];

if (!isset($conn) || !$conn) {
    $response['message'] = 'No $conn available';
    echo json_encode($response);
    exit();
}

$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $response['message'] = 'prepare() returned false';
    $response['mysqli_error'] = $conn->error;
    // Also check whether table exists
    $check = $conn->query("SHOW TABLES LIKE 'users'");
    $response['users_table_exists'] = ($check && $check->num_rows > 0) ? true : false;
    echo json_encode($response);
    exit();
}

$response['ok'] = true;
$response['message'] = 'prepare() succeeded';
echo json_encode($response);

$stmt->close();
$conn->close();

?>
