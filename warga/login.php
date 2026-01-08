<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../db_connect.php';

/* Kalau sudah login warga, arahkan sesuai password_changed */
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'warga' && !empty($_SESSION['nik'])) {
    $nikSess = $_SESSION['nik'];

    $q = $conn->prepare("SELECT password_changed FROM warga WHERE nik = ? LIMIT 1");
    $q->bind_param("s", $nikSess);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();

    if ($row && (int)$row['password_changed'] === 0) {
        header("Location: ubah-password.php");
        exit;
    }

    header("Location: dashboard.php");
    exit;
}

/* Proses login */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nik      = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    $stmt = $conn->prepare("
        SELECT nik, nama, password_hash, password, password_changed
        FROM warga
        WHERE nik = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) {
        echo "<script>alert('NIK yang Anda masukkan tidak terdaftar'); window.location.href='login.php';</script>";
        exit;
    }

    $ok = false;

    // Prioritas hash (baru)
    if (!empty($data['password_hash'])) {
        $ok = password_verify($password, $data['password_hash']);
    } else {
        // Fallback plaintext (lama)
        $ok = hash_equals((string)($data['password'] ?? ''), $password);
    }

    if (!$ok) {
        echo "<script>alert('Password yang Anda masukkan salah'); window.location.href='login.php';</script>";
        exit;
    }

    // Login sukses
    $_SESSION['nik']  = $data['nik'];
    $_SESSION['nama'] = $data['nama'];
    $_SESSION['role'] = 'warga';

    // First login wajib ganti password
    if ((int)$data['password_changed'] === 0) {
        header("Location: ubah-password.php");
        exit;
    }

    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Masuk</title>
  <link rel="stylesheet" href="/css/login.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <nav class="navbar">
    <div class="nav-container">
      <div class="logo">
        <img src="/assets/logo-sipelV3.png" alt="SipeL Logo" class="logo-img">
        <span>SipeL</span>
      </div>
    </div>
  </nav>

  <section class="hero" style="background-image: linear-gradient(180deg, rgba(14,165,233,0.15), rgba(14,165,233,0.25)), url('https://www.99.co/id/img-regional/800/800/fit/true/production/image/user/1ba2454f-8241-42d7-8750-56da6941d091/2025-01-07-06-54-53-0e31fd4b-35b7-415d-9e3c-88627c637fcd.jpg');">
    <div class="form-container">
      <h2>Login Warga</h2>
      <form action="login.php" method="post">
        <div class="form-group">
          <label for="username">NIK</label>
          <input type="text" id="username" name="username" placeholder="Masukkan username" required>
        </div>

        <div class="form-group">
          <label for="password">Sandi</label>
          <input type="password" id="password" name="password" placeholder="Masukkan sandi" required>
        </div>

        <button type="submit" class="btn-submit">Masuk</button>
      </form>
    </div>
  </section>

  <?php require '../layout/footer.php' ?>
</body>
</html>
