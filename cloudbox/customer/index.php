<?php
require_once '../config/auth.php';
$auth->requireLogin();

if ($auth->isAdmin()) {
    header('Location: ../admin/index.php');
    exit();
}

$functions = new CloudBoxFunctions();
$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];

// Get user data
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch(PDO::FETCH_ASSOC);

// Get files
$files = $conn->query("
    SELECT f.*, fo.folder_name 
    FROM files f 
    LEFT JOIN folders fo ON f.folder_id = fo.id 
    WHERE f.user_id = $user_id 
    ORDER BY f.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get folders
$folders = $conn->query("
    SELECT * FROM folders 
    WHERE user_id = $user_id AND parent_id IS NULL 
    ORDER BY folder_name
")->fetchAll(PDO::FETCH_ASSOC);

// Get notifications
$notifications = $conn->query("
    SELECT * FROM notifications 
    WHERE user_id = $user_id AND is_read = 0 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get active plan
$active_plan = $conn->query("
    SELECT t.*, sp.plan_name, sp.storage_size 
    FROM transactions t 
    JOIN storage_plans sp ON t.plan_id = sp.id 
    WHERE t.user_id = $user_id AND t.status = 'active' 
    ORDER BY t.end_date DESC 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CloudBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="customer-layout">
        <!-- Mobile Header -->
        <header class="mobile-header">
            <button class="menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="mobile-logo">
                <i class="fas fa-cloud"></i> CloudBox
            </div>
            <button class="notification-btn">
                <i class="fas fa-bell"></i>
                <?php if (count($notifications) > 0): ?>
                    <span class="badge"><?php echo count($notifications); ?></span>
                <?php endif; ?>
            </button>
        </header>
        
        <!-- Sidebar -->
        <aside class="customer-sidebar" id="sidebar">
            <div class="sidebar-header">
                <i class="fas fa-cloud"></i>
                <h2>CloudBox</h2>
            </div>
            
            <div class="user-profile-mini">
                <img src="../assets/images/default.png" alt="Profile">
                <div class="user-details">
                    <h4><?php echo $_SESSION['full_name']; ?></h4>
                    <span class="user-plan">
                        <?php echo $active_plan ? $active_plan['plan_name'] : 'Free'; ?>
                    </span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
                <a href="files.php" class="nav-item">
                    <i class="fas fa-folder"></i> My Files
                </a>
                <a href="storage.php" class="nav-item">
                    <i class="fas fa-database"></i> Storage
                </a>
                <a href="purchase.php" class="nav-item">
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
        
        <!-- Main Content -->
        <main class="customer-main">
            <div class="page-header">
                <h1>Welcome back, <?php echo $_SESSION['full_name']; ?>! 👋</h1>
                <p><?php echo date('l, d F Y'); ?></p>
            </div>
            
            <!-- Storage Overview -->
            <div class="storage-overview">
                <div class="storage-card">
                    <div class="storage-header">
                        <h3>Storage Usage</h3>
                        <span class="plan-badge">
                            <?php echo $active_plan ? $active_plan['plan_name'] : 'Free Plan'; ?>
                        </span>
                    </div>
                    
                    <?php 
                    $storage_percent = $functions->storagePercentage(
                        $user['storage_used'], 
                        $user['storage_quota']
                    );
                    ?>
                    
                    <div class="storage-visual">
                        <div class="progress-ring">
                            <svg viewBox="0 0 36 36">
                                <path d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                    fill="none" stroke="#eee" stroke-width="3"/>
                                <path d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"
                                    fill="none" 
                                    stroke="<?php echo $storage_percent > 90 ? '#e74c3c' : ($storage_percent > 70 ? '#f39c12' : '#27ae60'); ?>"
                                    stroke-width="3"
                                    stroke-dasharray="<?php echo $storage_percent; ?>, 100"/>
                            </svg>
                            <div class="progress-text">
                                <span class="percentage"><?php echo round($storage_percent); ?>%</span>
                                <span class="label">Used</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="storage-details">
                        <div class="detail-item">
                            <span class="detail-label">Used</span>
                            <span class="detail-value">
                                <?php echo $functions->formatSize($user['storage_used']); ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total</span>
                            <span class="detail-value">
                                <?php echo $functions->formatSize($user['storage_quota']); ?>
                            </span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Free</span>
                            <span class="detail-value">
                                <?php 
                                $free = $user['storage_quota'] - $user['storage_used'];
                                echo $functions->formatSize(max(0, $free));
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <a href="purchase.php" class="btn-upgrade">
                        <i class="fas fa-arrow-up"></i> Upgrade Storage
                    </a>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="files.php" class="action-card">
                            <i class="fas fa-upload"></i>
                            <span>Upload File</span>
                        </a>
                        <a href="files.php" class="action-card">
                            <i class="fas fa-folder-plus"></i>
                            <span>New Folder</span>
                        </a>
                        <a href="storage.php" class="action-card">
                            <i class="fas fa-chart-bar"></i>
                            <span>View Stats</span>
                        </a>
                        <a href="purchase.php" class="action-card">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Buy Storage</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Files -->
            <div class="recent-files">
                <div class="section-header">
                    <h3>Recent Files</h3>
                    <a href="files.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="files-grid">
                    <?php foreach (array_slice($files, 0, 8) as $file): 
                        $file_icon = $functions->getFileIcon($file['file_type']);
                    ?>
                    <div class="file-card" onclick="downloadFile(<?php echo $file['id']; ?>)">
                        <div class="file-icon" style="color: <?php echo $file_icon['color']; ?>">
                            <i class="fas <?php echo $file_icon['icon']; ?>"></i>
                        </div>
                        <div class="file-info">
                            <p class="file-name"><?php echo htmlspecialchars($file['original_name']); ?></p>
                            <p class="file-meta">
                                <?php echo $functions->formatSize($file['file_size']); ?> • 
                                <?php echo date('d/m/Y', strtotime($file['created_at'])); ?>
                            </p>
                        </div>
                        <div class="file-actions">
                            <button class="btn-icon" onclick="event.stopPropagation(); shareFile(<?php echo $file['id']; ?>)">
                                <i class="fas fa-share"></i>
                            </button>
                            <button class="btn-icon" onclick="event.stopPropagation(); deleteFile(<?php echo $file['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>
