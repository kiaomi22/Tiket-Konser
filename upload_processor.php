<?php
// Selalu mulai session
session_start();

// 1. Panggil Koneksi Database
require_once 'config/database.php';

// --- TAHAP 1: VALIDASI & KEAMANAN ---

// 2. Cek Keamanan: Apakah user sudah login?
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    $_SESSION['error_message'] = "Sesi Anda telah habis. Silakan login kembali.";
    header("Location: login.php");
    exit;
}
$id_user = $_SESSION['user_id'];

// 3. Cek Keamanan: Apakah data dikirim via POST dan file ada?
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['id_pemesanan']) || !isset($_FILES['bukti_bayar'])) {
    $_SESSION['error_message'] = 'Permintaan tidak valid.';
    header("Location: tiket_saya.php");
    exit;
}

// 4. Ambil data
$id_pemesanan = (int)$_POST['id_pemesanan'];
$file = $_FILES['bukti_bayar'];

// --- TAHAP 2: PROSES FILE UPLOAD ---

// 5. Cek apakah ada error saat upload
if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = 'Terjadi error saat mengupload file. Kode: ' . $file['error'];
    header("Location: tiket_saya.php");
    exit;
}

// 6. Tentukan folder penyimpanan
$upload_dir = 'uploads/';
// Pastikan folder 'uploads/' ada dan bisa ditulis (writable)

// 7. Validasi Tipe File (Izinkan JPG, JPEG, PNG)
$file_info = getimagesize($file['tmp_name']);
$allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF]; // Menambahkan GIF sebagai opsi

if ($file_info === false || !in_array($file_info[2], $allowed_types)) {
    $_SESSION['error_message'] = 'File tidak valid. Harap upload file gambar (JPG, JPEG, PNG, GIF).';
    header("Location: tiket_saya.php");
    exit;
}

// 8. Validasi Ukuran File (Contoh: maks 2MB)
$max_size = 2 * 1024 * 1024; // 2 Megabytes
if ($file['size'] > $max_size) {
    $_SESSION['error_message'] = 'Ukuran file terlalu besar. Maksimal 2MB.';
    header("Location: tiket_saya.php");
    exit;
}

// 9. Buat Nama File Unik (PENTING!)
// Format: [id_pemesanan]_[timestamp]_[nama_asli_bersih]
$timestamp = time();
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
// Bersihkan nama file dari karakter aneh
$safe_original_name = preg_replace("/[^a-zA-Z0-9-_\.]/", "_", basename($file['name']));

// Pastikan nama file tidak kosong setelah dibersihkan
if(empty($safe_original_name)) {
    $safe_original_name = "file_upload." . $file_extension;
}

$new_filename = $id_pemesanan . '_' . $timestamp . '_' . $safe_original_name;
$target_path = $upload_dir . $new_filename;


// 10. Pindahkan file dari temporary ke folder 'uploads/'
if (move_uploaded_file($file['tmp_name'], $target_path)) {
    
    // --- TAHAP 3: UPDATE DATABASE ---
    
    try {
        // Update nama file bukti bayar di database
        // Kita juga pastikan update ini HANYA untuk user yang login (keamanan)
        $sql = "UPDATE tbl_pemesanan 
                SET bukti_pembayaran = ? 
                WHERE id_pemesanan = ? AND id_user = ?";
        
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$new_filename, $id_pemesanan, $id_user])) {
            // Berhasil!
            $_SESSION['message'] = 'Bukti pembayaran berhasil diupload dan sedang menunggu validasi admin.';
            header("Location: tiket_saya.php");
            exit;
        } else {
            // Gagal update DB, hapus file yang sudah terlanjur diupload
            unlink($target_path); 
            $_SESSION['error_message'] = 'Gagal menyimpan data ke database.';
            header("Location: tiket_saya.php");
            exit;
        }

    } catch (PDOException $e) {
        // Gagal update DB, hapus file
        unlink($target_path);
        $_SESSION['error_message'] = 'Error database: ' . $e->getMessage();
        header("Location: tiket_saya.php");
        exit;
    }

} else {
    // Gagal memindahkan file
    $_SESSION['error_message'] = 'Gagal menyimpan file yang diupload. Pastikan folder "uploads/" writable.';
    header("Location: tiket_saya.php");
    exit;
}
?>