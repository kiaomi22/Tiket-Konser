<?php
// Set judul halaman dan tandai halaman aktif
$page_title = "Validasi Pembayaran";
$current_page = "validasi"; // Untuk menandai sidebar aktif

// 1. Panggil Header, Sidebar, dan Koneksi
require_once '../templates/header.php';
require_once '../templates/sidebar_admin.php';
require_once '../config/database.php';

// Inisialisasi variabel
$pesan = '';

// --- BAGIAN LOGIKA (PROSES AKSI) ---
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $aksi = $_GET['aksi'];
    $id_pemesanan = (int)$_GET['id'];

    if ($aksi == 'setuju') {
        // --- AKSI SETUJU (LUNAS) ---
        try {
            $sql = "UPDATE tbl_pemesanan SET status_pembayaran = 'Lunas' 
                    WHERE id_pemesanan = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_pemesanan]);
            $pesan = '<div class="alert success">Pesanan #' . $id_pemesanan . ' berhasil disetujui (Lunas).</div>';
        } catch (PDOException $e) {
            $pesan = '<div class="alert error">Gagal menyetujui pesanan: ' . $e->getMessage() . '</div>';
        }
    } 
    
    elseif ($aksi == 'batal') {
        // --- AKSI BATAL (TOLAK & KEMBALIKAN STOK) ---
        // Ini adalah proses yang kritis, harus pakai TRANSACTION
        
        try {
            // Mulai Transaksi
            $pdo->beginTransaction();

            // 1. Ambil semua detail tiket yang dibeli di pesanan ini
            $sql_get_detail = "SELECT id_kategori_tiket, jumlah_tiket FROM tbl_detail_pemesanan WHERE id_pemesanan = ?";
            $stmt_detail = $pdo->prepare($sql_get_detail);
            $stmt_detail->execute([$id_pemesanan]);
            $items_to_restore = $stmt_detail->fetchAll();

            // 2. Loop dan kembalikan stoknya
            $sql_restore_stok = "UPDATE tbl_kategori_tiket SET stok = stok + ? WHERE id_kategori = ?";
            $stmt_restore = $pdo->prepare($sql_restore_stok);
            
            foreach ($items_to_restore as $item) {
                $stmt_restore->execute([$item['jumlah_tiket'], $item['id_kategori_tiket']]);
            }

            // 3. Ubah status pesanan menjadi 'Batal'
            $sql_batal = "UPDATE tbl_pemesanan SET status_pembayaran = 'Batal', bukti_pembayaran = 'DITOLAK' 
                          WHERE id_pemesanan = ?";
            $stmt_batal = $pdo->prepare($sql_batal);
            $stmt_batal->execute([$id_pemesanan]);

            // Jika semua berhasil, commit transaksi
            $pdo->commit();
            $pesan = '<div class="alert success">Pesanan #' . $id_pemesanan . ' berhasil ditolak/dibatalkan. Stok telah dikembalikan.</div>';

        } catch (Exception $e) {
            // Jika ada error, batalkan semua
            $pdo->rollBack();
            $pesan = '<div class="alert error">Gagal membatalkan pesanan (Rollback): ' . $e->getMessage() . '</div>';
        }
    }
    
    // Redirect untuk membersihkan URL dari parameter 'aksi'
    if ($pesan) {
        $_SESSION['admin_message'] = $pesan;
        header("Location: validasi_bayar.php");
        exit;
    }
}

// Cek pesan dari session (setelah redirect)
if (isset($_SESSION['admin_message'])) {
    $pesan = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}


