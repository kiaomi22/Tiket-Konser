<?php
// Mulai session di paling atas file
session_start();

// Panggil file koneksi database
require_once 'config/database.php';

// Inisialisasi variabel pesan
$session_message = '';
$error_message = '';

// Cek apakah ada pesan dari halaman registrasi
if (isset($_SESSION['message'])) {
    $session_message = $_SESSION['message'];
    unset($_SESSION['message']); // Hapus pesan agar tidak tampil lagi
}

// Cek apakah form login sudah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Ambil data dari form dan bersihkan secara paksa
    $email = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $_POST['email']);
    $password = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $_POST['password']);

    // 2. Validasi sederhana
    if (empty($email) || empty($password)) {
        $error_message = 'Email dan Password wajib diisi!';
    } else {
        
        try {
            // 3. Cari user berdasarkan email
            $sql = "SELECT * FROM tbl_users WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            
            $user = $stmt->fetch(); // Ambil data user

            // 4. Cek apakah user ditemukan DAN password-nya cocok
            if ($user && password_verify($password, $user['password'])) {
                
                // --- INI KODE YANG HILANG TADI ---
                
                // 5. Simpan data penting user ke dalam SESSION
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['user_nama'] = $user['nama_lengkap'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['is_logged_in'] = true;

                // 6. Arahkan (redirect) berdasarkan ROLE
                if ($user['role'] == 'admin') {
                    // Arahkan ke dashboard admin
                    header("Location: admin/index.php"); 
                } else {
                    // Arahkan ke halaman utama user
                    header("Location: index.php"); 
                }
                exit; // Pastikan script berhenti setelah redirect
                
                // --- BATAS KODE YANG HILANG ---

            } else {
                // --- LOGIN GAGAL ---
                $error_message = 'Email atau Password salah!';
            }

        } catch (PDOException $e) {
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
    <title>Login - Sistem Tiket Konser</title>
    <style>
        body { font-family: sans-serif; display: grid; place-items: center; min-height: 90vh; background-color: #f4f4f4; }
        .container { background: #fff; border: 1px solid #ccc; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background-color: #218838; }
        .success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .error { color: red; background: #ffebee; border: 1px solid red; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .register-link { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>

    <div class="container">
        <h2>Login Sistem</h2>
        
        <?php if (!empty($session_message)): ?>
            <div class="success"><?php echo $session_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>

</body>
</html>