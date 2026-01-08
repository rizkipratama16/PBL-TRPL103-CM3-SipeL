<?php
session_start();

// Cek sudah login & role panitia
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'panitia') {
    header('Location: login.php');
    exit;
}

require_once '../db_connect.php';

// Ambil data statistik
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

// sudah memilih dari tabel voting
$res = $conn->query("SELECT COUNT(DISTINCT nik) AS jml FROM voting");
if ($res) {
    $row = $res->fetch_assoc();
    $sudahMemilih = (int)$row['jml'];
}

// partisipasi
if ($totalPemilih > 0) {
    $persentase = round($sudahMemilih / $totalPemilih * 100, 2);
}

// kandidat aktif periode ini
$res = $conn->query("SELECT COUNT(*) AS jml FROM kandidat WHERE id_periode = 1");
if ($res) {
    $row = $res->fetch_assoc();
    $totalKandidat = (int)$row['jml'];
}

// ===========================
//   DATA WILAYAH DASAR
// ===========================
$wilayah_results = [];

// Ambil data wilayah
$wilayah_query = "SELECT id_wilayah, nama_wilayah, jenis, rt, rw FROM wilayah ORDER BY id_wilayah";
$wilayah_res   = $conn->query($wilayah_query);

while ($row = $wilayah_res->fetch_assoc()) {
    $wilayah_id = $row['id_wilayah'];

    $wilayah_results[$wilayah_id] = [
        'nama_wilayah'  => $row['nama_wilayah'],
        'jenis'         => $row['jenis'],   // 'RT' / 'RW'
        'rt'            => $row['rt'],
        'rw'            => $row['rw'],
        'total_warga'   => 0,
        'sudah_memilih' => 0,
        'persentase'    => 0,
        'kandidat'      => []
    ];
}

// ===========================
//  PROGRESS PEMILIHAN PER WILAYAH
//  (PAKAI RT/RW, BUKAN id_wilayah)
// ===========================
foreach ($wilayah_results as $wid => &$wil) {
    if ($wil['jenis'] === 'RT') {
        // Wilayah RT
        $rtCode = $wil['rt']; // '01','02','03'

        // total warga di RT ini
        $stmt = $conn->prepare("SELECT COUNT(*) AS total_warga FROM warga WHERE rt = ?");
        $stmt->bind_param("s", $rtCode);
        $stmt->execute();
        $resT = $stmt->get_result();
        if ($rowT = $resT->fetch_assoc()) {
            $wil['total_warga'] = (int)$rowT['total_warga'];
        }
        $stmt->close();

        // warga yang sudah memilih untuk RT ini (FIX: tambahin k.id_periode biar sinkron)
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT v.nik) AS sudah_memilih
            FROM voting v
            JOIN kandidat k ON v.id_calon = k.id_calon
            WHERE v.id_periode = 1
              AND k.id_periode = 1
              AND k.rt = ?
        ");
        $stmt->bind_param("s", $rtCode);
        $stmt->execute();
        $resS = $stmt->get_result();
        if ($rowS = $resS->fetch_assoc()) {
            $wil['sudah_memilih'] = (int)$rowS['sudah_memilih'];
        }
        $stmt->close();

    } elseif ($wil['jenis'] === 'RW') {
        // Wilayah RW
        $rwCode = $wil['rw']; // '01'

        // total warga di RW ini
        $stmt = $conn->prepare("SELECT COUNT(*) AS total_warga FROM warga WHERE rw = ?");
        $stmt->bind_param("s", $rwCode);
        $stmt->execute();
        $resT = $stmt->get_result();
        if ($rowT = $resT->fetch_assoc()) {
            $wil['total_warga'] = (int)$rowT['total_warga'];
        }
        $stmt->close();

        // warga yang sudah memilih untuk RW ini (FIX: tambahin k.id_periode biar sinkron)
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT v.nik) AS sudah_memilih
            FROM voting v
            JOIN kandidat k ON v.id_calon = k.id_calon
            WHERE v.id_periode = 1
              AND k.id_periode = 1
              AND k.rw = ?
              AND (k.rt IS NULL OR k.rt = '' OR k.rt = '0')
        ");
        $stmt->bind_param("s", $rwCode);
        $stmt->execute();
        $resS = $stmt->get_result();
        if ($rowS = $resS->fetch_assoc()) {
            $wil['sudah_memilih'] = (int)$rowS['sudah_memilih'];
        }
        $stmt->close();
    }

    // hitung persentase
    if ($wil['total_warga'] > 0) {
        $wil['persentase'] = round(
            $wil['sudah_memilih'] / $wil['total_warga'] * 100,
            1
        );
    } else {
        $wil['persentase'] = 0;
    }
}
unset($wil);

// ===========================
//   AMBIL SEMUA KANDIDAT + SUARA
//   LALU BAGIKAN KE WILAYAH
// ===========================
$cand_sql = "
    SELECT 
        k.id_calon,
        k.nama,
        k.rt,
        k.rw,
        k.visi,
        k.misi,
        COALESCE(COUNT(v.id_voting), 0) AS total_suara
    FROM kandidat k
    LEFT JOIN voting v 
        ON k.id_calon = v.id_calon 
       AND v.id_periode = 1
    WHERE k.id_periode = 1
    GROUP BY k.id_calon
    ORDER BY k.rt IS NULL, k.rt, k.rw, k.id_calon
