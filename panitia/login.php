<?php
session_start();

$error = '';
$username_value = ''; // buat isi ulang field form

// koneksi ke database data_sipel
require_once __DIR__ . '/../db_connect.php';

// Kalau sudah login sebagai panitia, lempar ke dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'panitia') {
    header('Location: dashboard.php');
    exit;
}

// Proses login saat form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // pakai null-coalescing, aman kalau key belum ada
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $username_value = $username; // buat diisi lagi ke input

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi!';
    } else {
        // kolom di tabel admin: id_panitia, Nama, username, password
        $stmt = $conn->prepare("
            SELECT id_panitia, username, password, Nama
            FROM panitia
            WHERE username = ?
            LIMIT 1
        ");
        if (!$stmt) {
            die('Query error: ' . $conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($password === $user['password']) {
                $_SESSION['user_id']    = $user['id_panitia'];
                $_SESSION['username']   = $user['username'];
                $_SESSION['nama']       = $user['Nama'];
                $_SESSION['role']       = 'panitia';
                $_SESSION['login_time'] = time();

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Login Panitia</title>
  <link rel="stylesheet" href="/css/login.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar">
    <div class="nav-container">
      <div class="logo">
        <img src="/assets/logo-sipelV3.png" alt="SipeL Logo" class="logo-img">
        <span>SipeL</span>
      </div>
    </div>
  </nav>

  <!-- Hero Section dengan Form Login -->
  <section class="hero" style="background-image: linear-gradient(180deg, rgba(14,165,233,0.15), rgba(14,165,233,0.25)), url('https://www.99.co/id/img-regional/800/800/fit/true/production/image/user/1ba2454f-8241-42d7-8750-56da6941d091/2025-01-07-06-54-53-0e31fd4b-35b7-415d-9e3c-88627c637fcd.jpg');">
    <div class="form-container">
      <h2>Login Panitia</h2>
      
      <!-- Tampilkan error jika ada -->
      <?php if (!empty($error)): ?>
        <div class="error-message">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      
      <form action="login.php" method="post">
        <div class="form-group">
          <label for="username">Username</label>
         <input
           type="text"
           id="username"
           name="username"
           placeholder="Masukkan username"
           value="<?php echo htmlspecialchars($username_value); ?>"
           required
         >

        </div>
        <div class="form-group">
          <label for="password">Sandi</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukkan sandi"
            required
          >
        </div>
        <button type="submit" class="btn-submit">Masuk</button>
      </form>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <div class="footer-content">
      <p>© 2025 SipeL • Sistem Pemilihan Elektronik RT/RW</p>
    </div>
  </footer>
</body>
</html>
