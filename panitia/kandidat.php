<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

/**
 * Ambil kandidat RT tertentu (RT 01 / 02 / 03) di RW 01
 */
function getRtCandidates(mysqli $conn, string $rt, int $id_periode = 1)
{
    $sql = "
        SELECT id_calon, nama, rt, rw, foto, visi, misi
        FROM kandidat
        WHERE rt = ?
          AND rw = '01'
          AND id_periode = ?
        ORDER BY id_calon ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $rt, $id_periode);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Ambil kandidat RW 01 (rt NULL/kosong)
 */
function getRwCandidates(mysqli $conn, int $id_periode = 1)
{
    $sql = "
        SELECT id_calon, nama, rt, rw, foto, visi, misi
        FROM kandidat
        WHERE (rt IS NULL OR rt = '')
          AND rw = '01'
          AND id_periode = ?
        ORDER BY id_calon ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_periode);
    $stmt->execute();
    return $stmt->get_result();
}

// ambil data untuk setiap blok
$rt001 = getRtCandidates($conn, '01', 1);
$rt002 = getRtCandidates($conn, '02', 1);
$rt003 = getRtCandidates($conn, '03', 1);
$rw001 = getRwCandidates($conn, 1);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Data Kandidat</title>
  <link rel="stylesheet" href="/css/dashboard-panitia.css">
  <link rel="stylesheet" href="/css/kandidat.css">
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
        <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Jadwal Pemilihan</a></li>
        <li><a href="data-pemilih.php"><i class="fas fa-users"></i> Data Warga</a></li>
        <li><a href="kandidat.php" class="active"><i class="fas fa-user-tie"></i> Data Kandidat</a></li>
        <li><a href="rekap.php"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil.php"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <div class="content-page">
        <div class="page-header">
          <h1>Calon Kandidat</h1>
          <p class="subtitle">Perumahan Griya Harmoni</p>
        </div>

        <!-- ====== RT 01 ====== -->
        <section class="candidate-section">
          <div class="section-title">
            <span>Calon RT 01</span>
            <a href="kandidat-form.php?rt=01&rw=01" class="btn-add">
              <i class="fas fa-plus"></i> Tambah Kandidat
            </a>
          </div>

          <div class="candidate-grid">
            <?php if ($rt001->num_rows === 0): ?>
              <div class="empty-grid-message">
                <i class="fas fa-user-plus"></i>
                <p>Belum ada kandidat untuk RT 01</p>
              </div>
            <?php else: ?>
              <?php while ($row = $rt001->fetch_assoc()): ?>
                <div class="candidate-card">
                  <div class="candidate-info">
                    <?php $foto = $row['foto'] ?: 'default.png'; ?>
                    <img src="/assets/kandidat/<?= htmlspecialchars($foto); ?>"
                         alt="<?= htmlspecialchars($row['nama']); ?>">
                    <div class="candidate-text">
                      <h4><?= htmlspecialchars($row['nama']); ?></h4>
                      <p>RT <?= htmlspecialchars($row['rt']); ?> / RW <?= htmlspecialchars($row['rw']); ?></p>
                      <p>Visi : <?= htmlspecialchars($row['visi']); ?>
                      <BR></BR>
                      <p>misi : <?= htmlspecialchars($row['misi']);?></p>
                      
                    </div>
                  </div>
                  <div class="candidate-actions">
                    <a href="kandidat-form.php?id=<?= (int)$row['id_calon']; ?>" 
                       class="btn-small btn-edit">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="kandidat-hapus.php?id=<?= (int)$row['id_calon']; ?>" 
                       class="btn-small btn-delete"
                       onclick="return confirm('Yakin hapus kandidat <?= htmlspecialchars($row['nama']); ?>?');">
                      <i class="fas fa-trash"></i> Hapus
                    </a>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php endif; ?>
          </div>
        </section>

        <!-- ====== RT 02 ====== -->
        <section class="candidate-section">
          <div class="section-title">
            <span>Calon RT 02</span>
            <a href="kandidat-form.php?rt=02&rw=01" class="btn-add">
              <i class="fas fa-plus"></i> Tambah Kandidat
            </a>
          </div>

          <div class="candidate-grid">
            <?php if ($rt002->num_rows === 0): ?>
              <div class="empty-grid-message">
                <i class="fas fa-user-plus"></i>
                <p>Belum ada kandidat untuk RT 02</p>
              </div>
            <?php else: ?>
              <?php while ($row = $rt002->fetch_assoc()): ?>
                <div class="candidate-card">
                  <div class="candidate-info">
                    <?php $foto = $row['foto'] ?: 'default.png'; ?>
                    <img src="/assets/kandidat/<?= htmlspecialchars($foto); ?>"
                         alt="<?= htmlspecialchars($row['nama']); ?>">
                    <div class="candidate-text">
                      <h4><?= htmlspecialchars($row['nama']); ?></h4>
                      <p>RT <?= htmlspecialchars($row['rt']); ?> / RW <?= htmlspecialchars($row['rw']); ?></p>
                      <p>Visi : <?= htmlspecialchars($row['visi']); ?></p>
                      <BR></BR>
                       <p>misi : <?= htmlspecialchars($row['misi']);?></p>
                    </div>
                  </div>
                  <div class="candidate-actions">
                    <a href="kandidat-form.php?id=<?= (int)$row['id_calon']; ?>" 
                       class="btn-small btn-edit">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="kandidat-hapus.php?id=<?= (int)$row['id_calon']; ?>" 
                       class="btn-small btn-delete"
                       onclick="return confirm('Yakin hapus kandidat <?= htmlspecialchars($row['nama']); ?>?');">
                      <i class="fas fa-trash"></i> Hapus
                    </a>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php endif; ?>
          </div>
        </section>

        <!-- ====== RT 03 ====== -->
        <section class="candidate-section">
          <div class="section-title">
            <span>Calon RT 03</span>
            <a href="kandidat-form.php?rt=03&rw=01" class="btn-add">
              <i class="fas fa-plus"></i> Tambah Kandidat
            </a>
          </div>

          <div class="candidate-grid">
            <?php if ($rt003->num_rows === 0): ?>
              <div class="empty-grid-message">
                <i class="fas fa-user-plus"></i>
                <p>Belum ada kandidat untuk RT 03</p>
              </div>
            <?php else: ?>
              <?php while ($row = $rt003->fetch_assoc()): ?>
                <div class="candidate-card">
                  <div class="candidate-info">
                    <?php $foto = $row['foto'] ?: 'default.png'; ?>
                    <img src="/assets/kandidat/<?= htmlspecialchars($foto); ?>"
                         alt="<?= htmlspecialchars($row['nama']); ?>">
                    <div class="candidate-text">
                      <h4><?= htmlspecialchars($row['nama']); ?></h4>
                      <p>RT <?= htmlspecialchars($row['rt']); ?> / RW <?= htmlspecialchars($row['rw']); ?></p>
                      <p>Visi : <?= htmlspecialchars($row['visi']); ?></p>
                      <BR></BR>
                       <p>misi : <?= htmlspecialchars($row['misi']);?></p>
                    </div>
                  </div>
                  <div class="candidate-actions">
                    <a href="kandidat-form.php?id=<?= (int)$row['id_calon']; ?>" 
                       class="btn-small btn-edit">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="kandidat-hapus.php?id=<?= (int)$row['id_calon']; ?>" 
                       class="btn-small btn-delete"
                       onclick="return confirm('Yakin hapus kandidat <?= htmlspecialchars($row['nama']); ?>?');">
                      <i class="fas fa-trash"></i> Hapus
                    </a>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php endif; ?>
          </div>
        </section>

        <!-- ====== RW 01 ====== -->
        <section class="candidate-section">
          <div class="section-title">
            <span>Calon RW 01</span>
            <a href="kandidat-form.php?rw=01" class="btn-add">
              <i class="fas fa-plus"></i> Tambah Kandidat
            </a>
          </div>

          <div class="candidate-grid">
            <?php if ($rw001->num_rows === 0): ?>
              <div class="empty-grid-message">
                <i class="fas fa-user-plus"></i>
                <p>Belum ada kandidat untuk RW 01</p>
              </div>
            <?php else: ?>
              <?php while ($row = $rw001->fetch_assoc()): ?>
                <div class="candidate-card">
                  <div class="candidate-info">
                    <?php $foto = $row['foto'] ?: 'default.png'; ?>
                    <img src="/assets/kandidat/<?= htmlspecialchars($foto); ?>"
                         alt="<?= htmlspecialchars($row['nama']); ?>">
                    <div class="candidate-text">
                      <h4><?= htmlspecialchars($row['nama']); ?></h4>
                      <p>Visi : <?= htmlspecialchars($row['visi']); ?></p>
                      <BR></BR>
                       <p>misi : <?= htmlspecialchars($row['misi']);?></p>
                    </div>
                  </div>
                  <div class="candidate-actions">
                    <a href="kandidat-form.php?id=<?= (int)$row['id_calon']; ?>" 
                       class="btn-small btn-edit">
                      <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="kandidat-hapus.php?id=<?= (int)$row['id_calon']; ?>" 
                       class="btn-small btn-delete"
                       onclick="return confirm('Yakin hapus kandidat <?= htmlspecialchars($row['nama']); ?>?');">
                      <i class="fas fa-trash"></i> Hapus
                    </a>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php endif; ?>
          </div>
        </section>

        <button class="btn-back" onclick="window.history.back()">
          <i class="fas fa-arrow-left"></i> Kembali
        </button>
      </div>

      <footer>
        <div class="footer-content">
          <p>© 2025 SipeL • Sistem Pemilihan Elektronik RT/RW</p>
        </div>
      </footer>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
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
