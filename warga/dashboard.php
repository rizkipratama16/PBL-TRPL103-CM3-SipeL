<?php
require_once 'guard-warga.php';

/* Ambil data warga buat hero */
$stmt = $conn->prepare("SELECT nama, rt, rw FROM warga WHERE nik = ? LIMIT 1");
$stmt->bind_param("s", $nik);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$data['nama'] = $data['nama'] ?: $nama;
$data['rt']   = $data['rt'] ?? '';
$data['rw']   = $data['rw'] ?? '';

/* =========================
   HITUNG STATISTIK GLOBAL (SEMUA RT/RW)
   ========================= */

/* ambil periode aktif */
$id_periode = null;
$p = $conn->query("SELECT id_periode FROM periode WHERE status='aktif' ORDER BY id_periode DESC LIMIT 1");
if ($p && $p->num_rows > 0) {
    $id_periode = (int)$p->fetch_assoc()['id_periode'];
}

/* total pemilih terdaftar (SEMUA warga) */
$totalPemilih = 0;
$q = $conn->query("SELECT COUNT(*) AS total FROM warga");
if ($q) $totalPemilih = (int)($q->fetch_assoc()['total'] ?? 0);

/* sudah memilih (unik nik) di periode aktif (SEMUA RT/RW) */
$sudahMemilih = 0;
if ($id_periode) {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT nik) AS total
        FROM voting
        WHERE id_periode = ?
    ");
    $stmt->bind_param("i", $id_periode);
    $stmt->execute();
    $sudahMemilih = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

/* total kandidat (periode aktif kalau ada, kalau tidak ada -> semua kandidat) */
$totalKandidat = 0;
if ($id_periode) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM kandidat WHERE id_periode = ?");
    $stmt->bind_param("i", $id_periode);
    $stmt->execute();
    $totalKandidat = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
} else {
    $q = $conn->query("SELECT COUNT(*) AS total FROM kandidat");
    if ($q) $totalKandidat = (int)($q->fetch_assoc()['total'] ?? 0);
}

/* persentase partisipasi global */
$persentase = ($totalPemilih > 0) ? round(($sudahMemilih / $totalPemilih) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Dashboard Warga</title>
  <link rel="stylesheet" href="/css/dashboard-warga.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <nav class="navbar">
    <div class="nav-container">
      <div class="nav-logo">
        <a href="index.php">
          <img src="/assets/logo-sipelV3.png" alt="SipeL Logo" class="logo-img">
          <span>SipeL</span>
        </a>
      </div>

      <div class="nav-user">
        <span>Halo, <?php echo htmlspecialchars($nama); ?></span>
        <a href="../logout.php" class="btn-logout">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>

      <div class="hamburger" id="hamburger">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </div>
    </div>
  </nav>

  <div class="admin-container">
    <aside class="sidebar" id="sidebar">
      <div class="side-logo">Menu Warga</div>
      <ul>
        <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Beranda</a></li>
        <li><a href="jadwal-pemilihan.php"><i class="fas fa-calendar-alt"></i> Jadwal Pemilihan</a></li>
        <li><a href="daftar-kandidat.php"><i class="fas fa-users"></i> Data Calon</a></li>
        <li><a href="pilih-kandidat.php"><i class="fas fa-vote-yea"></i> Pilih Kandidat</a></li>
        <li><a href="history.php"><i class="fas fa-history"></i> History Voting</a></li>
        <li><a href="rekap-sementara.php"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil-akhir.php"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
        <li><a href="ubah-password.php"><i class="fas fa-key"></i> Ubah Password</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <section class="hero" style="background-image: linear-gradient(180deg, rgba(0, 117, 201, 0.3), rgba(0,117,201,0.3)), url('https://www.99.co/id/img-regional/800/800/fit/true/production/image/user/1ba2454f-8241-42d7-8750-56da6941d091/2025-01-07-06-54-53-0e31fd4b-35b7-415d-9e3c-88627c637fcd.jpg');">
        <div class="overlay-box">
          <h1>Selamat Datang, <?php echo htmlspecialchars($data['nama']); ?></h1>
          <h3>Warga RT <?php echo htmlspecialchars($data['rt']); ?> / RW <?php echo htmlspecialchars($data['rw']); ?></h3>
          <p>Gunakan hak pilihmu di Pemilihan RT/RW</p>
        </div>
      </section>

      <section class="stats-section">
        <h2 class="section-title">Statistik Pemilihan</h2>
        <div class="dashboard-grid">
          <div class="dashboard-card">
            <h3><?= (int)$totalPemilih ?></h3>
            <p>Pemilih Terdaftar</p>
          </div>
          <div class="dashboard-card">
            <h3><?= (int)$sudahMemilih ?></h3>
            <p>Sudah Memilih</p>
          </div>
          <div class="dashboard-card">
            <h3><?= (float)$persentase ?>%</h3>
            <p>Partisipasi</p>
          </div>
          <div class="dashboard-card">
            <h3><?= (int)$totalKandidat ?></h3>
            <p>Kandidat Terdaftar</p>
          </div>
        </div>
      </section>

      <section class="quick-actions">
        <h2>Aksi Cepat</h2>
        <div class="action-buttons">
          <a href="daftar-kandidat.php" class="action-btn">
            <i class="fas fa-users"></i>
            <h4>Lihat Kandidat</h4>
            <p>Kenali calon RT/RW sebelum memilih</p>
          </a>
          <a href="pilih-kandidat.php" class="action-btn">
            <i class="fas fa-vote-yea"></i>
            <h4>Pilih Sekarang</h4>
            <p>Gunakan hak pilih Anda</p>
          </a>
          <a href="jadwal-pemilihan.php" class="action-btn">
            <i class="fas fa-calendar-alt"></i>
            <h4>Jadwal Pemilihan</h4>
            <p>Lihat waktu dan tempat pemilihan</p>
          </a>
          <a href="rekap-sementara.php" class="action-btn">
            <i class="fas fa-chart-bar"></i>
            <h4>Rekap Sementara</h4>
            <p>Pantau perkembangan hasil pemilihan</p>
          </a>
        </div>
      </section>

      <footer>
        <div class="footer-content">
          <p>© 2025 SipeL • Sistem Pemilihan Elektronik RT/RW</p>
        </div>
      </footer>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const hamburger = document.getElementById('hamburger');
      const sidebar = document.getElementById('sidebar');
      const sidebarOverlay = document.getElementById('sidebar-overlay');

      if (hamburger && sidebar) {
        hamburger.addEventListener('click', () => {
          hamburger.classList.toggle('active');
          sidebar.classList.toggle('active');
          if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
        });

        if (sidebarOverlay) {
          sidebarOverlay.addEventListener('click', () => {
            hamburger.classList.remove('active');
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
          });
        }

        document.querySelectorAll('.sidebar a').forEach(link => {
          link.addEventListener('click', () => {
            hamburger.classList.remove('active');
            sidebar.classList.remove('active');
            if (sidebarOverlay) sidebarOverlay.classList.remove('active');
          });
        });
      }
    });
  </script>
</body>
</html>
