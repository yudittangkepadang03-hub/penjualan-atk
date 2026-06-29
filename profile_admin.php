<?php
session_start();
include 'koneksi.php';

// CEK LOGIN ADMIN
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = intval($_SESSION['id_user']);

// Cek role admin
$user_query = mysqli_query($conn, "SELECT role FROM users WHERE id_user = '$id_user'");
$user_data  = mysqli_fetch_assoc($user_query);
if ($user_data['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$message      = '';
$message_type = 'success';
$showEditForm = isset($_GET['action']) && $_GET['action'] === 'edit';

// Pastikan kolom foto_profil & no_telp ada di tabel users
$check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'foto_profil'");
if (mysqli_num_rows($check) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) NULL");
}
$check2 = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'no_telp'");
if (mysqli_num_rows($check2) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN no_telp VARCHAR(20) NULL");
}

// UPDATE PROFIL
if (isset($_POST['update'])) {
    $showEditForm  = true;
    $nama          = trim($_POST['nama'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $no_telp       = trim($_POST['no_telp'] ?? '');
    $password      = $_POST['password'] ?? '';
    $photoFilename = null;
    $uploadError   = false;

    if (!empty($_FILES['foto_profil']['name'])) {
        $uploadDir = __DIR__ . '/profile_images_admin';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $allowed  = ['jpg', 'jpeg', 'png'];
        $fileExt  = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
        $tmpName  = $_FILES['foto_profil']['tmp_name'];
        $fileSize = $_FILES['foto_profil']['size'];

        if (!in_array($fileExt, $allowed, true)) {
            $message = 'Hanya file JPG, JPEG, atau PNG yang diperbolehkan.';
            $message_type = 'error';
            $uploadError = true;
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $message = 'Ukuran file maksimal 2MB.';
            $message_type = 'error';
            $uploadError = true;
        } else {
            $photoFilename = uniqid('admin_', true) . '.' . $fileExt;
            if (!move_uploaded_file($tmpName, $uploadDir . '/' . $photoFilename)) {
                $message = 'Gagal mengunggah foto profil.';
                $message_type = 'error';
                $uploadError = true;
            }
        }
    }

    if (!$uploadError) {
        if (strlen($nama) < 2 || strlen($nama) > 100) {
            $message = 'Nama harus antara 2 hingga 100 karakter.';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Format email tidak valid.';
            $message_type = 'error';
        } elseif ($password !== '' && strlen($password) < 8) {
            $message = 'Password minimal 8 karakter.';
            $message_type = 'error';
        } else {
            if ($password !== '') {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                if ($photoFilename) {
                    $stmt = $conn->prepare("UPDATE users SET nama=?, email=?, password=?, no_telp=?, foto_profil=? WHERE id_user=?");
                    $stmt->bind_param('sssssi', $nama, $email, $passwordHash, $no_telp, $photoFilename, $id_user);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET nama=?, email=?, password=?, no_telp=? WHERE id_user=?");
                    $stmt->bind_param('ssssi', $nama, $email, $passwordHash, $no_telp, $id_user);
                }
            } else {
                if ($photoFilename) {
                    $stmt = $conn->prepare("UPDATE users SET nama=?, email=?, no_telp=?, foto_profil=? WHERE id_user=?");
                    $stmt->bind_param('ssssi', $nama, $email, $no_telp, $photoFilename, $id_user);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET nama=?, email=?, no_telp=? WHERE id_user=?");
                    $stmt->bind_param('sssi', $nama, $email, $no_telp, $id_user);
                }
            }

            if ($stmt->execute()) {
                $message = 'Profil berhasil diperbarui.';
                $message_type = 'success';
                $_SESSION['nama'] = $nama;
            } else {
                $message = 'Gagal memperbarui profil.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

// AMBIL DATA
$stmt = $conn->prepare("SELECT nama, email, no_telp, foto_profil FROM users WHERE id_user=?");
$stmt->bind_param('i', $id_user);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) { header('Location: admin.php'); exit; }

$no_telpValue = $row['no_telp'] ?? '';
$fotoAdmin    = $row['foto_profil'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        .profil-wrapper {
            display: flex;
            gap: 28px;
            flex-wrap: wrap;
        }

        /* === KARTU PROFIL KIRI === */
        .profil-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            padding: 36px 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            width: 260px;
            flex-shrink: 0;
        }
        .profil-avatar {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #5b73e8;
            box-shadow: 0 4px 18px rgba(91,115,232,0.18);
        }
        .profil-avatar-placeholder {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: linear-gradient(135deg, #5b73e8, #a29bfe);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: #fff;
            box-shadow: 0 4px 18px rgba(91,115,232,0.18);
        }
        .profil-card h2 {
            margin: 6px 0 0;
            font-size: 20px;
            color: #1e1e2f;
            text-align: center;
        }
        .profil-card .email-text {
            color: #6b7280;
            font-size: 14px;
            text-align: center;
            word-break: break-all;
        }
        .profil-card .telp-text {
            color: #6b7280;
            font-size: 14px;
        }
        .profil-card .role-badge {
            background: linear-gradient(135deg, #5b73e8, #a29bfe);
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 16px;
            border-radius: 20px;
            margin-top: 4px;
            letter-spacing: 0.5px;
        }
        .profil-card .btn-edit-profil {
            margin-top: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #5b73e8, #a29bfe);
            color: #fff;
            padding: 10px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: opacity .2s;
        }
        .profil-card .btn-edit-profil:hover { opacity: .85; }

        /* === PANEL KANAN === */
        .profil-right {
            flex: 1;
            min-width: 280px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Info detail */
        .info-box {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.07);
            padding: 28px;
        }
        .info-box h3 {
            margin: 0 0 18px;
            font-size: 16px;
            color: #1e1e2f;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f5;
        }
        .info-row:last-child { border-bottom: none; }
        .info-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f0f2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5b73e8;
            font-size: 15px;
            flex-shrink: 0;
        }
        .info-label {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 2px;
        }
        .info-value {
            font-size: 15px;
            color: #1e1e2f;
            font-weight: 500;
        }

        /* Aksi cepat */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 14px;
        }
        .quick-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.07);
            padding: 20px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #1e1e2f;
            font-size: 13px;
            font-weight: 600;
            transition: transform .15s, box-shadow .15s;
            text-align: center;
        }
        .quick-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.11); }
        .quick-card i { font-size: 24px; }
        .quick-card.blue i  { color: #5b73e8; }
        .quick-card.green i { color: #10ac84; }
        .quick-card.orange i{ color: #ff9f43; }
        .quick-card.cyan i  { color: #01a3a4; }

        /* === FORM EDIT === */
        .form-box {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.07);
            padding: 32px;
            margin-top: 24px;
        }
        .form-box h3 {
            margin: 0 0 22px;
            font-size: 17px;
            color: #1e1e2f;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }
        .form-group input {
            padding: 10px 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            color: #1e1e2f;
            transition: border-color .2s;
            outline: none;
        }
        .form-group input:focus { border-color: #5b73e8; }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .btn-simpan {
            background: linear-gradient(135deg, #5b73e8, #a29bfe);
            color: #fff;
            border: none;
            padding: 11px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity .2s;
        }
        .btn-simpan:hover { opacity: .85; }
        .btn-batal {
            background: #f3f4f6;
            color: #374151;
            padding: 11px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-batal:hover { background: #e5e7eb; }

        .notif-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .notif-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .profil-wrapper { flex-direction: column; }
            .profil-card { width: 100%; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'sidebar_admin.php'; ?>

<div class="main">
    <div class="page-header">
        <h1><i class="fa-solid fa-user-shield"></i> Profil Admin</h1>
        <p>Kelola informasi akun dan keamanan login kamu.</p>
    </div>

    <?php if (!empty($message)) { ?>
        <div class="notif-<?php echo $message_type; ?>">
            <i class="fa-solid fa-<?php echo $message_type === 'success' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <div class="profil-wrapper">
        <!-- KARTU KIRI -->
        <div class="profil-card">
            <?php if (!empty($fotoAdmin) && file_exists(__DIR__ . '/profile_images_admin/' . $fotoAdmin)) { ?>
                <img class="profil-avatar"
                     src="profile_images_admin/<?php echo htmlspecialchars($fotoAdmin); ?>"
                     alt="Foto Admin">
            <?php } else { ?>
                <div class="profil-avatar-placeholder">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            <?php } ?>
            <h2><?php echo htmlspecialchars($row['nama']); ?></h2>
            <span class="role-badge"><i class="fa-solid fa-shield-halved"></i> Administrator</span>
            <p class="email-text"><?php echo htmlspecialchars($row['email']); ?></p>
            <?php if ($no_telpValue) { ?>
                <p class="telp-text"><i class="fa-solid fa-phone" style="margin-right:5px;"></i><?php echo htmlspecialchars($no_telpValue); ?></p>
            <?php } ?>
            <a href="profile_admin.php?action=edit" class="btn-edit-profil">
                <i class="fa-solid fa-pen-to-square"></i> Edit Profil
            </a>
        </div>

        <!-- PANEL KANAN -->
        <div class="profil-right">
            <!-- Detail Info -->
            <div class="info-box">
                <h3><i class="fa-solid fa-circle-info" style="color:#5b73e8;"></i> Informasi Akun</h3>
                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-user"></i></div>
                    <div>
                        <div class="info-label">Nama Lengkap</div>
                        <div class="info-value"><?php echo htmlspecialchars($row['nama']); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($row['email']); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                    <div>
                        <div class="info-label">No. Telepon</div>
                        <div class="info-value"><?php echo htmlspecialchars($no_telpValue ?: '-'); ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon"><i class="fa-solid fa-shield-halved"></i></div>
                    <div>
                        <div class="info-label">Role</div>
                        <div class="info-value">Administrator</div>
                    </div>
                </div>
            </div>

            <!-- Aksi Cepat -->
            <div class="quick-actions">
                <a class="quick-card blue" href="data_produk.php">
                    <i class="fa-solid fa-box"></i> Kelola Produk
                </a>
                <a class="quick-card green" href="data_pesanan.php">
                    <i class="fa-solid fa-receipt"></i> Kelola Pesanan
                </a>
                <a class="quick-card orange" href="verifikasi_pembayaran.php">
                    <i class="fa-solid fa-credit-card"></i> Pembayaran
                </a>
                <a class="quick-card cyan" href="data_user.php">
                    <i class="fa-solid fa-users"></i> Kelola User
                </a>
            </div>
        </div>
    </div>

    <!-- FORM EDIT -->
    <?php if ($showEditForm) { ?>
    <div class="form-box">
        <h3><i class="fa-solid fa-pen-to-square" style="color:#5b73e8;"></i> Edit Profil</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Foto Profil <small style="font-weight:normal;color:#9ca3af;">(JPG/PNG, maks 2MB)</small></label>
                    <input type="file" name="foto_profil" accept="image/png, image/jpeg">
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="<?php echo htmlspecialchars($row['nama']); ?>" maxlength="100" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" maxlength="150" required>
                </div>
                <div class="form-group">
                    <label>Password Baru <small style="font-weight:normal;color:#9ca3af;">(kosongkan jika tidak diubah)</small></label>
                    <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Min. 8 karakter">
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="tel" name="no_telp" value="<?php echo htmlspecialchars($no_telpValue); ?>" maxlength="20" placeholder="Contoh: 081234567890">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" name="update" class="btn-simpan">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
                <a href="profile_admin.php" class="btn-batal">Kembali</a>
            </div>
        </form>
    </div>
    <?php } ?>
</div>

</body>
</html>