<?php
require_once '../config/auth.php';
$auth->requireLogin();
if ($auth->isAdmin()) { header('Location: ../admin/index.php'); exit(); }

$functions = new CloudBoxFunctions();
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Handle new folder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folder_name'])) {
    $folder_name = trim($_POST['folder_name']);
    if ($folder_name) {
        $conn->prepare("INSERT INTO folders (user_id, folder_name) VALUES (?, ?)")->execute([$user_id, $folder_name]);
        header("Location: files.php");
        exit();
    }
}

$folders = $conn->query("SELECT * FROM folders WHERE user_id = $user_id AND parent_id IS NULL ORDER BY folder_name")->fetchAll();
$files = $conn->query("
    SELECT f.*, fo.folder_name FROM files f 
    LEFT JOIN folders fo ON f.folder_id = fo.id 
    WHERE f.user_id = $user_id 
    ORDER BY f.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Saya - CloudBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'sidebar.php'; // Buat file sidebar terpisah atau copas ?>
<div class="customer-layout">
    <main class="customer-main">
        <div class="page-header">
            <h1>File Saya</h1>
            <button class="btn-upload" onclick="document.getElementById('fileInput').click()">
                <i class="fas fa-upload"></i> Upload
            </button>
            <input type="file" id="fileInput" multiple style="display:none" onchange="uploadFiles(this.files)">
        </div>

        <!-- Upload area (bisa disederhanakan) -->
        <div id="uploadStatus"></div>

        <!-- Folder creation -->
        <div class="quick-actions" style="margin-bottom:20px;">
            <form method="POST" style="display:flex; gap:10px;">
                <input type="text" name="folder_name" placeholder="Nama folder baru" required>
                <button type="submit" class="btn-secondary"><i class="fas fa-folder-plus"></i> Buat</button>
            </form>
        </div>

        <h3>Folder</h3>
        <div class="files-grid">
            <?php foreach($folders as $f): ?>
            <div class="file-card folder">
                <i class="fas fa-folder" style="font-size:48px; color:#f39c12;"></i>
                <div class="file-name"><?= htmlspecialchars($f['folder_name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <h3 style="margin-top:30px;">File</h3>
        <div class="files-grid">
            <?php foreach($files as $file): 
                $icon = $functions->getFileIcon($file['file_type']);
            ?>
            <div class="file-card">
                <div class="file-icon" style="color:<?= $icon['color'] ?>"><i class="fas <?= $icon['icon'] ?>"></i></div>
                <div class="file-name"><?= htmlspecialchars($file['original_name']) ?></div>
                <div class="file-meta"><?= $functions->formatSize($file['file_size']) ?></div>
                <div class="file-actions">
                    <button onclick="downloadFile(<?= $file['id'] ?>)" class="btn-icon"><i class="fas fa-download"></i></button>
                    <button onclick="shareFile(<?= $file['id'] ?>)" class="btn-icon"><i class="fas fa-share"></i></button>
                    <button onclick="deleteFile(<?= $file['id'] ?>)" class="btn-icon"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
<script src="../assets/js/main.js"></script>
<script>
function uploadFiles(files) {
    const status = document.getElementById('uploadStatus');
    status.innerHTML = 'Mengupload...';
    for (let file of files) {
        let formData = new FormData();
        formData.append('file', file);
        fetch('../api/upload.php', {method:'POST', body:formData})
        .then(r=>r.json())
        .then(d=> {
            if(d.success) {
                status.innerHTML += '<p>'+file.name+' berhasil</p>';
                setTimeout(()=>location.reload(), 1500);
            } else {
                status.innerHTML += '<p style="color:red;">'+file.name+' gagal: '+d.message+'</p>';
            }
        });
    }
}
</script>
</body>
</html>