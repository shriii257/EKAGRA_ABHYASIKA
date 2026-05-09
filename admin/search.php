<?php
ob_start();
require_once '../includes/db.php';
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

$q       = trim($_GET['q'] ?? '');
$results = [];

if ($q !== '') {
    $like   = "%$q%";
    $stmt   = $pdo->prepare(
        "SELECT s.*, se.seat_type as seat_cat
         FROM students s
         LEFT JOIN seats se ON se.seat_number = s.seat_number
         WHERE s.full_name LIKE ?
            OR s.mobile    LIKE ?
            OR s.seat_number LIKE ?
            OR s.aadhaar   LIKE ?
         ORDER BY s.full_name
         LIMIT 50"
    );
    $stmt->execute([$like, $like, $like, $like]);
    $results = $stmt->fetchAll();
}

$page_title = 'Search';
$base = '../';
include '../includes/header.php';
?>
<div class="admin-wrapper">
<?php include '_sidebar.php'; ?>
<div class="main-content">
  <div class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">Search Students</span>
    </div>
  </div>
  <div class="page-content">

    <div class="card-panel mb-4">
      <div class="card-panel-body">
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="q" class="form-control form-control-lg"
                 placeholder="Search by name, mobile, seat number, Aadhaar..."
                 value="<?php echo e($q); ?>" autofocus>
          <button type="submit" class="btn btn-primary px-4">
            <i class="fas fa-search me-1"></i>Search
          </button>
          <?php if ($q): ?>
          <a href="search.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <?php if ($q !== ''): ?>
    <div class="card-panel">
      <div class="card-panel-header">
        <span class="card-panel-title">
          <i class="fas fa-search me-2"></i>
          Results for "<?php echo e($q); ?>" — <?php echo count($results); ?> found
        </span>
      </div>
      <div class="card-panel-body p-0">
        <?php if (empty($results)): ?>
          <div class="empty-state">
            <i class="fas fa-search-minus"></i>
            <h5>No results found</h5>
            <p>Try searching with a different name, mobile number, or seat number.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>#</th><th>Name</th><th>Mobile</th><th>Seat</th>
                  <th>Type</th><th>Status</th><th>Renewal</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($results as $i => $s):
                $days = $s['renewal_date'] ? (strtotime($s['renewal_date']) - time()) / 86400 : null;
                $renewalStyle = ($days !== null && $days <= 7 && $s['status'] === 'active')
                    ? 'style="color:var(--danger);font-weight:700;"' : '';
              ?>
              <tr>
                <td style="color:var(--text-muted);"><?php echo $i + 1; ?></td>
                <td>
                  <div style="font-weight:600;"><?php echo e($s['full_name']); ?></div>
                  <?php if ($s['parent_name']): ?>
                  <div style="font-size:11px;color:var(--text-muted);"><?php echo e($s['parent_name']); ?></div>
                  <?php endif; ?>
                </td>
                <td><?php echo e($s['mobile']); ?></td>
                <td><span class="badge bg-primary"><?php echo $s['seat_number']; ?></span></td>
                <td><span class="badge-status <?php echo $s['seat_type']; ?>"><?php echo ucfirst($s['seat_type']); ?></span></td>
                <td><span class="badge-status <?php echo $s['status']; ?>"><?php echo ucfirst($s['status']); ?></span></td>
                <td <?php echo $renewalStyle; ?>><?php echo fdate($s['renewal_date']); ?></td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="students.php?view=<?php echo $s['id']; ?>" class="btn btn-outline-primary btn-sm" title="View">
                      <i class="fas fa-eye"></i>
                    </a>
                    <a href="payments.php?student_id=<?php echo $s['id']; ?>" class="btn btn-success btn-sm" title="Payments">
                      <i class="fas fa-rupee-sign"></i>
                    </a>
                    <a href="seats.php" class="btn btn-info btn-sm" title="View Seat">
                      <i class="fas fa-chair"></i>
                    </a>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <div class="empty-state">
      <i class="fas fa-search" style="opacity:.15;"></i>
      <h5>Enter a search term above</h5>
      <p>Search by student name, mobile number, seat number, or Aadhaar.</p>
    </div>
    <?php endif; ?>

  </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>