<?php
include 'koneksi.php';
require 'vendor/autoload.php';

if (isset($_POST['register'])) {
    $nama     = $_POST['nama'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $token    = bin2hex(random_bytes(32));

    $cek = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Email sudah terdaftar!'); window.location='form_register.php';</script>";
        exit;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, email, password, token_verifikasi) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $nama, $email, $password, $token);

    if (mysqli_stmt_execute($stmt)) {

        $link = "https://penjualan-atk-production.up.railway.app/verifikasi.php?token=$token";

        $resend = new \Resend\Client('re_eNEz2NWr_UgPH7K8r9LLAitAnHV1u4Gce');

        $resend->sendEmail([
            'from' => 'onboarding@resend.dev',
            'to'   => $email,
            'subject' => 'Verifikasi Akun Kamu',
            'html' => "
                <div style='font-family:sans-serif;max-width:480px;margin:auto;padding:32px;border:1px solid #e5e7eb;border-radius:12px;text-align:center;'>
                    <h2 style='color:#16a34a;'>Halo, $nama! 👋</h2>
                    <p style='color:#374151;'>Klik tombol di bawah untuk verifikasi akunmu:</p>
                    <a href='$link' style='display:inline-block;margin-top:16px;padding:12px 28px;background:#22c55e;color:#fff;border-radius:8px;text-decoration:none;font-weight:bold;font-size:16px;'>
                        ✅ Verifikasi Akun
                    </a>
                </div>
            ",
        ]);

        echo '
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Cek Email</title>
            <style>
                * { margin:0; padding:0; box-sizing:border-box; }
                body { min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f0fdf4; font-family:"Segoe UI",sans-serif; }
                .card { background:#fff; border-left:6px solid #22c55e; border-radius:12px; padding:32px 40px; max-width:420px; width:90%; box-shadow:0 8px 24px rgba(0,0,0,0.08); text-align:center; }
                .icon { width:64px; height:64px; background:#dcfce7; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:32px; }
                h3 { color:#16a34a; font-size:22px; margin-bottom:8px; }
                p { color:#6b7280; font-size:15px; line-height:1.6; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon">📧</div>
                <h3>Cek Email Kamu!</h3>
                <p>Link verifikasi sudah dikirim ke<br><strong>' . $email . '</strong><br><br>Buka email dan klik tombol <strong>Verifikasi Akun</strong>.</p>
            </div>
        </body>
        </html>';

    } else {
        echo "Registrasi gagal! Error: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}
?>