<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = intval($_SESSION['id_user']);

// Ambil foto profil
$fotoProfilPath = '';
$stmtProfile = $conn->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
$stmtProfile->bind_param('i', $id_user);
$stmtProfile->execute();
$resultProfile = $stmtProfile->get_result();
if ($resultProfile && $rowProfile = $resultProfile->fetch_assoc()) {
    if (!empty($rowProfile['foto_profil']) && file_exists(__DIR__ . '/profile_images/' . $rowProfile['foto_profil'])) {
        $fotoProfilPath = 'profile_images/' . $rowProfile['foto_profil'];
    }
}
$stmtProfile->close();

$id_pesanan = intval($_GET['id']);

$query = mysqli_query($conn, "
    SELECT * FROM pesanan WHERE id_pesanan='$id_pesanan'
");

$pesanan = mysqli_fetch_assoc($query);

if (!$pesanan) {
    die("Pesanan tidak ditemukan");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pembayaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 220px;
            --blue:      #3BA3E8;
            --blue-dark: #2980C4;
            --green:     #2ECC71;
            --green-dark:#27AE60;
            --gray-bg:   #F0F2F5;
            --gray-100:  #E4E7EB;
            --gray-800:  #2D3748;
            --white:     #FFFFFF;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--gray-bg);
            color: var(--gray-800);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--blue);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            padding: 0;
        }

        .sb-profile {
            padding: 2rem 1rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .sb-profile a { text-decoration: none; }

        .sb-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.6);
            object-fit: cover;
            margin: 0 auto 0.6rem;
            display: block;
        }

        .sb-avatar-icon {
            width: 72px; height: 72px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.6rem;
            color: #fff;
            font-size: 1.8rem;
        }

        .sb-nama {
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .sb-nav { padding: 0.75rem 0; flex: 1; }

        .sb-nav a {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 0.75rem 1.25rem;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 8px;
            margin: 2px 10px;
            transition: background 0.15s, color 0.15s;
        }

        .sb-nav a i { font-size: 1rem; width: 20px; text-align: center; }

        .sb-nav a:hover, .sb-nav a.active {
            background: rgba(255,255,255,0.22);
            color: #fff;
        }

        .sb-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .sb-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.875rem;
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            transition: background 0.15s;
        }

        .sb-footer a:hover { background: rgba(255,255,255,0.15); color: #fff; }

        /* MAIN */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 2rem 2.25rem;
        }

        /* PAYMENT BOX */
        .payment-box {
            background: var(--white);
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            max-width: 680px;
        }

        .payment-box h1 {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-box h2 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 20px 0 5px;
            color: var(--gray-800);
        }

        .payment-box hr {
            border: none;
            border-top: 1px solid var(--gray-100);
            margin: 20px 0;
        }

        .info {
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: var(--gray-800);
        }

        .info strong { margin-right: 6px; }

        /* REKENING BOX */
        .rekening-box {
            background: #f8fafc;
            border: 1px solid var(--gray-100);
            border-radius: 10px;
            padding: 16px;
            margin-top: 12px;
        }

        .rekening-box p {
            margin: 6px 0;
            font-size: 0.9rem;
            color: var(--gray-800);
        }

        /* QRIS */
        .qris {
            width: 220px;
            border-radius: 10px;
            margin-top: 15px;
            border: 1px solid var(--gray-100);
            display: block;
        }

        /* BUTTONS */
        .btn-group {
            margin-top: 25px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-bayar {
            background: var(--green);
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 9px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s;
        }

        .btn-bayar:hover { background: var(--green-dark); }

        .btn-group .btn-kembali {
            background: #f59e0b;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 9px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s;
        }

        .btn-group .btn-kembali:hover { background: #d97706; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { width: 64px; }
            .sb-profile, .sb-nama, .sb-nav a span, .sb-footer a span { display: none; }
            .sb-nav a { justify-content: center; padding: 0.75rem; margin: 2px 6px; }
            .sb-footer a { justify-content: center; }
            .main { margin-left: 64px; padding: 1.25rem; }
            .payment-box { max-width: 100%; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sb-profile">
        <a href="profile.php">
            <?php if (!empty($fotoProfilPath)): ?>
                <img class="sb-avatar" src="<?= htmlspecialchars($fotoProfilPath) ?>" alt="Foto Profil">
            <?php else: ?>
                <div class="sb-avatar-icon"><i class="fa-solid fa-user"></i></div>
            <?php endif; ?>
            <div class="sb-nama"><?= htmlspecialchars($_SESSION['nama']) ?></div>
        </a>
    </div>

    <nav class="sb-nav">
        <a href="dashboard.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="keranjang.php"><i class="fa-solid fa-cart-shopping"></i><span>Keranjang</span></a>
        <a href="pesanan.php"><i class="fa-solid fa-receipt"></i><span>Pesanan</span></a>
        <a href="pembayaran.php" class="active"><i class="fa-solid fa-credit-card"></i><span>Pembayaran</span></a>
    </nav>

    <div class="sb-footer">
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <div class="payment-box">

        <h1>
            <i class="fa-solid fa-credit-card"></i>
            Pembayaran
        </h1>

        <div class="info">
            <strong>ID Pesanan :</strong>
            #<?= $pesanan['id_pesanan'] ?>
        </div>

        <div class="info">
            <strong>Total Tagihan :</strong>
            Rp <?= number_format($pesanan['total']) ?>
        </div>

        <div class="info">
            <strong>Metode :</strong>
            <?= htmlspecialchars($pesanan['metode_pembayaran']) ?>
        </div>

        <hr>

        <!-- TRANSFER BANK -->
        <?php if ($pesanan['metode_pembayaran'] == "Transfer Bank"): ?>
            <h2>Transfer ke Rekening Berikut</h2>
            <div class="rekening-box">
                <p><strong>Bank :</strong> BRI</p>
                <p><strong>No Rekening :</strong> 500401036578531</p>
                <p><strong>Atas Nama :</strong> YUDIT TANGKE PADANG</p>
            </div>
            <div class="btn-group">
                <a href="konfirmasi_bayar.php?id=<?= $pesanan['id_pesanan'] ?>" class="btn-bayar">
                    <i class="fa-solid fa-money-check-dollar"></i> Bayar Sekarang
                </a>
                <a href="pesanan.php" class="btn-kembali">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        <?php endif; ?>

        <!-- QRIS -->
        <?php if ($pesanan['metode_pembayaran'] == "QRIS"): ?>
            <h2>Scan QRIS Berikut</h2>
            <img src="gambar/qris.png" class="qris" alt="QRIS">
            <div class="btn-group">
                <a href="konfirmasi_bayar.php?id=<?= $pesanan['id_pesanan'] ?>" class="btn-bayar">
                    <i class="fa-solid fa-qrcode"></i> Bayar Sekarang
                </a>
                <a href="pesanan.php" class="btn-kembali">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        <?php endif; ?>

        <!-- E-WALLET -->
        <?php if ($pesanan['metode_pembayaran'] == "E-Wallet"): ?>
            <h2>Pembayaran E-Wallet</h2>
            <div class="rekening-box">
                <p><strong>GoPay / OVO / Dana :</strong></p>
                <p><strong>No. Tujuan :</strong> 081242148409</p>
                <p><strong>Atas Nama :</strong> Toko ATK</p>
            </div>
            <div class="btn-group">
                <a href="konfirmasi_bayar.php?id=<?= $pesanan['id_pesanan'] ?>" class="btn-bayar">
                    <i class="fa-solid fa-wallet"></i> Bayar Sekarang
                </a>
                <a href="pesanan.php" class="btn-kembali">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        <?php endif; ?>

        <!-- COD -->
        <?php if ($pesanan['metode_pembayaran'] == "COD"): ?>
            <h2>Pembayaran COD</h2>
            <p style="color:#666; font-size:0.95rem; margin-top:8px;">
                Pembayaran dilakukan saat barang diterima.
            </p>
            <div class="btn-group">
                <a href="pesanan.php" class="btn-kembali">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        <?php endif; ?>

    </div>

</div>

</body>
</html>