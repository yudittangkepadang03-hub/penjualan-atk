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

// FOTO PROFIL
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

// AMBIL DATA KERANJANG — prepared statement (cegah SQL injection)
$stmt = $conn->prepare("
    SELECT keranjang.*, produk.nama_produk, produk.harga, produk.gambar, produk.stok
    FROM keranjang
    JOIN produk ON keranjang.id_produk = produk.id_produk
    WHERE keranjang.id_user = ?
    ORDER BY keranjang.id_keranjang ASC
");
$stmt->bind_param('i', $id_user);
$stmt->execute();
$query = $stmt->get_result();
$jumlah_item = $query->num_rows;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Saya</title>
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
            --red:       #E53E3E;
            --red-light: #FFF5F5;
            --gray-bg:   #F0F2F5;
            --gray-100:  #E4E7EB;
            --gray-300:  #CBD2D9;
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

        .sb-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .sb-footer a {
            display: flex; align-items: center; gap: 10px;
            color: rgba(255,255,255,0.8);
            text-decoration: none; font-size: 0.875rem;
            padding: 0.6rem 0.75rem; border-radius: 8px;
            transition: background 0.15s;
        }

        .sb-footer a:hover { background: rgba(255,255,255,0.15); color: #fff; }

        /* ── MAIN ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            padding: 2rem 2.25rem;
        }

        .page-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 1.5rem;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            background: var(--white);
            border-radius: 14px;
            border: 1px solid var(--gray-100);
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 3.5rem;
            color: var(--gray-300);
            display: block;
            margin-bottom: 1rem;
        }

        .empty-state p { font-size: 0.95rem; margin-bottom: 1.25rem; }

        .btn-belanja {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--blue);
            color: #fff;
            text-decoration: none;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            transition: background 0.15s;
        }

        .btn-belanja:hover { background: var(--blue-dark); }

        /* ── TABEL KERANJANG ── */
        .cart-wrapper {
            background: var(--white);
            border-radius: 14px;
            border: 1px solid var(--gray-100);
            overflow: hidden;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }

        .cart-table thead {
            background: var(--blue);
            color: #fff;
        }

        .cart-table thead th {
            padding: 0.85rem 1rem;
            font-weight: 600;
            font-size: 0.82rem;
            text-align: center;
            white-space: nowrap;
        }

        .cart-table thead th:nth-child(4) { text-align: left; }

        .cart-table tbody tr {
            border-bottom: 1px solid var(--gray-100);
            transition: background 0.12s;
        }

        .cart-table tbody tr:last-child { border-bottom: none; }
        .cart-table tbody tr:hover { background: #FAFBFC; }

        .cart-table td {
            padding: 0.9rem 1rem;
            text-align: center;
            vertical-align: middle;
            color: var(--gray-800);
        }

        .cart-table td.td-nama { text-align: left; font-weight: 600; }
        .cart-table td.td-harga { color: var(--gray-500); }
        .cart-table td.td-subtotal { font-weight: 700; color: var(--green-dark); }

        .cart-table td img {
            width: 64px; height: 64px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--gray-100);
        }

        /* CHECKBOX */
        input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--blue);
            cursor: pointer;
        }

        /* QTY CONTROL */
        .qty-wrap {
            display: inline-flex;
            align-items: center;
            gap: 0;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            overflow: hidden;
        }

        .qty-btn {
            width: 32px; height: 32px;
            border: none;
            background: var(--gray-bg);
            color: var(--gray-800);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            transition: background 0.15s;
            line-height: 1;
        }

        .qty-btn:hover { background: var(--gray-100); }
        .qty-btn:disabled { opacity: 0.35; cursor: not-allowed; background: var(--gray-bg); }

        .qty-value {
            min-width: 36px;
            text-align: center;
            font-weight: 700;
            font-size: 0.875rem;
            background: var(--white);
            padding: 0 4px;
            line-height: 32px;
            border-left: 1px solid var(--gray-300);
            border-right: 1px solid var(--gray-300);
        }

        /* HAPUS */
        .btn-hapus {
            display: inline-flex; align-items: center; gap: 5px;
            background: var(--red-light);
            color: var(--red);
            border: 1px solid #FED7D7;
            padding: 0.4rem 0.75rem;
            border-radius: 7px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.15s;
            white-space: nowrap;
        }

        .btn-hapus:hover { background: #FED7D7; }

        /* FOOTER TABEL */
        .cart-footer {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 2px solid var(--gray-100);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .total-info { font-size: 0.875rem; color: var(--gray-500); }

        .total-info .total-angka {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--gray-800);
            display: block;
            margin-top: 2px;
        }

        .btn-checkout {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--green);
            color: #fff;
            border: none;
            padding: 0.7rem 1.75rem;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
        }

        .btn-checkout:hover { background: var(--green-dark); }
        .btn-checkout:active { transform: scale(0.98); }
        .btn-checkout:disabled { opacity: 0.4; cursor: not-allowed; }

        /* NOTIF */
        .notif-pilih {
            font-size: 0.78rem;
            color: var(--red);
            display: none;
            margin-top: 4px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .sidebar { width: 64px; }
            .sb-profile, .sb-nama, .sb-nav a span, .sb-footer a span { display: none; }
            .sb-nav a { justify-content: center; padding: 0.75rem; margin: 2px 6px; }
            .sb-footer a { justify-content: center; }
            .main { margin-left: 64px; padding: 1.25rem; }
            .cart-footer { flex-direction: column; align-items: flex-start; }
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
            <i class="fa-solid fa-house"></i><span>Dashboard</span>
        </a>
        <a href="keranjang.php" class="active">
            <i class="fa-solid fa-cart-shopping"></i><span>Keranjang</span>
        </a>
        <a href="pesanan.php">
            <i class="fa-solid fa-receipt"></i><span>Pesanan</span>
        </a>
        <a href="pembayaran.php">
            <i class="fa-solid fa-credit-card"></i><span>Pembayaran</span>
        </a>
    </nav>

    <div class="sb-footer">
        <a href="logout.php">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </div>

</div>

<!-- MAIN -->
<div class="main">

    <h1 class="page-title"><i class="fa-solid fa-cart-shopping" style="color:var(--blue);margin-right:10px;"></i>Keranjang Saya</h1>

    <?php if ($jumlah_item === 0): ?>

    <!-- EMPTY STATE -->
    <div class="empty-state">
        <i class="fa-solid fa-cart-shopping"></i>
        <p>Keranjang kamu masih kosong.<br>Yuk, mulai belanja!</p>
        <a href="dashboard.php" class="btn-belanja">
            <i class="fa-solid fa-store"></i> Lihat Produk
        </a>
    </div>

    <?php else: ?>

    <form action="checkout.php" method="POST" id="form-checkout">
        <div class="cart-wrapper">

            <table class="cart-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="pilih-semua" title="Pilih semua"></th>
                        <th>No</th>
                        <th>Gambar</th>
                        <th style="text-align:left;">Produk</th>
                        <th>Harga Satuan</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th>Hapus</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                $grand_total = 0;
                while ($row = $query->fetch_assoc()):
                    $subtotal = $row['harga'] * $row['jumlah'];
                    $grand_total += $subtotal;
                ?>
                <tr>
                    <td>
                        <input type="checkbox" class="cb-item"
                               name="pilih[]"
                               value="<?= (int)$row['id_keranjang'] ?>">
                    </td>
                    <td><?= $no++ ?></td>
                    <td>
                        <?php if (!empty($row['gambar'])): ?>
                            <img src="gambar/<?= htmlspecialchars($row['gambar']) ?>"
                                 alt="<?= htmlspecialchars($row['nama_produk']) ?>"
                                 onerror="this.src='gambar/default.png'">
                        <?php else: ?>
                            <div style="width:64px;height:64px;background:var(--gray-100);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:auto;">
                                <i class="fa-solid fa-image" style="color:var(--gray-300);font-size:1.4rem;"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="td-nama"><?= htmlspecialchars($row['nama_produk']) ?></td>
                    <td class="td-harga">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                    <td>
                        <div class="qty-wrap">
                            <a href="update_keranjang.php?id_keranjang=<?= (int)$row['id_keranjang'] ?>&action=decrease"
                               class="qty-btn" title="Kurangi">−</a>
                            <span class="qty-value"><?= (int)$row['jumlah'] ?></span>
                            <?php if ($row['jumlah'] >= $row['stok']): ?>
                                <button class="qty-btn" disabled title="Stok habis">+</button>
                            <?php else: ?>
                                <a href="update_keranjang.php?id_keranjang=<?= (int)$row['id_keranjang'] ?>&action=increase"
                                   class="qty-btn" title="Tambah">+</a>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="td-subtotal">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                    <td>
                        <a href="hapus_keranjang.php?id=<?= (int)$row['id_keranjang'] ?>"
                           class="btn-hapus"
                           onclick="return confirm('Hapus produk ini dari keranjang?')">
                            <i class="fa-solid fa-trash"></i> Hapus
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

            <!-- FOOTER: TOTAL + CHECKOUT -->
            <div class="cart-footer">
                <div class="total-info">
                    Total Belanja (item terpilih)
                    <span class="total-angka" id="total-display">
                        Rp <?= number_format($grand_total, 0, ',', '.') ?>
                    </span>
                    <span class="notif-pilih" id="notif-pilih">
                        <i class="fa-solid fa-circle-exclamation"></i> Pilih minimal 1 produk dulu.
                    </span>
                </div>
                <button type="submit" class="btn-checkout" id="btn-checkout">
                    <i class="fa-solid fa-bag-shopping"></i> Checkout Sekarang
                </button>
            </div>

        </div>
    </form>

    <?php endif; ?>

</div>

<script>
    const pilihSemua = document.getElementById('pilih-semua');
    const cbItems    = document.querySelectorAll('.cb-item');
    const btnCheckout = document.getElementById('btn-checkout');
    const notifPilih  = document.getElementById('notif-pilih');

    // Data harga per baris (id_keranjang => subtotal)
    const subtotalMap = {
        <?php
        // Re-query untuk JS subtotal map
        $stmt2 = $conn->prepare("
            SELECT keranjang.id_keranjang, (keranjang.jumlah * produk.harga) AS subtotal
            FROM keranjang
            JOIN produk ON keranjang.id_produk = produk.id_produk
            WHERE keranjang.id_user = ?
        ");
        $stmt2->bind_param('i', $id_user);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $js_parts = [];
        while ($r = $res2->fetch_assoc()) {
            $js_parts[] = (int)$r['id_keranjang'] . ': ' . (int)$r['subtotal'];
        }
        echo implode(', ', $js_parts);
        $stmt2->close();
        ?>
    };

    function formatRupiah(angka) {
        return 'Rp ' + angka.toLocaleString('id-ID');
    }

    function hitungTotal() {
        let total = 0;
        cbItems.forEach(cb => {
            if (cb.checked) total += subtotalMap[parseInt(cb.value)] || 0;
        });
        document.getElementById('total-display').textContent = formatRupiah(total);
    }

    function cekPilihan() {
        const ada = Array.from(cbItems).some(cb => cb.checked);
        if (notifPilih) notifPilih.style.display = ada ? 'none' : 'block';
        return ada;
    }

    // Pilih semua
    if (pilihSemua) {
        pilihSemua.addEventListener('change', function () {
            cbItems.forEach(cb => cb.checked = this.checked);
            hitungTotal();
            cekPilihan();
        });
    }

    // Per item
    cbItems.forEach(cb => {
        cb.addEventListener('change', function () {
            const semuaTerpilih = Array.from(cbItems).every(c => c.checked);
            if (pilihSemua) pilihSemua.checked = semuaTerpilih;
            hitungTotal();
            cekPilihan();
        });
    });

    // Validasi checkout
    if (btnCheckout) {
        btnCheckout.addEventListener('click', function (e) {
            if (!cekPilihan()) {
                e.preventDefault();
            }
        });
    }
</script>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>