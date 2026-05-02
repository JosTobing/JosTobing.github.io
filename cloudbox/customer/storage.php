<?php
require_once '../config/auth.php';
$auth->requireLogin();
if ($auth->isAdmin()) { header('Location: ../admin/index.php'); exit(); }

$functions = new CloudBoxFunctions();
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch();
$active_plan = $conn->query("
    SELECT t.*, sp.plan_name, sp.storage_size FROM transactions t 
    JOIN storage_plans sp ON t.plan_id = sp.id 
    WHERE t.user_id = $user_id AND t.status='active' 
    ORDER BY t.end_date DESC LIMIT 1
")->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Saya - CloudBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="customer-layout">
    <?php include 'sidebar.php'; ?>
    <main class="customer-main">
        <div class="page-header"><h1>Detail Penyimpanan</h1></div>
        <div class="storage-card" style="max-width:500px; margin:20px auto;">
            <h3>Paket: <?= $active_plan ? $active_plan['plan_name'] : 'Gratis (100MB)' ?></h3>
            <div><strong>Kuota:</strong> <?= $functions->formatSize($user['storage_quota']) ?></div>
            <div><strong>Terpakai:</strong> <?= $functions->formatSize($user['storage_used']) ?></div>
            <div><strong>Sisa:</strong> <?= $functions->formatSize(max(0, $user['storage_quota'] - $user['storage_used'])) ?></div>
            <div class="progress-bar" style="margin-top:15px;">
                <div class="progress" style="width:<?= min(100, ($user['storage_used']/$user['storage_quota'])*100) ?>%"></div>
            </div>
            <a href="purchase.php" class="btn-upgrade" style="display:inline-block; margin-top:20px;">Upgrade Storage</a>
        </div>
    </main>
</div>
</body>
</html>