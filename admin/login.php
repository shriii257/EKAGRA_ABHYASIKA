<?php
// admin/login.php
require_once '../includes/db.php';

if (isset($_SESSION['admin_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = 'Please enter username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_user'] = $admin['username'];
            logActivity($pdo, 'Admin Login', $admin['username'], 'Login successful');
            redirect('dashboard.php');
        } else {
            $error = 'Invalid username or password.';
            logActivity($pdo, 'Failed Login', $username, 'Invalid credentials attempt');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Ekagra Abhyasika</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">EA</div>
    <h2 class="login-title">Admin Login</h2>
    <p class="login-subtitle">Ekagra Abhyasika Management System</p>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-circle me-2"></i><?php echo e($error); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <div class="input-group">
          <span class="input-group-text" style="background:var(--bg-page);border-color:var(--border);">
            <i class="fas fa-user" style="color:var(--text-muted);"></i>
          </span>
          <input type="text" name="username" class="form-control" placeholder="Enter username"
                 value="<?php echo e($_POST['username'] ?? ''); ?>"
                 required autocomplete="username">
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text" style="background:var(--bg-page);border-color:var(--border);">
            <i class="fas fa-lock" style="color:var(--text-muted);"></i>
          </span>
          <input type="password" name="password" id="passwordField" class="form-control"
                 placeholder="Enter password" required autocomplete="current-password">
          <button type="button" class="btn btn-outline-secondary" onclick="togglePass()" style="border-color:var(--border);">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:15px;">
        <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
      </button>
    </form>

    <div class="text-center mt-4">
      <a href="../index.php" style="color:var(--text-muted);font-size:13px;text-decoration:none;">
        <i class="fas fa-arrow-left me-1"></i>Back to Website
      </a>
    </div>
    <div class="text-center mt-3 p-3" style="background:var(--bg-page);border-radius:8px;font-size:12px;color:var(--text-muted);">
      <strong>Default:</strong> admin / Admin@123 &nbsp;|&nbsp; Change after first login
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
  const f = document.getElementById('passwordField');
  const i = document.getElementById('eyeIcon');
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
setTimeout(() => document.querySelectorAll('.alert').forEach(a => { try { new bootstrap.Alert(a).close(); } catch(e){} }), 4000);
</script>
</body>
</html>