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

    // extension -> real MIME types we trust for it (checked against file content, not the browser-supplied type)
    $allowedTypes = [
        'pdf'  => ['application/pdf'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'zip'  => ['application/zip', 'application/x-zip-compressed'],
        'txt'  => ['text/plain'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
    ];

    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'File required';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload error';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!isset($allowedTypes[$ext])) {
            $errors[] = 'File type not allowed';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $errors[] = 'Max 10MB';
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (!in_array($realType, $allowedTypes[$ext], true)) {
                $errors[] = 'File content does not match its extension';
            }
        }

        if (!$errors) {
            $safe = bin2hex(random_bytes(16)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads';
            $dest = $uploadDir . '/' . $safe;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                $errors[] = 'Move upload failed';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO resources (user_id, category_id, title, description, filename, original_filename, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $category_id, $title, $desc, $safe, $file['name'], $file['type'], $file['size']]);
                    header('Location: list.php?uploaded=1');
                    exit;
                } catch (PDOException $e) {
                    // Don't leave an orphaned file on disk if the DB record failed
                    @unlink($dest);
                    error_log('Resource insert failed: ' . $e->getMessage());
                    $errors[] = 'Could not save resource. Please try again.';
                }
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
        <option value="<?= htmlspecialchars((string)$category['id']) ?>" <?= (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : '' ?>>
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
