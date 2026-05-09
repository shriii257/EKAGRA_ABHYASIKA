<?php
ob_start();
require_once 'includes/db.php';
// No login required — public page

// Get all seats with occupancy info (no student names/mobiles for privacy)
$seats = $pdo->query("
    SELECT seat_number, seat_type, status
    FROM seats
    ORDER BY seat_number
")->fetchAll();

// Stats
$totalReserved   = 76;
$totalUnreserved = 32;
$resOccupied = $unresOccupied = 0;
foreach ($seats as $s) {
    if ($s['seat_type'] === 'reserved'   && $s['status'] === 'occupied') $resOccupied++;
    if ($s['seat_type'] === 'unreserved' && $s['status'] === 'occupied') $unresOccupied++;
}
$resAvail   = $totalReserved   - $resOccupied;
$unresAvail = $totalUnreserved - $unresOccupied;
$totalAvail = $resAvail + $unresAvail;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Seat Availability — Ekagra Abhyasika</title>
  <meta name="description" content="Check real-time seat availability at Ekagra Abhyasika Study Library, Undri Pune.">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* ── Page shell ── */
    body { background: var(--bg-page, #f4f6fb); font-family: 'Inter', sans-serif; }

    .pub-seat-page {
      min-height: 100vh;
      padding-bottom: 60px;
    }

    /* ── Nav (same as index.php) ── */
    .public-nav { /* already in style.css */ }

    /* ── Hero strip ── */
    .seat-hero {
      background: linear-gradient(135deg, #0d2b6e 0%, #1a4db5 100%);
      color: #fff;
      padding: 60px 0 40px;
      text-align: center;
    }
    .seat-hero .section-tag {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(255,255,255,0.12); border-radius: 50px;
      padding: 6px 18px; font-size: 12px; font-weight: 700;
      letter-spacing: 1px; text-transform: uppercase;
      color: #a8c4ff; margin-bottom: 16px;
    }
    .seat-hero h1 { font-family: 'Rajdhani', sans-serif; font-size: clamp(28px,5vw,46px); font-weight: 800; margin: 0 0 10px; }
    .seat-hero p  { font-size: 15px; opacity: .8; margin: 0; }

    /* live badge */
    .live-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(26,183,89,0.18); border: 1px solid rgba(26,183,89,0.35);
      border-radius: 50px; padding: 4px 14px;
      font-size: 12px; font-weight: 700; color: #4ade80;
      margin-bottom: 20px;
    }
    .live-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: #4ade80;
      animation: pulse-live 1.4s infinite;
    }
    @keyframes pulse-live {
      0%,100% { opacity:1; transform:scale(1); }
      50%      { opacity:.4; transform:scale(1.4); }
    }

    /* ── Stat cards ── */
    .pub-stat-card {
      background: #fff;
      border-radius: 14px;
      padding: 22px 18px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      display: flex; align-items: center; gap: 16px;
    }
    .pub-stat-icon {
      width: 52px; height: 52px; border-radius: 12px;
      display: flex; align-items:center; justify-content:center;
      font-size: 22px; flex-shrink: 0;
    }
    .pub-stat-icon.blue   { background: rgba(13,43,110,.10); color: #0d2b6e; }
    .pub-stat-icon.green  { background: rgba(26,183,89,.12); color: #1ab759; }
    .pub-stat-icon.gray   { background: rgba(100,100,100,.10); color: #616161; }
    .pub-stat-icon.yellow { background: rgba(240,165,0,.12); color: #e09900; }
    .pub-stat-val  { font-size: 28px; font-weight: 800; font-family: 'Rajdhani',sans-serif; line-height: 1; }
    .pub-stat-lbl  { font-size: 12px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing:.5px; margin-top: 3px; }

    /* ── Seat map card ── */
    .seat-map-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 16px rgba(0,0,0,.07);
      overflow: hidden;
    }
    .seat-map-header {
      padding: 18px 24px;
      border-bottom: 1px solid #f0f0f0;
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    }
    .seat-map-title { font-weight: 700; font-size: 15px; display:flex; align-items:center; gap:8px; }
    .seat-map-body  { padding: 24px; }

    /* legend — reuse your existing classes */
    .seat-legend { display:flex; flex-wrap:wrap; gap:14px; margin-bottom:20px; }
    .legend-item { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; }
    .legend-dot  { width:16px; height:16px; border-radius:4px; border:2px solid transparent; }
    .legend-dot.green  { background:#e8f5e9; border-color:#66bb6a; }
    .legend-dot.red    { background:#ffebee; border-color:#ef5350; }
    .legend-dot.gray   { background:#f5f5f5; border-color:#bdbdbd; }
    .legend-dot.yellow { background:#fff8e1; border-color:#ffca28; }

    /* seat grid — same as admin */
    .seat-grid-container { overflow-x:auto; }
    .seat-grid {
      display:grid;
      grid-template-columns: repeat(9,1fr);
      gap:8px; min-width:520px; padding:4px;
    }
    .seat-item {
      aspect-ratio:1; border-radius:8px;
      display:flex; flex-direction:column;
      align-items:center; justify-content:center;
      font-size:11px; font-weight:700;
      cursor:pointer;
      transition:transform .15s, box-shadow .15s;
      border:2px solid transparent;
      min-height:48px; user-select:none;
    }
    .seat-item:hover { transform:scale(1.08); box-shadow:0 4px 12px rgba(0,0,0,.2); z-index:10; position:relative; }
    .seat-item i { font-size:14px; margin-bottom:2px; }

    .seat-item.reserved-available   { background:#e8f5e9; border-color:#66bb6a; color:#2e7d32; }
    .seat-item.reserved-occupied    { background:#ffebee; border-color:#ef5350; color:#c62828; cursor:not-allowed; }
    .seat-item.unreserved-available { background:#f5f5f5; border-color:#bdbdbd; color:#616161; }
    .seat-item.unreserved-occupied  { background:#fff8e1; border-color:#ffca28; color:#e65100; cursor:not-allowed; }

    .seat-row-label {
      grid-column:1/-1; font-size:11px; font-weight:700;
      color:#aaa; text-transform:uppercase; letter-spacing:1px;
      padding:6px 0 2px; border-top:1px solid #f0f0f0; margin-top:4px;
    }

    /* ── Request modal niceness ── */
    .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.18); }
    .modal-header  { border-bottom: 1px solid #f0f0f0; padding: 20px 24px; }
    .modal-body    { padding: 24px; }
    .modal-footer  { border-top: 1px solid #f0f0f0; padding: 16px 24px; }
    .seat-badge    { display:inline-block; background:#0d2b6e; color:#fff; border-radius:8px; padding:4px 12px; font-weight:800; font-family:'Rajdhani',sans-serif; font-size:22px; }
    .whatsapp-btn  {
      background: #25d366; color: #fff; border: none;
      border-radius: 10px; padding: 12px 24px;
      font-weight: 700; font-size: 15px;
      display:inline-flex; align-items:center; gap:8px;
      text-decoration: none; transition: background .2s;
      width: 100%; justify-content: center;
    }
    .whatsapp-btn:hover { background: #1ebe5a; color:#fff; }
    .call-btn {
      background: #0d2b6e; color: #fff; border: none;
      border-radius: 10px; padding: 12px 24px;
      font-weight: 700; font-size: 15px;
      display:inline-flex; align-items:center; gap:8px;
      text-decoration: none; transition: background .2s;
      width: 100%; justify-content: center; margin-top: 10px;
    }
    .call-btn:hover { background: #0a2060; color:#fff; }

    /* ── Occupied tooltip ── */
    .occupied-note {
      background: #fff8e1; border: 1px solid #ffca28;
      border-radius: 10px; padding: 12px 16px;
      font-size: 13px; color: #7a5c00; margin-top: 12px;
      display: none;
    }

    /* ── Back link ── */
    .back-link { font-size:13px; color:#6b7fa3; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
    .back-link:hover { color:#0d2b6e; }

    @media(max-width:600px) {
      .seat-grid { gap:5px; }
      .seat-item { min-height:38px; font-size:10px; }
      .seat-item i { font-size:12px; }
      .seat-map-body { padding:16px; }
    }
  </style>
</head>
<body class="pub-seat-page">

<!-- ── Nav ── -->
<div class="announcement-bar">
  <div class="announcement-track">
    📢 Admissions Open at Ekagra Abhyasika • Reserved &amp; Unreserved Seats Available • Premium AC Study Library in Undri Pune • Open Daily 6:00 AM – 10:00 PM
  </div>
</div>
<style>
.announcement-bar{width:100%;overflow:hidden;background:linear-gradient(90deg,#0d2b6e,#081631);color:#fff;padding:12px 0;position:sticky;top:0;z-index:99999;border-bottom:1px solid rgba(255,255,255,0.08);box-shadow:0 4px 14px rgba(0,0,0,0.18);}
.announcement-track{white-space:nowrap;display:inline-block;padding-left:100%;font-size:14px;font-weight:700;letter-spacing:0.5px;animation:scrollAnnouncement 22s linear infinite;}
@keyframes scrollAnnouncement{0%{transform:translateX(0);}100%{transform:translateX(-100%);}}
</style>

<nav class="public-nav">
  <a href="index.php" class="public-nav-brand">
    <div class="brand-box">EA</div>
    <h1>Ekagra Abhyasika<span>Study Library · Undri, Pune</span></h1>
  </a>
  <div class="public-nav-links">
    <a href="index.php#about">About</a>
    <a href="index.php#facilities">Facilities</a>
    <a href="index.php#fees">Fees</a>
    <a href="index.php#contact">Contact</a>
    <a href="student/login.php" class="btn-login"><i class="fas fa-sign-in-alt me-1"></i>Student Login</a>
  </div>
</nav>

<!-- ── Hero strip ── -->
<div class="seat-hero">
  <div class="container">
    <div class="live-badge"><span class="live-dot"></span> Live Availability</div>
    <h1><i class="fas fa-chair me-2"></i>Seat Availability</h1>
    <p>Click any available seat to request it from the admin</p>
  </div>
</div>

<!-- ── Main content ── -->
<div class="container py-4">

  <!-- Back link -->
  <a href="index.php" class="back-link mb-3 d-inline-flex">
    <i class="fas fa-arrow-left"></i> Back to Home
  </a>

  <!-- Stats row -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="pub-stat-card">
        <div class="pub-stat-icon blue"><i class="fas fa-chair"></i></div>
        <div>
          <div class="pub-stat-val"><?= $totalAvail ?></div>
          <div class="pub-stat-lbl">Total Available</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="pub-stat-card">
        <div class="pub-stat-icon green"><i class="fas fa-lock-open"></i></div>
        <div>
          <div class="pub-stat-val"><?= $resAvail ?></div>
          <div class="pub-stat-lbl">Reserved Free</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="pub-stat-card">
        <div class="pub-stat-icon gray"><i class="fas fa-door-open"></i></div>
        <div>
          <div class="pub-stat-val"><?= $unresAvail ?></div>
          <div class="pub-stat-lbl">Unreserved Free</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="pub-stat-card">
        <div class="pub-stat-icon yellow"><i class="fas fa-users"></i></div>
        <div>
          <div class="pub-stat-val"><?= 108 - $totalAvail ?></div>
          <div class="pub-stat-lbl">Currently Occupied</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Seat map card -->
  <div class="seat-map-card">
    <div class="seat-map-header">
      <div class="seat-map-title">
        <i class="fas fa-th" style="color:#0d2b6e;"></i>
        Seat Map — Click an available seat to request it
      </div>
      <span style="font-size:12px;color:#aaa;">Seats 1–76: Reserved &nbsp;|&nbsp; Seats 77–108: Unreserved</span>
    </div>
    <div class="seat-map-body">

      <!-- Legend -->
      <div class="seat-legend">
        <div class="legend-item"><div class="legend-dot green"></div> Reserved – Available</div>
        <div class="legend-item"><div class="legend-dot red"></div> Reserved – Occupied</div>
        <div class="legend-item"><div class="legend-dot gray"></div> Unreserved – Available</div>
        <div class="legend-item"><div class="legend-dot yellow"></div> Unreserved – Occupied</div>
      </div>

      <!-- Grid -->
      <div class="seat-grid-container">
        <div class="seat-grid">
          <?php
          $row = 0;
          foreach ($seats as $i => $seat):
            if ($i % 9 === 0):
              $row++;
              if ($i > 0) echo '<div class="seat-row-label"></div>';
              $end = min($seat['seat_number'] + 8, 108);
              echo "<div class=\"seat-row-label\">Row {$row} &nbsp; (Seats {$seat['seat_number']}–{$end})</div>";
            endif;

            $isOccupied = $seat['status'] === 'occupied';
            if ($seat['seat_type'] === 'reserved') {
              $cls  = $isOccupied ? 'reserved-occupied'    : 'reserved-available';
              $icon = $isOccupied ? 'fa-user'              : 'fa-chair';
            } else {
              $cls  = $isOccupied ? 'unreserved-occupied'  : 'unreserved-available';
              $icon = $isOccupied ? 'fa-user'              : 'fa-chair';
            }

            $dataAttr = htmlspecialchars(json_encode([
              'seat_number' => $seat['seat_number'],
              'seat_type'   => ucfirst($seat['seat_type']),
              'status'      => $seat['status'],
            ]));
          ?>
          <div class="seat-item <?= $cls ?>"
               data-seat="<?= $dataAttr ?>"
               onclick="openSeatModal(this)"
               title="Seat <?= $seat['seat_number'] ?> — <?= ucfirst($seat['seat_type']) ?> — <?= ucfirst($seat['status']) ?>">
            <i class="fas <?= $icon ?>"></i>
            <?= $seat['seat_number'] ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div><!-- /seat-map-card -->

</div><!-- /container -->

<!-- ── Request Seat Modal ── -->
<div class="modal fade" id="requestModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-chair me-2" style="color:#0d2b6e;"></i>Seat Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Available state -->
        <div id="modalAvailable">
          <p style="margin-bottom:12px;font-size:14px;color:#555;">You selected:</p>
          <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
            <span class="seat-badge" id="modalSeatNo">–</span>
            <div>
              <div style="font-weight:700;font-size:15px;" id="modalSeatType">–</div>
              <div style="font-size:12px;color:#1ab759;font-weight:600;"><i class="fas fa-circle" style="font-size:8px;"></i> Available</div>
            </div>
          </div>
          <p style="font-size:13px;color:#666;margin-bottom:20px;">
            To reserve this seat, contact the admin via WhatsApp or call. Mention your name and this seat number.
          </p>
          <a id="waLink" href="#" target="_blank" class="whatsapp-btn">
            <i class="fab fa-whatsapp fa-lg"></i> Request via WhatsApp
          </a>
          <a href="tel:+919999999999" class="call-btn" id="callLink">
            <i class="fas fa-phone"></i> Call Admin Directly
          </a>
        </div>
        <!-- Occupied state -->
        <div id="modalOccupied" style="display:none;">
          <div style="text-align:center;padding:10px 0;">
            <i class="fas fa-user-times" style="font-size:40px;color:#ef5350;margin-bottom:14px;"></i>
            <div style="font-weight:700;font-size:18px;margin-bottom:6px;">Seat <span id="modalOccSeatNo"></span> is Occupied</div>
            <p style="font-size:13px;color:#888;margin-bottom:20px;">This seat is currently taken. Please choose another available seat from the map.</p>
            <a id="waAnyLink" href="#" target="_blank" class="whatsapp-btn">
              <i class="fab fa-whatsapp fa-lg"></i> Ask Admin for Any Available Seat
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // ── CONFIG: update phone number here ──
  const ADMIN_PHONE = '919999999999'; // 91 + 10-digit number, no +

  function openSeatModal(el) {
    const data = JSON.parse(el.dataset.seat);
    const modal = new bootstrap.Modal(document.getElementById('requestModal'));

    if (data.status === 'available') {
      document.getElementById('modalAvailable').style.display = '';
      document.getElementById('modalOccupied').style.display  = 'none';
      document.getElementById('modalSeatNo').textContent   = 'Seat ' + data.seat_number;
      document.getElementById('modalSeatType').textContent  = data.seat_type + ' Seat';

      const msg = encodeURIComponent(
        `Hello! I am interested in Seat No. ${data.seat_number} (${data.seat_type}) at Ekagra Abhyasika. Please let me know how to proceed with the admission.`
      );
      document.getElementById('waLink').href   = `https://wa.me/${ADMIN_PHONE}?text=${msg}`;
      document.getElementById('callLink').href  = `tel:+${ADMIN_PHONE}`;
    } else {
      document.getElementById('modalAvailable').style.display = 'none';
      document.getElementById('modalOccupied').style.display  = '';
      document.getElementById('modalOccSeatNo').textContent   = data.seat_number;

      const msg = encodeURIComponent(
        `Hello! I am looking for an available seat at Ekagra Abhyasika. Can you help me with admission?`
      );
      document.getElementById('waAnyLink').href = `https://wa.me/${ADMIN_PHONE}?text=${msg}`;
    }

    modal.show();
  }
</script>

</body>
</html>
