<?php
include 'koneksi.php';

// AMBIL ID PRODUK
$id = $_GET['id'];

// AMBIL DATA GAMBAR
$data = mysqli_query($conn, "SELECT * FROM produk WHERE id_produk='$id'");
$row = mysqli_fetch_assoc($data);

// HAPUS GAMBAR DARI FOLDER
if(file_exists("gambar/" . $row['gambar'])){
    unlink("gambar/" . $row['gambar']);
}

// HAPUS DATA PRODUK
$hapus = mysqli_query($conn, "DELETE FROM produk WHERE id_produk='$id'");

// CEK HASIL
if($hapus){
    echo "
    <script>
        alert('Produk berhasil dihapus!');
        window.location='data_produk.php';
    </script>
    ";
}else{
    echo "
    <script>
        alert('Produk gagal dihapus!');
        window.location='data_produk.php';
    </script>
    ";
}
?>