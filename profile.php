<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = intval($_SESSION['id_user']);
$message = '';
$message_type = 'success';
$showEditForm = false;
if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $showEditForm = true;
}

function ensureUserAddressColumn($conn) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'alamat'");
    if ($check && mysqli_num_rows($check) === 0)
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN alamat TEXT NULL");
}
function ensureUserPhotoColumn($conn) {
    $check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'foto_profil'");
    if ($check && mysqli_num_rows($check) === 0)
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) NULL");
}
ensureUserAddressColumn($conn);
ensureUserPhotoColumn($conn);

if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id_user=?");
    $stmt->bind_param('i', $id_user);
    if ($stmt->execute()) {
        $stmt->close();
        session_unset(); session_destroy();
        header('Location: form_login.php?deleted=1');
        exit;
    }
    $message = 'Gagal menghapus akun. Silakan coba lagi.';
    $message_type = 'error';
    $stmt->close();
}

if (isset($_POST['update'])) {
    $showEditForm  = true;
    $nama          = trim($_POST['nama']   ?? '');
    $email         = trim($_POST['email']  ?? '');
    $alamat        = trim($_POST['alamat'] ?? '');
    $password      = $_POST['password']    ?? '';
    $photoFilename = null;
    $uploadError   = false;

    if (!empty($_FILES['foto_profil']['name'])) {
        $uploadDir = __DIR__ . '/profile_images';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $allowed  = ['jpg','jpeg','png'];
        $fileExt  = strtolower(pathinfo(basename($_FILES['foto_profil']['name']), PATHINFO_EXTENSION));
        $tmpName  = $_FILES['foto_profil']['tmp_name'];
        $fileSize = $_FILES['foto_profil']['size'];

        if (!in_array($fileExt, $allowed, true)) {
            $message = 'Hanya file JPG, JPEG, atau PNG yang diperbolehkan.';
            $message_type = 'error'; $uploadError = true;
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $message = 'Ukuran file maksimal 2MB.';
            $message_type = 'error'; $uploadError = true;
        } else {
            $photoFilename = uniqid('profile_', true) . '.' . $fileExt;
            if (!move_uploaded_file($tmpName, $uploadDir . '/' . $photoFilename)) {
                $message = 'Gagal mengunggah foto profil.';
                $message_type = 'error'; $uploadError = true;
            }
        }
    }

    if (!$uploadError) {
        if (strlen($nama) < 2 || strlen($nama) > 100) {
            $message = 'Nama harus antara 2 hingga 100 karakter.'; $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Format email tidak valid.'; $message_type = 'error';
        } elseif ($password !== '' && strlen($password) < 8) {
            $message = 'Password minimal 8 karakter.'; $message_type = 'error';
        } else {
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($photoFilename) {
                    $stmt = $conn->prepare("UPDATE users SET nama=?,email=?,password=?,alamat=?,foto_profil=? WHERE id_user=?");
                    $stmt->bind_param('sssssi',$nama,$email,$hash,$alamat,$photoFilename,$id_user);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET nama=?,email=?,password=?,alamat=? WHERE id_user=?");
                    $stmt->bind_param('ssssi',$nama,$email,$hash,$alamat,$id_user);
                }
            } else {
                if ($photoFilename) {
                    $stmt = $conn->prepare("UPDATE users SET nama=?,email=?,alamat=?,foto_profil=? WHERE id_user=?");
                    $stmt->bind_param('ssssi',$nama,$email,$alamat,$photoFilename,$id_user);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET nama=?,email=?,alamat=? WHERE id_user=?");
                    $stmt->bind_param('sssi',$nama,$email,$alamat,$id_user);
                }
            }
            if ($stmt->execute()) {
                $message = 'Profil berhasil diperbarui.';
                $message_type = 'success';
                $_SESSION['nama'] = $nama;
            } else {
                $message = 'Gagal memperbarui profil. Silakan coba lagi.';
                $message_type = 'error';
            }
            $stmt->close();
        }
    }
}

$stmt = $conn->prepare("SELECT nama, email, alamat, foto_profil FROM users WHERE id_user=?");
$stmt->bind_param('i', $id_user);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) { header('Location: dashboard.php'); exit; }
$row = $result->fetch_assoc();
$stmt->close();

