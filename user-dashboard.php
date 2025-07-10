<?php
session_start();
?>
<?php
if(isset($_POST["confirm"])){
    $confirmName = $_POST["confirmName"];
    echo "Confirming name: " . htmlspecialchars($confirmName) . "<br>";
    echo "Confirming session data name: " . htmlspecialchars($_SESSION["fName"]) . "<br>";
    if($confirmName === $_SESSION['fName']){
        echo "Wellcome to the dashboard, " . $confirmName . "!";
    }
    else{
        
        header("location: /login-form/login.php?error=invalidname");
    }
}
?>