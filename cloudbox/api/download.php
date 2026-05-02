<?php
require_once '../config/auth.php';
$auth->requireLogin();
$user_id = $_SESSION['user_id'];
$file_id = $_GET['id'] ?? 0;

$db = new Database();
$conn = $db->getConnection();
$file = $conn->query("SELECT * FROM files WHERE id = $file_id AND user_id = $user_id")->fetch();

if (!$file) {
    // Check if shared link
    $file = $conn->query("SELECT * FROM files WHERE id = $file_id AND is_public = 1")->fetch();
    if (!$file) { http_response_code(404); exit("File tidak ditemukan."); }
}

$filepath = UPLOAD_DIR . $user_id . '/' . $file['filename'];
if (!file_exists($filepath)) {
    // fallback cari di root uploads
    $filepath = UPLOAD_DIR . $file['filename'];
    if (!file_exists($filepath)) { http_response_code(404); exit("File tidak ada di server."); }
}

// Update download count
$conn->exec("UPDATE files SET download_count = download_count + 1 WHERE id = $file_id");

header('Content-Type: ' . $file['file_type']);
header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit();
?>