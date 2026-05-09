<?php
ob_start();
require_once 'includes/db.php';

// Get live seat stats for homepage
$totalSeats = 108;
$occupied   = $pdo->query("SELECT COUNT(*) FROM seats WHERE status='occupied'")->fetchColumn();
$available  = $totalSeats - $occupied;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ekagra Abhyasika — Premium Study Library, Undri Pune</title>
  <meta name="description" content="Ekagra Abhyasika — Premium private study library at Undri, Pune. AC study hall, individual cabins, 108 seats. Open 6AM–10PM, 7 days a week.">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <!-- Running Announcement Bar -->
<div class="announcement-bar">
  <div class="announcement-track">
    📢 Admissions Open at Ekagra Abhyasika • Reserved & Unreserved Seats Available • Premium AC Study Library in Undri Pune • Open Daily 6:00 AM – 10:00 PM
  </div>
</div>

<style>
.announcement-bar{
    width:100%;
    overflow:hidden;
    background:linear-gradient(90deg,#0d2b6e,#081631);
    color:#fff;
    padding:12px 0;
    position:sticky;
    top:0;
    z-index:99999;
    border-bottom:1px solid rgba(255,255,255,0.08);
    box-shadow:0 4px 14px rgba(0,0,0,0.18);
}

.announcement-track{
    white-space:nowrap;
    display:inline-block;
    padding-left:100%;
    font-size:14px;
    font-weight:700;
    letter-spacing:0.5px;
    animation:scrollAnnouncement 22s linear infinite;
}

@keyframes scrollAnnouncement{
    0%{
        transform:translateX(0);
    }
    100%{
        transform:translateX(-100%);
    }
}
</style>

<!-- Navigation -->
<nav class="public-nav">
  <a href="index.php" class="public-nav-brand">
    <div class="brand-box">EA</div>
    <h1>Ekagra Abhyasika<span>Study Library · Undri, Pune</span></h1>
  </a>
  <div class="public-nav-links">
    <a href="#about">About</a>
    <a href="#facilities">Facilities</a>
    <a href="#fees">Fees</a>
    <a href="#contact">Contact</a>
    <a href="student/login.php" class="btn-login"><i class="fas fa-sign-in-alt me-1"></i>Student Login</a>
  </div>
</nav>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="hero" id="home">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7 hero-content">
        <div class="hero-badge">
          <i class="fas fa-star"></i>Premium Study Environment
        </div>
        <h1>Your Path to<br><span>Success</span> Starts Here</h1>
        <p>A focused, peaceful study environment designed for serious aspirants. Individual cabins, AC hall, and world-class facilities — all under one roof in the heart of Undri, Pune.</p>
        <div class="hero-cta">
          <a href="#fees" class="btn-hero-primary">
            <i class="fas fa-chair"></i>Check Seat Plans
          </a>
          <a href="#contact" class="btn-hero-outline">
            <i class="fas fa-phone"></i>Contact Us
          </a>
        </div>
        <div class="hero-stats">
          <div class="hero-stat-item">
            <div class="hero-stat-value">108</div>
            <div class="hero-stat-label">Total Seats</div>
          </div>
          <div class="hero-stat-item">
            <div class="hero-stat-value"><?php echo $available; ?></div>
            <div class="hero-stat-label">Available Now</div>
          </div>
          <div class="hero-stat-item">
            <div class="hero-stat-value">16</div>
            <div class="hero-stat-label">Hours Open</div>
          </div>
          <div class="hero-stat-item">
            <div class="hero-stat-value">7</div>
            <div class="hero-stat-label">Days a Week</div>
          </div>
        </div>
      </div>

      <!-- Live seat availability card -->
      <div class="col-lg-5 d-none d-lg-block">
        <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:20px;padding:32px;backdrop-filter:blur(12px);">
          <div class="text-center mb-4">
            <div style="font-family:var(--font-display);font-size:14px;color:var(--accent);letter-spacing:2px;text-transform:uppercase;margin-bottom:8px;">Live Availability</div>
            <div style="font-family:var(--font-display);font-size:52px;font-weight:800;color:#fff;line-height:1;">
              <?php echo $available; ?><span style="font-size:22px;color:rgba(255,255,255,0.5);">/108</span>
            </div>
            <div style="color:rgba(255,255,255,0.5);font-size:13px;margin-top:4px;">Seats Available Today</div>
          </div>
          <div style="background:rgba(255,255,255,0.06);border-radius:10px;padding:14px;margin-bottom:10px;">
            <div class="d-flex justify-content-between align-items-center">
              <span style="color:rgba(255,255,255,0.7);font-size:13px;">
                <i class="fas fa-lock me-2" style="color:var(--accent);"></i>Reserved Seats (1–76)
              </span>
              <span style="color:var(--accent);font-weight:700;font-size:15px;">76</span>
            </div>
          </div>
          <div style="background:rgba(255,255,255,0.06);border-radius:10px;padding:14px;margin-bottom:20px;">
            <div class="d-flex justify-content-between align-items-center">
              <span style="color:rgba(255,255,255,0.7);font-size:13px;">
                <i class="fas fa-door-open me-2" style="color:#66bb6a;"></i>Unreserved Seats (77–108)
              </span>
              <span style="color:#66bb6a;font-weight:700;font-size:15px;">32</span>
            </div>
          </div>
          <div style="background:rgba(26,183,89,0.15);border:1px solid rgba(26,183,89,0.3);border-radius:10px;padding:14px;text-align:center;">
            <div style="color:#66bb6a;font-size:13px;font-weight:700;">
              <i class="fas fa-clock me-2"></i>Open Today: 6:00 AM – 10:00 PM
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     ABOUT SECTION
     ============================================================ -->
<section id="about" style="background:#fff;">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="section-tag"><i class="fas fa-book-open"></i> About Us</div>
        <h2 class="section-title">Ekagra Abhyasika —<br>Where Focus Meets Ambition</h2>
        <p class="section-sub">Designed for competitive exam aspirants, students, and professionals who need a dedicated, distraction-free environment to achieve their goals.</p>
        <div class="row g-3">
          <?php
          $highlights = [
            ['fas fa-map-marker-alt', 'Prime Location',    'City Center Complex, 3rd Floor, Office 304, Undri, Pune'],
            ['fas fa-calendar-check', 'Open All Week',     '365 days a year — including holidays & weekends'],
            ['fas fa-bolt',           '24hr Power Backup', 'Uninterrupted power supply — never lose study time'],
            ['fas fa-shield-alt',     'CCTV Security',     'Round-the-clock CCTV for your safety & peace of mind'],
          ];
          foreach ($highlights as $h): ?>
          <div class="col-md-6">
            <div style="display:flex;align-items:flex-start;gap:14px;padding:16px;background:var(--bg-page);border-radius:var(--radius);border:1px solid var(--border);">
              <div style="width:40px;height:40px;background:rgba(13,43,110,0.08);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;color:var(--primary);flex-shrink:0;">
                <i class="<?php echo $h[0]; ?>"></i>
              </div>
              <div>
                <div style="font-weight:700;font-size:14px;color:var(--primary);margin-bottom:2px;"><?php echo $h[1]; ?></div>
                <div style="font-size:12px;color:var(--text-muted);line-height:1.5;"><?php echo $h[2]; ?></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="col-lg-6">
        <div style="background:linear-gradient(135deg,var(--primary-dark),var(--primary));border-radius:var(--radius-lg);padding:40px;color:#fff;position:relative;overflow:hidden;">
          <div style="position:absolute;right:-40px;bottom:-40px;width:200px;height:200px;background:rgba(255,255,255,0.04);border-radius:50%;"></div>
          <h3 style="font-family:var(--font-display);font-size:22px;font-weight:800;color:var(--accent);margin-bottom:24px;">
            <i class="fas fa-clock me-2"></i>Library Timings
          </h3>
          <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;padding:16px;background:rgba(255,255,255,0.06);border-radius:10px;">
            <i class="fas fa-sun" style="font-size:24px;color:#ffd700;flex-shrink:0;"></i>
            <div><div style="font-weight:700;">Morning Opening</div><div style="color:rgba(255,255,255,0.6);font-size:13px;">6:00 AM every day</div></div>
          </div>
          <div style="display:flex;align-items:center;gap:16px;margin-bottom:28px;padding:16px;background:rgba(255,255,255,0.06);border-radius:10px;">
            <i class="fas fa-moon" style="font-size:24px;color:#a78bfa;flex-shrink:0;"></i>
            <div><div style="font-weight:700;">Evening Closing</div><div style="color:rgba(255,255,255,0.6);font-size:13px;">10:00 PM every day</div></div>
          </div>
          <div style="background:rgba(240,165,0,0.15);border:1px solid rgba(240,165,0,0.3);border-radius:10px;padding:14px;text-align:center;">
            <div style="color:var(--accent);font-weight:700;font-size:15px;"><i class="fas fa-infinity me-2"></i>Open 365 Days a Year</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     FACILITIES SECTION
     ============================================================ -->
<section id="facilities" style="background:var(--bg-page);">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag"><i class="fas fa-star"></i> Facilities</div>
      <h2 class="section-title">World-Class Amenities</h2>
      <p class="section-sub mx-auto">Everything you need to study effectively — all included in your membership.</p>
    </div>
    <div class="row g-4">
      <?php
      $facilities = [
        ['fas fa-tint',       'RO Drinking Water',    'Pure RO-filtered drinking water available throughout the day at no extra cost.'],
        ['fas fa-snowflake',  'AC Reading Hall',      'Fully air-conditioned hall maintains perfect temperature for focused studying.'],
        ['fas fa-video',      'CCTV Security',        '24/7 surveillance cameras ensure the safety of students and their belongings.'],
        ['fas fa-bolt',       '24hr Power Backup',    'Never lose your study session — uninterrupted power supply is always on.'],
        ['fas fa-user-shield','Individual Cabins',    'Private study cabins for maximum focus and personal space away from distractions.'],
        ['fas fa-volume-mute','Silence Zone',         'Strict silence maintained — ideal for deep concentration and exam preparation.'],
      ];
      foreach ($facilities as $f): ?>
      <div class="col-md-6 col-lg-4">
        <div class="facility-card">
          <div class="facility-icon"><i class="<?php echo $f[0]; ?>"></i></div>
          <h5><?php echo $f[1]; ?></h5>
          <p><?php echo $f[2]; ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     FEE STRUCTURE SECTION
     ============================================================ -->
<section id="fees" style="background:#fff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag"><i class="fas fa-tags"></i> Fee Structure</div>
      <h2 class="section-title">Simple, Transparent Pricing</h2>
      <p class="section-sub mx-auto">No hidden charges. All payments are made directly at the library. No online payment required.</p>
    </div>
    <div class="row g-4 justify-content-center">

      <!-- Unreserved Plan -->
      <div class="col-md-6 col-lg-4">
        <div class="fee-card h-100">
          <div class="fee-tag">Monthly — Unreserved</div>
          <div class="fee-price"><sup>₹</sup>1800<small>/month</small></div>
          <hr style="margin:20px 0;border-color:var(--border);">
          <ul style="list-style:none;padding:0;margin:0;">
            <?php foreach (['Access to full library','AC reading hall','RO water included','CCTV security','24hr power backup','Any available seat (unreserved)'] as $item): ?>
            <li style="padding:7px 0;font-size:14px;color:var(--text-muted);display:flex;align-items:center;gap:10px;">
              <i class="fas fa-check-circle" style="color:var(--success);"></i><?php echo $item; ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- Reserved Plan (featured) -->
      <div class="col-md-6 col-lg-4">
        <div class="fee-card featured h-100" style="position:relative;">
          <div style="position:absolute;top:-12px;right:20px;background:var(--accent);color:var(--primary-dark);padding:4px 14px;border-radius:20px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:1px;">Most Popular</div>
          <div class="fee-tag">Monthly — Reserved</div>
          <div class="fee-price"><sup>₹</sup>1900<small>/month</small></div>
          <p style="font-size:12px;color:rgba(255,255,255,0.5);margin-top:4px;">₹1800 monthly + ₹100 seat reservation</p>
          <hr style="margin:20px 0;border-color:rgba(255,255,255,0.15);">
          <ul style="list-style:none;padding:0;margin:0;">
            <?php foreach (['Your own fixed seat number','No one else can use your seat','All unreserved plan benefits','₹100 extra for seat reservation','Guaranteed seat every visit','Peace of mind'] as $item): ?>
            <li style="padding:7px 0;font-size:14px;color:rgba(255,255,255,0.75);display:flex;align-items:center;gap:10px;">
              <i class="fas fa-check-circle" style="color:var(--accent);"></i><?php echo $item; ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- One-time fees -->
      <div class="col-md-6 col-lg-4">
        <div class="fee-card h-100">
          <div class="fee-tag">One-time Charges</div>
          <div style="margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);">
              <span style="font-size:14px;font-weight:600;">Registration Fee</span>
              <span style="font-weight:800;color:var(--primary);font-size:18px;">₹100</span>
            </div>
            <div style="font-size:12px;color:var(--text-muted);padding:6px 0 14px;border-bottom:1px solid var(--border);">Non-refundable · paid once at joining</div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);">
              <span style="font-size:14px;font-weight:600;">Security Deposit</span>
              <span style="font-weight:800;color:var(--primary);font-size:18px;">₹300</span>
            </div>
            <div style="font-size:12px;color:var(--text-muted);padding:6px 0 14px;">Fully refundable when you leave</div>
          </div>
          <div style="background:rgba(26,183,89,0.08);border:1px solid rgba(26,183,89,0.2);border-radius:10px;padding:14px;text-align:center;">
            <div style="font-size:13px;font-weight:700;color:var(--success);">
              <i class="fas fa-shield-alt me-2"></i>Deposit Fully Refundable
            </div>
          </div>
        </div>
      </div>

    </div>
    <div class="text-center mt-4">
      <p style="color:var(--text-muted);font-size:13px;">
        <i class="fas fa-info-circle me-2 text-primary"></i>
        All payments are collected directly at the library. No online payment required.
      </p>
    </div>
  </div>
