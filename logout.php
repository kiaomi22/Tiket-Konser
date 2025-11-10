<?php
session_start(); // Ambil session yang ada

// Hancurkan semua data session
session_unset();
session_destroy();

// Kembalikan pengguna ke halaman utama
header("Location: index.php");
exit;
?>