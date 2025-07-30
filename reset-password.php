<?php
session_start();
$sessionUserId = $_SESSION['sessionUserId'] ?? null;
echo "the user id is: " . $sessionUserId . "<br>";

if (isset($_POST["confirmRandomSubmit"])) {
    $randomNumber = (int)$_POST["randomnumberConfirm"];
    echo "Random number received: " . htmlspecialchars($randomNumber) . "<br>"; 
    $randomCchecker = $_SESSION["randomNumber"] ?? '';  
echo "Random number from session: " . htmlspecialchars($randomCchecker) . "<br>";
    if (empty($randomCchecker)) {
        echo "Session random number is not set. Please try again.";
        exit();
    }

    else if ($randomNumber === $randomCchecker) {
        echo "Random number confirmed successfully!";
        header("Location: /login-form/new-password.php");
        exit();
    } else {
        echo "Random number does not match. Please try again.";
        header("Location: /login-form/reset-password.php?error=invalidnumber");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Random Number</title>
</head>
<body>
    <?php
    if(isset($_GET["error"]) && $_GET["error"] === "invalidnumber") {
        echo "<p style='color: red;'>Invalid random number. Please try again.</p>";
    } 
    ?>
    <form action="" method="post"> 
        <label for="randomnumberConfirm">Enter the random number you received:</label><br>
        <input type="text" id="randomnumberConfirm" name="randomnumberConfirm" required><br><br>
        <input type="submit" name="confirmRandomSubmit" value="Confirm">
    </form>
</body>
</html>