$alamatValue    = $row['alamat'] ?? '';
$fotoProfilPath = '';
if (!empty($row['foto_profil']) && file_exists(__DIR__ . '/profile_images/' . $row['foto_profil']))
    $fotoProfilPath = 'profile_images/' . $row['foto_profil'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w:  220px;
            --blue:       #3BA3E8;
            --blue-dark:  #2980C4;
            --blue-light: #E8F4FD;
            --green:      #2ECC71;
            --green-dark: #27AE60;
            --gray-bg:    #F0F2F5;
            --gray-100:   #E4E7EB;
            --gray-500:   #8A94A3;
            --gray-800:   #2D3748;
            --white:      #FFFFFF;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--gray-bg);
            color: var(--gray-800);
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--blue);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
        }

        .sb-profile {
            padding: 2rem 1rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .sb-profile a { text-decoration: none; }

        .sb-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.6);
            object-fit: cover;
            margin: 0 auto 0.6rem;
            display: block;
        }

        .sb-avatar-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 0.6rem;
            color: #fff;
            font-size: 1.8rem;
        }

        .sb-nama {
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .sb-nav { padding: 0.75rem 0; flex: 1; }
        .sb-nav a {
            display: flex; align-items: center; gap: 11px;
            padding: 0.75rem 1.25rem;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 0.9rem; font-weight: 500;
            border-radius: 8px;
            margin: 2px 10px;
            transition: background 0.15s, color 0.15s;
        }
        .sb-nav a i { font-size: 1rem; width: 20px; text-align: center; }
        .sb-nav a:hover  { background: rgba(255,255,255,0.18); color: #fff; }
        .sb-nav a.active { background: rgba(255,255,255,0.22); color: #fff; }

        .sb-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .sb-footer a {
            display: flex; align-items: center; gap: 10px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.875rem;
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            transition: background 0.15s;
        }
        .sb-footer a:hover { background: rgba(255,255,255,0.15); color: #fff; }

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 2rem 2.25rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 1.5rem;
        }

        /* ── PROFILE CARD ── */
        .profile-action-panel {
            background: var(--white);
            border-radius: 14px;
            border: 1px solid var(--gray-100);
            padding: 1.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .profile-summary { display: flex; flex-direction: column; gap: 0.35rem; }

        .profile-photo-preview {
            width: 90px; height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--gray-100);
            margin-bottom: 0.75rem;
        }
        .profile-photo-preview.placeholder {
            display: flex; align-items: center; justify-content: center;
            background: var(--blue-light);
            color: var(--blue);
        }
        .profile-photo-preview.placeholder i { font-size: 2.5rem; }

        .profile-summary h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-800);
        }
        .profile-summary p { font-size: 0.9rem; color: var(--gray-500); }

        .action-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .action-card {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 0.65rem 1.1rem;
            border-radius: 10px;
            font-size: 0.875rem; font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            background: var(--gray-bg);
            color: var(--gray-800);
            border: 1.5px solid var(--gray-100);
        }
        .action-card:hover { background: var(--gray-100); transform: translateY(-1px); }
        .action-card.danger {
            background: #FFF5F5;
            color: #C53030;
            border-color: #FEB2B2;
        }
        .action-card.danger:hover { background: #FED7D7; }

        .action-card-form { margin: 0; }
        .action-card-form button {
            border: none; background: transparent;
            padding: 0; font: inherit; cursor: pointer;
        }

        /* ── ALERT ── */
        .alert-success, .alert-error {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
        }
        .alert-success { background: #F0FFF4; color: #276749; border: 1px solid #9AE6B4; }
        .alert-error   { background: #FFF5F5; color: #C53030; border: 1px solid #FEB2B2; }

        /* ── FORM BOX ── */
        .form-box {
            background: var(--white);
            border-radius: 14px;
            border: 1px solid var(--gray-100);
            padding: 1.75rem;
            max-width: 520px;
        }

        .form-box form { display: flex; flex-direction: column; gap: 0.25rem; }

        .form-box label {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-top: 0.9rem;
            margin-bottom: 0.3rem;
        }

        .form-box input,
        .form-box textarea,
        .form-box select {
            padding: 0.6rem 0.9rem;
            border: 1.5px solid var(--gray-100);
            border-radius: 8px;
            font-size: 0.875rem;
            color: var(--gray-800);
            background: var(--white);
            transition: border-color 0.15s, box-shadow 0.15s;
            outline: none;
        }
        .form-box input:focus,
        .form-box textarea:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(59,163,232,0.12);
        }
        .form-box textarea { resize: vertical; min-height: 90px; }

        .form-actions { display: flex; gap: 10px; margin-top: 1.25rem; }

        .btn-simpan {
            padding: 0.65rem 1.4rem;
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-simpan:hover { background: var(--blue-dark); }

        .btn-batal {
            padding: 0.65rem 1.2rem;
            background: var(--gray-bg);
            color: var(--gray-800);
            border: 1.5px solid var(--gray-100);
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex; align-items: center;
            transition: background 0.15s;
        }
        .btn-batal:hover { background: var(--gray-100); }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .sidebar { width: 64px; }
            .sb-profile, .sb-nama, .sb-nav a span, .sb-footer a span { display: none; }
            .sb-nav a  { justify-content: center; padding: 0.75rem; margin: 2px 6px; }
            .sb-footer a { justify-content: center; }
            .main { margin-left: 64px; padding: 1.25rem; }
            .form-box { max-width: 100%; }
        }
    </style>
</head>
<body>

<!-- ═══════════ SIDEBAR ═══════════ -->
<div class="sidebar">

    <div class="sb-profile">
        <a href="profile.php">
            <?php if (!empty($fotoProfilPath)): ?>
                <img class="sb-avatar"
                     src="<?= htmlspecialchars($fotoProfilPath) ?>"
                     alt="Foto Profil">
            <?php else: ?>
                <div class="sb-avatar-icon">
                    <i class="fa-solid fa-user"></i>
                </div>
            <?php endif; ?>
            <div class="sb-nama"><?= htmlspecialchars($_SESSION['nama']) ?></div>
        </a>
    </div>

    <nav class="sb-nav">
        <a href="dashboard.php">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>
        <a href="keranjang.php">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Keranjang</span>
        </a>
        <a href="pesanan.php">
            <i class="fa-solid fa-receipt"></i>
            <span>Pesanan</span>
        </a>
        <a href="pembayaran.php">
            <i class="fa-solid fa-credit-card"></i>
            <span>Pembayaran</span>
        </a>
    </nav>

    <div class="sb-footer">
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>

</div>

<!-- ═══════════ MAIN ═══════════ -->
<div class="main">

    <h1 class="page-title">
        <?= $showEditForm ? 'Edit Profil' : 'Profil Saya' ?>
    </h1>

    <!-- PROFILE SUMMARY CARD -->
    <div class="profile-action-panel">
        <div class="profile-summary">
            <?php if (!empty($fotoProfilPath)): ?>
                <img class="profile-photo-preview"
                     src="<?= htmlspecialchars($fotoProfilPath) ?>"
                     alt="Foto Profil">
            <?php else: ?>
                <div class="profile-photo-preview placeholder">
                    <i class="fa-solid fa-user"></i>
                </div>
            <?php endif; ?>
            <h2><?= htmlspecialchars($row['nama']) ?></h2>
            <p><?= htmlspecialchars($row['email']) ?></p>
            <p><?= nl2br(htmlspecialchars($alamatValue ?: 'Alamat pengiriman belum diisi.')) ?></p>
        </div>

        <div class="action-grid">
            <a class="action-card" href="profile.php?action=edit">
                <i class="fa-solid fa-pen-to-square"></i> Edit Profil
            </a>
            <a class="action-card" href="pesanan.php">
                <i class="fa-solid fa-receipt"></i> Lihat Pesanan
            </a>
            <form class="action-card-form" method="POST"
                  onsubmit="return confirm('Yakin ingin menghapus akun? Semua data akan hilang.');">
                <button type="submit" name="delete" class="action-card danger">
                    <i class="fa-solid fa-trash"></i> Hapus Akun
                </button>
            </form>
        </div>
    </div>

    <!-- NOTIFIKASI -->
    <?php if (!empty($message)): ?>
        <div class="<?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- FORM EDIT -->
    <?php if ($showEditForm): ?>
    <div class="form-box">
        <form method="POST" enctype="multipart/form-data">

            <label>Foto Profil</label>
            <input type="file" name="foto_profil" accept="image/png,image/jpeg">

            <label>Nama</label>
            <input type="text" name="nama"
                   value="<?= htmlspecialchars($row['nama']) ?>"
                   maxlength="100" required>

            <label>Email</label>
            <input type="email" name="email"
                   value="<?= htmlspecialchars($row['email']) ?>"
                   maxlength="150" required>

            <label>
                Password Baru
                <small style="font-weight:400;color:var(--gray-500);margin-left:4px;">
                    (kosongkan jika tidak ingin diubah)
                </small>
            </label>
            <input type="password" name="password"
                   minlength="8" autocomplete="new-password"
                   placeholder="Min. 8 karakter">

            <label>Alamat Pengiriman</label>
            <textarea name="alamat"
                      placeholder="Masukkan alamat lengkap pengiriman..."
                      ><?= htmlspecialchars($alamatValue) ?></textarea>

            <div class="form-actions">
                <button type="submit" name="update" class="btn-simpan">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
                <a href="profile.php" class="btn-batal">Batal</a>
            </div>

        </form>
    </div>
    <?php endif; ?>

</div>

</body>
</html>