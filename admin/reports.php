<?php
ob_start();
require_once '../includes/db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$report = $_GET['report'] ?? 'active';

// ---- Data queries ----
$activeStudents = $pdo->query(
    "SELECT s.*, st.full_name, st.mobile, st.seat_number, st.seat_type, st.status, st.joining_date, st.renewal_date, st.deposit_paid
     FROM students st LEFT JOIN seats s ON s.seat_number = st.seat_number
     WHERE st.status = 'active' ORDER BY st.full_name"
)->fetchAll();

// Re-query simply
$activeStudents = $pdo->query(
    "SELECT * FROM students WHERE status='active' ORDER BY full_name"
)->fetchAll();

$expiredStudents = $pdo->query(
    "SELECT * FROM students WHERE status='expired' ORDER BY renewal_date DESC"
)->fetchAll();

$leftStudents = $pdo->query(
    "SELECT * FROM students WHERE status='left' ORDER BY updated_at DESC"
)->fetchAll();

$reservedSeats = $pdo->query(
    "SELECT se.*, st.full_name, st.mobile, st.renewal_date, st.status as student_status
     FROM seats se LEFT JOIN students st ON se.student_id = st.id
     WHERE se.seat_type='reserved' ORDER BY se.seat_number"
)->fetchAll();

$availableSeats = $pdo->query(
    "SELECT * FROM seats WHERE status='available' ORDER BY seat_number"
)->fetchAll();

// Payment summary by month
$monthlyRevenue = $pdo->query(
    "SELECT DATE_FORMAT(payment_date,'%M %Y') as month_label,
            DATE_FORMAT(payment_date,'%Y-%m') as month_key,
            SUM(amount) as total,
            COUNT(*) as count
     FROM payments
     GROUP BY DATE_FORMAT(payment_date,'%Y-%m')
     ORDER BY month_key DESC LIMIT 12"
)->fetchAll();

// Stats
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn();
$thisMonthRev = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn();

