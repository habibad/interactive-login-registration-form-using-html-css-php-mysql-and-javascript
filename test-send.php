<?php
// test-send.php - quick diagnostic for SMTP / PHPMailer
header('Content-Type: application/json');
// require_once 'config.php';
// require_once 'smtp.php';

$to = isset($_GET['to']) && filter_var($_GET['to'], FILTER_VALIDATE_EMAIL) ? $_GET['to'] : SMTP_USERNAME;
$code = sprintf('%06d', rand(0, 999999));

$mailer = new EnhancedMailer();
$result = $mailer->sendVerificationCode($to, $code, 'password_reset');

echo json_encode([
    'to' => $to,
    'used_smtp_username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : null,
    'result' => $result,
]);

?>
