<?php
session_start();

// Cek sudah login & role panitia
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'panitia') {
    header('Location: login.php');
    exit;
}
$nama_panitia = isset($_SESSION['nama']) ? $_SESSION['nama'] : $_SESSION['username'];
require_once '../db_connect.php';

// inisialisasi
$totalPemilih   = 0;
$sudahMemilih   = 0;
$persentase     = 0;
$totalKandidat  = 0;

// total warga
$res = $conn->query("SELECT COUNT(*) AS jml FROM warga");
if ($res) {
    $row = $res->fetch_assoc();
    $totalPemilih = (int)$row['jml'];
}

// sudah memilih
$res = $conn->query("SELECT COUNT(DISTINCT nik) AS jml FROM voting");
if ($res) {
    $row = $res->fetch_assoc();
    $sudahMemilih = (int)$row['jml'];
}

// partisipasi
if ($totalPemilih > 0) {
    $persentase = round($sudahMemilih / $totalPemilih * 100, 2);
}

// kandidat
$res = $conn->query("SELECT COUNT(*) AS jml FROM kandidat");
if ($res) {
    $row = $res->fetch_assoc();
    $totalKandidat = (int)$row['jml'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Dashboard Panitia</title>
  <link rel="stylesheet" href="../css/dashboard-panitia.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <nav class="navbar">
    <div class="nav-container">
      <div class="nav-logo">
        <a href="../index.php">
          <img src="../assets/logo-sipelV3.png" alt="SipeL Logo" class="logo-img">
          <span>SipeL</span>
        </a>
      </div>
      
      <!-- User Info & Logout -->
      <div class="nav-user">
        <span>Halo, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Panitia'); ?></span>
        <a href="../logout.php" class="btn-logout">
       <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
      
      <!-- Hamburger Menu -->
      <div class="hamburger" id="hamburger">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </div>
    </div>
  </nav>

  <div class="admin-container">
    <aside class="sidebar" id="sidebar">
      <div class="side-logo">Menu Admin</div>
      <ul>
        <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Beranda</a></li>
        <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Jadwal Pemilihan</a></li>
        <li><a href="data-pemilih.php"><i class="fas fa-users"></i> Data Warga</a></li>
        <li><a href="kandidat.php"><i class="fas fa-user-tie"></i> Data Kandidat</a></li>
        <li><a href="rekap.php"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil.php"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <section class="hero" style="background-image: linear-gradient(180deg, rgba(0, 117, 201, 0.3), rgba(0,117,201,0.3)), url('https://www.99.co/id/img-regional/800/800/fit/true/production/image/user/1ba2454f-8241-42d7-8750-56da6941d091/2025-01-07-06-54-53-0e31fd4b-35b7-415d-9e3c-88627c637fcd.jpg');">
        <div class="overlay-box">
          <h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Panitia'); ?></h1>
          <h3>Panitia Pemilihan RT/RW</h3>
          <p>Perumahan Griya Harmoni</p>
        </div>
      </section>

      <section class="stats-section">
        <h2 class="section-title">Statistik Pemilihan</h2>
        <div class="dashboard-grid">
          <div class="dashboard-card">
            <h3><?= $totalPemilih ?></h3>
            <p>Pemilih Terdaftar</p>
          </div>
          <div class="dashboard-card">
            <h3><?= $sudahMemilih ?></h3>
            <p>Sudah Memilih</p>
          </div>
          <div class="dashboard-card">
            <h3><?= $persentase ?>%</h3>
            <p>Partisipasi</p>
          </div>
          <div class="dashboard-card">
            <h3><?= $totalKandidat ?></h3>
            <p>Kandidat Terdaftar</p>
          </div>
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
                
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('active');
                }
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
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('active');
                    }
                });
            });

            window.addEventListener('scroll', () => {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                    hamburger.classList.remove('active');
                    sidebar.classList.remove('active');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('active');
                    }
                }
            });
        }
    });
  </script>
</body>
</html>