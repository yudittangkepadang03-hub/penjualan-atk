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

// Validasi ID pesanan
if (!isset($_GET['id']) || !intval($_GET['id'])) {
    header("Location: kelola_pesanan.php");
    exit;
}
$id_pesanan = intval($_GET['id']);

$msg_type = '';
$msg_text = '';

// PROSES VERIFIKASI PEMBAYARAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verif_bayar'])) {
    $aksi = $_POST['aksi'];
    if ($aksi === 'terima') {
        mysqli_query($conn, "UPDATE pesanan SET status_pembayaran = 'diterima', status_psn = 'diproses' WHERE id_pesanan = $id_pesanan");
        $msg_type = 'success';
        $msg_text = "Pembayaran <b>diterima</b>. Status pesanan diubah ke Diproses.";
    } elseif ($aksi === 'tolak') {
        mysqli_query($conn, "UPDATE pesanan SET status_pembayaran = 'ditolak' WHERE id_pesanan = $id_pesanan");
        $msg_type = 'error';
        $msg_text = "Pembayaran <b>ditolak</b>. Pembeli akan diminta upload ulang.";
    }
}

// PROSES UPDATE STATUS PESANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);
    $valid = ['pending', 'diproses', 'dikirim', 'selesai'];
    if (in_array($status_baru, $valid)) {
        mysqli_query($conn, "UPDATE pesanan SET status_psn = '$status_baru' WHERE id_pesanan = $id_pesanan");
        $msg_type = 'success';
        $msg_text = "Status pesanan berhasil diubah menjadi <b>" . ucfirst($status_baru) . "</b>.";
    }
}

