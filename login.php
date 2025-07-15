<?php
session_start();

if (isset($_POST["signUp"])) {
    $fName = $_POST["fname"];
    $lName = $_POST["lname"];
    $email = $_POST['email'];
    $phone = $_POST["phone"];
    $dob = $_POST["dob"];
    $userPassword = $_POST["password"];

    include "config.php";
    // echo "first name: $fName <br>";
    // echo "last name: $lName <br>";
    // echo "email: $email <br>";
    // echo "phone: $phone <br>";
    // echo "dob: $dob <br>";
    // echo "password: $password <br>";    

    if ($fName === "" || $lName === "" || $email === "" || $phone === "" || $dob === "" || $userPassword === "") {
        $_SESSION['fName'] = $fName;
        $_SESSION['lName'] = $lName;
        $_SESSION['email'] = $email;
        $_SESSION['phone'] = $phone;
        $_SESSION['dob'] = $dob;
        $_SESSION['password'] = $userPassword;

        header("Location: /login-form/signup.php?error=emptyfields");
        exit();
    } else {
        $_SESSION['fName'] = $fName;
        $_SESSION['lName'] = $lName;
        $_SESSION['password'] = $userPassword;
       
        $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, dob, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $fName, $lName, $email, $phone, $dob, $userPassword);

        if ($stmt->execute()) {
            echo "✅ Sign-up successful!";
        } else {
            echo "❌ Error: " . $stmt->error;
        }
        

        echo "Thank you for signing up, $fName $lName!";
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
    <?Php
    if(isset($_GET["error"]) && $_GET["error"] === "invalidname"){
        echo "<p style='color: red;'>Invalid name or password. Please try again.</p>";
    }
    ?>
    <form action="user-dashboard.php" method="post">
        <label for="userName">user Name</label>
        <input type="text" name="userName" id="userName" value="" required><br><br>
        <label for="confirmPassword">Password</label>
        <input type="password" name="confirmPassword" id="confirmPassword" value="" required>

        <input type="submit" name="confirm" value="Confirm">

    </form>
</body>

</html>