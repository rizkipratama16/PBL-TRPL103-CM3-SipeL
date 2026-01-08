<?php
require_once 'guard-warga.php'; // harus nyediain: $conn, $nik, $nama

$err = '';
$ok  = '';

// =======================
// CSRF TOKEN (ADD)
// =======================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ambil status sekarang (bedain first login vs change biasa) */
$st = $conn->prepare("SELECT password_changed FROM warga WHERE nik = ? LIMIT 1");
$st->bind_param("s", $nik);
$st->execute();
$rowStatus = $st->get_result()->fetch_assoc();
$st->close();

$isFirstLogin = ($rowStatus && (int)$rowStatus['password_changed'] === 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =======================
    // CSRF CHECK (ADD)
    // =======================
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if ($csrf === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        $err = 'Permintaan tidak valid (CSRF). Silakan refresh halaman dan coba lagi.';
    } else {

        $old = (string)($_POST['old_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $cfn = (string)($_POST['confirm_password'] ?? '');

        if ($old === '' || $new === '' || $cfn === '') {
            $err = 'Semua field wajib diisi.';
        } elseif (strlen($new) < 8) {
            $err = 'Password baru minimal 8 karakter.';
        } elseif ($new !== $cfn) {
            $err = 'Konfirmasi password tidak sama.';
        } else {
            $stmt = $conn->prepare("SELECT password, password_hash FROM warga WHERE nik = ? LIMIT 1");
            $stmt->bind_param("s", $nik);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$data) {
                $err = 'Akun tidak ditemukan.';
            } else {
                $passOk = false;

                if (!empty($data['password_hash'])) {
                    $passOk = password_verify($old, $data['password_hash']);
                } else {
                    $passOk = hash_equals((string)$data['password'], (string)$old);
                }

                if (!$passOk) {
                    $err = 'Password lama salah.';
                } else {
                    $newHash = password_hash($new, PASSWORD_DEFAULT);

                    $up = $conn->prepare("UPDATE warga
                                          SET password_hash = ?, password_changed = 1, password = ''
                                          WHERE nik = ?
                                          LIMIT 1");
                    $up->bind_param("ss", $newHash, $nik);

                    if ($up->execute()) {
                        $up->close();

                        // rotate token biar token lama gak kepake lagi
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        if ($isFirstLogin) {
                            header("Location: dashboard.php");
                            exit;
                        } else {
                            $ok = "Password berhasil diubah.";
                        }
                    } else {
                        $err = 'Gagal menyimpan password. Coba lagi.';
                        $up->close();
                    }
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Ubah Password</title>

  <link rel="stylesheet" href="/css/ubah-password.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <div class="navbar">
    <div class="brand">
      <img src="/assets/logo-sipelV3.png" alt="SipeL">
      <span>SipeL</span>
    </div>
  </div>

  <div class="wrap">
    <div class="card">
      <h1>Ubah Password</h1>
      <div class="sub">Akun: <b><?php echo htmlspecialchars($nama); ?></b></div>

      <?php if ($isFirstLogin): ?>
        <div class="notice">Demi keamanan, kamu wajib mengganti password saat login pertama.</div>
      <?php endif; ?>

      <?php if ($err): ?>
        <div class="alert err"><?php echo htmlspecialchars($err); ?></div>
      <?php endif; ?>

      <?php if ($ok): ?>
        <div class="alert ok"><?php echo htmlspecialchars($ok); ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <label>Password Lama</label>
        <div class="field">
          <input type="password" id="old_password" name="old_password" required>
          <button type="button" class="toggle-pass" data-target="old_password" aria-label="Toggle password">
            <i class="fa-solid fa-eye-slash"></i>
          </button>
        </div>

        <label>Password Baru</label>
        <div class="field">
          <input type="password" id="new_password" name="new_password" minlength="8" required placeholder="Minimal 8 karakter">
          <button type="button" class="toggle-pass" data-target="new_password" aria-label="Toggle password">
            <i class="fa-solid fa-eye-slash"></i>
          </button>
        </div>

        <label>Konfirmasi Password Baru</label>
        <div class="field">
          <input type="password" id="confirm_password" name="confirm_password" minlength="8" required placeholder="Ulangi password baru">
          <button type="button" class="toggle-pass" data-target="confirm_password" aria-label="Toggle password">
            <i class="fa-solid fa-eye-slash"></i>
          </button>
        </div>

        <div class="actions">
          <button class="btn primary" type="submit">Simpan</button>
          <button class="btn secondary" type="button" onclick="window.location.href='../logout.php'">Keluar</button>
        </div>
      </form>
    </div>
  </div>

  <footer>
    <div class="container">
      <div class="footer-content">
        <p>© 2025 SipeL • Sistem Pemilihan Elektronik RT/RW</p>
      </div>
    </div>
  </footer>

<script>
  document.querySelectorAll('.toggle-pass').forEach(btn => {
    const input = document.getElementById(btn.dataset.target);
    const icon  = btn.querySelector('i');
    if (!input || !icon) return;

    function sync() {
      if (input.type === 'password') {
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
      } else {
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
      }
    }

    sync();

    btn.addEventListener('click', () => {
      input.type = (input.type === 'password') ? 'text' : 'password';
      sync();
    });
  });
</script>

</body>
</html>
