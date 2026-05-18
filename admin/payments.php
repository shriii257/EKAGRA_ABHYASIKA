<?php
ob_start();
require_once '../includes/db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$msg = $err = '';

// ---- RECORD PAYMENT ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_payment') {
    $student_id   = (int)$_POST['student_id'];
    $amount       = (float)$_POST['amount'];
    $type         = $_POST['payment_type'];
    $pay_date     = $_POST['payment_date'];
    $month_year   = trim($_POST['month_year'] ?? '');
    $notes        = trim($_POST['notes'] ?? '');

    if (!$student_id || !$amount || !$type || !$pay_date) {
        $err = 'Please fill all required fields.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO payments (student_id, amount, payment_type, payment_date, month_year, notes, recorded_by) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$student_id, $amount, $type, $pay_date, $month_year ?: null, $notes, $_SESSION['admin_id']]);

        // If monthly payment, update renewal date
        if ($type === 'monthly') {
            $student = $pdo->prepare("SELECT renewal_date, status FROM students WHERE id=?");
            $student->execute([$student_id]);
            $s = $student->fetch();
            // Use payment date as base so renewal extends exactly 1 month from when they paid.
            // Previously used renewal_date as base which caused 2-month extension for new students
            // (their renewal_date was already set 1 month ahead at registration time).
            $base = !empty($pay_date) ? $pay_date : date('Y-m-d');
            $newRenewal = date('Y-m-d', strtotime($base . ' +1 month'));
            $pdo->prepare("UPDATE students SET renewal_date=?, status='active' WHERE id=?")->execute([$newRenewal, $student_id]);
        }

        $stu = $pdo->prepare("SELECT full_name FROM students WHERE id=?");
        $stu->execute([$student_id]);
        $sName = $stu->fetchColumn();
        logActivity($pdo, 'Payment Recorded', $_SESSION['admin_user'], "₹$amount $type for $sName");
        $msg = "Payment of ₹" . number_format($amount) . " recorded successfully.";
    }
}

// ---- FILTER ----
$filter     = $_GET['filter'] ?? 'all';
$student_id = (int)($_GET['student_id'] ?? 0);
$search     = trim($_GET['search'] ?? '');

$where  = [];
$params = [];

