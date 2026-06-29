<?php
session_start();
include 'koneksi.php';

// AMBIL ID USER
$id = $_GET['id'];

// AMBIL DATA USER YANG AKAN DIHAPUS
$user = mysqli_query($conn, "SELECT role FROM users WHERE id_user='$id'");
if (!$user || mysqli_num_rows($user) == 0) {
    echo "
    <script>
        alert('User tidak ditemukan!');
        window.location='data_user.php';
    </script>
    ";
    exit;
}

$data = mysqli_fetch_assoc($user);

// CEK AGAR ADMIN TIDAK BISA HAPUS ADMIN
if ($data['role'] === 'admin') {
    echo "
    <script>
        alert('Admin tidak bisa dihapus!');
        window.location='data_user.php';
    </script>
    ";
    exit;
}

// CEK AGAR ADMIN TIDAK BISA HAPUS DIRI SENDIRI
if ($_SESSION['id_user'] == $id) {
    echo "
    <script>
        alert('Admin tidak bisa menghapus akun sendiri!');
        window.location='data_user.php';
    </script>
    ";
    exit;
}

// HAPUS USER
$hapus = mysqli_query($conn, "
    DELETE FROM users
    WHERE id_user='$id'
");

// CEK HASIL
if($hapus){

    echo "
    <script>
        alert('User berhasil dihapus!');
        window.location='data_user.php';
    </script>
    ";

}else{

    echo "
    <script>
        alert('User gagal dihapus!');
        window.location='data_user.php';
    </script>
    ";
}
?>