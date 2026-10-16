<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_login();

$userId = (int)current_user_id();
$maxFileSize = 10 * 1024 * 1024;
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
$errors = [];
$message = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $resourceId = (int)($_POST['resource_id'] ?? 0);
        $resourceStmt = $pdo->prepare('SELECT * FROM resources WHERE id = ? AND user_id = ?');
        $resourceStmt->execute([$resourceId, $userId]);
        $resource = $resourceStmt->fetch();

        if (!$resource) {
            $errors[] = 'Resource not found or you do not have permission to manage it.';
        } elseif ($action === 'update') {
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $categoryId = (int)($_POST['category_id'] ?? 0);

            if ($title === '') {
                $errors[] = 'Title required';
            }
            if (!$categoryId) {
                $errors[] = 'Category required';
            }

            if (!$errors) {
                $stmt = $pdo->prepare('UPDATE resources SET title = ?, description = ?, category_id = ? WHERE id = ? AND user_id = ?');
                $stmt->execute([$title, $description, $categoryId, $resourceId, $userId]);
                $message = 'Resource details updated.';
            }
        } elseif ($action === 'replace') {
            $file = $_FILES['file'] ?? null;
            $newFilePath = null;
            $newFilename = null;

            if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Choose a replacement file.';
            } elseif (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $errors[] = 'That file is too large to upload (max 10MB).';
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Upload error';
            } else {
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!isset($allowedTypes[$extension])) {
                    $errors[] = 'File type not allowed';
                } elseif ($file['size'] > $maxFileSize) {
                    $errors[] = 'That file is too large to upload (max 10MB).';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $realType = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    if (!in_array($realType, $allowedTypes[$extension], true)) {
                        $errors[] = 'File content does not match its extension';
                    }
                }

                if (!$errors) {
                    $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
                    $uploadDir = __DIR__ . '/../uploads';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $newFilePath = $uploadDir . '/' . $newFilename;

                    if (!move_uploaded_file($file['tmp_name'], $newFilePath)) {
                        $errors[] = 'Move upload failed';
                    } else {
                        try {
                            $stmt = $pdo->prepare('UPDATE resources SET filename = ?, original_filename = ?, file_type = ?, file_size = ? WHERE id = ? AND user_id = ?');
                            $stmt->execute([$newFilename, $file['name'], $file['type'], $file['size'], $resourceId, $userId]);
                            if (!empty($resource['filename'])) {
                                @unlink(__DIR__ . '/../uploads/' . basename($resource['filename']));
                            }
                            $message = 'Resource file replaced.';
                        } catch (PDOException $e) {
                            @unlink($newFilePath);
                            error_log('Resource replacement failed: ' . $e->getMessage());
                            $errors[] = 'Could not replace resource. Please try again.';
                        }
                    }
                }
            }
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM resources WHERE id = ? AND user_id = ?');
            $stmt->execute([$resourceId, $userId]);
            if (!empty($resource['filename'])) {
                @unlink(__DIR__ . '/../uploads/' . basename($resource['filename']));
            }
            $message = 'Resource deleted.';
        } else {
            $errors[] = 'Unknown resource action.';
        }
    }
}

$categoryStmt = $pdo->query('SELECT id, name FROM categories ORDER BY name');
$categories = $categoryStmt->fetchAll();
$resourceStmt = $pdo->prepare('SELECT r.*, c.name AS category FROM resources r LEFT JOIN categories c ON c.id = r.category_id WHERE r.user_id = ? ORDER BY r.created_at DESC');
$resourceStmt->execute([$userId]);
$resources = $resourceStmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/app.css">
  <title>Manage Uploads</title>
</head>
<body>
<header class="site-header"><h1>Manage My Uploads</h1><nav class="site-nav" aria-label="Primary navigation"><a href="/resources/list.php">Resources</a><a href="/resources/upload.php">Upload</a><a href="/">Home</a><a href="/logout.php">Logout</a></nav></header>
<main class="site-main">
<?php foreach ($errors as $error): ?>
  <div class="message message-error" role="alert"><?=htmlspecialchars($error)?></div>
<?php endforeach; ?>
<?php if ($message !== ''): ?>
  <div class="message message-success" role="status"><?=htmlspecialchars($message)?></div>
<?php endif; ?>

<?php if (!$resources): ?>
  <p>You have not uploaded any resources yet.</p>
<?php endif; ?>

<div class="resource-list">
<?php foreach ($resources as $resource): ?>
  <section class="resource-card">
    <h3><?=htmlspecialchars($resource['original_filename'] ?? $resource['title'])?></h3>
    <form method="post" class="form-stack">
      <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="resource_id" value="<?=htmlspecialchars((string)$resource['id'])?>">
      <div class="form-field"><label for="title-<?=htmlspecialchars((string)$resource['id'])?>">Title</label>
        <input id="title-<?=htmlspecialchars((string)$resource['id'])?>" name="title" value="<?=htmlspecialchars($resource['title'])?>" required>
      </div>
      <div class="form-field"><label for="description-<?=htmlspecialchars((string)$resource['id'])?>">Description</label>
        <textarea id="description-<?=htmlspecialchars((string)$resource['id'])?>" name="description"><?=htmlspecialchars($resource['description'] ?? '')?></textarea>
      </div>
      <div class="form-field"><label for="category-<?=htmlspecialchars((string)$resource['id'])?>">Category</label>
        <select id="category-<?=htmlspecialchars((string)$resource['id'])?>" name="category_id" required>
          <option value="">Select category</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?=htmlspecialchars((string)$category['id'])?>" <?=((int)$resource['category_id'] === (int)$category['id']) ? 'selected' : ''?>>
              <?=htmlspecialchars($category['name'])?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit">Save details</button>
    </form>

    <div class="resource-card__actions">
    <form method="post" enctype="multipart/form-data" class="form-stack">
      <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
      <input type="hidden" name="action" value="replace">
      <input type="hidden" name="resource_id" value="<?=htmlspecialchars((string)$resource['id'])?>">
      <div class="form-field"><label for="file-<?=htmlspecialchars((string)$resource['id'])?>">Replace file</label>
        <input id="file-<?=htmlspecialchars((string)$resource['id'])?>" type="file" name="file" required>
      </div>
      <button type="submit">Replace file</button>
    </form>

    <form method="post" onsubmit="return confirm('Delete this resource permanently?');">
      <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="resource_id" value="<?=htmlspecialchars((string)$resource['id'])?>">
      <button class="button-danger" type="submit">Delete resource</button>
    </form>
    </div>
  </section>
<?php endforeach; ?>
</div>
</main>
</body>
</html>
