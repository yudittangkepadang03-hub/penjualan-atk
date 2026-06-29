<?php
session_start();
include 'koneksi.php';

// CEK LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$id_produk = $_POST['id_produk'];

// CEK PRODUK DI KERANJANG
$cek = mysqli_query($conn, "
    SELECT * FROM keranjang
    WHERE id_user='$id_user' AND id_produk='$id_produk'
");

if (mysqli_num_rows($cek) > 0) {
    // JIKA SUDAH ADA -> TAMBAH JUMLAH
    mysqli_query($conn, "
        UPDATE keranjang
        SET jumlah = jumlah + 1
        WHERE id_user='$id_user' AND id_produk='$id_produk'
    ");
} else {
    // JIKA BELUM ADA -> INSERT
    mysqli_query($conn, "
        INSERT INTO keranjang (id_user, id_produk, jumlah)
        VALUES ('$id_user', '$id_produk', 1)
    ");
}

// NOTIFIKASI
echo "<script>
    alert('Barang berhasil dimasukkan ke keranjang!');
    window.location='dashboard.php';
</script>";
?>