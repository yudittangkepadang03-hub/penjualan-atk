<?php
if (!isset($_SESSION)) {
    session_start();
}
$activePage = basename($_SERVER['PHP_SELF']);

// Ambil data admin dari tabel users berdasarkan id_user di session
$_admin_sidebar = [];
if (isset($conn) && isset($_SESSION['id_user'])) {
    $id_sb   = intval($_SESSION['id_user']);
    $stmt_sb = $conn->prepare("SELECT nama, foto_profil FROM users WHERE id_user=?");
    $stmt_sb->bind_param('i', $id_sb);
    $stmt_sb->execute();
    $_admin_sidebar = $stmt_sb->get_result()->fetch_assoc() ?? [];
    $stmt_sb->close();
}
$_admin_nama = $_admin_sidebar['nama']       ?? ($_SESSION['nama'] ?? 'Admin');
$_admin_foto = $_admin_sidebar['foto_profil'] ?? '';
$_foto_path  = __DIR__ . '/profile_images_admin/' . $_admin_foto;
?>
<div class="sidebar">
    <div class="profile">
        <a href="profile_admin.php" style="text-decoration:none;color:inherit;">
            <?php if (!empty($_admin_foto) && file_exists($_foto_path)) { ?>
                <img src="profile_images_admin/<?php echo htmlspecialchars($_admin_foto); ?>"
                     alt="Foto Admin"
                     style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,0.5);margin-bottom:8px;">
            <?php } else { ?>
                <i class="fa-solid fa-user-circle" style="font-size:48px;"></i>
            <?php } ?>
            <p><?php echo htmlspecialchars($_admin_nama); ?></p>
        </a>
    </div>
    <ul>
        <li>
            <a href="admin.php"
               <?= ($activePage == 'admin.php') ? 'class="active"' : ''; ?>>
                <i class="fa-solid fa-house"></i>
                Dashboard
            </a>
        </li>
        <li>
            <a href="data_produk.php"
               <?= ($activePage == 'data_produk.php') ? 'class="active"' : ''; ?>>
                <i class="fa-solid fa-box"></i>
                Produk
            </a>
        </li>
        <li>
            <a href="data_pesanan.php"
               <?= ($activePage == 'data_pesanan.php' || $activePage == 'kelola_pesanan.php') ? 'class="active"' : ''; ?>>
                <i class="fa-solid fa-receipt"></i>
                Data Pesanan
            </a>
        </li>
        <li>
            <a href="verifikasi_pembayaran.php"
               <?= ($activePage == 'verifikasi_pembayaran.php') ? 'class="active"' : ''; ?>>
                <i class="fa-solid fa-credit-card"></i>
                Pembayaran
            </a>
        </li>
        <li>
            <a href="data_user.php"
               <?= ($activePage == 'data_user.php') ? 'class="active"' : ''; ?>>
                <i class="fa-solid fa-users"></i>
                User
            </a>
        </li>
        <li>
            <a href="laporan.php"
               <?= ($activePage == 'laporan.php') ? 'class="active"' : ''; ?>>
                <i class="fa-solid fa-file-lines"></i>
                Laporan
            </a>
        </li>
        <li>
            <a href="profile_admin.php"
               <?= ($activePage == 'profile_admin.php') ? 'class="active"' : ''; ?>>
                <i class="fa-solid fa-user-shield"></i>
                Profil Saya
            </a>
        </li>
        <li class="logout-menu">
            <a href="logout.php"
               onclick="return confirm('Yakin ingin logout?');">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>
        </li>
    </ul>
</div>