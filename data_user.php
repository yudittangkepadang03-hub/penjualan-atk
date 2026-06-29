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

// SEARCH & FILTER
$search        = trim($_GET['search'] ?? '');
$filter_role   = $_GET['role'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where = [];
if ($search !== '') {
    $s       = mysqli_real_escape_string($conn, $search);
    $where[] = "(nama LIKE '%$s%' OR email LIKE '%$s%')";
}
if ($filter_role !== '') {
    $r       = mysqli_real_escape_string($conn, $filter_role);
    $where[] = "role = '$r'";
}
if ($filter_status !== '') {
    $fs      = mysqli_real_escape_string($conn, $filter_status);
    $where[] = "status = '$fs'";
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$query = mysqli_query($conn, "SELECT * FROM users $where_sql ORDER BY id_user DESC");

// SUMMARY
$total_user  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role != 'admin'"))['c'] ?? 0;
$total_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role = 'admin'"))['c'] ?? 0;
$total_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE status = 'aktif'"))['c'] ?? 0;
$total_nonaktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE status = 'nonaktif'"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        /* SUMMARY */
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
            border: 2px solid transparent;
            text-decoration: none;
            color: inherit;
            transition: border-color .2s, transform .15s;
        }
        .summary-card:hover, .summary-card.active { border-color: currentColor; transform: translateY(-2px); }
        .sc-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .sc-num   { font-size: 22px; font-weight: 800; }
        .sc-label { font-size: 12px; color: #6b7280; font-weight: 500; }

        .sc-user    .sc-icon { background:#eff6ff; color:#3b82f6; }
        .sc-user    .sc-num  { color:#3b82f6; }
        .sc-admin   .sc-icon { background:#f3e8ff; color:#8b5cf6; }
        .sc-admin   .sc-num  { color:#8b5cf6; }
        .sc-aktif   .sc-icon { background:#d1fae5; color:#059669; }
        .sc-aktif   .sc-num  { color:#059669; }
        .sc-nonaktif .sc-icon { background:#fee2e2; color:#dc2626; }
        .sc-nonaktif .sc-num  { color:#dc2626; }

        /* TOOLBAR */
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
        }
        .btn-cari {
            padding: 10px 18px;
            border-radius: 10px;
            background: linear-gradient(135deg, #5b73e8, #a29bfe);
            color: #fff;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex; align-items: center; gap: 6px;
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

        /* TABLE */
        .table-wrap {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.07);
            overflow: hidden;
        }
        .table-wrap table { width: 100%; border-collapse: collapse; }
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
        .table-wrap tbody tr.clickable-row { cursor: pointer; transition: background .15s; }
        .table-wrap tbody tr.clickable-row:hover { background: #f8f9ff; }

        /* Avatar */
        .user-cell { display: flex; align-items: center; gap: 10px; }
        .user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e5e7eb;
            flex-shrink: 0;
        }
        .user-avatar-placeholder {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #5b73e8, #a29bfe);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 15px; flex-shrink: 0;
        }
        .user-name  { font-weight: 600; color: #1e1e2f; }
        .user-email { font-size: 12px; color: #9ca3af; }

        /* Badge */
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 12px; border-radius: 999px;
            font-size: 12px; font-weight: 700;
        }
        .badge-admin   { background:#f3e8ff; color:#6d28d9; }
        .badge-user    { background:#eff6ff; color:#1d4ed8; }
        .badge-aktif   { background:#d1fae5; color:#065f46; }
        .badge-nonaktif{ background:#fee2e2; color:#991b1b; }

        /* Tombol aksi */
        .kelola { display: flex; gap: 8px; }
        .btn-edit {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 14px; border-radius: 8px;
            background: #eff6ff; color: #2563eb;
            text-decoration: none; font-size: 13px; font-weight: 600;
            border: none; cursor: pointer; transition: background .2s;
        }
        .btn-edit:hover { background: #dbeafe; }
        .btn-hapus {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 14px; border-radius: 8px;
            background: #fee2e2; color: #dc2626;
            text-decoration: none; font-size: 13px; font-weight: 600;
            border: none; cursor: pointer; transition: background .2s;
        }
        .btn-hapus:hover { background: #fecaca; }
        .btn-disabled {
            opacity: .45; cursor: not-allowed;
        }

        /* Empty state */
        .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
        .empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
    </style>
</head>
<body>

<?php include 'sidebar_admin.php'; ?>

<div class="main">
    <div class="page-header">
        <h1><i class="fa-solid fa-users"></i> Data User</h1>
        <p>Kelola semua akun pengguna yang terdaftar.</p>
    </div>

    <!-- SUMMARY -->
    <div class="summary-grid">
        <a href="?role=user" class="summary-card sc-user <?php echo $filter_role === 'user' ? 'active' : ''; ?>">
            <div class="sc-icon"><i class="fa-solid fa-user"></i></div>
            <div>
                <div class="sc-num"><?php echo $total_user; ?></div>
                <div class="sc-label">User</div>
            </div>
        </a>
        <a href="?role=admin" class="summary-card sc-admin <?php echo $filter_role === 'admin' ? 'active' : ''; ?>">
            <div class="sc-icon"><i class="fa-solid fa-user-shield"></i></div>
            <div>
                <div class="sc-num"><?php echo $total_admin; ?></div>
                <div class="sc-label">Admin</div>
            </div>
        </a>
        <a href="?status=aktif" class="summary-card sc-aktif <?php echo $filter_status === 'aktif' ? 'active' : ''; ?>">
            <div class="sc-icon"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div class="sc-num"><?php echo $total_aktif; ?></div>
                <div class="sc-label">Aktif</div>
            </div>
        </a>
        <a href="?status=nonaktif" class="summary-card sc-nonaktif <?php echo $filter_status === 'nonaktif' ? 'active' : ''; ?>">
            <div class="sc-icon"><i class="fa-solid fa-circle-xmark"></i></div>
            <div>
                <div class="sc-num"><?php echo $total_nonaktif; ?></div>
                <div class="sc-label">Nonaktif</div>
            </div>
        </a>
    </div>

    <!-- TOOLBAR -->
    <form method="GET" class="toolbar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Cari nama atau email..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <select name="role" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Role</option>
            <option value="user"  <?php echo $filter_role === 'user'  ? 'selected' : ''; ?>>User</option>
            <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="aktif"    <?php echo $filter_status === 'aktif'    ? 'selected' : ''; ?>>Aktif</option>
            <option value="nonaktif" <?php echo $filter_status === 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
        </select>
        <button type="submit" class="btn-cari">
            <i class="fa-solid fa-magnifying-glass"></i> Cari
        </button>
        <?php if ($search || $filter_role || $filter_status) { ?>
            <a href="data_user.php" class="btn-reset">
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
                    <th>Pengguna</th>
                    <th>Role</th>
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
                $isAdmin  = ($row['role'] === 'admin');
                $foto      = $row['foto_profil'] ?? '';
                $foto_dir  = $isAdmin ? 'profile_images_admin' : 'profile_images';
                $foto_path = __DIR__ . '/' . $foto_dir . '/' . $foto;
            ?>
                <tr class="clickable-row"
                    onclick="<?php echo $isAdmin
                        ? "alert('Tidak dapat mengedit admin.')"
                        : "window.location='edit_user.php?id={$row['id_user']}'"; ?>"
                    title="<?php echo $isAdmin ? 'Akun admin tidak bisa diedit' : 'Klik untuk edit'; ?>">
                    <td><?php echo $no++; ?></td>
                    <td>
                        <div class="user-cell">
                            <?php if (!empty($foto) && file_exists($foto_path)) { ?>
                                <img class="user-avatar"
                                     src="<?php echo $foto_dir; ?>/<?php echo htmlspecialchars($foto); ?>"
                                     alt="foto">
                            <?php } else { ?>
                                <div class="user-avatar-placeholder">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            <?php } ?>
                            <div>
                                <div class="user-name"><?php echo htmlspecialchars($row['nama']); ?></div>
                                <div class="user-email"><?php echo htmlspecialchars($row['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?php echo $isAdmin ? 'badge-admin' : 'badge-user'; ?>">
                            <i class="fa-solid <?php echo $isAdmin ? 'fa-user-shield' : 'fa-user'; ?>"></i>
                            <?php echo ucfirst($row['role']); ?>
                        </span>
                    </td>
                    <td>
                        <?php $st = $row['status'] ?? 'aktif'; ?>
                        <span class="badge badge-<?php echo $st === 'aktif' ? 'aktif' : 'nonaktif'; ?>">
                            <i class="fa-solid <?php echo $st === 'aktif' ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
                            <?php echo ucfirst($st); ?>
                        </span>
                    </td>
                    <td>
                        <div class="kelola">
                            <?php if ($isAdmin) { ?>
                                <button type="button" class="btn-edit btn-disabled"
                                        onclick="event.stopPropagation(); alert('Tidak dapat mengedit admin.')">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                                <button type="button" class="btn-hapus btn-disabled"
                                        onclick="event.stopPropagation(); alert('Admin tidak bisa dihapus.')">
                                    <i class="fa-solid fa-trash"></i> Hapus
                                </button>
                            <?php } else { ?>
                                <a href="edit_user.php?id=<?php echo $row['id_user']; ?>"
                                   class="btn-edit"
                                   onclick="event.stopPropagation()">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </a>
                                <a href="hapus_user.php?id=<?php echo $row['id_user']; ?>"
                                   class="btn-hapus"
                                   onclick="event.stopPropagation(); return confirm('Yakin ingin menghapus user ini?')">
                                    <i class="fa-solid fa-trash"></i> Hapus
                                </a>
                            <?php } ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (!$has_data): ?>
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fa-solid fa-users-slash"></i>
                            <p>Tidak ada user<?php echo ($search || $filter_role || $filter_status) ? ' yang sesuai filter.' : ' terdaftar.'; ?></p>
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