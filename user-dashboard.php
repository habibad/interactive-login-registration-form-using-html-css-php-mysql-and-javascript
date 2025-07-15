<?php
session_start();
?>
<?php
if(isset($_POST["confirm"])){
    $userName = $_POST["userName"];
    $confirmedPassword = $_POST["confirmPassword"];
    echo "Confirming name: " . htmlspecialchars($userName) . "<br>";
    echo "Confirming session data name: " . htmlspecialchars($_SESSION["fName"]) . "<br>";
    if($userName === $_SESSION['fName'] && $confirmedPassword === $_SESSION['password']){
        echo "Wellcome to the dashboard, " . $userName . "!";
    }
    else{
        
        header("location: /login-form/login.php?error=invalidname");
    }
}
?>