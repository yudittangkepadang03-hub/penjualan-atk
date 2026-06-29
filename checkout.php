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

$alamat_default = '';
$columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'alamat'");
if ($columnCheck && mysqli_num_rows($columnCheck) > 0) {
    $userAlamat = mysqli_query($conn, "SELECT alamat FROM users WHERE id_user='$id_user' LIMIT 1");
    if ($userAlamat && mysqli_num_rows($userAlamat) > 0) {
        $dataAlamat = mysqli_fetch_assoc($userAlamat);
        $alamat_default = $dataAlamat['alamat'] ?? '';
    }
}

// CEK ADA ITEM DIPILIH ATAU PEMBELIAN LANGSUNG
$directBuy = false;
$directProductId = 0;
$pilih = [];
if (isset($_POST['pilih']) && !empty($_POST['pilih'])) {
    $pilih = $_POST['pilih'];
} elseif (isset($_POST['id_produk']) && intval($_POST['id_produk']) > 0) {
    $directBuy = true;
    $directProductId = intval($_POST['id_produk']);
}

if (!$directBuy && empty($pilih)) {
    header("Location: keranjang.php");
    exit;
}

$items = [];
$grand_total = 0;

if ($directBuy) {
    $query = mysqli_query($conn, "
        SELECT id_produk, nama_produk, harga, gambar, stok
        FROM produk
        WHERE id_produk = $directProductId
    ");

    if (!$query || mysqli_num_rows($query) == 0) {
        header("Location: dashboard.php");
        exit;
    }

    $row = mysqli_fetch_assoc($query);
    $row['jumlah'] = 1;
    $row['subtotal'] = $row['harga'];
    $grand_total = $row['subtotal'];
    $items[] = $row;
} else {
    $ids = implode(',', array_map('intval', $pilih));
    $query = mysqli_query($conn, "
        SELECT keranjang.*, produk.nama_produk, produk.harga, produk.gambar, produk.stok
        FROM keranjang
        JOIN produk ON keranjang.id_produk = produk.id_produk
        WHERE keranjang.id_keranjang IN ($ids)
        AND keranjang.id_user = '$id_user'
    ");

    if (!$query || mysqli_num_rows($query) == 0) {
        header("Location: keranjang.php");
        exit;
    }

    while ($row = mysqli_fetch_assoc($query)) {
        $row['subtotal'] = $row['harga'] * $row['jumlah'];
        $grand_total += $row['subtotal'];
        $items[] = $row;
    }
}

// PROSES CHECKOUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses'])) {
    $metode = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat_kirim']);

    $stok_ok = true;
    foreach ($items as $item) {
        if ($item['jumlah'] > $item['stok']) {
            $stok_ok = false;
            $error = "Stok produk <b>{$item['nama_produk']}</b> tidak mencukupi (stok tersedia: {$item['stok']}).";
            break;
        }
    }

    if ($stok_ok) {
        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "
                INSERT INTO pesanan (id_user, total, status_psn, metode_pembayaran, alamat_pengiriman, tanggal)
                VALUES ('$id_user', '$grand_total', 'pending', '$metode', '$alamat', NOW())
            ");
            $id_pesanan = mysqli_insert_id($conn);

            foreach ($items as $item) {
                $id_produk    = intval($item['id_produk']);
                $jumlah       = intval($item['jumlah']);
                $harga_satuan = intval($item['harga']);
                $subtotal     = $jumlah * $harga_satuan;

                mysqli_query($conn, "
                    INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_satuan, total_harga)
                    VALUES ('$id_pesanan', '$id_produk', '$jumlah', '$harga_satuan', '$subtotal')
                ");

                mysqli_query($conn, "
                    UPDATE produk SET stok = stok - $jumlah
                    WHERE id_produk = $id_produk
                ");

                if (!$directBuy) {
                    $id_keranjang = intval($item['id_keranjang']);
                    mysqli_query($conn, "
                        DELETE FROM keranjang WHERE id_keranjang = $id_keranjang
                    ");
                }
            }

            mysqli_commit($conn);
            header("Location: payment_details.php?id=$id_pesanan");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Terjadi kesalahan, silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 220px;
            --blue:      #3BA3E8;
            --blue-dark: #2980C4;
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

        .main h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--gray-800);
        }

        /* ERROR */
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        /* TABEL CHECKOUT */
        .checkout-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .checkout-table th {
            background: var(--blue);
            color: white;
            padding: 13px 12px;
            text-align: center;
            font-size: 0.9rem;
        }

        .checkout-table td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
            color: var(--gray-800);
        }

        .checkout-table tbody tr:last-child td {
            border-bottom: none;
            background: #f8fafc;
            font-size: 1rem;
        }

        .checkout-table img {
            width: 60px; height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* FORM BOX */
        .form-box {
            background: white;
            max-width: 560px;
            padding: 28px;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .form-box label {
            display: block;
            margin-top: 16px;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--gray-800);
        }

        .form-box select,
        .form-box textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid var(--gray-100);
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--gray-800);
            outline: none;
            transition: border-color 0.15s;
        }

        .form-box select:focus,
        .form-box textarea:focus {
            border-color: var(--blue);
        }

        .form-box textarea {
            resize: none;
            height: 100px;
        }

        .form-box button {
            margin-top: 20px;
            width: 100%;
            padding: 13px;
            background: var(--blue);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.15s;
        }

        .form-box button:hover { background: var(--blue-dark); }

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
        <a href="dashboard.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="keranjang.php"><i class="fa-solid fa-cart-shopping"></i><span>Keranjang</span></a>
        <a href="pesanan.php"><i class="fa-solid fa-receipt"></i><span>Pesanan</span></a>
        <a href="pembayaran.php"><i class="fa-solid fa-credit-card"></i><span>Pembayaran</span></a>
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

    <h1>Konfirmasi Checkout</h1>

    <?php if (isset($error)): ?>
        <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <!-- TABEL PRODUK -->
    <table class="checkout-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Gambar</th>
                <th>Produk</th>
                <th>Harga Satuan</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $item): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td>
                    <?php if (!empty($item['gambar'])): ?>
                        <img src="gambar/<?= htmlspecialchars($item['gambar']) ?>"
                             alt="<?= htmlspecialchars($item['nama_produk']) ?>">
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                <td>Rp <?= number_format($item['harga']) ?></td>
                <td><?= $item['jumlah'] ?></td>
                <td>Rp <?= number_format($item['subtotal']) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="5"><b>Total Pembayaran</b></td>
                <td><b>Rp <?= number_format($grand_total) ?></b></td>
            </tr>
        </tbody>
    </table>

    <!-- FORM CHECKOUT -->
    <div class="form-box">
        <form method="POST">
            <?php if ($directBuy): ?>
                <input type="hidden" name="id_produk" value="<?= intval($directProductId) ?>">
            <?php else: ?>
                <?php foreach ($pilih as $id_k): ?>
                    <input type="hidden" name="pilih[]" value="<?= intval($id_k) ?>">
                <?php endforeach; ?>
            <?php endif; ?>
            <input type="hidden" name="proses" value="1">

            <label>Metode Pembayaran</label>
            <select name="metode_pembayaran" required>
                <option value="">-- Pilih Metode --</option>
                <option value="QRIS">QRIS</option>
                <option value="E-Wallet">E-Wallet</option>
                <option value="Transfer Bank">Transfer Bank</option>
                <option value="COD">COD (Bayar di Tempat)</option>
            </select>

            <label>Alamat Pengiriman</label>
            <textarea name="alamat_kirim"
                      placeholder="Masukkan alamat lengkap pengiriman..."
                      required><?= htmlspecialchars($alamat_default) ?></textarea>

            <button type="submit">
                <i class="fa-solid fa-check"></i> Konfirmasi Pesanan
            </button>
        </form>
    </div>

</div>

</body>
</html>