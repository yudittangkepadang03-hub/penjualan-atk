<?php
session_start();
include 'koneksi.php';

// AMBIL ID PRODUK
$id = $_GET['id'];

// AMBIL DATA PRODUK
$data = mysqli_query($conn, "SELECT * FROM produk WHERE id_produk='$id'");
$row = mysqli_fetch_assoc($data);

// PROSES UPDATE
if(isset($_POST['update'])){

    $nama       = $_POST['nama_produk'];
    $deskripsi  = $_POST['deskripsi'];
    $harga      = $_POST['harga'];
    $stok       = $_POST['stok'];

    // GAMBAR BARU
    $gambar = $_FILES['gambar']['name'];
    $tmp    = $_FILES['gambar']['tmp_name'];

    // JIKA GAMBAR DIGANTI
    if($gambar != ""){

        move_uploaded_file($tmp, "gambar/" . $gambar);

        $update = mysqli_query($conn, "
            UPDATE produk SET
            nama_produk='$nama',
            deskripsi='$deskripsi',
            harga='$harga',
            stok='$stok',
            gambar='$gambar'
            WHERE id_produk='$id'
        ");

    }else{

        // JIKA TIDAK GANTI GAMBAR
        $update = mysqli_query($conn, "
            UPDATE produk SET
            nama_produk='$nama',
            deskripsi='$deskripsi',
            harga='$harga',
            stok='$stok'
            WHERE id_produk='$id'
        ");
    }

    if($update){
        echo "
        <script>
            alert('Produk berhasil diupdate!');
            window.location='data_produk.php';
        </script>
        ";
    }else{
        echo "
        <script>
            alert('Produk gagal diupdate!');
        </script>
        ";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Produk</title>

    <!-- ICON -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="style_dashboard.css">
</head>

<body>

<!-- PROFILE -->
<?php include 'sidebar_admin.php'; ?>

<!-- MAIN -->
<div class="main">

    <h1>Edit Produk</h1>

    <div class="form-box">

        <form method="POST" enctype="multipart/form-data">

            <label>Nama Produk</label>
            <input type="text"
                   name="nama_produk"
                   value="<?php echo $row['nama_produk']; ?>"
                   required>

            <label>Deskripsi</label>
            <textarea name="deskripsi" required><?php echo $row['deskripsi']; ?></textarea>

            <label>Harga</label>
            <input type="number"
                   name="harga"
                   value="<?php echo $row['harga']; ?>"
                   required>

            <label>Stok</label>
            <input type="number"
                   name="stok"
                   value="<?php echo $row['stok']; ?>"
                   required>

            <label>Gambar Lama</label>
            <br>
            <img src="gambar/<?php echo $row['gambar']; ?>" width="120">
            <br><br>

            <label>Ganti Gambar</label>
            <input type="file" name="gambar">

            <button type="submit" name="update">
                Update Produk
            </button>

        </form>

    </div>
    <a href="data_produk.php" class="btn-kembali">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

</div>

</body>
</html>
