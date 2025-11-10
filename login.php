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

     $email = trim($_POST['email']);
     $password = trim($_POST['password']);

     if (empty($email) || empty($password)) {
         $error_message = 'Email dan Password wajib diisi!';
     } else {
 
// Ganti blok try...catch Anda dengan ini untuk debug
try {

    $sql = "SELECT * FROM tbl_users WHERE email = ?";
 $stmt = $pdo->prepare($sql);
 $stmt->execute([$email]);
 
 $user = $stmt->fetch(); // Ambil data user
 
 echo "<pre>"; // Agar tampilannya rapi
 echo "--- DATA DEBUG ---<br>";
 echo "Email dari Form: " ."<strong>" . htmlspecialchars($email) . "</strong>" . "<br>";
 echo "Password dari Form: " . "<strong>" . htmlspecialchars($password) . "</strong>" . "<br>";

 echo "<br>--- DATA DARI DATABASE ---<br>";
 var_dump($user);

 if ($user) {
 echo "<br>--- HASIL VERIFIKASI ---<br>";
 echo "Hash di DB: " . "<strong>" . $user['password'] . "</strong>" . "<br>";
 echo "Panjang Hash di DB: " . "<strong>" . strlen($user['password']) . "</strong>" . " karakter<br>";

 if (password_verify($password, $user['password'])) {
 echo "Hasil password_verify(): <strong style='color:green;'>BERHASIL (TRUE)</strong>";
 } else {
 echo "Hasil password_verify(): <strong style='color:red;'>GAGAL (FALSE)</strong>";
 }
 } else {
  echo "<br>--- KESIMPULAN ---<br>";
 echo "<strong style='color:red;'>Email tidak ditemukan di database.</strong>";
 }

 echo "</pre>";
 die(); // Hentikan script di sini agar kita bisa baca


 if ($user && password_verify($password, $user['password'])) {
 

 } else {

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
