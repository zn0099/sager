<?php
session_start();
include_once("db.php");

function registerUser($conn) {
    $name_u      = trim($_POST['name_u']);
    $password    = trim($_POST['password']);
    $email       = trim($_POST['email']);
    $name        = trim($_POST['name']);
    $nationality = trim($_POST['nationality']);
    $gender      = isset($_POST['gender']) ? $_POST['gender'] : "";
    $address     = trim($_POST['address']);
    $priv        = 'U';

    $errors = [];

    if($name_u === ""){
        $errors[] = "Username is required.";
    }

 
    if($password === ""){
        $errors[] = "Password is required.";
    } else {
  
        if( !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password) ){
            $errors[] = "Password must contain letters and numbers.";
        }
    }

    if($email === ""){
        $errors[] = "Email is required.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors[] = "Email format is invalid.";
    }

    if($name === ""){
        $errors[] = "Full name is required.";
    }

    if($nationality === ""){
        $errors[] = "Nationality is required.";
    }

    if($address === ""){
        $errors[] = "Address is required.";
    }

    if($gender === ""){
        $errors[] = "Gender is required.";
    }

    if(!empty($errors)){
        foreach($errors as $e){
            echo "<p style='color:red;text-align:center;'>$e</p>";
        }
        return;
    }

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if($check->rowCount() > 0){
        echo "<p style='color:red;text-align:center;'>EMAIL ALREADY REGISTERED</p>";
    } else {
        $sql = "INSERT INTO users (name_u, password, priv, name, nationality, gender, address, email)
                VALUES ('$name_u', '$password', '$priv', '$name', '$nationality', '$gender', '$address', '$email')";
        $conn->exec($sql);
        echo "<p style='color:green;text-align:center;'>USER REGISTERED SUCCESSFULLY</p>";
    }
}

if(isset($_POST['register'])){
    registerUser($conn);
}
?>

<html>
<head>
    <meta charset="utf-8">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="form-box">
    <form method="post" name="regForm" onsubmit="return validateRegister();">
        <h3>REGISTER USER</h3>

        <p>Username</p>
        <input type="text" name="name_u" required>

        <p>Password</p>
        <input type="password" name="password" required>

        <p>Email</p>
        <input type="email" name="email" required>

        <p>Full Name</p>
        <input type="text" name="name" required>

        <p>Nationality</p>
        <input type="text" name="nationality" required>

        <p>Gender</p>
        <label>
            <input type="radio" name="gender" value="Male" required> Male
        </label>
        <label>
            <input type="radio" name="gender" value="Female"> Female
        </label>

        <p>Address</p>
        <input type="text" name="address" required>

        <input type="submit" name="register" value="REGISTER">
    </form>
</div>

<script type="text/javascript">
// فاليديشن بسيط بالـ JavaScript لنفس الشروط الأساسية
function validateRegister() {
    var form   = document.regForm;
    var uname  = form.name_u.value.trim();
    var pass   = form.password.value.trim();
    var email  = form.email.value.trim();
    var fname  = form.name.value.trim();
    var nat    = form.nationality.value.trim();
    var addr   = form.address.value.trim();
    var genderInputs = form.gender;
    var genderChecked = false;
    for (var i = 0; i < genderInputs.length; i++) {
        if (genderInputs[i].checked) {
            genderChecked = true;
            break;
        }
    }

    if (uname === "") {
        alert("Username is required");
        form.name_u.focus();
        return false;
    }
    if (pass === "") {
        alert("Password is required");
        form.password.focus();
        return false;
    }
    if (email === "") {
        alert("Email is required");
        form.email.focus();
        return false;
    }
    if (fname === "") {
        alert("Full name is required");
        form.name.focus();
        return false;
    }
    if (nat === "") {
        alert("Nationality is required");
        form.nationality.focus();
        return false;
    }
    if (!genderChecked) {
        alert("Gender is required");
        return false;
    }
    if (addr === "") {
        alert("Address is required");
        form.address.focus();
        return false;
    }

    return true;
}
</script>

</body>
</html>
