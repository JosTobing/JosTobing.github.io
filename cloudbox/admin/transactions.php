<?php
require_once '../config/auth.php';
$auth->requireAdmin();

$functions = new CloudBoxFunctions();
$db = new Database();
$conn = $db->getConnection();

$transactions = $conn->query("
    SELECT t.*, u.full_name, u.email, sp.plan_name 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    JOIN storage_plans sp ON t.plan_id = sp.id 
    ORDER BY t.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi - Admin CloudBox</title>
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
            <a href="transactions.php" class="nav-item active"><i class="fas fa-money-bill-wave"></i> Transactions</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
            <a href="../auth/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <h2>Daftar Transaksi</h2>
        </header>
        <div class="admin-content">
            <div class="table-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Kode</th><th>Customer</th><th>Paket</th><th>Jumlah</th>
                            <th>Status Bayar</th><th>Status Aktif</th><th>Tanggal</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($transactions as $trx): ?>
                        <tr>
                            <td><?= $trx['transaction_code'] ?></td>
                            <td><?= htmlspecialchars($trx['full_name']) ?></td>
                            <td><?= $trx['plan_name'] ?></td>
                            <td><?= $functions->formatCurrency($trx['amount']) ?></td>
                            <td><span class="status-badge status-<?= $trx['payment_status'] ?>"><?= ucfirst($trx['payment_status']) ?></span></td>
                            <td><span class="status-badge status-<?= $trx['status'] ?>"><?= ucfirst($trx['status']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($trx['created_at'])) ?></td>
                            <td>
                                <?php if($trx['payment_status'] == 'pending'): ?>
                                <button class="btn-icon" title="Konfirmasi Pembayaran" onclick="confirmPayment(<?= $trx['id'] ?>)"><i class="fas fa-check"></i></button>
                                <?php endif; ?>
                                <!-- Tombol tambah kuota manual -->
                                <button class="btn-icon" title="Tambah Kuota Manual" onclick="addQuota(<?= $trx['user_id'] ?>)"><i class="fas fa-plus-circle"></i></button>
                                <!-- Tombol hapus customer -->
                                <button class="btn-icon" title="Hapus Customer" onclick="deleteCustomer(<?= $trx['user_id'] ?>, '<?= htmlspecialchars($trx['full_name'], ENT_QUOTES) ?>')"><i class="fas fa-trash-alt" style="color:red;"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
// Konfirmasi pembayaran
function confirmPayment(id) {
    if(confirm('Konfirmasi pembayaran ini?')) {
        fetch('../api/admin_api.php?action=confirmPayment', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({transaction_id: id})
        }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Gagal: '+d.message); });
    }
}

// Tambah kuota manual
function addQuota(userId) {
    const additional = prompt('Masukkan tambahan kuota (dalam bytes):', '1073741824'); // default 1 GB
    if (additional && !isNaN(additional)) {
        fetch('../api/admin_api.php?action=addQuota', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({user_id: userId, additional_quota: parseInt(additional)})
        })
        .then(r => r.json())
        .then(d => {
            if(d.success) {
                alert('Kuota berhasil ditambahkan. Total sekarang: ' + d.new_quota_formatted);
                location.reload();
            } else {
                alert('Gagal: ' + d.message);
            }
        });
    }
}

// Hapus customer
function deleteCustomer(userId, name) {
    if(confirm(`Hapus customer "${name}" beserta semua data? Tindakan ini tidak dapat dibatalkan.`)) {
        fetch('../api/admin_api.php?action=deleteCustomer', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({user_id: userId})
        })
        .then(r => r.json())
        .then(d => {
            if(d.success) {
                alert('Customer dihapus');
                location.reload();
            } else {
                alert('Gagal: ' + d.message);
            }
        });
    }
}
</script>
</body>
</html>