<?php
// Set judul halaman
$page_title = "Tiket Saya";

// 1. Panggil Header dan Koneksi
require_once 'templates/header.php'; // Header sudah otomatis session_start()
require_once 'config/database.php';

// 2. Keamanan: Cek apakah user sudah login
if (!$is_logged_in) { // Variabel $is_logged_in didapat dari header.php
    $_SESSION['error_message'] = "Anda harus login untuk mengakses halaman ini.";
    header("Location: " . $project_folder . "login.php");
    exit;
}
$id_user = $_SESSION['user_id']; // Ambil ID user

// Inisialisasi variabel
$daftar_pesanan = [];
$error_db = '';
$pesan_sukses = '';
$pesan_error = '';

// Ambil pesan dari session (jika ada, misal dari checkout atau upload)
if (isset($_SESSION['message'])) {
    $pesan_sukses = $_SESSION['message'];
    unset($_SESSION['message']);
}
if (isset($_SESSION['error_message'])) {
    $pesan_error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}


// 3. Ambil Data Pemesanan User dari Database
try {
    $sql = "SELECT
                p.id_pemesanan,
                p.tanggal_pemesanan,
                p.total_harga,
                p.status_pembayaran,
                p.bukti_pembayaran,
                k.nama_konser,
                GROUP_CONCAT(d.jumlah_tiket, 'x ', kt.nama_kategori SEPARATOR '<br>') as rincian_tiket
            FROM
                tbl_pemesanan p
            JOIN
                tbl_detail_pemesanan d ON p.id_pemesanan = d.id_pemesanan
            JOIN
                tbl_kategori_tiket kt ON d.id_kategori_tiket = kt.id_kategori
            JOIN
                tbl_konser k ON kt.id_konser = k.id_konser
            WHERE
                p.id_user = ?
            GROUP BY
                p.id_pemesanan
            ORDER BY
                p.tanggal_pemesanan DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_user]);
    $daftar_pesanan = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_db = "Gagal mengambil data pesanan: " . $e->getMessage();
}
?>

