<?php
session_start();
include 'koneksi.php';

$id = intval($_GET['id']);

mysqli_query($conn,"
UPDATE pesanan
SET status_pembayaran='sudah_bayar'
WHERE id_pesanan='$id'
");

header("Location: verifikasi_pembayaran.php");
exit;
?>