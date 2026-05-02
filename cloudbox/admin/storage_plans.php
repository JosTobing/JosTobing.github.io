<?php
require_once '../config/auth.php';
$auth->requireAdmin();

$functions = new CloudBoxFunctions();
$db = new Database();
$conn = $db->getConnection();

$plans = $conn->query("SELECT * FROM storage_plans ORDER BY price")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Plans - Admin CloudBox</title>
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
            <a href="storage_plans.php" class="nav-item active"><i class="fas fa-layer-group"></i> Storage Plans</a>
            <a href="transactions.php" class="nav-item"><i class="fas fa-money-bill-wave"></i> Transactions</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cog"></i> Settings</a>
            <a href="../auth/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="fas fa-bars"></i>
            </button>
            <h2>Kelola Paket Storage</h2>
        </header>
        <div class="admin-content">
            <button onclick="openPlanForm()" class="btn-primary" style="margin-bottom:20px;">
                <i class="fas fa-plus"></i> Tambah Paket
            </button>
            <div class="table-card">
                <table class="admin-table">
                    <thead>
                        <tr><th>Paket</th><th>Ukuran</th><th>Harga</th><th>Durasi</th><th>Aktif</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach($plans as $p): ?>
                        <tr>
                            <td><?= $p['plan_name'] ?></td>
                            <td><?= $functions->formatSize($p['storage_size']) ?></td>
                            <td><?= $functions->formatCurrency($p['price']) ?></td>
                            <td><?= $p['duration_days'] ?> hari</td>
                            <td><span class="status-badge <?= $p['is_active']?'status-active':'status-inactive' ?>"><?= $p['is_active']?'Ya':'Tidak' ?></span></td>
                            <td>
                                <button class="btn-icon" onclick="editPlan(<?= $p['id'] ?>, '<?= htmlspecialchars($p['plan_name'], ENT_QUOTES) ?>', <?= $p['storage_size'] ?>, <?= $p['price'] ?>, <?= $p['duration_days'] ?>, <?= $p['is_active'] ?>)"><i class="fas fa-edit"></i></button>
                                <button class="btn-icon" onclick="deletePlan(<?= $p['id'] ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal Tambah/Edit Plan -->
<div id="planModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Tambah Paket</h2>
            <span class="close" onclick="closePlanModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="planForm">
                <input type="hidden" id="planId">
                <div class="form-group">
                    <label>Nama Paket</label>
                    <input type="text" id="planName" required>
                </div>
                <div class="form-group">
                    <label>Ukuran Storage (bytes)</label>
                    <input type="number" id="planSize" required>
                </div>
                <div class="form-group">
                    <label>Harga (Rp)</label>
                    <input type="number" id="planPrice" required>
                </div>
                <div class="form-group">
                    <label>Durasi (hari)</label>
                    <input type="number" id="planDuration" required>
                </div>
                <div class="form-group">
                    <label>Aktif</label>
                    <select id="planActive">
                        <option value="1">Ya</option>
                        <option value="0">Tidak</option>
                    </select>
                </div>
                <button type="submit" class="btn-auth"><i class="fas fa-save"></i> Simpan</button>
            </form>
        </div>
    </div>
</div>

<script>
// Buka modal untuk tambah
function openPlanForm() {
    document.getElementById('planId').value = '';
    document.getElementById('planName').value = '';
    document.getElementById('planSize').value = '';
    document.getElementById('planPrice').value = '';
    document.getElementById('planDuration').value = 30;
    document.getElementById('planActive').value = 1;
    document.getElementById('modalTitle').textContent = 'Tambah Paket';
    document.getElementById('planModal').classList.add('show');
    document.getElementById('planModal').style.display = 'flex';
}

// Buka modal untuk edit
function editPlan(id, name, size, price, duration, active) {
    document.getElementById('planId').value = id;
    document.getElementById('planName').value = name;
    document.getElementById('planSize').value = size;
    document.getElementById('planPrice').value = price;
    document.getElementById('planDuration').value = duration;
    document.getElementById('planActive').value = active;
    document.getElementById('modalTitle').textContent = 'Edit Paket';
    document.getElementById('planModal').classList.add('show');
    document.getElementById('planModal').style.display = 'flex';
}

function closePlanModal() {
    document.getElementById('planModal').classList.remove('show');
    setTimeout(() => { document.getElementById('planModal').style.display = 'none'; }, 300);
}

// Submit form
document.getElementById('planForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('planId').value;
    const name = document.getElementById('planName').value;
    const size = document.getElementById('planSize').value;
    const price = document.getElementById('planPrice').value;
    const duration = document.getElementById('planDuration').value;
    const active = document.getElementById('planActive').value;
    
    const action = id ? 'updatePlan' : 'addPlan';
    const body = id ? { plan_id: id, plan_name: name, storage_size: size, price: price, duration_days: duration, is_active: active }
                     : { plan_name: name, storage_size: size, price: price, duration_days: duration, is_active: active };
    
    fetch('../api/admin_api.php?action=' + action, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            alert(d.message);
            location.reload();
        } else {
            alert('Gagal: ' + d.message);
        }
    });
});

// Hapus paket
function deletePlan(id) {
    if(confirm('Hapus paket ini?')) {
        fetch('../api/admin_api.php?action=deletePlan', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({plan_id: id})
        })
        .then(r => r.json())
        .then(d => {
            if(d.success) {
                alert('Paket dihapus');
                location.reload();
            } else {
                alert('Gagal menghapus: ' + d.message);
            }
        });
    }
}
</script>
</body>
</html>