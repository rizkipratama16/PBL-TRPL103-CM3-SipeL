<?php
session_start();

// Cek sudah login & role panitia
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'panitia') {
    header('Location: login.php');
    exit;
}

require_once '../db_connect.php';

// Ambil periode aktif
$periode_aktif = null;
$sql_periode = "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1";
$result_periode = $conn->query($sql_periode);
if ($result_periode && $result_periode->num_rows > 0) {
    $periode_aktif = $result_periode->fetch_assoc();
}

// Cek apakah periode sudah selesai
$is_final = false;
$current_date = date('Y-m-d');
if ($periode_aktif && !empty($periode_aktif['tanggal_selesai']) && $current_date > $periode_aktif['tanggal_selesai']) {
    $is_final = true;
}

// ===============================
// FUNGSI: HASIL AKHIR PER WILAYAH
// ===============================
function getHasilAkhirByWilayah($conn, $id_periode, $jenis, $rt = null, $rw = null) {
    $hasil = [];

    if ($jenis == 'RT') {
        $query = "
            SELECT 
                k.id_calon,
                k.nama,
                k.rt,
                k.rw,
                k.foto,
                k.visi,
                k.misi,
                COUNT(v.id_calon) as total_suara,
                ROUND(COUNT(v.id_calon) * 100.0 / NULLIF((
                    SELECT COUNT(*) FROM voting v2 
                    JOIN warga w2 ON v2.nik = w2.nik 
                    WHERE v2.id_periode = ? AND w2.rt = k.rt AND w2.rw = k.rw
                ),0), 2) as persentase
            FROM kandidat k
            LEFT JOIN voting v ON k.id_calon = v.id_calon AND v.id_periode = ?
            WHERE k.id_periode = ? AND k.rt = ? AND k.rw = ?
            GROUP BY k.id_calon, k.nama, k.rt, k.rw, k.foto, k.visi, k.misi
            ORDER BY total_suara DESC, k.nama ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiiss", $id_periode, $id_periode, $id_periode, $rt, $rw);
        $stmt->execute();
        $result = $stmt->get_result();

        $kandidat_list = [];
        $max_suara     = 0;
        $total_voting  = 0;

        while ($row = $result->fetch_assoc()) {
            // Foto default kalau kosong
            if (empty($row['foto'])) {
                $foto_nama  = "rt" . $rt . "_calon" . (($row['id_calon'] % 3) + 1) . ".jpg";
                $row['foto'] = "../assets/kandidat/" . $foto_nama;
            } else {
                // Kalau cuma nama file di DB
                if (!preg_match('#^(https?://|/|\.\./)#', $row['foto'])) {
                    $row['foto'] = "../assets/kandidat/" . $row['foto'];
                }
            }

            $row['total_suara'] = (int)$row['total_suara'];
            $row['persentase']  = (float)($row['persentase'] ?? 0);
            $kandidat_list[]    = $row;

            if ($row['total_suara'] > $max_suara) $max_suara = $row['total_suara'];
            $total_voting += $row['total_suara'];
        }

        // Pemenang (bisa seri)
        $pemenang_list = [];
        foreach ($kandidat_list as $kandidat) {
            if ($kandidat['total_suara'] === $max_suara && $max_suara > 0) {
                $pemenang_list[] = $kandidat;
            }
        }

        $hasil = [
            'jenis'         => 'RT',
            'rt'            => $rt,
            'rw'            => $rw,
            'kandidat_list' => $kandidat_list,
            'pemenang_list' => $pemenang_list,
            'total_voting'  => $total_voting,
            'max_suara'     => $max_suara
        ];

        $stmt->close();

    } elseif ($jenis == 'RW') {
        $query = "
            SELECT 
                k.id_calon,
                k.nama,
                k.rt,
                k.rw,
                k.foto,
                k.visi,
                k.misi,
                COUNT(v.id_calon) as total_suara,
                ROUND(COUNT(v.id_calon) * 100.0 / NULLIF((
                    SELECT COUNT(*) FROM voting v2 
                    JOIN warga w2 ON v2.nik = w2.nik 
                    WHERE v2.id_periode = ? AND w2.rw = k.rw
                ),0), 2) as persentase
            FROM kandidat k
            LEFT JOIN voting v ON k.id_calon = v.id_calon AND v.id_periode = ?
            WHERE k.id_periode = ? AND k.rw = ? AND (k.rt IS NULL OR k.rt = '' OR k.rt = '0')
            GROUP BY k.id_calon, k.nama, k.rt, k.rw, k.foto, k.visi, k.misi
            ORDER BY total_suara DESC, k.nama ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiis", $id_periode, $id_periode, $id_periode, $rw);
        $stmt->execute();
        $result = $stmt->get_result();

        $kandidat_list = [];
        $max_suara     = 0;
        $total_voting  = 0;

        while ($row = $result->fetch_assoc()) {
            if (empty($row['foto'])) {
                $foto_nama  = "rw" . $rw . "_calon" . ((($row['id_calon'] - 10) % 3) + 1) . ".jpg";
                $row['foto'] = "../assets/kandidat/" . $foto_nama;
            } else {
                if (!preg_match('#^(https?://|/|\.\./)#', $row['foto'])) {
                    $row['foto'] = "../assets/kandidat/" . $row['foto'];
                }
            }

            $row['total_suara'] = (int)$row['total_suara'];
            $row['persentase']  = (float)($row['persentase'] ?? 0);
            $kandidat_list[]    = $row;

            if ($row['total_suara'] > $max_suara) $max_suara = $row['total_suara'];
            $total_voting += $row['total_suara'];
        }

        $pemenang_list = [];
        foreach ($kandidat_list as $kandidat) {
            if ($kandidat['total_suara'] === $max_suara && $max_suara > 0) {
                $pemenang_list[] = $kandidat;
            }
        }

        $hasil = [
            'jenis'         => 'RW',
            'rt'            => null,
            'rw'            => $rw,
            'kandidat_list' => $kandidat_list,
            'pemenang_list' => $pemenang_list,
            'total_voting'  => $total_voting,
            'max_suara'     => $max_suara
        ];

        $stmt->close();
    }

    return $hasil;
}

