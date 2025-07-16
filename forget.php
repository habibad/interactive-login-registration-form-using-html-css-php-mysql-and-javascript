<?php
session_start();
include "config.php"; // Ensure this file contains the $conn variable for database connection

if(isset($_POST["forget"])){
    $email = $_POST["email"];
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters (s = string, string)
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows > 0) {
            // User exists
            $stmt->bind_result($userId);
            $stmt->fetch();
            if ($userId){
                $randomNumber = rand(1000000, 9999999);
                $_SESSION['randomNumber'] = $randomNumber;
                echo "random numbr: $randomNumber <br>";
                header("Location: /login-form/reset-password.php?email=$email&randomNumber=$randomNumber");
            }
            echo "Account found for email: $email. User ID: $userId";
            // Here you can implement further actions like sending a reset link or deleting the account
        } else {
            // No user found with that email
            header("Location: /login-form/login.php?error=nouser");
            exit();
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
    <form action="forget.php" method ="post">
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" value="" required><br><br>
        <input type="submit" name="forget" value="Forget Account">
    </form>
</body>
</html>