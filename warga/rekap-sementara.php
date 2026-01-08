<?php
require_once 'guard-warga.php'; // sudah: session_start + db_connect + cek role+nik

date_default_timezone_set('Asia/Jakarta');

// Warna untuk bullet list kandidat (tampilan tetap sama)
$chart_colors = ['#27AE60', '#6FCF97', '#2D9CDB'];

// Ambil periode aktif
$periode_aktif = null;
$sql_periode = "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1";
$result_periode = $conn->query($sql_periode);
if ($result_periode && $result_periode->num_rows > 0) {
    $periode_aktif = $result_periode->fetch_assoc();
}

// Fungsi untuk mendapatkan rekap sementara per wilayah
function getRekapByWilayah($conn, $id_periode, $jenis, $rt = null, $rw = null) {
    $total_suara = 0;
    $kandidat_list = [];

    if ($jenis === 'RT') {
        $query = "
            SELECT
                k.id_calon,
                k.nama,
                k.rt,
                k.rw,
                k.foto,
                COUNT(v.id_calon) as jumlah_suara
            FROM kandidat k
            LEFT JOIN voting v 
              ON k.id_calon = v.id_calon 
             AND v.id_periode = ?
            WHERE k.id_periode = ?
              AND k.rt = ?
              AND k.rw = ?
            GROUP BY k.id_calon, k.nama, k.rt, k.rw, k.foto
            ORDER BY jumlah_suara DESC, k.id_calon ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiss", $id_periode, $id_periode, $rt, $rw);
        $stmt->execute();
        $result = $stmt->get_result();

        $counter = 1;
        while ($row = $result->fetch_assoc()) {
            if (empty($row['foto'])) {
                $rt_fmt = str_pad((string)$rt, 2, '0', STR_PAD_LEFT);
                $foto_nama = "rt{$rt_fmt}_calon{$counter}.jpg";
                $row['foto'] = "../assets/kandidat/" . $foto_nama;
            }

            $kandidat_list[] = $row;
            $total_suara += (int)$row['jumlah_suara'];
            $counter++;
        }
        $stmt->close();

    } elseif ($jenis === 'RW') {
        $query = "
            SELECT
                k.id_calon,
                k.nama,
                k.rt,
                k.rw,
                k.foto,
                COUNT(v.id_calon) as jumlah_suara
            FROM kandidat k
            LEFT JOIN voting v 
              ON k.id_calon = v.id_calon 
             AND v.id_periode = ?
            WHERE k.id_periode = ?
              AND k.rw = ?
              AND (k.rt IS NULL OR k.rt = '' OR k.rt = '0')
            GROUP BY k.id_calon, k.nama, k.rt, k.rw, k.foto
            ORDER BY jumlah_suara DESC, k.id_calon ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $id_periode, $id_periode, $rw);
        $stmt->execute();
        $result = $stmt->get_result();

        $counter = 1;
        while ($row = $result->fetch_assoc()) {
            if (empty($row['foto'])) {
                $rw_fmt = str_pad((string)$rw, 2, '0', STR_PAD_LEFT);
                $foto_nama = "rw{$rw_fmt}_calon{$counter}.jpg";
                $row['foto'] = "../assets/kandidat/" . $foto_nama;
            }

            $kandidat_list[] = $row;
            $total_suara += (int)$row['jumlah_suara'];
            $counter++;
        }
        $stmt->close();
    }

    // Hitung persentase
    foreach ($kandidat_list as &$kandidat) {
        $kandidat['persentase'] = $total_suara > 0
            ? round(((int)$kandidat['jumlah_suara'] / $total_suara) * 100, 2)
            : 0;
    }
    unset($kandidat);

    return [
        'jenis' => $jenis,
        'rt' => $rt,
        'rw' => $rw,
        'kandidat_list' => $kandidat_list,
        'total_suara' => $total_suara,
        'pemimpin' => !empty($kandidat_list) ? $kandidat_list[0] : null
    ];
}

