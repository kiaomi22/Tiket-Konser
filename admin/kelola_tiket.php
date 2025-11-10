<?php
// Set judul halaman dan tandai halaman aktif
$page_title = "Kelola Tiket Konser";
$current_page = "konser"; // Tetap di menu 'Kelola Konser'

// 1. Panggil Header, Sidebar, dan Koneksi
require_once '../templates/header.php';
require_once '../templates/sidebar_admin.php';
require_once '../config/database.php';

// --- BAGIAN LOGIKA ---

// 2. AMBIL ID KONSER DARI URL (WAJIB ADA)
if (!isset($_GET['id_konser']) || empty($_GET['id_konser'])) {
    echo "<h1>Error: ID Konser tidak ditemukan.</h1>";
    require_once '../templates/footer.php';
    exit;
}
$id_konser_terpilih = (int)$_GET['id_konser'];


// 3. Ambil data konser untuk judul
try {
    $stmt_konser = $pdo->prepare("SELECT nama_konser FROM tbl_konser WHERE id_konser = ?");
    $stmt_konser->execute([$id_konser_terpilih]);
    $konser = $stmt_konser->fetch();
    
    if (!$konser) {
        echo "<h1>Error: Data Konser tidak valid.</h1>";
        require_once '../templates/footer.php';
        exit;
    }
    $nama_konser_terpilih = $konser['nama_konser'];
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}


// Inisialisasi variabel
$pesan = '';
$aksi = $_GET['aksi'] ?? ''; // Ambil aksi dari URL (jika ada)
$id_kategori = $_GET['id'] ?? 0; // Ambil id kategori tiket (jika ada)

// Inisialisasi data untuk form (default kosong)
$data_form = [
    'id_kategori' => 0,
    'nama_kategori' => '',
    'harga' => '',
    'stok' => ''
];
$label_form = 'Tambah Tiket Baru';
$aksi_form = 'tambah';


// 4. PROSES POST (TAMBAH / UPDATE TIKET)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $id_kategori_post = $_POST['id_kategori'];
    $nama_kategori = trim($_POST['nama_kategori']);
    $harga = trim($_POST['harga']);
    $stok = trim($_POST['stok']);
    $aksi_post = $_POST['aksi'];
    // id_konser juga diambil dari hidden input
    $id_konser_post = $_POST['id_konser']; 

    try {
        if ($aksi_post == 'tambah') {
            // --- LOGIKA TAMBAH ---
            $sql = "INSERT INTO tbl_kategori_tiket (id_konser, nama_kategori, harga, stok) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_konser_post, $nama_kategori, $harga, $stok]);
            $pesan = '<div class="alert success">Kategori tiket baru berhasil ditambahkan.</div>';

        } elseif ($aksi_post == 'edit') {
            // --- LOGIKA UPDATE ---
            $sql = "UPDATE tbl_kategori_tiket 
                    SET nama_kategori = ?, harga = ?, stok = ?
                    WHERE id_kategori = ? AND id_konser = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nama_kategori, $harga, $stok, $id_kategori_post, $id_konser_post]);
            $pesan = '<div class="alert success">Data tiket berhasil diperbarui.</div>';
        }
        
        // Redirect untuk membersihkan form setelah submit
        header("Location: kelola_tiket.php?id_konser=" . $id_konser_post);
        exit;

    } catch (PDOException $e) {
        $pesan = '<div class="alert error">Gagal memproses data: ' . $e->getMessage() . '</div>';
    }
}

// 5. PROSES GET (HAPUS)
if ($aksi == 'hapus' && $id_kategori > 0) {
    try {
        $sql = "DELETE FROM tbl_kategori_tiket WHERE id_kategori = ? AND id_konser = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_kategori, $id_konser_terpilih]);
        $pesan = '<div class="alert success">Kategori tiket berhasil dihapus.</div>';
        
        // Redirect untuk refresh data
        header("Location: kelola_tiket.php?id_konser=" . $id_konser_terpilih);
        exit;
    } catch (PDOException $e) {
        $pesan = '<div class="alert error">Gagal menghapus data: ' . $e->getMessage() . '</div>';
    }
}

// 6. PROSES GET (UNTUK MENGISI FORM EDIT)
if ($aksi == 'edit' && $id_kategori > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_kategori_tiket WHERE id_kategori = ? AND id_konser = ?");
    $stmt->execute([$id_kategori, $id_konser_terpilih]);
    $data_edit = $stmt->fetch();
    
    if ($data_edit) {
        $data_form = $data_edit;
        $label_form = 'Edit Kategori Tiket';
        $aksi_form = 'edit';
    }
}

