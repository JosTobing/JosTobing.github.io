<?php
require_once '../config/auth.php';
$auth->requireAdmin();
header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();
$functions = new CloudBoxFunctions();

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'stats':
        $customers = $conn->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
        $files = $conn->query("SELECT COUNT(*) FROM files")->fetchColumn();
        $storage = $conn->query("SELECT COALESCE(SUM(file_size),0) FROM files")->fetchColumn();
        $revenue = $conn->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE payment_status='paid'")->fetchColumn();
        echo json_encode([
            'customers' => $customers,
            'files' => $files,
            'storage' => $functions->formatSize($storage),
            'revenue' => $functions->formatCurrency($revenue)
        ]);
        break;

    case 'toggleStatus':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$data['status'], $data['user_id']]);
        echo json_encode(['success' => true]);
        break;

    // KONFIRMASI PEMBAYARAN (storage bertambah)
    case 'confirmPayment':
        $data = json_decode(file_get_contents('php://input'), true);
        $tid = $data['transaction_id'] ?? 0;
        
        $stmt = $conn->prepare("SELECT t.*, sp.duration_days FROM transactions t JOIN storage_plans sp ON t.plan_id = sp.id WHERE t.id = ?");
        $stmt->execute([$tid]);
        $trx = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trx && $trx['payment_status'] == 'pending') {
            try {
                $conn->beginTransaction();
                
                // Update transaksi
                $upd = $conn->prepare("UPDATE transactions SET payment_status='paid', status='active', start_date=NOW(), end_date=DATE_ADD(NOW(), INTERVAL ? DAY) WHERE id=?");
                $upd->execute([$trx['duration_days'], $tid]);
                
                // Tambah kuota user
                $updUser = $conn->prepare("UPDATE users SET storage_quota = storage_quota + ? WHERE id=?");
                $updUser->execute([$trx['storage_size'], $trx['user_id']]);
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Pembayaran dikonfirmasi, kuota bertambah.']);
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Transaksi tidak valid atau sudah diproses.']);
        }
        break;

    // CUSTOMER
    case 'updateCustomer':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, storage_quota=? WHERE id=?");
        $stmt->execute([$data['full_name'], $data['email'], $data['storage_quota'], $data['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Customer diperbarui']);
        break;

    // STORAGE PLANS
    case 'addPlan':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("INSERT INTO storage_plans (plan_name, storage_size, price, duration_days, is_active) VALUES (?,?,?,?,?)");
        $stmt->execute([$data['plan_name'], $data['storage_size'], $data['price'], $data['duration_days'], $data['is_active']]);
        echo json_encode(['success' => true, 'message' => 'Paket ditambahkan']);
        break;

    case 'updatePlan':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("UPDATE storage_plans SET plan_name=?, storage_size=?, price=?, duration_days=?, is_active=? WHERE id=?");
        $stmt->execute([$data['plan_name'], $data['storage_size'], $data['price'], $data['duration_days'], $data['is_active'], $data['plan_id']]);
        echo json_encode(['success' => true, 'message' => 'Paket diperbarui']);
        break;

    case 'deletePlan':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("DELETE FROM storage_plans WHERE id=?");
        $stmt->execute([$data['plan_id']]);
        echo json_encode(['success' => true, 'message' => 'Paket dihapus']);
        break;

    // TAMBAH KUOTA MANUAL
    case 'addQuota':
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? 0;
        $additional = $data['additional_quota'] ?? 0;
        if ($userId > 0 && $additional > 0) {
            $stmt = $conn->prepare("UPDATE users SET storage_quota = storage_quota + ? WHERE id = ?");
            $stmt->execute([$additional, $userId]);
            $newQuota = $conn->query("SELECT storage_quota FROM users WHERE id = $userId")->fetchColumn();
            echo json_encode(['success' => true, 'new_quota' => $newQuota, 'new_quota_formatted' => $functions->formatSize($newQuota)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
        }
        break;

    // HAPUS CUSTOMER
    case 'deleteCustomer':
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'];
        // Hapus file fisik
        $files = $conn->query("SELECT filename FROM files WHERE user_id = $userId")->fetchAll();
        foreach ($files as $f) {
            $path = UPLOAD_DIR . $userId . '/' . $f['filename'];
            if (file_exists($path)) unlink($path);
        }
        $userDir = UPLOAD_DIR . $userId;
        if (is_dir($userDir)) rmdir($userDir);
        // Hapus dari database
        $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
        echo json_encode(['success' => true, 'message' => 'Customer dan semua data dihapus']);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}