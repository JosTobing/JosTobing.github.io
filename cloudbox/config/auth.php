<?php
// config/auth.php
session_start();
require_once 'functions.php';

class Auth {
    private $conn;
    private $functions;
    
    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
        $this->functions = new CloudBoxFunctions();
    }
    
    // Login
    public function login($email, $password) {
        $sql = "SELECT * FROM users WHERE email = :email AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['storage_quota'] = $user['storage_quota'];
            
            $this->functions->logActivity($user['id'], 'login', 'User logged in');
            
            return ['success' => true, 'role' => $user['role']];
        }
        
        return ['success' => false, 'message' => 'Email atau password salah'];
    }
    
    // Register
    public function register($data) {
        // Check existing user
        $sql = "SELECT id FROM users WHERE email = :email OR username = :username";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':email' => $data['email'], ':username' => $data['username']]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email atau username sudah terdaftar'];
        }
        
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (username, email, password, full_name, phone) 
                VALUES (:username, :email, :password, :full_name, :phone)";
        $stmt = $this->conn->prepare($sql);
        
        try {
            $stmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':password' => $hashed_password,
                ':full_name' => $data['full_name'],
                ':phone' => $data['phone'] ?? null
            ]);
            
            $user_id = $this->conn->lastInsertId();
            $this->functions->logActivity($user_id, 'register', 'New user registered');
            $this->functions->addNotification($user_id, 'Selamat Datang!', 'Selamat datang di CloudBox. Anda mendapatkan 100MB storage gratis.', 'success');
            
            return ['success' => true, 'message' => 'Registrasi berhasil! Silakan login.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registrasi gagal: ' . $e->getMessage()];
        }
    }
    
    // Check if logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Check if admin
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    // Check if customer
    public function isCustomer() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
    }
    
    // Require login
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'auth/login.php');
            exit();
        }
    }
    
    // Require admin
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: ' . BASE_URL . 'customer/index.php');
            exit();
        }
    }
    
    // Logout
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->functions->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit();
    }
}

$auth = new Auth();
?>