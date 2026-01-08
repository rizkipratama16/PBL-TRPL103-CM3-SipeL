<?php
date_default_timezone_set('Asia/Jakarta'); // WIB

$host = "localhost";
$user = "root";
$pass = "";
$db   = "data_sipel";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
