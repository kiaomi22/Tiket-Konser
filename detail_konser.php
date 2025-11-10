<?php
// Set judul halaman
$page_title = "Detail Konser";

// 1. Panggil Header dan Koneksi
require_once 'templates/header.php'; // Header sudah otomatis session_start()
require_once 'config/database.php';

// 2. Validasi ID Konser dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<h1>Error: Konser tidak ditemukan.</h1>";
    require_once 'templates/footer.php';
    exit;
}
$id_konser = (int)$_GET['id'];

// Inisialisasi variabel
$konser = null;
$daftar_tiket = [];
$error_db = '';

// 3. Ambil Data Detail Konser
try {
    $stmt_konser = $pdo->prepare("SELECT * FROM tbl_konser WHERE id_konser = ?");
    $stmt_konser->execute([$id_konser]);
    $konser = $stmt_konser->fetch();

    if ($konser) {
        // Jika konser ditemukan, ambil data tiketnya
        $stmt_tiket = $pdo->prepare("SELECT * FROM tbl_kategori_tiket WHERE id_konser = ? AND stok > 0 ORDER BY harga ASC");
        $stmt_tiket->execute([$id_konser]);
        $daftar_tiket = $stmt_tiket->fetchAll();
    } else {
        $error_db = "Konser yang Anda cari tidak ditemukan.";
    }

} catch (PDOException $e) {
    $error_db = "Gagal mengambil data: " . $e->getMessage();
}

// 4. Cek status login (diambil dari header.php)
// $is_logged_in sudah di-set di header.php
?>

<style>
    .konser-header {
        border-bottom: 2px solid #eee;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    .konser-header h1 { margin-bottom: 5px; }
    .konser-header p { font-size: 1.1em; color: #555; margin: 0; }
    .konser-deskripsi { margin-top: 20px; line-height: 1.6; }
    
    .tiket-form { margin-top: 30px; }
    .tiket-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .tiket-table th, .tiket-table td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }
    .tiket-table th { background-color: #f9f9f9; }
    .tiket-table input[type="number"] {
        width: 80px;
        padding: 5px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    .total-container {
        text-align: right;
        margin-top: 20px;
    }
    .btn-pesan {
        background-color: #28a745;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 5px;
        font-size: 1.1em;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-pesan:hover { background-color: #218838; }
    
    .login-prompt {
        background-color: #fff3cd;
        border: 1px solid #ffeeba;
        color: #856404;
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
    }
    .login-prompt a { color: #856404; font-weight: bold; }
    .error-db { color: red; background: #ffebee; border: 1px solid red; padding: 15px; border-radius: 4px; }
    .no-data { color: #777; font-style: italic; }
</style>


<?php if ($error_db): ?>
    <div class="error-db"><?php echo $error_db; ?></div>

<?php elseif ($konser): // Jika konser ditemukan ?>

    <div class="konser-header">
        <h1><?php echo htmlspecialchars($konser['nama_konser']); ?></h1>
        <p>
            <strong>Lokasi:</strong> <?php echo htmlspecialchars($konser['lokasi']); ?> <br>
            <strong>Waktu:</strong> <?php echo date('d F Y, H:i', strtotime($konser['tanggal_waktu'])); ?> WIB
        </p>
    </div>
    
    <div class="konser-deskripsi">
        <h3>Deskripsi Konser</h3>
        <p><?php echo nl2br(htmlspecialchars($konser['deskripsi'])); // nl2br agar enter terbaca ?></p>
    </div>

    <hr style="margin: 30px 0;">

    <div class="tiket-form">
        <h2>Pilih Tiket Anda</h2>
        
        <?php if (empty($daftar_tiket)): ?>
            <p class="no-data">Maaf, tiket untuk konser ini sudah habis atau belum tersedia.</p>
        
        <?php else: ?>
            <form action="checkout.php" method="POST">
                <input type="hidden" name="id_konser" value="<?php echo $id_konser; ?>">
                
                <table class="tiket-table">
                    <thead>
                        <tr>
                            <th>Kategori Tiket</th>
                            <th>Harga</th>
                            <th>Sisa Stok</th>
                            <th>Jumlah Beli</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_tiket as $tiket): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($tiket['nama_kategori']); ?></strong></td>
                                <td>Rp <?php echo number_format($tiket['harga'], 0, ',', '.'); ?></td>
                                <td><?php echo $tiket['stok']; ?></td>
                                <td>
                                    <input type="number" 
                                           name="jumlah[<?php echo $tiket['id_kategori']; ?>]" 
                                           value="0" 
                                           min="0" 
                                           max="<?php echo $tiket['stok']; // Maksimal adalah sisa stok ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="total-container">
                    <?php if ($is_logged_in): // Cek dari header.php ?>
                        <button type="submit" class="btn-pesan">Pesan Sekarang &raquo;</button>
                    <?php else: ?>
                        <div class="login-prompt">
                            Anda harus <a href="login.php?redirect=detail_konser.php?id=<?php echo $id_konser; ?>">Login</a> terlebih dahulu untuk memesan tiket.
                        </div>
                    <?php endif; ?>
                </div>
                
            </form>
        <?php endif; ?>
        
    </div>

<?php endif; // Penutup if $konser ?>


<?php
// 8. Panggil Footer
require_once 'templates/footer.php';
?>