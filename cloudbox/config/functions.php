<?php
// config/functions.php
require_once 'database.php';

class CloudBoxFunctions {
    private $conn;
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    // Format file size
    public function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    // Format currency
    public function formatCurrency($amount) {
        return CURRENCY_SYMBOL . ' ' . number_format($amount, 0, ',', '.');
    }
    
    // Get file icon
    public function getFileIcon($file_type, $extension = '') {
        $icons = [
            'image' => 'fa-file-image',
            'pdf' => 'fa-file-pdf',
            'word' => 'fa-file-word',
            'excel' => 'fa-file-excel',
            'powerpoint' => 'fa-file-powerpoint',
            'archive' => 'fa-file-archive',
            'text' => 'fa-file-alt',
            'audio' => 'fa-file-audio',
            'video' => 'fa-file-video',
            'code' => 'fa-file-code',
            'default' => 'fa-file'
        ];
        
        $colors = [
            'image' => '#3498db',
            'pdf' => '#e74c3c',
            'word' => '#2b579a',
            'excel' => '#217346',
            'powerpoint' => '#d24726',
            'archive' => '#f39c12',
            'text' => '#95a5a6',
            'audio' => '#9b59b6',
            'video' => '#e67e22',
            'code' => '#27ae60',
            'default' => '#7f8c8d'
        ];
        
        foreach ($icons as $key => $icon) {
            if (strpos($file_type, $key) !== false) {
                return ['icon' => $icon, 'color' => $colors[$key]];
            }
        }
        
        return ['icon' => $icons['default'], 'color' => $colors['default']];
    }
    
    // Calculate storage percentage
    public function storagePercentage($used, $total) {
        if ($total == 0) return 0;
        return min(($used / $total) * 100, 100);
    }
    
    // Generate transaction code
    public function generateTransactionCode() {
        return 'CBX-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
    
    // Log activity (AMAN)
    public function logActivity($user_id, $action, $description = '') {
        // Cek apakah user masih ada di database
        $checkSql = "SELECT COUNT(*) FROM users WHERE id = :user_id";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->execute([':user_id' => $user_id]);
        
        if ($checkStmt->fetchColumn() == 0) {
            // User sudah tidak ada, jangan insert log
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                VALUES (:user_id, :action, :description, :ip)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':action' => $action,
            ':description' => $description,
            ':ip' => $ip
        ]);
    }
    
    // Add notification
    public function addNotification($user_id, $title, $message, $type = 'info') {
        $sql = "INSERT INTO notifications (user_id, title, message, type) 
                VALUES (:user_id, :title, :message, :type)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':user_id' => $user_id,
            ':title' => $title,
            ':message' => $message,
            ':type' => $type
        ]);
    }
    
    // Check storage quota
    public function checkStorageQuota($user_id, $new_file_size = 0) {
        $sql = "SELECT storage_used, storage_quota FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($user['storage_used'] + $new_file_size) <= $user['storage_quota'];
    }
    
    // Update storage used
    public function updateStorageUsed($user_id) {
        $sql = "UPDATE users SET storage_used = (
            SELECT COALESCE(SUM(file_size), 0) FROM files WHERE user_id = :user_id
        ) WHERE id = :user_id2";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $user_id, ':user_id2' => $user_id]);
    }
}
?>