<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

// Wajib login panitia (kalau role beda, sesuaikan)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'panitia') {
    header('Location: login.php');
    exit;
}

// ---------- HANDLE DELETE ----------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM jadwal WHERE id_jadwal = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header('Location: schedule.php');
    exit;
}

// ---------- HANDLE EDIT (LOAD DATA UNTUK FORM) ----------
$editData = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $conn->prepare("SELECT * FROM jadwal WHERE id_jadwal = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result   = $stmt->get_result();
    $editData = $result->fetch_assoc();
}

// ---------- HANDLE INSERT / UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_jadwal      = isset($_POST['id_jadwal']) ? (int) $_POST['id_jadwal'] : 0;
    $keterangan     = trim($_POST['keterangan'] ?? '');
    $tanggal_mulai  = $_POST['tanggal_mulai']  ?? '';
    $jam_mulai      = $_POST['jam_mulai']      ?? '';
    $tanggal_selesai= $_POST['tanggal_selesai']?? '';
    $jam_selesai    = $_POST['jam_selesai']    ?? '';

    // gabung jadi datetime: 2025-11-30 08:00:00
    $mulai   = $tanggal_mulai   && $jam_mulai    ? $tanggal_mulai   . ' ' . $jam_mulai   . ':00' : null;
    $selesai = $tanggal_selesai && $jam_selesai  ? $tanggal_selesai . ' ' . $jam_selesai . ':00' : null;

    // default sementara, nanti bisa diubah jadi select/relasi sebenarnya
    $id_periode = 1;
    $id_wilayah = 1;
    $id_panitia = isset($_SESSION['id_panitia']) ? (int)$_SESSION['id_panitia'] : 1;

    if ($id_jadwal > 0) {
        // UPDATE
        $stmt = $conn->prepare("
            UPDATE jadwal 
            SET tanggal_mulai = ?, tanggal_selesai = ?, keterangan = ?
            WHERE id_jadwal = ?
        ");
        $stmt->bind_param("sssi", $mulai, $selesai, $keterangan, $id_jadwal);
        $stmt->execute();
    } else {
        // INSERT
        $stmt = $conn->prepare("
            INSERT INTO jadwal (id_periode, id_wilayah, tanggal_mulai, tanggal_selesai, id_panitia, keterangan)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissis", $id_periode, $id_wilayah, $mulai, $selesai, $id_panitia, $keterangan);
        $stmt->execute();
    }

    header('Location: schedule.php');
    exit;
}

// ---------- AMBIL DATA JADWAL UNTUK DITAMPILKAN ----------
$jadwalList = [];

$sql = "
    SELECT 
        j.id_jadwal,
        j.tanggal_mulai,
        j.tanggal_selesai,
        j.keterangan
    FROM jadwal j
    ORDER BY j.tanggal_mulai ASC
";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $jadwalList[] = $row;
    }
}

// ---------- FUNGSI BANTU ----------
function format_tanggal_indo($datetime)
{
    if (!$datetime) return '-';
    $bulan = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
             'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];

    $dt = new DateTime($datetime);
    $d  = (int)$dt->format('d');
    $m  = (int)$dt->format('m');
    $y  = $dt->format('Y');

    return $d . ' ' . $bulan[$m] . ' ' . $y;
}

function format_jam($datetime)
{
    if (!$datetime) return '-';
    $dt = new DateTime($datetime);
    return $dt->format('H:i');
}

function status_jadwal(array $row)
{
    $now     = new DateTime();
    $mulai   = new DateTime($row['tanggal_mulai']);
    $selesai = new DateTime($row['tanggal_selesai']);

    if ($now < $mulai) {
        return ['class' => 'status-upcoming', 'label' => 'Akan Datang'];
    } elseif ($now > $selesai) {
        return ['class' => 'status-completed', 'label' => 'Selesai'];
    } else {
        return ['class' => 'status-active', 'label' => 'Berjalan'];
    }
}

