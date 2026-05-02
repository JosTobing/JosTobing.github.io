<?php
require_once '../config/auth.php';
$auth->requireAdmin();
// Simple settings page (contoh)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update settings misal max file size, dsb. Simpan di file config atau database.
    $message = 'Pengaturan berhasil disimpan (simulasi).';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin CloudBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-cloud"></i><h2>CloudBox Admin</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item"><i class="fas fa-dashboard"></i> Dashboard</a>
            <a href="customers.php" class="nav-item"><i class="fas fa-users"></i> Customers</a>
            <a href="storage_plans.php" class="nav-item"><i class="fas fa-layer-group"></i> Storage Plans</a>
            <a href="transactions.php" class="nav-item"><i class="fas fa-money-bill-wave"></i> Transactions</a>
            <a href="settings.php" class="nav-item active"><i class="fas fa-cog"></i> Settings</a>
            <a href="../auth/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <h2>Pengaturan Aplikasi</h2>
        </header>
        <div class="admin-content">
            <?php if($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            <div class="table-card">
                <form method="POST">
                    <h3>Konfigurasi Umum</h3>
                    <div class="form-group">
                        <label>Maksimal Ukuran Upload (MB)</label>
                        <input type="number" name="max_upload" value="100" class="form-control">
                    </div>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>