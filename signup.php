<?php
session_start();
?>
<!DOCTYPE html>
<html>
<body>

<h2>HTML Forms</h2>

<?php
if(isset($_GET["error"]) && $_GET["error"] === "emptyfields"){
  echo "<p style = 'color:red;'>please fill in all fields.</p>";
}
?>
<form action="/login-form/login.php" method="post">
  <label for="fname">First name:</label><br>
  <input type="text" id="fname" name="fname" value=""><br>
  <label for="lname">Last name:</label><br>
  <input type="text" id="lname" name="lname" value=""><br><br>

  <label for="email">Email:</label><br>
  <input type="email" id="email" name="email" value=""><br><br>

  <label for="phone">Phone:</label><br>
  <input type="text" id="phone" name="phone" value=""><br><br>
  
  <label for="dob">Date of Birth:</label><br>
  <input type="date" id="dob" name="dob" value=""><br><br>

  <label for="password">Password:</label><br>
  <input type="password" id="password" name="password" value=""><br><br>
  <input type="submit" name= "signUp" value="Submit">
</form> 
<Script>
  var fname = "<?php echo isset($_SESSION["fName"]) ? $_SESSION['fName'] : ''; ?>";
  var lname = "<?php echo isset($_SESSION["lName"]) ? $_SESSION['lName'] : ''; ?>";
  var email = "<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>";
  var phone = "<?php echo isset($_SESSION['phone']) ? $_SESSION['phone'] : ''; ?>";
  var dob = "<?php echo isset($_SESSION['dob']) ? $_SESSION['dob'] : ''; ?>";
  var password1 = "<?php echo isset($_SESSION['password']) ? $_SESSION['password'] : ''; ?>";



  var inputValueFName = document.getElementById("fname");
  var inputValueLName = document.getElementById("lname");
  var inputValueEmail = document.getElementById("email");
  var inputValuePhone = document.getElementById("phone");
  var inputValuedob = document.getElementById("dob");
  var inputValuePassword = document.getElementById("password");

  inputValueFName.value = fname;
  inputValueLName.value  = lname;
  inputValueEmail.value  = email;
  inputValuePhone.value  = phone;
  inputValuedob.value  = dob;
  inputValuePassword.value  = password1;
</Script>

<p>If you click the "Submit" button, the form-data will be sent to a page called "/action_page.php".</p>

</body>
</html>

<?php
// Clear session values after displaying once
unset($_SESSION['fName']);
unset($_SESSION['lName']);
unset($_SESSION["email"]);
unset($_SESSION["phone"]);
unset($_SESSION["dob"]);
unset($_SESSION["password"]);
?>
