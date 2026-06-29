<?php
session_start();
include 'koneksi.php';

// PROSES TAMBAH PRODUK
if (isset($_POST['tambah'])) {
    $nama = $_POST['nama_produk'];
    $deskripsi = $_POST['deskripsi'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];

    // UPLOAD GAMBAR
    $gambar = $_FILES['gambar']['name'];
    $tmp = $_FILES['gambar']['tmp_name'];
    move_uploaded_file($tmp, "gambar/" . $gambar);

    // INSERT KE DATABASE
    $query = mysqli_query($conn, "
        INSERT INTO produk (nama_produk, deskripsi, harga, stok, gambar)
        VALUES ('$nama', '$deskripsi', '$harga', '$stok', '$gambar')
    ");

    if ($query) {
        echo "<script>
            alert('Produk berhasil ditambahkan!');
            window.location='data_produk.php';
        </script>";
    } else {
        echo "<script>
            alert('Produk gagal ditambahkan!');
        </script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Produk</title>

    <!-- ICON -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="style_dashboard.css">
</head>

<body>

    <!-- SIDEBAR -->
    <?php include 'sidebar_admin.php'; ?>

            <li>
                <a href="logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </li>
        </ul>

    </div>

    <!-- MAIN -->
    <div class="main">

        <h1>Tambah Produk</h1>

        <div class="form-box">

            <form method="POST" enctype="multipart/form-data">

                <label>Nama Produk</label>
                <input type="text" name="nama_produk" required>

                <label>Deskripsi</label>
                <textarea name="deskripsi" required></textarea>

                <label>Harga</label>
                <input type="number" name="harga" required>

                <label>Stok</label>
                <input type="number" name="stok" required>

                <label>Gambar</label>
                <input type="file" name="gambar" required>

                <div class="form-actions">
                    <button type="submit" name="tambah">
                        Tambah Produk
                    </button>

                    <a href="data_produk.php" class="btn-batal">Batal</a>
                </div>

            </form>

        </div>

    </div>

</body>
</html>