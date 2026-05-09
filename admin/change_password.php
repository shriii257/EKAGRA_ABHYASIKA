<?php
ob_start();
require_once '../includes/db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password']  ?? '';
    $new      = $_POST['new_password']      ?? '';
    $confirm  = $_POST['confirm_password']  ?? '';

    if (!$current || !$new || !$confirm) {
        $err = 'All fields are required.';
    } elseif ($new !== $confirm) {
        $err = 'New password and confirm password do not match.';
    } elseif (strlen($new) < 6) {
        $err = 'New password must be at least 6 characters.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id=?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if ($admin && (password_verify($current, $admin['password']) || $current === $admin['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admins SET password=? WHERE id=?")->execute([$hashed, $_SESSION['admin_id']]);
            logActivity($pdo, 'Password Changed', $_SESSION['admin_user'], 'Admin changed their password');
            $msg = 'Password updated successfully.';
        } else {
            $err = 'Current password is incorrect.';
        }
    }
}

$page_title = 'Change Password';
$base = '../';
include '../includes/header.php';
?>
<div class="admin-wrapper">
<?php include '_sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">Change Password</span>
    </div>
  </div>
  <div class="page-content">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card-panel">
          <div class="card-panel-header">
            <span class="card-panel-title"><i class="fas fa-key me-2"></i>Change Admin Password</span>
          </div>
          <div class="card-panel-body">
            <?php if ($msg): ?>
              <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo e($msg); ?></div>
            <?php endif; ?>
            <?php if ($err): ?>
              <div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i><?php echo e($err); ?></div>
            <?php endif; ?>
            <form method="POST">
              <div class="mb-3">
                <label class="form-label">Current Password *</label>
                <input type="password" name="current_password" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">New Password *</label>
                <input type="password" name="new_password" class="form-control" required minlength="6">
              </div>
              <div class="mb-4">
                <label class="form-label">Confirm New Password *</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-2"></i>Update Password
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>