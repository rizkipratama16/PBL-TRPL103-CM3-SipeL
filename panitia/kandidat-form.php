<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = $id > 0 ? 'edit' : 'add';

$nama = '';
$rt   = '';
$rw   = '01';
$foto = '';
$visi = '';
$misi = '';
$id_periode = 1; // sementara fix 1 dulu

// ========== PREFILL DATA SAAT EDIT ==========
if ($mode === 'edit') {
    $stmt = $conn->prepare("
        SELECT id_calon, nama, rt, rw, foto, visi, misi, id_periode
        FROM kandidat
        WHERE id_calon = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $nama       = $row['nama'];
        $rt         = $row['rt'];
        $rw         = $row['rw'];
        $foto       = $row['foto'];
        $visi       = $row['visi'];
        $misi       = $row['misi'];
        $id_periode = (int)$row['id_periode'];
    } else {
        die("Data kandidat tidak ditemukan.");
    }
}

// Prefill default kalau ADD dari query string
if ($mode === 'add') {
    if (isset($_GET['rt'])) {
        $rt = $_GET['rt']; // 01 / 02 / 03
    }
    if (isset($_GET['rw'])) {
        $rw = $_GET['rw'];
    }
}

// ========== HANDLE SUBMIT ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode       = $_POST['mode'] ?? 'add';
    $id_calon   = isset($_POST['id_calon']) ? (int)$_POST['id_calon'] : 0;

    $nama = trim($_POST['nama'] ?? '');
    $rt   = trim($_POST['rt'] ?? '');
    $rw   = trim($_POST['rw'] ?? '');
    $foto = trim($_POST['foto'] ?? '');
    $visi = trim($_POST['visi'] ?? '');
    $misi = trim($_POST['misi'] ?? '');

    if ($rw === '')  $rw   = '01';
    if ($foto === '') $foto = 'default.png';

    if ($nama === '' || $visi === '' || $misi === '') {
        $error = "Nama, visi, dan misi wajib diisi.";
    } else {
        // ===== EDIT =====
        if ($mode === 'edit' && $id_calon > 0) {

            if ($rt === '') {
                // calon RW → RT dikosongkan
                $rt = null;
            }

            $sql = "
                UPDATE kandidat
                SET nama = ?, rt = ?, rw = ?, foto = ?, visi = ?, misi = ?, id_periode = ?
                WHERE id_calon = ?
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssii",
                $nama,
                $rt,
                $rw,
                $foto,
                $visi,
                $misi,
                $id_periode,
                $id_calon
            );
            if (!$stmt->execute()) {
                die("Gagal update kandidat: " . $stmt->error);
            }

        // ===== TAMBAH =====
        } else {
            // generate id_calon manual (MAX+1)
            $resMax = $conn->query("SELECT COALESCE(MAX(id_calon), 0) AS last_id FROM kandidat");
            $rowMax = $resMax->fetch_assoc();
            $newId  = (int)$rowMax['last_id'] + 1;

            if ($rt === '') {
                $rt = null; // calon RW
            }

            $sql = "
                INSERT INTO kandidat (id_calon, nama, rt, rw, foto, visi, misi, id_periode)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "issssssi",
                $newId,
                $nama,
                $rt,
                $rw,
                $foto,
                $visi,
                $misi,
                $id_periode
            );
            if (!$stmt->execute()) {
                die("Gagal tambah kandidat: " . $stmt->error);
            }
        }

        header("Location: kandidat.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $mode === 'edit' ? 'Edit Kandidat' : 'Tambah Kandidat'; ?></title>
  <link rel="stylesheet" href="/css/dashboard-panitia.css">
  <!-- kalau mau, bikin css khusus form kandidat -->
  <style>
    .form-wrapper{
      max-width: 800px;
      margin: 80px auto 40px;
      background:#fff;
      padding:24px 28px;
      border-radius:16px;
      box-shadow:0 10px 30px rgba(0,0,0,.06);
      font-family:'Poppins',sans-serif;
    }
    .form-wrapper h1{font-size:24px;margin-bottom:16px;}
    .form-group{margin-bottom:16px;}
    .form-group label{display:block;margin-bottom:6px;font-weight:500;}
    .form-group input,
    .form-group textarea{
      width:100%;
      padding:10px 12px;
      border-radius:8px;
      border:1px solid #d0d5dd;
      font:inherit;
      resize:vertical;
    }
    .form-actions{
      margin-top:20px;
      display:flex;
      justify-content:flex-end;
      gap:10px;
    }
    .btn{padding:10px 18px;border-radius:8px;border:none;cursor:pointer;font:inherit;}
    .btn-primary{background:#005adc;color:#fff;}
    .btn-secondary{background:#e4e7ec;color:#101828;text-decoration:none;display:inline-flex;align-items:center;}
    .alert{padding:10px 12px;border-radius:8px;margin-bottom:12px;font-size:14px;}
    .alert-danger{background:#fee4e2;color:#b42318;}
  </style>
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
    </div>
  </nav>

  <div class="form-wrapper">
    <h1><?= $mode === 'edit' ? 'Edit Kandidat' : 'Tambah Kandidat'; ?></h1>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="mode" value="<?= htmlspecialchars($mode); ?>">
      <input type="hidden" name="id_calon" value="<?= (int)$id; ?>">

      <div class="form-group">
        <label>Nama Kandidat</label>
        <input type="text" name="nama" value="<?= htmlspecialchars($nama); ?>" required>
      </div>

      <div class="form-group">
        <label>RT (boleh kosong untuk calon RW)</label>
        <input type="text" name="rt" placeholder="01 / 02 / 03"
               value="<?= htmlspecialchars($rt); ?>">
      </div>

      <div class="form-group">
        <label>RW</label>
        <input type="text" name="rw" value="<?= htmlspecialchars($rw); ?>" required>
      </div>

      <div class="form-group">
        <label>Nama file foto (di folder <code>assets/kandidat</code>)</label>
        <input type="text" name="foto" placeholder="rt01_calon1.jpg"
               value="<?= htmlspecialchars($foto); ?>">
      </div>

      <div class="form-group">
        <label>Visi</label>
        <textarea name="visi" rows="3" required><?= htmlspecialchars($visi); ?></textarea>
      </div>

      <div class="form-group">
        <label>Misi</label>
        <textarea name="misi" rows="4" required><?= htmlspecialchars($misi); ?></textarea>
      </div>

      <div class="form-actions">
        <a href="kandidat.php" class="btn btn-secondary">Kembali</a>
        <button type="submit" class="btn btn-primary">
          <?= $mode === 'edit' ? 'Update Kandidat' : 'Simpan Kandidat'; ?>
        </button>
      </div>
    </form>
  </div>
</body>
</html>
