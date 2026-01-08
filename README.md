# PBL TRPL103 – Sistem Pemilihan Elektronik RT/RW (SipeL)

Repository ini berisi source code Project Based Learning (PBL).

## Gambaran Umum

SipeL merupakan aplikasi berbasis web yang dikembangkan untuk membantu pelaksanaan
pemilihan ketua RT/RW secara elektronik. Sistem ini memfasilitasi proses pengelolaan data
pemilih, kandidat, proses pemungutan suara, serta rekapitulasi hasil secara otomatis.

## Ruang Lingkup Fitur

### Hak Akses Panitia
- Autentikasi pengguna (Panitia)
- Dashboard monitoring pemilihan
- Pengaturan jadwal pemilihan
- Pengelolaan data warga
- Pengelolaan data kandidat
- Rekap sementara hasil pemilihan
- Melihat hasil akhir pemilihan

### Hak Akses Warga
- Autentikasi pengguna (Warga)
- Dashboard monitoring pemilihan
- Melihat jadwal pemilihan
- Melihat data kandidat
- Melakukan pemungutan suara (voting)
- Melihat rekap sementara
- Melihat hasil akhir pemilihan

## Teknologi yang Digunakan
- PHP  
- MySQL  
- HTML, CSS, dan JavaScript  
- Composer (PHP Dependency Manager)

## Panduan Instalasi

1. Clone repository:
   ```bash
   git clone https://github.com/rizkipratama16/PBL-TRPL103-CM3-SipeL.git
2. Masuk ke direktori project:
   cd PBL-TRPL103-CM3-SipeL
3. Install dependency menggunakan Composer:
   composer install
4. Buat database baru pada MySQL (contoh: sipel) lalu import file:
   sipel_new.sql
5. Jalankan aplikasi melalui browser:
   http://localhost/SipeL

| Nama Lengkap           | NIM        |
| ---------------------- | ---------- |
| Rizki Septi Pratama    | 4342511068 |
| M Farid Dhiarrurrahman | 4342511062 |
| Nur Aini Siti Sholehal | 4342511071 |
| Remitha Dwi Putri S    | 4342511077 |
| Sherly Andhini         | 4342511081 |
