<?php
require_once 'guard-warga.php';

// Guard udah nyediain: $conn, $nik, $nama

$history_data = [];
$total_voting = 0;

// Ambil rt/rw warga buat nav (biar $rt_warga ga undefined)
$rt_warga = '';
$rw_warga = '';
$stmt = $conn->prepare("SELECT rt, rw FROM warga WHERE nik = ? LIMIT 1");
$stmt->bind_param("s", $nik);
$stmt->execute();
$w = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($w) {
    $rt_warga = (string)($w['rt'] ?? '');
    $rw_warga = (string)($w['rw'] ?? '');
}

// Ambil riwayat voting warga
$sql = "
    SELECT 
        p.nama_periode,
        p.tanggal_mulai,
        p.tanggal_selesai,
        k.nama as nama_kandidat,
        k.rt,
        k.rw,
        v.waktu_pilih as timestamp,
        v.waktu_pilih
    FROM voting v
    JOIN periode p ON v.id_periode = p.id_periode
    JOIN kandidat k ON v.id_calon = k.id_calon
    WHERE v.nik = ?
    ORDER BY v.waktu_pilih DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nik);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $history_data[] = $row;
}
$stmt->close();

$total_voting = count($history_data);

// Hitung status terakhir
$status_terakhir = '-';
if ($total_voting > 0 && isset($history_data[0])) {
    $last_vote = $history_data[0];
    $current_date = date('Y-m-d');

    if ($current_date >= $last_vote['tanggal_mulai'] && $current_date <= $last_vote['tanggal_selesai']) {
        $status_terakhir = 'Sedang Berlangsung';
    } elseif ($current_date > $last_vote['tanggal_selesai']) {
        $status_terakhir = 'Selesai';
    }
}

