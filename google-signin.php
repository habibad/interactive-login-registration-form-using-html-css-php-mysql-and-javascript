<?php
// google-signin.php - Handle Google Sign-In
session_start();
require_once 'config.php';
// Use Composer autoload for PHPMailer and other dependencies (safer path)
require_once __DIR__ . '/vendor/autoload.php';

// Ensure PHP does not emit HTML error pages into JSON responses
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
// Start output buffering to prevent accidental HTML output from leaking
ob_start();

function send_json($data, $http_code = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($http_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Shutdown handler to catch fatal errors and return JSON when possible
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Attempt to return JSON instead of HTML
        if (ob_get_length()) ob_clean();
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Fatal server error', 'details' => $err]);
        exit();
    }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['token']) || !isset($input['user_data'])) {
    send_json(['error' => 'Invalid request data'], 400);
}

$token = $input['token'];
$userData = $input['user_data'];

// Verify Google ID token using Google's tokeninfo endpoint (lightweight server-side check)
$googleTokenInfoUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($token);

// Use cURL to fetch tokeninfo for better error handling and compatibility
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $googleTokenInfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$tokenInfo = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tokenInfo === false || $tokenInfo === '') {
    error_log('Google token verification failed: curl error: ' . $curlErr);
    $resp = ['error' => 'Google token verification failed'];
    if (!empty($input['debug'])) $resp['curl_error'] = $curlErr;
    send_json($resp, 400);
}

$tokenInfoData = json_decode($tokenInfo, true);
if (!isset($tokenInfoData['aud'])) {
    error_log('Google token verification failed: invalid tokeninfo response');
    send_json(['error' => 'Invalid Google token'], 400);
}

// Check that the token audience matches our client id
if ($tokenInfoData['aud'] !== GOOGLE_CLIENT_ID) {
    error_log('Google token audience mismatch. Token aud: ' . $tokenInfoData['aud'] . ' expected: ' . GOOGLE_CLIENT_ID);
    send_json(['error' => 'Token audience mismatch. Check GOOGLE_CLIENT_ID and authorized origins.'], 403);
}

// Extract user data from token info for authoritative values
$userData = $tokenInfoData; // overwrite with verified values
$googleId = $userData['sub'];
$email = $userData['email'] ?? '';
$firstName = $userData['given_name'] ?? '';
$lastName = $userData['family_name'] ?? '';
$profilePicture = $userData['picture'] ?? '';

try {
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM users WHERE email = ? OR google_id = ?");
    if ($stmt === false) {
        $dberr = $conn->error;
        error_log('DB prepare failed (select user): ' . $dberr);
        $resp = ['error' => 'Database error during user lookup'];
        if (!empty($input['debug'])) $resp['db_error'] = $dberr;
        send_json($resp, 500);
    }
    $stmt->bind_param("ss", $email, $googleId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User exists, log them in
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_email'] = $email;
        $_SESSION['login_type'] = 'google';
        
        send_json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user
        ]);
    } else {
        // Create new user
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, google_id, profile_picture, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($stmt === false) {
            $dberr = $conn->error;
            error_log('DB prepare failed (insert user): ' . $dberr);
            $resp = ['error' => 'Database error during user creation'];
            if (!empty($input['debug'])) $resp['db_error'] = $dberr;
            send_json($resp, 500);
        }
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $googleId, $profilePicture);
        
        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            $_SESSION['user_email'] = $email;
            $_SESSION['login_type'] = 'google';
            
            send_json([
                'success' => true,
                'message' => 'Account created and login successful',
                'user' => [
                    'id' => $userId,
                    'first_name' => $firstName,
                    'last_name' => $lastName
                ]
            ]);
        } else {
            throw new Exception('Failed to create user account');
        }
    }
} catch (Exception $e) {
    error_log('Google sign-in exception: ' . $e->getMessage());
    send_json(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>