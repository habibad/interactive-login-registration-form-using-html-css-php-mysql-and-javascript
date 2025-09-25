<?php
// forget.php - Enhanced Password Reset with Auto-Detection
session_start();
require_once 'config.php';
require_once 'smtp.php';

if (isset($_POST["forget"])) {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: forget.php?error=invalidemail");
        exit();
    }
    
    try {
        $stmt = $conn->prepare("SELECT id, first_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_email'] = $email;
            
            // Generate 6-digit code
            $verificationCode = sprintf('%06d', rand(0, 999999));
            $_SESSION['verification_code'] = $verificationCode;
            $_SESSION['code_timestamp'] = time();
            
            // Send email using enhanced mailer
            $mailer = new EnhancedMailer();
            $result = $mailer->sendVerificationCode($email, $verificationCode, 'password_reset');
            
            if ($result['success']) {
                header("Location: reset-password.php?email=" . urlencode($email));
                exit();
            } else {
                header("Location: forget.php?error=emailfailed");
                exit();
            }
        } else {
            // Don't reveal if email exists or not for security
            header("Location: reset-password.php?email=" . urlencode($email));
            exit();
        }
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        header("Location: forget.php?error=system");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 8px;
            font-weight: 500;
        }
        input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 20px rgba(79, 70, 229, 0.3);
        }
        input[type="email"]::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        .btn-primary {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.4);
        }
        .error-message {
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(255, 107, 107, 0.2);
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .back-link a:hover {
            color: white;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Reset Password</h2>
        
        <?php
        $errorMessages = [
            'invalidemail' => 'Please enter a valid email address.',
            'emailfailed' => 'Failed to send verification email. Please try again.',
            'system' => 'System error occurred. Please try again later.'
        ];
        
        if (isset($_GET["error"]) && isset($errorMessages[$_GET["error"]])) {
            echo "<div class='error-message'>" . $errorMessages[$_GET["error"]] . "</div>";
        }
        ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <button type="submit" name="forget" class="btn-primary">
                Send Verification Code
            </button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>