<?php
// new-password.php - Enhanced New Password Setup
session_start();
require_once 'config.php';

// Check if code was verified
if (!isset($_SESSION['code_verified']) || !$_SESSION['code_verified']) {
    header("Location: forget.php");
    exit();
}

if (isset($_POST["change_password"])) {
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];
    $userId = $_SESSION['reset_user_id'];
    
    if (empty($newPassword) || empty($confirmPassword)) {
        header("Location: new-password.php?error=empty");
        exit();
    }
    
    if (strlen($newPassword) < 8) {
        header("Location: new-password.php?error=weak");
        exit();
    }
    
    if ($newPassword !== $confirmPassword) {
        header("Location: new-password.php?error=mismatch");
        exit();
    }
    
    try {
        // Hash the password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        
        if ($stmt->execute()) {
            // Clear session data
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['verification_code'], 
                  $_SESSION['code_timestamp'], $_SESSION['code_verified']);
            
            header("Location: index.php?success=passwordchanged");
            exit();
        } else {
            header("Location: new-password.php?error=database");
            exit();
        }
    } catch (Exception $e) {
        error_log("Password update error: " . $e->getMessage());
        header("Location: new-password.php?error=system");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - Admin Dashboard</title>
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
            max-width: 450px;
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
        input[type="password"] {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        input[type="password"]:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 20px rgba(79, 70, 229, 0.3);
        }
        input[type="password"]::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        .password-strength {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .strength-weak {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        .strength-medium {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        .strength-strong {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        .btn-primary {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.4);
        }
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .error-message {
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(255, 107, 107, 0.2);
        }
        .requirements {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        .requirements h4 {
            color: white;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .requirement {
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        .requirement.met {
            color: #10b981;
        }
        .requirement i {
            margin-right: 8px;
            width: 12px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Set New Password</h2>
        
        <?php
        $errorMessages = [
            'empty' => 'Please fill in all fields.',
            'weak' => 'Password must be at least 8 characters long.',
            'mismatch' => 'Passwords do not match.',
            'database' => 'Database error occurred. Please try again.',
            'system' => 'System error occurred. Please try again later.'
        ];
        
        if (isset($_GET["error"]) && isset($errorMessages[$_GET["error"]])) {
            echo "<div class='error-message'>" . $errorMessages[$_GET["error"]] . "</div>";
        }
        ?>
        
        <form method="post" action="" id="passwordForm">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                <div id="passwordStrength" class="password-strength" style="display: none;"></div>
                
                <div class="requirements">
                    <h4>Password Requirements:</h4>
                    <div class="requirement" id="req-length">
                        <i class="fas fa-times"></i>
                        At least 8 characters
                    </div>
                    <div class="requirement" id="req-uppercase">
                        <i class="fas fa-times"></i>
                        One uppercase letter
                    </div>
                    <div class="requirement" id="req-lowercase">
                        <i class="fas fa-times"></i>
                        One lowercase letter
                    </div>
                    <div class="requirement" id="req-number">
                        <i class="fas fa-times"></i>
                        One number
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                <div id="passwordMatch" style="margin-top: 8px; font-size: 14px;"></div>
            </div>
            
            <button type="submit" name="change_password" class="btn-primary" id="submitBtn" disabled>
                Update Password
            </button>
        </form>
    </div>

    <script>
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordStrength = document.getElementById('passwordStrength');
        const passwordMatch = document.getElementById('passwordMatch');
        const submitBtn = document.getElementById('submitBtn');
        
        const requirements = {
            length: document.getElementById('req-length'),
            uppercase: document.getElementById('req-uppercase'),
            lowercase: document.getElementById('req-lowercase'),
            number: document.getElementById('req-number')
        };
        
        newPasswordInput.addEventListener('input', checkPassword);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        
        function checkPassword() {
            const password = newPasswordInput.value;
            let score = 0;
            let metRequirements = 0;
            
            // Check length
            if (password.length >= 8) {
                requirements.length.classList.add('met');
                requirements.length.querySelector('i').className = 'fas fa-check';
                metRequirements++;
            } else {
                requirements.length.classList.remove('met');
                requirements.length.querySelector('i').className = 'fas fa-times';
            }
            
            // Check uppercase
            if (/[A-Z]/.test(password)) {
                requirements.uppercase.classList.add('met');
                requirements.uppercase.querySelector('i').className = 'fas fa-check';
                metRequirements++;
                score++;
            } else {
                requirements.uppercase.classList.remove('met');
                requirements.uppercase.querySelector('i').className = 'fas fa-times';
            }
            
            // Check lowercase
            if (/[a-z]/.test(password)) {
                requirements.lowercase.classList.add('met');
                requirements.lowercase.querySelector('i').className = 'fas fa-check';
                metRequirements++;
                score++;
            } else {
                requirements.lowercase.classList.remove('met');
                requirements.lowercase.querySelector('i').className = 'fas fa-times';
            }
            
            // Check number
            if (/[0-9]/.test(password)) {
                requirements.number.classList.add('met');
                requirements.number.querySelector('i').className = 'fas fa-check';
                metRequirements++;
                score++;
            } else {
                requirements.number.classList.remove('met');
                requirements.number.querySelector('i').className = 'fas fa-times';
            }
            
            // Show strength indicator
            if (password.length > 0) {
                passwordStrength.style.display = 'block';
                
                if (score === 0) {
                    passwordStrength.className = 'password-strength strength-weak';
                    passwordStrength.textContent = 'Weak password';
                } else if (score <= 2) {
                    passwordStrength.className = 'password-strength strength-medium';
                    passwordStrength.textContent = 'Medium strength password';
                } else {
                    passwordStrength.className = 'password-strength strength-strong';
                    passwordStrength.textContent = 'Strong password';
                }
            } else {
                passwordStrength.style.display = 'none';
            }
            
            checkFormValidity();
        }
        
        function checkPasswordMatch() {
            const password = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    passwordMatch.style.color = '#10b981';
                    passwordMatch.textContent = '✓ Passwords match';
                } else {
                    passwordMatch.style.color = '#ef4444';
                    passwordMatch.textContent = '✗ Passwords do not match';
                }
            } else {
                passwordMatch.textContent = '';
            }
            
            checkFormValidity();
        }
        
        function checkFormValidity() {
            const password = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            const isValid = password.length >= 8 && 
                           /[A-Z]/.test(password) && 
                           /[a-z]/.test(password) && 
                           /[0-9]/.test(password) && 
                           password === confirmPassword;
            
            submitBtn.disabled = !isValid;
        }
    </script>
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</body>
</html>