// Untuk filter tahun (ambil tahun unik dari periode)
$tahun_list = [];
$sql_tahun = "SELECT DISTINCT YEAR(tanggal_mulai) as tahun FROM periode ORDER BY tahun DESC";
$result_tahun = $conn->query($sql_tahun);
if ($result_tahun) {
    while ($row = $result_tahun->fetch_assoc()) {
        $tahun_list[] = $row['tahun'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • History Voting</title>
  <link rel="stylesheet" href="../css/history.css">
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
      
      <div class="nav-user">
        <span>Halo, <?php echo htmlspecialchars($nama); ?></span>
        <?php if (!empty($rt_warga)): ?>
          (RT <?php echo htmlspecialchars(str_pad($rt_warga, 2, '0', STR_PAD_LEFT)); ?>)
        <?php endif; ?>
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
        <li><a href="jadwal-pemilihan.php"><i class="fas fa-calendar-alt"></i> Jadwal Pemilihan</a></li>
        <li><a href="daftar-kandidat.php"><i class="fas fa-users"></i> Data Calon</a></li>
        <li><a href="pilih-kandidat.php"><i class="fas fa-vote-yea"></i> Pilih Kandidat</a></li>
        <li><a href="history.php" class="active"><i class="fas fa-history"></i> History Voting</a></li>
        <li><a href="rekap-sementara.php"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil-akhir.php"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
        <li><a href="ubah-password.php"><i class="fas fa-key"></i>Ubah Password</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <section class="page-header">
        <div class="header-content">
          <h1>History Voting</h1>
          <h3>Perumahan Griya Harmoni</h3>
          <p>Riwayat penggunaan hak pilih Anda dalam pemilihan RT/RW</p>
        </div>
      </section>

      <section class="history-content">
        <div class="filter-section">
          <div class="filter-group">
            <label for="tahun-filter">Tahun Pemilihan:</label>
            <select id="tahun-filter" class="filter-select">
              <option value="all">Semua Tahun</option>
              <?php foreach ($tahun_list as $tahun): ?>
                <option value="<?php echo $tahun; ?>"><?php echo $tahun; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label for="status-filter">Status:</label>
            <select id="status-filter" class="filter-select">
              <option value="all">Semua Status</option>
              <option value="completed">Selesai</option>
              <option value="ongoing">Berlangsung</option>
            </select>
          </div>
        </div>

        <?php if ($total_voting > 0): ?>
        <div class="history-list">
          <?php foreach ($history_data as $history): ?>
          <div class="history-card" 
               data-tahun="<?php echo date('Y', strtotime($history['tanggal_mulai'])); ?>"
               data-status="<?php 
                 $current_date = date('Y-m-d');
                 if ($current_date >= $history['tanggal_mulai'] && $current_date <= $history['tanggal_selesai']) {
                   echo 'ongoing';
                 } else {
                   echo 'completed';
                 }
               ?>">
            <div class="history-header">
              <h3><?php echo htmlspecialchars($history['nama_periode']); ?></h3>
              <span class="history-status <?php 
                $current_date = date('Y-m-d');
                if ($current_date >= $history['tanggal_mulai'] && $current_date <= $history['tanggal_selesai']) {
                  echo 'status-ongoing';
                } else {
                  echo 'status-completed';
                }
              ?>">
                <?php 
                $current_date = date('Y-m-d');
                if ($current_date >= $history['tanggal_mulai'] && $current_date <= $history['tanggal_selesai']) {
                  echo 'Berlangsung';
                } else {
                  echo 'Selesai';
                }
                ?>
              </span>
            </div>
            
            <div class="history-body">
              <div class="history-detail">
                <div class="detail-item">
                  <span class="detail-label">Tanggal Voting:</span>
                  <span class="detail-value">
                    <?php echo date('d-m-Y H:i', strtotime($history['waktu_pilih'])); ?>
                  </span>
                </div>
                
                <div class="detail-item">
                  <span class="detail-label">Jabatan:</span>
                  <span class="detail-value">
                    <?php 
                    if (!empty($history['rt']) && $history['rt'] !== '') {
                      echo 'RT ' . $history['rt'] . ' RW ' . $history['rw'];
                    } else {
                      echo 'RW ' . $history['rw'];
                    }
                    ?>
                  </span>
                </div>
                
                <div class="detail-item">
                  <span class="detail-label">Kandidat Dipilih:</span>
                  <span class="detail-value candidate-name">
                    <?php echo htmlspecialchars($history['nama_kandidat']); ?>
                  </span>
                </div>
                
                <div class="detail-item">
                  <span class="detail-label">Periode:</span>
                  <span class="detail-value">
                    <?php 
                    $mulai = date('d-m-Y', strtotime($history['tanggal_mulai']));
                    $selesai = date('d-m-Y', strtotime($history['tanggal_selesai']));
                    echo "$mulai s/d $selesai";
                    ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        
        <?php else: ?>
        <div class="empty-history">
          <div class="empty-icon">
            <i class="fas fa-history"></i>
          </div>
          <h2>Belum Ada Riwayat Voting</h2>
          <p>Anda belum pernah menggunakan hak pilih dalam pemilihan RT/RW</p>
          <p class="empty-subtext">Riwayat voting akan muncul di sini setelah Anda melakukan pemilihan</p>
          <a href="pilih-kandidat.php" class="btn-primary">
            <i class="fas fa-vote-yea"></i> Pilih Kandidat Sekarang
          </a>
        </div>
        <?php endif; ?>

        <div class="stats-card">
          <div class="stat-item">
            <div class="stat-number"><?php echo $total_voting; ?></div>
            <div class="stat-label">Total Voting</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?php echo $total_voting; ?></div>
            <div class="stat-label">Pemilihan Diikuti</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?php echo $status_terakhir; ?></div>
            <div class="stat-label">Status Terakhir</div>
          </div>
        </div>

        <div class="info-section">
          <div class="info-card">
            <h3><i class="fas fa-info-circle"></i> Informasi History Voting</h3>
            <div class="info-content">
              <p>• History voting menampilkan semua pemilihan yang pernah Anda ikuti</p>
              <p>• Anda dapat melihat detail pilihan Anda untuk setiap pemilihan</p>
              <p>• Data voting bersifat rahasia dan hanya dapat dilihat oleh Anda</p>
              <p>• Riwayat voting akan tersimpan secara permanen dalam sistem</p>
            </div>
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

        const tahunFilter = document.getElementById('tahun-filter');
        const statusFilter = document.getElementById('status-filter');
        const historyCards = document.querySelectorAll('.history-card');

        function updateFilters() {
            const selectedTahun = tahunFilter.value;
            const selectedStatus = statusFilter.value;

            historyCards.forEach(card => {
                const cardTahun = card.dataset.tahun;
                const cardStatus = card.dataset.status;

                const showTahun = selectedTahun === 'all' || cardTahun === selectedTahun;
                const showStatus = selectedStatus === 'all' || cardStatus === selectedStatus;

                card.style.display = (showTahun && showStatus) ? 'block' : 'none';
            });
        }

        if (tahunFilter && statusFilter && historyCards.length > 0) {
            tahunFilter.addEventListener('change', updateFilters);
            statusFilter.addEventListener('change', updateFilters);
        }
    });
  </script>
</body>
</html>
