// ============================================================
// EKAGRA ABHYASIKA - Main JavaScript (assets/js/main.js)
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

  // ---- Sidebar Toggle (Mobile) ----
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar       = document.getElementById('adminSidebar');
  const overlay       = document.getElementById('sidebarOverlay');

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('active');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', function () {
      sidebar.classList.remove('open');
      overlay.classList.remove('active');
    });
  }

  // ---- Active Nav Link ----
  const currentPath = window.location.pathname.split('/').pop();
  document.querySelectorAll('.sidebar-nav a').forEach(link => {
    const href = (link.getAttribute('href') || '').split('/').pop();
    if (href && href !== '' && currentPath === href) {
      link.classList.add('active');
    }
  });

  // ---- Auto-dismiss alerts after 4 s ----
  document.querySelectorAll('.alert-dismissible').forEach(alert => {
    setTimeout(() => {
      try { new bootstrap.Alert(alert).close(); } catch (e) {}
    }, 4000);
  });

  // ---- Confirm dialogs ----
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', function (e) {
      if (!confirm(this.dataset.confirm || 'Are you sure?')) {
        e.preventDefault();
      }
    });
  });

  // ---- Client-side table search ----
  document.querySelectorAll('[data-search-table]').forEach(input => {
    const tableId = input.dataset.searchTable;
    const table   = document.getElementById(tableId);
    if (!table) return;
    const rows = table.querySelectorAll('tbody tr');
    input.addEventListener('input', function () {
      const q = this.value.toLowerCase().trim();
      rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  });

  // ---- Animated counters for stat cards ----
  document.querySelectorAll('.stat-value[data-target]').forEach(el => {
    const target = parseInt(el.dataset.target, 10);
    if (isNaN(target)) return;
    let current = 0;
    const step  = Math.max(1, Math.ceil(target / 30));
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current;
      if (current >= target) clearInterval(timer);
    }, 30);
  });

  // ---- Bootstrap tooltips ----
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { placement: 'top' });
  });

  // ---- Smooth scroll for anchor links (public pages) ----
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // ---- Navbar scroll effect (public) ----
  const publicNav = document.querySelector('.public-nav');
  if (publicNav) {
    window.addEventListener('scroll', () => {
      publicNav.style.boxShadow = window.scrollY > 50
        ? '0 4px 30px rgba(0,0,0,0.4)'
        : '0 4px 20px rgba(0,0,0,0.3)';
    });
  }

});

// ============================================================
// UTILITY FUNCTIONS (global scope)
// ============================================================

/**
 * Export an HTML table to a CSV file download.
 */
function exportTableToCSV(tableId, filename) {
  const table = document.getElementById(tableId);
  if (!table) { alert('Table not found: ' + tableId); return; }

  const rows    = table.querySelectorAll('tr');
  const csvRows = [];

  rows.forEach(row => {
    const cols = Array.from(row.querySelectorAll('th, td')).map(col => {
      const text = col.innerText
        .replace(/"/g,  '""')
        .replace(/\n/g, ' ')
        .trim();
      return '"' + text + '"';
    });
    csvRows.push(cols.join(','));
  });

  const blob = new Blob(['\uFEFF' + csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = filename || 'export.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

/**
 * Print the current page.
 */
function printPage() {
  window.print();
}

/**
 * Open the seat detail modal.
 * @param {string} encoded  URL-encoded JSON string from PHP.
 */
function openSeatModal(encoded) {
  let seatData;
  try {
    seatData = JSON.parse(decodeURIComponent(encoded));
  } catch (e) {
    console.error('openSeatModal: invalid JSON', e);
    return;
  }

  const set = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val || '—';
  };

  set('modalSeatNumber',  seatData.seat_number);
  set('modalSeatNumber2', seatData.seat_number);
  set('modalSeatType',    seatData.seat_type);
  set('modalSeatStatus',  seatData.status);
  set('modalStudentName', seatData.student_name || 'Vacant');
  set('modalStudentMobile', seatData.mobile);
  set('modalRenewalDate', seatData.renewal_date);

  const actionDiv = document.getElementById('modalSeatActions');
  if (actionDiv) {
    if (seatData.status === 'Occupied') {
      actionDiv.innerHTML =
        '<a href="students.php?view=' + seatData.student_id + '" class="btn btn-primary btn-sm">' +
          '<i class="fas fa-eye me-1"></i>View Student' +
        '</a>' +
        '<a href="seats.php?free=' + seatData.seat_number + '" class="btn btn-danger btn-sm"' +
           ' onclick="return confirm(\'Free seat ' + seatData.seat_number + '?\')">' +
          '<i class="fas fa-unlock me-1"></i>Free Seat' +
        '</a>';
    } else {
      actionDiv.innerHTML =
        '<a href="students.php?add=1&seat=' + seatData.seat_number + '" class="btn btn-success btn-sm">' +
          '<i class="fas fa-user-plus me-1"></i>Assign Student' +
        '</a>';
    }
  }

  const modal = new bootstrap.Modal(document.getElementById('seatDetailModal'));
  modal.show();
}
