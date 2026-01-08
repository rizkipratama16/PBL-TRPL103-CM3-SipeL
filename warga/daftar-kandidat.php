<?php
require_once 'guard-warga.php'; // sudah session_start + db_connect + cek role+nik

// Ambil data kandidat dari database
$kandidat_rt01 = [];
$kandidat_rt02 = [];
$kandidat_rt03 = [];
$kandidat_rw01 = [];

// base path foto kandidat
$base_foto_path = "../assets/kandidat/";

// Query untuk kandidat RT 01
$query_rt01 = "SELECT id_calon, nama, rt, rw, foto, visi, misi 
               FROM kandidat 
               WHERE id_periode = 1 AND rt = '01' AND rw = '01'
               ORDER BY id_calon";
$result_rt01 = $conn->query($query_rt01);
if ($result_rt01 && $result_rt01->num_rows > 0) {
    while ($row = $result_rt01->fetch_assoc()) {
        if (!empty($row['foto'])) {
            $row['foto'] = $base_foto_path . $row['foto'];
        } else {
            $row['foto'] = $base_foto_path . "default.jpg";
        }
        $kandidat_rt01[] = $row;
    }
}

// Query untuk kandidat RT 02
$query_rt02 = "SELECT id_calon, nama, rt, rw, foto, visi, misi 
               FROM kandidat 
               WHERE id_periode = 1 AND rt = '02' AND rw = '01'
               ORDER BY id_calon";
$result_rt02 = $conn->query($query_rt02);
if ($result_rt02 && $result_rt02->num_rows > 0) {
    while ($row = $result_rt02->fetch_assoc()) {
        if (!empty($row['foto'])) {
            $row['foto'] = $base_foto_path . $row['foto'];
        } else {
            $row['foto'] = $base_foto_path . "default.jpg";
        }
        $kandidat_rt02[] = $row;
    }
}

// Query untuk kandidat RT 03
$query_rt03 = "SELECT id_calon, nama, rt, rw, foto, visi, misi 
               FROM kandidat 
               WHERE id_periode = 1 AND rt = '03' AND rw = '01'
               ORDER BY id_calon";
$result_rt03 = $conn->query($query_rt03);
if ($result_rt03 && $result_rt03->num_rows > 0) {
    while ($row = $result_rt03->fetch_assoc()) {
        if (!empty($row['foto'])) {
            $row['foto'] = $base_foto_path . $row['foto'];
        } else {
            $row['foto'] = $base_foto_path . "default.jpg";
        }
        $kandidat_rt03[] = $row;
    }
}

// Query untuk kandidat RW 01 (rt NULL atau kosong)
$query_rw01 = "SELECT id_calon, nama, rt, rw, foto, visi, misi 
               FROM kandidat 
               WHERE id_periode = 1 AND rw = '01' AND (rt IS NULL OR rt = '' OR rt = '0')
               ORDER BY id_calon";