";
$cand_res = $conn->query($cand_sql);

// Pastikan array kandidat kosong dulu
foreach ($wilayah_results as $wid => &$wil) {
    $wil['kandidat'] = [];
}
unset($wil);

while ($row = $cand_res->fetch_assoc()) {
    $rt = $row['rt'];
    $rw = $row['rw'];

    foreach ($wilayah_results as $wid => &$wil) {
        // Kandidat RT (FIX: exclude rt = '0' biar gak keanggap RT)
        if ($wil['jenis'] === 'RT' &&
            $rt !== null && $rt !== '' && $rt !== '0' &&
            $wil['rt'] === $rt) {

            $wil['kandidat'][] = [
                'id_calon'    => $row['id_calon'],
                'nama'        => $row['nama'],
                'rt'          => $row['rt'],
                'rw'          => $row['rw'],
                'total_suara' => (int)$row['total_suara'],
                'visi'        => $row['visi'],
                'misi'        => $row['misi']
            ];
            break;
        }

        // Kandidat RW (FIX: samain definisi RW: rt NULL/''/'0')
        if ($wil['jenis'] === 'RW' &&
            ($rt === null || $rt === '' || $rt === '0') &&
            $wil['rw'] === $rw) {

            $wil['kandidat'][] = [
                'id_calon'    => $row['id_calon'],
                'nama'        => $row['nama'],
                'rt'          => $row['rt'],
                'rw'          => $row['rw'],
                'total_suara' => (int)$row['total_suara'],
                'visi'        => $row['visi'],
                'misi'        => $row['misi']
            ];
            break;
        }
    }
}
unset($wil);

// ===========================
// HITUNG PERSENTASE SUARA PER KANDIDAT
// ===========================
foreach ($wilayah_results as $wilayah_id => &$wilayah) {
    $total_suara_wilayah = 0;
    foreach ($wilayah['kandidat'] as $k) {
        $total_suara_wilayah += (int)$k['total_suara'];
    }

    foreach ($wilayah['kandidat'] as &$kandidat) {
        $kandidat['persentase'] = $total_suara_wilayah > 0
            ? round(($kandidat['total_suara'] / $total_suara_wilayah) * 100, 1)
            : 0;
    }
    unset($kandidat); // FIX KRUSIAL: anti-bug foreach reference
}
unset($wilayah);

// Urutkan kandidat berdasarkan suara terbanyak per wilayah
foreach ($wilayah_results as &$wilayah) {
    usort($wilayah['kandidat'], function ($a, $b) {
        return $b['total_suara'] - $a['total_suara'];
    });
}
unset($wilayah);

// Warna chart (dipakai di generateDonutChart)
$chart_colors = ['#0075c9', '#00a86b', '#ff6b6b', '#ffa726', '#8e44ad', '#3498db'];

