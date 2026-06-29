<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {

    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");

    if ($query && mysqli_num_rows($query) > 0) {

        $data = mysqli_fetch_assoc($query);

        
       if ($data['status'] != 'verified') {
            $_SESSION['error'] = "Akun belum diverifikasi! Silakan cek email untuk verifikasi.";
            $_SESSION['old_email'] = $email;

            header("Location: form_login.php");
            exit;
        }

        
        if (password_verify($password, $data['password'])) {

            unset($_SESSION['error']);
            unset($_SESSION['old_email']);

            $_SESSION['id_user'] = $data['id_user'];
            $_SESSION['nama']    = $data['nama'];
            $_SESSION['role']    = $data['role'];

            if ($data['role'] == 'admin') {
                echo "<script>
                        alert('Login berhasil!');
                        window.location='admin.php';
                      </script>";
            } else {
                echo "<script>
                        alert('Login berhasil!');
                        window.location='dashboard.php';
                      </script>";
            }
            exit;

        } else {
            $_SESSION['error'] = "Password salah!";
            $_SESSION['old_email'] = $email;
            header("Location: form_login.php");
            exit;
        }

    } else {
        $_SESSION['error'] = "Email tidak ditemukan!";
        $_SESSION['old_email'] = $email;
        header("Location: form_login.php");
        exit;
    }
}
?>