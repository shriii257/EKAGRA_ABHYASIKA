<?php
// student/login.php — FIX: ob_start, db.php handles session_start()
ob_start();
require_once '../includes/db.php';

if (isset($_SESSION['student_id'])) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile   = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$mobile || !$password) {
        $error = 'Please enter your mobile number and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE mobile = ?");
        $stmt->execute([$mobile]);
        $student = $stmt->fetch();

        if ($student && (password_verify($password, $student['password']) || $password === $student['password'])) {
            session_regenerate_id(true);
            $_SESSION['student_id']   = $student['id'];
            $_SESSION['student_name'] = $student['full_name'];
            $_SESSION['student_mob']  = $student['mobile'];
            redirect('dashboard.php');
        } else {
            $error = 'Invalid mobile number or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Login – Ekagra Abhyasika</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">EA</div>
    <h2 class="login-title">Student Login</h2>
    <p class="login-subtitle">Ekagra Abhyasika — Member Portal</p>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Mobile Number</label>
        <div class="input-group">
          <span class="input-group-text" style="background:var(--bg-page);border-color:var(--border);">
            <i class="fas fa-mobile-alt" style="color:var(--text-muted);"></i>
          </span>
          <input type="tel" name="mobile" class="form-control" placeholder="10-digit mobile number"
                 value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>"
                 required autocomplete="username" maxlength="10">
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text" style="background:var(--bg-page);border-color:var(--border);">
            <i class="fas fa-lock" style="color:var(--text-muted);"></i>
          </span>
          <input type="password" name="password" id="passwordField" class="form-control"
                 placeholder="Your password" required autocomplete="current-password">
          <button type="button" class="btn btn-outline-secondary" onclick="togglePass()" style="border-color:var(--border);">
            <i class="fas fa-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:15px;">
        <i class="fas fa-sign-in-alt me-2"></i>Login to My Account
      </button>
    </form>

    <div class="text-center mt-4">
      <a href="../index.php" style="color:var(--text-muted);font-size:13px;text-decoration:none;">
        <i class="fas fa-arrow-left me-1"></i>Back to Website
      </a>
    </div>
    <div class="mt-4 p-3" style="background:var(--bg-page);border-radius:8px;font-size:12px;color:var(--text-muted);text-align:center;">
      <i class="fas fa-info-circle me-1"></i>
      Use your registered mobile number and the password given by the admin.<br>
      <strong>Demo:</strong> 9579089287 / Password
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
  const f = document.getElementById('passwordField');
  const i = document.getElementById('eyeIcon');
  if (f.type === 'password') { f.type = 'text'; i.className = 'fas fa-eye-slash'; }
  else { f.type = 'password'; i.className = 'fas fa-eye'; }
}
setTimeout(() => document.querySelectorAll('.alert').forEach(a => { try { new bootstrap.Alert(a).close(); } catch(e){} }), 4000);
</script>
</body>
</html>
