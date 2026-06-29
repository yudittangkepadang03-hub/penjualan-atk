<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = intval($_SESSION['id_user']);

// Validasi input
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_pesanan'])) {
    header("Location: pembayaran.php");
    exit;
}

$id_pesanan = intval($_POST['id_pesanan']);

// Pastikan pesanan milik user yang login
$cek = $conn->prepare("SELECT id_pesanan, status_pembayaran FROM pesanan WHERE id_pesanan = ? AND id_user = ?");
$cek->bind_param('ii', $id_pesanan, $id_user);
$cek->execute();
$result = $cek->get_result();
$pesanan = $result->fetch_assoc();
$cek->close();

if (!$pesanan) {
    header("Location: pembayaran.php?error=invalid");
    exit;
}

if ($pesanan['status_pembayaran'] !== 'belum_bayar') {
    header("Location: pembayaran.php?error=already");
    exit;
}

// Proses upload file
$error = '';
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
$max_size = 5 * 1024 * 1024; // 5 MB

if (empty($_FILES['bukti_pembayaran']['name'])) {
    $error = 'Bukti pembayaran wajib diupload.';
} elseif (!in_array($_FILES['bukti_pembayaran']['type'], $allowed_types)) {
    $error = 'Format file harus JPG, PNG, atau WebP.';
} elseif ($_FILES['bukti_pembayaran']['size'] > $max_size) {
    $error = 'Ukuran file maksimal 5 MB.';
} else {
    $ext      = pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION);
    $filename = 'bukti_' . $id_pesanan . '_' . time() . '.' . strtolower($ext);
    $dir      = __DIR__ . '/bukti_pembayaran/';

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (!move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $dir . $filename)) {
        $error = 'Gagal mengupload file. Silakan coba lagi.';
    }
}

if ($error) {
    // Simpan error ke session dan redirect kembali
    $_SESSION['upload_error'] = $error;
    header("Location: pembayaran.php?error=upload");
    exit;
}

// Update DB: simpan nama file bukti + ubah status ke menunggu_verifikasi
$stmt = $conn->prepare("
    UPDATE pesanan
    SET bukti_pembayaran = ?, status_pembayaran = 'menunggu_verifikasi'
    WHERE id_pesanan = ? AND id_user = ?
");
$stmt->bind_param('sii', $filename, $id_pesanan, $id_user);
$stmt->execute();
$stmt->close();

header("Location: pembayaran.php?sukses=1");
exit;
?>