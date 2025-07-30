<?php
session_start();
$sessionUserId = $_SESSION['sessionUserId'] ?? null;
echo "the user id is: " . $sessionUserId . "<br>";
include "config.php";
if(isset($_POST["change_password"])){
    $newPassword = $_POST["new_password"];
    $confirmNewPassword = $_POST["confirm_new_password"];
    if(empty($newPassword) || empty($confirmNewPassword)) {
        echo "Please fill in all fields.";
        exit();
    }
    if($newPassword !== $confirmNewPassword) {
        echo "Passwords do not match.";
        exit();
    }else{
        // Update the password in the database
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("si", $newPassword, $sessionUserId);
            if ($stmt->execute()) {
                echo "Password updated successfully!";
                // Optionally redirect to login or another page
                header("Location: /login-form/login.php?success=passwordchanged");
                exit();
            } else {
                echo "Error updating password: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing statement: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="new-password.php" method="post">
    <label for="new_password">new password</label>
    <input type="password" id="new_password" name="new_password" required><br>
    <label for="confirm_new_password">confirm new password</label>
    <input type="password" id="confirm_new_password" name="confirm_new_password" required><br>
    <input type="submit" value="change_password" name ="change_password">
    </form>
</body>
</html>

<?php
// unset($_SESSION['sessionUserId']);
?>