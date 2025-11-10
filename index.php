<?php
// Set judul halaman
$page_title = "Selamat Datang di Sistem Tiket Konser";

// 1. Panggil Header
require_once 'templates/header.php';

// 2. Panggil Koneksi Database
require_once 'config/database.php';

// Inisialisasi variabel untuk data konser
$konser_list = [];
$error_db = '';

// 3. Ambil data konser dari database
try {
    // Ambil konser yang akan datang saja
    $sql = "SELECT * FROM tbl_konser 
            WHERE tanggal_waktu >= NOW() 
            ORDER BY tanggal_waktu ASC";
            
    $stmt = $pdo->query($sql);
    
    $konser_list = $stmt->fetchAll();

} catch (PDOException $e) {
    $error_db = "Gagal mengambil data konser: " . $e->getMessage();
}

?>

<style>
    .page-title { border-bottom: 2px solid #f4f4f4; padding-bottom: 10px; }
    .konser-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
    .konser-card { border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; }
    .konser-card img { width: 100%; height: 180px; object-fit: cover; background-color: #eee; }
    .konser-card-content { padding: 15px; }
    .konser-card h3 { margin-top: 0; }
    .konser-card p { margin-bottom: 10px; color: #555; }
    .konser-card .btn-detail {
        display: inline-block;
        background-color: #007bff;
        color: white;
        padding: 8px 12px;
        text-decoration: none;
        border-radius: 4px;
        text-align: center;
    }
    .konser-card .btn-detail:hover { background-color: #0056b3; }
    .error-db { color: red; background: #ffebee; border: 1px solid red; padding: 15px; border-radius: 4px; }
    .no-data { color: #777; font-style: italic; }
</style>


<h1 class="page-title">Konser yang Akan Datang</h1>

<?php if ($error_db): ?>
    <div class="error-db"><?php echo $error_db; ?></div>

<?php elseif (empty($konser_list)): ?>
    <p class="no-data">Belum ada konser yang tersedia saat ini.</p>

<?php else: ?>
    <div class="konser-grid">
        <?php foreach ($konser_list as $konser): ?>
            <div class="konser-card">
                <img src="https://via.placeholder.com/300x180.png?text=<?php echo htmlspecialchars($konser['nama_konser']); ?>" alt="Gambar Konser">
                
                <div class="konser-card-content">
                    <h3><?php echo htmlspecialchars($konser['nama_konser']); ?></h3>
                    
                    <p>
                        <strong>Lokasi:</strong> <?php echo htmlspecialchars($konser['lokasi']); ?>
                    </p>
                    <p>
                        <strong>Waktu:</strong> <?php echo date('d F Y, H:i', strtotime($konser['tanggal_waktu'])); ?> WIB
                    </p>
                    
                    <a href="detail_konser.php?id=<?php echo $konser['id_konser']; ?>" class="btn-detail">
                        Lihat Tiket
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<?php
// 5. Panggil Footer
require_once 'templates/footer.php';
?>