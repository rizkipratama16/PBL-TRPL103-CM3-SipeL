<?php
require_once 'guard-warga.php';

// WAJIB biar waktu server sama kayak waktu lo
date_default_timezone_set('Asia/Jakarta');

/**
 * Normalisasi path foto kandidat:
 * - Jika DB isi "rt01_calon1.jpg" -> jadi "../assets/kandidat/rt01_calon1.jpg"
 * - Jika sudah URL/path -> dipakai apa adanya
 * - Jika kosong -> default
 */
function normalizeFotoKandidat($foto)
{
    $default = "../assets/kandidat/default.jpg";
    $foto = trim((string) $foto);

    if ($foto === '') return $default;

    // sudah berupa URL / path absolut / path relatif
    if (preg_match('#^(https?://|/|\.\./)#', $foto)) return $foto;

    // DB hanya nama file
    return "../assets/kandidat/" . $foto;
}

// =====================
// Ambil periode aktif
// =====================
$periode_aktif = null;
$sql_periode = "SELECT * FROM periode WHERE status = 'aktif' LIMIT 1";
$result_periode = $conn->query($sql_periode);
if ($result_periode && $result_periode->num_rows > 0) {
    $periode_aktif = $result_periode->fetch_assoc();
}

$periode_id = (int)($periode_aktif['id_periode'] ?? 0);

// =====================
// FIX UTAMA: STATUS FINAL HARUS SINKRON KE TABEL jadwal (DATETIME)
// =====================

/**
 * Ambil jadwal per wilayah dari tabel jadwal
 */
