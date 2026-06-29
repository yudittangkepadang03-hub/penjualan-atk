<?php
include 'koneksi.php';

if (!isset($_GET['token'])) {
    die("Token tidak ditemukan!");
}

$token = mysqli_real_escape_string($conn, $_GET['token']);

$query = mysqli_query($conn, "SELECT * FROM users WHERE token_verifikasi='$token'");

if (!$query) {
    die("Query Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($query) > 0) {

    mysqli_query($conn, "UPDATE users 
        SET status='verified', token_verifikasi=NULL 
        WHERE token_verifikasi='$token'");

    echo "<script>
            alert('Akun berhasil diverifikasi!');
            window.location='form_login.php';
          </script>";

} else {
    echo "Token tidak valid!";
}
?>