<?php
require_once '../config/auth.php';
$auth->requireLogin();
header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();
$functions = new CloudBoxFunctions();
$user_id = $_SESSION['user_id'];

// API untuk memproses pembelian (hanya catat transaksi)
$data = json_decode(file_get_contents('php://input'), true);
$plan_id = $data['plan_id'] ?? 0;
$payment_method = $data['payment_method'] ?? '';

$plan = $conn->query("SELECT * FROM storage_plans WHERE id = $plan_id AND is_active = 1")->fetch();
if (!$plan) {
    echo json_encode(['success' => false, 'message' => 'Paket tidak tersedia.']);
    exit;
}

$code = $functions->generateTransactionCode();
$conn->prepare("INSERT INTO transactions (user_id, plan_id, transaction_code, amount, storage_size, payment_method) 
                VALUES (?, ?, ?, ?, ?, ?)")
     ->execute([$user_id, $plan_id, $code, $plan['price'], $plan['storage_size'], $payment_method]);

$functions->logActivity($user_id, 'purchase', "Purchased plan: {$plan['plan_name']}");

echo json_encode(['success' => true, 'message' => 'Transaksi dibuat, silakan lakukan pembayaran.', 'code' => $code]);
?>