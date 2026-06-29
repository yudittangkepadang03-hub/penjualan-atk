<?php
session_start();
include 'koneksi.php';

// AMBIL ID PESANAN
$id = $_GET['id'];

// DATA PESANAN
$pesanan = mysqli_query($conn, "
    SELECT 
        pesanan.*,
        users.nama

    FROM pesanan

    JOIN users
    ON pesanan.id_user = users.id_user

    WHERE pesanan.id_pesanan='$id'
");

$dataPesanan = mysqli_fetch_assoc($pesanan);

// DETAIL PESANAN
$detail = mysqli_query($conn, "
    SELECT 
        detail_pesanan.*,
        produk.nama_produk,
        produk.gambar

    FROM detail_pesanan

    JOIN produk
    ON detail_pesanan.id_produk = produk.id_produk

    WHERE detail_pesanan.id_pesanan='$id'
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Pesanan</title>

    <link rel="stylesheet" href="style_dashboard.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include 'sidebar_admin.php'; ?>

<div class="main">

    <h1>
        <i class="fas fa-file-invoice"></i>
        Detail Pesanan
    </h1>

    <!-- INFORMASI PESANAN -->
    <div class="detail-card">

        <h3>
            <i class="fas fa-user"></i>
            Informasi Pemesan
        </h3>

        <div class="detail-row">
            <span>Nama User</span>
            <strong><?php echo $dataPesanan['nama']; ?></strong>
        </div>

        <div class="detail-row">
            <span>ID Pesanan</span>
            <strong>#<?php echo $dataPesanan['id_pesanan']; ?></strong>
        </div>

        <div class="detail-row">
            <span>Tanggal Pesanan</span>
            <strong>
                <?php echo date('d M Y H:i', strtotime($dataPesanan['tanggal'])); ?>
            </strong>
        </div>

        <div class="detail-row">
            <span>Status Pesanan</span>
            <strong class="badge-status">
                <?php echo ucfirst($dataPesanan['status_psn']); ?>
            </strong>
        </div>

        <div class="detail-row">
            <span>Status Pembayaran</span>
            <strong class="badge-bayar">
                <?php echo ucfirst(str_replace('_',' ',$dataPesanan['status_pembayaran'])); ?>
            </strong>
        </div>

        <div class="detail-row">
            <span>Metode Pembayaran</span>
            <strong>
                <?php echo $dataPesanan['metode_pembayaran']; ?>
            </strong>
        </div>

        <div class="detail-row">
            <span>Total Pembayaran</span>
            <strong>
                Rp <?php echo number_format($dataPesanan['total']); ?>
            </strong>
        </div>

        <div class="detail-row alamat-row">
            <span>Alamat Pengiriman</span>

            <div class="alamat-text">
                <?php echo nl2br($dataPesanan['alamat_pengiriman']); ?>
            </div>
        </div>

    </div>

    <br>

    <!-- BUKTI PEMBAYARAN -->
    <div class="detail-card">

        <h3>
            <i class="fas fa-receipt"></i>
            Bukti Pembayaran
        </h3>

        <?php if(!empty($dataPesanan['bukti_pembayaran'])){ ?>

            <img src="bukti/<?php echo $dataPesanan['bukti_pembayaran']; ?>"
                 class="bukti-pembayaran">

        <?php } else { ?>

            <div class="belum-bayar">
                Belum ada bukti pembayaran.
            </div>

        <?php } ?>

    </div>

    <br>

    <!-- DETAIL PRODUK -->
    <div class="detail-card">

        <h3>
            <i class="fas fa-box"></i>
            Produk Yang Dibeli
        </h3>

        <table>

            <tr>
                <th>No</th>
                <th>Gambar</th>
                <th>Produk</th>
                <th>Harga</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
            </tr>

            <?php
            $no = 1;

            while($row = mysqli_fetch_assoc($detail)){
            ?>

            <tr>

                <td><?php echo $no++; ?></td>

                <td>
                    <img src="gambar/<?php echo $row['gambar']; ?>"
                         width="80">
                </td>

                <td>
                    <?php echo $row['nama_produk']; ?>
                </td>

                <td>
                    Rp <?php echo number_format($row['harga_satuan']); ?>
                </td>

                <td>
                    <?php echo $row['jumlah']; ?>
                </td>

                <td>
                    Rp <?php echo number_format($row['total_harga']); ?>
                </td>

            </tr>

            <?php } ?>

        </table>

    </div>

    <br>

    <!-- TOTAL -->
    <div class="total-box">

        <h2>
            Total Belanja :
            Rp <?php echo number_format($dataPesanan['total']); ?>
        </h2>

    </div>

    <br>

    <!-- TOMBOL -->
    <a href="javascript:history.back()" class="btn-kembali">
        <i class="fas fa-arrow-left"></i>
        Kembali
    </a>

</div>

</body>
</html>