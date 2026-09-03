<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_login();

$categoryStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $categoryStmt->fetchAll();


$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $user_id = current_user_id();
   
    if (!$title) $errors[] = 'Title required';

    if (!$category_id) {
        $errors[] = 'Category required';
    }

    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'File required';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload error';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','docx','pptx','zip','txt','png','jpg','jpeg'];
        if (!in_array($ext, $allowed)) $errors[] = 'File type not allowed';
        if ($file['size'] > 10 * 1024 * 1024) $errors[] = 'Max 10MB';

        if (!$errors) {
            $safe = uniqid('', true) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/' . $safe;
            if (!is_dir(__DIR__.'/../uploads')) mkdir(__DIR__.'/../uploads', 0755, true);

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $stmt = $pdo->prepare("INSERT INTO resources (user_id, category_id, title, description, filename, original_filename, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $category_id, $title, $desc, $safe, $file['name'], $file['type'], $file['size']]);
                header('Location: list.php?uploaded=1');
                exit;
            } else {
                $errors[] = 'Move upload failed';
            }
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Upload</title></head>
<body>
<h2>Upload Resource</h2>
<?php foreach ($errors as $e) echo "<div style='color:red'>".htmlspecialchars($e)."</div>"; ?>
<form method="post" enctype="multipart/form-data">
  <label>Title <input name="title" value="<?=htmlspecialchars($_POST['title'] ?? '')?>"></label><br>
  <label>Description <textarea name="description"><?=htmlspecialchars($_POST['description'] ?? '')?></textarea></label><br>
  <label>Category
  <select name="category_id">
    <option value="">Select category</option>

    <?php foreach ($categories as $category): ?>
      <option
    value="<?= htmlspecialchars($category['id']) ?>"
    <?= (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : '' ?>
>
    <?= htmlspecialchars($category['name']) ?>
        </option>
    <?php endforeach; ?>

  </select>
</label>
<br>
  <label>File <input type="file" name="file"></label><br>
  <button>Upload</button>
</form>
<p><a href="/resources/list.php">Back to list</a></p>
</body>
</html>
