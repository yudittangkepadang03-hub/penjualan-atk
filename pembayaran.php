<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}
$id_user = intval($_SESSION['id_user']);

// FOTO PROFIL
$fotoProfilPath = '';
$stmtProfile = $conn->prepare("SELECT foto_profil FROM users WHERE id_user = ?");
$stmtProfile->bind_param("i", $id_user);
$stmtProfile->execute();
$resultProfile = $stmtProfile->get_result();
if ($rowProfile = $resultProfile->fetch_assoc()) {
    if (!empty($rowProfile['foto_profil']) && file_exists(__DIR__ . '/profile_images/' . $rowProfile['foto_profil'])) {
        $fotoProfilPath = 'profile_images/' . $rowProfile['foto_profil'];
    }
}
$stmtProfile->close();

$query = mysqli_query($conn, "
    SELECT * FROM pesanan
    WHERE id_user = '$id_user'
    ORDER BY id_pesanan DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 220px;
            --blue:      #3BA3E8;
            --blue-dark: #2980C4;
            --blue-light:#E8F4FD;
            --green:     #2ECC71;
            --green-dark:#27AE60;
            --gray-bg:   #F0F2F5;
            --gray-100:  #E4E7EB;
            --gray-500:  #8A94A3;
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

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--blue);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
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
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 0.6rem;
            color: #fff; font-size: 1.8rem;
        }

        .sb-nama { color: #fff; font-size: 0.95rem; font-weight: 600; }

        .sb-nav { padding: 0.75rem 0; flex: 1; }

        .sb-nav a {
            display: flex; align-items: center; gap: 11px;
            padding: 0.75rem 1.25rem;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-size: 0.9rem; font-weight: 500;
            border-radius: 8px;
            margin: 2px 10px;
            transition: background 0.15s, color 0.15s;
        }

        .sb-nav a i { font-size: 1rem; width: 20px; text-align: center; }
        .sb-nav a:hover { background: rgba(255,255,255,0.18); color: #fff; }
        .sb-nav a.active { background: rgba(255,255,255,0.22); color: #fff; }

        .sb-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.2); }

        .sb-footer a {
            display: flex; align-items: center; gap: 10px;
            color: rgba(255,255,255,0.8); text-decoration: none;
            font-size: 0.875rem; padding: 0.6rem 0.75rem;
            border-radius: 8px; transition: background 0.15s;
        }

        .sb-footer a:hover { background: rgba(255,255,255,0.15); color: #fff; }

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 2rem 2.25rem;
        }

        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 1.5rem;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
            background: var(--white);
            border-radius: 14px;
            border: 1px solid var(--gray-100);
        }

        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--gray-100); }

        /* ── PAYMENT LIST ── */
        .payment-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .payment-card {
            background: var(--white);
            border-radius: 14px;
            border: 1px solid var(--gray-100);
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .payment-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        }

        /* Card Header */
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--gray-100);
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .order-id {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .order-id span { font-weight: 700; color: var(--gray-800); }

        /* Status Badge */
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-belum     { background: #fef3c7; color: #92400e; }
        .badge-menunggu  { background: #eff6ff; color: #1d4ed8; }
        .badge-lunas     { background: #f0fdf4; color: #16a34a; }
        .badge-ditolak   { background: #fef2f2; color: #dc2626; }

        /* Card Body */
        .card-body {
            padding: 1.1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .card-info { display: flex; flex-direction: column; gap: 6px; flex: 1; }

        .card-info-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--gray-800);
        }

        .card-info-row i {
            width: 16px;
            color: var(--gray-500);
            font-size: 0.8rem;
        }

        .card-info-row .label { color: var(--gray-500); min-width: 60px; }

        .card-total {
            text-align: right;
            min-width: 140px;
        }

        .card-total .label {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-bottom: 2px;
        }

        .card-total .amount {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--green);
        }

        /* Payment Instructions */
        .payment-instruction {
            margin: 0 1.25rem 1.25rem;
            border-radius: 10px;
            border: 1px solid var(--gray-100);
            overflow: hidden;
        }

        .instruction-header {
            background: var(--blue-light);
            border-bottom: 1px solid var(--gray-100);
            padding: 0.7rem 1rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--blue-dark);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .instruction-body { padding: 1rem; background: var(--white); }

        /* COD */
        .cod-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.875rem;
            color: #92400e;
            background: #fef3c7;
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }

        .cod-info i { font-size: 1.1rem; }

        /* Bank info rows */
        .bank-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.875rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .bank-row:last-of-type { border-bottom: none; }
        .bank-row .bank-label { color: var(--gray-500); min-width: 110px; font-size: 0.8rem; }
        .bank-row .bank-value { font-weight: 600; color: var(--gray-800); }

        /* QRIS */
        .qris-wrap {
            display: flex;
            gap: 1.25rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .qris-wrap img {
            width: 160px;
            height: 160px;
            object-fit: contain;
            border-radius: 10px;
            border: 1px solid var(--gray-100);
        }

        .qris-text {
            font-size: 0.85rem;
            color: var(--gray-500);
            line-height: 1.6;
            flex: 1;
            min-width: 140px;
        }

        .qris-text b { color: var(--gray-800); display: block; margin-bottom: 4px; }

        /* E-Wallet logos */
        .ewallet-logos {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 0.9rem;
        }

        .ewallet-logo-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            background: var(--gray-bg);
            border: 1px solid var(--gray-100);
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--gray-800);
        }

        .ewallet-logo-chip i { color: var(--blue); }

        /* Buttons */
        .card-actions {
            padding: 0.875rem 1.25rem;
            border-top: 1px solid var(--gray-100);
            background: #fafafa;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-confirm {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--green);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.55rem 1.1rem;
            font-size: 0.85rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-confirm:hover { background: var(--green-dark); }

        /* Paid / verified notice */
        .verified-notice {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #16a34a;
            font-weight: 600;
            padding: 0.875rem 1.25rem;
            border-top: 1px solid var(--gray-100);
            background: #f0fdf4;
        }

        .waiting-notice {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #1d4ed8;
            font-weight: 600;
            padding: 0.875rem 1.25rem;
            border-top: 1px solid var(--gray-100);
            background: #eff6ff;
        }

        /* Upload Bukti */
        .upload-section {
            margin: 0 1.25rem 1.25rem;
            border: 2px dashed var(--gray-100);
            border-radius: 10px;
            padding: 1.1rem;
            background: #fafafa;
        }

        .upload-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.6rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .upload-label i { color: var(--blue); }

        .upload-input-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-pilih-file {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--white);
            color: var(--blue);
            border: 1.5px solid var(--blue);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
        }

        .btn-pilih-file:hover { background: var(--blue); color: #fff; }

        .file-name-display {
            font-size: 0.8rem;
            color: var(--gray-500);
            flex: 1;
        }

        .preview-wrap {
            margin-top: 0.75rem;
            display: none;
        }

        .preview-wrap img {
            max-width: 180px;
            border-radius: 8px;
            border: 1px solid var(--gray-100);
        }

        .preview-wrap.show { display: block; }

        /* Bukti sudah diupload */
        .bukti-preview {
            margin: 0 1.25rem 1.25rem;
            padding: 0.875rem;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .bukti-preview img {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #bbf7d0;
        }

        .bukti-preview-info { font-size: 0.85rem; color: #166534; }
        .bukti-preview-info b { display: block; font-weight: 700; margin-bottom: 2px; }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { width: 64px; }
            .sb-profile, .sb-nama, .sb-nav a span, .sb-footer a span { display: none; }
            .sb-nav a { justify-content: center; padding: 0.75rem; margin: 2px 6px; }
            .sb-footer a { justify-content: center; }
            .main { margin-left: 64px; padding: 1.25rem; }
            .card-total { text-align: left; min-width: unset; }
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
        <a href="dashboard.php">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>
        <a href="keranjang.php">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Keranjang</span>
        </a>
        <a href="pesanan.php">
            <i class="fa-solid fa-receipt"></i>
            <span>Pesanan</span>
        </a>
        <a href="pembayaran.php" class="active">
            <i class="fa-solid fa-credit-card"></i>
            <span>Pembayaran</span>
        </a>
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

    <h1 class="page-title">Pembayaran Pesanan</h1>

    <?php if (isset($_GET['sukses'])): ?>
        <div style="display:flex;align-items:center;gap:10px;background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;border-radius:10px;padding:.85rem 1.1rem;font-size:.9rem;font-weight:500;margin-bottom:1.5rem;">
            <i class="fa-solid fa-circle-check"></i>
            Bukti pembayaran berhasil dikirim! Admin akan segera memverifikasi.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && isset($_SESSION['upload_error'])): ?>
        <div style="display:flex;align-items:center;gap:10px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:10px;padding:.85rem 1.1rem;font-size:.9rem;font-weight:500;margin-bottom:1.5rem;">
            <i class="fa-solid fa-circle-xmark"></i>
            <?= htmlspecialchars($_SESSION['upload_error']) ?>
        </div>
        <?php unset($_SESSION['upload_error']); ?>
    <?php endif; ?>

    <?php if (mysqli_num_rows($query) == 0): ?>
        <div class="empty-state">
            <i class="fa-solid fa-credit-card"></i>
            <p>Belum ada data pembayaran.</p>
        </div>
    <?php else: ?>

    <div class="payment-list">
    <?php while ($row = mysqli_fetch_assoc($query)):

        $status_byr = $row['status_pembayaran'] ?? 'belum_bayar';
        $metode     = $row['metode_pembayaran'] ?? '';

        // Badge
        $badge_class = 'badge-belum';
        $badge_label = 'Belum Bayar';
        $badge_icon  = 'fa-clock';

        if ($status_byr === 'menunggu_verifikasi') {
            $badge_class = 'badge-menunggu';
            $badge_label = 'Menunggu Verifikasi';
            $badge_icon  = 'fa-hourglass-half';
        } elseif ($status_byr === 'diterima') {
            $badge_class = 'badge-lunas';
            $badge_label = 'Sudah Dibayar';
            $badge_icon  = 'fa-circle-check';
        } elseif ($status_byr === 'ditolak') {
            $badge_class = 'badge-ditolak';
            $badge_label = 'Ditolak';
            $badge_icon  = 'fa-circle-xmark';
        }
    ?>

    <div class="payment-card">

        <!-- Header -->
        <div class="card-header">
            <div class="order-id">
                Order <span>#<?= str_pad($row['id_pesanan'], 4, '0', STR_PAD_LEFT) ?></span>
            </div>
            <span class="badge <?= $badge_class ?>">
                <i class="fa-solid <?= $badge_icon ?>"></i>
                <?= $badge_label ?>
            </span>
        </div>

        <!-- Body -->
        <div class="card-body">
            <div class="card-info">
                <div class="card-info-row">
                    <i class="fa-solid fa-wallet"></i>
                    <span class="label">Metode</span>
                    <span><?= htmlspecialchars($metode ?: '-') ?></span>
                </div>
                <div class="card-info-row">
                    <i class="fa-regular fa-calendar"></i>
                    <span class="label">Tanggal</span>
                    <span><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                </div>
            </div>
            <div class="card-total">
                <div class="label">Total Tagihan</div>
                <div class="amount">Rp <?= number_format($row['total']) ?></div>
            </div>
        </div>

        <?php if ($status_byr === 'belum_bayar'): ?>

            <!-- COD -->
            <?php if ($metode === 'COD'): ?>
            <div class="payment-instruction">
                <div class="instruction-header">
                    <i class="fa-solid fa-motorcycle"></i> Instruksi Pembayaran
                </div>
                <div class="instruction-body">
                    <div class="cod-info">
                        <i class="fa-solid fa-circle-info"></i>
                        Pembayaran dilakukan saat barang tiba di tangan kamu. Siapkan uang pas.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- QRIS -->
            <?php if ($metode === 'QRIS'): ?>
            <div class="payment-instruction">
                <div class="instruction-header">
                    <i class="fa-solid fa-qrcode"></i> Scan QRIS untuk Membayar
                </div>
                <div class="instruction-body">
                    <div class="qris-wrap">
                        <img src="gambar/qris.png" alt="QRIS">
                        <div class="qris-text">
                            <b>Cara Bayar:</b>
                            1. Buka aplikasi dompet digital kamu (GoPay, OVO, DANA, dll)<br>
                            2. Pilih menu <b>Scan QR</b><br>
                            3. Arahkan kamera ke kode QRIS di samping<br>
                            4. Konfirmasi jumlah <b>Rp <?= number_format($row['total']) ?></b><br>
                            5. Klik tombol <b>"Saya Sudah Bayar"</b> di bawah
                        </div>
                    </div>
                </div>
            </div>
            <!-- Upload Bukti QRIS -->
            <form action="konfirmasi_bayar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_pesanan" value="<?= $row['id_pesanan'] ?>">
                <div class="upload-section" id="upload-wrap-<?= $row['id_pesanan'] ?>">
                    <div class="upload-label">
                        <i class="fa-solid fa-image"></i> Upload Bukti Pembayaran
                    </div>
                    <div class="upload-input-wrap">
                        <label class="btn-pilih-file" for="bukti-<?= $row['id_pesanan'] ?>">
                            <i class="fa-solid fa-upload"></i> Pilih Foto
                        </label>
                        <span class="file-name-display" id="fname-<?= $row['id_pesanan'] ?>">Belum ada file dipilih</span>
                        <input type="file" id="bukti-<?= $row['id_pesanan'] ?>"
                               name="bukti_pembayaran" accept="image/*" required
                               style="display:none"
                               onchange="previewBukti(this, <?= $row['id_pesanan'] ?>)">
                    </div>
                    <div class="preview-wrap" id="preview-<?= $row['id_pesanan'] ?>">
                        <img id="preview-img-<?= $row['id_pesanan'] ?>" src="" alt="Preview">
                    </div>
                </div>
                <div class="card-actions">
                    <button type="submit" class="btn-confirm">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Bukti Pembayaran
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <!-- E-Wallet -->
            <?php if ($metode === 'E-Wallet'): ?>
            <div class="payment-instruction">
                <div class="instruction-header">
                    <i class="fa-solid fa-wallet"></i> Instruksi Pembayaran E-Wallet
                </div>
                <div class="instruction-body">
                    <div class="ewallet-logos">
                        <span class="ewallet-logo-chip"><i class="fa-solid fa-wallet"></i> DANA</span>
                        <span class="ewallet-logo-chip"><i class="fa-solid fa-wallet"></i> OVO</span>
                        <span class="ewallet-logo-chip"><i class="fa-solid fa-wallet"></i> GoPay</span>
                        <span class="ewallet-logo-chip"><i class="fa-solid fa-wallet"></i> ShopeePay</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">No. E-Wallet</span>
                        <span class="bank-value">081242148409 <em style="color:var(--gray-500);font-weight:400;"></em></span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">Atas Nama</span>
                        <span class="bank-value">Toko ATK <em style="color:var(--gray-500);font-weight:400;"></em></span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">Jumlah</span>
                        <span class="bank-value" style="color:var(--green);">Rp <?= number_format($row['total']) ?></span>
                    </div>
                    <div class="qris-text" style="margin-top:0.75rem;">
                        Transfer sesuai jumlah di atas menggunakan salah satu e-wallet (DANA / OVO / GoPay / ShopeePay) ke nomor tujuan, lalu upload bukti transfer di bawah ini.
                    </div>
                </div>
            </div>
            <!-- Upload Bukti E-Wallet -->
            <form action="konfirmasi_bayar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_pesanan" value="<?= $row['id_pesanan'] ?>">
                <div class="upload-section">
                    <div class="upload-label">
                        <i class="fa-solid fa-image"></i> Upload Bukti Pembayaran E-Wallet
                    </div>
                    <div class="upload-input-wrap">
                        <label class="btn-pilih-file" for="bukti-<?= $row['id_pesanan'] ?>">
                            <i class="fa-solid fa-upload"></i> Pilih Foto
                        </label>
                        <span class="file-name-display" id="fname-<?= $row['id_pesanan'] ?>">Belum ada file dipilih</span>
                        <input type="file" id="bukti-<?= $row['id_pesanan'] ?>"
                               name="bukti_pembayaran" accept="image/*" required
                               style="display:none"
                               onchange="previewBukti(this, <?= $row['id_pesanan'] ?>)">
                    </div>
                    <div class="preview-wrap" id="preview-<?= $row['id_pesanan'] ?>">
                        <img id="preview-img-<?= $row['id_pesanan'] ?>" src="" alt="Preview">
                    </div>
                </div>
                <div class="card-actions">
                    <button type="submit" class="btn-confirm">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Bukti Pembayaran
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <!-- Transfer Bank -->
            <?php if ($metode === 'Transfer Bank'): ?>
            <div class="payment-instruction">
                <div class="instruction-header">
                    <i class="fa-solid fa-building-columns"></i> Instruksi Transfer Bank
                </div>
                <div class="instruction-body">
                    <div class="bank-row">
                        <span class="bank-label">Bank</span>
                        <span class="bank-value">BRI</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">No. Rekening</span>
                        <span class="bank-value">500401036578531</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">Atas Nama</span>
                        <span class="bank-value">YUDIT TANGKE PADANG</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">Jumlah</span>
                        <span class="bank-value" style="color:var(--green);">Rp <?= number_format($row['total']) ?></span>
                    </div>
                </div>
            </div>
            <!-- Upload Bukti Transfer -->
            <form action="konfirmasi_bayar.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_pesanan" value="<?= $row['id_pesanan'] ?>">
                <div class="upload-section">
                    <div class="upload-label">
                        <i class="fa-solid fa-image"></i> Upload Bukti Transfer
                    </div>
                    <div class="upload-input-wrap">
                        <label class="btn-pilih-file" for="bukti-<?= $row['id_pesanan'] ?>">
                            <i class="fa-solid fa-upload"></i> Pilih Foto
                        </label>
                        <span class="file-name-display" id="fname-<?= $row['id_pesanan'] ?>">Belum ada file dipilih</span>
                        <input type="file" id="bukti-<?= $row['id_pesanan'] ?>"
                               name="bukti_pembayaran" accept="image/*" required
                               style="display:none"
                               onchange="previewBukti(this, <?= $row['id_pesanan'] ?>)">
                    </div>
                    <div class="preview-wrap" id="preview-<?= $row['id_pesanan'] ?>">
                        <img id="preview-img-<?= $row['id_pesanan'] ?>" src="" alt="Preview">
                    </div>
                </div>
                <div class="card-actions">
                    <button type="submit" class="btn-confirm">
                        <i class="fa-solid fa-paper-plane"></i> Kirim Bukti Transfer
                    </button>
                </div>
            </form>
            <?php endif; ?>

        <?php elseif ($status_byr === 'menunggu_verifikasi'): ?>
            <?php if (!empty($row['bukti_pembayaran'])): ?>
            <div class="bukti-preview">
                <img src="bukti_pembayaran/<?= htmlspecialchars($row['bukti_pembayaran']) ?>" alt="Bukti">
                <div class="bukti-preview-info">
                    <b>Bukti pembayaran terkirim</b>
                    Sedang diverifikasi oleh admin. Mohon tunggu sebentar.
                </div>
            </div>
            <?php endif; ?>
            <div class="waiting-notice">
                <i class="fa-solid fa-hourglass-half"></i>
                Pembayaran sedang diverifikasi oleh admin. Mohon tunggu sebentar.
            </div>

        <?php elseif ($status_byr === 'diterima'): ?>
            <div class="verified-notice">
                <i class="fa-solid fa-circle-check"></i>
                Pembayaran telah dikonfirmasi. Pesanan sedang diproses.
            </div>

        <?php elseif ($status_byr === 'ditolak'): ?>
            <div class="card-actions" style="background:#fef2f2; border-top-color:#fecaca;">
                <span style="color:#dc2626; font-size:0.85rem; font-weight:600; display:flex; align-items:center; gap:7px;">
                    <i class="fa-solid fa-circle-xmark"></i>
                    Pembayaran ditolak. Silakan hubungi admin atau ulangi pembayaran.
                </span>
            </div>
        <?php endif; ?>

    </div>
    <?php endwhile; ?>
    </div>

    <?php endif; ?>
</div>

<script>
function previewBukti(input, id) {
    const fname  = document.getElementById('fname-' + id);
    const wrap   = document.getElementById('preview-' + id);
    const img    = document.getElementById('preview-img-' + id);

    if (input.files && input.files[0]) {
        const file = input.files[0];
        fname.textContent = file.name;
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            wrap.classList.add('show');
        };
        reader.readAsDataURL(file);
    }
}
</script>
</body>
</html>