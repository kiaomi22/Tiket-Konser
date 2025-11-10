<?php
// Mulai session di paling atas file
session_start();

// 1. Panggil file koneksi database
require_once 'config/database.php';

// Inisialisasi variabel untuk pesan error
$error_message = '';

// 2. Cek apakah form sudah disubmit (method POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 3. Ambil data dari form dan bersihkan (trim)
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $konfirmasi_password = trim($_POST['konfirmasi_password']);

    // 4. Validasi Sederhana
    if (empty($nama_lengkap) || empty($email) || empty($password) || empty($konfirmasi_password)) {
        $error_message = 'Semua kolom wajib diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format email tidak valid!';
    } elseif ($password !== $konfirmasi_password) {
        $error_message = 'Password dan Konfirmasi Password tidak cocok!';
    } else {
        
        // --- Validasi Lolos, Lanjut Cek Database ---

        try {
            // 5. Cek apakah email sudah terdaftar (PENTING!)
            $sql_check = "SELECT id_user FROM tbl_users WHERE email = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$email]);

            if ($stmt_check->fetch()) {
                // Jika fetch() mengembalikan data, email sudah ada
                $error_message = 'Email ini sudah terdaftar. Silakan gunakan email lain.';
            } else {
                
                // --- Email aman, Lanjut Proses Registrasi ---

                // 6. HASH PASSWORD (KRUSIAL untuk "Layak Jual")
                // Jangan pernah simpan password sebagai plain text!
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // 7. Masukkan data ke database (tbl_users)
                $sql_insert = "INSERT INTO tbl_users (nama_lengkap, email, password, role) 
                               VALUES (?, ?, ?, 'user')";
                
                $stmt_insert = $pdo->prepare($sql_insert);
                
                // Eksekusi statement dengan data yang aman
                if ($stmt_insert->execute([$nama_lengkap, $email, $hashed_password])) {
                    
                    // 8. Registrasi Berhasil
                    // Set pesan sukses di session untuk ditampilkan di halaman login
                    $_SESSION['message'] = 'Registrasi berhasil! Silakan login.';
                    
                    // Arahkan (redirect) ke halaman login
                    header("Location: login.php");
                    exit; // Pastikan script berhenti setelah redirect

                } else {
                    $error_message = 'Terjadi kesalahan saat mendaftar. Coba lagi nanti.';
                }
            }

        } catch (PDOException $e) {
            // Tangani error database
            $error_message = "Error database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Tiket Konser</title>
    <style>
        body { font-family: sans-serif; display: grid; place-items: center; min-height: 90vh; background-color: #f4f4f4; }
        .container { background: #fff; border: 1px solid #ccc; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background-color: #0056b3; }
        .error { color: red; background: #ffebee; border: 1px solid red; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .login-link { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>

    <div class="container">
        <h2>Registrasi Akun Baru</h2>
        <p>Silakan isi data diri Anda untuk mendaftar.</p>
        
        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="konfirmasi_password">Konfirmasi Password</label>
                <input type="password" id="konfirmasi_password" name="konfirmasi_password" required>
            </div>
            <button type="submit" class="btn">Daftar</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>

</body>
</html>