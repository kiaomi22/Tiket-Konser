<?php
// Selalu mulai session
session_start();

// 1. Panggil Koneksi Database
require_once 'config/database.php';

// --- TAHAP 1: VALIDASI & KEAMANAN ---

// 2. Cek Keamanan: Apakah user sudah login?
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Anda harus login untuk melakukan checkout.";
    header("Location: login.php");
    exit;
}
$id_user = $_SESSION['user_id']; // Ambil ID user dari session

// 3. Cek Keamanan: Apakah data dikirim via POST?
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['jumlah']) || !isset($_POST['id_konser'])) {
    header("Location: index.php");
    exit;
}

// 4. Ambil data dari POST
$id_konser = (int)$_POST['id_konser'];
$jumlah_beli_array = $_POST['jumlah']; // Ini array: [id_kategori => jumlah]

// --- TAHAP 2: KALKULASI & VALIDASI STOK ---

$items_to_buy = []; // Keranjang belanja yang sudah divalidasi
$total_harga_keseluruhan = 0;
$stok_habis = false;
$nama_tiket_stok_habis = '';

// 5. Filter item yang dibeli (jumlah > 0) dan validasi harganya dari DB
try {
    $daftar_id_kategori = [];
    foreach ($jumlah_beli_array as $id_kategori => $jumlah) {
        if ((int)$jumlah > 0) {
            $daftar_id_kategori[] = (int)$id_kategori;
        }
    }

    if (empty($daftar_id_kategori)) {
        $_SESSION['error_message'] = 'Anda belum memilih jumlah tiket yang ingin dibeli.';
        header("Location: detail_konser.php?id=" . $id_konser);
        exit;
    }

    // 6. Ambil data HARGA ASLI dan STOK ASLI dari Database
    $placeholders = rtrim(str_repeat('?,', count($daftar_id_kategori)), ',');
    
    // Query ini mengambil id sebagai kolom PERTAMA, ini penting untuk FETCH_UNIQUE
    $sql_check = "SELECT id_kategori, nama_kategori, harga, stok FROM tbl_kategori_tiket 
                  WHERE id_kategori IN ($placeholders) AND id_konser = ?";
    
    $stmt_check = $pdo->prepare($sql_check);
    $params = $daftar_id_kategori;
    $params[] = $id_konser; // Tambahkan id_konser di akhir
    $stmt_check->execute($params);
    
    // --- PERBAIKAN DI SINI ---
    // Gunakan FETCH_UNIQUE agar id_kategori menjadi key array
    $tiket_data_db = $stmt_check->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    
    // 7. Loop final untuk validasi stok dan hitung total
    foreach ($jumlah_beli_array as $id_kategori => $jumlah) {
        $jumlah = (int)$jumlah;
        if ($jumlah <= 0) continue; // Lewati jika tidak beli

        if (!isset($tiket_data_db[$id_kategori])) {
             throw new Exception("Terjadi kesalahan validasi data tiket.");
        }
        
        $tiket = $tiket_data_db[$id_kategori]; // $tiket sekarang berisi ['nama_kategori', 'harga', 'stok']

        // VALIDASI STOK
        if ($jumlah > $tiket['stok']) {
            $stok_habis = true;
            $nama_tiket_stok_habis = $tiket['nama_kategori'];
            break; 
        }

        // Jika lolos, masukkan ke keranjang final
        $items_to_buy[] = [
            'id_kategori' => $id_kategori,
            'jumlah' => $jumlah,
            'harga_saat_pesan' => $tiket['harga'] // AMBIL HARGA DARI DB!
        ];
        
        $total_harga_keseluruhan += ($tiket['harga'] * $jumlah);
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error saat memvalidasi tiket: ' . $e->getMessage();
    header("Location: detail_konser.php?id=" . $id_konser);
    exit;
}

// 8. Handle jika stok tidak cukup
if ($stok_habis) {
    $_SESSION['error_message'] = "Maaf, stok untuk tiket '$nama_tiket_stok_habis' tidak mencukupi (sisa: ".$tiket['stok']."). Silakan ulangi pemesanan Anda.";
    header("Location: detail_konser.php?id=" . $id_konser);
    exit;
}

// --- TAHAP 3: DATABASE TRANSACTION (INTI) ---
try {
    // Mulai Transaksi
    $pdo->beginTransaction();

    // 1. INSERT ke tbl_pemesanan
    $sql_pemesanan = "INSERT INTO tbl_pemesanan (id_user, total_harga, status_pembayaran) 
                      VALUES (?, ?, 'Menunggu')";
    $stmt_pemesanan = $pdo->prepare($sql_pemesanan);
    $stmt_pemesanan->execute([$id_user, $total_harga_keseluruhan]);
    
    $id_pemesanan_baru = $pdo->lastInsertId();

    // Siapkan query untuk loop
    $sql_detail = "INSERT INTO tbl_detail_pemesanan (id_pemesanan, id_kategori_tiket, jumlah_tiket, harga_saat_pesan) 
                   VALUES (?, ?, ?, ?)";
    $stmt_detail = $pdo->prepare($sql_detail);
    
    $sql_update_stok = "UPDATE tbl_kategori_tiket SET stok = stok - ? WHERE id_kategori = ?";
    $stmt_update_stok = $pdo->prepare($sql_update_stok);
    
    // 2. & 3. Loop untuk insert detail dan update stok
    foreach ($items_to_buy as $item) {
        
        $stmt_detail->execute([
            $id_pemesanan_baru,
            $item['id_kategori'],
            $item['jumlah'],
            $item['harga_saat_pesan']
        ]);
        
        $stmt_update_stok->execute([
            $item['jumlah'],
            $item['id_kategori']
        ]);
    }

    // Kunci Transaksi!
    $pdo->commit();

    // --- TAHAP 4: REDIRECT SUKSES ---
    $_SESSION['message'] = 'Pemesanan Anda (ID: #'.$id_pemesanan_baru.') berhasil dibuat! Silakan segera lakukan pembayaran.';
    header("Location: tiket_saya.php");
    exit;

} catch (Exception $e) {
    // Batalkan semua query
    $pdo->rollBack();

    // --- TAHAP 4: REDIRECT GAGAL ---
    $_SESSION['error_message'] = 'Terjadi kesalahan fatal saat memproses pesanan Anda. Database dibatalkan. Silakan coba lagi. Error: ' . $e->getMessage();
    header("Location: detail_konser.php?id=" . $id_konser);
    exit;
}
?>