<?php

// --- Konfigurasi Database ---
// Sesuaikan nilai ini dengan pengaturan MySQL Anda
$db_host = 'localhost';     // Biasanya 'localhost'
$db_user = 'root';          // User default MySQL
$db_pass = '';              // Password default MySQL (kosongkan jika tidak ada)
$db_name = 'tiketkonser_db'; // Nama database yang tadi kita buat
// -----------------------------


// --- Membuat Koneksi ---
try {
    // Membuat objek PDO (PHP Data Objects) untuk koneksi
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Menampilkan error SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Mengembalikan data sbg array asosiatif
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $db_user, $db_pass, $options);

} catch (PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan error dan hentikan script
    die("Koneksi ke database gagal: " . $e->getMessage());
}

// Catatan: 
// Kita tidak perlu menutup koneksi ($pdo = null;) di sini.
// Koneksi akan otomatis ditutup saat script PHP selesai dieksekusi.
// File ini akan kita 'include' atau 'require' di file PHP lainnya.

?>