// AMBIL DATA PESANAN
$query = mysqli_query($conn, "
    SELECT pesanan.*, users.nama, users.email
    FROM pesanan
    LEFT JOIN users ON pesanan.id_user = users.id_user
    WHERE pesanan.id_pesanan = $id_pesanan
");
$pesanan = mysqli_fetch_assoc($query);

if (!$pesanan) {
    die("Pesanan tidak ditemukan.");
}

// AMBIL DETAIL PRODUK
$detail_query = mysqli_query($conn, "
    SELECT detail_pesanan.*, produk.nama_produk, produk.gambar
    FROM detail_pesanan
    LEFT JOIN produk ON detail_pesanan.id_produk = produk.id_produk
    WHERE detail_pesanan.id_pesanan = $id_pesanan
");

$status       = $pesanan['status_psn'] ?? 'pending';
$status_byr   = $pesanan['status_pembayaran'] ?? 'belum_bayar';

$status_steps = ['pending', 'diproses', 'dikirim', 'selesai'];
$step_labels  = ['Pending', 'Diproses', 'Dikirim', 'Selesai'];
$step_icons   = ['fa-hourglass-half', 'fa-gear', 'fa-truck', 'fa-circle-check'];
$cur_idx      = array_search($status, $status_steps);

$badge_map = [
    'pending'  => ['class' => 'badge-pending',  'icon' => 'fa-hourglass-half', 'label' => 'Pending'],
    'diproses' => ['class' => 'badge-diproses', 'icon' => 'fa-gear',           'label' => 'Diproses'],
    'dikirim'  => ['class' => 'badge-dikirim',  'icon' => 'fa-truck',          'label' => 'Dikirim'],
    'selesai'  => ['class' => 'badge-selesai',  'icon' => 'fa-circle-check',   'label' => 'Selesai'],
];
$bmap = $badge_map[$status] ?? $badge_map['pending'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pembayaran #<?= str_pad($id_pesanan, 4, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        /* PAGE HEADER */
        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 1.5rem;
        }

        .page-header-row h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2D3748;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 4px;
        }

        .page-header-row p {
            color: #9ca0ab;
            font-size: 0.875rem;
            margin: 0;
        }

        .btn-kembali-admin {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 10px;
            background: #fff;
            border: 1.5px solid #e2e2ea;
            color: #2d2d65;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 700;
            transition: background .15s, border-color .15s;
            white-space: nowrap;
        }
        .btn-kembali-admin:hover { background: #f5f5fb; border-color: #2d2d65; }

        /* ALERT */
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

        /* GRID LAYOUT */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 1.25rem;
            align-items: start;
        }

        @media (max-width: 900px) {
            .detail-grid { grid-template-columns: 1fr; }
        }

        /* CARD */
        .detail-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #ececf1;
            overflow: hidden;
        }

        .card-section-title {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #9ca0ab;
            padding: 1rem 1.4rem 0.5rem;
            display: flex;
            align-items: center;
            gap: 7px;
            border-bottom: 1px solid #F0F2F5;
        }

        /* INFO ROWS */
        .info-list { padding: 0.5rem 0; }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 0.75rem 1.4rem;
            font-size: 0.875rem;
            border-bottom: 1px solid #F0F2F5;
            gap: 1rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-row .lbl { color: #9ca0ab; white-space: nowrap; }
        .info-row .val { color: #2D3748; font-weight: 500; text-align: right; }

        /* BADGE */
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

        /* PROGRESS STEPS */
        .progress-wrap { padding: 1.1rem 1.4rem; border-bottom: 1px solid #F0F2F5; }
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
        .step-label { font-size: 0.68rem; font-weight: 600; color: #9ca0ab; text-align: center; white-space: nowrap; }
        .step.done   .step-label { color: #2d2d65; }
        .step.active .step-label { color: #5b73e8; }
        .step-line { flex: 1; height: 2px; background: #ececf1; margin-bottom: 20px; }
        .step-line.done { background: #2d2d65; }

        /* PRODUK TABLE */
        .produk-table {
            width: 100%;
            border-collapse: collapse;
        }
        .produk-table th {
            background: #fafafc;
            color: #9ca0ab;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 0.75rem 1.4rem;
            text-align: left;
            border-bottom: 1px solid #F0F2F5;
        }
        .produk-table td {
            padding: 0.85rem 1.4rem;
            font-size: 0.875rem;
            color: #2D3748;
            border-bottom: 1px solid #F0F2F5;
            vertical-align: middle;
        }
        .produk-table tr:last-child td { border-bottom: none; }
        .produk-table img {
            width: 48px; height: 48px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ececf1;
        }
        .produk-name { font-weight: 600; color: #2D3748; }

        /* TOTAL ROW */
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.4rem;
            background: #fafafc;
            border-top: 1px solid #F0F2F5;
        }
        .total-row .lbl { font-size: 0.875rem; color: #9ca0ab; font-weight: 600; }
        .total-row .amt { font-size: 1.2rem; font-weight: 800; color: #2d2d65; }

        /* BUKTI PEMBAYARAN */
        .bukti-section { padding: 1rem 1.4rem; }

        .bukti-img-wrap {
            margin-bottom: 1rem;
        }
        .bukti-img {
            width: 100%;
            max-height: 260px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #ececf1;
            cursor: pointer;
            transition: transform 0.2s;
            display: block;
        }
        .bukti-img:hover { transform: scale(1.02); }

        .bukti-hint {
            font-size: 0.72rem;
            color: #9ca0ab;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .bukti-status-box {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0.6rem 1rem;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .bukti-status-box.menunggu { background: #eef1fd; color: #2d2d65; }
        .bukti-status-box.diterima { background: #f0fdf4; color: #16a34a; }
        .bukti-status-box.ditolak  { background: #fef2f2; color: #dc2626; }

        .no-bukti {
            text-align: center;
            padding: 2rem 1rem;
            color: #9ca0ab;
            font-size: 0.875rem;
        }
        .no-bukti i { font-size: 2rem; display: block; margin-bottom: 0.5rem; color: #ececf1; }

        /* VERIF BUTTONS */
        .verif-actions { display: flex; flex-direction: column; gap: 8px; }

        .btn-terima {
            display: flex; align-items: center; justify-content: center; gap: 7px;
            background: #2d2d65; color: #fff;
            border: none; border-radius: 9px;
            padding: 0.65rem 1rem;
            font-size: 0.875rem; font-weight: 700;
            cursor: pointer; transition: opacity 0.15s;
            width: 100%;
        }
        .btn-terima:hover { opacity: .88; }

        .btn-tolak {
            display: flex; align-items: center; justify-content: center; gap: 7px;
            background: #fef2f2; color: #dc2626;
            border: 1.5px solid #fecaca; border-radius: 9px;
            padding: 0.65rem 1rem;
            font-size: 0.875rem; font-weight: 700;
            cursor: pointer; transition: background 0.15s, color 0.15s;
            width: 100%;
        }
        .btn-tolak:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

        /* UPDATE STATUS */
        .status-actions {
            padding: 1rem 1.4rem;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .status-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca0ab;
            margin-bottom: 4px;
        }

        .status-btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .btn-step {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            padding: 0.55rem 0.75rem;
            border-radius: 8px;
            border: 1.5px solid #ececf1;
            background: #fff;
            font-size: 0.8rem; font-weight: 600;
            color: #2D3748; cursor: pointer;
            transition: all 0.15s;
        }
        .btn-step:hover { border-color: #5b73e8; color: #5b73e8; }
        .btn-step.current { background: #2d2d65; border-color: #2d2d65; color: #fff; cursor: default; }

        /* LIGHTBOX */
        .lightbox {
            display: none; position: fixed; inset: 0;
            background: rgba(20,20,35,0.85);
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

    <!-- PAGE HEADER -->
    <div class="page-header-row">
        <div>
            <h1>
                <i class="fa-solid fa-credit-card"></i>
                Detail Pembayaran
                <span style="color:#9ca0ab;font-weight:400;font-size:1rem;">
                    #<?= str_pad($id_pesanan, 4, '0', STR_PAD_LEFT) ?>
                </span>
            </h1>
            <p>Verifikasi pembayaran dan kelola status pesanan pelanggan.</p>
        </div>
        <a href="kelola_pesanan.php" class="btn-kembali-admin">
            <i class="fa-solid fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- ALERT -->
    <?php if ($msg_text): ?>
        <div class="alert alert-<?= $msg_type ?>">
            <i class="fa-solid fa-<?= $msg_type === 'success' ? 'circle-check' : 'circle-xmark' ?>"></i>
            <?= $msg_text ?>
        </div>
    <?php endif; ?>

    <!-- GRID -->
    <div class="detail-grid">

        <!-- KOLOM KIRI -->
        <div style="display:flex; flex-direction:column; gap:1.25rem;">

            <!-- INFO PESANAN -->
            <div class="detail-card">
                <div class="card-section-title">
                    <i class="fa-solid fa-circle-info"></i> Informasi Pesanan
                </div>
                <div class="info-list">
                    <div class="info-row">
                        <span class="lbl">ID Pesanan</span>
                        <span class="val">#<?= str_pad($pesanan['id_pesanan'], 4, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="lbl">Tanggal</span>
                        <span class="val"><?= date('d M Y, H:i', strtotime($pesanan['tanggal'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="lbl">Pelanggan</span>
                        <span class="val"><?= htmlspecialchars($pesanan['nama'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="lbl">Email</span>
                        <span class="val"><?= htmlspecialchars($pesanan['email'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="lbl">Metode Pembayaran</span>
                        <span class="val"><?= htmlspecialchars($pesanan['metode_pembayaran'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="lbl">Alamat Pengiriman</span>
                        <span class="val" style="max-width:280px;"><?= htmlspecialchars($pesanan['alamat_pengiriman'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="lbl">Status Pesanan</span>
                        <span class="val">
                            <span class="badge <?= $bmap['class'] ?>">
                                <i class="fa-solid <?= $bmap['icon'] ?>"></i> <?= $bmap['label'] ?>
                            </span>
                        </span>
                    </div>
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

                <!-- Update Status -->
                <div class="status-actions">
                    <div class="status-label"><i class="fa-solid fa-pen"></i> Update Status Pesanan</div>
                    <form method="POST">
                        <input type="hidden" name="status_baru" value="">
                        <div class="status-btn-group">
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
                        </div>
                    </form>
                </div>
            </div>

            <!-- DETAIL PRODUK -->
            <div class="detail-card">
                <div class="card-section-title">
                    <i class="fa-solid fa-box"></i> Detail Produk
                </div>
                <table class="produk-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga Satuan</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = mysqli_fetch_assoc($detail_query)): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <?php if (!empty($item['gambar'])): ?>
                                        <img src="gambar/<?= htmlspecialchars($item['gambar']) ?>"
                                             alt="<?= htmlspecialchars($item['nama_produk']) ?>">
                                    <?php endif; ?>
                                    <span class="produk-name"><?= htmlspecialchars($item['nama_produk'] ?? '-') ?></span>
                                </div>
                            </td>
                            <td>Rp <?= number_format($item['harga_satuan']) ?></td>
                            <td><?= intval($item['jumlah']) ?></td>
                            <td>Rp <?= number_format($item['total_harga']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="total-row">
                    <span class="lbl">Total Pembayaran</span>
                    <span class="amt">Rp <?= number_format($pesanan['total']) ?></span>
                </div>
            </div>

        </div>

        <!-- KOLOM KANAN -->
        <div style="display:flex; flex-direction:column; gap:1.25rem;">

            <!-- BUKTI PEMBAYARAN -->
            <div class="detail-card">
                <div class="card-section-title">
                    <i class="fa-solid fa-image"></i> Bukti Pembayaran
                </div>
                <div class="bukti-section">

                    <?php if ($pesanan['metode_pembayaran'] === 'COD'): ?>
                        <div class="no-bukti">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                            Metode COD — pembayaran dilakukan saat barang diterima.
                        </div>

                    <?php elseif (!empty($pesanan['bukti_pembayaran'])): ?>

                        <!-- Status badge -->
                        <?php
                        $byr_map = [
                            'menunggu_verifikasi' => ['cls' => 'menunggu', 'icon' => 'fa-hourglass-half', 'label' => 'Menunggu Verifikasi'],
                            'diterima'            => ['cls' => 'diterima', 'icon' => 'fa-circle-check',   'label' => 'Pembayaran Diterima'],
                            'ditolak'             => ['cls' => 'ditolak',  'icon' => 'fa-circle-xmark',   'label' => 'Pembayaran Ditolak'],
                        ];
                        $byr = $byr_map[$status_byr] ?? ['cls' => 'menunggu', 'icon' => 'fa-hourglass-half', 'label' => 'Belum Diverifikasi'];
                        ?>
                        <div class="bukti-status-box <?= $byr['cls'] ?>">
                            <i class="fa-solid <?= $byr['icon'] ?>"></i>
                            <?= $byr['label'] ?>
                        </div>

                        <!-- Gambar bukti -->
                        <div class="bukti-img-wrap">
                            <img class="bukti-img"
                                 src="bukti_pembayaran/<?= htmlspecialchars($pesanan['bukti_pembayaran']) ?>"
                                 alt="Bukti Pembayaran"
                                 onclick="openLightbox(this.src)">
                            <div class="bukti-hint">
                                <i class="fa-solid fa-magnifying-glass-plus"></i>
                                Klik gambar untuk memperbesar
                            </div>
                        </div>

                        <!-- Tombol verifikasi jika masih menunggu -->
                        <?php if ($status_byr === 'menunggu_verifikasi'): ?>
                            <form method="POST" class="verif-actions">
                                <input type="hidden" name="aksi" value="">
                                <button type="submit" name="verif_bayar" value="1"
                                        class="btn-terima"
                                        onclick="this.form.aksi.value='terima'">
                                    <i class="fa-solid fa-check"></i> Terima Pembayaran
                                </button>
                                <button type="submit" name="verif_bayar" value="1"
                                        class="btn-tolak"
                                        onclick="this.form.aksi.value='tolak'; return confirm('Tolak pembayaran ini?')">
                                    <i class="fa-solid fa-xmark"></i> Tolak Pembayaran
                                </button>
                            </form>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="no-bukti">
                            <i class="fa-regular fa-image"></i>
                            Belum ada bukti pembayaran diupload.
                        </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div><!-- /.detail-grid -->

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