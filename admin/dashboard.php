<?php
ob_start();
require_once '../includes/db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

// ---- Dashboard Stats ----
$totalSeats       = 108;
$reservedSeats    = 76;
$unreservedSeats  = 32;

$reservedOccupied   = $pdo->query("SELECT COUNT(*) FROM seats WHERE seat_type='reserved' AND status='occupied'")->fetchColumn();
$reservedAvailable  = $reservedSeats - $reservedOccupied;
$unreservedOccupied = $pdo->query("SELECT COUNT(*) FROM seats WHERE seat_type='unreserved' AND status='occupied'")->fetchColumn();

$activeStudents  = $pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$expiredStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE status='expired'")->fetchColumn();
$leftStudents    = $pdo->query("SELECT COUNT(*) FROM students WHERE status='left'")->fetchColumn();

$expiringIn7     = $pdo->query("SELECT COUNT(*) FROM students WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND status='active'")->fetchColumn();
$expiredToday    = $pdo->query("SELECT COUNT(*) FROM students WHERE renewal_date < CURDATE() AND status='expired'")->fetchColumn();

// Monthly revenue (current month)
$monthRevenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn();

// Recent activity
$recentActivity = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8")->fetchAll();

// Expiring soon list
$expiringList = $pdo->query("SELECT full_name, mobile, seat_number, renewal_date FROM students WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND status='active' ORDER BY renewal_date ASC LIMIT 5")->fetchAll();

