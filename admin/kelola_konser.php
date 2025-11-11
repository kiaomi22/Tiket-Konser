<?php
// Set judul halaman dan tandai halaman aktif
$page_title = "Kelola Konser";
$current_page = "konser"; // Untuk menandai sidebar aktif

// 1. Panggil Header, Sidebar, dan Koneksi
require_once '../templates/header.php';
require_once '../templates/sidebar_admin.php';
require_once '../config/database.php';

// --- BAGIAN LOGIKA (PROSES FORM) ---

// Tentukan folder upload (keluar satu level dari 'admin')
$upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    // Buat folder jika belum ada
    if (!@mkdir($upload_dir, 0755, true)) {
        // Cek jika gagal membuat folder (karena masalah izin)
        $pesan = '<div class="alert error">Gagal membuat folder uploads. Pastikan folder htdocs/tiket_konser writable.</div>';
    }
}

// Inisialisasi variabel
$pesan = '';
$aksi = $_GET['aksi'] ?? ''; // Ambil aksi dari URL (jika ada)
$id_konser = $_GET['id'] ?? 0; // Ambil id dari URL (jika ada)

// Inisialisasi data untuk form (default kosong)
$data_form = [
    'id_konser' => 0,
    'nama_konser' => '',
    'lokasi' => '',
    'tanggal_waktu' => '',
    'deskripsi' => '',
    'gambar' => '' // TAMBAHAN BARU
];
$label_form = 'Tambah Konser Baru';
$aksi_form = 'tambah';


// 2. PROSES POST (TAMBAH / UPDATE)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $id_konser_post = $_POST['id_konser'];
    $nama_konser = trim($_POST['nama_konser']);
    $lokasi = trim($_POST['lokasi']);
    $tanggal_waktu = $_POST['tanggal_waktu'];
    $deskripsi = trim($_POST['deskripsi']);
    $aksi_post = $_POST['aksi'];
    $gambar_lama = $_POST['gambar_lama'] ?? ''; // TAMBAHAN BARU

    // Inisialisasi nama file gambar
    $nama_file_gambar = $gambar_lama;

    // --- LOGIKA UPLOAD GAMBAR BARU ---
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['gambar'];
        
        // Validasi Tipe File Sederhana
        $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
        $file_info = @getimagesize($file['tmp_name']); // @ untuk menekan error jika bukan gambar
        
        if ($file_info && in_array($file_info[2], $allowed_types)) {
            // Buat nama file unik
            $nama_file_unik = 'konser_' . time() . '_' . preg_replace("/[^a-zA-Z0-9-_\.]/", "_", basename($file['name']));
            $target_path = $upload_dir . $nama_file_unik;

            // Pindahkan file
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $nama_file_gambar = $nama_file_unik; // Gunakan file baru

                // Hapus file lama jika ini mode 'edit' dan file lama ada
                if ($aksi_post == 'edit' && !empty($gambar_lama) && file_exists($upload_dir . $gambar_lama)) {
                    @unlink($upload_dir . $gambar_lama);
                }
            } else {
                $pesan = '<div class="alert error">Gagal memindahkan file gambar. Pastikan folder uploads/ writable.</div>';
            }
        } else {
            $pesan = '<div class="alert error">Tipe file gambar tidak valid (izinkan JPG, PNG, GIF).</div>';
        }
    }
    // --- AKHIR LOGIKA UPLOAD ---

    // Lanjutkan proses DB hanya jika tidak ada error upload
    if (empty($pesan)) {
        try {
            if ($aksi_post == 'tambah') {
                // --- LOGIKA TAMBAH (DENGAN GAMBAR) ---
                $sql = "INSERT INTO tbl_konser (nama_konser, lokasi, tanggal_waktu, deskripsi, gambar) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nama_konser, $lokasi, $tanggal_waktu, $deskripsi, $nama_file_gambar]);
                $pesan = '<div class="alert success">Konser baru berhasil ditambahkan.</div>';

            } elseif ($aksi_post == 'edit') {
                // --- LOGIKA UPDATE (DENGAN GAMBAR) ---
                $sql = "UPDATE tbl_konser 
                        SET nama_konser = ?, lokasi = ?, tanggal_waktu = ?, deskripsi = ?, gambar = ? 
                        WHERE id_konser = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nama_konser, $lokasi, $tanggal_waktu, $deskripsi, $nama_file_gambar, $id_konser_post]);
                $pesan = '<div class="alert success">Data konser berhasil diperbarui.</div>';
            }
        } catch (PDOException $e) {
            $pesan = '<div class="alert error">Gagal memproses data: ' . $e->getMessage() . '</div>';
        }
    }
}

