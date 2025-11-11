<?php
// Selalu mulai session di baris paling atas
session_start();

// Hancurkan semua data session
session_unset();
session_destroy();

// Kembalikan pengguna ke halaman login (atau index)
header("Location: login.php");
exit;
?>