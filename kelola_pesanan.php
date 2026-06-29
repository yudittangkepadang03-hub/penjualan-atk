<?php
session_start();
include 'koneksi.php';

// CEK LOGIN & ROLE ADMIN
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}
$id_user    = intval($_SESSION['id_user']);
$user_query = mysqli_query($conn, "SELECT role FROM users WHERE id_user = '$id_user'");
$user_data  = mysqli_fetch_assoc($user_query);
if ($user_data['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$msg_type = '';
$msg_text = '';

// PROSES UPDATE STATUS PESANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id_pesanan  = intval($_POST['id_pesanan']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);
    $valid       = ['pending', 'diproses', 'dikirim', 'selesai'];
    if (in_array($status_baru, $valid)) {
        mysqli_query($conn, "UPDATE pesanan SET status_psn = '$status_baru' WHERE id_pesanan = $id_pesanan");
        $msg_type = 'success';
        $msg_text = "Status pesanan #$id_pesanan berhasil diubah menjadi <b>" . ucfirst($status_baru) . "</b>.";
    }
}

// PROSES VERIFIKASI PEMBAYARAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verif_bayar'])) {
    $id_pesanan = intval($_POST['id_pesanan']);
    $aksi       = $_POST['aksi'];

    if ($aksi === 'terima') {
        mysqli_query($conn, "UPDATE pesanan SET status_pembayaran = 'diterima', status_psn = 'diproses' WHERE id_pesanan = $id_pesanan");
        $msg_type = 'success';
        $msg_text = "Pembayaran pesanan #$id_pesanan <b>diterima</b>. Status pesanan diubah ke Diproses.";
    } elseif ($aksi === 'tolak') {
        mysqli_query($conn, "UPDATE pesanan SET status_pembayaran = 'ditolak' WHERE id_pesanan = $id_pesanan");
        $msg_type = 'error';
        $msg_text = "Pembayaran pesanan #$id_pesanan <b>ditolak</b>. Pembeli akan diminta upload ulang.";
    }
}

// FILTER & SEARCH
$filter_status = $_GET['status'] ?? '';
$search        = trim($_GET['search'] ?? '');

