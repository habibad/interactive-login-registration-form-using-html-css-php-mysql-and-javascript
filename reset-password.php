<?php
// reset-password.php - Enhanced Reset with Auto-Detection
session_start();

if (isset($_POST["verify_code"])) {
    $enteredCode = $_POST["verification_code"];
    $sessionCode = $_SESSION['verification_code'] ?? '';
    $codeTimestamp = $_SESSION['code_timestamp'] ?? 0;
    
    // Check if code is expired (15 minutes)
    if (time() - $codeTimestamp > 900) {
        unset($_SESSION['verification_code'], $_SESSION['code_timestamp']);
        header("Location: reset-password.php?error=expired");
        exit();
    }
    
    if ($enteredCode === $sessionCode) {
        $_SESSION['code_verified'] = true;
        header("Location: new-password.php");
        exit();
    } else {
        header("Location: reset-password.php?error=invalid");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Verification Code - Admin Dashboard</title>
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
            max-width: 500px;
            text-align: center;
        }
        h2 {
            color: white;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        .subtitle {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 30px;
        }
        .verification-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
        }
        .verification-input {
            width: 60px;
            height: 60px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            transition: all 0.3s ease;
        }
        .verification-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 20px rgba(79, 70, 229, 0.3);
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
        .error-message {
            color: #ff6b6b;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(255, 107, 107, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(255, 107, 107, 0.2);
        }
        .success-message {
            color: #10b981;
            margin-bottom: 20px;
            padding: 12px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        .resend-link {
            margin-top: 20px;
        }
        .resend-link button {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            text-decoration: underline;
            font-size: 14px;
        }
        .resend-link button:hover {
            color: white;
        }
        .auto-detect-info {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
            color: #10b981;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Enter Verification Code</h2>
        <p class="subtitle">We sent a 6-digit code to <?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : 'your email'; ?></p>
        
        <div class="auto-detect-info">
            <i class="fas fa-mobile-alt"></i> Auto-detection enabled for mobile devices
        </div>
        
        <?php
        $errorMessages = [
            'invalid' => 'Invalid verification code. Please try again.',
            'expired' => 'Verification code has expired. Please request a new one.',
            'missing' => 'Please enter the complete verification code.'
        ];
        
        if (isset($_GET["error"]) && isset($errorMessages[$_GET["error"]])) {
            echo "<div class='error-message'>" . $errorMessages[$_GET["error"]] . "</div>";
        }
        
        if (isset($_GET["success"])) {
            echo "<div class='success-message'>New verification code sent successfully!</div>";
        }
        ?>
        
        <form method="post" action="" id="verificationForm">
            <div class="verification-container">
                <input type="text" class="verification-input" maxlength="1" name="code1" autocomplete="one-time-code">
                <input type="text" class="verification-input" maxlength="1" name="code2">
                <input type="text" class="verification-input" maxlength="1" name="code3">
                <input type="text" class="verification-input" maxlength="1" name="code4">
                <input type="text" class="verification-input" maxlength="1" name="code5">
                <input type="text" class="verification-input" maxlength="1" name="code6">
            </div>
            
            <input type="hidden" name="verification_code" id="fullCode">
            <button type="submit" name="verify_code" class="btn-primary" id="verifyBtn">
                Verify Code
            </button>
        </form>
        
        <div class="resend-link">
            <button onclick="resendCode()">Didn't receive the code? Resend</button>
        </div>
    </div>

    <script>
        // Auto-focus and navigation between inputs
        const inputs = document.querySelectorAll('.verification-input');
        const form = document.getElementById('verificationForm');
        const fullCodeInput = document.getElementById('fullCode');
        
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value;
                
                if (value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                
                // Update full code
                updateFullCode();
                
                // Auto-submit when all fields are filled
                if (index === inputs.length - 1 && value) {
                    setTimeout(() => {
                        document.getElementById('verifyBtn').click();
                    }, 500);
                }
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
            
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const paste = e.clipboardData.getData('text');
                const digits = paste.replace(/\D/g, '').slice(0, 6);
                
                digits.split('').forEach((digit, i) => {
                    if (inputs[i]) {
                        inputs[i].value = digit;
                    }
                });
                
                updateFullCode();
                
                if (digits.length === 6) {
                    setTimeout(() => {
                        document.getElementById('verifyBtn').click();
                    }, 500);
                }
            });
        });
        
        function updateFullCode() {
            const code = Array.from(inputs).map(input => input.value).join('');
            fullCodeInput.value = code;
        }
        
        // Web OTP API for automatic SMS detection
        if ('OTPCredential' in window) {
            navigator.credentials.get({
                otp: { transport: ['sms'] }
            }).then(otp => {
                const code = otp.code;
                if (code && code.length === 6) {
                    code.split('').forEach((digit, i) => {
                        if (inputs[i]) {
                            inputs[i].value = digit;
                        }
                    });
                    updateFullCode();
                    setTimeout(() => {
                        document.getElementById('verifyBtn').click();
                    }, 500);
                }
            }).catch(err => {
                console.log('OTP detection not available:', err);
            });
        }
        
        function resendCode() {
            const email = '<?php echo isset($_GET['email']) ? urlencode($_GET['email']) : ''; ?>';
            window.location.href = 'forget.php?resend=1&email=' + email;
        }
        
        // Auto-focus first input on page load
        window.addEventListener('load', () => {
            inputs[0].focus();
        });
    </script>
</body>
</html>