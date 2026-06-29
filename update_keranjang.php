<?php
session_start();
include 'koneksi.php';

// CEK LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user      = mysqli_real_escape_string($conn, $_SESSION['id_user']);
$id_keranjang = intval($_POST['id_keranjang'] ?? $_GET['id_keranjang'] ?? 0);
$action       = trim($_POST['action'] ?? $_GET['action'] ?? '');

if ($id_keranjang <= 0 || !in_array($action, ['increase', 'decrease'])) {
    header("Location: keranjang.php");
    exit;
}

// AMBIL DATA KERANJANG + STOK PRODUK
$cek = mysqli_query($conn, "
    SELECT keranjang.jumlah, produk.stok
    FROM keranjang
    JOIN produk ON keranjang.id_produk = produk.id_produk
    WHERE keranjang.id_keranjang = $id_keranjang
    AND keranjang.id_user = '$id_user'
");

if (!$cek || mysqli_num_rows($cek) == 0) {
    header("Location: keranjang.php");
    exit;
}

$data   = mysqli_fetch_assoc($cek);
$jumlah = intval($data['jumlah']);
$stok   = intval($data['stok']);

if ($action === 'increase') {
    // TAMBAH 1 (tidak boleh melebihi stok)
    if ($jumlah < $stok) {
        $update = mysqli_query($conn, "
            UPDATE keranjang SET jumlah = jumlah + 1
            WHERE id_keranjang = $id_keranjang AND id_user = '$id_user'
        ");
    }
} elseif ($action === 'decrease') {
    if ($jumlah > 1) {
        // KURANGI 1
        $update = mysqli_query($conn, "
            UPDATE keranjang SET jumlah = jumlah - 1
            WHERE id_keranjang = $id_keranjang AND id_user = '$id_user'
        ");
    } else {
        // JIKA JUMLAH SUDAH 1, HAPUS DARI KERANJANG
        $update = mysqli_query($conn, "
            DELETE FROM keranjang
            WHERE id_keranjang = $id_keranjang AND id_user = '$id_user'
        ");
    }
}

header("Location: keranjang.php");
exit;