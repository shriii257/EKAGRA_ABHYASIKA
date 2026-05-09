<?php
ob_start();
require_once '../includes/db.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: login.php'); exit;
}

$sid  = (int)$_SESSION['student_id'];
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$sid]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header('Location: login.php'); exit;
}

// Payment history
$payments = $pdo->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
$payments->execute([$sid]);
$payHistory = $payments->fetchAll();

// Total paid
$totalPaid = array_sum(array_column($payHistory, 'amount'));

// Days until expiry
$daysLeft = null;
if ($student['renewal_date']) {
    $daysLeft = ceil((strtotime($student['renewal_date']) - time()) / 86400);
}

// Status color
$statusColor = match($student['status']) {
    'active'  => 'var(--success)',
    'expired' => 'var(--danger)',
    'left'    => 'var(--text-muted)',
    default   => 'var(--text-muted)',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Dashboard – Ekagra Abhyasika</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="student-wrapper">

<!-- Top Bar -->
<div class="student-topbar">
  <a href="../index.php" class="brand">
    <span style="width:36px;height:36px;background:var(--accent);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-weight:800;color:var(--primary-dark);font-size:14px;">EA</span>
    Ekagra Abhyasika
  </a>
  <div style="display:flex;align-items:center;gap:16px;">
    <span style="color:rgba(255,255,255,0.7);font-size:13px;">
      <i class="fas fa-user me-1"></i>
      <?php echo htmlspecialchars($student['full_name']); ?>
    </span>
    <a href="logout.php" style="color:rgba(255,255,255,0.5);font-size:13px;text-decoration:none;display:flex;align-items:center;gap:6px;">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>

<div style="padding:28px 0;background:var(--bg-page);min-height:calc(100vh - 64px);">
  <div class="container">

    <!-- Expiry Alert -->
    <?php if($student['status'] === 'expired'): ?>
    <div class="alert alert-danger mb-4">
      <i class="fas fa-exclamation-triangle me-2"></i>
      <strong>Your membership has expired.</strong> Please contact the library admin to renew your subscription.
    </div>
    <?php elseif($daysLeft !== null && $daysLeft <= 7 && $student['status'] === 'active'): ?>
    <div class="alert alert-warning mb-4">
      <i class="fas fa-clock me-2"></i>
      <strong>Renewal Due!</strong> Your membership expires in <strong><?php echo max(0,$daysLeft); ?> day(s)</strong> on <?php echo date('d M Y', strtotime($student['renewal_date'])); ?>. Please renew soon.
    </div>
    <?php endif; ?>

    <!-- Profile Card -->
    <div class="student-profile-card mb-4">
      <div class="row align-items-center">
        <div class="col-md-7">
          <div style="font-family:var(--font-display);font-size:13px;color:rgba(255,255,255,0.5);letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;">Member Profile</div>
          <h2 style="font-family:var(--font-display);font-size:28px;font-weight:800;color:#fff;margin-bottom:12px;"><?php echo htmlspecialchars($student['full_name']); ?></h2>
          <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
            <span style="color:rgba(255,255,255,0.7);font-size:14px;display:flex;align-items:center;gap:6px;">
              <i class="fas fa-mobile-alt"></i><?php echo $student['mobile']; ?>
            </span>
            <span class="badge-status <?php echo $student['status']; ?>" style="font-size:12px;">
              <?php echo ucfirst($student['status']); ?>
            </span>
          </div>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
          <div style="font-family:var(--font-display);font-size:13px;color:rgba(255,255,255,0.5);margin-bottom:8px;">YOUR SEAT</div>
          <div class="student-seat-badge">
            <i class="fas fa-chair"></i>
            Seat <?php echo $student['seat_number']; ?>
          </div>
          <div style="margin-top:8px;">
            <span class="badge-status <?php echo $student['seat_type']; ?>" style="font-size:12px;">
              <?php echo ucfirst($student['seat_type']); ?> Seat
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Membership Info -->
      <div class="col-md-8">
        <div class="card-panel mb-4">
          <div class="card-panel-header">
            <span class="card-panel-title"><i class="fas fa-id-card me-2"></i>Membership Details</span>
          </div>
          <div class="card-panel-body">
            <div class="row g-3">
              <div class="col-sm-6">
                <div style="background:var(--bg-page);border-radius:var(--radius);padding:16px;border:1px solid var(--border);">
                  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Joining Date</div>
                  <div style="font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--primary);">
                    <?php echo date('d M Y', strtotime($student['joining_date'])); ?>
                  </div>
                </div>
              </div>
              <div class="col-sm-6">
                <div style="background:var(--bg-page);border-radius:var(--radius);padding:16px;border:1px solid <?php echo ($daysLeft !== null && $daysLeft <=7)?'var(--warning)':'var(--border)'; ?>;">
                  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Subscription Expires</div>
                  <div style="font-family:var(--font-display);font-size:18px;font-weight:700;color:<?php echo ($daysLeft!==null&&$daysLeft<=7)?'var(--danger)':'var(--primary)'; ?>;">
                    <?php echo $student['renewal_date'] ? date('d M Y', strtotime($student['renewal_date'])) : '—'; ?>
                  </div>
                  <?php if($daysLeft !== null): ?>
                  <div style="font-size:12px;color:<?php echo $daysLeft<=7?'var(--danger)':'var(--text-muted)'; ?>;margin-top:4px;">
                    <?php echo $daysLeft > 0 ? $daysLeft . ' days remaining' : 'Expired'; ?>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-sm-6">
                <div style="background:var(--bg-page);border-radius:var(--radius);padding:16px;border:1px solid var(--border);">
                  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Seat Type</div>
                  <div style="font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--primary);">
                    <?php echo ucfirst($student['seat_type']); ?>
                  </div>
                  <?php if($student['seat_type']==='reserved'): ?>
                  <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Fixed seat — exclusively yours</div>
                  <?php else: ?>
                  <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Flexible — any available seat</div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-sm-6">
                <div style="background:var(--bg-page);border-radius:var(--radius);padding:16px;border:1px solid var(--border);">
                  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Security Deposit</div>
                  <div style="font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--primary);">
                    ₹<?php echo number_format($student['deposit_amount'],0); ?>
                  </div>
                  <div style="font-size:12px;margin-top:4px;">
                    <?php if($student['deposit_paid']): ?>
                    <span style="color:var(--success);">✓ Paid — Refundable on leaving</span>
                    <?php else: ?>
                    <span style="color:var(--danger);">✗ Not paid yet</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="col-sm-6">
                <div style="background:var(--bg-page);border-radius:var(--radius);padding:16px;border:1px solid var(--border);">
                  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Monthly Fee</div>
                  <div style="font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--primary);">₹1,800</div>
                  <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Per month + ₹100 reservation if reserved</div>
                </div>
              </div>
              <div class="col-sm-6">
                <div style="background:var(--bg-page);border-radius:var(--radius);padding:16px;border:1px solid var(--border);">
                  <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Total Paid</div>
                  <div style="font-family:var(--font-display);font-size:18px;font-weight:700;color:var(--success);">₹<?php echo number_format($totalPaid,0); ?></div>
                  <div style="font-size:12px;color:var(--text-muted);margin-top:4px;"><?php echo count($payHistory); ?> transaction(s)</div>
                </div>
              </div>
            </div>

            <?php if($student['notes']): ?>
            <div style="background:rgba(13,43,110,0.06);border-left:4px solid var(--primary);border-radius:8px;padding:14px 16px;margin-top:16px;">
              <strong style="font-size:13px;color:var(--primary);">Notes:</strong>
              <span style="font-size:13px;color:var(--text-muted);"> <?php echo htmlspecialchars($student['notes']); ?></span>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Payment History -->
        <div class="card-panel">
          <div class="card-panel-header">
            <span class="card-panel-title"><i class="fas fa-history me-2"></i>Payment History</span>
          </div>
          <div class="card-panel-body p-0">
            <?php if(empty($payHistory)): ?>
            <div class="empty-state"><i class="fas fa-receipt"></i><p>No payment records yet.</p></div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table">
                <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Notes</th></tr></thead>
                <tbody>
                <?php foreach($payHistory as $p): ?>
                <tr>
                  <td style="font-size:13px;"><?php echo date('d M Y', strtotime($p['payment_date'])); ?></td>
                  <td><span style="font-size:12px;font-weight:600;text-transform:capitalize;"><?php echo str_replace('_',' ',$p['payment_type']); ?></span></td>
                  <td><strong style="color:var(--success);">₹<?php echo number_format($p['amount'],0); ?></strong></td>
                  <td style="font-size:12px;color:var(--text-muted);"><?php echo htmlspecialchars($p['notes'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Sidebar Info -->
      <div class="col-md-4">
        <!-- Library Info -->
        <div class="card-panel mb-4">
          <div class="card-panel-header">
            <span class="card-panel-title"><i class="fas fa-clock me-2"></i>Library Timings</span>
          </div>
          <div class="card-panel-body">
            <div style="display:flex;align-items:center;gap:12px;padding:12px;background:rgba(26,183,89,0.08);border-radius:10px;margin-bottom:12px;">
              <i class="fas fa-sun" style="color:#f59e0b;font-size:20px;"></i>
              <div>
                <div style="font-weight:700;font-size:14px;">Morning Opening</div>
                <div style="color:var(--text-muted);font-size:13px;">6:00 AM</div>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;padding:12px;background:rgba(13,43,110,0.06);border-radius:10px;margin-bottom:16px;">
              <i class="fas fa-moon" style="color:#818cf8;font-size:20px;"></i>
              <div>
                <div style="font-weight:700;font-size:14px;">Evening Closing</div>
                <div style="color:var(--text-muted);font-size:13px;">10:00 PM</div>
              </div>
            </div>
            <div style="background:rgba(240,165,0,0.08);border:1px solid rgba(240,165,0,0.2);border-radius:8px;padding:10px;text-align:center;font-size:13px;color:var(--warning);font-weight:700;">
              <i class="fas fa-calendar-check me-1"></i>Open 7 Days a Week
            </div>
          </div>
        </div>

        <!-- Location -->
        <div class="card-panel mb-4">
          <div class="card-panel-header">
            <span class="card-panel-title"><i class="fas fa-map-marker-alt me-2"></i>Location</span>
          </div>
          <div class="card-panel-body">
            <p style="font-size:14px;line-height:1.7;margin-bottom:12px;">
              City Center Complex,<br>3rd Floor, Office No 304,<br>Undri, Pune, Maharashtra, India
            </p>
            <a href="tel:+919999999999" class="btn btn-primary btn-sm w-100 mb-2">
              <i class="fas fa-phone me-2"></i>Call Library
            </a>
            <a href="https://wa.me/919999999999" target="_blank" class="btn btn-sm w-100" style="background:#25d366;color:#fff;">
              <i class="fab fa-whatsapp me-2"></i>WhatsApp
            </a>
          </div>
        </div>

        <!-- Rules -->
        <div class="card-panel">
          <div class="card-panel-header">
            <span class="card-panel-title"><i class="fas fa-gavel me-2"></i>Library Rules</span>
          </div>
          <div class="card-panel-body p-0">
            <?php
            $rules = [
              'Maintain complete silence',
              'Mobile on silent/vibrate mode',
              'Keep study area clean',
              'Carry ID card at all times',
              'Food & beverages not allowed inside',
              'Reserved seats are exclusively assigned',
              'Entry only during library hours',
              'Respect other students',
            ];
            foreach($rules as $i => $rule): ?>
            <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;font-size:13px;">
              <span style="width:22px;height:22px;background:var(--primary);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;flex-shrink:0;"><?php echo $i+1; ?></span>
              <?php echo $rule; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<footer class="site-footer">
  <div class="container">
    <p>© <?php echo date('Y'); ?> Ekagra Abhyasika | City Center Complex, Undri, Pune</p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>