// jadwal untuk info-card (ambil yang pertama saja dulu)
$highlight = $jadwalList[0] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Jadwal Pemilihan</title>
  <link rel="stylesheet" href="/css/dashboard-panitia.css">
  <link rel="stylesheet" href="/css/schedule.css">
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
        <li><a href="schedule.php" class="active"><i class="fas fa-calendar-alt"></i> Jadwal Pemilihan</a></li>
        <li><a href="data-pemilih.php"><i class="fas fa-users"></i> Data Warga</a></li>
        <li><a href="kandidat.php"><i class="fas fa-user-tie"></i> Data Kandidat</a></li>
        <li><a href="rekap.php"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil.php"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <div class="content-page">
        <div class="page-header">
          <h1>Jadwal Pemilihan RT & RW</h1>
          <p>Perumahan Griya Harmoni</p>
        </div>

        <?php if ($highlight): ?>
          <?php $st = status_jadwal($highlight); ?>
          <div class="info-card">
            <h4>Periode Pemilihan : 
              <?= htmlspecialchars($highlight['keterangan'] ?: 'Tanpa keterangan'); ?>
            </h4>
            <div class="info-row">
              <strong>Waktu Mulai :</strong> 
              <span><?= format_tanggal_indo($highlight['tanggal_mulai']); ?></span>
            </div>
            <div class="info-row">
              <strong>Status :</strong> 
              <span class="status-badge <?= $st['class']; ?>">
                <?= $st['label']; ?>
              </span>
            </div>
          </div>
        <?php else: ?>
          <div class="info-card">
            <h4>Belum Ada Jadwal Pemilihan</h4>
            <div class="info-row">
              <strong>Status :</strong> 
              <span class="status-badge status-upcoming">Belum ada jadwal</span>
            </div>
          </div>
        <?php endif; ?>

        <div class="form-card">
          <h3><?= $editData ? 'Edit Jadwal Pemilihan' : 'Tambah/Jadwalkan Pemilihan'; ?></h3>
          
          <form method="post" action="schedule.php">
            <input type="hidden" name="id_jadwal" 
                   value="<?= $editData['id_jadwal'] ?? ''; ?>">

            <div class="form-group">
              <label for="periodeName">Nama Periode / Keterangan</label>
              <input type="text" id="periodeName" name="keterangan"
                     placeholder="Pemilu ketua RT 01 2025"
                     value="<?= htmlspecialchars($editData['keterangan'] ?? ''); ?>">
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="tanggalMulai">Tanggal Mulai</label>
                <input type="date" id="tanggalMulai" name="tanggal_mulai"
                       value="<?= $editData ? substr($editData['tanggal_mulai'], 0, 10) : ''; ?>">
              </div>
              <div class="form-group">
                <label for="tanggalSelesai">Tanggal Selesai</label>
                <input type="date" id="tanggalSelesai" name="tanggal_selesai"
                       value="<?= $editData ? substr($editData['tanggal_selesai'], 0, 10) : ''; ?>">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="jamMulai">Jam Mulai</label>
                <input type="time" id="jamMulai" name="jam_mulai"
                       value="<?= $editData ? substr($editData['tanggal_mulai'], 11, 5) : '08:00'; ?>">
              </div>
              <div class="form-group">
                <label for="jamSelesai">Jam Selesai</label>
                <input type="time" id="jamSelesai" name="jam_selesai"
                       value="<?= $editData ? substr($editData['tanggal_selesai'], 11, 5) : '16:00'; ?>">
              </div>
            </div>

            <div class="table-container">
              <h4>Daftar Jadwal Pemilihan</h4>
              <div class="table-card">
                <table class="data-table">
                  <thead>
                    <tr>
                      <th>No</th>
                      <th>Periode / Keterangan</th>
                      <th>Tanggal</th>
                      <th>Jam Mulai</th>
                      <th>Jam Selesai</th>
                      <th>Status</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody id="scheduleTableBody">
                    <?php if (empty($jadwalList)): ?>
                      <tr>
                        <td colspan="7" class="no-data-message">
                          Belum ada jadwal pemilihan. Silakan tambah jadwal baru.
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($jadwalList as $index => $row): ?>
                        <?php $st = status_jadwal($row); ?>
                        <tr>
                          <td><?= $index + 1; ?></td>
                          <td><?= htmlspecialchars($row['keterangan'] ?? ''); ?></td>
                          <td><?= format_tanggal_indo($row['tanggal_mulai']); ?></td>
                          <td><?= format_jam($row['tanggal_mulai']); ?> WIB</td>
                          <td><?= format_jam($row['tanggal_selesai']); ?> WIB</td>
                          <td>
                            <span class="status-badge <?= $st['class']; ?>">
                              <?= $st['label']; ?>
                            </span>
                          </td>
                          <td>
                            <a href="schedule.php?action=edit&id=<?= $row['id_jadwal']; ?>"
                               class="btn-icon btn-edit" title="Edit">
                              <i class="fas fa-edit"></i>
                            </a>
                            <a href="schedule.php?action=delete&id=<?= $row['id_jadwal']; ?>"
                               class="btn-icon btn-delete" title="Hapus"
                               onclick="return confirm('Yakin hapus jadwal ini?')">
                              <i class="fas fa-trash"></i>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="actions">
              <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> Kembali
              </button>
              <button type="submit" class="btn btn-primary" id="submitButton">
                <i class="fas fa-save"></i> <?= $editData ? 'Update Jadwal' : 'Simpan Jadwal'; ?>
              </button>
            </div>
          </form>
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
    // JS kecil buat hamburger menu
    document.addEventListener('DOMContentLoaded', function() {
      const hamburger = document.getElementById('hamburger');
      const sidebar   = document.getElementById('sidebar');
      const overlay   = document.getElementById('sidebar-overlay');

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
  </script>
</body>
</html>
