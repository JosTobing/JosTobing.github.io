<?php
require_once '../config/auth.php';
$auth->requireLogin();

$functions = new CloudBoxFunctions();
$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];

// Get available plans
$plans = $conn->query("SELECT * FROM storage_plans WHERE is_active = 1 ORDER BY price")->fetchAll(PDO::FETCH_ASSOC);

// Get payment methods
$payment_methods = [
    ['id' => 'bca', 'name' => 'Bank BCA', 'account' => '1234567890', 'holder' => 'CloudBox Indonesia'],
    ['id' => 'mandiri', 'name' => 'Bank Mandiri', 'account' => '0987654321', 'holder' => 'CloudBox Indonesia'],
    ['id' => 'bni', 'name' => 'Bank BNI', 'account' => '1122334455', 'holder' => 'CloudBox Indonesia'],
    ['id' => 'gopay', 'name' => 'GoPay', 'account' => '08123456789', 'holder' => 'CloudBox Indonesia'],
    ['id' => 'ovo', 'name' => 'OVO', 'account' => '08123456789', 'holder' => 'CloudBox Indonesia']
];

// Handle purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan_id = $_POST['plan_id'];
    $payment_method = $_POST['payment_method'];
    
    // Get plan details
    $plan = $conn->query("SELECT * FROM storage_plans WHERE id = $plan_id")->fetch(PDO::FETCH_ASSOC);
    
    if ($plan) {
        $transaction_code = $functions->generateTransactionCode();
        
        $sql = "INSERT INTO transactions (user_id, plan_id, transaction_code, amount, storage_size, payment_method) 
                VALUES (:user_id, :plan_id, :transaction_code, :amount, :storage_size, :payment_method)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':plan_id' => $plan_id,
            ':transaction_code' => $transaction_code,
            ':amount' => $plan['price'],
            ':storage_size' => $plan['storage_size'],
            ':payment_method' => $payment_method
        ]);
        
        $transaction_id = $conn->lastInsertId();
        
        // Handle payment proof upload
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/payments/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $filename = 'payment_' . $transaction_id . '_' . time() . '.jpg';
            move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_dir . $filename);
            
            $conn->query("UPDATE transactions SET payment_proof = '$filename' WHERE id = $transaction_id");
        }
        
        $success_message = "Pembelian berhasil! Silakan lakukan pembayaran dan upload bukti pembayaran.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Storage - CloudBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="customer-layout">
        <!-- Sidebar sama seperti sebelumnya -->
        <aside class="customer-sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-cloud"></i>
                <h2>CloudBox</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
                <a href="files.php" class="nav-item">
                    <i class="fas fa-folder"></i> My Files
                </a>
                <a href="storage.php" class="nav-item">
                    <i class="fas fa-database"></i> Storage
                </a>
                <a href="purchase.php" class="nav-item active">
                    <i class="fas fa-shopping-cart"></i> Buy Storage
                </a>
                <a href="profile.php" class="nav-item">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="../auth/logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </aside>
        
        <main class="customer-main">
            <div class="page-header">
                <h1>Buy Storage Plan</h1>
                <p>Pilih paket storage yang sesuai dengan kebutuhan Anda</p>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Storage Plans -->
            <div class="plans-grid">
                <?php foreach ($plans as $plan): 
                    $features = json_decode($plan['features'], true);
                ?>
                <div class="plan-card <?php echo $plan['plan_name'] === 'Standard' ? 'featured' : ''; ?>">
                    <?php if ($plan['plan_name'] === 'Standard'): ?>
                        <div class="plan-badge">Popular</div>
                    <?php endif; ?>
                    
                    <div class="plan-header">
                        <h3><?php echo $plan['plan_name']; ?></h3>
                        <div class="plan-price">
                            <span class="currency">Rp</span>
                            <span class="amount"><?php echo number_format($plan['price'], 0, ',', '.'); ?></span>
                            <span class="period">/ <?php echo $plan['duration_days']; ?> hari</span>
                        </div>
                        <p class="plan-storage">
                            <i class="fas fa-database"></i>
                            <?php echo $functions->formatSize($plan['storage_size']); ?>
                        </p>
                    </div>
                    
                    <div class="plan-features">
                        <?php foreach ($features as $feature): ?>
                        <div class="feature-item">
                            <i class="fas fa-check"></i>
                            <span><?php echo $feature; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button class="btn-purchase" onclick="openPaymentModal(<?php echo $plan['id']; ?>, '<?php echo $plan['plan_name']; ?>', <?php echo $plan['price']; ?>)">
                        <i class="fas fa-shopping-cart"></i> Pilih Paket
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Payment Methods Info -->
            <div class="payment-info">
                <h3>Metode Pembayaran</h3>
                <div class="payment-methods">
                    <?php foreach ($payment_methods as $method): ?>
                    <div class="payment-method-card">
                        <i class="fas fa-<?php echo strpos($method['id'], 'bank') !== false || in_array($method['id'], ['bca', 'mandiri', 'bni']) ? 'university' : 'mobile-alt'; ?>"></i>
                        <div>
                            <h4><?php echo $method['name']; ?></h4>
                            <p><?php echo $method['account']; ?> - <?php echo $method['holder']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Konfirmasi Pembelian</h2>
                <span class="close" onclick="closePaymentModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="paymentForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="plan_id" id="planId">
                    
                    <div class="purchase-summary">
                        <h4 id="planNameDisplay"></h4>
                        <p class="plan-price-display" id="planPriceDisplay"></p>
                    </div>
                    
                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="payment_method" required>
                            <option value="">Pilih metode pembayaran</option>
                            <option value="bca">Bank BCA</option>
                            <option value="mandiri">Bank Mandiri</option>
                            <option value="bni">Bank BNI</option>
                            <option value="gopay">GoPay</option>
                            <option value="ovo">OVO</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Bukti Pembayaran</label>
                        <div class="file-upload-area" onclick="document.getElementById('paymentProof').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik untuk upload bukti pembayaran</p>
                            <input type="file" name="payment_proof" id="paymentProof" accept="image/*" style="display: none;">
                        </div>
                        <div id="filePreview" class="file-preview"></div>
                    </div>
                    
                    <button type="submit" class="btn-confirm">
                        <i class="fas fa-check"></i> Konfirmasi Pembelian
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/payment.js"></script>
</body>
</html>