<?php
// config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "cloudbox_db";
    private $username = "root";
    private $password = "";
    public $conn;
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            die();
        }
        
        return $this->conn;
    }
}

// Application constants
define('BASE_URL', 'http://localhost/cloudbox/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 104857600); // 100MB
define('APP_NAME', 'CloudBox');
define('APP_VERSION', '2.0');
define('CURRENCY', 'IDR');
define('CURRENCY_SYMBOL', 'Rp');

// Allowed file types
$GLOBALS['allowed_types'] = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    'application/pdf', 'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
    'text/plain', 'text/csv',
    'audio/mpeg', 'audio/wav', 'audio/ogg',
    'video/mp4', 'video/mpeg', 'video/quicktime',
    'application/json', 'application/xml', 'text/xml'
];
?>