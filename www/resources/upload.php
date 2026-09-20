<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/auth.php';
require_login();

$categoryStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $categoryStmt->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);

    if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
        // When a request exceeds post_max_size, PHP drops the whole body before
        // populating $_POST/$_FILES — there's no error code to inspect, so this
        // is the only way to detect it and show something better than "Title required".
        $errors[] = 'That file is too large to upload (max 10MB).';
    } else {
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
        } elseif (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            // Rejected by PHP itself (upload_max_filesize / MAX_FILE_SIZE) before our own check ever runs.
            $errors[] = 'That file is too large to upload (max 10MB).';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Upload error';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!isset($allowedTypes[$ext])) {
                $errors[] = 'File type not allowed';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $errors[] = 'That file is too large to upload (max 10MB).';
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
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Upload</title></head>
<body>
<h2>Upload Resource</h2>
<?php foreach ($errors as $e) echo "<div style='color:red'>".htmlspecialchars($e)."</div>"; ?>
<form id="upload-form" method="post" enctype="multipart/form-data">
  <input type="hidden" name="MAX_FILE_SIZE" value="10485760">
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
    <div id="client-file-error" role="alert" style="color:red" hidden></div>
  <button>Upload</button>
</form>
<p><a href="/resources/list.php">Back to list</a></p>
<script>
const uploadForm = document.getElementById('upload-form');
const fileInput = uploadForm.querySelector('input[name="file"]');
const fileError = document.getElementById('client-file-error');
const maxFileSize = 10 * 1024 * 1024;
const allowedExtensions = new Set(['pdf', 'docx', 'pptx', 'zip', 'txt', 'png', 'jpg', 'jpeg']);

function validateSelectedFile() {
    const file = fileInput.files[0];

    fileError.hidden = true;
    fileError.textContent = '';

    if (!file) {
        return true;
    }

    const fileNameParts = file.name.toLowerCase().split('.');
    const extension = fileNameParts.length > 1 ? fileNameParts.pop() : '';

    if (!allowedExtensions.has(extension)) {
        fileError.textContent = 'File type not allowed.';
    } else if (file.size > maxFileSize) {
        fileError.textContent = 'That file is too large to upload (max 10MB).';
    }

    if (fileError.textContent) {
        fileError.hidden = false;
        return false;
    }

    return true;
}

fileInput.addEventListener('change', validateSelectedFile);
uploadForm.addEventListener('submit', (event) => {
    if (!validateSelectedFile()) {
        event.preventDefault();
    }
});
</script>
</body>
</html>
