<?php
ob_start();
require_once '../includes/db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$msg = $err = '';

// Free a seat
if (isset($_GET['free'])) {
    $seatNo = (int)$_GET['free'];
    $seat = $pdo->prepare("SELECT * FROM seats WHERE seat_number=?");
    $seat->execute([$seatNo]);
    $s = $seat->fetch();
    if ($s && $s['status']==='occupied') {
        $pdo->prepare("UPDATE seats SET status='available', student_id=NULL WHERE seat_number=?")->execute([$seatNo]);
        if ($s['student_id']) {
            $pdo->prepare("UPDATE students SET status='left' WHERE id=?")->execute([$s['student_id']]);
        }
        logActivity($pdo,'Seat Freed',$_SESSION['admin_user'],"Seat $seatNo freed");
        $msg = "Seat $seatNo marked as available.";
    }
}

// Get all seats with student info
$seats = $pdo->query("
    SELECT s.*, st.full_name as student_name, st.mobile, st.renewal_date, st.status as student_status, st.id as sid
    FROM seats s
    LEFT JOIN students st ON s.student_id = st.id
    ORDER BY s.seat_number
")->fetchAll();

// Stats
$totalReserved   = 76;
$totalUnreserved = 32;
$resOccupied     = 0; $unresOccupied = 0;
foreach($seats as $s) {
    if ($s['seat_type']==='reserved' && $s['status']==='occupied') $resOccupied++;
    if ($s['seat_type']==='unreserved' && $s['status']==='occupied') $unresOccupied++;
}

$page_title = 'Seat Management';
$base = '../';
include '../includes/header.php';
?>
<div class="admin-wrapper">
<?php include '_sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">Seat Management</span>
    </div>
  </div>

  <div class="page-content">
    <?php if($msg): ?><div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($msg); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-lock"></i></div>
          <div class="stat-info"><div class="stat-value"><?php echo $resOccupied; ?>/<?php echo $totalReserved; ?></div><div class="stat-label">Reserved Occupied</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-check"></i></div>
          <div class="stat-info"><div class="stat-value"><?php echo $totalReserved - $resOccupied; ?></div><div class="stat-label">Reserved Free</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon yellow"><i class="fas fa-door-open"></i></div>
          <div class="stat-info"><div class="stat-value"><?php echo $unresOccupied; ?>/<?php echo $totalUnreserved; ?></div><div class="stat-label">Unreserved Occupied</div></div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon info"><i class="fas fa-chair"></i></div>
          <div class="stat-info"><div class="stat-value"><?php echo 108 - $resOccupied - $unresOccupied; ?></div><div class="stat-label">Total Available</div></div>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="seat-legend mb-3">
      <div class="legend-item"><div class="legend-dot green"></div>Reserved – Available</div>
      <div class="legend-item"><div class="legend-dot red"></div>Reserved – Occupied</div>
      <div class="legend-item"><div class="legend-dot gray"></div>Unreserved – Free</div>
      <div class="legend-item"><div class="legend-dot yellow"></div>Unreserved – Occupied</div>
    </div>

    <!-- Seat Grid -->
    <div class="card-panel mb-4">
      <div class="card-panel-header">
        <span class="card-panel-title"><i class="fas fa-th me-2"></i>Visual Seat Map — Click a seat for details</span>
      </div>
      <div class="card-panel-body">
        <div class="seat-grid-container">
          <div class="seat-grid">
            <?php
            $row = 0;
            foreach($seats as $i => $seat):
              // Row label every 9 seats
              if ($i % 9 === 0):
                $row++;
                if ($i > 0) echo '<div class="seat-row-label"></div>';
                echo '<div class="seat-row-label">Row ' . $row . ' &nbsp; (Seats ' . ($seat['seat_number']) . '-' . min($seat['seat_number']+8,108) . ')</div>';
              endif;

              // Determine class
              if ($seat['seat_type']==='reserved') {
                  $cls = ($seat['status']==='occupied') ? 'reserved-occupied' : 'reserved-available';
              } else {
                  $cls = ($seat['status']==='occupied') ? 'unreserved-occupied' : 'unreserved-available';
              }
              $icon = ($seat['status']==='occupied') ? 'fa-user' : 'fa-chair';

              $seatData = json_encode([
                'seat_number'  => $seat['seat_number'],
                'seat_type'    => ucfirst($seat['seat_type']),
                'status'       => ucfirst($seat['status']),
                'student_name' => $seat['student_name'] ?? 'Vacant',
                'mobile'       => $seat['mobile'] ?? '',
                'renewal_date' => $seat['renewal_date'] ? date('d M Y', strtotime($seat['renewal_date'])) : '-',
                'student_id'   => $seat['sid'] ?? '',
              ]);
              $encoded = urlencode($seatData);

              $tooltip = "Seat {$seat['seat_number']}";
              if ($seat['student_name']) $tooltip .= ": {$seat['student_name']}";
            ?>
            <div class="seat-item <?php echo $cls; ?>"
                 onclick="openSeatModal('<?php echo htmlspecialchars($encoded); ?>')"
                 data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($tooltip); ?>">
              <i class="fas <?php echo $icon; ?>"></i>
              <?php echo $seat['seat_number']; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Seat Table -->
    <div class="card-panel">
      <div class="card-panel-header">
        <span class="card-panel-title"><i class="fas fa-table me-2"></i>Seat Details</span>
        <input type="text" data-search-table="seatTable" class="form-control form-control-sm" placeholder="Search seats..." style="width:200px;">
      </div>
      <div class="card-panel-body p-0">
        <div class="table-responsive">
          <table class="table" id="seatTable">
            <thead><tr><th>Seat</th><th>Type</th><th>Status</th><th>Student</th><th>Mobile</th><th>Renewal</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach($seats as $s): ?>
            <tr>
              <td><span class="badge bg-primary"><?php echo $s['seat_number']; ?></span></td>
              <td><span class="badge-status <?php echo $s['seat_type']; ?>"><?php echo ucfirst($s['seat_type']); ?></span></td>
              <td>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:700;color:<?php echo $s['status']==='occupied'?'var(--danger)':'var(--success)'; ?>">
                  <i class="fas fa-circle" style="font-size:8px;"></i>
                  <?php echo ucfirst($s['status']); ?>
                </span>
              </td>
              <td><?php echo $s['student_name'] ? htmlspecialchars($s['student_name']) : '<span style="color:var(--text-muted);">–</span>'; ?></td>
              <td style="font-size:13px;"><?php echo htmlspecialchars($s['mobile'] ?? '–'); ?></td>
              <td style="font-size:13px;"><?php echo $s['renewal_date'] ? date('d M Y', strtotime($s['renewal_date'])) : '–'; ?></td>
              <td>
                <?php if($s['status']==='occupied' && $s['sid']): ?>
                <a href="students.php?view=<?php echo $s['sid']; ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye"></i></a>
                <a href="?free=<?php echo $s['seat_number']; ?>" class="btn btn-danger btn-sm"
                   data-confirm="Free seat <?php echo $s['seat_number']; ?>?"><i class="fas fa-unlock"></i></a>
                <?php else: ?>
                <a href="students.php?add=1&seat=<?php echo $s['seat_number']; ?>" class="btn btn-success btn-sm"><i class="fas fa-user-plus"></i></a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Seat Detail Modal -->
<div class="modal fade" id="seatDetailModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-chair me-2"></i>Seat <span id="modalSeatNumber"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-sm">
          <tr><th>Seat Number</th><td><strong id="modalSeatNumber2"></strong></td></tr>
          <tr><th>Type</th><td id="modalSeatType"></td></tr>
          <tr><th>Status</th><td id="modalSeatStatus"></td></tr>
          <tr><th>Student</th><td><strong id="modalStudentName"></strong></td></tr>
          <tr><th>Mobile</th><td id="modalStudentMobile"></td></tr>
          <tr><th>Renewal Date</th><td id="modalRenewalDate"></td></tr>
        </table>
      </div>
      <div class="modal-footer" id="modalSeatActions"></div>
    </div>
  </div>
</div>

<?php
$extra_js = "<script>
function openSeatModal(encoded) {
  const seatData = JSON.parse(decodeURIComponent(encoded));
  document.getElementById('modalSeatNumber').textContent = seatData.seat_number;
  document.getElementById('modalSeatNumber2').textContent = seatData.seat_number;
  document.getElementById('modalSeatType').textContent = seatData.seat_type;
  document.getElementById('modalSeatStatus').textContent = seatData.status;
  document.getElementById('modalStudentName').textContent = seatData.student_name || 'Vacant';
  document.getElementById('modalStudentMobile').textContent = seatData.mobile || '–';
  document.getElementById('modalRenewalDate').textContent = seatData.renewal_date || '–';
  const a = document.getElementById('modalSeatActions');
  a.innerHTML = '';
  if (seatData.status === 'Occupied') {
    a.innerHTML = '<a href=\"students.php?view='+seatData.student_id+'\" class=\"btn btn-primary btn-sm\"><i class=\"fas fa-eye me-1\"></i>View Student</a>' +
      '<a href=\"seats.php?free='+seatData.seat_number+'\" class=\"btn btn-danger btn-sm\" onclick=\"return confirm(\\\"Free seat '+seatData.seat_number+'?\\\")\"><i class=\"fas fa-unlock me-1\"></i>Free Seat</a>';
  } else {
    a.innerHTML = '<a href=\"students.php?add=1&seat='+seatData.seat_number+'\" class=\"btn btn-success btn-sm\"><i class=\"fas fa-user-plus me-1\"></i>Assign Student</a>';
  }
  new bootstrap.Modal(document.getElementById('seatDetailModal')).show();
}
</script>";
include '../includes/footer.php';
?>