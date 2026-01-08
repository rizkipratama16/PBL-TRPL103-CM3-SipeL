<?php
session_start();
require_once __DIR__ . '/../db_connect.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: kandidat.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM kandidat WHERE id_calon = ?");
$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    die("Gagal menghapus kandidat: " . $stmt->error);
}

header("Location: kandidat.php");
exit;
