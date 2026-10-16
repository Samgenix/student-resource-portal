<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_login();

$search = trim($_GET['q'] ?? '');
$sql = "SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) AS uploader, c.name AS category
        FROM resources r
        JOIN users u ON u.id = r.user_id
        LEFT JOIN categories c ON c.id = r.category_id";
$params = [];

if ($search !== '') {
    $sql .= " WHERE r.title LIKE :title
              OR r.description LIKE :description
              OR r.original_filename LIKE :filename
              OR CONCAT(u.first_name, ' ', u.last_name) LIKE :uploader
              OR c.name LIKE :category";
    $searchTerm = '%' . $search . '%';
    $params = [
        ':title' => $searchTerm,
        ':description' => $searchTerm,
        ':filename' => $searchTerm,
        ':uploader' => $searchTerm,
        ':category' => $searchTerm,
    ];
}

$sql .= " ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/app.css">
  <title>Resources</title>
</head>
<body>
<header class="site-header">
  <h1>Resources</h1>
  <nav class="site-nav" aria-label="Primary navigation">
    <a href="/resources/upload.php">Upload</a>
    <a href="/resources/manage.php">Manage uploads</a>
    <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
      <a href="/admin.php">Admin</a>
    <?php endif; ?>
    <a href="/">Home</a>
    <a href="/logout.php">Logout</a>
  </nav>
</header>
<main class="site-main">
<div class="page-intro"><p>Welcome <?=htmlspecialchars($_SESSION['user_name'] ?? 'User')?>. Browse shared study resources.</p></div>
<?php if (!empty($_GET['uploaded'])): ?><div class="message message-success" role="status">Uploaded!</div><?php endif; ?>

<form method="get" role="search" class="search-bar">
  <div class="form-field"><label for="resource-search">Search resources</label><input id="resource-search" type="search" name="q" value="<?=htmlspecialchars($search)?>" placeholder="Title, description, file, category, or uploader"></div>
  <div class="search-actions"><button type="submit">Search</button>
  <?php if ($search !== ''): ?><a href="/resources/list.php">Clear</a><?php endif; ?>
  </div>
</form>

<?php if ($search !== '' && !$resources): ?>
  <p>No resources matched &quot;<?=htmlspecialchars($search)?>&quot;.</p>
<?php endif; ?>

<div class="resource-table-wrap"><table class="resource-table">
<thead><tr><th>Title</th><th>Category</th><th>Uploader</th><th>File</th><th>Date</th></tr></thead>
<tbody>
<?php foreach ($resources as $r): ?>
  <tr>
    <td><?=htmlspecialchars($r['title'])?></td>
    <td><?= htmlspecialchars($r['category'] ?? 'Uncategorized') ?></td>
    <td><?=htmlspecialchars($r['uploader'])?></td>
    <td>
      <?php if ($r['filename']): ?>
        <a href="/uploads/<?=rawurlencode($r['filename'])?>" download="<?=htmlspecialchars($r['original_filename'])?>">Download</a>
      <?php else: ?>—<?php endif; ?>
    </td>
    <td><?=htmlspecialchars($r['created_at'])?></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table></div>
</main>
</body>
</html>