$result_rw01 = $conn->query($query_rw01);
if ($result_rw01 && $result_rw01->num_rows > 0) {
    while ($row = $result_rw01->fetch_assoc()) {
        if (!empty($row['foto'])) {
            $row['foto'] = $base_foto_path . $row['foto'];
        } else {
            $row['foto'] = $base_foto_path . "default.jpg";
        }
        $kandidat_rw01[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SipeL • Data Calon</title>
  <link rel="stylesheet" href="../css/daftar-kandidat.css">
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
        <li><a href="daftar-kandidat.php" class="active"><i class="fas fa-users"></i> Data Calon</a></li>
        <li><a href="pilih-kandidat.php"><i class="fas fa-vote-yea"></i> Pilih Kandidat</a></li>
        <li><a href="history.php"><i class="fas fa-history"></i> History Voting</a></li>
        <li><a href="rekap-sementara.php"><i class="fas fa-chart-bar"></i> Rekap Sementara</a></li>
        <li><a href="hasil-akhir.php"><i class="fas fa-trophy"></i> Hasil Akhir</a></li>
        <li><a href="ubah-password.php"><i class="fas fa-key"></i> Ubah Password</a></li>
      </ul>
    </aside>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <main class="main-content">
      <section class="page-header">
        <div class="header-content">
          <h1>Calon Kandidat</h1>
          <h3>Perumahan Griya Harmoni</h3>
          <p>Kenali calon RT/RW sebelum menggunakan hak pilih Anda</p>
        </div>
      </section>

      <section class="candidate-content">
        <div class="filter-section">
          <div class="filter-group">
            <label for="jabatan-filter">Pilih Jabatan:</label>
            <select id="jabatan-filter" class="filter-select">
              <option value="all">Semua Jabatan</option>
              <option value="rt001">RT 01</option>
              <option value="rt002">RT 02</option>
              <option value="rt003">RT 03</option>
              <option value="rw001">RW 01</option>
            </select>
          </div>
        </div>

        <div class="candidates-grid">
          <div class="jabatan-section" data-jabatan="rt001">
            <div class="jabatan-header">
              <h2>RT 01</h2>
              <span class="kandidat-count"><?php echo count($kandidat_rt01); ?> Kandidat</span>
            </div>

            <?php if (!empty($kandidat_rt01)): ?>
              <div class="candidates-list">
                <?php foreach ($kandidat_rt01 as $kandidat): ?>
                <div class="candidate-card">
                  <div class="candidate-photo">
                    <img src="<?php echo htmlspecialchars($kandidat['foto']); ?>" alt="<?php echo htmlspecialchars($kandidat['nama']); ?>"
                         onerror="this.src='../assets/kandidat/default.jpg'">
                  </div>
                  <div class="candidate-info">
                    <h3><?php echo htmlspecialchars($kandidat['nama']); ?></h3>
                    <div class="candidate-details">
                      <p><strong>RT:</strong> <?php echo htmlspecialchars($kandidat['rt']); ?> |
                         <strong>RW:</strong> <?php echo htmlspecialchars($kandidat['rw']); ?></p>

                      <button class="btn-detail" onclick="toggleDetails(event, <?php echo (int)$kandidat['id_calon']; ?>)">
                        <i class="fas fa-info-circle"></i> Lihat Detail
                      </button>
                    </div>

                    <div class="detail-content" id="detail-<?php echo (int)$kandidat['id_calon']; ?>" style="display: none;">
                      <div class="detail-section">
                        <h4><i class="fas fa-eye"></i> Visi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['visi'])); ?></p>
                      </div>
                      <div class="detail-section">
                        <h4><i class="fas fa-bullseye"></i> Misi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['misi'])); ?></p>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-candidates">
                <i class="fas fa-users"></i>
                <h3>Belum ada kandidat</h3>
                <p>Data calon untuk RT 01 akan ditampilkan di sini setelah pendaftaran dibuka</p>
              </div>
            <?php endif; ?>
          </div>

          <div class="jabatan-section" data-jabatan="rt002">
            <div class="jabatan-header">
              <h2>RT 02</h2>
              <span class="kandidat-count"><?php echo count($kandidat_rt02); ?> Kandidat</span>
            </div>

            <?php if (!empty($kandidat_rt02)): ?>
              <div class="candidates-list">
                <?php foreach ($kandidat_rt02 as $kandidat): ?>
                <div class="candidate-card">
                  <div class="candidate-photo">
                    <img src="<?php echo htmlspecialchars($kandidat['foto']); ?>" alt="<?php echo htmlspecialchars($kandidat['nama']); ?>"
                         onerror="this.src='../assets/kandidat/default.jpg'">
                  </div>
                  <div class="candidate-info">
                    <h3><?php echo htmlspecialchars($kandidat['nama']); ?></h3>
                    <div class="candidate-details">
                      <p><strong>RT:</strong> <?php echo htmlspecialchars($kandidat['rt']); ?> |
                         <strong>RW:</strong> <?php echo htmlspecialchars($kandidat['rw']); ?></p>

                      <button class="btn-detail" onclick="toggleDetails(event, <?php echo (int)$kandidat['id_calon']; ?>)">
                        <i class="fas fa-info-circle"></i> Lihat Detail
                      </button>
                    </div>

                    <div class="detail-content" id="detail-<?php echo (int)$kandidat['id_calon']; ?>" style="display: none;">
                      <div class="detail-section">
                        <h4><i class="fas fa-eye"></i> Visi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['visi'])); ?></p>
                      </div>
                      <div class="detail-section">
                        <h4><i class="fas fa-bullseye"></i> Misi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['misi'])); ?></p>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-candidates">
                <i class="fas fa-users"></i>
                <h3>Belum ada kandidat</h3>
                <p>Data calon untuk RT 02 akan ditampilkan di sini setelah pendaftaran dibuka</p>
              </div>
            <?php endif; ?>
          </div>

          <div class="jabatan-section" data-jabatan="rt003">
            <div class="jabatan-header">
              <h2>RT 03</h2>
              <span class="kandidat-count"><?php echo count($kandidat_rt03); ?> Kandidat</span>
            </div>

            <?php if (!empty($kandidat_rt03)): ?>
              <div class="candidates-list">
                <?php foreach ($kandidat_rt03 as $kandidat): ?>
                <div class="candidate-card">
                  <div class="candidate-photo">
                    <img src="<?php echo htmlspecialchars($kandidat['foto']); ?>" alt="<?php echo htmlspecialchars($kandidat['nama']); ?>"
                         onerror="this.src='../assets/kandidat/default.jpg'">
                  </div>
                  <div class="candidate-info">
                    <h3><?php echo htmlspecialchars($kandidat['nama']); ?></h3>
                    <div class="candidate-details">
                      <p><strong>RT:</strong> <?php echo htmlspecialchars($kandidat['rt']); ?> |
                         <strong>RW:</strong> <?php echo htmlspecialchars($kandidat['rw']); ?></p>

                      <button class="btn-detail" onclick="toggleDetails(event, <?php echo (int)$kandidat['id_calon']; ?>)">
                        <i class="fas fa-info-circle"></i> Lihat Detail
                      </button>
                    </div>

                    <div class="detail-content" id="detail-<?php echo (int)$kandidat['id_calon']; ?>" style="display: none;">
                      <div class="detail-section">
                        <h4><i class="fas fa-eye"></i> Visi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['visi'])); ?></p>
                      </div>
                      <div class="detail-section">
                        <h4><i class="fas fa-bullseye"></i> Misi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['misi'])); ?></p>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-candidates">
                <i class="fas fa-users"></i>
                <h3>Belum ada kandidat</h3>
                <p>Data calon untuk RT 03 akan ditampilkan di sini setelah pendaftaran dibuka</p>
              </div>
            <?php endif; ?>
          </div>

          <div class="jabatan-section" data-jabatan="rw001">
            <div class="jabatan-header">
              <h2>RW 01</h2>
              <span class="kandidat-count"><?php echo count($kandidat_rw01); ?> Kandidat</span>
            </div>

            <?php if (!empty($kandidat_rw01)): ?>
              <div class="candidates-list">
                <?php foreach ($kandidat_rw01 as $kandidat): ?>
                <div class="candidate-card">
                  <div class="candidate-photo">
                    <img src="<?php echo htmlspecialchars($kandidat['foto']); ?>" alt="<?php echo htmlspecialchars($kandidat['nama']); ?>"
                         onerror="this.src='../assets/kandidat/default.jpg'">
                  </div>
                  <div class="candidate-info">
                    <h3><?php echo htmlspecialchars($kandidat['nama']); ?></h3>
                    <div class="candidate-details">
                      <p><strong>RW:</strong> <?php echo htmlspecialchars($kandidat['rw']); ?></p>

                      <button class="btn-detail" onclick="toggleDetails(event, <?php echo (int)$kandidat['id_calon']; ?>)">
                        <i class="fas fa-info-circle"></i> Lihat Detail
                      </button>
                    </div>

                    <div class="detail-content" id="detail-<?php echo (int)$kandidat['id_calon']; ?>" style="display: none;">
                      <div class="detail-section">
                        <h4><i class="fas fa-eye"></i> Visi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['visi'])); ?></p>
                      </div>
                      <div class="detail-section">
                        <h4><i class="fas fa-bullseye"></i> Misi</h4>
                        <p><?php echo nl2br(htmlspecialchars($kandidat['misi'])); ?></p>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-candidates">
                <i class="fas fa-users"></i>
                <h3>Belum ada kandidat</h3>
                <p>Data calon untuk RW 001 akan ditampilkan di sini setelah pendaftaran dibuka</p>
              </div>
            <?php endif; ?>
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

                if (sidebarOverlay) sidebarOverlay.classList.toggle('active');
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
                    if (sidebarOverlay) sidebarOverlay.classList.remove('active');
                });
            });

            window.addEventListener('scroll', () => {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                    hamburger.classList.remove('active');
                    sidebar.classList.remove('active');
                    if (sidebarOverlay) sidebarOverlay.classList.remove('active');
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
    });

    function toggleDetails(e, id) {
        const detailElement = document.getElementById('detail-' + id);
        const button = e.target.closest('.btn-detail');

        if (!detailElement || !button) return;

        if (detailElement.style.display === 'none' || detailElement.style.display === '') {
            detailElement.style.display = 'block';
            button.innerHTML = '<i class="fas fa-times-circle"></i> Tutup Detail';
            button.classList.add('active');
        } else {
            detailElement.style.display = 'none';
            button.innerHTML = '<i class="fas fa-info-circle"></i> Lihat Detail';
            button.classList.remove('active');
        }
    }
  </script>
</body>
</html>