if ($student_id) {
    $where[]  = "p.student_id = ?";
    $params[] = $student_id;
}
if ($filter === 'monthly')    { $where[] = "p.payment_type = 'monthly'"; }
if ($filter === 'registration') { $where[] = "p.payment_type = 'registration'"; }
if ($filter === 'deposit')    { $where[] = "p.payment_type = 'deposit'"; }
if ($filter === 'locker')     { $where[] = "p.payment_type = 'locker'"; }
if ($filter === 'expiring') {
    // Show students whose renewal is within 7 days - handled separately below
}
if ($search) {
    $where[]  = "(st.full_name LIKE ? OR st.mobile LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "SELECT p.*, st.full_name, st.mobile, st.seat_number FROM payments p
        LEFT JOIN students st ON p.student_id = st.id";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY p.created_at DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Expiring students list
$expiringStudents = $pdo->query(
    "SELECT * FROM students WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status='active' ORDER BY renewal_date ASC"
)->fetchAll();

// Expired students
$expiredStudents = $pdo->query(
    "SELECT * FROM students WHERE status='expired' ORDER BY renewal_date DESC LIMIT 20"
)->fetchAll();

// All active students for dropdown
$allStudents = $pdo->query("SELECT id, full_name, mobile, seat_number FROM students WHERE status IN ('active','expired') ORDER BY full_name")->fetchAll();

// Stats
$totalThisMonth = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn();
$totalAllTime   = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn();
$pendingCount   = count($expiringStudents);
$expiredCount   = count($expiredStudents);

$page_title = 'Payments';
$base = '../';
include '../includes/header.php';
?>
<div class="admin-wrapper">
<?php include '_sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">Fee & Payment Management</span>
    </div>
    <div class="topbar-right">
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
        <i class="fas fa-plus me-1"></i> Record Payment
      </button>
    </div>
  </div>

  <div class="page-content">
    <?php if($msg): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if($err): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-times-circle me-2"></i><?php echo htmlspecialchars($err); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-rupee-sign"></i></div>
          <div class="stat-info">
            <div class="stat-value">₹<?php echo number_format($totalThisMonth); ?></div>
            <div class="stat-label">This Month</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-database"></i></div>
          <div class="stat-info">
            <div class="stat-value">₹<?php echo number_format($totalAllTime); ?></div>
            <div class="stat-label">All Time Total</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon yellow"><i class="fas fa-clock"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?php echo $pendingCount; ?></div>
            <div class="stat-label">Renewals Due (7d)</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon red"><i class="fas fa-user-times"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?php echo $expiredCount; ?></div>
            <div class="stat-label">Expired Members</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Expiring Soon Alert -->
    <?php if(!empty($expiringStudents)): ?>
    <div class="card-panel mb-4">
      <div class="card-panel-header" style="background:rgba(0,188,212,0.08);">
        <span class="card-panel-title" style="color:var(--warning);"><i class="fas fa-exclamation-triangle me-2"></i>Renewals Due Within 7 Days (<?php echo count($expiringStudents); ?>)</span>
        <button class="btn btn-outline-primary btn-sm" onclick="exportTableToCSV('expiringTable','expiring_renewals.csv')"><i class="fas fa-download me-1"></i>Export</button>
      </div>
      <div class="card-panel-body p-0">
        <div class="table-responsive">
          <table class="table" id="expiringTable">
            <thead><tr><th>Student</th><th>Mobile</th><th>Seat</th><th>Type</th><th>Expires On</th><th>Days Left</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($expiringStudents as $s):
              $days = ceil((strtotime($s['renewal_date']) - time()) / 86400);
            ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></td>
              <td><?php echo $s['mobile']; ?></td>
              <td><span class="badge bg-primary"><?php echo $s['seat_number']; ?></span></td>
              <td><span class="badge-status <?php echo $s['seat_type']; ?>"><?php echo ucfirst($s['seat_type']); ?></span></td>
              <td style="color:var(--warning);font-weight:700;"><?php echo date('d M Y', strtotime($s['renewal_date'])); ?></td>
              <td>
                <span style="color:<?php echo $days<=3?'var(--danger)':'var(--warning)';?>;font-weight:800;"><?php echo max(0,$days); ?> day<?php echo $days!=1?'s':''; ?></span>
              </td>
              <td>
                <button class="btn btn-success btn-sm" onclick="prefillPayment(<?php echo $s['id']; ?>,'<?php echo addslashes($s['full_name']); ?>')"
                        data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                  <i class="fas fa-rupee-sign me-1"></i>Collect Fee
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Expired Members -->
    <?php if(!empty($expiredStudents)): ?>
    <div class="card-panel mb-4">
      <div class="card-panel-header" style="background:rgba(224,60,60,0.06);">
        <span class="card-panel-title" style="color:var(--danger);"><i class="fas fa-user-times me-2"></i>Expired Memberships</span>
      </div>
      <div class="card-panel-body p-0">
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Student</th><th>Mobile</th><th>Seat</th><th>Expired On</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($expiredStudents as $s): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></td>
              <td><?php echo $s['mobile']; ?></td>
              <td><span class="badge bg-secondary"><?php echo $s['seat_number']; ?></span></td>
              <td style="color:var(--danger);font-weight:700;"><?php echo date('d M Y', strtotime($s['renewal_date'])); ?></td>
              <td>
                <button class="btn btn-primary btn-sm" onclick="prefillPayment(<?php echo $s['id']; ?>,'<?php echo addslashes($s['full_name']); ?>')"
                        data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                  <i class="fas fa-sync me-1"></i>Renew
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Payment History -->
    <div class="card-panel">
      <div class="card-panel-header">
        <span class="card-panel-title"><i class="fas fa-history me-2"></i>Payment History</span>
        <button class="btn btn-outline-primary btn-sm" onclick="exportTableToCSV('paymentsTable','payments_export.csv')">
          <i class="fas fa-download me-1"></i>Export CSV
        </button>
      </div>
      <div class="card-panel-body">
        <!-- Filters -->
        <form method="GET" class="row g-2 mb-4">
          <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search by name or mobile..." value="<?php echo htmlspecialchars($search); ?>">
          </div>
          <div class="col-md-3">
            <select name="filter" class="form-select">
              <option value="all" <?php echo $filter==='all'?'selected':''; ?>>All Types</option>
              <option value="monthly" <?php echo $filter==='monthly'?'selected':''; ?>>Monthly Fees</option>
              <option value="registration" <?php echo $filter==='registration'?'selected':''; ?>>Registration</option>
              <option value="deposit" <?php echo $filter==='deposit'?'selected':''; ?>>Deposits</option>
              <option value="locker" <?php echo $filter==='locker'?'selected':''; ?>>Locker</option>
            </select>
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Filter</button>
          </div>
          <?php if($filter!=='all' || $search || $student_id): ?>
          <div class="col-md-2">
            <a href="payments.php" class="btn btn-outline-secondary w-100"><i class="fas fa-times me-1"></i>Clear</a>
          </div>
          <?php endif; ?>
        </form>

        <div class="table-responsive">
          <table class="table" id="paymentsTable">
            <thead>
              <tr>
                <th>#</th><th>Date</th><th>Student</th><th>Seat</th><th>Type</th><th>Month</th><th>Amount</th><th>Notes</th>
              </tr>
            </thead>
            <tbody>
            <?php if(empty($payments)): ?>
            <tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted);">No payment records found.</td></tr>
            <?php else: ?>
            <?php foreach($payments as $i => $p): ?>
            <tr>
              <td style="color:var(--text-muted);font-size:12px;"><?php echo $i+1; ?></td>
              <td style="font-size:13px;"><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
              <td>
                <div style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($p['full_name'] ?? '—'); ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?php echo $p['mobile'] ?? ''; ?></div>
              </td>
              <td><span class="badge bg-primary"><?php echo $p['seat_number'] ?? '—'; ?></span></td>
              <td>
                <?php
                $typeColors = ['monthly'=>'info','registration'=>'purple','deposit'=>'blue','deposit_refund'=>'red','reservation'=>'yellow','locker'=>'green'];
                $tc = $typeColors[$p['payment_type']] ?? 'blue';
                ?>
                <span class="badge-status <?php echo $tc; ?>"><?php echo ucfirst(str_replace('_',' ',$p['payment_type'])); ?></span>
              </td>
              <td style="font-size:12px;color:var(--text-muted);"><?php echo $p['month_year'] ?? '—'; ?></td>
              <td><strong style="color:var(--success);font-size:15px;">₹<?php echo number_format($p['amount'],0); ?></strong></td>
              <td style="font-size:12px;color:var(--text-muted);"><?php echo htmlspecialchars($p['notes'] ?? ''); ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /page-content -->
