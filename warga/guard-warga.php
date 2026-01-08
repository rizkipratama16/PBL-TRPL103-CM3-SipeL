<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db_connect.php'; // pastiin path ini sesuai lokasi guard-warga.php

// ===== WAJIB LOGIN WARGA =====
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'warga' || empty($_SESSION['nik'])) {
    header("Location: login.php"); // ganti ke ../login.php kalau login ada di root
    exit;
}

$nik  = (string)$_SESSION['nik'];
$nama = (string)($_SESSION['nama'] ?? 'Warga');

// ===== CEK STATUS password_changed DI DB =====
$st = $conn->prepare("SELECT password_changed FROM warga WHERE nik = ? LIMIT 1");
$st->bind_param("s", $nik);
$st->execute();
$row = $st->get_result()->fetch_assoc();
$st->close();

if (!$row) {
    // akun hilang dari DB -> putus sesi
    session_destroy();
    header("Location: login.php"); // ganti ke ../login.php kalau login ada di root
    exit;
}

$passwordChanged = (int)$row['password_changed'];

// ===== PAKSA UBAH PASSWORD SAAT FIRST LOGIN SAJA =====
$currentPage = basename($_SERVER['PHP_SELF']); // contoh: dashboard.php

if ($passwordChanged === 0 && $currentPage !== 'ubah-password.php') {
    header("Location: ubah-password.php");
    exit;
}

// selesai: halaman lanjut
