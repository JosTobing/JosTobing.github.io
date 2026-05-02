<?php
require_once '../config/auth.php';
$auth->requireAdmin();

$functions = new CloudBoxFunctions();
$db = new Database();
$conn = $db->getConnection();

// ==================== STATISTIK ====================
$total_customers = $conn->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$total_files = $conn->query("SELECT COUNT(*) FROM files")->fetchColumn();
$total_storage = $conn->query("SELECT COALESCE(SUM(file_size), 0) FROM files")->fetchColumn();
$total_revenue = $conn->query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE payment_status='paid'")->fetchColumn();
$pending_transactions = $conn->query("SELECT COUNT(*) FROM transactions WHERE payment_status='pending'")->fetchColumn();

// ==================== DATA GRAFIK ====================
$months = [];
$storageData = [];
$revenueData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime($month));
    
    // Storage per bulan
    $stmt = $conn->prepare("SELECT COALESCE(SUM(file_size), 0) FROM files WHERE DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $storageData[] = round($stmt->fetchColumn() / (1024 * 1024 * 1024), 2); // GB
    
    // Revenue per bulan (paid)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE payment_status='paid' AND DATE_FORMAT(created_at, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $revenueData[] = (int)$stmt->fetchColumn();
}

// ==================== TRANSAKSI TERBARU ====================
$recent_transactions = $conn->query("
    SELECT t.*, u.username, u.full_name, sp.plan_name 
    FROM transactions t 
    JOIN users u ON t.user_id = u.id 
    JOIN storage_plans sp ON t.plan_id = sp.id 
    ORDER BY t.created_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CloudBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Tambahan untuk stat card yang bisa diklik */
        .stat-card {
            text-decoration: none;
            color: inherit;
            display: flex;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-cloud"></i>
                <h2>CloudBox Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
                <a href="customers.php" class="nav-item">
                    <i class="fas fa-users"></i> Customers
                </a>
                <a href="storage_plans.php" class="nav-item">
                    <i class="fas fa-layer-group"></i> Storage Plans
                </a>
                <a href="transactions.php" class="nav-item">
                    <i class="fas fa-money-bill-wave"></i> Transactions
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="../auth/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <header class="admin-header">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header-search">
                    <form id="searchForm" style="display:flex; width:100%;">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Cari transaksi..." id="searchInput">
                    </form>
                </div>
                <div class="header-actions">
                    <div class="notification-bell">
                        <i class="fas fa-bell"></i>
                        <?php if ($pending_transactions > 0): ?>
                            <span class="badge"><?= $pending_transactions ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="admin-profile">
                        <img src="../assets/images/default.png" alt="Admin">
                        <span><?= $_SESSION['full_name'] ?></span>
                    </div>
                </div>
            </header>
            
            <div class="admin-content">
                <h1 class="page-title">Dashboard Overview</h1>
                
                <!-- Stats Cards (sekarang bisa diklik) -->
                <div class="stats-grid">
                    <a href="customers.php" class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= number_format($total_customers) ?></h3>
                            <p>Total Customers</p>
                        </div>
                    </a>
                    
                    <a href="customers.php" class="stat-card">
                        <div class="stat-icon" style="background: #27ae60;">
                            <i class="fas fa-file"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= number_format($total_files) ?></h3>
                            <p>Total Files</p>
                        </div>
                    </a>
                    
                    <a href="storage_plans.php" class="stat-card">
                        <div class="stat-icon" style="background: #f39c12;">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $functions->formatSize($total_storage) ?></h3>
                            <p>Total Storage Used</p>
                        </div>
                    </a>
                    
                    <a href="transactions.php" class="stat-card">
                        <div class="stat-icon" style="background: #e74c3c;">
                            <i class="fas fa-money-bill"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?= $functions->formatCurrency($total_revenue) ?></h3>
                            <p>Total Revenue</p>
                        </div>
                    </a>
                </div>
                
                <!-- Charts -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <h3>Storage Usage Over Time (GB)</h3>
                        <canvas id="storageChart"></canvas>
                    </div>
                    <div class="chart-card">
                        <h3>Revenue Overview (Rp)</h3>
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <div class="table-card">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h3>Recent Transactions</h3>
                        <a href="transactions.php" class="btn-secondary" style="text-decoration:none;">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Transaction Code</th>
                                    <th>Customer</th>
                                    <th>Plan</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_transactions as $trx): ?>
                                <tr>
                                    <td><?= $trx['transaction_code'] ?></td>
                                    <td><a href="customers.php?search=<?= urlencode($trx['full_name']) ?>"><?= htmlspecialchars($trx['full_name']) ?></a></td>
                                    <td><?= $trx['plan_name'] ?></td>
                                    <td><?= $functions->formatCurrency($trx['amount']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $trx['payment_status'] ?>">
                                            <?= ucfirst($trx['payment_status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($trx['created_at'])) ?></td>
                                    <td>
                                        <button class="btn-icon view-detail" 
                                            data-code="<?= $trx['transaction_code'] ?>"
                                            data-customer="<?= htmlspecialchars($trx['full_name']) ?>"
                                            data-plan="<?= $trx['plan_name'] ?>"
                                            data-amount="<?= $functions->formatCurrency($trx['amount']) ?>"
                                            data-status="<?= $trx['payment_status'] ?>"
                                            data-date="<?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?>"
                                            title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="transactions.php?search=<?= $trx['transaction_code'] ?>" class="btn-icon" title="Cari transaksi">
                                            <i class="fas fa-search"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modal Detail Transaksi (tetap sama) -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detail Transaksi</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <table class="admin-table">
                    <tr><th>Kode</th><td id="modalCode"></td></tr>
                    <tr><th>Customer</th><td id="modalCustomer"></td></tr>
                    <tr><th>Paket</th><td id="modalPlan"></td></tr>
                    <tr><th>Jumlah</th><td id="modalAmount"></td></tr>
                    <tr><th>Status Bayar</th><td id="modalStatus"></td></tr>
                    <tr><th>Tanggal</th><td id="modalDate"></td></tr>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // Search form
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                window.location.href = 'transactions.php?search=' + encodeURIComponent(query);
            }
        });

        // Modal Detail
        function closeModal() {
            document.getElementById('detailModal').classList.remove('show');
            setTimeout(() => {
                document.getElementById('detailModal').style.display = 'none';
            }, 300);
        }

        document.querySelectorAll('.view-detail').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modalCode').textContent = this.dataset.code;
                document.getElementById('modalCustomer').textContent = this.dataset.customer;
                document.getElementById('modalPlan').textContent = this.dataset.plan;
                document.getElementById('modalAmount').textContent = this.dataset.amount;
                document.getElementById('modalStatus').innerHTML = `<span class="status-badge status-${this.dataset.status}">${this.dataset.status}</span>`;
                document.getElementById('modalDate').textContent = this.dataset.date;
                
                const modal = document.getElementById('detailModal');
                modal.classList.add('show');
                modal.style.display = 'flex';
            });
        });

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('detailModal');
            if (e.target === modal) closeModal();
        });

        // Grafik
        document.addEventListener('DOMContentLoaded', function() {
            const storageCtx = document.getElementById('storageChart').getContext('2d');
            new Chart(storageCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($months) ?>,
                    datasets: [{
                        label: 'Storage Used (GB)',
                        data: <?= json_encode($storageData) ?>,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
            
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($months) ?>,
                    datasets: [{
                        label: 'Revenue (Rp)',
                        data: <?= json_encode($revenueData) ?>,
                        backgroundColor: '#27ae60'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        });
    </script>
</body>
</html>