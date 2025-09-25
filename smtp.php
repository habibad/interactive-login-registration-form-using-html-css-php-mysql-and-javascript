<?php
// smtp.php - Enhanced SMTP Email System

class EnhancedMailer {
    /** @var \PHPMailer\PHPMailer\PHPMailer|null */
    private $mailer = null;

    public function __construct() {
        // Allow enabling SMTP debug output to error_log by defining SMTP_DEBUG = true in config.php
        if (!defined('SMTP_DEBUG')) {
            define('SMTP_DEBUG', false);
        }
        if (class_exists('\PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $this->mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
                // configure debug output to error_log so we can capture SMTP conversation
                $this->mailer->Debugoutput = function($str, $level) {
                    error_log('[PHPMailer debug] ' . trim($str));
                };
                $this->mailer->SMTPDebug = SMTP_DEBUG ? 2 : 0;

                $this->setupSMTP();
            } catch (\Exception $e) {
                error_log('PHPMailer initialization failed: ' . $e->getMessage());
                // keep $this->mailer as null to allow fallback behavior
                $this->mailer = null;
            }
        } else {
            error_log("PHPMailer not available: running in fallback mode. Run 'composer install' to enable real email sending.");
        }
    }

    private function setupSMTP() {
        if ($this->mailer === null) return;
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = SMTP_ENCRYPTION;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->CharSet = 'UTF-8';
        } catch (\Exception $e) {
            throw new \Exception('SMTP setup failed: ' . $e->getMessage());
        }
    }

    public function sendVerificationCode($email, $code, $type = 'password_reset') {
        if ($this->mailer === null) {
            $msg = sprintf("[FALLBACK MAILER] to=%s type=%s code=%s", $email, $type, $code);
            error_log($msg);
            return ['success' => false, 'error' => 'Mailer not configured on this system (fallback)'];
        }

        try {
            $this->mailer->clearAddresses();
            $this->mailer->setFrom(SMTP_USERNAME, 'Admin Dashboard');
            $this->mailer->addAddress($email);

            if ($type === 'password_reset') {
                $this->mailer->Subject = 'Password Reset Verification Code';
                $this->mailer->isHTML(true);
                $this->mailer->Body = $this->getPasswordResetTemplate($code);
            } else if ($type === 'email_verification') {
                $this->mailer->Subject = 'Email Verification Code';
                $this->mailer->isHTML(true);
                $this->mailer->Body = $this->getEmailVerificationTemplate($code);
            }

            $this->mailer->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
        } catch (\Exception $e) {
            // log detailed error info from PHPMailer if available
            $err = 'Email failed (exception): ' . $e->getMessage();
            if (isset($this->mailer) && property_exists($this->mailer, 'ErrorInfo')) {
                $err .= ' | PHPMailer ErrorInfo: ' . $this->mailer->ErrorInfo;
            }
            error_log($err);
            return ['success' => false, 'error' => $err];
        }
    }

    private function getPasswordResetTemplate($code) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .code { background: #4f46e5; color: white; padding: 15px 30px; font-size: 24px; font-weight: bold; text-align: center; border-radius: 8px; margin: 20px 0; letter-spacing: 3px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request</h1>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>You have requested to reset your password. Use the verification code below:</p>
                    <div class='code'>{$code}</div>
                    <p>This code will expire in 15 minutes for security reasons.</p>
                    <p>If you didn't request this password reset, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 Admin Dashboard. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }

    private function getEmailVerificationTemplate($code) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .code { background: #10b981; color: white; padding: 15px 30px; font-size: 24px; font-weight: bold; text-align: center; border-radius: 8px; margin: 20px 0; letter-spacing: 3px; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Email Verification</h1>
                </div>
                <div class='content'>
                    <p>Welcome!</p>
                    <p>Please verify your email address using the code below:</p>
                    <div class='code'>{$code}</div>
                    <p>This code will expire in 15 minutes.</p>
                    <p>Thank you for joining our platform!</p>
                </div>
                <div class='footer'>
                    <p>© 2024 Admin Dashboard. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}

?>