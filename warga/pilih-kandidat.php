<?php
require_once 'guard-warga.php'; // harusnya sudah set: $conn, $nik, $nama (dan session)

$history_data = [];
$total_voting = 0;

// Ambil NIK dari guard (lebih stabil)
$nik = (string)($nik ?? '');
if ($nik === '') {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Cek apakah warga sudah memilih (pisah RT & RW)
$sudahMemilihRT = false;
$sudahMemilihRW = false;

$query_cek = "
    SELECT v.id_calon, v.id_periode, k.rt
    FROM voting v
    JOIN kandidat k ON v.id_calon = k.id_calon
    WHERE v.nik = ? AND v.id_periode = 1
";
$stmt_cek = $conn->prepare($query_cek);
$stmt_cek->bind_param("s", $nik);
$stmt_cek->execute();
$result_cek = $stmt_cek->get_result();

while ($row = $result_cek->fetch_assoc()) {
    if (!empty($row['rt'])) {
        $sudahMemilihRT = true;
    } else {
        $sudahMemilihRW = true;
    }
}
$stmt_cek->close();

// Ambil data warga untuk mendapatkan RT/RW
$query_warga = "SELECT rt, rw, id_wilayah FROM warga WHERE nik = ? LIMIT 1";
$stmt_warga = $conn->prepare($query_warga);
$stmt_warga->bind_param("s", $nik);
$stmt_warga->execute();
$result_warga = $stmt_warga->get_result();

$rt_warga = '';
$rw_warga = '';
$id_wilayah = null;

if ($row_warga = $result_warga->fetch_assoc()) {
    $rt_warga = $row_warga['rt'] ?? '';
    $rw_warga = $row_warga['rw'] ?? '';
    $id_wilayah = $row_warga['id_wilayah'] ?? null;
}
$stmt_warga->close();

// Jika RT/RW kosong, coba ambil dari tabel wilayah
if ((empty($rt_warga) || empty($rw_warga)) && !empty($id_wilayah)) {
    $query_wilayah = "SELECT rt, rw FROM wilayah WHERE id_wilayah = ? LIMIT 1";
    $stmt_wil = $conn->prepare($query_wilayah);
    $stmt_wil->bind_param("i", $id_wilayah);
    $stmt_wil->execute();
    $result_wil = $stmt_wil->get_result();

    if ($row_wil = $result_wil->fetch_assoc()) {
        $rt_warga = $row_wil['rt'] ?? $rt_warga;
        $rw_warga = $row_wil['rw'] ?? $rw_warga;
    }
    $stmt_wil->close();
}

// NORMALISASI RT/RW: ambil hanya digit (misal "RT 03" -> "03")
$rt_warga = preg_replace('/\D/', '', (string)$rt_warga);
$rw_warga = preg_replace('/\D/', '', (string)$rw_warga);

// Jika masih kosong, set default
if ($rt_warga === '') $rt_warga = '01';
if ($rw_warga === '') $rw_warga = '01';

// =======================
// AMBIL KANDIDAT
// =======================
$kandidat_rt = [];
$kandidat_rw = [];

// Kandidat RT
if ($rt_warga !== '' && !$sudahMemilihRT) {
    $query_kandidat_rt = "
        SELECT k.id_calon, k.nama, k.rt, k.rw, k.foto, k.visi, k.misi
        FROM kandidat k
        WHERE k.id_periode = 1
          AND k.rt = ?
          AND k.rw = ?
        ORDER BY k.id_calon
    ";
    $stmt_kandidat_rt = $conn->prepare($query_kandidat_rt);
    $stmt_kandidat_rt->bind_param("ss", $rt_warga, $rw_warga);
    $stmt_kandidat_rt->execute();
    $result_kandidat_rt = $stmt_kandidat_rt->get_result();

    if ($result_kandidat_rt && $result_kandidat_rt->num_rows > 0) {
        $counter = 1;
        while ($row = $result_kandidat_rt->fetch_assoc()) {
            $rt_formatted = str_pad((string)$row['rt'], 2, '0', STR_PAD_LEFT);
            $foto_nama = "rt{$rt_formatted}_calon{$counter}.jpg";
            $row['foto'] = "../assets/kandidat/" . $foto_nama;
            $kandidat_rt[] = $row;
            $counter++;
        }
    }
    $stmt_kandidat_rt->close();
}

// Kandidat RW
if ($rw_warga !== '' && !$sudahMemilihRW) {
    $query_kandidat_rw = "
        SELECT k.id_calon, k.nama, k.rt, k.rw, k.foto, k.visi, k.misi
        FROM kandidat k
        WHERE k.id_periode = 1
          AND k.rw = ?
          AND (k.rt IS NULL OR k.rt = '' OR k.rt = '0')
        ORDER BY k.id_calon
    ";
    $stmt_kandidat_rw = $conn->prepare($query_kandidat_rw);
    $stmt_kandidat_rw->bind_param("s", $rw_warga);
    $stmt_kandidat_rw->execute();
    $result_kandidat_rw = $stmt_kandidat_rw->get_result();

    if ($result_kandidat_rw && $result_kandidat_rw->num_rows > 0) {
        $counter = 1;
        while ($row = $result_kandidat_rw->fetch_assoc()) {
            $rw_formatted = str_pad((string)$row['rw'], 2, '0', STR_PAD_LEFT);
            $foto_nama = "rw{$rw_formatted}_calon{$counter}.jpg";
            $row['foto'] = "../assets/kandidat/" . $foto_nama;
            $kandidat_rw[] = $row;
            $counter++;
        }
    }
    $stmt_kandidat_rw->close();
}

// =======================
// CEK JADWAL PEMILIHAN
// =======================
$jadwal_rt = null;
$jadwal_rw = null;

if ($rt_warga !== '' && $rw_warga !== '') {
    $rt_numeric = (int)$rt_warga;

    $query_jadwal_rt = "
        SELECT j.*, w.nama_wilayah
        FROM jadwal j
        LEFT JOIN wilayah w ON j.id_wilayah = w.id_wilayah
        WHERE w.jenis = 'RT'
          AND CAST(w.rt AS UNSIGNED) = ?
          AND j.id_periode = 1
        LIMIT 1
    ";
    $stmt_jadwal_rt = $conn->prepare($query_jadwal_rt);
    $stmt_jadwal_rt->bind_param("i", $rt_numeric);
    $stmt_jadwal_rt->execute();
    $result_jadwal_rt = $stmt_jadwal_rt->get_result();
    if ($result_jadwal_rt && $result_jadwal_rt->num_rows > 0) {
        $jadwal_rt = $result_jadwal_rt->fetch_assoc();
    }
    $stmt_jadwal_rt->close();

    $rw_numeric = (int)$rw_warga;

    $query_jadwal_rw = "
        SELECT j.*, w.nama_wilayah
        FROM jadwal j
        LEFT JOIN wilayah w ON j.id_wilayah = w.id_wilayah
        WHERE w.jenis = 'RW'
          AND CAST(w.rw AS UNSIGNED) = ?
          AND j.id_periode = 1
        LIMIT 1
    ";
    $stmt_jadwal_rw = $conn->prepare($query_jadwal_rw);
    $stmt_jadwal_rw->bind_param("i", $rw_numeric);
    $stmt_jadwal_rw->execute();
    $result_jadwal_rw = $stmt_jadwal_rw->get_result();
    if ($result_jadwal_rw && $result_jadwal_rw->num_rows > 0) {
        $jadwal_rw = $result_jadwal_rw->fetch_assoc();
    }
    $stmt_jadwal_rw->close();
}

// =======================
// STATUS PEMILIHAN
// =======================
function getStatusPemilihan($jadwal) {
    if (!$jadwal) return 'upcoming';

    $mulaiRaw   = trim($jadwal['tanggal_mulai'] ?? '');
    $selesaiRaw = trim($jadwal['tanggal_selesai'] ?? '');

    if ($mulaiRaw === '' || $selesaiRaw === '') return 'upcoming';

    try {
        $tz      = new DateTimeZone('Asia/Jakarta');
        $mulai   = new DateTime($mulaiRaw,   $tz);
        $selesai = new DateTime($selesaiRaw, $tz);
        $now     = new DateTime('now',       $tz);
    } catch (Exception $e) {
        return 'upcoming';
    }

    if ($now < $mulai)   return 'upcoming';
    if ($now > $selesai) return 'completed';
    return 'active';
}

$status_rt = getStatusPemilihan($jadwal_rt);
$status_rw = getStatusPemilihan($jadwal_rw);

// =======================
// PROSES VOTING
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_calon'])) {
    $id_calon = (int)$_POST['id_calon'];

    $query_cek_kandidat = "SELECT rt FROM kandidat WHERE id_calon = ? LIMIT 1";
    $stmt_cek_k = $conn->prepare($query_cek_kandidat);
    $stmt_cek_k->bind_param("i", $id_calon);
    $stmt_cek_k->execute();
    $result_cek_k = $stmt_cek_k->get_result();

    $tipe_kandidat = '';
    if ($row_k = $result_cek_k->fetch_assoc()) {
        $tipe_kandidat = !empty($row_k['rt']) ? 'RT' : 'RW';
    }
    $stmt_cek_k->close();

    $bolehVote = false;
    if ($tipe_kandidat === 'RT' && !$sudahMemilihRT && $status_rt === 'active') {
        $bolehVote = true;
    } elseif ($tipe_kandidat === 'RW' && !$sudahMemilihRW && $status_rw === 'active') {
        $bolehVote = true;
    }

    if ($bolehVote) {
        $query_max_id = "SELECT MAX(id_voting) as max_id FROM voting";
        $result_max = $conn->query($query_max_id);
        $max_id = 0;
        if ($result_max && $row_max = $result_max->fetch_assoc()) {
            $max_id = (int)($row_max['max_id'] ?? 0);
        }
        $new_id = $max_id + 1;

        $query_insert = "
            INSERT INTO voting (id_voting, nik, id_calon, id_periode, waktu_pilih)
            VALUES (?, ?, ?, 1, NOW())
        ";
        $stmt_insert = $conn->prepare($query_insert);
        $stmt_insert->bind_param("isi", $new_id, $nik, $id_calon);

        if ($stmt_insert->execute()) {
            $query_update_warga = "UPDATE warga SET status_voting = 'sudah', waktu_voting = NOW() WHERE nik = ?";
            $stmt_update = $conn->prepare($query_update_warga);
            $stmt_update->bind_param("s", $nik);
            $stmt_update->execute();
            $stmt_update->close();

            $_SESSION['vote_success'] = "Terima kasih! Suara Anda telah tercatat.";
            header("Location: pilih-kandidat.php");
            exit;
        } else {
            $error = "Gagal menyimpan pilihan. Silakan coba lagi.";
        }
        $stmt_insert->close();
    } else {
        $error = "Anda tidak dapat memilih kandidat ini. Mungkin karena: <br>
                1. Anda sudah memilih untuk $tipe_kandidat<br>
                2. Pemilihan belum/tidak aktif<br>
                3. Kandidat tidak valid untuk wilayah Anda";
    }
}