</section>

<!-- ============================================================
     CONTACT SECTION
     ============================================================ -->
<section class="contact-section" id="contact">
  <div class="container">
    <div class="row align-items-start g-5">
      <div class="col-lg-5">
        <div class="section-tag" style="background:rgba(255,255,255,0.1);color:var(--accent);">
          <i class="fas fa-phone"></i> Contact Us
        </div>
        <h2 class="section-title">Visit Us Today</h2>
        <p class="section-sub">We'd love to show you around. Come visit, see the facilities, and enroll today.</p>

        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div>
            <h6>Address</h6>
            <p>City Center Complex, 3rd Floor, Office No 304,<br>Undri, Pune, Maharashtra, India</p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fas fa-clock"></i></div>
          <div>
            <h6>Working Hours</h6>
            <p>Monday to Sunday<br>6:00 AM – 10:00 PM</p>
          </div>
        </div>
        <div class="contact-info-item">
          <div class="contact-info-icon"><i class="fab fa-whatsapp"></i></div>
          <div>
            <h6>WhatsApp / Call</h6>
            <p><a href="tel:+919579089287" style="color:var(--accent);text-decoration:none;font-weight:700;">+91 95790 89287</a></p>
          </div>
        </div>

        <div class="d-flex gap-3 mt-4">
          <a href="tel:+919579089287" class="btn btn-accent" style="flex:1;text-align:center;">
            <i class="fas fa-phone me-2"></i>Call Now
          </a>
          <a href="https://wa.me/919579089287?text=Hello%2C+I+want+to+know+about+Ekagra+Abhyasika" target="_blank"
             class="btn" style="flex:1;text-align:center;background:#25d366;color:#fff;">
            <i class="fab fa-whatsapp me-2"></i>WhatsApp
          </a>
        </div>
      </div>

      <div class="col-lg-7">
        <div style="border-radius:var(--radius-lg);overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.4);">
          <iframe
            src="https://maps.google.com/maps?q=Undri+City+Center+Complex+Pune+Maharashtra&t=&z=15&ie=UTF8&iwloc=&output=embed"
            width="100%" height="420" style="border:0;display:block;"
            allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     LIBRARY RULES SECTION
     ============================================================ -->
