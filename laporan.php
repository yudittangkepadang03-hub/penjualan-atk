<?php
session_start();
include 'koneksi.php';

// CEK LOGIN & ROLE ADMIN
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') != 'admin') {
    header("Location: form_login.php");
    exit;
}

// AMBIL FILTER DARI FORM
$filter_bulan     = $_GET['bulan']     ?? '';
$filter_tahun     = $_GET['tahun']     ?? '';
$filter_tgl_mulai = $_GET['tgl_mulai'] ?? '';
$filter_tgl_akhir = $_GET['tgl_akhir'] ?? '';

// BUILD WHERE CLAUSE
$where = "WHERE 1=1";
if ($filter_bulan)     $where .= " AND MONTH(pesanan.tanggal) = '$filter_bulan'";
if ($filter_tahun)     $where .= " AND YEAR(pesanan.tanggal)  = '$filter_tahun'";
if ($filter_tgl_mulai) $where .= " AND DATE(pesanan.tanggal) >= '$filter_tgl_mulai'";
if ($filter_tgl_akhir) $where .= " AND DATE(pesanan.tanggal) <= '$filter_tgl_akhir'";

// AMBIL DATA
$query = mysqli_query($conn, "
    SELECT pesanan.*, users.nama
    FROM pesanan
    JOIN users ON pesanan.id_user = users.id_user
    $where
    ORDER BY pesanan.tanggal DESC
");

// HITUNG TOTAL
$total_all = 0;
$rows = [];
while ($r = mysqli_fetch_assoc($query)) {
    $total_all += $r['total'];
    $rows[] = $r;
}

// AMBIL DAFTAR TAHUN
$tahun_query = mysqli_query($conn, "SELECT DISTINCT YEAR(tanggal) as tahun FROM pesanan ORDER BY tahun DESC");

$bulan_list = [
    1=>'Januari', 2=>'Februari', 3=>'Maret',    4=>'April',
    5=>'Mei',     6=>'Juni',     7=>'Juli',      8=>'Agustus',
    9=>'September',10=>'Oktober',11=>'November', 12=>'Desember'
];

// JUDUL PERIODE
$judul_filter = "Semua Periode";
if ($filter_bulan && $filter_tahun)
    $judul_filter = $bulan_list[$filter_bulan] . " " . $filter_tahun;
elseif ($filter_bulan)
    $judul_filter = $bulan_list[$filter_bulan];
elseif ($filter_tahun)
    $judul_filter = "Tahun " . $filter_tahun;
elseif ($filter_tgl_mulai && $filter_tgl_akhir)
    $judul_filter = $filter_tgl_mulai . " s/d " . $filter_tgl_akhir;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        .filter-box {
            background: #fff;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
        }
        .filter-group { display: flex; flex-direction: column; gap: 4px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: #6b7280; }
        .filter-group select,
        .filter-group input[type="date"] {
            padding: 7px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            background: #f9fafb;
        }
        .btn-filter {
            padding: 8px 16px; background: #2563eb; color: #fff;
            border: none; border-radius: 6px; cursor: pointer; font-size: 13px;
        }
        .btn-filter:hover { background: #1d4ed8; }
        .btn-reset {
            padding: 8px 16px; background: #6b7280; color: #fff;
            border: none; border-radius: 6px; cursor: pointer;
            font-size: 13px; text-decoration: none; display: inline-block;
        }
        .btn-reset:hover { background: #4b5563; }
        .top-actions { display: flex; gap: 12px; align-items: center; margin-bottom: 12px; }
        .btn-print {
            padding: 8px 14px; background: #16a34a; color: #fff;
            border-radius: 6px; text-decoration: none; font-size: 13px;
            border: none; cursor: pointer; display: inline-block;
        }
        .btn-print:hover { background: #15803d; }
        .filter-info {
            background: #eff6ff; border-left: 4px solid #2563eb;
            padding: 8px 14px; border-radius: 6px;
            font-size: 13px; color: #1e40af; margin-bottom: 12px;
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            /* Sembunyikan elemen tidak perlu */
            .sidebar,
            .no-print,
            .filter-box,
            .filter-info,
            .top-actions     { display: none !important; }

            /* Reset layout */
            body  { margin: 0; padding: 0; background: #fff; }
            .main { margin: 0; padding: 20px; width: 100%; }

            /* KOP SURAT muncul saat print */
            .kop-print       { display: block !important; }

            /* Tabel full width */
            table {
                width: 100%;
                font-size: 11px;
                border-collapse: collapse;
            }
            table th {
                background: #1e3a5f !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 7px;
            }
            table td { padding: 6px 7px; border-bottom: 1px solid #ddd; }
            table tr:nth-child(even) td {
                background: #f3f4f6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            /* Sembunyikan kolom Detail */
            .col-detail { display: none !important; }

            /* Info total print */
            .print-info { display: block !important; }
        }

        /* Sembunyikan elemen khusus print di layar */
        .kop-print  { display: none; }
        .print-info { display: none; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<?php include 'sidebar_admin.php'; ?>

<!-- MAIN -->
<div class="main">

    <!-- KOP SURAT (hanya muncul saat print) -->
    <div class="kop-print" style="text-align:center; border-bottom:3px double #000; padding-bottom:10px; margin-bottom:14px;">
        <h2 style="font-size:18px; margin:0;">LAPORAN PENJUALAN</h2>
        <p style="font-size:11px; color:#555; margin:4px 0 0;">
            Periode: <?php echo $judul_filter; ?> &nbsp;|&nbsp;
            Dicetak: <?php echo date('d/m/Y H:i'); ?> WIB &nbsp;|&nbsp;
            Admin: <?php echo $_SESSION['nama']; ?>
        </p>
    </div>

    <!-- INFO RINGKAS (hanya muncul saat print) -->
    <div class="print-info" style="margin-bottom:10px; font-size:11px;">
        Total Transaksi: <strong><?php echo count($rows); ?> pesanan</strong> &nbsp;|&nbsp;
        Total Pendapatan: <strong>Rp <?php echo number_format($total_all); ?></strong>
    </div>

    <h1 class="no-print">Laporan Penjualan</h1>

    <!-- FORM FILTER -->
    <form method="GET" action="laporan.php">
        <div class="filter-box no-print">
            <div class="filter-group">
                <label><i class="fa-solid fa-calendar-days"></i> Bulan</label>
                <select name="bulan">
                    <option value="">-- Semua Bulan --</option>
                    <?php foreach($bulan_list as $num => $nama_bulan):
                        $selected = ($filter_bulan == $num) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $num; ?>" <?php echo $selected; ?>>
                        <?php echo $nama_bulan; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><i class="fa-solid fa-calendar"></i> Tahun</label>
                <select name="tahun">
                    <option value="">-- Semua Tahun --</option>
                    <?php while($ty = mysqli_fetch_assoc($tahun_query)):
                        $selected = ($filter_tahun == $ty['tahun']) ? 'selected' : '';
                    ?>
                    <option value="<?php echo $ty['tahun']; ?>" <?php echo $selected; ?>>
                        <?php echo $ty['tahun']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group">
                <label><i class="fa-solid fa-calendar-week"></i> Dari Tanggal</label>
                <input type="date" name="tgl_mulai" value="<?php echo $filter_tgl_mulai; ?>">
            </div>

            <div class="filter-group">
                <label><i class="fa-solid fa-calendar-week"></i> Sampai Tanggal</label>
                <input type="date" name="tgl_akhir" value="<?php echo $filter_tgl_akhir; ?>">
            </div>

            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-magnifying-glass"></i> Tampilkan
            </button>
            <a href="laporan.php" class="btn-reset">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        </div>
    </form>

    <!-- INFO FILTER AKTIF -->
    <?php
    $info = [];
    if($filter_bulan)     $info[] = "Bulan: <strong>" . $bulan_list[$filter_bulan] . "</strong>";
    if($filter_tahun)     $info[] = "Tahun: <strong>$filter_tahun</strong>";
    if($filter_tgl_mulai) $info[] = "Dari: <strong>$filter_tgl_mulai</strong>";
    if($filter_tgl_akhir) $info[] = "Sampai: <strong>$filter_tgl_akhir</strong>";
    if(!empty($info)):
    ?>
    <div class="filter-info no-print">
        <i class="fa-solid fa-filter"></i> Filter aktif: <?php echo implode(' | ', $info); ?>
        &nbsp;— <?php echo count($rows); ?> data ditemukan
    </div>
    <?php endif; ?>

    <!-- TOP ACTIONS -->
    <div class="top-actions no-print">
        <button class="btn-print" onclick="window.print()">
            <i class="fa-solid fa-file-pdf"></i> Cetak PDF
        </button>
        <div style="color:#6b7280; font-size:13px;">
            Total record: <strong><?php echo count($rows); ?></strong>
        </div>
    </div>

    <!-- TABEL LAPORAN -->
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Total</th>
                <th class="no-print col-detail">Detail</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($rows)): $no = 1; foreach($rows as $r): ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($r['nama']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($r['tanggal'])); ?></td>
                <td><?php echo $r['status_psn']; ?></td>
                <td>Rp <?php echo number_format($r['total']); ?></td>
                <td class="no-print col-detail">
                    <a href="detail_pesanan.php?id=<?php echo $r['id_pesanan']; ?>" class="btn-edit">
                        <i class="fa-solid fa-eye"></i> Detail
                    </a>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
                <td colspan="6" style="text-align:center; color:#6b7280; padding:16px;">
                    Tidak ada data pesanan.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right"><strong>Total:</strong></td>
                <td><strong>Rp <?php echo number_format($total_all); ?></strong></td>
                <td class="no-print col-detail"></td>
            </tr>
        </tfoot>
    </table>

    <!-- TANDA TANGAN (hanya muncul saat print) -->
    <div class="kop-print" style="margin-top:40px; text-align:right; font-size:11px;">
        <div style="display:inline-block; text-align:center; width:160px;">
            <p>Admin,</p>
            <div style="margin-top:50px; border-top:1px solid #000; padding-top:4px;">
                <strong><?php echo $_SESSION['nama']; ?></strong>
            </div>
        </div>
    </div>

</div>
</body>
</html>