$success = $_SESSION['vote_success'] ?? '';
unset($_SESSION['vote_success']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Pilih Kandidat</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    <?php include __DIR__ . '/../css/pilih-kandidat.css'; ?>
  </style>
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
        <?php if (!empty($rt_warga)): ?>
          (RT <?php echo htmlspecialchars(str_pad($rt_warga, 2, '0', STR_PAD_LEFT)); ?>)
        <?php endif; ?></span>
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
        <li><a href="pilih-kandidat.php" class="active"><i class="fas fa-vote-yea"></i> Pilih Kandidat</a></li>
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
          <h1>Pilih Kandidat</h1>
          <h3>Perumahan Griya Harmoni</h3>
          <p>Gunakan hak pilih Anda untuk memilih calon RT/RW</p>
        </div>
      </section>

      <section class="voting-content">
        <?php if ($success): ?>
        <div class="success-message">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <div class="voting-grid">
          <!-- RT -->
          <div class="jabatan-section">
            <div class="jabatan-header">
              <h2>RT <?php echo htmlspecialchars(str_pad($rt_warga, 2, '0', STR_PAD_LEFT)); ?></h2>
              <?php if ($sudahMemilihRT): ?>
                <span class="status-badge status-completed">Sudah Memilih</span>
              <?php elseif ($status_rt === 'active'): ?>
                <span class="status-badge status-active">Sedang Berlangsung</span>
              <?php elseif ($status_rt === 'upcoming'): ?>
                <span class="status-badge status-upcoming">Belum Dimulai</span>
              <?php else: ?>
                <span class="status-badge status-completed">Selesai</span>
              <?php endif; ?>
            </div>

            <?php if ($sudahMemilihRT): ?>
              <div class="empty-voting">
                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                <h3>Anda sudah memilih untuk RT <?php echo htmlspecialchars(str_pad($rt_warga, 2, '0', STR_PAD_LEFT)); ?></h3>
                <p>Terima kasih telah menggunakan hak pilih Anda untuk RT</p>
              </div>
            <?php elseif (empty($kandidat_rt)): ?>
              <div class="empty-voting">
                <i class="fas fa-users"></i>
                <h3>Belum ada kandidat untuk dipilih</h3>
                <p>Pemilihan untuk RT <?php echo htmlspecialchars(str_pad($rt_warga, 2, '0', STR_PAD_LEFT)); ?> akan dibuka ketika pendaftaran kandidat selesai</p>
              </div>
            <?php else: ?>
              <div class="candidates-list">
                <?php foreach ($kandidat_rt as $kandidat): ?>
                <div class="candidate-card">
                  <div class="candidate-photo">
                    <img src="<?php echo htmlspecialchars($kandidat['foto']); ?>" alt="<?php echo htmlspecialchars($kandidat['nama']); ?>"
                         onerror="this.src='../assets/kandidat/default.jpg'">
                  </div>
                  <div class="candidate-info">
                    <h3><?php echo htmlspecialchars($kandidat['nama']); ?></h3>
                    <p><strong>Calon Ketua RT <?php echo htmlspecialchars(str_pad($rt_warga, 2, '0', STR_PAD_LEFT)); ?></strong></p>

                    <button class="btn-detail" onclick="toggleDetails(<?php echo $kandidat['id_calon']; ?>)">
                      <i class="fas fa-info-circle"></i> Lihat Detail Visi & Misi
                    </button>

                    <div class="detail-content" id="detail-<?php echo $kandidat['id_calon']; ?>">
                      <div class="detail-section">
                        <h4><i class="fas fa-eye"></i> Visi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['visi'])); ?></p>
                      </div>
                      <div class="detail-section">
                        <h4><i class="fas fa-bullseye"></i> Misi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['misi'])); ?></p>
                      </div>
                    </div>

                    <?php if ($status_rt === 'active'): ?>
                    <form method="POST" action="pilih-kandidat.php">
                      <input type="hidden" name="id_calon" value="<?php echo $kandidat['id_calon']; ?>">
                      <button type="submit" class="btn-vote" onclick="return confirm('Apakah Anda yakin memilih <?php echo htmlspecialchars(addslashes($kandidat['nama'])); ?> sebagai ketua RT <?php echo htmlspecialchars(str_pad($rt_warga, 2, '0', STR_PAD_LEFT)); ?>?')">
                        <i class="fas fa-vote-yea"></i> Pilih Kandidat Ini
                      </button>
                    </form>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- RW -->
          <div class="jabatan-section">
            <div class="jabatan-header">
              <h2>RW <?php echo htmlspecialchars(str_pad($rw_warga, 2, '0', STR_PAD_LEFT)); ?></h2>
              <?php if ($sudahMemilihRW): ?>
                <span class="status-badge status-completed">Sudah Memilih</span>
              <?php elseif ($status_rw === 'active'): ?>
                <span class="status-badge status-active">Sedang Berlangsung</span>
              <?php elseif ($status_rw === 'upcoming'): ?>
                <span class="status-badge status-upcoming">Belum Dimulai</span>
              <?php else: ?>
                <span class="status-badge status-completed">Selesai</span>
              <?php endif; ?>
            </div>

            <?php if ($sudahMemilihRW): ?>
              <div class="empty-voting">
                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                <h3>Anda sudah memilih untuk RW <?php echo htmlspecialchars(str_pad($rw_warga, 2, '0', STR_PAD_LEFT)); ?></h3>
                <p>Terima kasih telah menggunakan hak pilih Anda untuk RW</p>
              </div>
            <?php elseif (empty($kandidat_rw)): ?>
              <div class="empty-voting">
                <i class="fas fa-users"></i>
                <h3>Belum ada kandidat untuk dipilih</h3>
                <p>Pemilihan untuk RW <?php echo htmlspecialchars(str_pad($rw_warga, 2, '0', STR_PAD_LEFT)); ?> akan dibuka ketika pendaftaran kandidat selesai</p>
              </div>
            <?php else: ?>
              <div class="candidates-list">
                <?php foreach ($kandidat_rw as $kandidat): ?>
                <div class="candidate-card">
                  <div class="candidate-photo">
                    <img src="<?php echo htmlspecialchars($kandidat['foto']); ?>" alt="<?php echo htmlspecialchars($kandidat['nama']); ?>"
                         onerror="this.src='../assets/kandidat/default.jpg'">
                  </div>
                  <div class="candidate-info">
                    <h3><?php echo htmlspecialchars($kandidat['nama']); ?></h3>
                    <p><strong>Calon Ketua RW <?php echo htmlspecialchars(str_pad($rw_warga, 2, '0', STR_PAD_LEFT)); ?></strong></p>

                    <button class="btn-detail" onclick="toggleDetails(<?php echo $kandidat['id_calon']; ?>)">
                      <i class="fas fa-info-circle"></i> Lihat Detail Visi & Misi
                    </button>

                    <div class="detail-content" id="detail-<?php echo $kandidat['id_calon']; ?>">
                      <div class="detail-section">
                        <h4><i class="fas fa-eye"></i> Visi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['visi'])); ?></p>
                      </div>
                      <div class="detail-section">
                        <h4><i class="fas fa-bullseye"></i> Misi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['misi'])); ?></p>
                      </div>
                    </div>

                    <?php if ($status_rw === 'active'): ?>
                    <form method="POST" action="pilih-kandidat.php">
                      <input type="hidden" name="id_calon" value="<?php echo $kandidat['id_calon']; ?>">
                      <button type="submit" class="btn-vote" onclick="return confirm('Apakah Anda yakin memilih <?php echo htmlspecialchars(addslashes($kandidat['nama'])); ?> sebagai ketua RW <?php echo htmlspecialchars(str_pad($rw_warga, 2, '0', STR_PAD_LEFT)); ?>?')">
                        <i class="fas fa-vote-yea"></i> Pilih Kandidat Ini
                      </button>
                    </form>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="voting-info">
          <div class="info-card">
            <h3><i class="fas fa-info-circle"></i> Informasi Pemilihan</h3>
            <div class="info-content">
              <p>• Warga hanya dapat memilih kandidat di RT/RW tempat mereka terdaftar</p>
              <p>• Setiap warga memiliki hak memilih 1 suara untuk RT dan 1 suara untuk RW</p>
              <p>• Pilihan bersifat rahasia dan tidak dapat diubah setelah disubmit</p>
              <p>• Hasil pemilihan akan diumumkan setelah periode voting berakhir</p>
              <?php if ($sudahMemilihRT && $sudahMemilihRW): ?>
              <p style="color: #28a745; font-weight: 500;"><i class="fas fa-check-circle"></i> Anda telah menggunakan seluruh hak pilih</p>
              <?php elseif ($sudahMemilihRT || $sudahMemilihRW): ?>
              <p style="color: #ffc107; font-weight: 500;"><i class="fas fa-exclamation-circle"></i> Anda masih memiliki hak pilih yang tersisa</p>
              <?php else: ?>
              <p style="color: #dc3545; font-weight: 500;"><i class="fas fa-clock"></i> Anda belum menggunakan hak pilih</p>
              <?php endif; ?>
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
    });

    function toggleDetails(id) {
        const detailElement = document.getElementById('detail-' + id);
        const button = event.target.closest('.btn-detail');

        if (detailElement.style.display === 'none' || detailElement.style.display === '') {
            detailElement.style.display = 'block';
            button.innerHTML = '<i class="fas fa-times-circle"></i> Tutup Detail';
        } else {
            detailElement.style.display = 'none';
            button.innerHTML = '<i class="fas fa-info-circle"></i> Lihat Detail Visi & Misi';
        }
    }
  </script>
</body>
</html>