function getJadwalWilayah($conn, $id_periode, $id_wilayah)
{
    if ($id_periode <= 0 || $id_wilayah <= 0) {
        return null;
    }

    $sql = "SELECT tanggal_mulai, tanggal_selesai 
            FROM jadwal 
            WHERE id_periode = ? AND id_wilayah = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;

    $stmt->bind_param("ii", $id_periode, $id_wilayah);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

/**
 * Tentukan status wilayah: selesai / berlangsung / belum_mulai
 * dan final = true kalau sekarang >= tanggal_selesai (DATETIME)
 */
function getStatusWilayah($jadwalRow)
{
    $now = time();

    if (!$jadwalRow || empty($jadwalRow['tanggal_mulai']) || empty($jadwalRow['tanggal_selesai'])) {
        return [
            'final' => false,
            'status' => 'belum_ada',
        ];
    }

    $mulai_ts = strtotime($jadwalRow['tanggal_mulai']);
    $selesai_ts = strtotime($jadwalRow['tanggal_selesai']);

    if ($mulai_ts === false || $selesai_ts === false) {
        return [
            'final' => false,
            'status' => 'belum_ada',
        ];
    }

    if ($now >= $selesai_ts) {
        return [
            'final' => true,
            'status' => 'selesai',
        ];
    }

    if ($now >= $mulai_ts && $now < $selesai_ts) {
        return [
            'final' => false,
            'status' => 'berlangsung',
        ];
    }

    return [
        'final' => false,
        'status' => 'belum_mulai',
    ];
}

// Mapping sesuai screenshot lo
$id_wilayah_rt01 = 1;
$id_wilayah_rt02 = 2;
$id_wilayah_rt03 = 3;
$id_wilayah_rw01 = 4;

// Ambil jadwal & status tiap wilayah
$jadwal_rt01 = getJadwalWilayah($conn, $periode_id, $id_wilayah_rt01);
$jadwal_rt02 = getJadwalWilayah($conn, $periode_id, $id_wilayah_rt02);
$jadwal_rt03 = getJadwalWilayah($conn, $periode_id, $id_wilayah_rt03);
$jadwal_rw01 = getJadwalWilayah($conn, $periode_id, $id_wilayah_rw01);

$status_rt01 = getStatusWilayah($jadwal_rt01);
$status_rt02 = getStatusWilayah($jadwal_rt02);
$status_rt03 = getStatusWilayah($jadwal_rt03);
$status_rw01 = getStatusWilayah($jadwal_rw01);

// Final per wilayah (ini yang dipakai badge)
$is_final_rt01 = $status_rt01['final'];
$is_final_rt02 = $status_rt02['final'];
$is_final_rt03 = $status_rt03['final'];
$is_final_rw01 = $status_rw01['final'];

// Final global (buat pengumuman & auto refresh)
$is_final = ($is_final_rt01 && $is_final_rt02 && $is_final_rt03 && $is_final_rw01);

// =====================
// Fungsi pemenang per wilayah
// - Winner cuma 1 (anti seri ganda)
// - Kalau seri: yang menang = last_vote_id paling besar (suara terakhir)
// =====================
function getPemenangByWilayah($conn, $id_periode, $jenis, $rt = null, $rw = null)
{
    $base = [
        'jenis' => $jenis,
        'rt' => $rt,
        'rw' => $rw,
        'kandidat_list' => [],
        'pemenang_list' => [],
        'total_voting' => 0,
        'max_suara' => 0
    ];

    if ((int)$id_periode <= 0) return $base;

    if ($jenis === 'RT') {
        $query = "
            SELECT 
                k.id_calon, k.nama, k.rt, k.rw, k.foto,
                COUNT(v.id_calon) AS total_suara,
                COALESCE(MAX(v.id_voting), 0) AS last_vote_id,
                ROUND(
                    (COUNT(v.id_calon) * 100.0) / NULLIF((
                        SELECT COUNT(*)
                        FROM voting v2
                        JOIN warga w2 ON v2.nik = w2.nik
                        WHERE v2.id_periode = ?
                          AND w2.rt = k.rt
                          AND w2.rw = k.rw
                    ), 0),
                2) AS persentase
            FROM kandidat k
            LEFT JOIN voting v 
                ON k.id_calon = v.id_calon 
               AND v.id_periode = ?
            WHERE k.id_periode = ?
              AND k.rt = ?
              AND k.rw = ?
            GROUP BY k.id_calon, k.nama, k.rt, k.rw, k.foto
            ORDER BY total_suara DESC, last_vote_id DESC, k.nama ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiiss", $id_periode, $id_periode, $id_periode, $rt, $rw);
        $stmt->execute();
        $result = $stmt->get_result();

        $kandidat_list = [];
        $max_suara = 0;
        $total_voting = 0;

        while ($row = $result->fetch_assoc()) {
            $row['foto'] = normalizeFotoKandidat($row['foto'] ?? '');
            $row['total_suara'] = (int)($row['total_suara'] ?? 0);
            $row['last_vote_id'] = (int)($row['last_vote_id'] ?? 0);
            $row['persentase'] = (float)($row['persentase'] ?? 0);

            $kandidat_list[] = $row;

            if ($row['total_suara'] > $max_suara) $max_suara = $row['total_suara'];
            $total_voting += $row['total_suara'];
        }
        $stmt->close();

        $pemenang_list = [];
        if ($max_suara > 0) {
            $top = null;
            foreach ($kandidat_list as $k) {
                if ($k['total_suara'] !== $max_suara) continue;

                if ($top === null) { $top = $k; continue; }

                if ($k['last_vote_id'] > $top['last_vote_id']) {
                    $top = $k;
                } elseif ($k['last_vote_id'] === $top['last_vote_id']) {
                    if (strcmp($k['nama'], $top['nama']) < 0) $top = $k;
                }
            }
            if ($top !== null) $pemenang_list[] = $top;
        }

        return [
            'jenis' => 'RT',
            'rt' => $rt,
            'rw' => $rw,
            'kandidat_list' => $kandidat_list,
            'pemenang_list' => $pemenang_list,
            'total_voting' => $total_voting,
            'max_suara' => $max_suara
        ];
    }

    if ($jenis === 'RW') {
        $query = "
            SELECT 
                k.id_calon, k.nama, k.rt, k.rw, k.foto,
                COUNT(v.id_calon) AS total_suara,
                COALESCE(MAX(v.id_voting), 0) AS last_vote_id,
                ROUND(
                    (COUNT(v.id_calon) * 100.0) / NULLIF((
                        SELECT COUNT(*)
                        FROM voting v2
                        JOIN warga w2 ON v2.nik = w2.nik
                        WHERE v2.id_periode = ?
                          AND w2.rw = k.rw
                    ), 0),
                2) AS persentase
            FROM kandidat k
            LEFT JOIN voting v 
                ON k.id_calon = v.id_calon 
               AND v.id_periode = ?
            WHERE k.id_periode = ?
              AND k.rw = ?
              AND (k.rt IS NULL OR k.rt = '' OR k.rt = '0')
            GROUP BY k.id_calon, k.nama, k.rt, k.rw, k.foto
            ORDER BY total_suara DESC, last_vote_id DESC, k.nama ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiis", $id_periode, $id_periode, $id_periode, $rw);
        $stmt->execute();
        $result = $stmt->get_result();

        $kandidat_list = [];
        $max_suara = 0;
        $total_voting = 0;

        while ($row = $result->fetch_assoc()) {
            $row['foto'] = normalizeFotoKandidat($row['foto'] ?? '');
            $row['total_suara'] = (int)($row['total_suara'] ?? 0);
            $row['last_vote_id'] = (int)($row['last_vote_id'] ?? 0);
            $row['persentase'] = (float)($row['persentase'] ?? 0);

            $kandidat_list[] = $row;

            if ($row['total_suara'] > $max_suara) $max_suara = $row['total_suara'];
            $total_voting += $row['total_suara'];
        }
        $stmt->close();

        $pemenang_list = [];
        if ($max_suara > 0) {
            $top = null;
            foreach ($kandidat_list as $k) {
                if ($k['total_suara'] !== $max_suara) continue;

                if ($top === null) { $top = $k; continue; }

                if ($k['last_vote_id'] > $top['last_vote_id']) {
                    $top = $k;
                } elseif ($k['last_vote_id'] === $top['last_vote_id']) {
                    if (strcmp($k['nama'], $top['nama']) < 0) $top = $k;
                }
            }
            if ($top !== null) $pemenang_list[] = $top;
        }

        return [
            'jenis' => 'RW',
            'rt' => null,
            'rw' => $rw,
            'kandidat_list' => $kandidat_list,
            'pemenang_list' => $pemenang_list,
            'total_voting' => $total_voting,
            'max_suara' => $max_suara
        ];
    }

    return $base;
}

// =====================
// Ambil hasil setiap wilayah
// =====================
$hasil_rt01 = getPemenangByWilayah($conn, $periode_id, 'RT', '01', '01');
$hasil_rt02 = getPemenangByWilayah($conn, $periode_id, 'RT', '02', '01');
$hasil_rt03 = getPemenangByWilayah($conn, $periode_id, 'RT', '03', '01');
$hasil_rw01 = getPemenangByWilayah($conn, $periode_id, 'RW', null, '01');

// =====================
// Total pemilih per wilayah
// =====================
function getTotalPemilihByWilayah($conn, $rt = null, $rw = null)
{
    $query = "SELECT COUNT(*) as total FROM warga WHERE 1=1";
    $params = [];
    $types = "";

    if ($rt !== null && $rt !== '') {
        $query .= " AND rt = ?";
        $params[] = $rt;
        $types .= "s";
    }
    if ($rw !== null && $rw !== '') {
        $query .= " AND rw = ?";
        $params[] = $rw;
        $types .= "s";
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) $stmt->bind_param($types, ...$params);

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return (int)($row['total'] ?? 0);
}

$total_pemilih_rt01 = getTotalPemilihByWilayah($conn, '01', '01');
$total_pemilih_rt02 = getTotalPemilihByWilayah($conn, '02', '01');
$total_pemilih_rt03 = getTotalPemilihByWilayah($conn, '03', '01');
$total_pemilih_rw01 = getTotalPemilihByWilayah($conn, null, '01');
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Hasil Akhir</title>
  <link rel="stylesheet" href="../css/hasil-akhir.css">
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
        <li><a href="rekap-sementara.php"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil-akhir.php" class="active"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
        <li><a href="ubah-password.php"><i class="fas fa-key"></i>Ubah Password</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <section class="page-header">
        <div class="header-content">
          <h1>Hasil Akhir Pemilihan</h1>
          <h3>Perumahan Griya Harmoni</h3>
          <p>Pengumuman resmi hasil akhir pemilihan RT/RW</p>

          <?php if ($periode_aktif): ?>
          <div class="periode-info">
            <span class="periode-badge">
              <i class="fas fa-calendar"></i> Periode: <?php echo htmlspecialchars($periode_aktif['nama_periode']); ?>
            </span>
            <span class="periode-date">
              <?php 
              $mulai = date('d-m-Y', strtotime($periode_aktif['tanggal_mulai']));
              $selesai = date('d-m-Y', strtotime($periode_aktif['tanggal_selesai']));
              echo "$mulai s/d $selesai";
              ?>
            </span>
          </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="results-content">
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

        <div class="results-grid">

          <!-- RT 01 -->
          <div class="jabatan-section" data-jabatan="rt01">
            <div class="result-card">
              <div class="result-header">
                <h2>RT 01</h2>

                <?php if (count($hasil_rt01['kandidat_list']) <= 0): ?>
                  <span class="status-badge status-upcoming">Belum Ada Data</span>
                <?php else: ?>
                  <?php if ($status_rt01['status'] === 'selesai'): ?>
                    <span class="status-badge status-active">SELESAI</span>
                  <?php elseif ($status_rt01['status'] === 'berlangsung'): ?>
                    <span class="status-badge status-upcoming">BERLANGSUNG</span>
                  <?php elseif ($status_rt01['status'] === 'belum_mulai'): ?>
                    <span class="status-badge status-upcoming">BELUM MULAI</span>
                  <?php else: ?>
                    <span class="status-badge status-upcoming">BELUM ADA JADWAL</span>
                  <?php endif; ?>
                <?php endif; ?>

              </div>

              <div class="result-body">
                <?php if (count($hasil_rt01['kandidat_list']) > 0): ?>
                  <?php if (count($hasil_rt01['pemenang_list']) > 0 && $hasil_rt01['max_suara'] > 0): ?>
                    <div class="winner-section">
                      <div class="winner-header">
                        <i class="fas fa-crown"></i>
                        <h3>Pemenang</h3>
                      </div>
                      <div class="winner-list">
                        <?php foreach ($hasil_rt01['pemenang_list'] as $pemenang): ?>
                        <div class="winner-card">
                          <div class="winner-photo">
                            <img src="<?php echo htmlspecialchars($pemenang['foto']); ?>" 
                                 alt="<?php echo htmlspecialchars($pemenang['nama']); ?>"
                                 onerror="this.src='../assets/kandidat/default.jpg'">
                          </div>
                          <div class="winner-info">
                            <h4><?php echo htmlspecialchars($pemenang['nama']); ?></h4>
                            <p class="winner-votes">
                              <i class="fas fa-vote-yea"></i>
                              <?php echo $pemenang['total_suara']; ?> suara 
                              (<?php echo number_format($pemenang['persentase'], 2); ?>%)
                            </p>
                          </div>
                        </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <div class="stats-section">
                    <h4>Statistik Pemilihan</h4>
                    <div class="stats-grid">
                      <div class="stat-item">
                        <span class="stat-label">Total Pemilih</span>
                        <span class="stat-value"><?php echo $total_pemilih_rt01; ?></span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-label">Total Voting</span>
                        <span class="stat-value"><?php echo $hasil_rt01['total_voting']; ?></span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-label">Partisipasi</span>
                        <span class="stat-value">
                          <?php 
                          $partisipasi = $total_pemilih_rt01 > 0 ? 
                            round(($hasil_rt01['total_voting'] / $total_pemilih_rt01) * 100, 2) : 0;
                          echo $partisipasi . '%';
                          ?>
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="detail-results">
                    <h4>Detail Hasil</h4>
                    <div class="results-table">
                      <table>
                        <thead>
                          <tr>
                            <th>No</th>
                            <th>Nama Kandidat</th>
                            <th>Jumlah Suara</th>
                            <th>Persentase</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $no = 1; ?>
                          <?php foreach ($hasil_rt01['kandidat_list'] as $kandidat): ?>
                          <tr class="<?php echo in_array($kandidat, $hasil_rt01['pemenang_list']) ? 'winner-row' : ''; ?>">
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($kandidat['nama']); ?></td>
                            <td><?php echo $kandidat['total_suara']; ?></td>
                            <td><?php echo number_format(floatval($kandidat['persentase'] ?? 0), 2); ?>%</td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>

                <?php else: ?>
                  <div class="empty-result">
                    <i class="fas fa-trophy"></i>
                    <h3>Belum Ada Hasil</h3>
                    <p>Belum ada data voting untuk RT 01</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- RT 02 -->
          <div class="jabatan-section" data-jabatan="rt02">
            <div class="result-card">
              <div class="result-header">
                <h2>RT 02</h2>

                <?php if (count($hasil_rt02['kandidat_list']) <= 0): ?>
                  <span class="status-badge status-upcoming">Belum Ada Data</span>
                <?php else: ?>
                  <?php if ($status_rt02['status'] === 'selesai'): ?>
                    <span class="status-badge status-active">SELESAI</span>
                  <?php elseif ($status_rt02['status'] === 'berlangsung'): ?>
                    <span class="status-badge status-upcoming">BERLANGSUNG</span>
                  <?php elseif ($status_rt02['status'] === 'belum_mulai'): ?>
                    <span class="status-badge status-upcoming">BELUM MULAI</span>
                  <?php else: ?>
                    <span class="status-badge status-upcoming">BELUM ADA JADWAL</span>
                  <?php endif; ?>
                <?php endif; ?>

              </div>

              <div class="result-body">
                <?php if (count($hasil_rt02['kandidat_list']) > 0): ?>
                  <?php if (count($hasil_rt02['pemenang_list']) > 0 && $hasil_rt02['max_suara'] > 0): ?>
                    <div class="winner-section">
                      <div class="winner-header">
                        <i class="fas fa-crown"></i>
                        <h3>Pemenang</h3>
                      </div>
                      <div class="winner-list">
                        <?php foreach ($hasil_rt02['pemenang_list'] as $pemenang): ?>
                        <div class="winner-card">
                          <div class="winner-photo">
                            <img src="<?php echo htmlspecialchars($pemenang['foto']); ?>" 
                                 alt="<?php echo htmlspecialchars($pemenang['nama']); ?>"
                                 onerror="this.src='../assets/kandidat/default.jpg'">
                          </div>
                          <div class="winner-info">
                            <h4><?php echo htmlspecialchars($pemenang['nama']); ?></h4>
                            <p class="winner-votes">
                              <i class="fas fa-vote-yea"></i>
                              <?php echo $pemenang['total_suara']; ?> suara 
                              (<?php echo number_format($pemenang['persentase'], 2); ?>%)
                            </p>
                          </div>
                        </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <div class="stats-section">
                    <h4>Statistik Pemilihan</h4>
                    <div class="stats-grid">
                      <div class="stat-item">
                        <span class="stat-label">Total Pemilih</span>
                        <span class="stat-value"><?php echo $total_pemilih_rt02; ?></span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-label">Total Voting</span>
                        <span class="stat-value"><?php echo $hasil_rt02['total_voting']; ?></span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-label">Partisipasi</span>
                        <span class="stat-value">
                          <?php 
                          $partisipasi = $total_pemilih_rt02 > 0 ? 
                            round(($hasil_rt02['total_voting'] / $total_pemilih_rt02) * 100, 2) : 0;
                          echo $partisipasi . '%';
                          ?>
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="detail-results">
                    <h4>Detail Hasil</h4>
                    <div class="results-table">
                      <table>
                        <thead>
                          <tr>
                            <th>No</th>
                            <th>Nama Kandidat</th>
                            <th>Jumlah Suara</th>
                            <th>Persentase</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $no = 1; ?>
                          <?php foreach ($hasil_rt02['kandidat_list'] as $kandidat): ?>
                          <tr class="<?php echo in_array($kandidat, $hasil_rt02['pemenang_list']) ? 'winner-row' : ''; ?>">
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($kandidat['nama']); ?></td>
                            <td><?php echo $kandidat['total_suara']; ?></td>
                            <td><?php echo number_format(floatval($kandidat['persentase'] ?? 0), 2); ?>%</td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>

                <?php else: ?>
                  <div class="empty-result">
                    <i class="fas fa-trophy"></i>
                    <h3>Belum Ada Hasil</h3>
                    <p>Belum ada data voting untuk RT 02</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- RT 03 -->
          <div class="jabatan-section" data-jabatan="rt03">
            <div class="result-card">
              <div class="result-header">
                <h2>RT 03</h2>

                <?php if (count($hasil_rt03['kandidat_list']) <= 0): ?>
                  <span class="status-badge status-upcoming">Belum Ada Data</span>
                <?php else: ?>
                  <?php if ($status_rt03['status'] === 'selesai'): ?>
                    <span class="status-badge status-active">SELESAI</span>
                  <?php elseif ($status_rt03['status'] === 'berlangsung'): ?>
                    <span class="status-badge status-upcoming">BERLANGSUNG</span>
                  <?php elseif ($status_rt03['status'] === 'belum_mulai'): ?>
                    <span class="status-badge status-upcoming">BELUM MULAI</span>
                  <?php else: ?>
                    <span class="status-badge status-upcoming">BELUM ADA JADWAL</span>
                  <?php endif; ?>
                <?php endif; ?>

              </div>

              <div class="result-body">
                <?php if (count($hasil_rt03['kandidat_list']) > 0): ?>
                  <?php if (count($hasil_rt03['pemenang_list']) > 0 && $hasil_rt03['max_suara'] > 0): ?>
                    <div class="winner-section">
                      <div class="winner-header">
                        <i class="fas fa-crown"></i>
                        <h3>Pemenang</h3>
                      </div>
                      <div class="winner-list">
                        <?php foreach ($hasil_rt03['pemenang_list'] as $pemenang): ?>
                        <div class="winner-card">
                          <div class="winner-photo">
                            <img src="<?php echo htmlspecialchars($pemenang['foto']); ?>" 
                                 alt="<?php echo htmlspecialchars($pemenang['nama']); ?>"
                                 onerror="this.src='../assets/kandidat/default.jpg'">
                          </div>
                          <div class="winner-info">
                            <h4><?php echo htmlspecialchars($pemenang['nama']); ?></h4>
                            <p class="winner-votes">
                              <i class="fas fa-vote-yea"></i>
                              <?php echo $pemenang['total_suara']; ?> suara 
                              (<?php echo number_format($pemenang['persentase'], 2); ?>%)
                            </p>
                          </div>
                        </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <div class="stats-section">
                    <h4>Statistik Pemilihan</h4>
                    <div class="stats-grid">
                      <div class="stat-item">
                        <span class="stat-label">Total Pemilih</span>
                        <span class="stat-value"><?php echo $total_pemilih_rt03; ?></span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-label">Total Voting</span>
                        <span class="stat-value"><?php echo $hasil_rt03['total_voting']; ?></span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-label">Partisipasi</span>
                        <span class="stat-value">
                          <?php 
                          $partisipasi = $total_pemilih_rt03 > 0 ? 
                            round(($hasil_rt03['total_voting'] / $total_pemilih_rt03) * 100, 2) : 0;
                          echo $partisipasi . '%';
                          ?>
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="detail-results">
                    <h4>Detail Hasil</h4>
                    <div class="results-table">
                      <table>
                        <thead>
                          <tr>
                            <th>No</th>
                            <th>Nama Kandidat</th>
                            <th>Jumlah Suara</th>
                            <th>Persentase</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $no = 1; ?>
                          <?php foreach ($hasil_rt03['kandidat_list'] as $kandidat): ?>
                          <tr class="<?php echo in_array($kandidat, $hasil_rt03['pemenang_list']) ? 'winner-row' : ''; ?>">
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($kandidat['nama']); ?></td>
                            <td><?php echo $kandidat['total_suara']; ?></td>
                            <td><?php echo number_format(floatval($kandidat['persentase'] ?? 0), 2); ?>%</td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>

                <?php else: ?>
                  <div class="empty-result">
                    <i class="fas fa-trophy"></i>
                    <h3>Belum Ada Hasil</h3>
                    <p>Belum ada data voting untuk RT 03</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- RW 01 -->
          <div class="jabatan-section" data-jabatan="rw01">
            <div class="result-card">
              <div class="result-header">
                <h2>RW 01</h2>

                <?php if (count($hasil_rw01['kandidat_list']) <= 0): ?>
                  <span class="status-badge status-upcoming">Belum Ada Data</span>
                <?php else: ?>
                  <?php if ($status_rw01['status'] === 'selesai'): ?>
                    <span class="status-badge status-active">SELESAI</span>
                  <?php elseif ($status_rw01['status'] === 'berlangsung'): ?>
                    <span class="status-badge status-upcoming">BERLANGSUNG</span>
                  <?php elseif ($status_rw01['status'] === 'belum_mulai'): ?>
                    <span class="status-badge status-upcoming">BELUM MULAI</span>
                  <?php else: ?>
                    <span class="status-badge status-upcoming">BELUM ADA JADWAL</span>
                  <?php endif; ?>
                <?php endif; ?>

              </div>

              <div class="result-body">
                <?php if (count($hasil_rw01['kandidat_list']) > 0): ?>
                  <?php if (count($hasil_rw01['pemenang_list']) > 0 && $hasil_rw01['max_suara'] > 0): ?>
                    <div class="winner-section">
                      <div class="winner-header">
                        <i class="fas fa-crown"></i>
                        <h3>Pemenang</h3>
                      </div>
                      <div class="winner-list">
                        <?php foreach ($hasil_rw01['pemenang_list'] as $pemenang): ?>
                        <div class="winner-card">
                          <div class="winner-photo">
                            <img src="<?php echo htmlspecialchars($pemenang['foto']); ?>" 
                                 alt="<?php echo htmlspecialchars($pemenang['nama']); ?>"
                                 onerror="this.src='../assets/kandidat/default.jpg'">
                          </div>
                          <div class="winner-info">
                            <h4><?php echo htmlspecialchars($pemenang['nama']); ?></h4>
                            <p class="winner-votes">
                              <i class="fas fa-vote-yea"></i>
                              <?php echo $pemenang['total_suara']; ?> suara 
                              (<?php echo number_format($pemenang['persentase'], 2); ?>%)
                            </p>
                          </div>
                        </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <div class="stats-section">
                    <h4>Statistik Pemilihan</h4>
                    <div class="stats-grid">
                      <div class="stat-item">
                        <span class="stat-label">Total Pemilih</span>
                        <span class="stat-value"><?php echo $total_pemilih_rw01; ?></span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-label">Total Voting</span>
                        <span class="stat-value"><?php echo $hasil_rw01['total_voting']; ?></span>
                      </div>
                      <div class="stat-item">
                        <span class="stat-label">Partisipasi</span>
                        <span class="stat-value">
                          <?php 
                          $partisipasi = $total_pemilih_rw01 > 0 ? 
                            round(($hasil_rw01['total_voting'] / $total_pemilih_rw01) * 100, 2) : 0;
                          echo $partisipasi . '%';
                          ?>
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="detail-results">
                    <h4>Detail Hasil</h4>
                    <div class="results-table">
                      <table>
                        <thead>
                          <tr>
                            <th>No</th>
                            <th>Nama Kandidat</th>
                            <th>Jumlah Suara</th>
                            <th>Persentase</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php $no = 1; ?>
                          <?php foreach ($hasil_rw01['kandidat_list'] as $kandidat): ?>
                          <tr class="<?php echo in_array($kandidat, $hasil_rw01['pemenang_list']) ? 'winner-row' : ''; ?>">
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($kandidat['nama']); ?></td>
                            <td><?php echo $kandidat['total_suara']; ?></td>
                            <td><?php echo number_format(floatval($kandidat['persentase'] ?? 0), 2); ?>%</td>
                          </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>

                <?php else: ?>
                  <div class="empty-result">
                    <i class="fas fa-trophy"></i>
                    <h3>Belum Ada Hasil</h3>
                    <p>Belum ada data voting untuk RW 01</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

        </div>

        <div class="announcement-section">
          <div class="announcement-card">
            <div class="announcement-header">
              <i class="fas fa-bullhorn"></i>
              <h3>Pengumuman Resmi</h3>
            </div>
            <div class="announcement-content">
              <?php if ($is_final): ?>
                <div class="final-notice">
                  <i class="fas fa-check-circle"></i>
                  <h4>Hasil Final</h4>
                  <p>Hasil di atas adalah hasil akhir pemilihan yang telah diverifikasi dan ditetapkan oleh panitia pemilihan.</p>
                </div>
              <?php else: ?>
                <p>Hasil akhir pemilihan RT/RW akan diumumkan secara resmi setelah:</p>
                <ul>
                  <li>Periode pemilihan berakhir</li>
                  <li>Proses penghitungan suara selesai</li>
                  <li>Verifikasi dan validasi data oleh panitia</li>
                  <li>Pengumuman resmi dari ketua panitia pemilihan</li>
                </ul>
              <?php endif; ?>

              <div class="announcement-note">
                <i class="fas fa-info-circle"></i>
                <span>
                  <?php echo $is_final ? 
                    'Hasil yang diumumkan di sini bersifat final dan mengikat' : 
                    'Hasil saat ini bersifat sementara sampai jadwal pemilihan berakhir';
                  ?>
                </span>
              </div>
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

        <?php if (!$is_final): ?>
        setTimeout(function() {
            window.location.reload();
        }, 30000);
        <?php endif; ?>
    });
  </script>
</body>
</html>
