<?php
session_start();
include 'koneksi.php';

// CEK LOGIN & ROLE ADMIN
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = intval($_SESSION['id_user']);
$user_query = mysqli_query($conn, "SELECT role FROM users WHERE id_user = '$id_user'");
$user_data = mysqli_fetch_assoc($user_query);

if ($user_data['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// ===== STATISTIK UTAMA =====
$queryTotalPenjualan = mysqli_query($conn, "SELECT SUM(total) as total FROM pesanan");
$dataTotalPenjualan = mysqli_fetch_assoc($queryTotalPenjualan);
$totalPenjualan = $dataTotalPenjualan['total'] ?? 0;

$queryTotalPesanan = mysqli_query($conn, "SELECT COUNT(*) as jumlah FROM pesanan");
$dataTotalPesanan = mysqli_fetch_assoc($queryTotalPesanan);
$totalPesanan = $dataTotalPesanan['jumlah'] ?? 0;

$queryPesananPending = mysqli_query($conn, "SELECT COUNT(*) as jumlah FROM pesanan WHERE status_psn = 'pending'");
$dataPesananPending = mysqli_fetch_assoc($queryPesananPending);
$pesananPending = $dataPesananPending['jumlah'] ?? 0;

$queryTotalProduk = mysqli_query($conn, "SELECT COUNT(*) as jumlah FROM produk");
$dataTotalProduk = mysqli_fetch_assoc($queryTotalProduk);
$totalProduk = $dataTotalProduk['jumlah'] ?? 0;

$queryTotalUser = mysqli_query($conn, "SELECT COUNT(*) as jumlah FROM users");
$dataTotalUser = mysqli_fetch_assoc($queryTotalUser);
$totalUser = $dataTotalUser['jumlah'] ?? 0;

// ===== GRAPH DATA =====
$bulan = [];
$total_penjualan = [];
$nama_bulan = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
$queryGrafik = mysqli_query($conn, "
    SELECT MONTH(tanggal) as bulan, SUM(total) as total
    FROM pesanan
    WHERE YEAR(tanggal) = YEAR(NOW())
    GROUP BY MONTH(tanggal)
    ORDER BY MONTH(tanggal)
");
while ($data = mysqli_fetch_assoc($queryGrafik)) {
    $bulan[] = $nama_bulan[$data['bulan']];
    $total_penjualan[] = intval($data['total']);
}

$status_labels = [];
$status_data = [];
$status_order = ['pending', 'diproses', 'dikemas', 'dikirim', 'selesai'];
$status_map = [];
$queryStatus = mysqli_query($conn, "
    SELECT status_psn, COUNT(*) as jumlah
    FROM pesanan
    GROUP BY status_psn
");
while ($row = mysqli_fetch_assoc($queryStatus)) {
    $status_map[$row['status_psn']] = intval($row['jumlah']);
}
foreach ($status_order as $status) {
    if (isset($status_map[$status])) {
        $status_labels[] = ucfirst($status);
        $status_data[] = $status_map[$status];
    }
}

$produk_names = [];
$produk_sales = [];
$queryTopProduk = mysqli_query($conn, "
    SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual
    FROM detail_pesanan dp
    JOIN produk p ON dp.id_produk = p.id_produk
    GROUP BY dp.id_produk
    ORDER BY total_terjual DESC
    LIMIT 5
");
while ($row = mysqli_fetch_assoc($queryTopProduk)) {
    $produk_names[] = $row['nama_produk'];
    $produk_sales[] = intval($row['total_terjual']);
}

$queryPesananTerbaru = mysqli_query($conn, "
    SELECT ps.*, u.nama
    FROM pesanan ps
    JOIN users u ON ps.id_user = u.id_user
    ORDER BY ps.tanggal DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            border-radius: 16px;
            padding: 24px;
            color: #fff;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            min-height: 140px;
        }
        .stat-card h3 {
            margin: 0 0 14px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: .88;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .stat-icon {
            font-size: 28px;
            opacity: .9;
        }
        .stat-card.blue { background: linear-gradient(135deg, #5b73e8, #4f5ce9); }
        .stat-card.green { background: linear-gradient(135deg, #10ac84, #00b894); }
        .stat-card.orange { background: linear-gradient(135deg, #ff9f43, #ff7f50); }
        .stat-card.cyan { background: linear-gradient(135deg, #01a3a4, #00d2d3); }
        .stat-card.purple { background: linear-gradient(135deg, #6a82fb, #fc5c7d); }

        /* ===== CHARTS SECTION — REDESIGN MODERN MINIMALIS ===== */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 25px;
        }
        .chart-box {
            background: #fff;
            border-radius: 14px;
            padding: 28px;
            border: 1px solid #ececf1;
            box-shadow: none;
        }
        .chart-box h2 {
            margin: 0 0 4px;
            font-size: 15px;
            font-weight: 600;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: -0.01em;
        }
        .chart-box h2 i {
            font-size: 13px;
            color: #9ca0ab;
        }
        .chart-subtitle {
            margin: 0 0 22px;
            font-size: 12.5px;
            color: #9ca0ab;
            font-weight: 400;
        }
        .chart-wrapper { height: 280px; }

        @media (max-width: 1180px) {
            .charts-section { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 760px) {
            .charts-section { grid-template-columns: 1fr; }
        }
        /* ===== END CHARTS SECTION ===== */

        .recent-orders {
            background: #fff;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 18px 40px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .recent-orders h2 {
            margin-bottom: 20px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #222;
        }
        .order-item {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            padding: 18px 0;
            border-bottom: 1px solid #f0f0f5;
            align-items: center;
        }
        .order-item:last-child { border-bottom: none; }
        .order-info {
            display: grid;
            gap: 6px;
        }
        .order-id { font-weight: 700; color: #2d2d65; }
        .order-customer { color: #6c757d; font-size: 14px; }
        .order-amount { font-weight: 700; color: #2d2d65; text-align: right; }
        .status-badge {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
        }
        .status-pending { background: #ffe9c7; color: #b75d00; }
        .status-diproses { background: #f3d6ff; color: #8f2f93; }
        .status-dikemas { background: #d4f1ff; color: #1e6fa2; }
        .status-dikirim { background: #d9f8e9; color: #0f7b5f; }
        .status-selesai { background: #dff3d7; color: #227d34; }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>
    <div class="main">
        <div class="page-header">
            <h1><i class="fa-solid fa-chart-line"></i> Dashboard Admin</h1>
            <p>Ringkasan kinerja penjualan, status pesanan, dan produk terlaris.</p>
        </div>
        <div class="stats-grid">
            <div class="stat-card blue">
                <h3><i class="fa-solid fa-money-bill-trend-up stat-icon"></i> Total Penjualan</h3>
                <div class="stat-value">Rp <?php echo number_format($totalPenjualan, 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card green">
                <h3><i class="fa-solid fa-receipt stat-icon"></i> Total Pesanan</h3>
                <div class="stat-value"><?php echo number_format($totalPesanan); ?></div>
            </div>
            <div class="stat-card orange">
                <h3><i class="fa-solid fa-hourglass-half stat-icon"></i> Pending</h3>
                <div class="stat-value"><?php echo number_format($pesananPending); ?></div>
            </div>
            <div class="stat-card cyan">
                <h3><i class="fa-solid fa-boxes-stacked stat-icon"></i> Produk</h3>
                <div class="stat-value"><?php echo number_format($totalProduk); ?></div>
            </div>
            <div class="stat-card purple">
                <h3><i class="fa-solid fa-users stat-icon"></i> User</h3>
                <div class="stat-value"><?php echo number_format($totalUser); ?></div>
            </div>
        </div>

        <!-- ===== CHARTS SECTION — 3 kolom sejajar, equal width ===== -->
        <div class="charts-section">
            <div class="chart-box">
                <h2><i class="fa-solid fa-chart-line"></i> Penjualan Bulanan</h2>
                <p class="chart-subtitle">Pendapatan per bulan tahun ini</p>
                <div class="chart-wrapper"><canvas id="chartPenjualan"></canvas></div>
            </div>
            <div class="chart-box">
                <h2><i class="fa-solid fa-chart-pie"></i> Status Pesanan</h2>
                <p class="chart-subtitle">Distribusi status seluruh pesanan</p>
                <div class="chart-wrapper"><canvas id="chartStatus"></canvas></div>
            </div>
            <div class="chart-box">
                <h2><i class="fa-solid fa-fire"></i> Top Produk</h2>
                <p class="chart-subtitle">5 produk terlaris</p>
                <div class="chart-wrapper"><canvas id="chartTopProduk"></canvas></div>
            </div>
        </div>
        <!-- ===== END CHARTS SECTION ===== -->

        <div class="recent-orders">
            <h2><i class="fa-solid fa-clock"></i> Pesanan Terbaru</h2>
            <?php while ($pesanan = mysqli_fetch_assoc($queryPesananTerbaru)) : ?>
                <div class="order-item">
                    <div class="order-info">
                        <div class="order-id">#<?php echo $pesanan['id_pesanan']; ?></div>
                        <div class="order-customer"><?php echo htmlspecialchars($pesanan['nama']); ?> • <?php echo date('d M Y', strtotime($pesanan['tanggal'])); ?></div>
                    </div>
                    <div class="order-amount">Rp <?php echo number_format($pesanan['total'], 0, ',', '.'); ?></div>
                    <span class="status-badge status-<?php echo htmlspecialchars($pesanan['status_psn']); ?>"><?php echo ucfirst(htmlspecialchars($pesanan['status_psn'])); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <script>
        /* ===== Penjualan Bulanan: gaya klasik, garis solid + grid ===== */
        const ctxPenjualan = document.getElementById('chartPenjualan').getContext('2d');
        new Chart(ctxPenjualan, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($bulan); ?>,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: <?php echo json_encode($total_penjualan); ?>,
                    borderColor: '#2d2d65',
                    backgroundColor: '#2d2d65',
                    borderWidth: 2.5,
                    fill: false,
                    tension: 0.15,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointBackgroundColor: '#2d2d65',
                    pointBorderColor: '#2d2d65',
                    pointBorderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a2e',
                        padding: 10,
                        cornerRadius: 6,
                        titleFont: { size: 12 },
                        bodyFont: { size: 12, weight: 'bold' },
                        callbacks: {
                            label: ctx => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'Rp ' + value.toLocaleString('id-ID'),
                            font: { size: 11 },
                            color: '#4a4a5a'
                        },
                        grid: { color: '#e2e2ea', drawBorder: false },
                        border: { display: false }
                    },
                    x: {
                        ticks: { font: { size: 11 }, color: '#4a4a5a' },
                        grid: { color: '#e2e2ea', drawBorder: false },
                        border: { display: false }
                    }
                }
            }
        });

        const ctxStatus = document.getElementById('chartStatus').getContext('2d');
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_data); ?>,
                    backgroundColor: ['#2d2d65', '#6a6ab3', '#a5a5d6', '#d4d4ec', '#ececf6'],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 11 },
                            color: '#5a5a6e',
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 14,
                            boxWidth: 7
                        }
                    }
                }
            }
        });

        const ctxTopProduk = document.getElementById('chartTopProduk').getContext('2d');
        new Chart(ctxTopProduk, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($produk_names); ?>,
                datasets: [{
                    label: 'Terjual',
                    data: <?php echo json_encode($produk_sales); ?>,
                    backgroundColor: '#2d2d65',
                    borderRadius: 6,
                    borderSkipped: false,
                    barThickness: 14
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { font: { size: 11 }, color: '#9ca0ab' },
                        grid: { color: '#f1f1f5', drawBorder: false }
                    },
                    y: {
                        ticks: { font: { size: 11 }, color: '#5a5a6e' },
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
</body>
</html>