// --- BAGIAN TAMPILAN (FETCH DATA) ---
// Ambil semua pesanan yang SUDAH upload bukti & statusnya 'Menunggu'
try {
    $sql_fetch = "SELECT
                      p.id_pemesanan, p.tanggal_pemesanan, p.total_harga, p.bukti_pembayaran,
                      u.nama_lengkap AS nama_pemesan,
                      k.nama_konser,
                      GROUP_CONCAT(d.jumlah_tiket, 'x ', kt.nama_kategori SEPARATOR '<br>') AS rincian_tiket
                  FROM
                      tbl_pemesanan p
                  JOIN
                      tbl_users u ON p.id_user = u.id_user
                  JOIN
                      tbl_detail_pemesanan d ON p.id_pemesanan = d.id_pemesanan
                  JOIN
                      tbl_kategori_tiket kt ON d.id_kategori_tiket = kt.id_kategori
                  JOIN
                      tbl_konser k ON kt.id_konser = k.id_konser
                  WHERE
                      p.status_pembayaran = 'Menunggu' AND p.bukti_pembayaran IS NOT NULL
                  GROUP BY
                      p.id_pemesanan
                  ORDER BY
                      p.tanggal_pemesanan ASC";
                      
    $stmt_fetch = $pdo->query($sql_fetch);
    $daftar_validasi = $stmt_fetch->fetchAll();

} catch (PDOException $e) {
    $daftar_validasi = [];
    $pesan .= '<div class="alert error">Gagal mengambil data validasi: ' . $e->getMessage() . '</div>';
}
?>

<!-- CSS untuk Tabel dan Aksi -->
<style>
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .alert.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    table th, table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    table th { background-color: #f2f2f2; }
    table tr:nth-child(even) { background-color: #f9f9f9; }
    
    .btn-aksi {
        padding: 5px 10px;
        text-decoration: none;
        color: white;
        border-radius: 4px;
        font-size: 0.9em;
        margin: 2px;
        display: inline-block;
    }
    .btn-setuju { background-color: #28a745; }
    .btn-batal { background-color: #dc3545; }
    .btn-bukti { background-color: #007bff; }
    
    .no-data { text-align: center; color: #777; padding: 20px; }
</style>

<h1>Validasi Pembayaran</h1>
<p>Daftar pesanan yang telah mengupload bukti pembayaran dan menunggu validasi.</p>

<!-- Tampilkan pesan sukses/error -->
<?php echo $pesan; ?>

<table>
    <thead>
        <tr>
            <th>ID Pesanan</th>
            <th>Pemesan</th>
            <th>Detail Pesanan</th>
            <th>Total Bayar</th>
            <th>Bukti Pembayaran</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($daftar_validasi)): ?>
            <tr>
                <td colspan="6" class="no-data">Belum ada data pembayaran yang perlu divalidasi.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($daftar_validasi as $validasi): ?>
                <tr>
                    <td><strong>#<?php echo $validasi['id_pemesanan']; ?></strong></td>
                    <td><?php echo htmlspecialchars($validasi['nama_pemesan']); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($validasi['nama_konser']); ?></strong><br>
                        <small><?php echo $validasi['rincian_tiket']; ?></small>
                    </td>
                    <td>Rp <?php echo number_format($validasi['total_harga'], 0, ',', '.'); ?></td>
                    <td>
                        <!-- Link ke file bukti bayar di folder uploads/ -->
                        <a href="../uploads/<?php echo htmlspecialchars($validasi['bukti_pembayaran']); ?>" 
                           target="_blank" class="btn-aksi btn-bukti">
                           Lihat Bukti
                        </a>
                    </td>
                    <td>
                        <a href="validasi_bayar.php?aksi=setuju&id=<?php echo $validasi['id_pemesanan']; ?>" 
                           class="btn-aksi btn-setuju" 
                           onclick="return confirm('Anda yakin ingin MENYETUJUI pembayaran ini?');">
                           Setujui
                        </a>
                        <a href="validasi_bayar.php?aksi=batal&id=<?php echo $validasi['id_pemesanan']; ?>" 
                           class="btn-aksi btn-batal" 
                           onclick="return confirm('Anda yakin ingin MENOLAK pembayaran ini? Stok akan dikembalikan.');">
                           Tolak
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