<section style="background:#fff;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag"><i class="fas fa-gavel"></i> Library Rules</div>
      <h2 class="section-title">Code of Conduct</h2>
      <p class="section-sub mx-auto">Please follow these rules to maintain a peaceful environment for all students.</p>
    </div>
    <div class="row g-3">
      <?php
      $rules = [
        'Maintain complete silence inside the library at all times',
        'Mobile phones must be kept on silent or vibrate mode',
        'Do not disturb or distract other students',
        'Keep your study area clean and tidy',
        'Personal belongings are solely your own responsibility',
        'Reserved seats cannot be used by other students',
        'Entry is permitted only during library hours (6 AM–10 PM)',
        'Food and beverages are not allowed inside the reading hall',
        'ID card must be carried at all times',
        'Violation of rules may result in membership cancellation',
      ];
      foreach ($rules as $i => $rule): ?>
      <div class="col-md-6">
        <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--bg-page);border-radius:var(--radius);border:1px solid var(--border);">
          <span style="width:28px;height:28px;background:var(--primary);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;"><?php echo $i + 1; ?></span>
          <span style="font-size:14px;"><?php echo e($rule); ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="site-footer">
  <div class="container">
    <p>© <?php echo date('Y'); ?> Ekagra Abhyasika. All Rights Reserved.</p>
    <p style="margin-top:4px;font-size:12px;color:rgba(255,255,255,0.3);">
      City Center Complex, 3rd Floor, Office No 304, Undri, Pune, Maharashtra, India
      &nbsp;|&nbsp;
      <a href="admin/login.php" style="color:rgba(255,255,255,0.2);text-decoration:none;">Admin</a>
    </p>
  </div>
</footer>

<!-- Floating Buttons -->
<div class="floating-btns">
  <a href="https://wa.me/919579089287?text=Hello%2C+I+want+to+know+about+Ekagra+Abhyasika"
     target="_blank" class="float-btn wa" title="WhatsApp Us">
    <i class="fab fa-whatsapp"></i>
  </a>
  <a href="tel:+919579089287" class="float-btn call" title="Call Us">
    <i class="fas fa-phone"></i>
  </a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
