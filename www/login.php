<?php
require __DIR__ . '/inc/db.php';
session_start();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: /resources/list.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title></head>
<body>
<h2>Login</h2>
<?php if ($error): ?><div style="color:red"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if (!empty($_GET['registered'])): ?><div style="color:green">Registration successful. Please log in.</div><?php endif; ?>
<form method="post">
  <label>Email <input name="email"></label><br>
  <label>Password <input type="password" name="password"></label><br>
  <button>Login</button>
</form>
<p><a href="/register.php">Register</a></p>
</body>
</html>