// Hitung total suara dan partisipasi untuk seluruh sistem
function getTotalStatistik($conn, $id_periode) {
    $statistik = [
        'total_suara' => 0,
        'total_pemilih' => 0,
        'partisipasi' => 0
    ];

    $sql_total_suara = "SELECT COUNT(*) as total FROM voting WHERE id_periode = ?";
    $stmt = $conn->prepare($sql_total_suara);
    $stmt->bind_param("i", $id_periode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $statistik['total_suara'] = (int)($row['total'] ?? 0);
    $stmt->close();

    $sql_total_pemilih = "SELECT COUNT(*) as total FROM warga";
    $result = $conn->query($sql_total_pemilih);
    $row = $result ? $result->fetch_assoc() : null;
    $statistik['total_pemilih'] = (int)($row['total'] ?? 0);

    $statistik['partisipasi'] = $statistik['total_pemilih'] > 0
        ? round(($statistik['total_suara'] / $statistik['total_pemilih']) * 100, 2)
        : 0;

    return $statistik;
}

// Status wilayah (RT/RW) dari tabel jadwal+wilayah
function getStatusWilayah($conn, $id_periode, $jenis, $kode) {
    $now = new DateTime('now', new DateTimeZone('Asia/Jakarta'));

    $status = 'Belum Dimulai';
    $status_class = 'status-upcoming';

    if ($jenis === 'RT') {
        $sql = "
            SELECT j.tanggal_mulai, j.tanggal_selesai
            FROM jadwal j
            JOIN wilayah w ON w.id_wilayah = j.id_wilayah
            WHERE j.id_periode = ?
              AND w.jenis = 'RT'
              AND w.rt = ?
            ORDER BY j.tanggal_mulai ASC
            LIMIT 1
        ";
    } else {
        $sql = "
            SELECT j.tanggal_mulai, j.tanggal_selesai
            FROM jadwal j
            JOIN wilayah w ON w.id_wilayah = j.id_wilayah
            WHERE j.id_periode = ?
              AND w.jenis = 'RW'
              AND w.rw = ?
            ORDER BY j.tanggal_mulai ASC
            LIMIT 1
        ";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $id_periode, $kode);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $mulai = new DateTime($row['tanggal_mulai'], new DateTimeZone('Asia/Jakarta'));
        $selesai = new DateTime($row['tanggal_selesai'], new DateTimeZone('Asia/Jakarta'));

        if ($now < $mulai) {
            $status = 'Belum Dimulai';
            $status_class = 'status-upcoming';
        } elseif ($now > $selesai) {
            $status = 'Selesai';
            $status_class = 'status-completed';
        } else {
            $status = 'Berlangsung';
            $status_class = 'status-active';
        }
    }

    $stmt->close();
    return ['status' => $status, 'class' => $status_class];
}

// Ambil data rekap
$rekap_rt01 = $rekap_rt02 = $rekap_rt03 = $rekap_rw01 = [];
$total_statistik = ['total_suara' => 0, 'total_pemilih' => 0, 'partisipasi' => 0];

$status_rt01 = ['status' => 'Belum Dimulai', 'class' => 'status-upcoming'];
$status_rt02 = ['status' => 'Belum Dimulai', 'class' => 'status-upcoming'];
$status_rt03 = ['status' => 'Belum Dimulai', 'class' => 'status-upcoming'];
$status_rw01 = ['status' => 'Belum Dimulai', 'class' => 'status-upcoming'];

if ($periode_aktif) {
    $id_periode = (int)$periode_aktif['id_periode'];

    $rekap_rt01 = getRekapByWilayah($conn, $id_periode, 'RT', '01', '01');
    $rekap_rt02 = getRekapByWilayah($conn, $id_periode, 'RT', '02', '01');
    $rekap_rt03 = getRekapByWilayah($conn, $id_periode, 'RT', '03', '01');
    $rekap_rw01 = getRekapByWilayah($conn, $id_periode, 'RW', null, '01');

    $total_statistik = getTotalStatistik($conn, $id_periode);

    $status_rt01 = getStatusWilayah($conn, $id_periode, 'RT', '01');
    $status_rt02 = getStatusWilayah($conn, $id_periode, 'RT', '02');
    $status_rt03 = getStatusWilayah($conn, $id_periode, 'RT', '03');
    $status_rw01 = getStatusWilayah($conn, $id_periode, 'RW', '01');
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Rekap Sementara</title>
  <link rel="stylesheet" href="../css/rekap-sementara.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <span>Halo, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Warga'); ?></span>
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
        <li><a href="history.php"><i class="fas fa-history"></i> History Voting</a></li>
        <li><a href="rekap-sementara.php" class="active"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil-akhir.php"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
        <li><a href="ubah-password.php"><i class="fas fa-key"></i>Ubah Password</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <section class="page-header">
        <div class="header-content">
          <h1>Rekap Sementara</h1>
          <h3>Perumahan Griya Harmoni</h3>
          <p>Pantau perkembangan hasil pemilihan RT/RW secara real-time</p>

          <?php if ($periode_aktif): ?>
          <div class="periode-info">
            <span class="periode-badge">
              <i class="fas fa-calendar"></i> Periode: <?php echo htmlspecialchars($periode_aktif['nama_periode']); ?>
            </span>
            <span class="last-update">
              <i class="fas fa-sync-alt"></i> Terakhir diperbarui: <?php echo date('d-m-Y H:i:s'); ?>
            </span>
          </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="rekap-content">
        <div class="filter-section">
          <div class="filter-group">
            <label for="jabatan-filter">Pilih Jabatan:</label>
            <select id="jabatan-filter" class="filter-select">
              <option value="all">Semua Jabatan</option>
              <option value="rt01">RT 01</option>
              <option value="rt02">RT 02</option>
              <option value="rt03">RT 03</option>
              <option value="rw01">RW 01</option>
            </select>
          </div>
        </div>

        <div class="summary-stats">
          <div class="stat-card">
            <h3><?php echo $total_statistik['total_suara']; ?></h3>
            <p>Total Suara</p>
          </div>
          <div class="stat-card">
            <h3><?php echo $total_statistik['partisipasi']; ?>%</h3>
            <p>Partisipasi</p>
          </div>
        </div>

        <div class="charts-container">
          <!-- RT 01 -->
          <div class="jabatan-section" data-jabatan="rt01">
            <div class="chart-card">
              <div class="chart-header">
                <h3>RT 01</h3>
                <span class="status-badge <?php echo $status_rt01['class']; ?>">
                  <?php echo $status_rt01['status']; ?>
                </span>
              </div>

              <?php if (!empty($rekap_rt01['kandidat_list'])): ?>
              <div class="chart-wrapper">
                <canvas id="chartRt01" width="200" height="200"></canvas>
                <div class="chart-total">
                  <span class="total-votes"><?php echo $rekap_rt01['total_suara']; ?></span>
                  <span class="votes-label">suara</span>
                </div>
              </div>

              <div class="candidate-list">
                <?php $color_index = 0; ?>
                <?php foreach ($rekap_rt01['kandidat_list'] as $kandidat): ?>
                <div class="candidate-item">
                  <span class="candidate-color" style="background-color: <?php echo $chart_colors[$color_index % count($chart_colors)]; ?>"></span>
                  <span class="candidate-name">
                    <?php echo htmlspecialchars($kandidat['nama']); ?>
                    <?php if ($kandidat === $rekap_rt01['pemimpin'] && $rekap_rt01['total_suara'] > 0): ?>
                      <span class="leader-badge"><i class="fas fa-crown"></i> Unggul</span>
                    <?php endif; ?>
                  </span>
                  <span class="vote-count">
                    <?php echo (int)$kandidat['jumlah_suara']; ?> suara
                    (<?php echo $kandidat['persentase']; ?>%)
                  </span>
                </div>
                <?php $color_index++; ?>
                <?php endforeach; ?>
              </div>
              <?php else: ?>
              <div class="donut-container">
                <div class="donut" style="background: conic-gradient(#e0e0e0 0deg 360deg);"></div>
                <div class="donut-center">
                  <span class="total-votes">0</span>
                  <span class="votes-label">suara</span>
                </div>
              </div>
              <div class="candidate-list">
                <div class="candidate-item">
                  <span class="candidate-name">Belum ada data</span>
                  <span class="vote-count">0 suara</span>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- RT 02 -->
          <div class="jabatan-section" data-jabatan="rt02">
            <div class="chart-card">
              <div class="chart-header">
                <h3>RT 02</h3>
                <span class="status-badge <?php echo $status_rt02['class']; ?>">
                  <?php echo $status_rt02['status']; ?>
                </span>
              </div>

              <?php if (!empty($rekap_rt02['kandidat_list'])): ?>
              <div class="chart-wrapper">
                <canvas id="chartRt02" width="200" height="200"></canvas>
                <div class="chart-total">
                  <span class="total-votes"><?php echo $rekap_rt02['total_suara']; ?></span>
                  <span class="votes-label">suara</span>
                </div>
              </div>

              <div class="candidate-list">
                <?php $color_index = 0; ?>
                <?php foreach ($rekap_rt02['kandidat_list'] as $kandidat): ?>
                <div class="candidate-item">
                  <span class="candidate-color" style="background-color: <?php echo $chart_colors[$color_index % count($chart_colors)]; ?>"></span>
                  <span class="candidate-name">
                    <?php echo htmlspecialchars($kandidat['nama']); ?>
                    <?php if ($kandidat === $rekap_rt02['pemimpin'] && $rekap_rt02['total_suara'] > 0): ?>
                      <span class="leader-badge"><i class="fas fa-crown"></i> Unggul</span>
                    <?php endif; ?>
                  </span>
                  <span class="vote-count">
                    <?php echo (int)$kandidat['jumlah_suara']; ?> suara
                    (<?php echo $kandidat['persentase']; ?>%)
                  </span>
                </div>
                <?php $color_index++; ?>
                <?php endforeach; ?>
              </div>
              <?php else: ?>
              <div class="donut-container">
                <div class="donut" style="background: conic-gradient(#e0e0e0 0deg 360deg);"></div>
                <div class="donut-center">
                  <span class="total-votes">0</span>
                  <span class="votes-label">suara</span>
                </div>
              </div>
              <div class="candidate-list">
                <div class="candidate-item">
                  <span class="candidate-name">Belum ada data</span>
                  <span class="vote-count">0 suara</span>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- RT 03 -->
          <div class="jabatan-section" data-jabatan="rt03">
            <div class="chart-card">
              <div class="chart-header">
                <h3>RT 03</h3>
                <span class="status-badge <?php echo $status_rt03['class']; ?>">
                  <?php echo $status_rt03['status']; ?>
                </span>
              </div>

              <?php if (!empty($rekap_rt03['kandidat_list'])): ?>
              <div class="chart-wrapper">
                <canvas id="chartRt03" width="200" height="200"></canvas>
                <div class="chart-total">
                  <span class="total-votes"><?php echo $rekap_rt03['total_suara']; ?></span>
                  <span class="votes-label">suara</span>
                </div>
              </div>

              <div class="candidate-list">
                <?php $color_index = 0; ?>
                <?php foreach ($rekap_rt03['kandidat_list'] as $kandidat): ?>
                <div class="candidate-item">
                  <span class="candidate-color" style="background-color: <?php echo $chart_colors[$color_index % count($chart_colors)]; ?>"></span>
                  <span class="candidate-name">
                    <?php echo htmlspecialchars($kandidat['nama']); ?>
                    <?php if ($kandidat === $rekap_rt03['pemimpin'] && $rekap_rt03['total_suara'] > 0): ?>
                      <span class="leader-badge"><i class="fas fa-crown"></i> Unggul</span>
                    <?php endif; ?>
                  </span>
                  <span class="vote-count">
                    <?php echo (int)$kandidat['jumlah_suara']; ?> suara
                    (<?php echo $kandidat['persentase']; ?>%)
                  </span>
                </div>
                <?php $color_index++; ?>
                <?php endforeach; ?>
              </div>
              <?php else: ?>
              <div class="donut-container">
                <div class="donut" style="background: conic-gradient(#e0e0e0 0deg 360deg);"></div>
                <div class="donut-center">
                  <span class="total-votes">0</span>
                  <span class="votes-label">suara</span>
                </div>
              </div>
              <div class="candidate-list">
                <div class="candidate-item">
                  <span class="candidate-name">Belum ada data</span>
                  <span class="vote-count">0 suara</span>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- RW 01 -->
          <div class="jabatan-section" data-jabatan="rw01">
            <div class="chart-card">
              <div class="chart-header">
                <h3>RW 01</h3>
                <span class="status-badge <?php echo $status_rw01['class']; ?>">
                  <?php echo $status_rw01['status']; ?>
                </span>
              </div>

              <?php if (!empty($rekap_rw01['kandidat_list'])): ?>
              <div class="chart-wrapper">
                <canvas id="chartRw01" width="200" height="200"></canvas>
                <div class="chart-total">
                  <span class="total-votes"><?php echo $rekap_rw01['total_suara']; ?></span>
                  <span class="votes-label">suara</span>
                </div>
              </div>

              <div class="candidate-list">
                <?php $color_index = 0; ?>
                <?php foreach ($rekap_rw01['kandidat_list'] as $kandidat): ?>
                <div class="candidate-item">
                  <span class="candidate-color" style="background-color: <?php echo $chart_colors[$color_index % count($chart_colors)]; ?>"></span>
                  <span class="candidate-name">
                    <?php echo htmlspecialchars($kandidat['nama']); ?>
                    <?php if ($kandidat === $rekap_rw01['pemimpin'] && $rekap_rw01['total_suara'] > 0): ?>
                      <span class="leader-badge"><i class="fas fa-crown"></i> Unggul</span>
                    <?php endif; ?>
                  </span>
                  <span class="vote-count">
                    <?php echo (int)$kandidat['jumlah_suara']; ?> suara
                    (<?php echo $kandidat['persentase']; ?>%)
                  </span>
                </div>
                <?php $color_index++; ?>
                <?php endforeach; ?>
              </div>
              <?php else: ?>
              <div class="donut-container">
                <div class="donut" style="background: conic-gradient(#e0e0e0 0deg 360deg);"></div>
                <div class="donut-center">
                  <span class="total-votes">0</span>
                  <span class="votes-label">suara</span>
                </div>
              </div>
              <div class="candidate-list">
                <div class="candidate-item">
                  <span class="candidate-name">Belum ada data</span>
                  <span class="vote-count">0 suara</span>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="info-section">
          <div class="info-card">
            <h3><i class="fas fa-info-circle"></i> Informasi Rekap Sementara</h3>
            <div class="info-content">
              <p>• Rekap sementara menampilkan perkembangan hasil pemilihan secara real-time</p>
              <p>• Data akan diperbarui secara otomatis selama pemilihan berlangsung</p>
              <p>• Rekap hanya menampilkan jumlah suara tanpa identitas pemilih</p>
              <p>• Hasil akhir akan diumumkan setelah periode voting berakhir</p>
              <p class="update-info"><i class="fas fa-sync-alt"></i> Data diperbarui setiap 30 detik</p>
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

        const jabatanFilter = document.getElementById('jabatan-filter');
        const jabatanSections = document.querySelectorAll('.jabatan-section');

        if (jabatanFilter) {
            jabatanFilter.addEventListener('change', function() {
                const selectedValue = this.value;

                jabatanSections.forEach(section => {
                    if (selectedValue === 'all' || section.dataset.jabatan === selectedValue) {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
            });
        }

        const chartColors = [ '#0ebbffff','#f598ffff','#ffe102ff','#84CC16', '#14B8A6' ,'#3B82F6', '#22C55E',  '#14B8A6' ];

        <?php if (!empty($rekap_rt01['kandidat_list'])): ?>
        createChart('chartRt01',
            <?php echo json_encode(array_column($rekap_rt01['kandidat_list'], 'nama')); ?>,
            <?php echo json_encode(array_column($rekap_rt01['kandidat_list'], 'jumlah_suara')); ?>,
            chartColors
        );
        <?php endif; ?>

        <?php if (!empty($rekap_rt02['kandidat_list'])): ?>
        createChart('chartRt02',
            <?php echo json_encode(array_column($rekap_rt02['kandidat_list'], 'nama')); ?>,
            <?php echo json_encode(array_column($rekap_rt02['kandidat_list'], 'jumlah_suara')); ?>,
            chartColors
        );
        <?php endif; ?>

        <?php if (!empty($rekap_rt03['kandidat_list'])): ?>
        createChart('chartRt03',
            <?php echo json_encode(array_column($rekap_rt03['kandidat_list'], 'nama')); ?>,
            <?php echo json_encode(array_column($rekap_rt03['kandidat_list'], 'jumlah_suara')); ?>,
            chartColors
        );
        <?php endif; ?>

        <?php if (!empty($rekap_rw01['kandidat_list'])): ?>
        createChart('chartRw01',
            <?php echo json_encode(array_column($rekap_rw01['kandidat_list'], 'nama')); ?>,
            <?php echo json_encode(array_column($rekap_rw01['kandidat_list'], 'jumlah_suara')); ?>,
            chartColors
        );
        <?php endif; ?>

        setTimeout(function() {
            window.location.reload();
        }, 30000);
    });

    function createChart(canvasId, labels, data, colors) {
        const el = document.getElementById(canvasId);
        if (!el) return;

        const ctx = el.getContext('2d');
        const chartData = {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 0,
                hoverOffset: 15
            }]
        };

        new Chart(ctx, {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: false,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${value} suara (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
  </script>
</body>
</html>
