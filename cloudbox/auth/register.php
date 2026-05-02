<?php
require_once '../config/auth.php';

if ($auth->isLoggedIn()) {
    header('Location: ../customer/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'Password tidak cocok';
    } else {
        $result = $auth->register($_POST);
        if ($result['success']) {
            header('Location: login.php?registered=1');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CloudBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-cloud"></i>
                <h1>CloudBox</h1>
                <p>Buat Akun Baru</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-user"></i> Nama Lengkap
                    </label>
                    <input type="text" id="full_name" name="full_name" required 
                           placeholder="Masukkan nama lengkap">
                </div>
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-at"></i> Username
                    </label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Pilih username">
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Masukkan email">
                </div>
                
                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> No. Telepon
                    </label>
                    <input type="tel" id="phone" name="phone" 
                           placeholder="Masukkan nomor telepon">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Minimal 8 karakter">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Konfirmasi Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Ulangi password">
                </div>
                
                <button type="submit" class="btn-auth">
                    <i class="fas fa-user-plus"></i> Daftar
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Sudah punya akun? <a href="login.php">Masuk sekarang</a></p>
            </div>
        </div>
    </div>
</body>
</html>