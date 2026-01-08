<?php
session_start();

if (isset($_SESSION['role'])) 
{
    if ($_SESSION['role'] === 'warga') {
        header('Location: warga/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'panitia') {
        header('Location: panitia/dashboard.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SipeL - Sistem Pemilihan Elektronik RT/RW</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header dan Navigasi -->
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="index.php">
                        <img src="assets/logo-sipelV3.png" alt="Logo SipeL">
                        <span>SipeL</span>
                    </a>
                </div>
                
                <div class="nav-menu" id="nav-menu">
                    <ul class="nav-list">
                        <li class="nav-item nav-buttons">
                            <a href="warga/login.php" class="btn btn-outline">Login Warga</a>
                            <a href="panitia/login.php" class="btn btn-primary">Login Panitia</a>
                        </li>
                    </ul>
                </div>
                
                <div class="hamburger" id="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero" style="background-image: linear-gradient(180deg, rgba(0, 117, 201, 0.3), rgba(0,117,201,0.3)), url('https://www.99.co/id/img-regional/800/800/fit/true/production/image/user/1ba2454f-8241-42d7-8750-56da6941d091/2025-01-07-06-54-53-0e31fd4b-35b7-415d-9e3c-88627c637fcd.jpg');">
        <div class="overlay-box">
            <h1>Selamat Datang</h1>
            <h2>SipeL – Sistem Aplikasi Pemilihan RT/RW</h2>
            <p>Mudah, cepat, transparan, dan aman.</p>
        </div>
    </section>

    <!-- Info Section -->
    <section class="info-section">
        <div class="container">
            <div class="description">
                <p>
                    SipeL adalah aplikasi e‑voting yang mempermudah proses pemilihan ketua RT/RW secara online. Dengan SipeL, warga dapat memilih dari rumah dengan sistem yang terjamin dan hasil bisa dipantau secara real‑time, transparan, dan aman.
                </p>
            </div>
        </div>
    </section>

    <!-- Fitur Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Keunggulan SipeL</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3>Aman</h3>
                    <p>Sistem keamanan terjamin dengan enkripsi data dan verifikasi identitas.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3>Cepat</h3>
                    <p>Proses pemilihan yang efisien dengan hasil yang dapat dilihat secara real-time.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <h3>Ramah Pengguna</h3>
                    <p>Antarmuka yang intuitif dan mudah digunakan oleh semua kalangan.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3>Transparan</h3>
                    <p>Hasil pemilihan dapat dipantau secara langsung dan terbuka untuk semua warga.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Siap Memulai Pemilihan dengan SipeL?</h2>
                <p>Mulai proses pemilihan ketua RT/RW secara online, teratur, dan transparan bersama SipeL.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <p>© 2025 SipeL • Sistem Pemilihan Elektronik RT/RW</p>
            </div>
        </div>
    </footer>


    <script>
    // Hamburger Menu Toggle untuk navigasi
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.getElementById('hamburger');
            const navMenu = document.getElementById('nav-menu');
        
            if (hamburger && navMenu) {
                hamburger.addEventListener('click', () => {
                    hamburger.classList.toggle('active');
                    navMenu.classList.toggle('active');
                });
        
                // Close menu when clicking on a nav link
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', () => {
                        hamburger.classList.remove('active');
                        navMenu.classList.remove('active');
                    });
                });
        
                // Close menu when scrolling on mobile
                window.addEventListener('scroll', () => {
                    if (window.innerWidth <= 992 && navMenu.classList.contains('active')) {
                        hamburger.classList.remove('active');
                        navMenu.classList.remove('active');
                    }
                });
            }
        });
  </script>
</body>
</html>