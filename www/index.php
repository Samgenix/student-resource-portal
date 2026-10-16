<?php
// www/index.php
session_start();
$logged_in = !empty($_SESSION['user_id']);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/app.css">
  <title>Student Resource Portal</title>
</head>
<body>
  <header class="site-header">
    <h1>Student Resource Portal</h1>
    <?php if ($logged_in): ?>
      <nav class="site-nav" aria-label="Primary navigation">
        <a href="/resources/list.php">Resources</a>
        <a href="/resources/manage.php">Manage uploads</a>
        <a href="/logout.php">Logout</a>
      </nav>
    <?php endif; ?>
  </header>
  <main class="site-main">
  <?php if ($logged_in): ?>
    <section class="page-intro">
      <h2>Welcome <?=htmlspecialchars($_SESSION['user_name'] ?? 'User')?></h2>
      <p>Find, share, and manage your study resources.</p>
      <p>
        <a href="/resources/upload.php">Upload a resource</a>
        <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
          | <a href="/admin.php">Admin dashboard</a>
        <?php endif; ?>
      </p>
    </section>
  <?php else: ?>
    <section class="page-intro">
      <h2>Student resources, in one place</h2>
      <p><a href="/register.php">Register</a> or <a href="/login.php">Login</a></p>
    </section>
  <?php endif; ?>
  </main>
</body>
</html>