$page_title = 'Reports';
$base = '../';
include '../includes/header.php';
?>
<div class="admin-wrapper">
<?php include '_sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">Reports</span>
    </div>
    <div class="topbar-right">
      <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
        <i class="fas fa-print me-1"></i>Print
      </button>
    </div>
  </div>

  <div class="page-content">

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-users"></i></div>
          <div class="stat-info"><div class="stat-value"><?php echo count($activeStudents); ?></div><div class="stat-label">Active Students</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon red"><i class="fas fa-user-times"></i></div>
          <div class="stat-info"><div class="stat-value"><?php echo count($expiredStudents); ?></div><div class="stat-label">Expired</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-chair"></i></div>
          <div class="stat-info"><div class="stat-value"><?php echo count($availableSeats); ?></div><div class="stat-label">Free Seats</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon purple"><i class="fas fa-rupee-sign"></i></div>
          <div class="stat-info"><div class="stat-value">₹<?php echo number_format($totalRevenue); ?></div><div class="stat-label">Total Revenue</div></div>
        </div>
      </div>
    </div>

    <!-- Report Tabs -->
    <div class="card-panel mb-4">
      <div class="card-panel-body" style="padding:12px;">
        <div class="d-flex flex-wrap gap-2">
          <?php
          $tabs = [
            'active'    => ['Active Students', 'fas fa-users', 'success'],
            'expired'   => ['Expired Members', 'fas fa-user-times', 'danger'],
            'left'      => ['Left Students', 'fas fa-user-minus', 'secondary'],
            'reserved'  => ['Reserved Seats', 'fas fa-lock', 'primary'],
            'available' => ['Available Seats', 'fas fa-chair', 'info'],
            'revenue'   => ['Monthly Revenue', 'fas fa-rupee-sign', 'warning'],
          ];
          foreach($tabs as $key => [$label, $icon, $color]): ?>
          <a href="?report=<?php echo $key; ?>"
             class="btn btn-<?php echo $report===$key ? $color : 'outline-'.$color; ?> btn-sm">
            <i class="<?php echo $icon; ?> me-1"></i><?php echo $label; ?>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Active Students Report -->
    <?php if($report === 'active'): ?>
    <div class="card-panel">
      <div class="card-panel-header">
        <span class="card-panel-title"><i class="fas fa-users me-2"></i>Active Students Report (<?php echo count($activeStudents); ?>)</span>
        <button class="btn btn-outline-primary btn-sm" onclick="exportTableToCSV('reportTable','active_students.csv')">
          <i class="fas fa-download me-1"></i>Export CSV
        </button>
      </div>
      <div class="card-panel-body p-0">
        <div class="table-responsive">
          <table class="table" id="reportTable">
            <thead><tr><th>#</th><th>Name</th><th>Mobile</th><th>Seat</th><th>Type</th><th>Joining Date</th><th>Renewal Date</th><th>Deposit</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach($activeStudents as $i => $s): ?>
            <tr>
              <td><?php echo $i+1; ?></td>
              <td><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></td>
              <td><?php echo $s['mobile']; ?></td>
              <td><span class="badge bg-primary"><?php echo $s['seat_number']; ?></span></td>
              <td><span class="badge-status <?php echo $s['seat_type']; ?>"><?php echo ucfirst($s['seat_type']); ?></span></td>
              <td><?php echo date('d M Y', strtotime($s['joining_date'])); ?></td>
              <td><?php echo $s['renewal_date'] ? date('d M Y', strtotime($s['renewal_date'])) : '—'; ?></td>
              <td><?php echo $s['deposit_paid'] ? '<span class="badge-status paid">Paid</span>' : '<span class="badge-status unpaid">Unpaid</span>'; ?></td>
              <td style="font-size:12px;color:var(--text-muted);"><?php echo htmlspecialchars($s['notes'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Expired Members Report -->
    <?php elseif($report === 'expired'): ?>
    <div class="card-panel">
      <div class="card-panel-header">
        <span class="card-panel-title" style="color:var(--danger);"><i class="fas fa-user-times me-2"></i>Expired Memberships (<?php echo count($expiredStudents); ?>)</span>
        <button class="btn btn-outline-primary btn-sm" onclick="exportTableToCSV('reportTable','expired_members.csv')"><i class="fas fa-download me-1"></i>Export CSV</button>
      </div>
      <div class="card-panel-body p-0">
        <div class="table-responsive">
          <table class="table" id="reportTable">
            <thead><tr><th>#</th><th>Name</th><th>Mobile</th><th>Seat</th><th>Type</th><th>Expired On</th><th>Joining Date</th></tr></thead>
            <tbody>
            <?php foreach($expiredStudents as $i => $s): ?>
            <tr>
              <td><?php echo $i+1; ?></td>
              <td><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></td>
              <td><?php echo $s['mobile']; ?></td>
              <td><span class="badge bg-secondary"><?php echo $s['seat_number']; ?></span></td>
              <td><span class="badge-status <?php echo $s['seat_type']; ?>"><?php echo ucfirst($s['seat_type']); ?></span></td>
              <td style="color:var(--danger);font-weight:700;"><?php echo date('d M Y', strtotime($s['renewal_date'])); ?></td>
              <td><?php echo date('d M Y', strtotime($s['joining_date'])); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Left Students -->
    <?php elseif($report === 'left'): ?>
    <div class="card-panel">
      <div class="card-panel-header">
        <span class="card-panel-title"><i class="fas fa-user-minus me-2"></i>Students Who Left (<?php echo count($leftStudents); ?>)</span>
        <button class="btn btn-outline-primary btn-sm" onclick="exportTableToCSV('reportTable','left_students.csv')"><i class="fas fa-download me-1"></i>Export CSV</button>
      </div>
      <div class="card-panel-body p-0">
        <div class="table-responsive">
          <table class="table" id="reportTable">
            <thead><tr><th>#</th><th>Name</th><th>Mobile</th><th>Last Seat</th><th>Type</th><th>Joining Date</th><th>Deposit</th></tr></thead>
            <tbody>
            <?php foreach($leftStudents as $i => $s): ?>
            <tr>
              <td><?php echo $i+1; ?></td>
              <td><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></td>
              <td><?php echo $s['mobile']; ?></td>
              <td><span class="badge bg-secondary"><?php echo $s['seat_number']; ?></span></td>
              <td><span class="badge-status <?php echo $s['seat_type']; ?>"><?php echo ucfirst($s['seat_type']); ?></span></td>
              <td><?php echo date('d M Y', strtotime($s['joining_date'])); ?></td>
              <td><?php echo $s['deposit_paid'] ? '<span class="badge-status paid">Paid (Refund Due)</span>' : '<span class="badge-status unpaid">Unpaid</span>'; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Reserved Seats -->
    <?php elseif($report === 'reserved'): ?>
    <div class="card-panel">
      <div class="card-panel-header">
        <span class="card-panel-title"><i class="fas fa-lock me-2"></i>Reserved Seats Status (Seats 1–76)</span>
        <button class="btn btn-outline-primary btn-sm" onclick="exportTableToCSV('reportTable','reserved_seats.csv')"><i class="fas fa-download me-1"></i>Export CSV</button>
      </div>
      <div class="card-panel-body p-0">
        <div class="table-responsive">
          <table class="table" id="reportTable">
            <thead><tr><th>Seat</th><th>Status</th><th>Student Name</th><th>Mobile</th><th>Renewal Date</th><th>Student Status</th></tr></thead>
            <tbody>
            <?php foreach($reservedSeats as $s): ?>
            <tr>
              <td><span class="badge bg-primary"><?php echo $s['seat_number']; ?></span></td>
              <td>
                <span style="color:<?php echo $s['status']==='occupied'?'var(--danger)':'var(--success)';?>;font-weight:700;">
                  <i class="fas fa-circle" style="font-size:8px;"></i> <?php echo ucfirst($s['status']); ?>
                </span>
              </td>
              <td><?php echo $s['full_name'] ? htmlspecialchars($s['full_name']) : '<span style="color:var(--text-muted);">Vacant</span>'; ?></td>
              <td style="font-size:13px;"><?php echo $s['mobile'] ?? '—'; ?></td>
              <td style="font-size:13px;"><?php echo $s['renewal_date'] ? date('d M Y', strtotime($s['renewal_date'])) : '—'; ?></td>
              <td><?php echo $s['student_status'] ? '<span class="badge-status '.$s['student_status'].'">'.ucfirst($s['student_status']).'</span>' : '—'; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Available Seats -->
    <?php elseif($report === 'available'): ?>
    <div class="card-panel">
      <div class="card-panel-header">
        <span class="card-panel-title" style="color:var(--success);"><i class="fas fa-chair me-2"></i>Available Seats (<?php echo count($availableSeats); ?>)</span>
        <button class="btn btn-outline-primary btn-sm" onclick="exportTableToCSV('reportTable','available_seats.csv')"><i class="fas fa-download me-1"></i>Export CSV</button>
      </div>
      <div class="card-panel-body">
        <div class="row g-2 mb-4">
          <?php foreach($availableSeats as $s): ?>
          <div class="col-4 col-md-2 col-lg-1">
            <div style="text-align:center;padding:10px 4px;border-radius:8px;background:<?php echo $s['seat_type']==='reserved'?'#e8f5e9':'#f5f5f5';?>;border:2px solid <?php echo $s['seat_type']==='reserved'?'#66bb6a':'#bdbdbd';?>;font-weight:800;font-size:13px;color:<?php echo $s['seat_type']==='reserved'?'#2e7d32':'#616161';?>;">
              <?php echo $s['seat_number']; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="d-flex gap-3">
          <div class="legend-item"><div class="legend-dot green"></div>Reserved — Available</div>
          <div class="legend-item"><div class="legend-dot gray"></div>Unreserved — Available</div>
        </div>
        <hr>
        <div class="table-responsive">
          <table class="table table-sm" id="reportTable">
            <thead><tr><th>Seat</th><th>Type</th></tr></thead>
            <tbody>
            <?php foreach($availableSeats as $s): ?>
            <tr>
              <td><span class="badge bg-<?php echo $s['seat_type']==='reserved'?'success':'secondary'; ?>"><?php echo $s['seat_number']; ?></span></td>
              <td><span class="badge-status <?php echo $s['seat_type']; ?>"><?php echo ucfirst($s['seat_type']); ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Monthly Revenue -->
    <?php elseif($report === 'revenue'): ?>
    <div class="card-panel">
      <div class="card-panel-header">
        <span class="card-panel-title"><i class="fas fa-chart-line me-2"></i>Monthly Revenue Report</span>
        <button class="btn btn-outline-primary btn-sm" onclick="exportTableToCSV('reportTable','monthly_revenue.csv')"><i class="fas fa-download me-1"></i>Export CSV</button>
      </div>
      <div class="card-panel-body">
        <canvas id="fullRevenueChart" height="80" class="mb-4"></canvas>
        <div class="table-responsive">
          <table class="table" id="reportTable">
            <thead><tr><th>Month</th><th>Total Revenue</th><th>Transactions</th></tr></thead>
            <tbody>
            <?php $grandTotal = 0; foreach($monthlyRevenue as $m): $grandTotal += $m['total']; ?>
            <tr>
              <td><strong><?php echo $m['month_label']; ?></strong></td>
              <td style="color:var(--success);font-weight:700;font-size:16px;">₹<?php echo number_format($m['total']); ?></td>
              <td><?php echo $m['count']; ?> payments</td>
            </tr>
            <?php endforeach; ?>
            <tr style="background:rgba(26,122,46,0.05);">
              <td><strong>Grand Total</strong></td>
              <td><strong style="color:var(--primary);font-size:18px;">₹<?php echo number_format($grandTotal); ?></strong></td>
              <td></td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php
    $cLabels = json_encode(array_reverse(array_column($monthlyRevenue, 'month_label')));
    $cValues = json_encode(array_reverse(array_column($monthlyRevenue, 'total')));
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
      const ctx = document.getElementById('fullRevenueChart');
      if(ctx) new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
          labels: <?php echo $cLabels; ?>,
          datasets: [{ label: 'Revenue (₹)', data: <?php echo $cValues; ?>,
            borderColor: 'var(--primary)', backgroundColor: 'rgba(26,122,46,0.08)',
            fill: true, tension: 0.4, pointRadius: 5 }]
        },
        options: {
          responsive: true,
          plugins: { legend: {display:false}, tooltip: { callbacks: { label: ctx => '₹' + Number(ctx.raw).toLocaleString() } } },
          scales: { y: { beginAtZero:true, ticks: { callback: v => '₹'+Number(v).toLocaleString() } } }
        }
      });
    });
    </script>
    <?php endif; ?>

  </div>
</div>
</div>

<?php include '../includes/footer.php'; ?>
