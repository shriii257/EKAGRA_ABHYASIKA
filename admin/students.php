<?php
ob_start();
require_once '../includes/db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$msg = $err = '';

// ---- ADD STUDENT ----
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='add') {
    $name    = trim($_POST['full_name']);
    $mobile  = trim($_POST['mobile']);
    $pass    = $_POST['password'];
    $addr    = trim($_POST['address']??'');
    $aadhaar = trim($_POST['aadhaar']??'');
    $parent  = trim($_POST['parent_name']??'');
    $emerg   = trim($_POST['emergency_contact']??'');
    $jdate        = $_POST['joining_date'];
    $stype        = $_POST['seat_type'];   // 'reserved' or 'unreserved'
    $seatNo       = ($stype === 'reserved') ? (int)$_POST['seat_number'] : null;
    $dep          = isset($_POST['deposit_paid']) ? 1 : 0;
    $monthly_paid = isset($_POST['monthly_paid']) ? 1 : 0;
    $monthly_amt  = $monthly_paid ? max(1, (float)($_POST['monthly_amount'] ?? 1200)) : 0;
    $notes        = trim($_POST['notes']??'');

    // Renewal date: if monthly fee collected now, auto-set to joining_date + 1 month.
    // Otherwise set to joining_date — admin collects monthly fee later via Payments page.
    $rdate = $monthly_paid
        ? date('Y-m-d', strtotime($jdate . ' +1 month'))
        : $jdate;

    // ── Capacity guards ──────────────────────────────────────────────
    $totalCap          = 107;
    $reservedCap       = 76;
    $unreservedCap     = 31;
    $totalActive       = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
    $reservedActive    = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE seat_type='reserved'   AND status='active'")->fetchColumn();
    $unreservedActive2 = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE seat_type='unreserved' AND status='active'")->fetchColumn();

    if (!$name || !$mobile || !$pass || !$jdate || !$stype) {
        $err = 'Please fill all required fields.';
    } elseif ($stype === 'reserved' && !$seatNo) {
        $err = 'Please enter a seat number for reserved admission.';
    } elseif ($totalActive >= $totalCap) {
        $err = "Admission full. All 107 seats are occupied ($totalActive/107 active students).";
    } elseif ($stype === 'reserved' && $reservedActive >= $reservedCap) {
        $err = "Reserved quota full ($reservedActive/$reservedCap). No more reserved admissions.";
    } elseif ($stype === 'unreserved' && $unreservedActive2 >= $unreservedCap) {
        $err = "Unreserved quota full ($unreservedActive2/$unreservedCap). No more unreserved admissions.";
    } else {
        // If reserved, check seat availability
        $seatOk = true;
        if ($stype === 'reserved') {
            $seatRow = $pdo->prepare("SELECT * FROM seats WHERE seat_number=?");
            $seatRow->execute([$seatNo]);
            $seat = $seatRow->fetch();
            if (!$seat)                       { $err = "Seat $seatNo does not exist."; $seatOk = false; }
            elseif ($seat['status']==='occupied') { $err = "Seat $seatNo is already reserved by another student."; $seatOk = false; }
        }

        if ($seatOk) {
            try {
                $hashed = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO students (full_name,mobile,password,address,aadhaar,parent_name,emergency_contact,joining_date,seat_number,seat_type,renewal_date,deposit_paid,notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$name,$mobile,$hashed,$addr,$aadhaar,$parent,$emerg,$jdate,$seatNo,$stype,$rdate,$dep,$notes]);
                $sid = $pdo->lastInsertId();

                // Lock the physical seat only for reserved students
                if ($stype === 'reserved') {
                    $pdo->prepare("UPDATE seats SET status='occupied', seat_type='reserved', student_id=? WHERE seat_number=?")->execute([$sid,$seatNo]);
                }

                // Payments
                $locker = isset($_POST['locker_paid']) ? 1 : 0;
                $pdo->prepare("INSERT INTO payments (student_id,amount,payment_type,payment_date,notes) VALUES (?,100,'registration',?,?)")->execute([$sid,$jdate,'Registration fee']);
                if ($dep) {
                    $pdo->prepare("INSERT INTO payments (student_id,amount,payment_type,payment_date,notes) VALUES (?,500,'deposit',?,?)")->execute([$sid,$jdate,'Security deposit']);
                }
                if ($stype === 'reserved') {
                    $pdo->prepare("INSERT INTO payments (student_id,amount,payment_type,payment_date,notes) VALUES (?,100,'reservation',?,?)")->execute([$sid,$jdate,'Seat reservation fee']);
                }
                if ($locker) {
                    $pdo->prepare("INSERT INTO payments (student_id,amount,payment_type,payment_date,notes) VALUES (?,100,'locker',?,?)")->execute([$sid,$jdate,'Locker fee']);
                }
                if ($monthly_paid) {
                    $pdo->prepare("INSERT INTO payments (student_id,amount,payment_type,payment_date,month_year,notes) VALUES (?,?,'monthly',?,?,?)")->execute([$sid,$monthly_amt,$jdate,date('Y-m',$jdate?strtotime($jdate):time()),'First month fee']);
                }

                $seatInfo = $stype === 'reserved' ? "Seat $seatNo (Reserved)" : "Unreserved (no fixed seat)";
                logActivity($pdo,'Student Added',$_SESSION['admin_user'],"Added $name — $seatInfo");
                $msg = "Student '$name' added successfully. " . ($stype==='reserved' ? "Seat $seatNo locked." : "Unreserved access granted.");
            } catch (PDOException $e) {
                $err = 'Error: ' . ($e->getCode()==23000 ? 'Mobile number already exists.' : $e->getMessage());
            }
        }
    }
}

