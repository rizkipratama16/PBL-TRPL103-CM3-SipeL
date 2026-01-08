<?php
session_start();

// Cek sudah login & role panitia
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'panitia') {
    header('Location: login.php');
    exit;
}

require_once '../db_connect.php';

// Pesan status
$message = '';
$message_type = '';

// Handle tambah / edit / delete data pemilih
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Tambah
    if ($_POST['action'] === 'add') {
        $nik    = trim($_POST['nik']);
        $nama   = trim($_POST['nama']);
        $alamat = trim($_POST['alamat']);
        $rt     = trim($_POST['rt']);
        $rw     = trim($_POST['rw']);

        // Validasi NIK 16 digit
        if (strlen($nik) !== 16 || !is_numeric($nik)) {
            $message = 'NIK harus 16 digit angka!';
            $message_type = 'error';
        } else {
            // Cek NIK duplikat
            $check = $conn->prepare("SELECT COUNT(*) FROM warga WHERE nik = ?");
            $check->bind_param("s", $nik);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            if ($count > 0) {
                $message = 'NIK sudah terdaftar!';
                $message_type = 'error';
            } else {
                // Generate id_wilayah dari RT
                $id_wilayah = 0;
                if ($rt === '01' || $rt === '01') $id_wilayah = 1;
                elseif ($rt === '02' || $rt === '02') $id_wilayah = 2;
                elseif ($rt === '03' || $rt === '03') $id_wilayah = 3;

               // Password default = NIK (tapi disimpan dalam bentuk hash)
               $password_plain = $nik;
               $password_hash  = password_hash($password_plain, PASSWORD_DEFAULT);

               $stmt = $conn->prepare("
               INSERT INTO warga (nik, nama, rt, rw, alamat, password, password_hash, id_wilayah)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ");
// tetap isi kolom password untuk kompatibilitas (opsional), hash juga diisi
               $stmt->bind_param("sssssssi", $nik, $nama, $rt, $rw, $alamat, $password_plain, $password_hash, $id_wilayah);


                if ($stmt->execute()) {
                    $message = 'Data pemilih berhasil ditambahkan!';
                    $message_type = 'success';
                } else {
                    $message = 'Gagal menambahkan data: ' . $conn->error;
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }

    // Edit
    if ($_POST['action'] === 'edit') {
        $nik    = trim($_POST['nik']);
        $nama   = trim($_POST['nama']);
        $alamat = trim($_POST['alamat']);
        $rt     = trim($_POST['rt']);
        $rw     = trim($_POST['rw']);
        $old_nik = $_POST['old_nik'];

        // Generate id_wilayah dari RT
        $id_wilayah = 0;
        if ($rt === '01' || $rt === '01') $id_wilayah = 1;
        elseif ($rt === '02' || $rt === '02') $id_wilayah = 2;
        elseif ($rt === '03' || $rt === '03') $id_wilayah = 3;

        // Jika NIK berubah, cek duplikat
        if ($nik !== $old_nik) {
            $check = $conn->prepare("SELECT COUNT(*) FROM warga WHERE nik = ?");
            $check->bind_param("s", $nik);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();

            if ($count > 0) {
                $message = 'NIK baru sudah terdaftar!';
                $message_type = 'error';
            }
        }

        if (empty($message)) {
            $stmt = $conn->prepare("
                UPDATE warga
                SET nik = ?, nama = ?, rt = ?, rw = ?, alamat = ?, id_wilayah = ?
                WHERE nik = ?
            ");
            $stmt->bind_param("sssssis", $nik, $nama, $rt, $rw, $alamat, $id_wilayah, $old_nik);

            if ($stmt->execute()) {
                $message = 'Data pemilih berhasil diperbarui!';
                $message_type = 'success';
            } else {
                $message = 'Gagal memperbarui data: ' . $conn->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    }

    // Delete
    if ($_POST['action'] === 'delete') {
        $nik = $_POST['nik'];

        $stmt = $conn->prepare("DELETE FROM warga WHERE nik = ?");
        $stmt->bind_param("s", $nik);

        if ($stmt->execute()) {
            $message = 'Data pemilih berhasil dihapus!';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus data: ' . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Jika mode edit, ambil data pemilih
$editing_voter = null;
if (isset($_GET['edit']) && $_GET['edit'] !== '') {
    $nik_edit = $_GET['edit'];
    $stmt = $conn->prepare("SELECT nik, nama, alamat, rt, rw FROM warga WHERE nik = ?");
    $stmt->bind_param("s", $nik_edit);
    $stmt->execute();
    $result_edit = $stmt->get_result();
    $editing_voter = $result_edit->fetch_assoc();
    $stmt->close();
}

// Ambil data pemilih dengan filter
$where_clauses = [];
$params = [];
$types = '';

// Filter search
if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = "%" . $_GET['search'] . "%";
    $where_clauses[] = "(warga.nik LIKE ? OR warga.nama LIKE ? OR warga.alamat LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sss';
}

// Filter RT
if (isset($_GET['rt']) && $_GET['rt'] !== '') {
    $where_clauses[] = "warga.rt = ?";
    $params[] = $_GET['rt'];
    $types .= 's';
}

// Filter RW
if (isset($_GET['rw']) && $_GET['rw'] !== '') {
    $where_clauses[] = "warga.rw = ?";
    $params[] = $_GET['rw'];
    $types .= 's';
}

// Query utama
$query = "SELECT warga.nik, warga.nama, warga.alamat, warga.rt, warga.rw,
                 warga.password, warga.status_voting, warga.waktu_voting,
                 wilayah.nama_wilayah
          FROM warga
          LEFT JOIN wilayah ON warga.id_wilayah = wilayah.id_wilayah";

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY warga.rt, warga.rw, warga.nama";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$voters = $result->fetch_all(MYSQLI_ASSOC);
$total_voters = count($voters);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Data Pemilih</title>
  <link rel="stylesheet" href="/css/dashboard-panitia.css">
  <link rel="stylesheet" href="/css/data-pemilih.css">
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
        <span>Halo, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Panitia'); ?></span>
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
      <div class="side-logo">Menu Panitia</div>
      <ul>
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Beranda</a></li>
        <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Jadwal Pemilihan</a></li>
        <li><a href="data-pemilih.php" class="active"><i class="fas fa-users"></i> Data Warga</a></li>
        <li><a href="kandidat.php"><i class="fas fa-user-tie"></i> Data Kandidat</a></li>
        <li><a href="rekap.php"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil.php"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <div class="content-page">
        <div class="page-header">
          <h1>Daftar Pemilih</h1>
          <p>Perumahan Griya Harmoni</p>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>" id="statusMessage">
          <span><?php echo htmlspecialchars($message); ?></span>
          <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <!-- FORM TAMBAH / EDIT (tanpa JS) -->
        <div class="table-card" id="form-pemilih">
          <h3 style="margin-bottom: 10px;">
            <?php echo $editing_voter ? 'Edit Data Pemilih' : 'Tambah Pemilih Baru'; ?>
          </h3>
          <form method="POST" class="voter-form">
            <input type="hidden" name="action" value="<?php echo $editing_voter ? 'edit' : 'add'; ?>">
            <input type="hidden" name="old_nik" value="<?php echo $editing_voter['nik'] ?? ''; ?>">

            <div class="form-group">
              <label for="nik">NIK</label>
              <input type="text" id="nik" name="nik" required maxlength="16"
                     pattern="[0-9]{16}" title="NIK harus 16 digit angka"
                     placeholder="Masukkan NIK (16 digit angka)"
                     value="<?php echo htmlspecialchars($editing_voter['nik'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="nama">Nama Lengkap</label>
              <input type="text" id="nama" name="nama" required
                     placeholder="Masukkan nama lengkap"
                     value="<?php echo htmlspecialchars($editing_voter['nama'] ?? ''); ?>">
            </div>
            <div class="form-group">
              <label for="alamat">Alamat</label>
              <input type="text" id="alamat" name="alamat" required
                     placeholder="Masukkan alamat lengkap"
                     value="<?php echo htmlspecialchars($editing_voter['alamat'] ?? ''); ?>">
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="rt">RT</label>
                <select id="rt" name="rt" required>
                  <option value="">Pilih RT</option>
                  <option value="01" <?php echo (isset($editing_voter['rt']) && $editing_voter['rt'] === '01') ? 'selected' : ''; ?>>RT 01</option>
                  <option value="02" <?php echo (isset($editing_voter['rt']) && $editing_voter['rt'] === '02') ? 'selected' : ''; ?>>RT 02</option>
                  <option value="03" <?php echo (isset($editing_voter['rt']) && $editing_voter['rt'] === '03') ? 'selected' : ''; ?>>RT 03</option>
                </select>
              </div>
              <div class="form-group">
                <label for="rw">RW</label>
                <select id="rw" name="rw" required>
                  <option value="">Pilih RW</option>
                  <option value="01" <?php echo (isset($editing_voter['rw']) && $editing_voter['rw'] === '01') ? 'selected' : ''; ?>>RW 01</option>
                </select>
              </div>
            </div>
            <div class="modal-actions" style="margin-top: 10px;">
              <?php if ($editing_voter): ?>
                <a href="data-pemilih.php" class="btn btn-secondary">Batal Edit</a>
              <?php endif; ?>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan
              </button>
            </div>
          </form>
        </div>

        <!-- Search & Filter -->
        <form method="GET" action="" class="search-filter">
          <div class="search-box">
            <input type="text" name="search" id="searchInput"
                   placeholder="Cari berdasarkan NIK, nama, atau alamat..."
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
          </div>
          <div class="filter-select">
            <select name="rt" id="rtFilter">
              <option value="">Semua RT</option>
              <option value="01" <?php echo (isset($_GET['rt']) && $_GET['rt'] === '01') ? 'selected' : ''; ?>>RT 01</option>
              <option value="02" <?php echo (isset($_GET['rt']) && $_GET['rt'] === '02') ? 'selected' : ''; ?>>RT 02</option>
              <option value="03" <?php echo (isset($_GET['rt']) && $_GET['rt'] === '03') ? 'selected' : ''; ?>>RT 03</option>
            </select>
          </div>
          <div class="filter-select">
            <select name="rw" id="rwFilter">
              <option value="">Semua RW</option>
              <option value="01" <?php echo (isset($_GET['rw']) && $_GET['rw'] === '01') ? 'selected' : ''; ?>>RW 01</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Cari
          </button>
          <button type="button" class="btn btn-secondary" onclick="window.location.href='data-pemilih.php'">
            <i class="fas fa-redo"></i> Reset
          </button>
        </form>

        <!-- TABEL DATA -->
        <div class="table-card">
          <div class="table-info">
            <p>Total Data: <strong><?php echo $total_voters; ?></strong> pemilih</p>
          </div>

          <?php if ($total_voters > 0): ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Alamat</th>
                <th>RT</th>
                <th>RW</th>
                <th>Password</th>
                <th>Status Voting</th>
                <th>Waktu Voting</th>
                <th>Wilayah</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($voters as $index => $voter): ?>
              <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($voter['nik']); ?></td>
                <td><?php echo htmlspecialchars($voter['nama']); ?></td>
                <td><?php echo htmlspecialchars($voter['alamat']); ?></td>
                <td><?php echo htmlspecialchars($voter['rt']); ?></td>
                <td><?php echo htmlspecialchars($voter['rw']); ?></td>
                <td><?php echo htmlspecialchars($voter['password']); ?></td>
                <td>
                  <span class="status-badge <?php echo $voter['status_voting'] === 'sudah' ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $voter['status_voting'] === 'sudah' ? 'Sudah' : 'Belum'; ?>
                  </span>
                </td>
                <td>
                  <?php echo $voter['waktu_voting'] ? date('d-m-Y H:i', strtotime($voter['waktu_voting'])) : '-'; ?>
                </td>
                <td><?php echo htmlspecialchars($voter['nama_wilayah'] ?? '-'); ?></td>
                <td>
                  <a href="data-pemilih.php?edit=<?php echo urlencode($voter['nik']); ?>"
                     class="btn-icon btn-edit" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>

                  <form method="POST" style="display:inline-block;"
                        onsubmit="return confirm('Yakin hapus pemilih <?php echo htmlspecialchars(addslashes($voter['nama'])); ?>?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="nik" value="<?php echo htmlspecialchars($voter['nik']); ?>">
                    <button type="submit" class="btn-icon btn-delete" title="Hapus">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <div id="noDataMessage" class="no-data-message" style="display: block;">
            <i class="fas fa-users-slash"></i>
            <p>Belum ada data pemilih. Silakan tambah data pemilih baru.</p>
          </div>
          <?php endif; ?>

          <div class="actions">
            <button class="btn btn-secondary" onclick="window.history.back()">
              <i class="fas fa-arrow-left"></i> Kembali
            </button>
            <a href="#form-pemilih" class="btn btn-primary">
              <i class="fas fa-plus"></i> Tambah Pemilih
            </a>
          </div>
        </div>
      </div>

      <footer>
        <div class="footer-content">
          <p>© 2025 SipeL • Sistem Pemilihan Elektronik RT/RW</p>
        </div>
      </footer>
    </main>
  </div>

  <!-- JS CUMA UNTUK HAMBURGER + SIDEBAR -->
  <script>
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

      document.querySelectorAll('.sidebar a').forEach(link => {
        link.addEventListener('click', () => {
          hamburger.classList.remove('active');
          sidebar.classList.remove('active');
          if (overlay) overlay.classList.remove('active');
        });
      });

      window.addEventListener('scroll', () => {
        if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
          hamburger.classList.remove('active');
          sidebar.classList.remove('active');
          if (overlay) overlay.classList.remove('active');
        }
      });
    });
  </script>
</body>
</html>
