<?php
session_start();
include 'koneksi.php';

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

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    $stmt = $conn->prepare("SELECT * FROM produk WHERE nama_produk LIKE ? ORDER BY nama_produk ASC");
    $like = '%' . $search . '%';
    $stmt->bind_param('s', $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM produk ORDER BY nama_produk ASC");
}

$stmt->execute();
$query = $stmt->get_result();
$jumlah_produk = $query->num_rows;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembeli</title>
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

        /* PROFILE */
        .sb-profile {
            padding: 2rem 1rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .sb-profile a { text-decoration: none; }

        .sb-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.6);
            object-fit: cover;
            margin: 0 auto 0.6rem;
            display: block;
        }

        .sb-avatar-icon {
            width: 72px;
            height: 72px;
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

        /* NAV */
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

        .sb-nav a:hover {
            background: rgba(255,255,255,0.18);
            color: #fff;
        }

        .sb-nav a.active {
            background: rgba(255,255,255,0.22);
            color: #fff;
        }

        /* LOGOUT */
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

        .sb-footer a:hover {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 2rem 2.25rem;
        }

        /* HEADER */
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 1.5rem;
        }

        /* SEARCH */
        .search-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }

        .search-input-box {
            display: flex;
            align-items: center;
            background: var(--white);
            border: 1.5px solid var(--gray-100);
            border-radius: 10px;
            padding: 0 14px;
            flex: 1;
            max-width: 400px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .search-input-box:focus-within {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(59,163,232,0.15);
        }

        .search-input-box input {
            border: none;
            outline: none;
            padding: 0.65rem 0;
            font-size: 0.875rem;
            background: transparent;
            width: 100%;
            color: var(--gray-800);
        }

        .search-input-box input::placeholder { color: var(--gray-500); }

        .btn-cari {
            background: var(--blue);
            color: #fff;
            border: none;
            border-radius: 10px;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.95rem;
            transition: background 0.15s;
            flex-shrink: 0;
        }

        .btn-cari:hover { background: var(--blue-dark); }

        /* INFO */
        .info-bar {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-bottom: 1.1rem;
        }

        .info-bar b { color: var(--gray-800); }

        /* ── PRODUK GRID ── */
        .produk-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.1rem;
        }

        .produk-card {
            background: var(--white);
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--gray-100);
            transition: box-shadow 0.2s, transform 0.2s;
        }

        .produk-card:hover {
            box-shadow: 0 8px 28px rgba(0,0,0,0.09);
            transform: translateY(-3px);
        }

        .produk-card img {
            width: 100%;
            height: 155px;
            object-fit: cover;
            background: #f9f9f9;
        }

        .produk-body {
            padding: 0.9rem 1rem 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
            text-align: center;
        }

        .produk-body h3 {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--gray-800);
            line-height: 1.3;
        }

        .produk-body .harga {
            font-size: 1rem;
            font-weight: 700;
            color: var(--green);
        }

        .produk-body .stok {
            font-size: 0.78rem;
            color: var(--gray-500);
        }

        .stok-habis-label {
            font-size: 0.75rem;
            color: #E53E3E;
            font-weight: 600;
        }

        /* ACTIONS */
        .produk-actions {
            display: flex;
            gap: 8px;
            padding: 0.875rem 1rem;
        }

        .btn-beli {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            background: var(--green);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.55rem 0.5rem;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }

        .btn-beli:hover { background: var(--green-dark); }

        .btn-keranjang {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--blue-light);
            color: var(--blue);
            border: 1.5px solid var(--blue);
            border-radius: 8px;
            padding: 0.55rem 0.75rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }

        .btn-keranjang:hover {
            background: var(--blue);
            color: #fff;
        }

        .btn-beli:active, .btn-keranjang:active { transform: scale(0.97); }

        .btn-beli:disabled, .btn-keranjang:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }

        /* EMPTY STATE */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }

        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; color: var(--gray-100); }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { width: 64px; }
            .sb-profile, .sb-nama, .sb-nav a span, .sb-footer a span { display: none; }
            .sb-nav a { justify-content: center; padding: 0.75rem; margin: 2px 6px; }
            .sb-footer a { justify-content: center; }
            .main { margin-left: 64px; padding: 1.25rem; }
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
        <a href="dashboard.php" class="active">
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

    <h1 class="page-title">
        Selamat Datang, <?= htmlspecialchars($_SESSION['nama']) ?> 👋
    </h1>

    <!-- SEARCH -->
    <form method="GET" class="search-wrap">
        <div class="search-input-box">
            <input
                type="text"
                name="search"
                placeholder="Cari produk..."
                value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn-cari" aria-label="Cari">
            <i class="fa fa-search"></i>
        </button>
    </form>

    <!-- INFO BAR -->
    <div class="info-bar">
        <?php if ($search !== ''): ?>
            <b><?= $jumlah_produk ?></b> hasil untuk &ldquo;<?= htmlspecialchars($search) ?>&rdquo;
        <?php else: ?>
            <b><?= $jumlah_produk ?></b> produk tersedia
        <?php endif; ?>
    </div>

    <!-- PRODUK GRID -->
    <div class="produk-grid">

        <?php if ($jumlah_produk === 0): ?>
        <div class="empty-state">
            <i class="fa-solid fa-box-open"></i>
            <p>Tidak ada produk untuk &ldquo;<b><?= htmlspecialchars($search) ?></b>&rdquo;.</p>
        </div>
        <?php else: ?>

        <?php while ($row = $query->fetch_assoc()): ?>
        <div class="produk-card">

            <img src="gambar/<?= htmlspecialchars($row['gambar']) ?>"
                 alt="<?= htmlspecialchars($row['nama_produk']) ?>"
                 onerror="this.src='gambar/default.png'">

            <div class="produk-body">
                <h3><?= htmlspecialchars($row['nama_produk']) ?></h3>
                <p class="harga">Rp <?= number_format($row['harga'], 0, ',', '.') ?></p>
                <?php if ($row['stok'] > 0): ?>
                    <p class="stok">Stok: <?= (int)$row['stok'] ?></p>
                <?php else: ?>
                    <p class="stok-habis-label">Stok Habis</p>
                <?php endif; ?>
            </div>

            <div class="produk-actions">
                <form action="checkout.php" method="POST" style="flex:1;display:flex;">
                    <input type="hidden" name="id_produk" value="<?= (int)$row['id_produk'] ?>">
                    <button type="submit" class="btn-beli"
                        <?= $row['stok'] <= 0 ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-bag-shopping"></i> Beli
                    </button>
                </form>
                <form action="tambah_keranjang.php" method="POST">
                    <input type="hidden" name="id_produk" value="<?= (int)$row['id_produk'] ?>">
                    <button type="submit" class="btn-keranjang"
                        title="Tambah ke keranjang"
                        <?= $row['stok'] <= 0 ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-cart-plus"></i>
                    </button>
                </form>
            </div>

        </div>
        <?php endwhile; ?>

        <?php endif; ?>
    </div>

</div>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>