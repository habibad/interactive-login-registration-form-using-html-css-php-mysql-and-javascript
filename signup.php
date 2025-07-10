<?php
session_start();
?>
<!DOCTYPE html>
<html>
<body>

<h2>HTML Forms</h2>


<form action="/login-form//login.php" method="post">
  <label for="fname">First name:</label><br>
  <input type="text" id="fname" name="fname" value=""><br>
  <label for="lname">Last name:</label><br>
  <input type="text" id="lname" name="lname" value=""><br><br>
  <input type="submit" name= "signUp" value="Submit">
</form> 
<Script>
  var fname = "<?php echo isset($_SESSION["fName"]) ? $_SESSION['fName'] : ''; ?>";
  var lname = "<?php echo isset($_SESSION["lName"]) ? $_SESSION['lName'] : ''; ?>";
  var inputValue1 = document.getElementById("fname");
  var inputValue2 = document.getElementById("lname");
  inputValue1.value = fname;
  inputValue2.value  = lname;
</Script>

<p>If you click the "Submit" button, the form-data will be sent to a page called "/action_page.php".</p>

</body>
</html>

<?php
// Clear session values after displaying once
unset($_SESSION['fName']);
unset($_SESSION['lName']);
?>