// ===============================
// FUNGSI: STATISTIK UMUM
// ===============================
function getStatistikUmum($conn, $id_periode = null) {
    $statistik = [
        'total_pemilih' => 0,
        'total_voting'  => 0,
        'partisipasi'   => 0,
        'total_kandidat'=> 0
    ];

    $res = $conn->query("SELECT COUNT(*) AS total FROM warga");
    $row = $res ? $res->fetch_assoc() : [];
    $statistik['total_pemilih'] = (int)($row['total'] ?? 0);

    $sql_voting = $id_periode
        ? "SELECT COUNT(*) AS total FROM voting WHERE id_periode = " . intval($id_periode)
        : "SELECT COUNT(*) AS total FROM voting";
    $res = $conn->query($sql_voting);
    $row = $res ? $res->fetch_assoc() : [];
    $statistik['total_voting'] = (int)($row['total'] ?? 0);

    $sql_kandidat = $id_periode
        ? "SELECT COUNT(*) AS total FROM kandidat WHERE id_periode = " . intval($id_periode)
        : "SELECT COUNT(*) AS total FROM kandidat";
    $res = $conn->query($sql_kandidat);
    $row = $res ? $res->fetch_assoc() : [];
    $statistik['total_kandidat'] = (int)($row['total'] ?? 0);

    if ($statistik['total_pemilih'] > 0) {
        $statistik['partisipasi'] = round(($statistik['total_voting'] / $statistik['total_pemilih']) * 100, 2);
    }

    return $statistik;
}

// ===============================
// AMBIL DATA HASIL / STATISTIK
// ===============================
$hasil_rt01 = $hasil_rt02 = $hasil_rt03 = $hasil_rw01 = [];
$statistik_umum = [];

