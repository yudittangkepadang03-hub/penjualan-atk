<?php
session_start();
include 'koneksi.php';

// AMBIL DATA PRODUK
$query = mysqli_query($conn, "SELECT * FROM produk");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Produk</title>

    <!-- ICON -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- CSS -->
    <link rel="stylesheet" href="style_dashboard.css">
</head>

<body>

<!-- SIDEBAR -->
<?php include 'sidebar_admin.php'; ?>

<!-- MAIN -->
<div class="main">

    <h1>Data Produk</h1>

    <!-- TOMBOL TAMBAH -->
    <a href="tambah_produk.php" class="btn-tambah">
        <i class="fa-solid fa-plus"></i> Tambah Produk
    </a>

    <br><br>

    <!-- TABEL -->
    <table>

        <tr>
            <th>No</th>
            <th>Gambar</th>
            <th>Nama Produk</th>
            <th>Deskripsi</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Kelola</th>
        </tr>

        <?php
        $no = 1;

        while($row = mysqli_fetch_assoc($query)) {
        ?>

        <tr>

            <td><?php echo $no++; ?></td>

            <td>
                <img src="gambar/<?php echo $row['gambar']; ?>" width="80">
            </td>

            <td>
                <?php echo $row['nama_produk']; ?>
            </td>

            <td>
                <?php echo $row['deskripsi']; ?>
            </td>

            <td>
                Rp <?php echo number_format($row['harga']); ?>
            </td>

            <td>
                <?php echo $row['stok']; ?>
            </td>

            <td class="kelola">
                <a href="edit_produk.php?id=<?php echo $row['id_produk']; ?>" class="btn-edit">
                    Edit
                </a>

                <a href="hapus_produk.php?id=<?php echo $row['id_produk']; ?>" 
                class="btn-hapus"
                onclick="return confirm('Yakin ingin menghapus produk?')">
                    Hapus
                </a>
            </td>

        </tr>

        <?php } ?>

    </table>

</div>

</body>
</html>