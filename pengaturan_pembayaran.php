<?php
session_start();
include 'koneksi.php';

// CEK LOGIN DAN ROLE ADMIN
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = intval($_SESSION['id_user']);
$user_query = mysqli_query($conn, "SELECT role FROM users WHERE id_user = '$id_user'");
if (!$user_query || mysqli_num_rows($user_query) == 0) {
    header("Location: dashboard.php");
    exit;
}

$user_data = mysqli_fetch_assoc($user_query);
if ($user_data['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$msg = '';
$error = '';

// PROSES UPDATE PENGATURAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode = mysqli_real_escape_string($conn, $_POST['metode']);
    
    if ($metode == 'Transfer Bank') {
        $nama_bank = mysqli_real_escape_string($conn, $_POST['nama_bank']);
        $nama_rekening = mysqli_real_escape_string($conn, $_POST['nama_rekening']);
        $nomor_rekening = mysqli_real_escape_string($conn, $_POST['nomor_rekening']);
        $swift_code = mysqli_real_escape_string($conn, $_POST['swift_code']);
        
        $query = mysqli_query($conn, "
            UPDATE pengaturan_pembayaran 
            SET nama_bank = '$nama_bank',
                nama_rekening = '$nama_rekening',
                nomor_rekening = '$nomor_rekening',
                swift_code = '$swift_code'
            WHERE metode = 'Transfer Bank'
        ");
        
        if ($query) {
            $msg = "Pengaturan Bank berhasil disimpan!";
        } else {
            $error = "Gagal menyimpan pengaturan: " . mysqli_error($conn);
        }
    } elseif ($metode == 'QRIS') {
        $qris_code = mysqli_real_escape_string($conn, $_POST['qris_code']);
        
        $query = mysqli_query($conn, "
            UPDATE pengaturan_pembayaran 
            SET qris_code = '$qris_code'
            WHERE metode = 'QRIS'
        ");
        
        if ($query) {
            $msg = "Pengaturan QRIS berhasil disimpan!";
        } else {
            $error = "Gagal menyimpan pengaturan: " . mysqli_error($conn);
        }
    }
}

// AMBIL PENGATURAN PEMBAYARAN
$query_bank = mysqli_query($conn, "SELECT * FROM pengaturan_pembayaran WHERE metode = 'Transfer Bank'");
$bank_data = mysqli_fetch_assoc($query_bank) ?? [];

$query_qris = mysqli_query($conn, "SELECT * FROM pengaturan_pembayaran WHERE metode = 'QRIS'");
$qris_data = mysqli_fetch_assoc($query_qris) ?? [];

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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pengaturan Pembayaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .setting-box {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .setting-box h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 12px;
        }
        .setting-box .form-group {
            margin-bottom: 16px;
        }
        .setting-box label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: bold;
        }
        .setting-box input[type="text"],
        .setting-box textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .setting-box textarea {
            resize: vertical;
            min-height: 100px;
        }
        .setting-box input[type="text"]:focus,
        .setting-box textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .form-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        .btn-save {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-save:hover {
            background: #218838;
        }
        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #1565c0;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 600px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_admin.php'; ?>

    <!-- MAIN -->
    <div class="main">
        <h1><i class="fa-solid fa-cog"></i> Pengaturan Pembayaran</h1>

        <?php if ($msg) { ?>
            <div class="message success">
                <i class="fa-solid fa-check-circle"></i> <?php echo $msg; ?>
            </div>
        <?php } ?>

        <?php if ($error) { ?>
            <div class="message error">
                <i class="fa-solid fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php } ?>

        <div class="settings-container">
            <!-- PENGATURAN TRANSFER BANK -->
            <div class="setting-box">
                <h3><i class="fa-solid fa-building"></i> Transfer Bank</h3>
                
                <div class="info-box">
                    <i class="fa-solid fa-info-circle"></i>
                    Update data rekening bank Anda di sini. Data ini akan ditampilkan kepada pembeli saat melakukan checkout.
                </div>

                <form method="POST">
                    <input type="hidden" name="metode" value="Transfer Bank">
                    
                    <div class="form-group">
                        <label for="nama_bank">Nama Bank</label>
                        <input type="text" id="nama_bank" name="nama_bank" 
                               value="<?php echo htmlspecialchars($bank_data['nama_bank'] ?? 'Bank BCA'); ?>" required>
                        <div class="form-hint">Contoh: Bank BCA, Bank Mandiri, Bank BNI, dll</div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label for="nama_rekening">Nama Pemilik Rekening</label>
                            <input type="text" id="nama_rekening" name="nama_rekening" 
                                   value="<?php echo htmlspecialchars($bank_data['nama_rekening'] ?? 'PT. PENJUALAN ATK'); ?>" required>
                            <div class="form-hint">Nama sesuai rekening bank</div>
                        </div>

                        <div class="form-group">
                            <label for="nomor_rekening">Nomor Rekening</label>
                            <input type="text" id="nomor_rekening" name="nomor_rekening" 
                                   value="<?php echo htmlspecialchars($bank_data['nomor_rekening'] ?? '1234567890'); ?>" required>
                            <div class="form-hint">Nomor rekening 10-16 digit</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="swift_code">SWIFT Code</label>
                        <input type="text" id="swift_code" name="swift_code" 
                               value="<?php echo htmlspecialchars($bank_data['swift_code'] ?? 'BCAINIDJA'); ?>">
                        <div class="form-hint">Opsional - Digunakan untuk transfer internasional. Contoh: BCAINIDJA, MANDINID</div>
                    </div>

                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-save"></i> Simpan Pengaturan Bank
                    </button>
                </form>
            </div>

            <!-- PENGATURAN QRIS -->
            <div class="setting-box">
                <h3><i class="fa-solid fa-qrcode"></i> QRIS</h3>
                
                <div class="info-box">
                    <i class="fa-solid fa-info-circle"></i>
                    Masukkan data QRIS Anda. Biarkan kosong jika tidak menggunakan QRIS. QR Code akan dibuat otomatis berdasarkan nominal pembayaran.
                </div>

                <form method="POST">
                    <input type="hidden" name="metode" value="QRIS">
                    
                    <div class="form-group">
                        <label for="qris_code">Data QRIS / Merchant ID</label>
                        <textarea id="qris_code" name="qris_code" placeholder="Paste data QRIS Anda di sini (opsional)..."><?php echo htmlspecialchars($qris_data['qris_code'] ?? ''); ?></textarea>
                        <div class="form-hint">
                            Jika kosong, sistem akan menghasilkan QR Code dinamis. <br>
                            Format QRIS: 00020126360014COM.MIDTRANS... (dari dashboard Midtrans/Xendit)
                        </div>
                    </div>

                    <button type="submit" class="btn-save">
                        <i class="fa-solid fa-save"></i> Simpan Pengaturan QRIS
                    </button>
                </form>
            </div>

            <!-- INFO TAMBAHAN -->
            <div class="setting-box" style="background: #f0f9ff; border-color: #bfdbfe;">
                <h3><i class="fa-solid fa-circle-info"></i> Informasi Penting</h3>
                
                <div style="font-size: 14px; line-height: 1.6; color: #1e40af;">
                    <p><strong>📱 QRIS:</strong></p>
                    <ul>
                        <li>QR Code akan dibuat otomatis berdasarkan nominal pembayaran</li>
                        <li>Pembeli bisa scan dengan e-wallet (GCash, OVO, Dana, dll)</li>
                        <li>Tidak perlu memasukkan QR Code statis kecuali menggunakan Midtrans/Xendit</li>
                    </ul>

                    <p><strong>🏦 Transfer Bank:</strong></p>
                    <ul>
                        <li>Data rekening akan ditampilkan di halaman instruksi pembayaran</li>
                        <li>Pastikan nomor rekening benar dan aktif</li>
                        <li>Pembeli dapat meng-copy nomor rekening dengan satu klik</li>
                    </ul>

                    <p><strong>✅ Tips:</strong></p>
                    <ul>
                        <li>Update pengaturan sebelum menerima pesanan</li>
                        <li>Gunakan both methods untuk fleksibilitas maksimal</li>
                        <li>Monitor pembayaran di halaman "Verifikasi Pembayaran"</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
