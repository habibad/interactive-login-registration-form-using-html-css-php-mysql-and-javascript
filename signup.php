<?php
// signup.php - Modern Sign-Up Page
session_start();
require_once 'config.php';
require_once 'smtp.php';

if (isset($_POST["signUp"])) {
    $firstName = trim($_POST["first_name"]);
    $lastName = trim($_POST["last_name"]);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST["phone"]);
    $dob = $_POST["dob"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirm_password"];

    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        header("Location: signup.php?error=emptyfields");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: signup.php?error=invalidemail");
        exit();
    }

    if (strlen($password) < 8) {
        header("Location: signup.php?error=weakpassword");
        exit();
    }

    if ($password !== $confirmPassword) {
        header("Location: signup.php?error=passwordmismatch");
        exit();
    }

    try {
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($checkStmt === false) {
            // Log detailed DB error for debugging and show a friendly error to the user
            $dbError = "Prepare failed (check email): " . $conn->error;
            error_log($dbError);
            // Save into session for optional debug display
            $_SESSION['last_db_error'] = $dbError;
            header("Location: signup.php?error=database");
            exit();
        }
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            header("Location: signup.php?error=emailexists");
            exit();
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, dob, password, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
        if ($stmt === false) {
            $dbError = "Prepare failed (insert user): " . $conn->error;
            error_log($dbError);
            $_SESSION['last_db_error'] = $dbError;
            header("Location: signup.php?error=database");
            exit();
        }
        $stmt->bind_param("ssssss", $firstName, $lastName, $email, $phone, $dob, $hashedPassword);

        if ($stmt->execute()) {
            $userId = $conn->insert_id;
            
            // Send welcome email (optional)
            try {
                $mailer = new EnhancedMailer();
                // $mail = PHPMailer();

                $welcomeCode = sprintf('%06d', rand(100000, 999999));
                $mailer->sendVerificationCode($email, $welcomeCode, 'email_verification');
            } catch (Exception $e) {
                error_log("Welcome email failed: " . $e->getMessage());
            }

            header("Location: index.php?success=registered");
            exit();
        } else {
            // Execution failed — log detailed error for debugging
            $dbError = "Execute failed (insert user): " . $stmt->error . " | DB: " . $conn->error;
            error_log($dbError);
            $_SESSION['last_db_error'] = $dbError;
            header("Location: signup.php?error=database");
            exit();
        }
    } catch (Exception $e) {
        error_log("Signup error: " . $e->getMessage());
        header("Location: signup.php?error=system");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Admin Dashboard</title>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            transition: transform 0.3s ease;
        }

        .auth-container:hover {
            transform: translateY(-5px);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #4f46e5, #7c3aed);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
        }

        .auth-title {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .auth-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.875rem;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 20px rgba(79, 70, 229, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            font-size: 1.1rem;
        }

        .password-toggle:hover {
            color: white;
        }

        .password-strength {
            margin-top: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
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

        .password-match {
            margin-top: 8px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .password-match.match {
            color: #10b981;
        }

        .password-match.no-match {
            color: #ef4444;
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(45deg, #4f46e5, #7c3aed);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 16px;
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

        .divider {
            display: flex;
            align-items: center;
            margin: 24px 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
        }

        .divider-text {
            padding: 0 16px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.875rem;
        }

        .google-signin-btn {
            width: 100%;
            padding: 16px;
            background: white;
            color: #1f2937;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .google-signin-btn:hover {
            background: #f3f4f6;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .google-icon {
            width: 20px;
            height: 20px;
        }

        .signin-link {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .signin-link span {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
        }

        .signin-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
            margin-left: 4px;
            transition: color 0.3s ease;
        }

        .signin-link a:hover {
            color: #7c3aed;
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .success-message {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 24px;
        }

        .terms-checkbox input {
            width: 18px;
            height: 18px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
            margin-top: 2px;
        }

        .terms-checkbox input:checked {
            background: #4f46e5;
            border-color: #4f46e5;
        }

        .terms-text {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            line-height: 1.4;
        }

        .terms-text a {
            color: #4f46e5;
            text-decoration: none;
        }

        .terms-text a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .auth-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .auth-title {
                font-size: 1.75rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading .btn-primary::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-left: 8px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="logo">AD</div>
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Join us and start managing your dashboard</p>
        </div>

        <?php
        $errorMessages = [
            'emptyfields' => 'Please fill in all required fields.',
            'invalidemail' => 'Please enter a valid email address.',
            'emailexists' => 'An account with this email already exists.',
            'weakpassword' => 'Password must be at least 8 characters long.',
            'passwordmismatch' => 'Passwords do not match.',
            'database' => 'Database error occurred. Please try again.',
            'system' => 'System error occurred. Please try again later.'
        ];
        
        if (isset($_GET["error"]) && isset($errorMessages[$_GET["error"]])) {
            echo "<div class='error-message'>
                    <i class='fas fa-exclamation-triangle'></i>
                    " . $errorMessages[$_GET["error"]] . "
                  </div>";
        }
        ?>

        <form method="post" action="" id="signupForm">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-input" 
                           placeholder="John" required 
                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-input" 
                           placeholder="Doe" required 
                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" 
                       placeholder="john@example.com" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="phone">Phone (Optional)</label>
                    <input type="tel" id="phone" name="phone" class="form-input" 
                           placeholder="+1 (555) 000-0000" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="dob">Date of Birth (Optional)</label>
                    <input type="date" id="dob" name="dob" class="form-input" 
                           value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Create a strong password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password', 'passwordIcon1')">
                        <i class="fas fa-eye" id="passwordIcon1"></i>
                    </button>
                </div>
                <div id="passwordStrength" class="password-strength" style="display: none;"></div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           placeholder="Confirm your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'passwordIcon2')">
                        <i class="fas fa-eye" id="passwordIcon2"></i>
                    </button>
                </div>
                <div id="passwordMatch" class="password-match"></div>
            </div>

            <div class="terms-checkbox">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms" class="terms-text">
                    I agree to the <a href="#" target="_blank">Terms of Service</a> and 
                    <a href="#" target="_blank">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" name="signUp" class="btn-primary" id="signupBtn" disabled>
                Create Account
            </button>
        </form>

        <div class="divider">
            <span class="divider-text">or sign up with</span>
        </div>

        <button class="google-signin-btn" onclick="signUpWithGoogle()">
            <svg class="google-icon" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Sign up with Google
        </button>

        <div class="signin-link">
            <span>Already have an account?</span>
            <a href="index.php">Sign in here</a>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordStrength = document.getElementById('passwordStrength');
        const passwordMatch = document.getElementById('passwordMatch');
        const signupBtn = document.getElementById('signupBtn');
        const termsCheckbox = document.getElementById('terms');

        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const passwordIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }

        passwordInput.addEventListener('input', checkPasswordStrength);
        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        termsCheckbox.addEventListener('change', checkFormValidity);

        function checkPasswordStrength() {
            const password = passwordInput.value;
            let score = 0;
            let feedback = '';

            if (password.length === 0) {
                passwordStrength.style.display = 'none';
                checkFormValidity();
                return;
            }

            passwordStrength.style.display = 'block';

            // Length check
            if (password.length >= 8) score++;
            else feedback = 'At least 8 characters required';

            // Uppercase check
            if (/[A-Z]/.test(password)) score++;
            else if (!feedback) feedback = 'Add uppercase letters';

            // Lowercase check
            if (/[a-z]/.test(password)) score++;
            else if (!feedback) feedback = 'Add lowercase letters';

            // Number check
            if (/[0-9]/.test(password)) score++;
            else if (!feedback) feedback = 'Add numbers';

            // Special character check
            if (/[^A-Za-z0-9]/.test(password)) score++;
            else if (!feedback) feedback = 'Add special characters';

            // Set strength display
            if (score < 2) {
                passwordStrength.className = 'password-strength strength-weak';
                passwordStrength.textContent = 'Weak - ' + feedback;
            } else if (score < 4) {
                passwordStrength.className = 'password-strength strength-medium';
                passwordStrength.textContent = 'Medium - Consider adding more variety';
            } else {
                passwordStrength.className = 'password-strength strength-strong';
                passwordStrength.textContent = 'Strong password!';
            }

            checkFormValidity();
        }

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (confirmPassword.length === 0) {
                passwordMatch.textContent = '';
                passwordMatch.className = 'password-match';
                checkFormValidity();
                return;
            }

            if (password === confirmPassword) {
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.className = 'password-match match';
            } else {
                passwordMatch.textContent = '✗ Passwords do not match';
                passwordMatch.className = 'password-match no-match';
            }

            checkFormValidity();
        }

        function checkFormValidity() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const terms = termsCheckbox.checked;

            const isPasswordStrong = password.length >= 8 && 
                                   /[A-Z]/.test(password) && 
                                   /[a-z]/.test(password) && 
                                   /[0-9]/.test(password);
            
            const passwordsMatch = password === confirmPassword;
            const requiredFieldsFilled = firstName && lastName && email && password && confirmPassword;

            const isValid = requiredFieldsFilled && passwordsMatch && terms;
            signupBtn.disabled = !isValid;
        }

        // Form submission with loading state
        document.getElementById('signupForm').addEventListener('submit', function() {
            document.querySelector('.auth-container').classList.add('loading');
        });

        // Google Sign-Up Integration
        function signUpWithGoogle() {
            google.accounts.id.initialize({
                client_id: '<?php echo GOOGLE_CLIENT_ID; ?>',
                callback: handleGoogleSignUp
            });
            
            google.accounts.id.prompt();
        }

        function handleGoogleSignUp(response) {
            // Send token to server
            fetch('google-signin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: response.credential,
                    user_data: parseJwt(response.credential)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'admin-dashboard.html';
                } else {
                    alert('Google sign-up failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Google sign-up failed. Please try again.');
            });
        }

        function parseJwt(token) {
            const base64Url = token.split('.')[1];
            const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            return JSON.parse(jsonPayload);
        }

        // Auto-focus first input
        window.addEventListener('load', function() {
            document.getElementById('first_name').focus();
        });

        // Enhanced email validation
        const emailInput = document.getElementById('email');
        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();
            if (email && !isValidEmail(email)) {
                this.style.borderColor = '#ef4444';
            } else {
                this.style.borderColor = 'rgba(255, 255, 255, 0.2)';
            }
        });

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = `(${value}`;
                } else if (value.length <= 6) {
                    value = `(${value.slice(0, 3)}) ${value.slice(3)}`;
                } else {
                    value = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
                }
            }
            this.value = value;
        });

        // Real-time validation as user types
        ['first_name', 'last_name', 'email'].forEach(fieldId => {
            document.getElementById(fieldId).addEventListener('input', checkFormValidity);
        });
    </script>
</body>
</html>