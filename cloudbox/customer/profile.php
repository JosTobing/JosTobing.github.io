<?php
require_once '../config/auth.php';
$auth->requireLogin();
if ($auth->isAdmin()) { header('Location: ../admin/index.php'); exit(); }

$functions = new CloudBoxFunctions();
$db = new Database();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    // --- Upload Avatar ---
    $avatar_filename = $user['avatar']; // gunakan yang lama dulu
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        // Validasi tipe file
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $error = 'Tipe file tidak diizinkan. Hanya JPG, PNG, GIF, WebP.';
        } elseif ($file['size'] > $max_size) {
            $error = 'Ukuran file terlalu besar. Maksimal 2MB.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_name = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
            $upload_path = '../uploads/avatars/' . $new_name;

            // Buat folder jika belum ada
            if (!file_exists('../uploads/avatars/')) {
                mkdir('../uploads/avatars/', 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Hapus avatar lama jika bukan default.png
                if ($user['avatar'] !== 'default.png' && file_exists('../uploads/avatars/' . $user['avatar'])) {
                    unlink('../uploads/avatars/' . $user['avatar']);
                }
                $avatar_filename = $new_name;
            } else {
                $error = 'Gagal mengupload file.';
            }
        }
    }

    // Jika tidak ada error, update database
    if (empty($error)) {
        if ($password) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, password=?, avatar=? WHERE id=?");
            $stmt->execute([$full_name, $phone, $hashed, $avatar_filename, $user_id]);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, phone=?, avatar=? WHERE id=?");
            $stmt->execute([$full_name, $phone, $avatar_filename, $user_id]);
        }
        $message = 'Profil berhasil diperbarui.';
        // Update session
        $_SESSION['full_name'] = $full_name;
        $_SESSION['avatar'] = $avatar_filename; // jika ingin dipakai di sidebar
        // Refresh user data
        $user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch();
    }
}

// Tentukan halaman aktif untuk sidebar
$active_page = 'profile';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - CloudBox</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .avatar-upload {
            text-align: center;
            margin-bottom: 20px;
        }
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #4A90E2;
            margin-bottom: 10px;
            background: #f0f0f0;
        }
        .btn-upload-avatar {
            background: #4A90E2;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            display: inline-block;
        }
        .btn-upload-avatar:hover {
            background: #357ABD;
        }
    </style>
</head>
<body>
<div class="customer-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="customer-main">
        <div class="page-header"><h1>Profil Saya</h1></div>
        <?php if($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="table-card" style="max-width:500px; margin:20px auto;">
            <!-- ENCTYPE multipart penting untuk upload file -->
            <form method="POST" enctype="multipart/form-data">
                <!-- Avatar Upload -->
                <div class="avatar-upload">
                    <?php
                    // Tentukan path avatar
                    $avatar_path = '../uploads/avatars/' . $user['avatar'];
                    if (!file_exists($avatar_path) || $user['avatar'] === 'default.png') {
                        $avatar_path = '../assets/images/default.png';
                    }
                    // Tambahkan timestamp untuk mencegah cache
                    $avatar_path .= '?t=' . time();
                    ?>
                    <img src="<?= $avatar_path ?>" alt="Avatar" class="avatar-preview" id="avatarPreview">
                    <br>
                    <label for="avatarInput" class="btn-upload-avatar">
                        <i class="fas fa-camera"></i> Ganti Foto
                    </label>
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display: none;" onchange="previewAvatar(event)">
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Nama Lengkap</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly disabled>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Nomor Telepon</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password Baru (kosongkan jika tidak ingin mengubah)</label>
                    <input type="password" name="password" placeholder="Minimal 8 karakter">
                </div>
                <button type="submit" class="btn-auth"><i class="fas fa-save"></i> Simpan Perubahan</button>
            </form>
        </div>
    </main>
</div>

<script>
// Preview avatar sebelum upload
function previewAvatar(event) {
    const reader = new FileReader();
    reader.onload = function() {
        document.getElementById('avatarPreview').src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
}
</script>
</body>
</html>