$where = [];
if ($filter_status !== '') {
    $fs      = mysqli_real_escape_string($conn, $filter_status);
    $where[] = "pesanan.status_psn = '$fs'";
}
if ($search !== '') {
    $s       = mysqli_real_escape_string($conn, $search);
    $where[] = "(users.nama LIKE '%$s%' OR pesanan.id_pesanan LIKE '%$s%')";
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$query = mysqli_query($conn, "
    SELECT
        pesanan.*,
        users.nama,
        users.email,
        SUM(detail_pesanan.jumlah) AS total_barang
    FROM pesanan
    LEFT JOIN users ON pesanan.id_user = users.id_user
    LEFT JOIN detail_pesanan ON pesanan.id_pesanan = detail_pesanan.id_pesanan
    $where_sql
    GROUP BY pesanan.id_pesanan
    ORDER BY pesanan.id_pesanan DESC
");

// Summary count per status
$summary = [];
$sum_q   = mysqli_query($conn, "SELECT status_psn, COUNT(*) as jml FROM pesanan GROUP BY status_psn");
while ($s = mysqli_fetch_assoc($sum_q)) { $summary[$s['status_psn']] = $s['jml']; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- CSS yang sama dengan dashboard -->
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        /* ── PAGE HEADER + TOMBOL KEMBALI ── */
        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }
        .btn-kembali {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 10px;
            background: #fff;
            border: 1.5px solid #e2e2ea;
            color: #2d2d65;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            transition: background .15s, border-color .15s;
            white-space: nowrap;
        }
        .btn-kembali:hover { background: #f5f5fb; border-color: #2d2d65; }

        /* ── SUMMARY CARDS ── */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            background: #fff;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
            border: 1.5px solid #ececf1;
            transition: border-color 0.2s, transform 0.15s;
            box-shadow: none;
        }

        .summary-card:hover { transform: translateY(-2px); border-color: #c7c7e0; }
        .summary-card.active { border-color: #2d2d65; }

        .sc-icon {
            width: 42px; height: 42px;
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.05rem; flex-shrink: 0;
        }

        .sc-num   { font-size: 1.4rem; font-weight: 800; line-height: 1; color: #1a1a2e; }
        .sc-label { font-size: 0.72rem; color: #9ca0ab; font-weight: 500; margin-top: 3px; }

        .sc-pending  .sc-icon { background: #fdf0e3; color: #d97706; }
        .sc-diproses .sc-icon { background: #f1edfb; color: #6d28d9; }
        .sc-dikirim  .sc-icon { background: #e6f9ef; color: #15803d; }
        .sc-selesai  .sc-icon { background: #e3f6ef; color: #047857; }
        .sc-menunggu .sc-icon { background: #fef9e7; color: #b45309; }

        /* ── ALERT ── */
        .alert {
            display: flex; align-items: center; gap: 10px;
            border-radius: 12px;
            padding: 0.9rem 1.2rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            border: 1.5px solid transparent;
        }
        .alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #16a34a; }
        .alert-error   { background: #fef2f2; border-color: #fecaca; color: #dc2626; }

        /* ── TOOLBAR ── */
        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            display: flex; align-items: center; gap: 8px;
            background: #fff;
            border: 1.5px solid #ececf1;
            border-radius: 10px;
            padding: 0 14px;
            flex: 1; min-width: 220px;
            transition: border-color 0.15s;
        }
        .search-box:focus-within { border-color: #2d2d65; }
        .search-box i { color: #9ca0ab; font-size: 0.875rem; }
        .search-box input {
            border: none; outline: none;
            padding: 0.65rem 0;
            font-size: 0.875rem;
            background: transparent;
            width: 100%;
            color: #2D3748;
        }

        .filter-select {
            padding: 0.65rem 1rem;
            border: 1.5px solid #ececf1;
            border-radius: 10px;
            font-size: 0.875rem;
            background: #fff;
            color: #2D3748;
            cursor: pointer;
        }

        .btn-search {
            display: inline-flex; align-items: center; gap: 6px;
            background: linear-gradient(135deg, #5b73e8, #a29bfe);
            color: #fff;
            border: none; border-radius: 10px;
            padding: 0.65rem 1.1rem;
            font-size: 0.875rem; font-weight: 600;
            cursor: pointer; transition: opacity 0.15s;
        }
        .btn-search:hover { opacity: .88; }

        .btn-reset {
            display: inline-flex; align-items: center; gap: 6px;
            background: #f3f4f6; color: #374151;
            border: none; border-radius: 10px;
            padding: 0.65rem 1rem;
            font-size: 0.875rem; font-weight: 600;
            text-decoration: none; cursor: pointer;
            transition: background 0.15s;
        }
        .btn-reset:hover { background: #e5e7eb; }

        /* ── ORDER CARD ── */
        .pesanan-list { display: flex; flex-direction: column; gap: 1rem; }

        .order-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #ececf1;
            overflow: hidden;
            box-shadow: none;
            transition: border-color 0.2s;
        }
        .order-card:hover { border-color: #d8d8ea; }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.4rem;
            border-bottom: 1px solid #F0F2F5;
            gap: 1rem; flex-wrap: wrap;
        }
        .card-header-left { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .order-num { font-size: 0.8rem; color: #9ca0ab; }
        .order-num span { font-weight: 700; color: #2d2d65; font-size: 0.95rem; }
        .customer-info { font-size: 0.82rem; color: #9ca0ab; display: flex; gap: 14px; flex-wrap: wrap; }
        .customer-info i { width: 14px; }

        .badge {
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.73rem;
            font-weight: 700;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .badge-pending  { background: #fdf0e3; color: #b45309; }
        .badge-diproses { background: #f1edfb; color: #6d28d9; }
        .badge-dikirim  { background: #e6f9ef; color: #15803d; }
        .badge-selesai  { background: #e3f6ef; color: #047857; }

        /* Progress Steps */
        .progress-wrap {
            padding: 1.1rem 1.4rem;
            border-bottom: 1px solid #F0F2F5;
        }
        .progress-steps { display: flex; align-items: center; }

        .step {
            display: flex; flex-direction: column;
            align-items: center; gap: 5px;
            flex: 1; position: relative;
        }
        .step-circle {
            width: 36px; height: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700;
            border: 2px solid #ececf1;
            background: #fff; color: #9ca0ab;
            position: relative; z-index: 1;
            transition: all 0.2s;
        }
        .step.done   .step-circle { background: #2d2d65; border-color: #2d2d65; color: #fff; }
        .step.active .step-circle { background: #5b73e8; border-color: #5b73e8; color: #fff; box-shadow: 0 0 0 4px rgba(91,115,232,0.18); }

        .step-label {
            font-size: 0.68rem; font-weight: 600;
            color: #9ca0ab; text-align: center; white-space: nowrap;
        }
        .step.done   .step-label { color: #2d2d65; }
        .step.active .step-label { color: #5b73e8; }

        .step-line {
            flex: 1; height: 2px;
            background: #ececf1;
            margin-bottom: 20px;
        }
        .step-line.done { background: #2d2d65; }

        /* Card Body */
        .card-body {
            padding: 1rem 1.4rem;
            display: flex; gap: 2rem; flex-wrap: wrap;
            border-bottom: 1px solid #F0F2F5;
        }
        .info-group { display: flex; flex-direction: column; gap: 6px; }
        .info-row {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.82rem; color: #2D3748;
        }
        .info-row i { width: 14px; color: #9ca0ab; font-size: 0.75rem; }
        .info-row .lbl { color: #9ca0ab; min-width: 120px; }

        .total-box { margin-left: auto; text-align: right; }
        .total-box .lbl { font-size: 0.72rem; color: #000000; }
        .total-box .amt { font-size: 1.25rem; font-weight: 800; color: #000000; }

        /* Bukti Pembayaran */
        .bukti-section {
            padding: 1rem 1.4rem;
            border-bottom: 1px solid #F0F2F5;
            background: #fafafc;
        }
        .bukti-header {
            font-size: 0.74rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: #9ca0ab; margin-bottom: 0.75rem;
            display: flex; align-items: center; gap: 6px;
        }
        .bukti-inner { display: flex; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
        .bukti-img {
            width: 90px; height: 90px;
            object-fit: cover; border-radius: 10px;
            border: 1px solid #ececf1;
            cursor: pointer; transition: transform 0.2s;
        }
        .bukti-img:hover { transform: scale(1.04); }

        .bukti-actions { display: flex; flex-direction: column; gap: 8px; }

        .btn-terima {
            display: inline-flex; align-items: center; gap: 6px;
            background: #2d2d65; color: #fff;
            border: none; border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.82rem; font-weight: 700;
            cursor: pointer; transition: opacity 0.15s;
        }
        .btn-terima:hover { opacity: .88; }

        .btn-tolak {
            display: inline-flex; align-items: center; gap: 6px;
            background: #fef2f2; color: #dc2626;
            border: 1.5px solid #fecaca; border-radius: 8px;
            padding: 0.5rem 1rem;
            font-size: 0.82rem; font-weight: 700;
            cursor: pointer; transition: background 0.15s, color 0.15s;
        }
        .btn-tolak:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

        .bukti-status {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.82rem; font-weight: 600;
            padding: 0.4rem 0.9rem; border-radius: 8px;
        }
        .bukti-status.diterima { background: #f0fdf4; color: #16a34a; }
        .bukti-status.ditolak  { background: #fef2f2; color: #dc2626; }
        .bukti-status.menunggu { background: #eef1fd; color: #2d2d65; }
        .no-bukti { font-size: 0.82rem; color: #9ca0ab; font-style: italic; }

        /* Card Actions */
        .card-actions {
            padding: 0.9rem 1.4rem;
            background: #fafafc;
            display: flex; align-items: center;
            gap: 8px; flex-wrap: wrap;
        }
        .action-label {
            font-size: 0.72rem; font-weight: 700;
            color: #9ca0ab; text-transform: uppercase;
            letter-spacing: 0.04em; margin-right: 4px;
        }
        .btn-step {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            border: 1.5px solid #ececf1;
            background: #fff;
            font-size: 0.8rem; font-weight: 600;
            color: #2D3748; cursor: pointer;
            transition: all 0.15s;
        }
        .btn-step:hover { border-color: #5b73e8; color: #5b73e8; }
        .btn-step.current { background: #2d2d65; border-color: #2d2d65; color: #fff; cursor: default; }

        .btn-detail-link {
            display: inline-flex; align-items: center; gap: 6px;
            background: #eef1fd; color: #2d2d65;
            border: 1.5px solid #d8def9;
            border-radius: 8px; padding: 0.45rem 0.9rem;
            font-size: 0.8rem; font-weight: 600;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            margin-left: auto;
        }
        .btn-detail-link:hover { background: #2d2d65; color: #fff; border-color: #2d2d65; }

        /* Empty State */
        .empty-state {
            text-align: center; padding: 4rem 2rem;
            color: #9ca0ab; background: #fff;
            border-radius: 16px; border: 1px solid #ececf1;
            box-shadow: none;
        }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; color: #ececf1; }

        /* Lightbox */
        .lightbox {
            display: none; position: fixed; inset: 0;
            background: rgba(20,20,35,0.8);
            z-index: 999; align-items: center; justify-content: center;
        }
        .lightbox.open { display: flex; }
        .lightbox img {
            max-width: 90vw; max-height: 85vh;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .lightbox-close {
            position: absolute; top: 1.5rem; right: 1.5rem;
            color: #fff; font-size: 1.5rem; cursor: pointer;
            background: rgba(255,255,255,0.15);
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }
    </style>
</head>
<body>

    <?php include 'sidebar_admin.php'; ?>

    <div class="main">

        <div class="page-header page-header-row">
            <div>
                <h1><i class="fa-solid fa-receipt"></i> Kelola Pesanan</h1>
                <p>Pantau, verifikasi pembayaran, dan update status semua pesanan pelanggan.</p>
            </div>
            <a href="data_pesanan.php" class="btn-kembali">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if ($msg_text): ?>
            <div class="alert alert-<?= $msg_type ?>">
                <i class="fa-solid fa-<?= $msg_type === 'success' ? 'circle-check' : 'circle-xmark' ?>"></i>
                <?= $msg_text ?>
            </div>
        <?php endif; ?>

        <!-- SUMMARY CARDS -->
        <?php
        $sc_list = [
            'pending'  => ['label' => 'Pending',  'icon' => 'fa-hourglass-half', 'cls' => 'sc-pending'],
            'diproses' => ['label' => 'Diproses', 'icon' => 'fa-gear',           'cls' => 'sc-diproses'],
            'dikirim'  => ['label' => 'Dikirim',  'icon' => 'fa-truck',          'cls' => 'sc-dikirim'],
            'selesai'  => ['label' => 'Selesai',  'icon' => 'fa-circle-check',   'cls' => 'sc-selesai'],
        ];
        $verif_q   = mysqli_query($conn, "SELECT COUNT(*) as jml FROM pesanan WHERE status_pembayaran = 'menunggu_verifikasi'");
        $verif_row = mysqli_fetch_assoc($verif_q);
        $verif_jml = $verif_row['jml'] ?? 0;
        ?>
        <div class="summary-grid">
            <?php foreach ($sc_list as $key => $sc):
                $jml    = $summary[$key] ?? 0;
                $active = ($filter_status === $key) ? 'active' : '';
            ?>
            <a href="?status=<?= $key ?><?= $search ? '&search='.urlencode($search) : '' ?>"
               class="summary-card <?= $sc['cls'] ?> <?= $active ?>">
                <div class="sc-icon"><i class="fa-solid <?= $sc['icon'] ?>"></i></div>
                <div>
                    <div class="sc-num"><?= $jml ?></div>
                    <div class="sc-label"><?= $sc['label'] ?></div>
                </div>
            </a>
            <?php endforeach; ?>
            <a href="?butuh_verif=1" class="summary-card sc-menunggu <?= isset($_GET['butuh_verif']) ? 'active' : '' ?>">
                <div class="sc-icon"><i class="fa-solid fa-clock"></i></div>
                <div>
                    <div class="sc-num"><?= $verif_jml ?></div>
                    <div class="sc-label">Butuh Verifikasi</div>
                </div>
            </a>
        </div>

        <!-- TOOLBAR -->
        <form method="GET" class="toolbar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="search" placeholder="Cari nama pelanggan / ID pesanan..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <select name="status" class="filter-select" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <?php foreach ($sc_list as $key => $sc): ?>
                    <option value="<?= $key ?>" <?= $filter_status === $key ? 'selected' : '' ?>><?= $sc['label'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
            <?php if ($search || $filter_status): ?>
                <a href="kelola_pesanan.php" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            <?php endif; ?>
        </form>

        <!-- DAFTAR PESANAN -->
        <?php
        $status_steps = ['pending', 'diproses', 'dikirim', 'selesai'];
        $step_labels  = ['Pending', 'Diproses', 'Dikirim', 'Selesai'];
        $step_icons   = ['fa-hourglass-half', 'fa-gear', 'fa-truck', 'fa-circle-check'];
        $badge_map = [
            'pending'  => ['class' => 'badge-pending',  'icon' => 'fa-hourglass-half', 'label' => 'Pending'],
            'diproses' => ['class' => 'badge-diproses', 'icon' => 'fa-gear',           'label' => 'Diproses'],
            'dikirim'  => ['class' => 'badge-dikirim',  'icon' => 'fa-truck',          'label' => 'Dikirim'],
            'selesai'  => ['class' => 'badge-selesai',  'icon' => 'fa-circle-check',   'label' => 'Selesai'],
        ];
        $has_data = false;
        ?>

        <div class="pesanan-list">
        <?php while ($row = mysqli_fetch_assoc($query)):
            $has_data   = true;
            $status     = $row['status_psn'] ?? 'pending';
            $status_byr = $row['status_pembayaran'] ?? 'belum_bayar';
            $cur_idx    = array_search($status, $status_steps);
            $bmap       = $badge_map[$status] ?? $badge_map['pending'];
        ?>

        <div class="order-card">

            <!-- Header -->
            <div class="card-header">
                <div class="card-header-left">
                    <div class="order-num">Order <span>#<?= str_pad($row['id_pesanan'], 4, '0', STR_PAD_LEFT) ?></span></div>
                    <div class="customer-info">
                        <span><i class="fa-solid fa-user"></i><?= htmlspecialchars($row['nama'] ?? '-') ?></span>
                        <span><i class="fa-regular fa-calendar"></i><?= date('d M Y, H:i', strtotime($row['tanggal'])) ?></span>
                        <span><i class="fa-solid fa-envelope"></i><?= htmlspecialchars($row['email'] ?? '-') ?></span>
                    </div>
                </div>
                <span class="badge <?= $bmap['class'] ?>">
                    <i class="fa-solid <?= $bmap['icon'] ?>"></i> <?= $bmap['label'] ?>
                </span>
            </div>

            <!-- Progress Steps -->
            <div class="progress-wrap">
                <div class="progress-steps">
                    <?php foreach ($status_steps as $i => $st):
                        $is_done   = ($i < $cur_idx);
                        $is_active = ($i === $cur_idx);
                        $cls = $is_done ? 'done' : ($is_active ? 'active' : '');
                    ?>
                        <div class="step <?= $cls ?>">
                            <div class="step-circle"><i class="fa-solid <?= $step_icons[$i] ?>"></i></div>
                            <div class="step-label"><?= $step_labels[$i] ?></div>
                        </div>
                        <?php if ($i < count($status_steps) - 1): ?>
                            <div class="step-line <?= $is_done ? 'done' : '' ?>"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Body Info -->
            <div class="card-body">
                <div class="info-group">
                    <div class="info-row">
                        <i class="fa-solid fa-wallet"></i>
                        <span class="lbl">Metode Bayar</span>
                        <span><?= htmlspecialchars($row['metode_pembayaran'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <i class="fa-solid fa-box"></i>
                        <span class="lbl">Total Barang</span>
                        <span><?= (int)($row['total_barang'] ?? 0) ?> item</span>
                    </div>
                    <div class="info-row">
                        <i class="fa-solid fa-location-dot"></i>
                        <span class="lbl">Alamat Kirim</span>
                        <span><?= htmlspecialchars($row['alamat_pengiriman'] ?? '-') ?></span>
                    </div>
                </div>
                <div class="total-box">
                    <div class="lbl">Total Pembayaran</div>
                    <div class="amt">Rp <?= number_format($row['total']) ?></div>
                </div>
            </div>

            <!-- Bukti Pembayaran -->
            <?php if ($row['metode_pembayaran'] !== 'COD'): ?>
            <div class="bukti-section">
                <div class="bukti-header"><i class="fa-solid fa-image"></i> Bukti Pembayaran</div>
                <div class="bukti-inner">
                    <?php if (!empty($row['bukti_pembayaran'])): ?>
                        <img class="bukti-img"
                             src="bukti_pembayaran/<?= htmlspecialchars($row['bukti_pembayaran']) ?>"
                             alt="Bukti"
                             onclick="openLightbox(this.src)">

                        <?php if ($status_byr === 'menunggu_verifikasi'): ?>
                            <div class="bukti-actions">
                                <div style="font-size:0.8rem;color:#9ca0ab;margin-bottom:4px;">
                                    <i class="fa-solid fa-circle-info"></i> Klik gambar untuk perbesar
                                </div>
                                <form method="POST" style="display:contents;">
                                    <input type="hidden" name="id_pesanan" value="<?= $row['id_pesanan'] ?>">
                                    <button type="submit" name="verif_bayar" value="1"
                                            onclick="this.form.aksi.value='terima'"
                                            class="btn-terima">
                                        <i class="fa-solid fa-check"></i> Terima Pembayaran
                                    </button>
                                    <button type="submit" name="verif_bayar" value="1"
                                            onclick="this.form.aksi.value='tolak';return confirm('Tolak pembayaran ini?')"
                                            class="btn-tolak">
                                        <i class="fa-solid fa-xmark"></i> Tolak
                                    </button>
                                    <input type="hidden" name="aksi" value="">
                                </form>
                            </div>
                        <?php elseif ($status_byr === 'diterima'): ?>
                            <span class="bukti-status diterima"><i class="fa-solid fa-circle-check"></i> Pembayaran Diterima</span>
                        <?php elseif ($status_byr === 'ditolak'): ?>
                            <span class="bukti-status ditolak"><i class="fa-solid fa-circle-xmark"></i> Pembayaran Ditolak</span>
                        <?php else: ?>
                            <span class="bukti-status menunggu"><i class="fa-solid fa-hourglass-half"></i> Menunggu Upload Bukti</span>
                        <?php endif; ?>

                    <?php else: ?>
                        <span class="no-bukti"><i class="fa-regular fa-image"></i> Belum ada bukti pembayaran diupload.</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Update Status + Detail -->
            <div class="card-actions">
                <span class="action-label">Update Status:</span>
                <form method="POST" style="display:contents;">
                    <input type="hidden" name="id_pesanan" value="<?= $row['id_pesanan'] ?>">
                    <input type="hidden" name="status_baru" value="">
                    <?php foreach ($status_steps as $i => $st):
                        $is_cur = ($st === $status);
                    ?>
                        <button type="submit" name="update_status" value="1"
                                class="btn-step <?= $is_cur ? 'current' : '' ?>"
                                <?= $is_cur ? 'disabled' : '' ?>
                                onclick="this.form.status_baru.value='<?= $st ?>'<?= !$is_cur ? ";return confirm('Ubah status ke ".ucfirst($st)."?')" : '' ?>">
                            <i class="fa-solid <?= $step_icons[$i] ?>"></i>
                            <?= $step_labels[$i] ?>
                        </button>
                    <?php endforeach; ?>
                </form>
                <a href="detail_pesanan.php?id=<?= $row['id_pesanan'] ?>" class="btn-detail-link">
                    <i class="fa-solid fa-eye"></i> Detail
                </a>
            </div>

        </div>
        <?php endwhile; ?>

        <?php if (!$has_data): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <p>Tidak ada pesanan<?= ($search || $filter_status) ? ' yang sesuai filter.' : ' saat ini.' ?></p>
            </div>
        <?php endif; ?>
        </div>

    </div><!-- /.main -->

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <div class="lightbox-close"><i class="fa-solid fa-xmark"></i></div>
    <img id="lightbox-img" src="" alt="Bukti Pembayaran">
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
</script>

</body>
</html>