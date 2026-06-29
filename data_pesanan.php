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
    SELECT pesanan.*, users.nama
    FROM pesanan
    JOIN users ON pesanan.id_user = users.id_user
    $where_sql
    ORDER BY pesanan.id_pesanan DESC
");

$next_status = [
    'pending'  => 'diproses',
    'diproses' => 'dikemas',
    'dikemas'  => 'dikirim',
    'dikirim'  => 'selesai',
];
$badge_class = [
    'pending'  => 'badge-menunggu',
    'diproses' => 'badge-diproses',
    'dikemas'  => 'badge-dikemas',
    'dikirim'  => 'badge-dikirim',
    'selesai'  => 'badge-selesai',
];
$status_icon = [
    'pending'  => 'fa-hourglass-half',
    'diproses' => 'fa-gear',
    'dikemas'  => 'fa-box',
    'dikirim'  => 'fa-truck',
    'selesai'  => 'fa-circle-check',
];

// Hitung total per status untuk summary
$summary = [];
$sum_q = mysqli_query($conn, "SELECT status_psn, COUNT(*) as jml FROM pesanan GROUP BY status_psn");
while ($s = mysqli_fetch_assoc($sum_q)) {
    $summary[$s['status_psn']] = $s['jml'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Pesanan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        /* === SUMMARY CARDS === */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .summary-card {
            background: #fff;
            border-radius: 14px;
            padding: 16px 18px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 14px;
            cursor: pointer;
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
            transition: border-color .2s, transform .15s;
        }
        .summary-card:hover, .summary-card.active { border-color: currentColor; transform: translateY(-2px); }
        .summary-card .sc-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .summary-card .sc-info { line-height: 1.3; }
        .summary-card .sc-num { font-size: 22px; font-weight: 800; }
        .summary-card .sc-label { font-size: 12px; color: #6b7280; font-weight: 500; }

        .sc-pending  .sc-icon { background:#fff3e0; color:#ff9f43; }
        .sc-pending  .sc-num  { color:#ff9f43; }
        .sc-diproses .sc-icon { background:#f3e8ff; color:#8b5cf6; }
        .sc-diproses .sc-num  { color:#8b5cf6; }
        .sc-dikemas  .sc-icon { background:#e0f2fe; color:#0ea5e9; }
        .sc-dikemas  .sc-num  { color:#0ea5e9; }
        .sc-dikirim  .sc-icon { background:#dcfce7; color:#22c55e; }
        .sc-dikirim  .sc-num  { color:#22c55e; }
        .sc-selesai  .sc-icon { background:#d1fae5; color:#059669; }
        .sc-selesai  .sc-num  { color:#059669; }

        /* === TOOLBAR === */
        .toolbar {
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
            align-items: center;
        }
        .search-box {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 0 14px;
            gap: 8px;
            flex: 1;
            min-width: 220px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .search-box i { color: #9ca3af; }
        .search-box input {
            border: none; outline: none;
            padding: 10px 0;
            font-size: 14px;
            width: 100%;
            background: transparent;
        }
        .filter-select {
            padding: 10px 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
            color: #374151;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .btn-reset {
            padding: 10px 16px;
            border-radius: 10px;
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-reset:hover { background: #e5e7eb; }

        /* === TABLE === */
        .table-wrap {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .table-wrap table {
            width: 100%;
            border-collapse: collapse;
        }
        .table-wrap thead {
            background: linear-gradient(135deg, #5b73e8, #a29bfe);
            color: #fff;
        }
        .table-wrap th {
            padding: 14px 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.03em;
        }
        .table-wrap td {
            padding: 14px 16px;
            font-size: 14px;
            color: #374151;
            border-bottom: 1px solid #f0f0f5;
            vertical-align: middle;
        }
        .table-wrap tr:last-child td { border-bottom: none; }
        .table-wrap tbody tr:hover { background: #f8f9ff; }

        .order-id { font-weight: 700; color: #5b73e8; }
        .customer-name { font-weight: 600; color: #1e1e2f; }
        .amount { font-weight: 700; color: #1e1e2f; }

        /* Badge status */
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 5px 12px; border-radius: 999px;
            font-size: 12px; font-weight: 700; text-transform: capitalize;
        }
        .badge-menunggu { background:#fff3e0; color:#b75d00; }
        .badge-diproses { background:#f3e8ff; color:#6d28d9; }
        .badge-dikemas  { background:#e0f2fe; color:#0369a1; }
        .badge-dikirim  { background:#dcfce7; color:#166534; }
        .badge-selesai  { background:#d1fae5; color:#065f46; }

        /* Tombol aksi */
        .kelola { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-detail {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 14px; border-radius: 8px;
            background: #eff6ff; color: #2563eb;
            text-decoration: none; font-size: 13px; font-weight: 600;
            transition: background .2s;
        }
        .btn-detail:hover { background: #dbeafe; }
        .btn-status {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 7px 14px; border-radius: 8px;
            background: linear-gradient(135deg, #5b73e8, #a29bfe);
            color: #fff; text-decoration: none;
            font-size: 13px; font-weight: 600;
            transition: opacity .2s;
        }
        .btn-status:hover { opacity: .85; }

        .empty-state {
            text-align: center; padding: 60px 20px; color: #9ca3af;
        }
        .empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
        .empty-state p { font-size: 15px; }

        /* === PAGE HEADER + TOMBOL KELOLA PESANAN === */
        .page-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }
        .btn-kelola-pesanan {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 10px;
            background: linear-gradient(135deg, #5b73e8, #a29bfe);
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            box-shadow: 0 6px 16px rgba(91,115,232,0.3);
            transition: opacity .2s, transform .15s;
            white-space: nowrap;
        }
        .btn-kelola-pesanan:hover { opacity: .9; transform: translateY(-1px); }
    </style>
</head>
<body>

<?php include 'sidebar_admin.php'; ?>

<div class="main">
    <div class="page-header page-header-row">
        <div>
            <h1><i class="fa-solid fa-receipt"></i> Data Pesanan</h1>
            <p>Kelola dan pantau semua pesanan pelanggan.</p>
        </div>
        <a href="kelola_pesanan.php" class="btn-kelola-pesanan">
            <i class="fa-solid fa-clipboard-list"></i> Kelola Pesanan
        </a>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="summary-grid">
        <?php
        $sc_list = [
            'pending'  => ['label' => 'Pending',  'icon' => 'fa-hourglass-half'],
            'diproses' => ['label' => 'Diproses', 'icon' => 'fa-gear'],
            'dikemas'  => ['label' => 'Dikemas',  'icon' => 'fa-box'],
            'dikirim'  => ['label' => 'Dikirim',  'icon' => 'fa-truck'],
            'selesai'  => ['label' => 'Selesai',  'icon' => 'fa-circle-check'],
        ];
        foreach ($sc_list as $key => $sc) {
            $jml    = $summary[$key] ?? 0;
            $active = ($filter_status === $key) ? 'active' : '';
        ?>
        <a href="?status=<?php echo $key; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>"
           class="summary-card sc-<?php echo $key; ?> <?php echo $active; ?>">
            <div class="sc-icon"><i class="fa-solid <?php echo $sc['icon']; ?>"></i></div>
            <div class="sc-info">
                <div class="sc-num"><?php echo $jml; ?></div>
                <div class="sc-label"><?php echo $sc['label']; ?></div>
            </div>
        </a>
        <?php } ?>
    </div>

    <!-- TOOLBAR -->
    <form method="GET" class="toolbar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Cari nama pelanggan / ID pesanan..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <?php foreach ($sc_list as $key => $sc) { ?>
                <option value="<?php echo $key; ?>" <?php echo $filter_status === $key ? 'selected' : ''; ?>>
                    <?php echo $sc['label']; ?>
                </option>
            <?php } ?>
        </select>
        <button type="submit" class="btn-status" style="border:none;cursor:pointer;padding:10px 18px;">
            <i class="fa-solid fa-magnifying-glass"></i> Cari
        </button>
        <?php if ($search || $filter_status) { ?>
            <a href="data_pesanan.php" class="btn-reset">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        <?php } ?>
    </form>

    <!-- TABEL -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>ID Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            $has_data = false;
            while ($row = mysqli_fetch_assoc($query)):
                $has_data = true;
                $status = $row['status_psn'];
                $class  = $badge_class[$status] ?? 'badge-menunggu';
                $icon   = $status_icon[$status] ?? 'fa-circle';
            ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><span class="order-id">#<?php echo $row['id_pesanan']; ?></span></td>
                    <td><span class="customer-name"><?php echo htmlspecialchars($row['nama']); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($row['tanggal'])); ?></td>
                    <td><span class="amount">Rp <?php echo number_format($row['total'], 0, ',', '.'); ?></span></td>
                    <td>
                        <span class="badge <?php echo $class; ?>">
                            <i class="fa-solid <?php echo $icon; ?>"></i>
                            <?php echo ucfirst($status); ?>
                        </span>
                    </td>
                    <td>
                        <div class="kelola">
                            <a href="detail_pesanan.php?id=<?php echo $row['id_pesanan']; ?>" class="btn-detail">
                                <i class="fa-solid fa-eye"></i> Detail
                            </a>
                            <?php if (isset($next_status[$status])): $label = $next_status[$status]; ?>
                                <a href="update_status.php?id=<?php echo $row['id_pesanan']; ?>&status=<?php echo $label; ?>"
                                   class="btn-status"
                                   onclick="return confirm('Ubah status ke <?php echo $label; ?>?')">
                                    <i class="fa-solid fa-arrow-right"></i> <?php echo ucfirst($label); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$has_data): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fa-solid fa-box-open"></i>
                            <p>Tidak ada pesanan<?php echo ($search || $filter_status) ? ' yang sesuai filter.' : ' saat ini.'; ?></p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>