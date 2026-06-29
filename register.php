<?php
include 'koneksi.php';
require 'vendor/autoload.php';

if (isset($_POST['register'])) {
    $nama     = $_POST['nama'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $cek = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Email sudah terdaftar!'); window.location='form_register.php';</script>";
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, email, password, status) VALUES (?, ?, ?, 'verified')");
    mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $password);

    if (mysqli_stmt_execute($stmt)) {
        echo '
        <!DOCTYPE html>
        <html lang="id">
        <head><meta charset="UTF-8"><title>Registrasi Berhasil</title>
        <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f0fdf4; font-family:"Segoe UI",sans-serif; }
        .card { background:#fff; border-left:6px solid #22c55e; border-radius:12px; padding:32px 40px; max-width:420px; width:90%; box-shadow:0 8px 24px rgba(0,0,0,0.08); text-align:center; }
        h3 { color:#16a34a; font-size:22px; margin-bottom:8px; }
        p { color:#6b7280; font-size:15px; line-height:1.6; }
        a { display:inline-block; margin-top:16px; padding:10px 24px; background:#22c55e; color:#fff; border-radius:8px; text-decoration:none; font-weight:bold; }
        </style>
        </head>
        <body>
        <div class="card">
            <h3>✅ Registrasi Berhasil!</h3>
            <p>Akun kamu sudah aktif.<br>Silakan login sekarang.</p>
            <a href="form_login.php">Login Sekarang</a>
        </div>
        </body>
        </html>';
    } else {
        echo "Registrasi gagal! Error: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}
?>