// 3. PROSES GET (HAPUS)
if ($aksi == 'hapus' && $id_konser > 0) {
    try {
        // Ambil nama file gambar dulu sebelum dihapus
        $stmt_get_img = $pdo->prepare("SELECT gambar FROM tbl_konser WHERE id_konser = ?");
        $stmt_get_img->execute([$id_konser]);
        $gambar_lama = $stmt_get_img->fetchColumn();

        // Hapus data dari database
        $sql = "DELETE FROM tbl_konser WHERE id_konser = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_konser]);

        // Hapus file gambar dari server
        if ($gambar_lama && file_exists($upload_dir . $gambar_lama)) {
            @unlink($upload_dir . $gambar_lama);
        }

        $pesan = '<div class="alert success">Konser dan gambar terkait berhasil dihapus.</div>';
        $_SESSION['admin_message'] = $pesan; // Simpan di session untuk redirect
        header("Location: kelola_konser.php");
        exit;
    } catch (PDOException $e) {
        $pesan = '<div class="alert error">Gagal menghapus data: ' . $e->getMessage() . '</div>';
    }
}

// 4. PROSES GET (UNTUK MENGISI FORM EDIT)
if ($aksi == 'edit' && $id_konser > 0) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_konser WHERE id_konser = ?");
    $stmt->execute([$id_konser]);
    $data_edit = $stmt->fetch();
    
    if ($data_edit) {
        $data_form = $data_edit;
        $label_form = 'Edit Data Konser';
        $aksi_form = 'edit';
    }
}

