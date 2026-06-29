<?php
session_start();
include 'koneksi.php';

// CEK LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: form_login.php");
    exit;
}

$id_user = intval($_SESSION['id_user']);
$id_pesanan = intval($_GET['id'] ?? 0);

// AMBIL DATA PESANAN
$query = mysqli_query($conn, "
    SELECT * FROM pesanan 
    WHERE id_pesanan = $id_pesanan AND id_user = $id_user
");

if (!$query || mysqli_num_rows($query) == 0) {
    header("Location: pesanan.php");
    exit;
}

$pesanan = mysqli_fetch_assoc($query);

$error = '';
$success = '';

// PROSES UPLOAD BUKTI PEMBAYARAN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');
    
    // CEK FILE UPLOAD
    if (empty($_FILES['bukti_pembayaran']['name'])) {
        $error = "Silakan pilih file bukti pembayaran!";
    } else {
        $file_name = $_FILES['bukti_pembayaran']['name'];
        $file_size = $_FILES['bukti_pembayaran']['size'];
        $file_tmp = $_FILES['bukti_pembayaran']['tmp_name'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // VALIDASI TIPE FILE
        $allowed_ext = array('jpg', 'jpeg', 'png', 'pdf');
        if (!in_array(strtolower($file_ext), $allowed_ext)) {
            $error = "Format file tidak diizinkan! Gunakan: JPG, JPEG, PNG, atau PDF";
        } elseif ($file_size > 5242880) { // 5MB
            $error = "Ukuran file terlalu besar! Maksimal 5MB";
        } else {
            // BUAT NAMA FILE UNIK
            $new_file_name = 'bukti_' . $id_pesanan . '_' . time() . '.' . $file_ext;
            $upload_dir = __DIR__ . '/bukti_pembayaran/';
            
            // BUAT FOLDER JIKA BELUM ADA
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // UPLOAD FILE
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                // SIMPAN KE DATABASE
                $query_insert = mysqli_query($conn, "
                    INSERT INTO pembayaran (id_pesanan, bukti_pembayaran, catatan, tanggal_upload, status)
                    VALUES ('$id_pesanan', '$new_file_name', '$catatan', NOW(), 'pending')
                    ON DUPLICATE KEY UPDATE 
                    bukti_pembayaran = '$new_file_name',
                    catatan = '$catatan',
                    tanggal_upload = NOW(),
                    status = 'pending'
                ");
                
                if ($query_insert) {
                    $success = "Bukti pembayaran berhasil diupload! Admin akan memverifikasi dalam waktu 1x24 jam.";
                } else {
                    $error = "Gagal menyimpan data: " . mysqli_error($conn);
                }
            } else {
                $error = "Gagal mengupload file!";
            }
        }
    }
}

// AMBIL DATA PEMBAYARAN YANG SUDAH DIUPLOAD
$payment_query = mysqli_query($conn, "SELECT * FROM pembayaran WHERE id_pesanan = $id_pesanan");
$payment_data = null;
if ($payment_query && mysqli_num_rows($payment_query) > 0) {
    $payment_data = mysqli_fetch_assoc($payment_query);
}

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
    <title>Upload Bukti Pembayaran</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style_dashboard.css">
    <style>
        .upload-container {
            max-width: 500px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-box input[type="file"],
        .form-box textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            box-sizing: border-box;
        }
        .form-box textarea {
            resize: vertical;
            min-height: 100px;
        }
        .file-info {
            font-size: 12px;
            color: #666;
            margin: 8px 0;
        }
        .preview-section {
            margin: 20px 0;
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #007bff;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 6px;
            margin: 10px 0;
        }
        .status-message {
            padding: 12px;
            border-radius: 6px;
            margin: 16px 0;
        }
        .status-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-back {
            background: #6c757d;
            margin-top: 8px;
        }
        .btn-back:hover {
            background: #545b62;
        }
        .payment-info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 16px;
            border-radius: 4px;
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
            <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="keranjang.php"><i class="fa-solid fa-cart-shopping"></i> Keranjang</a></li>
            <li><a href="pesanan.php"><i class="fa-solid fa-receipt"></i> Pesanan</a></li>
            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN -->
    <div class="main">
        <div class="upload-container">
            <h1><i class="fa-solid fa-receipt"></i> Upload Bukti Pembayaran</h1>
            
            <div class="payment-info">
                <p><strong>ID Pesanan:</strong> <?php echo $pesanan['id_pesanan']; ?></p>
                <p><strong>Total Pembayaran:</strong> Rp <?php echo number_format($pesanan['total']); ?></p>
                <p><strong>Metode:</strong> <?php echo htmlspecialchars($pesanan['metode_pembayaran'] ?? 'Tidak diketahui'); ?></p>
            </div>

            <?php if ($error) { ?>
                <div class="status-message status-error">
                    <i class="fa-solid fa-circle-xmark"></i> <?php echo $error; ?>
                </div>
            <?php } ?>

            <?php if ($success) { ?>
                <div class="status-message status-success">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
                </div>
            <?php } ?>

            <?php if ($payment_data) { ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 16px; margin: 16px 0;">
                    <p style="margin: 0;">
                        <i class="fa-solid fa-circle-check" style="color: #155724;"></i>
                        <strong>Bukti pembayaran sudah diupload</strong>
                    </p>
                    <p style="margin: 8px 0 0 0; font-size: 12px; color: #155724;">
                        Status: <strong><?php echo ucfirst($payment_data['status']); ?></strong>
                    </p>
                </div>
            <?php } ?>

            <div class="form-box">
                <form method="POST" enctype="multipart/form-data">
                    <label><strong>Bukti Pembayaran</strong></label>
                    <input type="file" name="bukti_pembayaran" accept=".jpg,.jpeg,.png,.pdf" required>
                    <div class="file-info">
                        <i class="fa-solid fa-info-circle"></i>
                        Format: JPG, JPEG, PNG, atau PDF | Maksimal: 5MB
                    </div>

                    <label><strong>Catatan (Opsional)</strong></label>
                    <textarea name="catatan" placeholder="Contoh: Transfer dari BCA a.n Budi Santoso, ref 12345..."></textarea>

                    <button type="submit" class="btn">
                        <i class="fa-solid fa-upload"></i> Upload Bukti Pembayaran
                    </button>
                    
                    <a href="payment_details.php?id=<?php echo $id_pesanan; ?>" class="btn btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </form>
            </div>

            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 16px; margin-top: 20px;">
                <p style="margin: 0; color: #856404; font-size: 14px;">
                    <i class="fa-solid fa-lightbulb"></i>
                    <strong>Tips:</strong> Ambil screenshot atau foto bukti transfer Anda, kemudian upload di sini. 
                    Admin akan memverifikasi dan mengirimkan pesanan Anda setelah pembayaran dikonfirmasi.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
