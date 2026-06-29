<?php
session_start();
include 'koneksi.php';

// CEK LOGIN ADMIN
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = intval($_SESSION['id_user']);
$user_query = mysqli_query($conn, "SELECT role FROM users WHERE id_user = '$id_user'");
$user_data = mysqli_fetch_assoc($user_query);

if ($user_data['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$id     = intval($_GET['id'] ?? 0);
$status = mysqli_real_escape_string($conn, $_GET['status'] ?? '');

// Normalisasi status (lowercase)
$status = strtolower($status);

// Valid statuses
$valid_statuses = array('pending', 'diproses', 'dikirim', 'selesai');

if(in_array($status, $valid_statuses) && $id > 0) {
    mysqli_query($conn, "
        UPDATE pesanan 
        SET status_psn = '$status' 
        WHERE id_pesanan = $id
    ");
    header("Location: kelola_pesanan.php?msg=Status pesanan berhasil diubah");
} else {
    header("Location: kelola_pesanan.php?error=Status tidak valid");
}
exit;
?>