<?php
session_start();

$conn = new mysqli("localhost", "root", "", "db_query");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

/* LOGIN */
if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $_SESSION['admin'] = $username;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $message = "Invalid Username or Password!";
    }
}

/* CREATE ACCOUNT */
if(isset($_POST['create'])){
    $username = $_POST['new_username'];
    $password = $_POST['new_password'];

    $check = $conn->query("SELECT * FROM admin WHERE username='$username'");

    if($check->num_rows > 0){
        $message = "Username already exists!";
    }else{
        $conn->query("INSERT INTO admin(username,password) VALUES('$username','$password')");
        $message = "Account Created Successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Login</title>

<style>
*{
    box-sizing:border-box;
    transition:all .3s ease;
}

body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:#dcdcdc;
}

/* HEADER */
.top-header{
    background:#5F2521;
    text-align:center;
    padding:30px 10px;
    box-shadow:0 4px 15px rgba(0,0,0,0.15);
}
.bank-logo{
    width:110px;
}
.bank-name{
    font-weight:800;
    font-size:22px;
    color:white;
    margin-top:5px;
}

/* CENTER PAGE */
.page-center{
    display:flex;
    justify-content:center;
    align-items:center;
    height:calc(100vh - 150px);
}

/* CARD */
.card{
    background:#f2f2f2;
    width:420px;
    padding:45px 40px;
    border-radius:20px;
    box-shadow:0 20px 50px rgba(0,0,0,0.15);
    text-align:center;
    position:relative;
}

.card::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:6px;
    background:#926b00;
    border-top-left-radius:20px;
    border-top-right-radius:20px;
}

h2{
    margin-bottom:25px;
    letter-spacing:1px;
}

/* INPUT */
input{
    width:100%;
    padding:14px;
    margin:12px 0;
    border-radius:10px;
    border:1px solid #b0c4de;
    font-size:14px;
}

input:focus{
    outline:none;
    border:1px solid #926b00;
    box-shadow:0 0 5px rgba(146,107,0,0.3);
}

/* BUTTON */
.btn{
    width:100%;
    padding:14px;
    border:none;
    border-radius:40px;
    font-weight:bold;
    cursor:pointer;
    margin-top:12px;
    font-size:14px;
}

.login-btn{ 
    background:#926b00; 
    color:white;
}

.create-btn{ 
    background:#926b00; 
    color:white;
}

.back-btn{
    background:#6c757d;
    color:white;
    margin-top:18px;
}

.btn:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 20px rgba(0,0,0,0.2);
}

/* MESSAGE */
.error{
    margin-top:20px;
    color:#dc3545;
    font-weight:600;
}

.success{
    margin-top:20px;
    color:#28a745;
    font-weight:600;
}

@media(max-width:480px){
    .card{
        width:90%;
        padding:35px 25px;
    }
}
</style>

<script>
function showCreate(){
    document.getElementById("loginForm").style.display="none";
    document.getElementById("createForm").style.display="block";
}
function showLogin(){
    document.getElementById("loginForm").style.display="block";
    document.getElementById("createForm").style.display="none";
}
</script>
</head>

<body>

<div class="top-header">
    <img src="mcrbi-logo.png" class="bank-logo">
    <div class="bank-name">MOUNT CARMEL RURAL BANK INC.</div>
</div>

<div class="page-center">
<div class="card">

<h2>ADMIN LOGIN</h2>

<form method="POST" id="loginForm">
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>

<button type="submit" name="login" class="btn login-btn">Login</button>
<button type="button" onclick="showCreate()" class="btn create-btn">Create Account</button>
<button type="button" class="btn back-btn" onclick="window.location.href='index.php'">Back</button>
</form>

<form method="POST" id="createForm" style="display:none">
<input type="text" name="new_username" placeholder="New Username" required>
<input type="password" name="new_password" placeholder="New Password" required>

<button type="submit" name="create" class="btn create-btn">Create</button>
<button type="button" onclick="showLogin()" class="btn back-btn">Back</button>
</form>

<?php if($message!=""){ ?>
<div class="<?php echo (strpos($message,'Success')!==false)?'success':'error'; ?>">
<?php echo $message; ?>
</div>
<?php } ?>

</div>
</div>

</body>
</html>