</div><!-- /main-content -->
</div><!-- /admin-wrapper -->

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-rupee-sign me-2"></i>Record Payment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="add_payment">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Student *</label>
            <select name="student_id" id="paymentStudentSelect" class="form-select" required>
              <option value="">— Select Student —</option>
              <?php foreach($allStudents as $s): ?>
              <option value="<?php echo $s['id']; ?>">
                <?php echo htmlspecialchars($s['full_name']); ?> (<?php echo $s['mobile']; ?>) — Seat <?php echo $s['seat_number']; ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label">Payment Type *</label>
              <select name="payment_type" id="paymentTypeSelect" class="form-select" required onchange="updateAmount(this.value)">
                <option value="monthly">Monthly Fee</option>
                <option value="registration">Registration Fee</option>
                <option value="deposit">Security Deposit</option>
                <option value="deposit_refund">Deposit Refund</option>
                <option value="reservation">Reservation Fee</option>
                <option value="locker">Locker Fee</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label">Amount (₹) *</label>
              <input type="number" name="amount" id="paymentAmount" class="form-control" value="1200" min="1" required>
            </div>
            <div class="col-6">
              <label class="form-label">Payment Date *</label>
              <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label">Month/Year <small style="color:var(--text-muted);">(for monthly)</small></label>
              <input type="text" name="month_year" class="form-control" placeholder="e.g. 2025-05">
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <input type="text" name="notes" class="form-control" placeholder="Optional note">
            </div>
          </div>
          <div class="alert alert-info mt-3" style="font-size:12px;">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Fee Rates:</strong> Monthly ₹1200 (Unreserved) · ₹1300 (Reserved) · ₹1400 (Reserved + Locker) | Registration ₹100 (one-time, non-refundable) | Deposit ₹500 (one-time, refundable on leaving) | Reservation ₹100 | Locker ₹100
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const amountMap = { monthly:1200, registration:100, deposit:500, deposit_refund:500, reservation:100, locker:100 };
function updateAmount(type) {
  document.getElementById('paymentAmount').value = amountMap[type] || '';
}
function prefillPayment(id, name) {
  const sel = document.getElementById('paymentStudentSelect');
  for (let opt of sel.options) { if (opt.value == id) { opt.selected = true; break; } }
}
</script>

<?php include '../includes/footer.php'; ?>
