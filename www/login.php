<?php
require __DIR__ . '/inc/db.php';
session_start();

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password, first_name, last_name, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (($user['status'] ?? 'active') === 'suspended') {
            $error = 'This account has been suspended. Please contact the administrator.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            header('Location: /resources/list.php');
            exit;
        }
    } else {
        $error = 'Invalid credentials';
    }
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
    <title>Login</title>
</head>
<body>
<main class="site-main">
    <section class="auth-panel">
        <div class="page-intro"><h1>Login</h1><p>Access your resource library.</p></div>
        <?php if ($error): ?><div class="message message-error" role="alert"><?=htmlspecialchars($error)?></div><?php endif; ?>
        <?php if (!empty($_GET['registered'])): ?><div class="message message-success" role="status">Registration successful. Please log in.</div><?php endif; ?>
        <form method="post" class="form-stack">
            <div class="form-field"><label for="email">Email</label><input id="email" name="email" type="email" autocomplete="email" required></div>
            <div class="form-field"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required></div>
            <button type="submit">Login</button>
        </form>
        <p><a href="/register.php">Create an account</a></p>
    </section>
</main>
</body>
</html>
