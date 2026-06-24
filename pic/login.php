<?php 
session_start();
include_once("db.php"); 

function login($email, $pass, $conn) {
    $sql = "SELECT * FROM users WHERE email='$email' AND password='$pass'";
    $result = $conn->query($sql);

    if($result->rowCount() > 0) {
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if($row['priv'] == 'R'){ 
            $_SESSION['editor_id'] = $row['no_U'];
            $_SESSION['editor_name'] = $row['name_u'];
            header("Location: mdashbord.php");
            exit;
        } else { 
            $_SESSION['user_id'] = $row['no_U'];
            $_SESSION['user_name'] = $row['name_u'];
            header("Location: udashbord.php");
            exit;
        }
    } else {
        return "<p style='color:red;text-align:center;'>WRONG EMAIL OR PASSWORD</p>";
    }
}

$error_message = "";

if(isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $pass  = trim($_POST['password']);

    if($email === ""){
        $error_message = "<p style='color:red;text-align:center;'>Email is required.</p>";
    } elseif($pass === ""){
        $error_message = "<p style='color:red;text-align:center;'>Password is required.</p>";
    } else {
        $error_message = login($email, $pass, $conn); 
    }
}
?>

<html>
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
<div class="form-box">
    <form method="post" name="loginForm" onsubmit="return validateLogin();">
        <h3>LOGIN</h3>
        <p>Email</p>
        <input type="text" name="email">

        <p>Password</p>
        <input type="password" name="password">

        <input type="submit" name="login" value="ENTER">
    </form>
    <a href="register.php">Create New Account</a>
    <?php if(!empty($error_message)) echo $error_message; ?>
</div>

<script type="text/javascript">
function validateLogin() {
    var form  = document.loginForm;
    var email = form.email.value.trim();
    var pass  = form.password.value.trim();

    if (email === "") {
        alert("Email is required");
        form.email.focus();
        return false;
    }
    if (pass === "") {
        alert("Password is required");
        form.password.focus();
        return false;
    }
    return true;
}
</script>

</body>
</html>
