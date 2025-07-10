<?php
session_start();
// echo "fire the login page";
if(isset($_POST["signUp"])){
    $fName = $_POST["fname"];
    $lName = $_POST["lname"];
    if($fName === "" || $lName === "") {
        echo "Please fill in all fields.";
        $_SESSION['fName'] = $fName;
        $_SESSION['lName'] = $lName;
        header("location:  /login-form/signup.php?error=emptyfields");
    } else {
        // Here you would typically handle the signup logic, like saving to a database
        $_SESSION['fName'] = $fName;
        $_SESSION['lName'] = $lName;
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
        echo "<p style='color: red;'>Invalid name. Please try again.</p>";
    }
    ?>
    <form action="user-dashboard.php" method="post">
        <label for="confirmName">Confirm Name</label>
        <input type = "text" name="confirmName" id = "confirmName" value="" required>
        <input type="submit" name = "confirm" value="Confirm">

    </form>
</body>
</html>