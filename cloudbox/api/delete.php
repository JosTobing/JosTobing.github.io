<?php
require_once '../config/auth.php';
$auth->requireLogin();
header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();
$functions = new CloudBoxFunctions();
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$file_id = $data['file_id'] ?? 0;

$file = $conn->query("SELECT * FROM files WHERE id = $file_id AND user_id = $user_id")->fetch();
if (!$file) {
    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan.']);
    exit;
}

$filepath = UPLOAD_DIR . $user_id . '/' . $file['filename'];
if (file_exists($filepath)) unlink($filepath);
else {
    $filepath = UPLOAD_DIR . $file['filename'];
    if (file_exists($filepath)) unlink($filepath);
}

$conn->prepare("DELETE FROM files WHERE id = ?")->execute([$file_id]);
$functions->updateStorageUsed($user_id);
$functions->logActivity($user_id, 'delete', "Deleted file: {$file['original_name']}");

echo json_encode(['success' => true, 'message' => 'File dihapus.']);
?>