if ($periode_aktif) {
    $id_periode      = $periode_aktif['id_periode'];
    $hasil_rt01      = getHasilAkhirByWilayah($conn, $id_periode, 'RT', '01', '01');
    $hasil_rt02      = getHasilAkhirByWilayah($conn, $id_periode, 'RT', '02', '01');
    $hasil_rt03      = getHasilAkhirByWilayah($conn, $id_periode, 'RT', '03', '01');
    $hasil_rw01      = getHasilAkhirByWilayah($conn, $id_periode, 'RW', null, '01');
    $statistik_umum  = getStatistikUmum($conn, $id_periode);
}

// ===============================
// FUNGSI: DATA CHART DONUT (CSS)
// ===============================
function generateChartData($hasil) {
    if (empty($hasil) || empty($hasil['kandidat_list'])) {
        return 'conic-gradient(#e0e0e0 0deg 360deg)';
    }

    $total = (int)($hasil['total_voting'] ?? 0);
    if ($total <= 0) {
        return 'conic-gradient(#e0e0e0 0deg 360deg)';
    }

    $colors = ['#4CAF50', '#2196F3', '#FF9800', '#9C27B0', '#E91E63', '#00BCD4'];
    $parts  = [];
    $cur    = 0;

    foreach ($hasil['kandidat_list'] as $i => $k) {
        $suara = (int)($k['total_suara'] ?? 0);
        if ($suara <= 0) continue;

        $deg   = ($suara / $total) * 360;
        $start = $cur;
        $end   = $cur + $deg;
        $color = $colors[$i % count($colors)];

        $parts[] = "{$color} {$start}deg {$end}deg";
        $cur = $end;
    }

    if (empty($parts)) return 'conic-gradient(#e0e0e0 0deg 360deg)';
    if ($cur < 360) $parts[] = "#e0e0e0 {$cur}deg 360deg";

    return 'conic-gradient(' . implode(', ', $parts) . ')';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Hasil Akhir (Admin)</title>
  <link rel="stylesheet" href="../css/dashboard-panitia.css">
  <link rel="stylesheet" href="../css/hasil.css">
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
          <img src="../assets/logo-sipelV3.png" alt="SipeL Logo" class="logo-img">
          <span>SipeL</span>
        </a>
      </div>

      <div class="nav-user">
        <span>Halo, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Admin'); ?></span>
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
      <div class="side-logo">Menu Admin</div>
      <ul>
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Beranda</a></li>
        <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Jadwal Pemilihan</a></li>
        <li><a href="data-pemilih.php"><i class="fas fa-users"></i> Data Warga</a></li>
        <li><a href="kandidat.php"><i class="fas fa-user-tie"></i> Data Kandidat</a></li>
        <li><a href="rekap.php"><i class="fas fa-chart-bar"></i> Rekap Sementa</a></li>
        <li><a href="hasil.php" class="active"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <div class="content-page">
        <div class="page-header">
          <h1>Hasil Akhir Pemilihan</h1>
          <p>Pemilihan RT/RW Perumahan Griya Harmoni</p>

          <?php if ($periode_aktif): ?>
          <div class="periode-info">
            <span class="periode-badge">
              <i class="fas fa-calendar"></i> <?php echo htmlspecialchars($periode_aktif['nama_periode']); ?>
            </span>
            <span class="status-badge <?php echo $is_final ? 'status-active' : 'status-upcoming'; ?>">
              <?php echo $is_final ? 'HASIL FINAL' : 'BELUM FINAL'; ?>
            </span>
            <span class="last-update">
              <i class="fas fa-clock"></i> <?php echo date('d-m-Y H:i:s'); ?>
            </span>
          </div>
          <?php endif; ?>
        </div>

        <!-- PERKIRAAN PEMENANG -->
        <div class="winner-announcement">
          <div class="winner-badge">
            <i class="fas fa-trophy"></i>
            <?php echo $is_final ? 'PEMENANG RESMI PEMILIHAN' : 'PERKIRAAN PEMENANG SEMENTARA PER WILAYAH'; ?>
          </div>

          <?php
          $wilayah_pemenang = [
            'RT 01' => $hasil_rt01,
            'RT 02' => $hasil_rt02,
            'RT 03' => $hasil_rt03,
            'RW 01' => $hasil_rw01,
          ];
          ?>

          <div class="winner-grid">
            <?php foreach ($wilayah_pemenang as $label => $hasil): ?>
              <div class="winner-card">
                <h3><?php echo $label; ?></h3>
                <?php if (!empty($hasil) && !empty($hasil['pemenang_list'])): ?>
                  <ul class="winner-list">
                    <?php foreach ($hasil['pemenang_list'] as $p): ?>
                      <li>
                        <span class="winner-name"><?php echo htmlspecialchars($p['nama']); ?></span>
                        <span class="winner-votes">
                          <?php echo (int)$p['total_suara']; ?> suara
                          (<?php echo number_format((float)($p['persentase'] ?? 0), 2); ?>%)
                        </span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <p class="winner-empty">Belum ada pemenang</p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- RINGKASAN -->
        <div class="final-summary">
          <h3>Ringkasan Hasil Akhir</h3>
          <div class="summary-grid">
            <div class="summary-item">
              <h4><?php echo (int)($statistik_umum['total_pemilih'] ?? 0); ?></h4>
              <p>Pemilih Terdaftar</p>
            </div>
            <div class="summary-item">
              <h4><?php echo (int)($statistik_umum['total_voting'] ?? 0); ?></h4>
              <p>Pemilih Menggunakan Hak</p>
            </div>
            <div class="summary-item">
              <h4><?php echo (float)($statistik_umum['partisipasi'] ?? 0); ?>%</h4>
              <p>Tingkat Partisipasi</p>
            </div>
            <div class="summary-item">
              <h4><?php echo (int)($statistik_umum['total_kandidat'] ?? 0); ?></h4>
              <p>Kandidat</p>
            </div>
          </div>
        </div>

        <!-- CHARTS -->
        <div class="charts-container">
          <div class="chart-card">
            <div class="donut-container">
              <div class="donut" style="background: <?php echo generateChartData($hasil_rt01); ?>;"></div>
              <div class="donut-center">
                <span class="total-votes"><?php echo (int)($hasil_rt01['total_voting'] ?? 0); ?></span>
                <span class="votes-label">suara</span>
              </div>
            </div>
            <h4>RT 01</h4>
            <ul class="candidate-list">
              <?php if (!empty($hasil_rt01['kandidat_list'])): ?>
                <?php foreach ($hasil_rt01['kandidat_list'] as $kandidat): ?>
                  <li class="<?php echo (!empty($hasil_rt01['pemenang_list']) && in_array($kandidat, $hasil_rt01['pemenang_list'], true)) ? 'winner-item' : ''; ?>">
                    <?php echo htmlspecialchars($kandidat['nama']); ?>
                    <span class="vote-count"><?php echo (int)$kandidat['total_suara']; ?> suara (<?php echo number_format((float)($kandidat['persentase'] ?? 0), 2); ?>%)</span>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li>Belum ada data <span class="vote-count">0 suara (0%)</span></li>
              <?php endif; ?>
            </ul>
          </div>

          <div class="chart-card">
            <div class="donut-container">
              <div class="donut" style="background: <?php echo generateChartData($hasil_rt02); ?>;"></div>
              <div class="donut-center">
                <span class="total-votes"><?php echo (int)($hasil_rt02['total_voting'] ?? 0); ?></span>
                <span class="votes-label">suara</span>
              </div>
            </div>
            <h4>RT 02</h4>
            <ul class="candidate-list">
              <?php if (!empty($hasil_rt02['kandidat_list'])): ?>
                <?php foreach ($hasil_rt02['kandidat_list'] as $kandidat): ?>
                  <li class="<?php echo (!empty($hasil_rt02['pemenang_list']) && in_array($kandidat, $hasil_rt02['pemenang_list'], true)) ? 'winner-item' : ''; ?>">
                    <?php echo htmlspecialchars($kandidat['nama']); ?>
                    <span class="vote-count"><?php echo (int)$kandidat['total_suara']; ?> suara (<?php echo number_format((float)($kandidat['persentase'] ?? 0), 2); ?>%)</span>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li>Belum ada data <span class="vote-count">0 suara (0%)</span></li>
              <?php endif; ?>
            </ul>
          </div>

          <div class="chart-card">
            <div class="donut-container">
              <div class="donut" style="background: <?php echo generateChartData($hasil_rt03); ?>;"></div>
              <div class="donut-center">
                <span class="total-votes"><?php echo (int)($hasil_rt03['total_voting'] ?? 0); ?></span>
                <span class="votes-label">suara</span>
              </div>
            </div>
            <h4>RT 03</h4>
            <ul class="candidate-list">
              <?php if (!empty($hasil_rt03['kandidat_list'])): ?>
                <?php foreach ($hasil_rt03['kandidat_list'] as $kandidat): ?>
                  <li class="<?php echo (!empty($hasil_rt03['pemenang_list']) && in_array($kandidat, $hasil_rt03['pemenang_list'], true)) ? 'winner-item' : ''; ?>">
                    <?php echo htmlspecialchars($kandidat['nama']); ?>
                    <span class="vote-count"><?php echo (int)$kandidat['total_suara']; ?> suara (<?php echo number_format((float)($kandidat['persentase'] ?? 0), 2); ?>%)</span>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li>Belum ada data <span class="vote-count">0 suara (0%)</span></li>
              <?php endif; ?>
            </ul>
          </div>

          <div class="chart-card">
            <div class="donut-container">
              <div class="donut" style="background: <?php echo generateChartData($hasil_rw01); ?>;"></div>
              <div class="donut-center">
                <span class="total-votes"><?php echo (int)($hasil_rw01['total_voting'] ?? 0); ?></span>
                <span class="votes-label">suara</span>
              </div>
            </div>
            <h4>RW 01</h4>
            <ul class="candidate-list">
              <?php if (!empty($hasil_rw01['kandidat_list'])): ?>
                <?php foreach ($hasil_rw01['kandidat_list'] as $kandidat): ?>
                  <li class="<?php echo (!empty($hasil_rw01['pemenang_list']) && in_array($kandidat, $hasil_rw01['pemenang_list'], true)) ? 'winner-item' : ''; ?>">
                    <?php echo htmlspecialchars($kandidat['nama']); ?>
                    <span class="vote-count"><?php echo (int)$kandidat['total_suara']; ?> suara (<?php echo number_format((float)($kandidat['persentase'] ?? 0), 2); ?>%)</span>
                  </li>
                <?php endforeach; ?>
              <?php else: ?>
                <li>Belum ada data <span class="vote-count">0 suara (0%)</span></li>
              <?php endif; ?>
            </ul>
          </div>
        </div>

        <div class="congrats-message">
          <?php if ($is_final): ?>
          <div class="final-notice">
            <i class="fas fa-check-circle"></i>
            <h4>HASIL FINAL TELAH DITETAPKAN</h4>
            <p>Hasil di atas adalah hasil akhir pemilihan yang telah diverifikasi dan ditetapkan oleh panitia pemilihan.
               Selamat kepada para pemenang dan terima kasih kepada semua warga yang telah berpartisipasi.</p>
          </div>
          <?php else: ?>
          <p>Hasil akhir akan diumumkan setelah proses pemilihan selesai dan semua suara telah dihitung</p>
          <?php endif; ?>
        </div>

        <div class="export-section">
          <h3>Ekspor Hasil Akhir</h3>
          <div class="export-actions">
            <button class="btn-export pdf" onclick="printResults()">
              <i class="fas fa-file-pdf"></i> Cetak PDF
            </button>
          </div>
        </div>

        <div class="actions">
          <button class="btn btn-secondary" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Kembali
          </button>
          <button class="btn btn-primary" onclick="shareResults()">
            <i class="fas fa-share-alt"></i> Bagikan Hasil
          </button>
          <?php if ($is_final): ?>
          <button class="btn btn-success" onclick="announceWinners()">
            <i class="fas fa-bullhorn"></i> Umumkan Pemenang
          </button>
          <?php endif; ?>
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
  // Hamburger menu
  document.addEventListener('DOMContentLoaded', function () {
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (!hamburger || !sidebar) return;

    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('active');
      sidebar.classList.toggle('active');
      if (overlay) overlay.classList.toggle('active');
    });

    if (overlay) {
      overlay.addEventListener('click', () => {
        hamburger.classList.remove('active');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
      });
    }
  });

  function printResults() {
    window.print();
  }

  function shareResults() {
    const periode = "<?php echo htmlspecialchars($periode_aktif['nama_periode'] ?? 'Pemilihan RT/RW'); ?>";
    const isFinal = <?php echo $is_final ? 'true' : 'false'; ?>;

    const makeWinnerLine = (label, hasil) => {
      if (!hasil || !hasil.pemenang_list || hasil.pemenang_list.length === 0) {
        return `${label}: Belum ada pemenang`;
      }
      const names = hasil.pemenang_list.map(p => p.nama).join(", ");
      const votes = hasil.pemenang_list
        .map(p => `${p.total_suara} suara (${Number(p.persentase || 0).toFixed(2)}%)`)
        .join(" | ");
      return `${label}: ${names} — ${votes}`;
    };

    const winners = {
      rt01: <?php echo json_encode($hasil_rt01 ?? []); ?>,
      rt02: <?php echo json_encode($hasil_rt02 ?? []); ?>,
      rt03: <?php echo json_encode($hasil_rt03 ?? []); ?>,
      rw01: <?php echo json_encode($hasil_rw01 ?? []); ?>
    };

    const title = `Hasil ${isFinal ? 'Akhir' : 'Sementara'} ${periode}`;
    const url = window.location.href;

    let text = `${title}\n`;
    text += `Status: ${isFinal ? 'FINAL' : 'BELUM FINAL'}\n`;
    text += `Update: <?php echo date('d-m-Y H:i:s'); ?>\n\n`;
    text += `Ringkasan Pemenang:\n`;
    text += `- ${makeWinnerLine('RT 01', winners.rt01)}\n`;
    text += `- ${makeWinnerLine('RT 02', winners.rt02)}\n`;
    text += `- ${makeWinnerLine('RT 03', winners.rt03)}\n`;
    text += `- ${makeWinnerLine('RW 01', winners.rw01)}\n\n`;
    text += `Detail lengkap: ${url}`;

    if (navigator.share) {
      navigator.share({ title, text, url })
        .then(() => showAlert('Berhasil dibagikan!', 'success'))
        .catch(() => showAlert('Berbagi dibatalkan', 'info'));
      return;
    }

    copyToClipboard(text);
  }

  function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text)
        .then(() => showAlert('Teks + link disalin. Tinggal paste ke WA/Telegram.', 'success'))
        .catch(() => legacyCopy(text));
    } else {
      legacyCopy(text);
    }
  }

  function legacyCopy(text) {
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showAlert('Teks + link disalin. Tinggal paste ke WA/Telegram.', 'success');
  }

  // kalau kamu masih pakai showAlert dari sebelumnya, BIARIN.
  // kalau belum ada, tambahin ini:
  function showAlert(message, type = 'info') {
    const existing = document.querySelector('.custom-alert');
    if (existing) existing.remove();

    const alert = document.createElement('div');
    alert.className = `custom-alert alert-${type}`;
    alert.innerHTML = `
      <span>${message}</span>
      <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    document.body.appendChild(alert);

    setTimeout(() => {
      if (alert.parentElement) alert.remove();
    }, 4000);
  }
</script>
