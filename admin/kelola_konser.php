<?php
// FILE INI HARUS DIPANGGIL SETELAH session_start() di header.php

// 1. CEK KEAMANAN: Apakah sudah login?
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // Jika belum login, tendang ke halaman login
    header("Location: ../login.php"); // ../ artinya keluar satu folder
    exit;
}

// 2. CEK KEAMANAN: Apakah rolenya 'admin'?
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Jika bukan admin (tapi user biasa), tendang ke halaman utama
    $_SESSION['error_message'] = "Anda tidak memiliki hak akses ke halaman Admin.";
    header("Location: ../index.php"); // ../ artinya keluar satu folder
    exit;
}

// --- Jika lolos 2 cek di atas, berarti dia adalah Admin ---
?>

<style>
    .admin-wrapper { display: flex; }
    .sidebar { 
        width: 250px; 
        background: #4a4a4a; 
        color: white; 
        min-height: 80vh; /* Setidaknya setinggi layar */
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
        ```

---

### Langkah 12: Membuat Dashboard Admin (`admin/index.php`)

Ini adalah halaman *landing* untuk admin. Halaman ini akan memanggil `header.php`, `sidebar_admin.php`, dan `footer.php`.

Buat file baru di `admin/index.php`:

**`admin/index.php`**
```php
<?php
// Set judul halaman dan tandai halaman aktif
$page_title = "Admin Dashboard";
$current_page = "dashboard"; // Untuk menandai sidebar aktif

// 1. Panggil Header
// Kita panggil header dari folder utama (keluar satu level '..')
require_once '../templates/header.php';

// 2. Panggil Sidebar Admin
// (Sidebar sudah otomatis mengecek status login dan role admin)
require_once '../templates/sidebar_admin.php';

// 3. Panggil Koneksi Database
require_once '../config/database.php';

// --- LOGIKA DASHBOARD (Contoh Ambil Statistik) ---
try {
    // Hitung jumlah user
    $stmt_users = $pdo->query("SELECT COUNT(*) FROM tbl_users WHERE role = 'user'");
    $total_users = $stmt_users->fetchColumn();

    // Hitung jumlah konser
    $stmt_konser = $pdo->query("SELECT COUNT(*) FROM tbl_konser");
    $total_konser = $stmt_konser->fetchColumn();

    // Hitung pesanan menunggu validasi
    $stmt_pesanan = $pdo->query("SELECT COUNT(*) FROM tbl_pemesanan WHERE status_pembayaran = 'Menunggu'");
    $total_pesanan_menunggu = $stmt_pesanan->fetchColumn();

} catch (PDOException $e) {
    // Jika error, tampilkan pesan
    echo "Error mengambil data: " . $e->getMessage();
    $total_users = $total_konser = $total_pesanan_menunggu = 'N/A';
}
?>

<style>
    .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .stat-card {
        background: #f4f4f4;
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .stat-card h3 { font-size: 3em; margin: 0 0 10px 0; }
    .stat-card p { font-size: 1.1em; color: #555; margin: 0; }
</style>

<h1>Selamat Datang, <?php echo htmlspecialchars($_SESSION['user_nama']); ?>!</h1>
<p>Ini adalah halaman Dashboard Admin. Gunakan menu di samping untuk mengelola sistem.</p>

<hr style="margin: 20px 0;">

<h2>Ringkasan Sistem</h2>
<div class="stat-grid">
    <div class="stat-card">
        <h3><?php echo $total_konser; ?></h3>
        <p>Total Konser</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $total_users; ?></h3>
        <p>Total Pengguna</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $total_pesanan_menunggu; ?></h3>
        <p>Pesanan Menunggu Validasi</p>
    </div>
</div>


<?php
// 4. Tutup tag <main> dari sidebar
echo '</main>'; 

// 5. Tutup tag <div> dari sidebar
echo '</div>'; 

// 6. Panggil Footer
require_once '../templates/footer.php';
?>