// Monthly payment data for chart (last 6 months)
$chartData = $pdo->query("
  SELECT DATE_FORMAT(payment_date,'%b %Y') as month, SUM(amount) as total
  FROM payments
  WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY DATE_FORMAT(payment_date,'%Y-%m')
  ORDER BY MIN(payment_date)
")->fetchAll();

$page_title = 'Dashboard';
$base = '../';
include '../includes/header.php';
?>
<div class="admin-wrapper">
<?php include '_sidebar.php'; ?>

<div class="main-content">
  <!-- Topbar -->
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">Dashboard</span>
    </div>
    <div class="topbar-right">
      <?php if($expiringIn7 > 0): ?>
      <button class="topbar-notif" title="<?php echo $expiringIn7; ?> renewals due soon" onclick="window.location='payments.php?filter=expiring'">
        <i class="fas fa-bell"></i><span class="dot"></span>
      </button>
      <?php endif; ?>
      <div class="admin-avatar" title="<?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>">
        <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?>
      </div>
    </div>
  </div>

  <div class="page-content">
    <!-- Welcome -->
    <div style="margin-bottom:24px;">
      <h5 style="font-family:var(--font-display);font-weight:700;color:var(--primary);margin:0;">
        Good <?php echo (date('H')<12)?'Morning':((date('H')<17)?'Afternoon':'Evening'); ?>, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>! 👋
      </h5>
      <p style="color:var(--text-muted);font-size:13px;margin:4px 0 0;">
        <?php echo date('l, d F Y'); ?> &nbsp;|&nbsp; Library is open today 6:00 AM – 10:00 PM
      </p>
    </div>

    <?php if($expiringIn7 > 0): ?>
    <div class="alert-renewal">
      <i class="fas fa-exclamation-triangle me-2" style="color:var(--warning);"></i>
      <strong><?php echo $expiringIn7; ?> student(s)</strong> have renewals due within 7 days.
      <a href="payments.php?filter=expiring" style="color:var(--primary);font-weight:700;margin-left:8px;">View all →</a>
    </div>
    <?php endif; ?>

    <!-- Stat Cards Row 1 -->
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-chair"></i></div>
          <div class="stat-info">
            <div class="stat-value" data-target="<?php echo $totalSeats; ?>"><?php echo $totalSeats; ?></div>
            <div class="stat-label">Total Seats</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-users"></i></div>
          <div class="stat-info">
            <div class="stat-value" data-target="<?php echo $activeStudents; ?>"><?php echo $activeStudents; ?></div>
            <div class="stat-label">Active Students</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
          <div class="stat-icon yellow"><i class="fas fa-lock"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?php echo $reservedOccupied; ?></div>
            <div class="stat-label">Reserved Occupied</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
          <div class="stat-icon info"><i class="fas fa-check-circle"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?php echo $reservedAvailable; ?></div>
            <div class="stat-label">Reserved Free</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
          <div class="stat-icon red"><i class="fas fa-clock"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?php echo $expiringIn7; ?></div>
            <div class="stat-label">Expiring Soon</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
          <div class="stat-icon purple"><i class="fas fa-rupee-sign"></i></div>
          <div class="stat-info">
            <div class="stat-value">₹<?php echo number_format($monthRevenue); ?></div>
            <div class="stat-label">This Month</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Second row -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-user-lock"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?php echo $reservedSeats; ?></div>
            <div class="stat-label">Total Reserved</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon info"><i class="fas fa-door-open"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?php echo $unreservedSeats; ?></div>
            <div class="stat-label">Total Unreserved</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon red"><i class="fas fa-user-times"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?php echo $expiredStudents; ?></div>
            <div class="stat-label">Expired</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon yellow"><i class="fas fa-user-minus"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?php echo $leftStudents; ?></div>
            <div class="stat-label">Left Library</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts & Lists -->
    <div class="row g-4">
      <!-- Revenue Chart -->
      <div class="col-lg-8">
        <div class="card-panel">
          <div class="card-panel-header">
            <span class="card-panel-title"><i class="fas fa-chart-bar me-2"></i>Monthly Revenue</span>
            <small style="color:var(--text-muted);">Last 6 Months</small>
          </div>
          <div class="card-panel-body">
            <canvas id="revenueChart" height="100"></canvas>
          </div>
        </div>
      </div>

      <!-- Seat Status Pie -->
      <div class="col-lg-4">
        <div class="card-panel">
          <div class="card-panel-header">
            <span class="card-panel-title"><i class="fas fa-chart-pie me-2"></i>Seat Status</span>
          </div>
          <div class="card-panel-body">
            <canvas id="seatChart" height="180"></canvas>
            <div class="mt-3">
              <?php
              $occupied_total = $reservedOccupied + $unreservedOccupied;
              $free_total = $totalSeats - $occupied_total;
              $pairs = [['Reserved Occupied',$reservedOccupied,'#ef5350'],['Reserved Free',$reservedAvailable,'#66bb6a'],['Unreserved Occupied',$unreservedOccupied,'#ffca28'],['Unreserved Free',$totalSeats-$reservedSeats-$unreservedOccupied,'#bdbdbd']];
              foreach($pairs as $p): ?>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="display:flex;align-items:center;gap:8px;font-size:12px;">
                  <span style="width:10px;height:10px;background:<?php echo $p[2]; ?>;border-radius:2px;display:inline-block;"></span>
                  <?php echo $p[0]; ?>
                </span>
                <strong style="font-size:12px;"><?php echo $p[1]; ?></strong>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Expiring Soon -->
      <div class="col-lg-6">
        <div class="card-panel">
          <div class="card-panel-header">
            <span class="card-panel-title"><i class="fas fa-clock me-2 text-warning"></i>Expiring Soon</span>
            <a href="payments.php?filter=expiring" style="font-size:12px;color:var(--primary);">View All</a>
          </div>
          <div class="card-panel-body p-0">
            <?php if(empty($expiringList)): ?>
            <div class="empty-state"><i class="fas fa-check-circle" style="color:var(--success);"></i><p>No expiring memberships</p></div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm">
                <thead><tr><th>Student</th><th>Seat</th><th>Expires</th></tr></thead>
                <tbody>
                <?php foreach($expiringList as $s):
                  $days = (strtotime($s['renewal_date']) - time()) / 86400;
                ?>
                <tr>
                  <td>
                    <div style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($s['full_name']); ?></div>
                    <div style="font-size:11px;color:var(--text-muted);"><?php echo $s['mobile']; ?></div>
                  </td>
                  <td><span class="badge bg-primary"><?php echo $s['seat_number']; ?></span></td>
                  <td>
                    <span style="font-size:12px;color:<?php echo $days<=3?'var(--danger)':'var(--warning)';?>;font-weight:700;">
                      <?php echo date('d M', strtotime($s['renewal_date'])); ?>
                    </span><br>
                    <span style="font-size:11px;color:var(--text-muted);"><?php echo max(0,ceil($days)); ?> days left</span>
                  </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="col-lg-6">
        <div class="card-panel">
          <div class="card-panel-header">
            <span class="card-panel-title"><i class="fas fa-history me-2"></i>Recent Activity</span>
          </div>
          <div class="card-panel-body p-0">
            <div style="max-height:280px;overflow-y:auto;">
              <?php if(empty($recentActivity)): ?>
              <div class="empty-state"><i class="fas fa-list"></i><p>No recent activity</p></div>
              <?php else: ?>
              <?php foreach($recentActivity as $log): ?>
              <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;gap:12px;">
                <div style="width:8px;height:8px;background:var(--primary);border-radius:50%;margin-top:6px;flex-shrink:0;"></div>
                <div>
                  <div style="font-size:13px;font-weight:600;"><?php echo htmlspecialchars($log['action']); ?></div>
                  <?php if($log['details']): ?><div style="font-size:11px;color:var(--text-muted);"><?php echo htmlspecialchars($log['details']); ?></div><?php endif; ?>
                  <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                    <?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /page-content -->
</div><!-- /main-content -->
</div><!-- /admin-wrapper -->

<?php
$chartLabels = json_encode(array_column($chartData, 'month'));
$chartValues = json_encode(array_column($chartData, 'total'));
$unreservedFree = $unreservedSeats - $unreservedOccupied;
$extra_js = "
<script>
// Revenue Chart
const rctx = document.getElementById('revenueChart').getContext('2d');
new Chart(rctx, {
  type: 'bar',
  data: {
    labels: {$chartLabels},
    datasets: [{
      label: 'Revenue (₹)',
      data: {$chartValues},
      backgroundColor: 'rgba(13,43,110,0.8)',
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    responsive:true,
    plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: ctx => '₹' + ctx.raw.toLocaleString() } } },
    scales:{ y:{ beginAtZero:true, ticks:{ callback: v => '₹'+v.toLocaleString() } } }
  }
});

// Seat Pie
const sctx = document.getElementById('seatChart').getContext('2d');
new Chart(sctx, {
  type: 'doughnut',
  data: {
    labels: ['Reserved Occ.','Reserved Free','Unreserved Occ.','Unreserved Free'],
    datasets: [{ data: [{$reservedOccupied},{$reservedAvailable},{$unreservedOccupied},{$unreservedFree}], backgroundColor:['#ef5350','#66bb6a','#ffca28','#bdbdbd'], borderWidth:2 }]
  },
  options: { responsive:true, plugins:{ legend:{display:false} }, cutout:'70%' }
});
</script>";
include '../includes/footer.php';
?>