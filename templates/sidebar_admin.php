<?php
// FILE INI HARUS DIPANGGIL SETELAH session_start() di header.php

// 1. CEK KEAMANAN: Apakah sudah login?
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../login.php"); 
    exit;
}

// 2. CEK KEAMANAN: Apakah rolenya 'admin'?
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses ke halaman Admin.";
    header("Location: ../index.php"); 
    exit;
}
?>

<style>
    .admin-wrapper { display: flex; }
    .sidebar { 
        width: 250px; 
        background: #4a4a4a; 
        color: white; 
        min-height: 80vh; 
        padding: 15px;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }
    .sidebar-menu { list-style: none; padding: 0; margin: 0; }
    .sidebar-menu li a {
        display: block;
        padding: 12px 15px;
        text-decoration: none;
        color: #f1f1f1;
        border-radius: 5px;
        margin-bottom: 5px;
    }
    .sidebar-menu li a:hover, .sidebar-menu li a.active {
        background-color: #575757;
    }
    .admin-content {
        flex-grow: 1; /* Mengisi sisa ruang */
        padding: 20px;
    }
</style>

<div class="admin-wrapper">
    <aside class="sidebar">
        <h3>Menu Admin</h3>
        <ul class="sidebar-menu">
            <li>
                <a href="index.php" 
                   class="<?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                   Dashboard
                </a>
            </li>
            <li>
                <a href="kelola_konser.php" 
                   class="<?php echo ($current_page == 'konser') ? 'active' : ''; ?>">
                   Kelola Konser
                </a>
            </li>
            <li>
                <a href="validasi_bayar.php"
                   class="<?php echo ($current_page == 'validasi') ? 'active' : ''; ?>">
                   Validasi Pembayaran
                </a>
            </li>
            <li>
                <a href="laporan.php"
                   class="<?php echo ($current_page == 'laporan') ? 'active' : ''; ?>">
                   Laporan Penjualan
                </a>
            </li>
        </ul>
    </aside>

    <main class="admin-content">