<?php
require __DIR__ . '/inc/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$name) $errors[] = 'Name required';
    if (!$email) $errors[] = 'Valid email required';
    if (strlen($password) < 6) $errors[] = 'Password must be 6+ chars';

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$name, $email, $hash]);
            header('Location: /login.php?registered=1');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Email already registered';
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Register</title></head>
<body>
<h2>Register</h2>
<?php foreach ($errors as $err): ?>
  <div style="color:red"><?=htmlspecialchars($err)?></div>
<?php endforeach; ?>
<form method="post">
  <label>Name <input name="name" value="<?=htmlspecialchars($_POST['name'] ?? '')?>"></label><br>
  <label>Email <input name="email" value="<?=htmlspecialchars($_POST['email'] ?? '')?>"></label><br>
  <label>Password <input type="password" name="password"></label><br>
  <button>Register</button>
</form>
<p><a href="/login.php">Login</a></p>
</body>
</html>