// Fungsi donut
function generateDonutChart($kandidat_list)
{
    if (!is_array($kandidat_list) || empty($kandidat_list)) {
        return "background: conic-gradient(#e0e0e0 0deg 360deg);";
    }

    $colors = ['#0075c9', '#00a86b', '#ff6b6b', '#ffa726', '#8e44ad', '#3498db'];

    $valid_kandidat = [];
    foreach ($kandidat_list as $k) {
        if (isset($k['persentase']) && is_numeric($k['persentase']) && $k['persentase'] > 0) {
            $valid_kandidat[] = $k;
        }
    }

    if (empty($valid_kandidat)) {
        return "background: conic-gradient(#e0e0e0 0deg 360deg);";
    }

    $conic_gradient   = "conic-gradient(";
    $current_degree   = 0;
    $total_percentage = 0;

    foreach ($valid_kandidat as $k) {
        $total_percentage += $k['persentase'];
    }

    for ($i = 0; $i < count($valid_kandidat); $i++) {
        $degree      = ($valid_kandidat[$i]['persentase'] / 100) * 360;
        $next_degree = $current_degree + $degree;
        $color       = $colors[$i % count($colors)];

        $conic_gradient .= "{$color} {$current_degree}deg {$next_degree}deg";

        if ($i < count($valid_kandidat) - 1) {
            $conic_gradient .= ", ";
        }

        $current_degree = $next_degree;
    }

    if ($total_percentage < 100) {
        $remaining_degree = ((100 - $total_percentage) / 100) * 360;
        if ($remaining_degree > 0) {
            $next_degree     = $current_degree + $remaining_degree;
            $conic_gradient .= ", #e0e0e0 {$current_degree}deg {$next_degree}deg";
        }
    }

    $conic_gradient .= ")";

    return "background: {$conic_gradient};";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Rekap Suara</title>
  <link rel="stylesheet" href="../css/dashboard-panitia.css">
  <link rel="stylesheet" href="../css/rekap.css">
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
        <a href="logout.php" class="btn-logout">
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
      <div class="side-logo">Menu Panitia</div>
      <ul>
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Beranda</a></li>
        <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Jadwal Pemilihan</a></li>
        <li><a href="data-pemilih.php"><i class="fas fa-users"></i> Data Warga</a></li>
        <li><a href="kandidat.php"><i class="fas fa-user-tie"></i> Data Kandidat</a></li>
        <li><a href="rekap.php" class="active"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil.php"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <div class="content-page">
        <div class="page-header">
          <div class="real-time-badge">
            <i class="fas fa-circle"></i> Data Real-time
          </div>
          <h1>Rekap Suara Real-time</h1>
          <p>Pemilihan RT/RW Perumahahan Griya Harmoni</p>
        </div>

        <div class="summary-stats">
          <div class="stat-card">
            <h3><?php echo number_format($totalPemilih); ?></h3>
            <p>Pemilih Terdaftar</p>
          </div>
          <div class="stat-card">
            <h3><?php echo number_format($sudahMemilih); ?></h3>
            <p>Sudah Memilih</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $persentase; ?>%</h3>
            <p>Tingkat Partisipasi</p>
          </div>
          <div class="stat-card">
            <h3><?php echo number_format($totalKandidat); ?></h3>
            <p>Kandidat Aktif</p>
          </div>
        </div>

        <div class="progress-section">
          <h3>Progress Pemilihan per Wilayah</h3>
          <?php foreach ($wilayah_results as $wilayah_id => $wilayah): ?>
          <div class="progress-item">
            <div class="progress-header">
              <span class="progress-label"><?php echo htmlspecialchars($wilayah['nama_wilayah']); ?></span>
              <span class="progress-percentage"><?php echo $wilayah['persentase']; ?>%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: <?php echo $wilayah['persentase']; ?>%;"></div>
            </div>
            <div class="progress-details">
              <span><?php echo $wilayah['sudah_memilih']; ?> dari <?php echo $wilayah['total_warga']; ?> warga</span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="charts-container">
          <?php foreach ($wilayah_results as $wilayah_id => $wilayah): ?>
          <div class="chart-card">
            <div class="donut-container">
              <div class="donut" style="<?php 
                if (!empty($wilayah['kandidat'])) {
                    echo generateDonutChart($wilayah['kandidat']);
                } else {
                    echo 'background: conic-gradient(#e0e0e0 0deg 360deg);';
                }
              ?>"></div>
            </div>
            <h4><?php echo htmlspecialchars($wilayah['nama_wilayah']); ?></h4>
            <ul class="candidate-list">
              <?php if (!empty($wilayah['kandidat'])): ?>
                <?php 
                $kandidat_displayed = 0;
                foreach ($wilayah['kandidat'] as $kandidat): 
                    if ($kandidat_displayed >= 3) break;

                    $nama        = $kandidat['nama'] ?? 'Tidak diketahui';
                    $total_suara = $kandidat['total_suara'] ?? 0;
                    $persen_k    = $kandidat['persentase'] ?? 0;
                ?>
                <li>
                  <?php echo htmlspecialchars($nama); ?>
                  <span class="vote-count">
                    <?php echo number_format($total_suara); ?> suara 
                    (<?php echo $persen_k; ?>%)
                  </span>
                </li>
                <?php 
                    $kandidat_displayed++;
                endforeach; 
                ?>
                <?php if (count($wilayah['kandidat']) > 3): ?>
                <li style="text-align: center; font-style: italic; color: #666;">
                  + <?php echo count($wilayah['kandidat']) - 3; ?> kandidat lainnya
                </li>
                <?php endif; ?>
              <?php else: ?>
                <li>Belum ada data <span class="vote-count">0 suara (0%)</span></li>
                <li>Belum ada data <span class="vote-count">0 suara (0%)</span></li>
                <li>Belum ada data <span class="vote-count">0 suara (0%)</span></li>
              <?php endif; ?>
            </ul>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="update-info">
          <p><i class="fas fa-sync-alt"></i> Data diperbarui setiap 30 detik secara otomatis</p>
          <button class="btn-refresh" onclick="location.reload()">
            <i class="fas fa-redo"></i> Refresh Sekarang
          </button>
        </div>

        <div class="actions">
          <button class="btn btn-secondary" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Kembali
          </button>
          <button class="btn btn-primary" onclick="printRekap()">
            <i class="fas fa-print"></i> Cetak Rekap
          </button>
        </div>
      </div>

      <footer>
        <div class="footer-content">
          <p>© 2025 SipeL • Sistem Pemilihan Elektronik RT/RW</p>
        </div>
      </footer>
    </main>
  </div>

  <script>
    // Auto refresh setiap 30 detik
    setTimeout(function() {
      location.reload();
    }, 30000);

    // Hamburger Menu Toggle
    document.addEventListener('DOMContentLoaded', function() {
        const hamburger      = document.getElementById('hamburger');
        const sidebar        = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        if (!hamburger || !sidebar) return;

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
    });

    function printRekap() {
        window.print();
    }
  </script>
</body>
</html>
