<?php
// customer/sidebar.php
if (!isset($active_page)) {
    $active_page = basename($_SERVER['PHP_SELF'], '.php');
}
?>
<aside class="customer-sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-cloud"></i>
        <h2>CloudBox</h2>
    </div>

    <div class="user-profile-mini">
        <img src="../assets/images/default.png" alt="Profile">
        <div class="user-details">
            <h4><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></h4>
            <span class="user-plan">Customer</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?= $active_page == 'index' ? 'active' : '' ?>">
            <i class="fas fa-dashboard"></i> Dashboard
        </a>
        <a href="files.php" class="nav-item <?= $active_page == 'files' ? 'active' : '' ?>">
            <i class="fas fa-folder"></i> My Files
        </a>
        <a href="storage.php" class="nav-item <?= $active_page == 'storage' ? 'active' : '' ?>">
            <i class="fas fa-database"></i> Storage
        </a>
        <a href="purchase.php" class="nav-item <?= $active_page == 'purchase' ? 'active' : '' ?>">
            <i class="fas fa-shopping-cart"></i> Buy Storage
        </a>
        <a href="profile.php" class="nav-item <?= $active_page == 'profile' ? 'active' : '' ?>">
            <i class="fas fa-user"></i> Profile
        </a>
        <a href="../auth/logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>