// 7. AMBIL SEMUA KATEGORI TIKET (UNTUK TABEL)
try {
    $stmt_list = $pdo->prepare("SELECT * FROM tbl_kategori_tiket WHERE id_konser = ? ORDER BY harga ASC");
    $stmt_list->execute([$id_konser_terpilih]);
    $daftar_tiket = $stmt_list->fetchAll();
} catch (PDOException $e) {
    $daftar_tiket = [];
    $pesan .= '<div class="alert error">Gagal mengambil daftar tiket: ' . $e->getMessage() . '</div>';
}

?>

<style>
    .form-konser { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-primary { background-color: #007bff; color: white; }
    .btn-secondary { background-color: #6c757d; color: white; }
    .btn-edit { background-color: #ffc107; color: #333; font-size: 0.9em; padding: 5px 8px; }
    .btn-hapus { background-color: #dc3545; color: white; font-size: 0.9em; padding: 5px 8px; }
    .alert { padding: 15px; margin-bottom: 15px; border-radius: 4px; }
    .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th, table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    table th { background-color: #f2f2f2; }
    table td .aksi-links a { margin-right: 5px; }
    
    .header-info { padding: 10px; background-color: #e9ecef; border-radius: 5px; margin-bottom: 20px; }
    .header-info strong { font-size: 1.2em; }
</style>

<h1>Manajemen Tiket</h1>
<div class="header-info">
    Konser: <strong><?php echo htmlspecialchars($nama_konser_terpilih); ?></strong><br>
    <a href="kelola_konser.php" style="font-size: 0.9em;">&laquo; Kembali ke Daftar Konser</a>
</div>


<?php echo $pesan; ?>

<div class="form-konser">
    <h2><?php echo $label_form; ?></h2>
    
    <form action="kelola_tiket.php?id_konser=<?php echo $id_konser_terpilih; ?>" method="POST">
        <input type="hidden" name="aksi" value="<?php echo $aksi_form; ?>">
        <input type="hidden" name="id_kategori" value="<?php echo htmlspecialchars($data_form['id_kategori']); ?>">
        <input type="hidden" name="id_konser" value="<?php echo $id_konser_terpilih; ?>">
        
        <div class="form-group">
            <label for="nama_kategori">Nama Kategori (Contoh: VIP, Reguler, Festival)</label>
            <input type="text" id="nama_kategori" name="nama_kategori" 
                   value="<?php echo htmlspecialchars($data_form['nama_kategori']); ?>" required>
        </div>
        <div class="form-group">
            <label for="harga">Harga (Rp)</label>
            <input type="number" id="harga" name="harga" min="0" 
                   value="<?php echo htmlspecialchars($data_form['harga']); ?>" required>
        </div>
        <div class="form-group">
            <label for="stok">Stok Tiket</label>
            <input type="number" id="stok" name="stok" min="0" 
                   value="<?php echo htmlspecialchars($data_form['stok']); ?>" required>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <?php echo ($aksi_form == 'edit') ? 'Update Tiket' : 'Tambah Tiket'; ?>
        </button>
        
        <?php if ($aksi_form == 'edit'): ?>
            <a href="kelola_tiket.php?id_konser=<?php echo $id_konser_terpilih; ?>" class="btn btn-secondary">Batal Edit</a>
        <?php endif; ?>
    </form>
</div>


<hr>
<h2>Daftar Kategori Tiket</h2>
<table>
    <thead>
        <tr>
            <th>Nama Kategori</th>
            <th>Harga</th>
            <th>Stok Tersedia</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($daftar_tiket)): ?>
            <tr>
                <td colspan="4" style="text-align: center;">Belum ada kategori tiket untuk konser ini.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($daftar_tiket as $tiket): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tiket['nama_kategori']); ?></td>
                    <td>Rp <?php echo number_format($tiket['harga'], 0, ',', '.'); ?></td>
                    <td><?php echo number_format($tiket['stok']); ?></td>
                    <td class="aksi-links">
                        <a href="kelola_tiket.php?id_konser=<?php echo $id_konser_terpilih; ?>&aksi=edit&id=<?php echo $tiket['id_kategori']; ?>" class="btn btn-edit">Edit</a>
                        <a href="kelola_tiket.php?id_konser=<?php echo $id_konser_terpilih; ?>&aksi=hapus&id=<?php echo $tiket['id_kategori']; ?>" 
                           class="btn btn-hapus" 
                           onclick="return confirm('Anda yakin ingin menghapus kategori tiket ini?');">
                           Hapus
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>


<?php
// 8. Panggil Footer
echo '</main>'; // Penutup .admin-content
echo '</div>'; // Penutup .admin-wrapper
require_once '../templates/footer.php';
?>