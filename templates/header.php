<?php
// Selalu mulai session di baris paling atas
session_start();

// Tentukan folder proyek Anda
// (Berdasarkan screenshot Anda, folder Anda adalah 'tiket_konser')
$project_folder = '/tiket_konser/';

// Cek status login
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
$user_role = $is_logged_in ? $_SESSION['user_role'] : '';
$user_nama = $is_logged_in ? $_SESSION['user_nama'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistem Tiket Konser'; ?></title>
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f9f9f9; }
        .navbar { background-color: #333; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar .logo { font-size: 1.5em; font-weight: bold; color: white; text-decoration: none; }
        .navbar .nav-links { list-style: none; margin: 0; padding: 0; display: flex; }
        .navbar .nav-links li { margin-left: 15px; }
        .navbar .nav-links a { color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px; }
        .navbar .nav-links a:hover, .navbar .nav-links a.active { background-color: #555; }
        .container { max-width: 1200px; margin: 20px auto; padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .welcome-msg { margin-right: 15px; }
    </style>
</head>
<body>

<nav class="navbar">
    <!-- LINK UTAMA -->
    <a href="<?php echo $project_folder; ?>index.php" class="logo">TIKETKONSER</a>
    
    <ul class="nav-links">
        <?php if ($is_logged_in): ?>
            <span class="welcome-msg">Halo, <?php echo htmlspecialchars($user_nama); ?>!</span>
            
            <!-- --- LOGIKA MENU --- -->
            <?php if ($user_role == 'admin'): ?>
                <!-- JIKA ADMIN: Tampilkan Dashboard Admin SAJA -->
                <li><a href="<?php echo $project_folder; ?>admin/index.php">Dashboard Admin</a></li>
            
            <?php else: ?>
                <!-- JIKA USER BIASA: Tampilkan Tiket Saya SAJA -->
                <li><a href="<?php echo $project_folder; ?>tiket_saya.php">Tiket Saya</a></li>
            <?php endif; ?>
            <!-- --- AKHIR LOGIKA MENU --- -->
            
            <li><a href="<?php echo $project_folder; ?>logout.php" style="background-color: #d9534f;">Logout</a></li>
            
        <?php else: ?>
            <!-- Menu Jika BELUM Login -->
            <li><a href="<?php echo $project_folder; ?>login.php">Login</a></li>
            <li><a href="<?php echo $project_folder; ?>register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<!-- Konten utama akan dimulai di sini -->
<div class="container">