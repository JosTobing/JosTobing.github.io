<?php
require_once '../config/auth.php';
$auth->requireAdmin();

$functions = new CloudBoxFunctions();
$db = new Database();
$conn = $db->getConnection();

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM users WHERE role='customer'";
if ($search) {
    $sql .= " AND (username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
}
$sql .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($search) {
    $stmt->bindValue(':search', "%$search%");
}
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Customers - Admin CloudBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="admin-layout">
    <aside class="admin-sidebar" id="sidebar">
        <!-- sidebar sama seperti sebelumnya -->
        <div class="sidebar-header">
            <i class="fas fa-cloud"></i><h2>CloudBox Admin</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item"><i class="fas fa-dashboard"></i> Dashboard</a>
            <a href="customers.php" class="nav-item active"><i class="fas fa-users"></i> Customers</a>
            <a href="storage_plans.php" class="nav-item"><i class="fas fa-layer-group"></i> Storage Plans</a>
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
            <div class="header-search">
                <form method="GET" style="display:flex; width:100%;">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Cari customer..." value="<?= htmlspecialchars($search) ?>">
                </form>
            </div>
        </header>
        <div class="admin-content">
            <h1 class="page-title">Data Customers</h1>
            <div class="table-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nama</th><th>Email</th><th>Username</th><th>Storage Terpakai</th><th>Kuota</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($customers as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['full_name']) ?></td>
                            <td><?= htmlspecialchars($c['email']) ?></td>
                            <td><?= htmlspecialchars($c['username']) ?></td>
                            <td><?= $functions->formatSize($c['storage_used']) ?></td>
                            <td><?= $functions->formatSize($c['storage_quota']) ?></td>
                            <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                            <td>
                                <button class="btn-icon" onclick="editCustomer(<?= $c['id'] ?>, '<?= htmlspecialchars($c['full_name'], ENT_QUOTES) ?>', '<?= $c['email'] ?>', <?= $c['storage_quota'] ?>)"><i class="fas fa-edit"></i></button>
                                <button class="btn-icon" onclick="toggleStatus(<?= $c['id'] ?>, '<?= $c['status'] ?>')"><i class="fas fa-ban"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Modal Edit Customer -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Customer</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editForm">
                <input type="hidden" id="editUserId">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" id="editFullName" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="editEmail" required>
                </div>
                <div class="form-group">
                    <label>Kuota Storage (bytes)</label>
                    <input type="number" id="editQuota" required>
                </div>
                <button type="submit" class="btn-auth"><i class="fas fa-save"></i> Simpan</button>
            </form>
        </div>
    </div>
</div>

<script>
// Fungsi buka modal edit
function editCustomer(id, name, email, quota) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editFullName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editQuota').value = quota;
    document.getElementById('editModal').classList.add('show');
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').classList.remove('show');
    setTimeout(() => { document.getElementById('editModal').style.display = 'none'; }, 300);
}

// Submit edit
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('editUserId').value;
    const fullName = document.getElementById('editFullName').value;
    const email = document.getElementById('editEmail').value;
    const quota = document.getElementById('editQuota').value;
    
    fetch('../api/admin_api.php?action=updateCustomer', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({user_id: id, full_name: fullName, email: email, storage_quota: quota})
    })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            alert('Data berhasil diperbarui');
            location.reload();
        } else {
            alert('Gagal: ' + d.message);
        }
    });
});

// Toggle status
function toggleStatus(id, current) {
    const newStatus = current === 'active' ? 'suspended' : 'active';
    if (confirm(`Ubah status menjadi ${newStatus}?`)) {
        fetch('../api/admin_api.php?action=toggleStatus', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({user_id: id, status: newStatus})
        }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); });
    }
}
</script>
</body>
</html>