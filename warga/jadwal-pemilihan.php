<?php
require_once 'guard-warga.php';

// Guard udah nyediain: $conn, $nik, $nama

$jadwal = [];

$sql = "SELECT * FROM jadwal ORDER BY tanggal_mulai ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $jadwal[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Jadwal Pemilihan</title>
  <link rel="stylesheet" href="/css/jadwal-pemilihan.css">
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
          <img src="/assets/logo-sipelV3.png" alt="SipeL Logo" class="logo-img">
          <span>SipeL</span>
        </a>
      </div>
      <div class="nav-user">
        <span>Halo, <?php echo htmlspecialchars($nama); ?></span>
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
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Beranda</a></li>
        <li><a href="jadwal-pemilihan.php" class="active"><i class="fas fa-calendar-alt"></i> Jadwal Pemilihan</a></li>
        <li><a href="daftar-kandidat.php"><i class="fas fa-users"></i> Data Calon</a></li>
        <li><a href="pilih-kandidat.php"><i class="fas fa-vote-yea"></i> Pilih Kandidat</a></li>
        <li><a href="history.php"><i class="fas fa-history"></i> History Voting</a></li>
        <li><a href="rekap-sementara.php"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil-akhir.php"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
        <li><a href="ubah-password.php"><i class="fas fa-key"></i>Ubah Password</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <section class="page-header">
        <div class="header-content">
          <h1>Jadwal Pemilihan RT/RW</h1>
          <p>Perumahan Griya Harmoni</p>
        </div>
      </section>

      <section class="election-list">
        <h2>Daftar Pemilihan Aktif</h2>

        <div class="election-cards">
          <?php if (empty($jadwal)): ?>
            <p class="no-data">Belum ada jadwal pemilihan yang terdaftar.</p>
          <?php else: ?>
            <?php
            $now = new DateTime();
            foreach ($jadwal as $j):
                $mulai   = new DateTime($j['tanggal_mulai']);
                $selesai = new DateTime($j['tanggal_selesai']);

                if ($now < $mulai) {
                    $status      = 'Belum Dimulai';
                    $statusClass = 'status-upcoming';
                } elseif ($now > $selesai) {
                    $status      = 'Selesai';
                    $statusClass = 'status-ended';
                } else {
                    $status      = 'Sedang Berlangsung';
                    $statusClass = 'status-active';
                }
            ?>
            <div class="election-card">
              <div class="election-header">
                <h3><?= htmlspecialchars($j['keterangan'] ?? '-') ?></h3>
                <span class="status-badge <?= $statusClass ?>"><?= $status ?></span>
              </div>
              <div class="election-info">
                <div class="info-item">
                  <i class="fas fa-calendar"></i>
                  <span>
                    Tanggal: <?= $mulai->format('d-m-Y') ?> s/d <?= $selesai->format('d-m-Y') ?>
                  </span>
                </div>
                <div class="info-item">
                  <i class="fas fa-clock"></i>
                  <span>Waktu mulai: <?= $mulai->format('H:i') ?> WIB</span>
                </div>
                <div class="info-item">
                  <i class="fas fa-users"></i>
                  <span>Jumlah Kandidat: -</span>
                </div>
                <div class="info-item">
                  <i class="fas fa-user-check"></i>
                  <span>Pemilih Terdaftar: -</span>
                </div>
              </div>
              <button class="btn-detail" disabled>
                <i class="fas fa-info-circle"></i>
                Detail Pemilihan
              </button>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
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

            window.addEventListener('scroll', () => {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                    hamburger.classList.remove('active');
                    sidebar.classList.remove('active');
                    if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                }
            });
        }
    });
  </script>
</body>
</html>
