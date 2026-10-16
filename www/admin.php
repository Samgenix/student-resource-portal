<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/auth.php';
require_login();
require_admin();

$action = $_POST['action'] ?? null;
$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $currentUserId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        $error = 'Invalid user.';
    } else {
        switch ($action) {
            case 'delete_user':
                $stmt = $pdo->prepare('DELETE FROM resources WHERE user_id = ?');
                $stmt->execute([$userId]);
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                $notice = 'User deleted.';
                break;

            case 'toggle_suspend':
                $stmt = $pdo->prepare('SELECT status FROM users WHERE id = ?');
                $stmt->execute([$userId]);
                $currentStatus = $stmt->fetchColumn();
                $newStatus = ($currentStatus === 'suspended') ? 'active' : 'suspended';
                $stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
                $stmt->execute([$newStatus, $userId]);
                $notice = 'User status updated to ' . $newStatus . '.';
                break;

            case 'make_admin':
                if ($userId === $currentUserId) {
                    $error = 'You cannot change your own admin role from here.';
                    break;
                }
                $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
                $stmt->execute(['admin', $userId]);
                $notice = 'User role updated to admin.';
                break;

            case 'remove_admin':
                if ($userId === $currentUserId) {
                    $error = 'You cannot remove your own admin role.';
                    break;
                }
                $adminCountStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
                $adminCountStmt->execute(['admin']);
                if ((int)$adminCountStmt->fetchColumn() <= 1) {
                    $error = 'At least one admin must remain in the system.';
                    break;
                }
                $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
                $stmt->execute(['user', $userId]);
                $notice = 'Admin role removed.';
                break;

            default:
                $error = 'Unsupported action.';
        }
    }
}

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$roleFilter = $_GET['role'] ?? 'all';

$userQuery = "SELECT id, first_name, last_name, email, role, status, created_at FROM users WHERE 1=1";
$userParams = [];

if ($search !== '') {
    $userQuery .= " AND (CONCAT(first_name, ' ', last_name) LIKE :search OR email LIKE :search OR role LIKE :search OR status LIKE :search)";
    $userParams[':search'] = '%' . $search . '%';
}

if ($statusFilter !== 'all') {
    $userQuery .= ' AND status = :status';
    $userParams[':status'] = $statusFilter;
}

if ($roleFilter !== 'all') {
    $userQuery .= ' AND role = :role';
    $userParams[':role'] = $roleFilter;
}

$userQuery .= ' ORDER BY created_at DESC';
$userStmt = $pdo->prepare($userQuery);
$userStmt->execute($userParams);
$users = $userStmt->fetchAll();

if (isset($_GET['export'])) {
    $exportStmt = $pdo->prepare($userQuery);
    $exportStmt->execute($userParams);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="user-report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Email', 'Role', 'Status', 'Joined']);
    foreach ($exportStmt->fetchAll() as $userRow) {
        $fullName = trim(($userRow['first_name'] ?? '') . ' ' . ($userRow['last_name'] ?? '')) ?: 'Unknown user';
        fputcsv($output, [$fullName, $userRow['email'], $userRow['role'], $userRow['status'] ?? 'active', $userRow['created_at']]);
    }
    fclose($output);
    exit;
}

$resourceCount = (int)$pdo->query('SELECT COUNT(*) FROM resources')->fetchColumn();
$userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$activeCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status != 'suspended'")->fetchColumn();
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/assets/css/app.css">
  <title>Admin Dashboard</title>
