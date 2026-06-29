<?php
session_start();
include 'koneksi.php';

// CEK LOGIN & ROLE ADMIN
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') != 'admin') {
    header("Location: form_login.php");
    exit;
}

// [DIPERBAIKI] Validasi $id dengan filter_input, bukan intval($_GET)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id <= 0) {
    header('Location: data_user.php');
    exit;
}

// [DIPERBAIKI] CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$message_type = 'error';

if (isset($_POST['update'])) {

    // [DIPERBAIKI] Validasi CSRF token
    if (!isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header('Location: data_user.php');
        exit;
    }

    // [DIPERBAIKI] Validasi & sanitasi input
    $nama   = trim($_POST['nama'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $role   = $_POST['role'] ?? '';
    $status = $_POST['status'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validasi panjang & format
    if (strlen($nama) < 2 || strlen($nama) > 100) {
        $message = 'Nama harus 2–100 karakter.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid.';
    } elseif (!in_array($role, ['admin', 'user'], true)) {
        $message = 'Role tidak valid.';
    } elseif (!in_array($status, ['active', 'pending', 'disabled'], true)) {
        $message = 'Status tidak valid.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $message = 'Password minimal 8 karakter.';
    }

    if (empty($message)) {
        // [DIPERBAIKI] Prepared statements — cegah SQL Injection
        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "UPDATE users SET nama=?, email=?, password=?, role=?, status=? WHERE id_user=?"
            );
            $stmt->bind_param('sssssi', $nama, $email, $passwordHash, $role, $status, $id);
        } else {
            $stmt = $conn->prepare(
                "UPDATE users SET nama=?, email=?, role=?, status=? WHERE id_user=?"
            );
            $stmt->bind_param('ssssi', $nama, $email, $role, $status, $id);
        }

        if ($stmt->execute()) {
            header('Location: data_user.php?updated=1');
            exit;
        } else {
            // Jangan tampilkan detail error DB ke user
            $message = 'Gagal memperbarui data. Silakan coba lagi.';
            error_log('edit_user error: ' . $stmt->error);
        }
        $stmt->close();
    }
}

// [DIPERBAIKI] Prepared statement untuk fetch data user
$stmt = $conn->prepare("SELECT id_user, nama, email, role, status FROM users WHERE id_user=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows == 0) {
    header('Location: data_user.php');
    exit;
}
$row = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
</head>
<body>

<!-- SIDEBAR (tidak berubah) -->
...

<div class="main">
    <h1>Edit User</h1>

    <!-- [DIPERBAIKI] Gunakan class "error" bukan "success" untuk pesan error -->
    <?php if (!empty($message)) { ?>
        <div class="error" style="margin-bottom:20px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php } ?>

    <div class="form-box">
        <form method="POST">
            <!-- [DIPERBAIKI] CSRF token field -->
            <input type="hidden" name="csrf_token"
                   value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label>Nama</label>
            <input type="text" name="nama"
                   value="<?php echo htmlspecialchars($row['nama']); ?>"
                   maxlength="100" required>

            <label>Email</label>
            <input type="email" name="email"
                   value="<?php echo htmlspecialchars($row['email']); ?>"
                   maxlength="150" required>

            <label>Password Baru
                <small>(kosongkan bila tidak ingin diubah)</small>
            </label>
            <!-- [DIPERBAIKI] Tambah minlength untuk password -->
            <input type="password" name="password" minlength="8" autocomplete="new-password">

            <label>Role</label>
            <select name="role" required>
                <option value="admin" <?php echo ($row['role']=='admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="user"  <?php echo ($row['role']=='user')  ? 'selected' : ''; ?>>User</option>
            </select>

            <label>Status</label>
            <select name="status" required>
                <option value="active"   <?php echo ($row['status']=='active')   ? 'selected' : ''; ?>>Active</option>
                <option value="pending"  <?php echo ($row['status']=='pending')  ? 'selected' : ''; ?>>Pending</option>
                <option value="disabled" <?php echo ($row['status']=='disabled') ? 'selected' : ''; ?>>Disabled</option>
            </select>

            <div class="form-actions">
                <button type="submit" name="update">Update User</button>
                <a href="data_user.php" class="btn-batal">Batal</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>