// ---- LEAVE / DELETE ----
if (isset($_GET['action'])) {
    $sid = (int)$_GET['id'];
    if ($_GET['action']==='leave') {
        $s = $pdo->prepare("SELECT * FROM students WHERE id=?"); $s->execute([$sid]); $s = $s->fetch();
        if ($s) {
            $pdo->prepare("UPDATE students SET status='left' WHERE id=?")->execute([$sid]);
            if ($s['seat_type']==='reserved' && $s['seat_number']) {
                $pdo->prepare("UPDATE seats SET status='available', seat_type=NULL, student_id=NULL WHERE seat_number=?")->execute([$s['seat_number']]);
            }
            logActivity($pdo,'Student Left',$_SESSION['admin_user'],"$s[full_name] left — " . ($s['seat_number'] ? "Seat $s[seat_number] freed" : "Unreserved"));
            $msg = "Student marked as left." . ($s['seat_number'] ? " Seat freed." : "");
        }
    }
    if ($_GET['action']==='delete') {
        $s = $pdo->prepare("SELECT * FROM students WHERE id=?"); $s->execute([$sid]); $s = $s->fetch();
        if ($s) {
            if ($s['seat_type']==='reserved' && $s['seat_number']) {
                $pdo->prepare("UPDATE seats SET status='available', seat_type=NULL, student_id=NULL WHERE seat_number=?")->execute([$s['seat_number']]);
            }
            $pdo->prepare("DELETE FROM students WHERE id=?")->execute([$sid]);
            logActivity($pdo,'Student Deleted',$_SESSION['admin_user'],"Deleted $s[full_name]");
            $msg = "Student deleted.";
        }
    }
}

// ---- FILTERS ----
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$where = []; $params = [];
if ($search) { $where[] = "(s.full_name LIKE ? OR s.mobile LIKE ? OR s.seat_number LIKE ?)"; $q="%$search%"; $params=[$q,$q,$q]; }
if ($filter==='active')     { $where[] = "s.status='active'"; }
if ($filter==='expired')    { $where[] = "s.status='expired'"; }
if ($filter==='left')       { $where[] = "s.status='left'"; }
if ($filter==='reserved')   { $where[] = "s.seat_type='reserved'"; }
if ($filter==='unreserved') { $where[] = "s.seat_type='unreserved'"; }
$sql = "SELECT s.* FROM students s" . ($where ? " WHERE ".implode(' AND ',$where) : "") . " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$students = $stmt->fetchAll();

