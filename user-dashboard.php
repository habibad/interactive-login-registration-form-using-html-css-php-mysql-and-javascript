<?php
session_start();
include "config.php"; // Make sure this contains $conn for DB connection

if (isset($_POST["confirm"])) {
    $userName = $_POST["userName"];
    $confirmedPassword = $_POST["confirmPassword"];

    // Sanitize and debug output
    echo "Confirming name: " . htmlspecialchars($userName) . "<br>";
    echo "Confirming session data name: " . htmlspecialchars($_SESSION["fName"]) . "<br>";

    // Check if inputs are empty
    if (empty($userName) || empty($confirmedPassword)) {
        header("Location: /login-form/login.php?error=emptyfields");
        exit();
    }

    // Prepare and execute secure SQL statement
    $sql = "SELECT id FROM users WHERE first_name = ? AND password = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters (s = string, string)
        $stmt->bind_param("ss", $userName, $confirmedPassword);
        $stmt->execute();
        $stmt->store_result();

        // Check if a matching user is found
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($userId);
            $stmt->fetch();
            // Success
            echo "User confirmed. You may proceed.";
            echo $userId . " user(s) found.<br>";
            $userInfo = "SELECT id, first_name, last_name, email, phone, dob, password FROM users WHERE id = ?";
            $userdata = $conn->prepare($userInfo);
            if ($userdata) {
                $userdata->bind_param("i", $userId); // 'i' because id is integer
                $userdata->execute();
                $userdata->store_result();
                $userdata->bind_result($id, $firstName, $lastName, $email, $phone, $dob, $password);
                while ($userdata->fetch()) {
                    echo "User ID: $id, Name: $firstName $lastName, Email: $email, Phone: $phone, DOB: $dob";
                }
            }

            
            // You can also redirect or store data in session
        } else {
            // No match found
            header("Location: /login-form/login.php?error=invalidname");
            exit();
        }

        $stmt->close();
    } else {
        header("Location: /login-form/login.php?error=sqlerror");
        exit();
    }
}
?>