<!-- CSS untuk Halaman Tiket Saya -->
<style>
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    .pesanan-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .pesanan-header {
        background-color: #f9f9f9;
        padding: 15px 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .pesanan-header h3 { margin: 0; }
    .status {
        padding: 5px 10px;
        border-radius: 15px;
        font-weight: bold;
        font-size: 0.9em;
    }
    .status-menunggu { background-color: #fff3cd; color: #856404; }
    .status-lunas { background-color: #d4edda; color: #155724; }
    .status-batal { background-color: #f8d7da; color: #721c24; }
    
    .pesanan-body {
        display: flex;
        padding: 20px;
    }
    .pesanan-detail {
        flex-basis: 60%; /* Ambil 60% lebar */
    }
    .pesanan-aksi {
        flex-basis: 40%; /* Ambil 40% lebar */
        padding-left: 20px;
        border-left: 1px solid #eee;
    }
    .pesanan-detail p { margin: 0 0 10px 0; }
    
    .upload-form { margin-top: 10px; }
    .upload-form input[type="file"] {
        display: block;
        margin-bottom: 10px;
    }
    .btn-upload {
        background-color: #007bff;
        color: white;
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .btn-upload:hover { background-color: #0056b3; }
    .bukti-terupload { font-size: 0.9em; color: #28a745; }
    .menunggu-validasi { font-size: 0.9em; color: #007bff; }
    
    .no-data { color: #777; font-style: italic; }
</style>

<h1>Riwayat Pemesanan Anda</h1>

<!-- Tampilkan pesan sukses/error dari session -->
<?php if ($pesan_sukses): ?>
    <div class="alert alert-success"><?php echo $pesan_sukses; ?></div>
<?php endif; ?>
<?php if ($pesan_error): ?>
    <div class="alert alert-error"><?php echo $pesan_error; ?></div>
<?php endif; ?>
<?php if ($error_db): ?>
    <div class="alert alert-error"><?php echo $error_db; ?></div>
<?php endif; ?>


<!-- 4. Looping Data Pesanan -->
<?php if (empty($daftar_pesanan)): ?>
    <p class="no-data">Anda belum memiliki riwayat pemesanan.</p>

<?php else: ?>
    <?php foreach ($daftar_pesanan as $pesanan): ?>
        <div class="pesanan-card">
            <div class="pesanan-header">
                <div>
                    <h3>ID Pesanan: #<?php echo $pesanan['id_pemesanan']; ?></h3>
                    <span>Tanggal: <?php echo date('d M Y, H:i', strtotime($pesanan['tanggal_pemesanan'])); ?></span>
                </div>
                <!-- Tampilkan status dengan style berbeda -->
                <?php
                    $status_class = '';
                    if ($pesanan['status_pembayaran'] == 'Menunggu') $status_class = 'status-menunggu';
                    if ($pesanan['status_pembayaran'] == 'Lunas') $status_class = 'status-lunas';
                    if ($pesanan['status_pembayaran'] == 'Batal') $status_class = 'status-batal';
                ?>
                <span class="status <?php echo $status_class; ?>">
                    <?php echo $pesanan['status_pembayaran']; ?>
                </span>
            </div>
            
            <div class="pesanan-body">
                <div class="pesanan-detail">
                    <p><strong>Konser:</strong> <?php echo htmlspecialchars($pesanan['nama_konser']); ?></p>
                    <p><strong>Rincian Tiket:</strong><br><?php echo $pesanan['rincian_tiket']; ?></p>
                    <p><strong>Total Pembayaran:</strong> 
                        <strong style="color: #d9534f;">Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></strong>
                    </p>
                </div>
                
                <div class="pesanan-aksi">
                    
                    <!-- --- LOGIKA KONDISIONAL DIPERBAIKI --- -->
                    
                    <?php if ($pesanan['status_pembayaran'] == 'Lunas'): ?>
                        <h4>Pembayaran Lunas</h4>
                        <p style="font-size: 0.9em; color: #155724;">Tiket Anda sudah dikonfirmasi.</p>
                        
                    <?php elseif ($pesanan['status_pembayaran'] == 'Batal'): ?>
                        <h4>Pesanan Dibatalkan</h4>
                        <p style="font-size: 0.9em;">Pesanan ini telah dibatalkan.</p>
                        
                    <?php elseif ($pesanan['status_pembayaran'] == 'Menunggu' && !empty($pesanan['bukti_pembayaran'])): ?>
                         <h4>Menunggu Validasi Admin</h4>
                         <p class.menunggu-validasi>Anda sudah mengupload bukti bayar. Admin akan segera memverifikasinya.</p>
                         <p class="bukti-terupload">File: <?php echo htmlspecialchars($pesanan['bukti_pembayaran']); ?></p>
                         
                    <?php elseif ($pesanan['status_pembayaran'] == 'Menunggu'): ?>
                        <h4>Konfirmasi Pembayaran</h4>
                        <p style="font-size: 0.9em;">Silakan lakukan pembayaran dan upload bukti transfer Anda di sini.</p>
                        
                        <!-- Form Upload Bukti Pembayaran -->
                        <form action="upload_processor.php" method="POST" enctype="multipart/form-data" class="upload-form">
                            <input type="hidden" name="id_pemesanan" value="<?php echo $pesanan['id_pemesanan']; ?>">
                            <input type="file" name="bukti_bayar" accept="image/jpeg, image/png, image/gif" required>
                            <button type="submit" class="btn-upload">Upload Bukti</button>
                        </form>
                        
                    <?php endif; ?>
                    
                    <!-- --- BATAS PERBAIKAN --- -->
                    
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>


<?php
// 6. Panggil Footer
require_once 'templates/footer.php';
?>