// View single student
$viewStudent = null;
if (isset($_GET['view'])) {
    $vs = $pdo->prepare("SELECT * FROM students WHERE id=?"); $vs->execute([(int)$_GET['view']]); $viewStudent = $vs->fetch();
    if ($viewStudent) {
        $payments = $pdo->prepare("SELECT * FROM payments WHERE student_id=? ORDER BY payment_date DESC");
        $payments->execute([$viewStudent['id']]); $viewPayments = $payments->fetchAll();
    }
}

// Free seats list for modal
$freeSeats = $pdo->query("SELECT seat_number FROM seats WHERE status='available' ORDER BY seat_number")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Students';
$base = '../';
include '../includes/header.php';
?>
<div class="admin-wrapper">
<?php include '_sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">Student Management</span>
    </div>
    <div class="topbar-right">
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="fas fa-user-plus me-1"></i> Add Student
      </button>
    </div>
  </div>

  <div class="page-content">
    <?php if($msg): ?><div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if($err): ?><div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-times-circle me-2"></i><?php echo htmlspecialchars($err); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <?php if($viewStudent): ?>
    <div class="card-panel mb-4">
      <div class="card-panel-header">
        <span class="card-panel-title"><i class="fas fa-user me-2"></i><?php echo htmlspecialchars($viewStudent['full_name']); ?></span>
        <a href="students.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
      </div>
      <div class="card-panel-body">
        <div class="row g-4">
          <div class="col-md-6">
            <table class="table table-sm">
              <tr><th style="width:160px;">Full Name</th><td><?php echo htmlspecialchars($viewStudent['full_name']); ?></td></tr>
              <tr><th>Mobile</th><td><?php echo htmlspecialchars($viewStudent['mobile']); ?></td></tr>
              <tr><th>Address</th><td><?php echo htmlspecialchars($viewStudent['address']); ?></td></tr>
              <tr><th>Aadhaar</th><td><?php echo htmlspecialchars($viewStudent['aadhaar']); ?></td></tr>
              <tr><th>Parent Name</th><td><?php echo htmlspecialchars($viewStudent['parent_name']); ?></td></tr>
              <tr><th>Emergency</th><td><?php echo htmlspecialchars($viewStudent['emergency_contact']); ?></td></tr>
            </table>
          </div>
          <div class="col-md-6">
            <table class="table table-sm">
              <tr><th style="width:160px;">Seat</th><td>
                <?php if($viewStudent['seat_type']==='reserved'): ?>
                  <span class="badge bg-primary fs-6"><?php echo $viewStudent['seat_number']; ?></span>
                  <span class="badge-status reserved ms-1">Reserved</span>
                <?php else: ?>
                  <span class="badge bg-secondary">No fixed seat</span>
                  <span class="badge-status unreserved ms-1">Unreserved</span>
                <?php endif; ?>
              </td></tr>
              <tr><th>Status</th><td><span class="badge-status <?php echo $viewStudent['status']; ?>"><?php echo ucfirst($viewStudent['status']); ?></span></td></tr>
              <tr><th>Joining Date</th><td><?php echo date('d M Y', strtotime($viewStudent['joining_date'])); ?></td></tr>
              <tr><th>Renewal Date</th><td><?php echo date('d M Y', strtotime($viewStudent['renewal_date'])); ?></td></tr>
              <tr><th>Deposit</th><td><?php echo $viewStudent['deposit_paid'] ? '<span class="badge-status paid">Paid</span>' : '<span class="badge-status unpaid">Unpaid</span>'; ?></td></tr>
            </table>
            <?php if($viewStudent['notes']): ?>
            <div style="background:var(--bg-page);border-radius:8px;padding:12px;font-size:13px;margin-top:8px;">
              <strong>Notes:</strong> <?php echo htmlspecialchars($viewStudent['notes']); ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <hr>
        <h6 class="font-display fw-bold mb-3">Payment History</h6>
        <?php if(!empty($viewPayments)): ?>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach($viewPayments as $p): ?>
            <tr>
              <td><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
              <td><?php echo ucfirst(str_replace('_',' ',$p['payment_type'])); ?></td>
              <td><strong>₹<?php echo number_format($p['amount'],0); ?></strong></td>
              <td style="color:var(--text-muted);font-size:12px;"><?php echo htmlspecialchars($p['notes']); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Filter & List -->
    <div class="card-panel">
      <div class="card-panel-header">
        <span class="card-panel-title"><i class="fas fa-users me-2"></i>All Students (<?php echo count($students); ?>)</span>
        <button class="btn btn-outline-primary btn-sm" onclick="exportTableToCSV('studentsTable','ekagra_students.csv')">
          <i class="fas fa-download me-1"></i>Export CSV
        </button>
      </div>
      <div class="card-panel-body">
        <form method="GET" class="row g-2 mb-4">
          <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Search name, mobile, seat..." value="<?php echo htmlspecialchars($search); ?>">
          </div>
          <div class="col-md-4">
            <select name="filter" class="form-select">
              <option value="all"        <?php echo $filter==='all'?'selected':''; ?>>All Students</option>
              <option value="active"     <?php echo $filter==='active'?'selected':''; ?>>Active</option>
              <option value="expired"    <?php echo $filter==='expired'?'selected':''; ?>>Expired</option>
              <option value="left"       <?php echo $filter==='left'?'selected':''; ?>>Left Library</option>
              <option value="reserved"   <?php echo $filter==='reserved'?'selected':''; ?>>Reserved</option>
              <option value="unreserved" <?php echo $filter==='unreserved'?'selected':''; ?>>Unreserved</option>
            </select>
          </div>
          <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Search</button>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table" id="studentsTable">
            <thead><tr><th>#</th><th>Name</th><th>Mobile</th><th>Seat</th><th>Type</th><th>Status</th><th>Renewal</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if(empty($students)): ?>
            <tr><td colspan="8" class="text-center py-4" style="color:var(--text-muted);">No students found.</td></tr>
            <?php else: ?>
            <?php foreach($students as $i => $s):
              $days = (strtotime($s['renewal_date']) - time()) / 86400;
              $renewalClass = ($s['status']==='active' && $days<=7) ? 'style="color:var(--danger);font-weight:700;"' : '';
            ?>
            <tr>
              <td style="font-size:13px;color:var(--text-muted);"><?php echo $i+1; ?></td>
              <td>
                <div style="font-weight:600;"><?php echo htmlspecialchars($s['full_name']); ?></div>
                <?php if($s['parent_name']): ?><div style="font-size:11px;color:var(--text-muted);"><?php echo htmlspecialchars($s['parent_name']); ?></div><?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($s['mobile']); ?></td>
              <td>
                <?php if($s['seat_type']==='reserved'): ?>
                  <span class="badge bg-primary"><?php echo $s['seat_number']; ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary" title="No fixed seat">General</span>
                <?php endif; ?>
              </td>
              <td><span class="badge-status <?php echo $s['seat_type']; ?>"><?php echo ucfirst($s['seat_type']); ?></span></td>
              <td><span class="badge-status <?php echo $s['status']; ?>"><?php echo ucfirst($s['status']); ?></span></td>
              <td <?php echo $renewalClass; ?>><?php echo $s['renewal_date'] ? date('d M Y', strtotime($s['renewal_date'])) : '-'; ?></td>
              <td>
                <div class="d-flex gap-1">
                  <a href="?view=<?php echo $s['id']; ?>" class="btn btn-outline-primary btn-sm" title="View"><i class="fas fa-eye"></i></a>
                  <?php if($s['status']==='active'): ?>
                  <a href="?action=leave&id=<?php echo $s['id']; ?>" class="btn btn-warning btn-sm" title="Mark Left"
                     data-confirm="Mark <?php echo htmlspecialchars($s['full_name']); ?> as left?<?php echo $s['seat_number'] ? ' Seat will be freed.' : ''; ?>">
                    <i class="fas fa-sign-out-alt"></i>
                  </a>
                  <?php endif; ?>
                  <a href="payments.php?student_id=<?php echo $s['id']; ?>" class="btn btn-success btn-sm" title="Payments"><i class="fas fa-rupee-sign"></i></a>
                  <a href="?action=delete&id=<?php echo $s['id']; ?>" class="btn btn-danger btn-sm" title="Delete"
                     data-confirm="Delete <?php echo htmlspecialchars($s['full_name']); ?>? Cannot be undone.">
                    <i class="fas fa-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Add New Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Full Name *</label>
              <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Mobile Number *</label>
              <input type="tel" name="mobile" class="form-control" pattern="[0-9]{10}" placeholder="10 digit number" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password * <small style="color:var(--text-muted);">(for student login)</small></label>
              <input type="text" name="password" class="form-control" required placeholder="Set a password">
            </div>
            <div class="col-md-6">
              <label class="form-label">Aadhaar Number</label>
              <input type="text" name="aadhaar" class="form-control" placeholder="XXXX-XXXX-XXXX">
            </div>
            <div class="col-12">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Parent/Guardian Name</label>
              <input type="text" name="parent_name" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Emergency Contact</label>
              <input type="tel" name="emergency_contact" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Joining Date *</label>
              <input type="date" name="joining_date" id="joiningDateInput" class="form-control" value="<?php echo date('Y-m-d'); ?>" required onchange="updateRenewalPreview()">
            </div>
            <div class="col-md-6">
              <label class="form-label">Renewal Date <small style="color:var(--text-muted);">(auto-calculated)</small></label>
              <input type="text" id="renewalPreview" class="form-control" readonly style="background:var(--bg-page);color:var(--text-muted);cursor:not-allowed;" value="Set after collecting monthly fee">
            </div>

            <!-- Seat Type Choice -->
            <div class="col-12">
              <label class="form-label fw-bold">Seat Preference * <small style="color:var(--text-muted);font-weight:400;">(student's choice)</small></label>
              <div class="d-flex gap-3 mt-1">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="seat_type" id="typeReserved" value="reserved" onchange="toggleSeatField()" required>
                  <label class="form-check-label fw-bold" for="typeReserved">
                    🔒 Reserved <small style="color:var(--text-muted);font-weight:400;">(picks a specific seat, locked permanently, +₹100 fee)</small>
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="seat_type" id="typeUnreserved" value="unreserved" onchange="toggleSeatField()">
                  <label class="form-check-label" for="typeUnreserved">
                    🪑 Unreserved <small style="color:var(--text-muted);">(sits anywhere free each day, no fixed seat)</small>
                  </label>
                </div>
              </div>
            </div>

            <!-- Seat Number — shown only for reserved -->
            <div class="col-md-6" id="seatNumberField" style="display:none;">
              <label class="form-label">Seat Number * <small style="color:var(--text-muted);">(1–107, any available seat)</small></label>
              <input type="number" name="seat_number" id="seatNumberInput" class="form-control" min="1" max="107" placeholder="Enter seat no.">
              <div class="mt-1" style="font-size:12px;color:var(--text-muted);">
                Free seats:
                <span id="freeSeatsList"><?php echo implode(', ', array_slice($freeSeats, 0, 30)); ?><?php echo count($freeSeats)>30 ? ' ...' : ''; ?></span>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Notes</label>
              <input type="text" name="notes" class="form-control" placeholder="e.g., UPSC aspirant">
            </div>
            <div class="col-12">
              <div class="form-check">
                <input type="checkbox" name="deposit_paid" id="depositCheck" class="form-check-input" checked>
                <label for="depositCheck" class="form-check-label">Security Deposit Paid (₹500)</label>
              </div>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input type="checkbox" name="locker_paid" id="lockerCheck" class="form-check-input">
                <label for="lockerCheck" class="form-check-label">Locker Allotted (₹100 additional)</label>
              </div>
            </div>

            <!-- Monthly Fee at Registration -->
            <div class="col-12">
              <div style="background:var(--bg-page);border-radius:10px;padding:14px 16px;border:1.5px solid var(--border);">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                  <div class="form-check mb-0">
                    <input type="checkbox" name="monthly_paid" id="monthlyCheck" class="form-check-input"
                           onchange="toggleMonthlyAmount(); updateRenewalPreview()">
                    <label for="monthlyCheck" class="form-check-label fw-bold">Monthly Fee Collected at Joining</label>
                  </div>
                  <div id="monthlyAmountWrap" style="display:none;" class="d-flex align-items-center gap-2">
                    <span style="color:var(--text-muted);font-size:13px;">Amount ₹</span>
                    <input type="number" name="monthly_amount" id="monthlyAmountInput" class="form-control form-control-sm"
                           style="width:100px;" value="1200" min="1"
                           onchange="updateRenewalPreview()" onkeyup="updateRenewalPreview()">
                    <small style="color:var(--text-muted);">(edit for negotiated amount)</small>
                  </div>
                </div>
                <div id="renewalNote" style="display:none;margin-top:8px;font-size:12px;color:var(--success);">
                  <i class="fas fa-check-circle me-1"></i>
                  Renewal will be set to: <strong id="renewalNoteDate"></strong>
                </div>
                <div id="renewalNoteOff" style="margin-top:8px;font-size:12px;color:var(--text-muted);">
                  <i class="fas fa-info-circle me-1"></i>
                  If not collected now, collect monthly fee later from the Payments page — renewal date will stay as joining date until then.
                </div>
              </div>
            </div>
          </div>

          <div class="alert alert-info mt-3" style="font-size:12px;" id="feeInfo">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Fees:</strong> Registration ₹100 + Deposit ₹500 + Monthly ₹1200 — select seat type above to see full breakdown. From 2nd month only monthly fee applies.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Student</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleSeatField() {
  const isReserved = document.getElementById('typeReserved').checked;
  const field = document.getElementById('seatNumberField');
  const input = document.getElementById('seatNumberInput');
  const feeInfo = document.getElementById('feeInfo');
  field.style.display = isReserved ? '' : 'none';
  input.required = isReserved;
  feeInfo.innerHTML = isReserved
    ? '<i class="fas fa-info-circle me-2"></i><strong>Fees (Reserved):</strong> Registration ₹100 + Deposit ₹500 + Reservation ₹100 + Monthly ₹1300 = <strong>₹2000 first month</strong> (+ ₹100 locker if applicable). From 2nd month onwards: <strong>₹1300/month only</strong>. Deposit ₹500 refunded on leaving.'
    : '<i class="fas fa-info-circle me-2"></i><strong>Fees (Unreserved):</strong> Registration ₹100 + Deposit ₹500 + Monthly ₹1200 = <strong>₹1800 first month</strong>. From 2nd month onwards: <strong>₹1200/month only</strong>. Deposit ₹500 refunded on leaving.';
}

function toggleMonthlyAmount() {
  const checked = document.getElementById('monthlyCheck').checked;
  document.getElementById('monthlyAmountWrap').style.display = checked ? '' : 'none';
  document.getElementById('renewalNote').style.display    = checked ? '' : 'none';
  document.getElementById('renewalNoteOff').style.display = checked ? 'none' : '';
  updateRenewalPreview();
}

function updateRenewalPreview() {
  const joiningVal = document.getElementById('joiningDateInput').value;
  const monthlyChecked = document.getElementById('monthlyCheck').checked;
  const previewEl = document.getElementById('renewalPreview');
  const noteDateEl = document.getElementById('renewalNoteDate');

  if (!joiningVal) return;

  if (monthlyChecked) {
    const base = new Date(joiningVal);
    base.setMonth(base.getMonth() + 1);
    const formatted = base.toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' });
    previewEl.value = formatted;
    if (noteDateEl) noteDateEl.textContent = formatted;
  } else {
    previewEl.value = 'Same as joining date (fee not collected yet)';
  }
}

// Init on page load
document.addEventListener('DOMContentLoaded', function() { updateRenewalPreview(); });
</script>

<?php include '../includes/footer.php'; ?>