</head>
<body>
  <header class="site-header">
    <h1>Admin Dashboard</h1>
    <nav class="site-nav" aria-label="Primary navigation">
      <a href="/resources/list.php">Resources</a>
      <a href="/resources/upload.php">Upload</a>
      <a href="/resources/manage.php">Manage uploads</a>
      <a href="/">Home</a>
      <a href="/logout.php">Logout</a>
    </nav>
  </header>

  <main class="site-main">
    <section class="page-intro">
      <h2>Portal overview</h2>
    </section>

    <?php if ($notice !== ''): ?><div class="message message-success" role="status"><?=htmlspecialchars($notice)?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="message message-error" role="alert"><?=htmlspecialchars($error)?></div><?php endif; ?>

    <section class="panel" style="margin-bottom: 24px;">
      <div class="resource-list" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div class="resource-card">
          <h3>Total users</h3>
          <p><?=htmlspecialchars((string)$userCount)?></p>
        </div>
        <div class="resource-card">
          <h3>Active users</h3>
          <p><?=htmlspecialchars((string)$activeCount)?></p>
        </div>
        <div class="resource-card">
          <h3>Admins</h3>
          <p><?=htmlspecialchars((string)$adminCount)?></p>
        </div>
        <div class="resource-card">
          <h3>Resources</h3>
          <p><?=htmlspecialchars((string)$resourceCount)?></p>
        </div>
      </div>
    </section>

    <section class="panel">
      <h3>Manage users</h3>

      <form method="get" class="search-bar" style="margin-top: 16px;">
        <div class="form-field">
          <label for="admin-search">Search users</label>
          <input id="admin-search" type="search" name="q" value="<?=htmlspecialchars($search)?>" placeholder="Name, email, role, or status">
        </div>
        <div class="form-field" style="max-width: 180px;">
          <label for="status-filter">Status</label>
          <select id="status-filter" name="status">
            <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
          </select>
        </div>
        <div class="form-field" style="max-width: 180px;">
          <label for="role-filter">Role</label>
          <select id="role-filter" name="role">
            <option value="all" <?= $roleFilter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>User</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
          </select>
        </div>
        <div class="search-actions" style="align-self:end;">
          <button type="submit">Filter</button>
          <a href="/admin.php">Reset</a>
          <a href="/admin.php?export=1<?= $search !== '' ? '&q=' . urlencode($search) : '' ?><?= $statusFilter !== 'all' ? '&status=' . urlencode($statusFilter) : '' ?><?= $roleFilter !== 'all' ? '&role=' . urlencode($roleFilter) : '' ?>">Export CSV</a>
        </div>
      </form>

      <div class="resource-table-wrap">
        <table class="resource-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Joined</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?=htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?: 'Unknown user'?></td>
                <td><?=htmlspecialchars($user['email'])?></td>
                <td><?=htmlspecialchars($user['role'])?></td>
                <td>
                  <?php $userStatus = $user['status'] ?? 'active'; ?>
                  <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background: <?= $userStatus === 'suspended' ? '#d4a017' : '#2e9d54'; ?>; margin-right:8px; vertical-align:middle;"></span>
                  <?=htmlspecialchars($userStatus)?>
                </td>
                <td><?=htmlspecialchars($user['created_at'])?></td>
                <td>
                  <form method="post" style="display:inline-block; margin-right: 8px;">
                    <input type="hidden" name="action" value="toggle_suspend">
                    <input type="hidden" name="user_id" value="<?=htmlspecialchars((string)$user['id'])?>">
                    <button type="submit">
                      <?= (($user['status'] ?? 'active') === 'suspended') ? 'Activate' : 'Suspend' ?>
                    </button>
                  </form>
                  <?php if ((int)$user['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                    <form method="post" style="display:inline-block; margin-right: 8px;">
                      <input type="hidden" name="action" value="<?= $user['role'] === 'admin' ? 'remove_admin' : 'make_admin' ?>">
                      <input type="hidden" name="user_id" value="<?=htmlspecialchars((string)$user['id'])?>">
                      <button type="submit">
                        <?= $user['role'] === 'admin' ? 'Remove admin' : 'Make admin' ?>
                      </button>
                    </form>
                  <?php endif; ?>
                  <form method="post" onsubmit="return confirm('Delete this user and all their uploaded resources?');" style="display:inline-block;">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="<?=htmlspecialchars((string)$user['id'])?>">
                    <button type="submit" class="button-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</body>
</html>
