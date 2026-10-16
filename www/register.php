<?php
require __DIR__ . '/inc/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$firstName) $errors[] = 'First name required';
    if (!$lastName) $errors[] = 'Last name required';
    if (!$email) $errors[] = 'Valid email required';
    if (strlen($password) < 6) $errors[] = 'Password must be 6+ chars';

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$firstName, $lastName, $email, $hash]);
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
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/app.css">
  <title>Register</title>
</head>
<body>
<main class="site-main">
  <section class="auth-panel">
    <div class="page-intro"><h1>Create account</h1><p>Join the student resource portal.</p></div>
    <?php foreach ($errors as $err): ?>
      <div class="message message-error" role="alert"><?=htmlspecialchars($err)?></div>
    <?php endforeach; ?>
    <form method="post" class="form-stack">
      <div class="form-field"><label for="first_name">First name</label><input id="first_name" name="first_name" value="<?=htmlspecialchars($_POST['first_name'] ?? '')?>" autocomplete="given-name" required></div>
      <div class="form-field"><label for="last_name">Last name</label><input id="last_name" name="last_name" value="<?=htmlspecialchars($_POST['last_name'] ?? '')?>" autocomplete="family-name" required></div>
      <div class="form-field"><label for="email">Email</label><input id="email" name="email" type="email" value="<?=htmlspecialchars($_POST['email'] ?? '')?>" autocomplete="email" required></div>
      <div class="form-field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="new-password" minlength="6" required></div>
      <button type="submit">Register</button>
    </form>
    <p><a href="/login.php">Already have an account?</a></p>
  </section>
</main>
</body>
</html>
