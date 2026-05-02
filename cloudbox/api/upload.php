<?php
require_once '../config/auth.php';
$auth->requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$functions = new CloudBoxFunctions();
$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit();
}

$file = $_FILES['file'];

// Check upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload failed']);
    exit();
}

// Check storage quota
if (!$functions->checkStorageQuota($user_id, $file['size'])) {
    echo json_encode(['success' => false, 'message' => 'Storage quota exceeded. Please upgrade your plan.']);
    exit();
}

// Check file type
$file_type = mime_content_type($file['tmp_name']);
if (!in_array($file_type, $GLOBALS['allowed_types'])) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed']);
    exit();
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$unique_name = uniqid() . '_' . time() . '.' . $extension;
$upload_path = UPLOAD_DIR . $user_id . '/';

// Create user directory if not exists
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0777, true);
}

// Move file
if (move_uploaded_file($file['tmp_name'], $upload_path . $unique_name)) {
    // Save to database
    $sql = "INSERT INTO files (user_id, filename, original_name, file_size, file_type, file_extension) 
            VALUES (:user_id, :filename, :original_name, :file_size, :file_type, :extension)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':filename' => $unique_name,
        ':original_name' => $file['name'],
        ':file_size' => $file['size'],
        ':file_type' => $file_type,
        ':extension' => $extension
    ]);
    
    // Update storage used
    $functions->updateStorageUsed($user_id);
    
    // Log activity
    $functions->logActivity($user_id, 'upload', "Uploaded file: {$file['name']}");
    
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'file_id' => $conn->lastInsertId()
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}
?>