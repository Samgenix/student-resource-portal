<?php
// www/index.php
session_start();
$logged_in = !empty($_SESSION['user_id']);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Student Resource Portal</title>
</head>
<body>
  <h1>Student Resource Portal</h1>
  <?php if ($logged_in): ?>
    <p>Welcome <?=htmlspecialchars($_SESSION['user_name'] ?? 'User')?> — <a href="/resources/list.php">Resources</a> | <a href="/resources/upload.php">Upload</a> | <a href="/logout.php">Logout</a></p>
  <?php else: ?>
    <p><a href="/register.php">Register</a> or <a href="/login.php">Login</a></p>
  <?php endif; ?>
</body>
</html>
