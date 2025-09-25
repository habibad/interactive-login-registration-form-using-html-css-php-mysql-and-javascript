<?php
// index.php - Modern Sign-In Page
session_start();
require_once 'config.php';


if (isset($_POST["signIn"])) {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];
    
    if (empty($email) || empty($password)) {
        header("Location: index.php?error=emptyfields");
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?error=invalidemail");
        exit();
    }
    
    try {
        if (!isset($conn) || !$conn) {
            error_log("Database connection not available in index.php");
            header("Location: index.php?error=system");
            exit();
        }

        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, status FROM users WHERE email = ?");
        if ($stmt === false) {
            error_log("MySQL prepare failed (SELECT user): " . $conn->error);
            header("Location: index.php?error=system");
            exit();
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            if ($user['status'] !== 'active') {
                header("Location: index.php?error=inactive");
                exit();
            }
            
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                // Update to hashed password if using plain text
                if ($password === $user['password']) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($updateStmt === false) {
                        error_log("MySQL prepare failed (UPDATE password): " . $conn->error);
                    } else {
                        $updateStmt->bind_param("si", $hashedPassword, $user['id']);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['login_type'] = 'regular';
                
                header("Location: admin-dashboard.html");
                exit();
            } else {
                header("Location: index.php?error=invalidlogin");
                exit();
            }
        } else {
            header("Location: index.php?error=invalidlogin");
            exit();
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: index.php?error=system");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Admin Dashboard</title>
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
            max-width: 450px;
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

        .forgot-link {
            text-align: center;
            margin-bottom: 24px;
        }

        .forgot-link a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }

        .forgot-link a:hover {
            color: white;
        }

        .signup-link {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .signup-link span {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
        }

        .signup-link a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
            margin-left: 4px;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
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

        @media (max-width: 768px) {
            .auth-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .auth-title {
                font-size: 1.75rem;
            }
        }

        /* Remember me checkbox */
        .remember-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .remember-checkbox {
            width: 18px;
            height: 18px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .remember-checkbox:checked {
            background: #4f46e5;
            border-color: #4f46e5;
        }

        .remember-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="logo">AD</div>
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your account to continue</p>
        </div>

        <?php
        $errorMessages = [
            'emptyfields' => 'Please fill in all fields.',
            'invalidemail' => 'Please enter a valid email address.',
            'invalidlogin' => 'Invalid email or password.',
            'inactive' => 'Your account is inactive. Please contact support.',
            'system' => 'System error occurred. Please try again later.'
        ];
        
    if (isset($_GET["error"]) && isset($errorMessages[$_GET["error"]])) {
            echo "<div class='error-message'>
                    <i class='fas fa-exclamation-triangle'></i>
                    " . $errorMessages[$_GET["error"]] . "
                  </div>";
        }
        
        if (isset($_GET["success"])) {
            $successMessages = [
                'passwordchanged' => 'Password changed successfully! Please sign in.',
                'registered' => 'Registration successful! Please sign in.'
            ];
            
            if (isset($successMessages[$_GET["success"]])) {
                echo "<div class='success-message'>
                        <i class='fas fa-check-circle'></i>
                        " . $successMessages[$_GET["success"]] . "
                      </div>";
            }
        }

        // Debug helper: show configured Google client ID when ?debug_google=1 is present
        if (isset($_GET['debug_google']) && $_GET['debug_google'] == '1') {
            echo "<div style='background:#fff;padding:12px;border-radius:8px;margin:12px 0;color:#111;'>";
            echo "<strong>DEBUG</strong>: GOOGLE_CLIENT_ID = " . htmlspecialchars(GOOGLE_CLIENT_ID);
            echo "<br>Open DevTools Console and run <code>location.origin</code> to see the origin to register in Google Cloud.";
            echo "</div>";
        }
        ?>

        <form method="post" action="" id="signinForm">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-input" 
                       placeholder="Enter your email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordIcon"></i>
                    </button>
                </div>
            </div>

            <div class="remember-container">
                <input type="checkbox" id="remember" name="remember" class="remember-checkbox">
                <label for="remember" class="remember-label">Remember me</label>
            </div>

            <button type="submit" name="signIn" class="btn-primary">
                Sign In
            </button>
        </form>

        <div class="forgot-link">
            <a href="forget.php">Forgot your password?</a>
        </div>

        <div class="divider">
            <span class="divider-text">or continue with</span>
        </div>

        <button class="google-signin-btn" id="googleSignInBtn" onclick="signInWithGoogle()">
            <svg class="google-icon" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Sign in with Google
        </button>

        <div class="signup-link">
            <span>Don't have an account?</span>
            <a href="signup.php">Sign up for free</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }

        // Form submission with loading state
        document.getElementById('signinForm').addEventListener('submit', function() {
            document.querySelector('.auth-container').classList.add('loading');
        });

        // Google Sign-In Integration
        function signInWithGoogle() {
            console.log('Google Sign-In clicked');
            console.log('Configured GOOGLE_CLIENT_ID: <?php echo htmlspecialchars(GOOGLE_CLIENT_ID); ?>');
            console.log('location.origin:', location.origin);
            google.accounts.id.initialize({
                client_id: '<?php echo GOOGLE_CLIENT_ID; ?>',
                callback: handleGoogleSignIn
            });
            
            google.accounts.id.prompt();
        }

        function handleGoogleSignIn(response) {
            // Send token to server
            fetch('google-signin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: response.credential,
                    user_data: parseJwt(response.credential),
                    debug: (new URLSearchParams(location.search).get('debug_google') === '1')
                })
            })
            .then(async response => {
                const text = await response.text();
                console.log('google-signin.php raw response:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('google-signin.php parsed JSON:', data);
                    if (data.success) {
                        window.location.href = 'admin-dashboard.html';
                    } else {
                        alert('Google sign-in failed: ' + (data.error || 'Unknown error'));
                    }
                } catch (err) {
                    console.error('Failed to parse JSON from google-signin.php:', err);
                    alert('Google sign-in failed. Server returned invalid response — check browser console for details.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Google sign-in failed. Please try again.');
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
            document.getElementById('email').focus();
        });

        // Enhanced form validation
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

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

        // Remember me functionality
        const rememberCheckbox = document.getElementById('remember');
        const emailField = document.getElementById('email');

        // Load remembered email
        window.addEventListener('load', function() {
            const rememberedEmail = localStorage.getItem('rememberedEmail');
            if (rememberedEmail) {
                emailField.value = rememberedEmail;
                rememberCheckbox.checked = true;
            }
        });

        // Save email when form is submitted
        document.getElementById('signinForm').addEventListener('submit', function() {
            if (rememberCheckbox.checked) {
                localStorage.setItem('rememberedEmail', emailField.value);
            } else {
                localStorage.removeItem('rememberedEmail');
            }
        });
    </script>
</body>
</html>