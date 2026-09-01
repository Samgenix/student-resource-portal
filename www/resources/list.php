<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_login();

$stmt = $pdo->query("SELECT r.*, u.name as uploader FROM resources r JOIN users u ON u.id = r.user_id ORDER BY r.created_at DESC");
$resources = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Resources</title></head>
<body>
<h2>Resources</h2>
<p>Welcome <?=htmlspecialchars($_SESSION['user_name'] ?? 'User')?> — 
  <a href="/resources/upload.php">Upload</a> | 
  <a href="/">Home</a> | 
  <a href="/logout.php">Logout</a>
</p>
<?php if (!empty($_GET['uploaded'])): ?><div style="color:green">Uploaded!</div><?php endif; ?>

<table border="1" cellpadding="6" cellspacing="0">
<thead><tr><th>Title</th><th>Uploader</th><th>File</th><th>Date</th></tr></thead>
<tbody>
<?php foreach ($resources as $r): ?>
  <tr>
    <td><?=htmlspecialchars($r['title'])?></td>
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
</table>
</body>
</html>
