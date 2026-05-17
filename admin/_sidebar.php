<?php
ob_start();
// admin/_sidebar.php — included on every admin page
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

// Renewal alerts count
$alertCount = $pdo->query(
    "SELECT COUNT(*) FROM students WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status='active'"
)->fetchColumn();

$current_page = basename($_SERVER['PHP_SELF']);
?>
<div id="sidebarOverlay" class="sidebar-overlay"></div>
<div class="sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">EA</div>
    <h4>EKAGRA<br>ABHYASIKA</h4>
    <small>Admin Panel</small>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-title">Main</div>
    <a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
      <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a href="students.php" class="<?php echo $current_page === 'students.php' ? 'active' : ''; ?>">
      <i class="fas fa-users"></i> Students
    </a>
    <a href="seats.php" class="<?php echo $current_page === 'seats.php' ? 'active' : ''; ?>">
      <i class="fas fa-chair"></i> Seat Management
    </a>
    <div class="nav-section-title">Finance</div>
    <a href="payments.php" class="<?php echo $current_page === 'payments.php' ? 'active' : ''; ?>">
      <i class="fas fa-rupee-sign"></i> Payments
      <?php if ($alertCount > 0): ?>
        <span class="badge-count"><?php echo $alertCount; ?></span>
      <?php endif; ?>
    </a>
    <div class="nav-section-title">Reports</div>
    <a href="reports.php" class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
      <i class="fas fa-chart-bar"></i> Reports
    </a>
    <div class="nav-section-title">System</div>
    <a href="../index.php" target="_blank">
      <i class="fas fa-globe"></i> View Website
    </a>
    <a href="change_password.php" class="<?php echo $current_page === 'change_password.php' ? 'active' : ''; ?>">
      <i class="fas fa-key"></i> Change Password
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php">
      <i class="fas fa-sign-out-alt"></i>
      Logout (<?php echo htmlspecialchars($_SESSION['admin_user'] ?? 'admin'); ?>)
    </a>
  </div>
</div>
