<?php
session_start();
include 'koneksi.php';

if(!isset($_SESSION['id_user'])){
    header("Location: form_login.php");
    exit;
}

$query = mysqli_query($conn,"
SELECT
    p.*,
    u.nama
FROM pesanan p
JOIN users u
ON p.id_user = u.id_user
WHERE p.status_pembayaran='menunggu_verifikasi'
ORDER BY p.id_pesanan DESC
");

$total_verifikasi = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verifikasi Pembayaran</title>

    <link rel="stylesheet" href="style_dashboard.css">

    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<?php include 'sidebar_admin.php'; ?>

<div class="main">

    <div class="page-header">
        <h1>
            <i class="fas fa-money-check-alt"></i>
            Verifikasi Pembayaran
        </h1>

        <p>
            Kelola pembayaran pelanggan yang menunggu verifikasi.
        </p>
    </div>

    <div class="stats-container">

        <div class="stat-card">
            <i class="fas fa-clock"></i>

            <div>
                <h2><?php echo $total_verifikasi; ?></h2>
                <p>Menunggu Verifikasi</p>
            </div>
        </div>

    </div>

    <div class="table-container">

        <?php if($total_verifikasi > 0){ ?>

        <table>

            <tr>
                <th>ID Pesanan</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Metode</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <?php while($row=mysqli_fetch_assoc($query)){ ?>

            <tr>

                <td>
                    #<?php echo $row['id_pesanan']; ?>
                </td>

                <td>
                    <?php echo $row['nama']; ?>
                </td>

                <td>
                    Rp <?php echo number_format($row['total']); ?>
                </td>

                <td>
                    <?php echo $row['metode_pembayaran']; ?>
                </td>

                <td>
                    <?php echo date('d M Y', strtotime($row['tanggal'])); ?>
                </td>

                <td>
                    <span class="badge-menunggu">
                        Menunggu
                    </span>
                </td>

                <td>

                    <a href="detail_pembayaran.php?id=<?php echo $row['id_pesanan']; ?>"
                       class="btn-detail">
                        <i class="fas fa-eye"></i>
                        Detail
                    </a>

                    <a href="approve_pembayaran.php?id=<?php echo $row['id_pesanan']; ?>"
                       class="btn-verifikasi"
                       onclick="return confirm('Verifikasi pembayaran ini?')">

                        <i class="fas fa-check"></i>
                        Verifikasi

                    </a>

                </td>

            </tr>

            <?php } ?>

        </table>

        <?php } else { ?>

        <div class="empty-box">

            <i class="fas fa-check-circle"></i>

            <h3>Tidak Ada Pembayaran Menunggu</h3>

            <p>
                Semua pembayaran sudah diverifikasi.
            </p>

        </div>

        <?php } ?>

    </div>

</div>

</body>
</html>