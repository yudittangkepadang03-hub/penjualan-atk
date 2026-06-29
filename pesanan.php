<?php
session_start();
include 'koneksi.php';

// CEK LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = intval($_SESSION['id_user']);
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

// AMBIL DATA PESANAN BESERTA DETAIL
$query = mysqli_query($conn, "
    SELECT
        pesanan.id_pesanan,
        pesanan.total AS total_harga,
        pesanan.status_psn,
        pesanan.tanggal,
        pesanan.metode_pembayaran,
        pesanan.alamat_pengiriman,
        GROUP_CONCAT(produk.nama_produk SEPARATOR ', ') AS nama_produk,
        SUM(detail_pesanan.jumlah) AS total_item
    FROM pesanan
    LEFT JOIN detail_pesanan ON pesanan.id_pesanan = detail_pesanan.id_pesanan
    LEFT JOIN produk ON detail_pesanan.id_produk = produk.id_produk
    WHERE pesanan.id_user = '$id_user'
    GROUP BY pesanan.id_pesanan
    ORDER BY pesanan.id_pesanan DESC
");

if (!$query) {
    die('Query gagal: ' . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya</title>
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

        /* SUCCESS ALERT */
        .alert-success {
            display: flex; align-items: center; gap: 10px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            border-radius: 10px;
            padding: 0.85rem 1.1rem;
            font-size: 0.9rem;
            font-weight: 500;
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

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
            color: var(--gray-100);
        }

        .empty-state p { font-size: 0.95rem; }

        /* ── PESANAN LIST ── */
        .pesanan-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .pesanan-card {
            background: var(--white);
            border-radius: 14px;
            border: 1px solid var(--gray-100);
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .pesanan-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        }

        /* Card Header */
        .pesanan-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--gray-100);
            gap: 1rem;
            flex-wrap: wrap;
        }

        .pesanan-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .pesanan-id {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .pesanan-id span {
            font-weight: 700;
            color: var(--gray-800);
        }

        .pesanan-date {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .pesanan-date i { margin-right: 4px; }

        /* Badge */
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-pending  { background: #f3f4f6; color: #6b7280; }
        .badge-diproses { background: #fff7ed; color: #ea580c; }
        .badge-dikirim  { background: #eff6ff; color: #2563eb; }
        .badge-selesai  { background: #f0fdf4; color: #16a34a; }

        /* Card Body */
        .pesanan-body {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .pesanan-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            min-width: 200px;
        }

        .pesanan-produk-nama {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-800);
            line-height: 1.4;
        }

        .pesanan-meta {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .pesanan-meta i { width: 14px; margin-right: 3px; }

        .pesanan-total {
            text-align: right;
            min-width: 130px;
        }

        .pesanan-total .label {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-bottom: 2px;
        }

        .pesanan-total .amount {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--green);
        }

        /* Card Actions */
        .pesanan-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0.875rem 1.25rem;
            border-top: 1px solid var(--gray-100);
            background: #fafafa;
            flex-wrap: wrap;
        }

        .btn-detail {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--blue-light);
            color: var(--blue);
            border: 1.5px solid var(--blue);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
        }

        .btn-detail:hover { background: var(--blue); color: #fff; }

        .btn-bayar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff7ed;
            color: #ea580c;
            border: 1.5px solid #ea580c;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
        }

        .btn-bayar:hover { background: #ea580c; color: #fff; }

        .spacer { flex: 1; }

        /* ── DETAIL PANEL ── */
        .detail-panel {
            display: none;
            border-top: 1px solid var(--gray-100);
            padding: 1.1rem 1.25rem;
            background: #f9fafb;
            animation: slideDown 0.2s ease;
        }

        .detail-panel.open { display: block; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .detail-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--gray-500);
            margin-bottom: 0.75rem;
        }

        .detail-address {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--gray-800);
            background: var(--white);
            border: 1px solid var(--gray-100);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }

        .detail-address i { color: var(--blue); margin-top: 2px; flex-shrink: 0; }

        /* Detail items table */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--gray-100);
        }

        .detail-table th {
            background: #f1f5f9;
            color: var(--gray-500);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.65rem 0.9rem;
            text-align: left;
        }

        .detail-table td {
            padding: 0.75rem 0.9rem;
            font-size: 0.85rem;
            border-top: 1px solid var(--gray-100);
            color: var(--gray-800);
        }

        .detail-table tr:first-child td { border-top: none; }

        .detail-table img {
            width: 48px; height: 48px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--gray-100);
        }

        .detail-table td.subtotal {
            font-weight: 700;
            color: var(--green);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { width: 64px; }
            .sb-profile, .sb-nama, .sb-nav a span, .sb-footer a span { display: none; }
            .sb-nav a { justify-content: center; padding: 0.75rem; margin: 2px 6px; }
            .sb-footer a { justify-content: center; }
            .main { margin-left: 64px; padding: 1.25rem; }
            .pesanan-total { min-width: unset; text-align: left; }
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
        <a href="pesanan.php" class="active">
            <i class="fa-solid fa-receipt"></i>
            <span>Pesanan</span>
        </a>
        <a href="pembayaran.php">
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

    <h1 class="page-title">Pesanan Saya</h1>

    <?php if (isset($_GET['sukses'])): ?>
        <div class="alert-success">
            <i class="fa-solid fa-circle-check"></i>
            Pesanan berhasil dibuat! Silakan tunggu konfirmasi dari admin.
        </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($query) == 0): ?>
        <div class="empty-state">
            <i class="fa-solid fa-box-open"></i>
            <p>Belum ada pesanan. Silakan checkout barang dari keranjang.</p>
        </div>
    <?php else: ?>

    <div class="pesanan-list">
    <?php while ($row = mysqli_fetch_assoc($query)):

        $status = $row['status_psn'];
        $badge_class  = 'badge-pending';
        $status_label = 'Pending';
        $status_icon  = 'fa-clock';

        if ($status === 'diproses') {
            $badge_class  = 'badge-diproses';
            $status_label = 'Diproses';
            $status_icon  = 'fa-gear';
        } elseif ($status === 'dikirim') {
            $badge_class  = 'badge-dikirim';
            $status_label = 'Dikirim';
            $status_icon  = 'fa-truck';
        } elseif ($status === 'selesai') {
            $badge_class  = 'badge-selesai';
            $status_label = 'Selesai';
            $status_icon  = 'fa-circle-check';
        }

        $id_psn = intval($row['id_pesanan']);
    ?>

    <div class="pesanan-card">

        <!-- Header -->
        <div class="pesanan-header">
            <div class="pesanan-header-left">
                <div class="pesanan-id">
                    Order <span>#<?= str_pad($id_psn, 4, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="pesanan-date">
                    <i class="fa-regular fa-calendar"></i>
                    <?= date('d M Y', strtotime($row['tanggal'])) ?>
                </div>
            </div>
            <span class="badge <?= $badge_class ?>">
                <i class="fa-solid <?= $status_icon ?>"></i>
                <?= $status_label ?>
            </span>
        </div>

        <!-- Body -->
        <div class="pesanan-body">
            <div class="pesanan-info">
                <div class="pesanan-produk-nama">
                    <?= htmlspecialchars($row['nama_produk'] ?? '-') ?>
                </div>
                <div class="pesanan-meta">
                    <i class="fa-solid fa-box"></i><?= (int)($row['total_item'] ?? 0) ?> item
                    <?php if (!empty($row['metode_pembayaran'])): ?>
                        &nbsp;&bull;&nbsp;
                        <i class="fa-solid fa-credit-card"></i><?= htmlspecialchars($row['metode_pembayaran']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pesanan-total">
                <div class="label">Total Pembayaran</div>
                <div class="amount">Rp <?= number_format($row['total_harga'] ?? 0) ?></div>
            </div>
        </div>

        <!-- Actions -->
        <div class="pesanan-actions">
            <button class="btn-detail" onclick="toggleDetail(<?= $id_psn ?>)">
                <i class="fa-solid fa-eye"></i> Lihat Detail
            </button>
            <?php if ($status === 'pending'): ?>
                <a href="payment_details.php?id=<?= $id_psn ?>" class="btn-bayar">
                    <i class="fa-solid fa-credit-card"></i> Bayar Sekarang
                </a>
            <?php endif; ?>
        </div>

        <!-- Detail Panel -->
        <div class="detail-panel" id="detail-<?= $id_psn ?>">
            <?php
            $detail = mysqli_query($conn, "
                SELECT detail_pesanan.*, produk.nama_produk, produk.gambar
                FROM detail_pesanan
                JOIN produk ON detail_pesanan.id_produk = produk.id_produk
                WHERE detail_pesanan.id_pesanan = $id_psn
            ");
            ?>



            <?php if (!empty($row['alamat_pengiriman'])): ?>
            <div class="detail-section-title">Alamat Pengiriman</div>
            <div class="detail-address">
                <i class="fa-solid fa-location-dot"></i>
                <?= htmlspecialchars($row['alamat_pengiriman']) ?>
            </div>
            <?php endif; ?>

            <div class="detail-section-title">Rincian Produk</div>
            <table class="detail-table">
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Produk</th>
                        <th>Harga Satuan</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = mysqli_fetch_assoc($detail)): ?>
                    <tr>
                        <td>
                            <?php if (!empty($d['gambar'])): ?>
                                <img src="gambar/<?= htmlspecialchars($d['gambar']) ?>" alt="">
                            <?php else: ?>
                                <span style="color:var(--gray-500);">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($d['nama_produk']) ?></td>
                        <td>Rp <?= number_format($d['harga_satuan']) ?></td>
                        <td><?= (int)$d['jumlah'] ?></td>
                        <td class="subtotal">Rp <?= number_format($d['total_harga']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </div>
    <?php endwhile; ?>
    </div>

    <?php endif; ?>
</div>

<script>
    function toggleDetail(id) {
        const panel = document.getElementById('detail-' + id);
        panel.classList.toggle('open');

        // Update button text
        const btn = panel.closest('.pesanan-card').querySelector('.btn-detail');
        if (panel.classList.contains('open')) {
            btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i> Sembunyikan';
        } else {
            btn.innerHTML = '<i class="fa-solid fa-eye"></i> Lihat Detail';
        }
    }
</script>

</body>
</html>