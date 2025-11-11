<?php
// Set judul halaman dan tandai halaman aktif
$page_title = "Laporan Penjualan";
$current_page = "laporan"; // Untuk menandai sidebar aktif

// 1. Panggil Header, Sidebar, dan Koneksi
require_once '../templates/header.php';
require_once '../templates/sidebar_admin.php';
require_once '../config/database.php';

// Inisialisasi variabel
$pesan_error = '';
$laporan_data = [];
$total_pendapatan = 0;
$total_tiket_terjual = 0;
$total_transaksi_lunas = 0;

// --- BAGIAN LOGIKA (FETCH DATA) ---
try {
    // 2. Ambil Statistik Ringkasan (HANYA YANG LUNAS)
    
    // Total Pendapatan
    $stmt_rev = $pdo->query("SELECT SUM(total_harga) FROM tbl_pemesanan WHERE status_pembayaran = 'Lunas'");
    $total_pendapatan = $stmt_rev->fetchColumn() ?: 0;

    // Total Tiket Terjual
    $stmt_tix = $pdo->query("
        SELECT SUM(d.jumlah_tiket) 
        FROM tbl_detail_pemesanan d
        JOIN tbl_pemesanan p ON d.id_pemesanan = p.id_pemesanan
        WHERE p.status_pembayaran = 'Lunas'
    ");
    $total_tiket_terjual = $stmt_tix->fetchColumn() ?: 0;

    // Total Transaksi Sukses
    $stmt_trx = $pdo->query("SELECT COUNT(id_pemesanan) FROM tbl_pemesanan WHERE status_pembayaran = 'Lunas'");
    $total_transaksi_lunas = $stmt_trx->fetchColumn() ?: 0;


    // 3. Ambil Laporan Rincian per Konser (HANYA YANG LUNAS)
    $sql_report = "
        SELECT
            k.nama_konser,
            SUM(d.jumlah_tiket) AS total_tiket_terjual,
            SUM(d.jumlah_tiket * d.harga_saat_pesan) AS total_pendapatan_konser
        FROM
            tbl_konser k
        JOIN
            tbl_kategori_tiket kt ON k.id_konser = kt.id_konser
        JOIN
            tbl_detail_pemesanan d ON kt.id_kategori = d.id_kategori_tiket
        JOIN
            tbl_pemesanan p ON d.id_pemesanan = p.id_pemesanan
        WHERE
            p.status_pembayaran = 'Lunas'
        GROUP BY
            k.id_konser, k.nama_konser
        ORDER BY
            total_pendapatan_konser DESC
    ";
    
    $stmt_report = $pdo->query($sql_report);
    $laporan_data = $stmt_report->fetchAll();

} catch (PDOException $e) {
    $pesan_error = '<div class="alert error">Gagal mengambil data laporan: ' . $e->getMessage() . '</div>';
}
?>

<!-- CSS untuk Statistik dan Laporan -->
<style>
    .alert.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    .stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
    .stat-card {
        background: #f4f4f4;
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .stat-card h3 { font-size: 2.5em; margin: 0 0 10px 0; color: #333; }
    .stat-card p { font-size: 1.1em; color: #555; margin: 0; }
    
    table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    table th, table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    table th { background-color: #f2f2f2; }
    table tr:nth-child(even) { background-color: #f9f9f9; }
    table td:nth-child(2), table td:nth-child(3) { text-align: right; }
    .no-data { text-align: center; color: #777; padding: 20px; }
</style>

<h1>Laporan Penjualan</h1>
<p>Ringkasan penjualan tiket yang telah berstatus "Lunas".</p>

<?php echo $pesan_error; ?>

<!-- --- BAGIAN RINGKASAN --- -->
<h2>Ringkasan Total</h2>
<div class="stat-grid"> 
    <div class="stat-card">
        <h3>Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></h3>
        <p>Total Pendapatan</p>
    </div>
    <div class="stat-card">
        <h3><?php echo number_format($total_tiket_terjual); ?></h3>
        <p>Total Tiket Terjual</p>
    </div>
    <div class="stat-card">
        <h3><?php echo number_format($total_transaksi_lunas); ?></h3>
        <p>Total Transaksi Lunas</p>
    </div>
</div>


<!-- --- BAGIAN TABEL RINCIAN --- -->
<hr style="margin: 30px 0;">
<h2>Rincian Penjualan per Konser</h2>
<table>
    <thead>
        <tr>
            <th>Nama Konser</th>
            <th style="text-align: right;">Total Tiket Terjual</th>
            <th style="text-align: right;">Total Pendapatan</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($laporan_data)): ?>
            <tr>
                <td colspan="3" class="no-data">Belum ada data penjualan yang lunas.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($laporan_data as $laporan): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($laporan['nama_konser']); ?></strong></td>
                    <td><?php echo number_format($laporan['total_tiket_terjual']); ?> tiket</td> 
                    <td>Rp <?php echo number_format($laporan['total_pendapatan_konser'], 0, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php
// 4. Panggil Footer
echo '</main>'; // Penutup .admin-content
echo '</div>'; // Penutup .admin-wrapper
require_once '../templates/footer.php';
?>