<?php
session_start();
include 'koneksi.php';

// Redirect documentation page to dashboard because dokumentasi feature is removed
header("Location: dashboard.php");
exit;

// CEK LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = intval($_SESSION['id_user']);
$user_query = mysqli_query($conn, "SELECT role FROM users WHERE id_user = '$id_user'");
$user_data = mysqli_fetch_assoc($user_query);

$is_admin = ($user_data['role'] === 'admin');

// Foto profil
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

// Get active tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'quick-start';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dokumentasi Sistem</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        .doc-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .doc-tabs {
            display: flex;
            flex-wrap: wrap;
            border-bottom: 2px solid #e5e7eb;
            background: #f9fafb;
        }

        .doc-tab {
            padding: 12px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 14px;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .doc-tab:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .doc-tab.active {
            color: #007bff;
            border-bottom-color: #007bff;
            background: white;
        }

        .doc-content {
            display: none;
            padding: 30px;
            max-width: 1000px;
        }

        .doc-content.active {
            display: block;
        }

        .doc-section {
            margin-bottom: 30px;
        }

        .doc-section h2 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 15px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }

        .doc-section h3 {
            font-size: 18px;
            color: #374151;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .doc-section p, .doc-section li {
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 10px;
        }

        .doc-section ul, .doc-section ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .code-block {
            background: #1f2937;
            color: #e5e7eb;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.5;
        }

        .note-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .success-box {
            background: #dcfce7;
            border-left: 4px solid #16a34a;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .warning-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .flow-chart {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 6px;
            margin: 15px 0;
            text-align: center;
            font-family: monospace;
            white-space: pre;
            overflow-x: auto;
            line-height: 1.8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table th, table td {
            border: 1px solid #e5e7eb;
            padding: 12px;
            text-align: left;
        }

        table th {
            background: #f3f4f6;
            font-weight: bold;
        }

        table tr:hover {
            background: #fafbfc;
        }

        .checklist {
            list-style: none;
            margin-left: 0;
        }

        .checklist li {
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
        }

        .checklist li:before {
            content: "☐";
            position: absolute;
            left: 0;
            font-size: 16px;
            color: #6b7280;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #1f2937;
            color: white;
            overflow-y: auto;
            padding: 20px 0;
            z-index: 999;
        }

        .main {
            margin-left: 250px;
            padding: 30px;
        }

        h1 {
            color: #1f2937;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="profile">
        <a href="profile.php">
            <?php if (!empty($fotoProfilPath)) { ?>
                <img src="<?php echo htmlspecialchars($fotoProfilPath); ?>" alt="Foto Profil">
            <?php } else { ?>
                <i class="fa-solid fa-user-circle"></i>
            <?php } ?>
            <p><?php echo htmlspecialchars($_SESSION['nama']); ?></p>
        </a>
    </div>

    <ul>
        <?php if ($is_admin) { ?>
            <li><a href="admin.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="data_produk.php"><i class="fa-solid fa-box"></i> Produk</a></li>
            <li><a href="kelola_pesanan.php"><i class="fa-solid fa-receipt"></i> Kelola Pesanan</a></li>
            <li><a href="verifikasi_pembayaran.php"><i class="fa-solid fa-check-double"></i> Verifikasi Pembayaran</a></li>
            <li><a href="pengaturan_pembayaran.php"><i class="fa-solid fa-cog"></i> Pengaturan Pembayaran</a></li>
            <li><a href="data_user.php"><i class="fa-solid fa-users"></i> User</a></li>
        <?php } else { ?>
            <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="pesanan.php"><i class="fa-solid fa-receipt"></i> Pesanan Saya</a></li>
            <li><a href="keranjang.php"><i class="fa-solid fa-shopping-cart"></i> Keranjang</a></li>
        <?php } ?>
        <li><a href="documentation.php" class="active"><i class="fa-solid fa-book"></i> Dokumentasi</a></li>
        <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <h1><i class="fa-solid fa-book"></i> Dokumentasi Sistem</h1>

    <div class="doc-container">
        <!-- TABS -->
        <div class="doc-tabs">
            <button class="doc-tab <?php echo ($tab === 'quick-start') ? 'active' : ''; ?>" onclick="switchTab('quick-start')">
                <i class="fa-solid fa-rocket"></i> Quick Start
            </button>
            <button class="doc-tab <?php echo ($tab === 'panduan') ? 'active' : ''; ?>" onclick="switchTab('panduan')">
                <i class="fa-solid fa-gears"></i> Panduan Pembayaran
            </button>
            <button class="doc-tab <?php echo ($tab === 'status-flow') ? 'active' : ''; ?>" onclick="switchTab('status-flow')">
                <i class="fa-solid fa-diagram-project"></i> Status Flow
            </button>
            <button class="doc-tab <?php echo ($tab === 'checklist') ? 'active' : ''; ?>" onclick="switchTab('checklist')">
                <i class="fa-solid fa-clipboard-check"></i> Checklist
            </button>
            <button class="doc-tab <?php echo ($tab === 'technical') ? 'active' : ''; ?>" onclick="switchTab('technical')">
                <i class="fa-solid fa-code"></i> Technical Docs
            </button>
            <button class="doc-tab <?php echo ($tab === 'solution') ? 'active' : ''; ?>" onclick="switchTab('solution')">
                <i class="fa-solid fa-check-circle"></i> Solusi
            </button>
        </div>

        <!-- QUICK START TAB -->
        <div id="quick-start" class="doc-content <?php echo ($tab === 'quick-start') ? 'active' : ''; ?>">
            <div class="doc-section">
                <h2><i class="fa-solid fa-rocket"></i> Quick Start Guide</h2>
                <p><strong>Cara tercepat untuk memulai sistem pembayaran</strong></p>
            </div>

            <div class="success-box">
                ✓ Database sudah berhasil ditambahkan! Sistem siap digunakan.
            </div>

            <div class="doc-section">
                <h3>⚙️ 5 Menit Konfigurasi</h3>
                <ol>
                    <li>Login sebagai admin</li>
                    <li>Menu → "Pengaturan Pembayaran"</li>
                    <li>Update: Nama Bank, Rekening, Nomor</li>
                    <li>Save ✓</li>
                    <li>Done!</li>
                </ol>
            </div>

            <div class="doc-section">
                <h3>🎯 Metode Pembayaran yang Didukung</h3>
                <table>
                    <tr>
                        <th>Metode</th>
                        <th>Deskripsi</th>
                    </tr>
                    <tr>
                        <td><strong>🔳 QRIS</strong></td>
                        <td>Auto QR Code (dynamic based on amount). E-wallet friendly. Scan & pay</td>
                    </tr>
                    <tr>
                        <td><strong>🏦 Transfer Bank</strong></td>
                        <td>Static account. Manual transfer. Copy nomor rekening</td>
                    </tr>
                </table>
            </div>

            <div class="doc-section">
                <h3>👤 User Flow</h3>
                <div class="flow-chart">
Checkout
   ↓
Pilih Metode Pembayaran (QRIS / Transfer Bank)
   ↓
Lihat Instruksi Pembayaran
   ↓
Lakukan Pembayaran
   ↓
Upload Bukti Pembayaran
   ↓
Tunggu Verifikasi Admin
                </div>
            </div>

            <div class="doc-section">
                <h3>✅ Admin Actions</h3>
                <div class="flow-chart">
Verifikasi Pembayaran
   ↓
Review Bukti Pembayaran
   ↓
Approve ✅ OR Reject ❌
   ↓
Auto Update Status Pesanan
                </div>
            </div>

            <div class="doc-section">
                <h3>📂 File Quick Reference</h3>
                <table>
                    <tr>
                        <th>File</th>
                        <th>Purpose</th>
                    </tr>
                    <tr>
                        <td><strong>payment_details.php</strong></td>
                        <td>👤 Payment Instructions - Instruksi pembayaran</td>
                    </tr>
                    <tr>
                        <td><strong>upload_bukti_pembayaran.php</strong></td>
                        <td>📸 Upload Proof - Upload bukti pembayaran</td>
                    </tr>
                    <tr>
                        <td><strong>verifikasi_pembayaran.php</strong></td>
                        <td>✅ Admin Verify - Verifikasi pembayaran</td>
                    </tr>
                    <tr>
                        <td><strong>pengaturan_pembayaran.php</strong></td>
                        <td>⚙️ Configure - Konfigurasi pembayaran</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- PANDUAN PEMBAYARAN TAB -->
        <div id="panduan" class="doc-content <?php echo ($tab === 'panduan') ? 'active' : ''; ?>">
            <div class="doc-section">
                <h2><i class="fa-solid fa-gears"></i> Panduan Integrasi QRIS & Transfer Bank</h2>
                <p><strong>Panduan lengkap implementasi sistem pembayaran</strong></p>
            </div>

            <div class="doc-section">
                <h3>📋 File-File yang Dibuat</h3>
                
                <h4>1. payment_details.php</h4>
                <ul>
                    <li>Halaman untuk menampilkan instruksi pembayaran</li>
                    <li>Menampilkan QR Code QRIS</li>
                    <li>Menampilkan detail rekening bank</li>
                    <li>Instruksi pembayaran langkah demi langkah</li>
                </ul>

                <h4>2. upload_bukti_pembayaran.php</h4>
                <ul>
                    <li>Halaman untuk upload bukti transfer/pembayaran</li>
                    <li>Validasi file (JPG, PNG, PDF, max 5MB)</li>
                    <li>Menyimpan data ke database</li>
                </ul>

                <h4>3. verifikasi_pembayaran.php</h4>
                <ul>
                    <li>Dashboard admin untuk verifikasi pembayaran</li>
                    <li>Menampilkan bukti pembayaran</li>
                    <li>Fitur approve/reject pembayaran</li>
                    <li>Tab untuk melihat riwayat verifikasi</li>
                </ul>

                <h4>4. pengaturan_pembayaran.php</h4>
                <ul>
                    <li>Halaman konfigurasi metode pembayaran</li>
                    <li>Setting data rekening bank</li>
                    <li>Setting QRIS configuration</li>
                </ul>


            </div>

            <div class="doc-section">
                <h3>🔧 Langkah Instalasi</h3>



                <h4>STEP 1: Update Data Admin</h4>
                <p>Di phpMyAdmin, jalankan query ini untuk set role admin:</p>
                <div class="code-block">UPDATE users SET role = 'admin' WHERE id_user = 1;</div>
                <p>Ganti <code>id_user = 1</code> dengan ID admin Anda.</p>

                <h4>STEP 2: Konfigurasi Metode Pembayaran</h4>
                <ol>
                    <li>Masuk ke <strong>phpMyAdmin</strong></li>
                    <li>Buka tabel <code>pengaturan_pembayaran</code></li>
                    <li>Edit data bank sesuai dengan data rekening Anda:
                        <ul>
                            <li><strong>nama_bank</strong>: Nama bank Anda</li>
                            <li><strong>nama_rekening</strong>: Nama pemilik rekening</li>
                            <li><strong>nomor_rekening</strong>: Nomor rekening</li>
                            <li><strong>swift_code</strong>: Swift code bank</li>
                        </ul>
                    </li>
                </ol>

                <h4>STEP 3: Buat Folder untuk Bukti Pembayaran</h4>
                <ol>
                    <li>Di folder root aplikasi (<code>c:\xampp\htdocs\penjualan_atk\</code>), buat folder baru bernama <code>bukti_pembayaran</code></li>
                    <li>Pastikan folder ini memiliki permission untuk write</li>
                </ol>
                <div class="code-block">mkdir bukti_pembayaran
chmod 777 bukti_pembayaran</div>
            </div>

            <div class="doc-section">
                <h3>🎯 Alur Pembayaran</h3>

                <h4>Dari Perspektif Pembeli:</h4>
                <ol>
                    <li><strong>Checkout</strong> → Pilih metode pembayaran (QRIS / Transfer Bank)</li>
                    <li><strong>Lihat Pesanan</strong> → Di halaman pesanan, klik tombol "Bayar" (hanya muncul jika status pending)</li>
                    <li><strong>Instruksi Pembayaran</strong> → Lihat detail pembayaran (bank/QRIS)</li>
                    <li><strong>Upload Bukti</strong> → Klik "Upload Bukti Pembayaran" dan upload screenshot/foto bukti</li>
                </ol>

                <h4>Dari Perspektif Admin:</h4>
                <ol>
                    <li><strong>Login Admin</strong> → Sidebar akan menampilkan menu "Verifikasi Pembayaran"</li>
                    <li><strong>Verifikasi Pembayaran</strong> → Lihat daftar pembayaran pending</li>
                    <li><strong>Review Bukti</strong> → Lihat bukti transfer/pembayaran</li>
                    <li><strong>Approve/Reject</strong> → Klik "Terima & Verifikasi" atau "Tolak Pembayaran"</li>
                    <li><strong>Status Update</strong> → Setelah approve, status pesanan berubah menjadi "Diproses"</li>
                </ol>
            </div>
        </div>

        <!-- STATUS FLOW TAB -->
        <div id="status-flow" class="doc-content <?php echo ($tab === 'status-flow') ? 'active' : ''; ?>">
            <div class="doc-section">
                <h2><i class="fa-solid fa-diagram-project"></i> Flow Status Pesanan</h2>
                <p><strong>Alur status pesanan yang benar dan lengkap</strong></p>
            </div>

            <div class="doc-section">
                <h3>🎯 Status Flow yang Benar</h3>
                <div class="flow-chart">
PENDING
  ↓
  (Menunggu Pembayaran)
  Admin approve di "Verifikasi Pembayaran"
  ↓
DIPROSES
  ↓
  (Sedang Dikemas/Dipersiapkan)
  Admin ubah status di "Kelola Pesanan"
  ↓
DIKIRIM
  ↓
  (Dalam Perjalanan ke Pembeli)
  Admin ubah status di "Kelola Pesanan"
  ↓
SELESAI
  ↓
  (Pesanan Selesai/Diterima Pembeli)
                </div>
            </div>

            <div class="doc-section">
                <h3>👥 Role dalam Flow Pesanan</h3>

                <h4>1. Pembeli:</h4>
                <ul>
                    <li>Checkout barang</li>
                    <li>Pilih metode pembayaran (QRIS/Transfer)</li>
                    <li>Upload bukti pembayaran</li>
                    <li>Lihat status di "Pesanan Saya"</li>
                    <li>Tunggu konfirmasi admin</li>
                </ul>

                <h4>2. Admin:</h4>
                <ul>
                    <li><strong>Verifikasi Pembayaran</strong> → Approve/Reject bukti</li>
                    <li><strong>Kelola Pesanan</strong> → Ubah status pesanan</li>
                    <li><strong>Detail Pesanan</strong> → Lihat data lengkap</li>
                </ul>
            </div>

            <div class="doc-section">
                <h3>📋 Langkah-Langkah Update Status</h3>

                <h4>STEP 1: Approve Pembayaran</h4>
                <div class="flow-chart">
1. Login sebagai admin
2. Menu → "Verifikasi Pembayaran"
3. Tab "Menunggu Verifikasi"
4. Lihat bukti pembayaran
5. Klik "Terima & Verifikasi"
6. ✓ Status pesanan otomatis berubah: pending → diproses
                </div>

                <h4>STEP 2: Update ke Dikirim</h4>
                <div class="flow-chart">
1. Menu → "Kelola Pesanan"
2. Cari pesanan dengan status "Diproses"
3. Di action section, klik tombol "Dikirim"
4. ✓ Status berubah: diproses → dikirim
5. Siapkan barang untuk pengiriman
                </div>

                <h4>STEP 3: Finalisasi ke Selesai</h4>
                <div class="flow-chart">
1. Menu → "Kelola Pesanan"
2. Cari pesanan dengan status "Dikirim"
3. Di action section, klik tombol "Selesai"
4. ✓ Status berubah: dikirim → selesai
5. Transaksi selesai
                </div>
            </div>

            <div class="doc-section">
                <h3>🔀 Status Transitions</h3>

                <h4>Dari PENDING:</h4>
                <ul>
                    <li>✅ Bisa ke: <strong>DIPROSES</strong> (otomatis saat approve pembayaran)</li>
                    <li>❌ TIDAK bisa ke: DIKIRIM, SELESAI</li>
                </ul>

                <h4>Dari DIPROSES:</h4>
                <ul>
                    <li>✅ Bisa ke: <strong>DIKIRIM</strong> (manual via kelola_pesanan.php)</li>
                    <li>✅ Bisa ke: <strong>PENDING</strong> (jika perlu reset)</li>
                    <li>❌ TIDAK bisa ke: SELESAI (harus via DIKIRIM)</li>
                </ul>

                <h4>Dari DIKIRIM:</h4>
                <ul>
                    <li>✅ Bisa ke: <strong>SELESAI</strong> (manual via kelola_pesanan.php)</li>
                    <li>✅ Bisa ke: <strong>DIPROSES</strong> (jika ada masalah)</li>
                    <li>❌ TIDAK bisa ke: PENDING</li>
                </ul>

                <h4>Dari SELESAI:</h4>
                <ul>
                    <li>❌ TIDAK bisa diubah (final status)</li>
                </ul>
            </div>
        </div>

        <!-- CHECKLIST TAB -->
        <div id="checklist" class="doc-content <?php echo ($tab === 'checklist') ? 'active' : ''; ?>">
            <div class="doc-section">
                <h2><i class="fa-solid fa-clipboard-check"></i> Checklist Instalasi</h2>
                <p><strong>Daftar verifikasi untuk memastikan semua berjalan dengan baik</strong></p>
            </div>

            <div class="doc-section">
                <h3>🎯 PRE-INSTALLATION</h3>
                <ul class="checklist">
                    <li>Backup database (recommended)</li>
                    <li>Backup aplikasi (recommended)</li>
                    <li>Pastikan akses admin tersedia</li>
                    <li>Periksa koneksi database aktif</li>
                    <li>Persiapkan data rekening bank</li>
                </ul>
            </div>

            <div class="doc-section">
                <h3>🔧 INSTALLATION STEPS</h3>

                <h4>Step 1: Database Setup (5 menit)</h4>
                <p>Pilih satu metode:</p>

                <h5>✓ Database Sudah Siap</h5>
                <ul class="checklist">
                    <li>Kolom metode_pembayaran & alamat_pengiriman di tabel pesanan</li>
                    <li>Kolom role di tabel users</li>
                    <li>Tabel pembayaran untuk bukti pembayaran</li>
                    <li>Tabel pengaturan_pembayaran untuk konfigurasi</li>
                </ul>

                <h4>Step 1: Verifikasi Database (5 menit)</h4>
                <ul class="checklist">
                    <li>Buka phpMyAdmin</li>
                    <li>Lihat tabel <code>pembayaran</code> sudah ada</li>
                    <li>Lihat tabel <code>pengaturan_pembayaran</code> sudah ada</li>
                    <li>Lihat kolom <code>metode_pembayaran</code> di tabel pesanan</li>
                    <li>Lihat kolom <code>alamat_pengiriman</code> di tabel pesanan</li>
                    <li>Lihat kolom <code>role</code> di tabel users</li>
                </ul>

                <h4>Step 2: Configure Payment Methods (5 menit)</h4>
                <ul class="checklist">
                    <li>Login sebagai admin</li>
                    <li>Menu Sidebar → "Pengaturan Pembayaran"</li>
                    <li>
                        <strong>Configure Bank Transfer:</strong>
                        <ul class="checklist">
                            <li>Masukkan Nama Bank</li>
                            <li>Masukkan Nama Pemilik Rekening</li>
                            <li>Masukkan Nomor Rekening</li>
                            <li>Masukkan SWIFT Code</li>
                            <li>Klik "Simpan Pengaturan Bank"</li>
                            <li>Lihat notifikasi "Pengaturan Bank berhasil disimpan!"</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Configure QRIS (Optional):</strong>
                        <ul class="checklist">
                            <li>Masukkan QRIS data jika ada merchant code</li>
                            <li>Atau biarkan kosong (sistem akan auto-generate)</li>
                            <li>Klik "Simpan Pengaturan QRIS"</li>
                        </ul>
                    </li>
                </ul>

                <h4>Step 3: Verify Folder Structure (2 menit)</h4>
                <ul class="checklist">
                    <li>Check folder <code>bukti_pembayaran/</code> ada di root aplikasi</li>
                    <li>Folder harus writeable (permission 777)</li>
                    <li>Folder kosong pada awalnya (normal)</li>
                </ul>
            </div>

            <div class="success-box">
                ✓ Jika semua checklist sudah tercentang, maka instalasi berhasil dan sistem siap digunakan!
            </div>
        </div>

        <!-- TECHNICAL DOCS TAB -->
        <div id="technical" class="doc-content <?php echo ($tab === 'technical') ? 'active' : ''; ?>">
            <div class="doc-section">
                <h2><i class="fa-solid fa-code"></i> Technical Documentation</h2>
                <p><strong>Dokumentasi teknis lengkap sistem pembayaran</strong></p>
            </div>

            <div class="doc-section">
                <h3>📦 File & Komponen yang Dibuat</h3>

                <h4>1. Frontend Pages (User)</h4>
                <table>
                    <tr>
                        <th>File</th>
                        <th>Deskripsi</th>
                    </tr>
                    <tr>
                        <td><code>payment_details.php</code></td>
                        <td>Halaman instruksi pembayaran (QRIS/Transfer)</td>
                    </tr>
                    <tr>
                        <td><code>upload_bukti_pembayaran.php</code></td>
                        <td>Halaman upload bukti pembayaran</td>
                    </tr>
                </table>

                <h4>2. Admin Pages</h4>
                <table>
                    <tr>
                        <th>File</th>
                        <th>Deskripsi</th>
                    </tr>
                    <tr>
                        <td><code>verifikasi_pembayaran.php</code></td>
                        <td>Dashboard verifikasi pembayaran dengan approve/reject</td>
                    </tr>
                    <tr>
                        <td><code>pengaturan_pembayaran.php</code></td>
                        <td>Halaman setting bank & QRIS</td>
                    </tr>
                    <tr>
                        <td><code>kelola_pesanan.php</code></td>
                        <td>Dashboard kelola status pesanan</td>
                    </tr>
                </table>

                <h4>3. Modified Files</h4>
                <table>
                    <tr>
                        <th>File</th>
                        <th>Perubahan</th>
                    </tr>
                    <tr>
                        <td><code>checkout.php</code></td>
                        <td>Tambah penyimpanan metode_pembayaran & redirect ke payment_details</td>
                    </tr>
                    <tr>
                        <td><code>pesanan.php</code></td>
                        <td>Tambah tombol "Bayar" untuk pending orders</td>
                    </tr>
                    <tr>
                        <td><code>update_status.php</code></td>
                        <td>Support semua status & admin verification</td>
                    </tr>
                </table>
            </div>

            <div class="doc-section">
                <h3>💾 Database Schema</h3>

                <h4>Tabel: pembayaran</h4>
                <div class="code-block">CREATE TABLE pembayaran (
  id_pembayaran INT PRIMARY KEY AUTO_INCREMENT,
  id_pesanan INT UNIQUE,
  bukti_pembayaran VARCHAR(255),
  catatan TEXT,
  tanggal_upload DATETIME,
  status ENUM('pending','verified','rejected'),
  tanggal_verifikasi DATETIME,
  diverifikasi_oleh INT,
  FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan)
);</div>

                <h4>Tabel: pengaturan_pembayaran</h4>
                <div class="code-block">CREATE TABLE pengaturan_pembayaran (
  id_pengaturan INT PRIMARY KEY AUTO_INCREMENT,
  metode VARCHAR(50) UNIQUE,
  nama_bank VARCHAR(100),
  nama_rekening VARCHAR(100),
  nomor_rekening VARCHAR(50),
  swift_code VARCHAR(20),
  qris_data TEXT
);</div>

                <h4>Modified Columns</h4>
                <p>Kolom baru ditambahkan ke tabel yang sudah ada:</p>
                <ul>
                    <li><code>pesanan.metode_pembayaran</code> - VARCHAR(50)</li>
                    <li><code>pesanan.alamat_pengiriman</code> - TEXT</li>
                    <li><code>users.role</code> - VARCHAR(20) DEFAULT 'user'</li>
                </ul>
            </div>

            <div class="doc-section">
                <h3>🔐 Security Features</h3>
                <ul>
                    <li>✅ File type validation (JPG, PNG, PDF only)</li>
                    <li>✅ File size limits (5MB max)</li>
                    <li>✅ Unique file naming with timestamp</li>
                    <li>✅ Role-based access control</li>
                    <li>✅ SQL injection prevention</li>
                    <li>✅ Session-based authentication</li>
                </ul>
            </div>

            <div class="doc-section">
                <h3>📂 Folder Structure</h3>
                <div class="code-block">penjualan_atk/
├── bukti_pembayaran/     (folder untuk menyimpan bukti)
├── payment_details.php
├── upload_bukti_pembayaran.php
├── verifikasi_pembayaran.php
├── pengaturan_pembayaran.php
├── kelola_pesanan.php
└── [file-file lainnya]</div>
            </div>
        </div>

        <!-- SOLUTION TAB -->
        <div id="solution" class="doc-content <?php echo ($tab === 'solution') ? 'active' : ''; ?>">
            <div class="doc-section">
                <h2><i class="fa-solid fa-check-circle"></i> Solusi Status Pesanan</h2>
                <p><strong>Solusi lengkap untuk masalah status pesanan yang tidak bisa berpindah</strong></p>
            </div>

            <div class="success-box">
                🟢 <strong>STATUS: SOLVED - SIAP DIGUNAKAN</strong>
                <br>Problem: Status pesanan tidak bisa berpindah dari pending ke diproses ke dikirim
                <br>Solusi: Halaman "Kelola Pesanan" baru dengan interface visual
            </div>

            <div class="doc-section">
                <h3>📦 Yang Sudah Ditambahkan</h3>

                <h4>1. Halaman Baru: kelola_pesanan.php</h4>
                <ul>
                    <li>✨ Visual status flow (pending → diproses → dikirim → selesai)</li>
                    <li>✨ Buttons untuk ubah status</li>
                    <li>✨ Filter by status</li>
                    <li>✨ Real-time status preview</li>
                    <li>✨ Detail pesanan terintegrasi</li>
                    <li>✨ Responsive design</li>
                </ul>

                <h4>2. File Update: update_status.php</h4>
                <ul>
                    <li>🔧 Support semua status (pending, diproses, dikirim, selesai)</li>
                    <li>🔧 Better validation</li>
                    <li>🔧 Admin role check</li>
                    <li>🔧 Improved error handling</li>
                </ul>

                <h4>3. Sidebar Update</h4>
                <ul>
                    <li>📍 File yang diupdate: verifikasi_pembayaran.php, pengaturan_pembayaran.php</li>
                    <li>✨ Tambah menu "Kelola Pesanan"</li>
                    <li>✨ Ubah link "Pesanan" ke "Kelola Pesanan"</li>
                    <li>✨ Konsistensi navigasi admin</li>
                </ul>
            </div>

            <div class="doc-section">
                <h3>🚀 Cara Menggunakan (SUPER MUDAH!)</h3>

                <h4>Langkah 1: Lihat Pesanan</h4>
                <ol>
                    <li>Login admin</li>
                    <li>Sidebar → "Kelola Pesanan"</li>
                    <li>Lihat daftar pesanan dengan visual status</li>
                </ol>

                <h4>Langkah 2: Verifikasi Pembayaran (jika perlu)</h4>
                <div class="note-box">
                    Jika status = "Menunggu Pembayaran":
                    <ol style="margin-top: 10px;">
                        <li>Sidebar → "Verifikasi Pembayaran"</li>
                        <li>Review bukti pembayaran</li>
                        <li>Click "Terima & Verifikasi"</li>
                        <li>✓ Status otomatis berubah ke "Diproses"</li>
                    </ol>
                </div>

                <h4>Langkah 3: Update Status Pengiriman</h4>
                <ol>
                    <li>Kembali ke "Kelola Pesanan"</li>
                    <li>Pesanan dengan status "Diproses":
                        <ul>
                            <li>Click tombol "Dikirim"</li>
                            <li>✓ Status berubah</li>
                        </ul>
                    </li>
                    <li>Pesanan dengan status "Dikirim":
                        <ul>
                            <li>Click tombol "Selesai"</li>
                            <li>✓ Status final</li>
                        </ul>
                    </li>
                </ol>

                <div class="success-box">SELESAI! 🎉</div>
            </div>

            <div class="doc-section">
                <h3>📊 Status Flow Visual</h3>
                <div class="flow-chart">
START
  ↓
┌──────────────────┐
│ 1. PENDING       │  Menunggu Pembayaran
│ ⏳ Status Awal    │  - Pembeli upload bukti
└──────────────────┘
         ↓
    [Admin approve pembayaran]
         ↓
┌──────────────────┐
│ 2. DIPROSES      │  Sedang Diproses
│ ⚙️ In Progress    │  - Barang dikemas
└──────────────────┘
         ↓
    [Admin ubah di Kelola Pesanan]
         ↓
┌──────────────────┐
│ 3. DIKIRIM       │  Dalam Perjalanan
│ 📦 Shipping      │  - Barang dikirim
└──────────────────┘
         ↓
    [Admin ubah di Kelola Pesanan]
         ↓
┌──────────────────┐
│ 4. SELESAI       │  Pesanan Selesai
│ ✓ Completed      │  - Transaksi selesai
└──────────────────┘
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all content
    const contents = document.querySelectorAll('.doc-content');
    contents.forEach(content => {
        content.classList.remove('active');
    });

    // Remove active class from all tabs
    const tabs = document.querySelectorAll('.doc-tab');
    tabs.forEach(tab => {
        tab.classList.remove('active');
    });

    // Show selected content
    const selectedContent = document.getElementById(tabName);
    if (selectedContent) {
        selectedContent.classList.add('active');
    }

    // Add active class to clicked tab
    event.target.closest('.doc-tab').classList.add('active');

    // Update URL
    window.history.replaceState({}, '', '?tab=' + tabName);
}
</script>

</body>
</html>