// Cek pesan dari session (jika ada redirect)
if (isset($_SESSION['admin_message'])) {
    $pesan = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

// 5. AMBIL SEMUA DATA KONSER (UNTUK TABEL)
try {
    // Ambil kolom 'gambar' juga
    $stmt_list = $pdo->query("SELECT id_konser, nama_konser, lokasi, tanggal_waktu, gambar FROM tbl_konser ORDER BY tanggal_waktu DESC");
    $daftar_konser = $stmt_list->fetchAll();
} catch (PDOException $e) {
    $daftar_konser = [];
    $pesan .= '<div class="alert error">Gagal mengambil daftar konser: ' . $e->getMessage() . '</div>';
}

?>

<!-- CSS untuk Form dan Tabel -->
<style>
    .form-konser { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
    .form-group input[type="text"],
    .form-group input[type="datetime-local"],
    .form-group input[type="file"],
    .form-group textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box; 
    }
    .current-image { max-width: 200px; height: auto; display: block; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;}
    .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-primary { background-color: #007bff; color: white; }
    .btn-secondary { background-color: #6c757d; color: white; }
    .btn-edit { background-color: #ffc107; color: #333; font-size: 0.9em; padding: 5px 8px; }
    .btn-hapus { background-color: #dc3545; color: white; font-size: 0.9em; padding: 5px 8px; }
    .btn-tiket { background-color: #17a2b8; color: white; font-size: 0.9em; padding: 5px 8px; }
    .alert { padding: 15px; margin-bottom: 15px; border-radius: 4px; }
    .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table th, table td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: middle; }
    table th { background-color: #f2f2f2; }
    table td .aksi-links a { margin-right: 5px; }
    table .thumb-img { max-width: 100px; height: auto; border-radius: 4px; }
</style>

<h1>Manajemen Konser</h1>

<!-- Tampilkan pesan sukses/error -->
<?php echo $pesan; ?>

<!-- --- BAGIAN TAMPILAN (FORM) --- -->
<div class="form-konser">
    <h2><?php echo $label_form; ?></h2>
    
    <!-- TAMBAHAN BARU: enctype="multipart/form-data" -->
    <form action="kelola_konser.php" method="POST" enctype="multipart/form-data">
        
        <input type="hidden" name="aksi" value="<?php echo $aksi_form; ?>">
        <input type="hidden" name="id_konser" value="<?php echo htmlspecialchars($data_form['id_konser']); ?>">
        <input type="hidden" name="gambar_lama" value="<?php echo htmlspecialchars($data_form['gambar']); ?>">
        
        <div class="form-group">
            <label for="nama_konser">Nama Konser</label>
            <input type="text" id="nama_konser" name="nama_konser" 
                   value="<?php echo htmlspecialchars($data_form['nama_konser']); ?>" required>
        </div>
        <div class="form-group">
            <label for="lokasi">Lokasi</label>
            <input type="text" id="lokasi" name="lokasi" 
                   value="<?php echo htmlspecialchars($data_form['lokasi']); ?>" required>
        </div>
        <div class="form-group">
            <label for="tanggal_waktu">Tanggal & Waktu</label>
            <input type="datetime-local" id="tanggal_waktu" name="tanggal_waktu" 
                   value="<?php echo $data_form['tanggal_waktu'] ? date('Y-m-d\TH:i', strtotime($data_form['tanggal_waktu'])) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="4"><?php echo htmlspecialchars($data_form['deskripsi']); ?></textarea>
        </div>
        
        <!-- --- INPUT GAMBAR BARU --- -->
        <div class="form-group">
            <label for="gambar">Gambar Konser</label>
            <input type="file" id="gambar" name="gambar" accept="image/jpeg, image/png, image/gif">
            
            <?php if ($aksi_form == 'edit' && !empty($data_form['gambar'])): ?>
                <p style="font-size: 0.9em; margin-top: 10px;">Gambar saat ini:</p>
                <img src="<?php echo $upload_dir . htmlspecialchars($data_form['gambar']); ?>" alt="Gambar saat ini" class="current-image">
                <small>Upload file baru untuk mengganti gambar ini.</small>
            <?php endif; // <-- INI YANG HILANG DAN SUDAH DIPERBAIKI ?>
            
        </div>
        <!-- --- BATAS INPUT GAMBAR --- -->

        
        <button type="submit" class="btn btn-primary">
            <?php echo ($aksi_form == 'edit') ? 'Update Data' : 'Tambah Konser'; ?>
        </button>
        
        <?php if ($aksi_form == 'edit'): ?>
            <!-- Tombol Batal untuk kembali ke mode Tambah -->
            <a href="kelola_konser.php" class="btn btn-secondary">Batal Edit</a>
        <?php endif; ?>
    </form>
</div>


<!-- --- BAGIAN TAMPILAN (TABEL) --- -->
<hr>
<h2>Daftar Konser</h2>
<table>
    <thead>
        <tr>
            <th>Gambar</th> <!-- TAMBAHAN BARU -->
            <th>Nama Konser</th>
            <th>Lokasi</th>
            <th>Waktu</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($daftar_konser)): ?>
            <tr>
                <td colspan="5" style="text-align: center;">Belum ada data konser.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($daftar_konser as $konser): ?>
                <tr>
                    <td>
                        <!-- TAMBAHAN BARU: Tampilkan Thumbnail -->
                        <?php if (!empty($konser['gambar']) && file_exists($upload_dir . $konser['gambar'])): ?>
                            <img src="<?php echo $upload_dir . htmlspecialchars($konser['gambar']); ?>" alt="Thumbnail" class="thumb-img">
                        <?php else: ?>
                            <small>No Img</small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($konser['nama_konser']); ?></td>
                    <td><?php echo htmlspecialchars($konser['lokasi']); ?></td>
                    <td><?php echo date('d M Y, H:i', strtotime($konser['tanggal_waktu'])); ?></td>
                    <td class="aksi-links">
                        <a href="kelola_tiket.php?id_konser=<?php echo $konser['id_konser']; ?>" class="btn btn-tiket">Kelola Tiket</a>
                        <a href="kelola_konser.php?aksi=edit&id=<?php echo $konser['id_konser']; ?>" class="btn btn-edit">Edit</a>
                        <a href="kelola_konser.php?aksi=hapus&id=<?php echo $konser['id_konser']; ?>" 
                           class="btn btn-hapus" 
                           onclick="return confirm('Anda yakin ingin menghapus konser ini? Semua data tiket dan gambar terkait akan ikut terhapus permanen.');">
                           Hapus
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>


<?php
// 6. Panggil Footer
echo '</main>'; // Penutup .admin-content
echo '</div>'; // Penutup .admin-wrapper
require_once '../templates/footer.php';
?>