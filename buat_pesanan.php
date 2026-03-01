<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if(!isset($_SESSION['nama'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';
$nama_user = $_SESSION['nama']; // Sekarang menyimpan NAMA user
$total_harga = 0;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $jenis_barang = mysqli_real_escape_string($conn, $_POST['jenis_barang']);
    $jumlah_barang = (int)$_POST['jumlah_barang'];
    $berat_barang = mysqli_real_escape_string($conn, $_POST['berat_barang']);
    $ambil = mysqli_real_escape_string($conn, $_POST['ambil']);
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan']);
    
    // Ambil koordinat dari input tersembunyi
    $lat_ambil = isset($_POST['lat_ambil']) ? (float)$_POST['lat_ambil'] : 0;
    $lng_ambil = isset($_POST['lng_ambil']) ? (float)$_POST['lng_ambil'] : 0;
    $lat_tujuan = isset($_POST['lat_tujuan']) ? (float)$_POST['lat_tujuan'] : 0;
    $lng_tujuan = isset($_POST['lng_tujuan']) ? (float)$_POST['lng_tujuan'] : 0;
    $jarak = isset($_POST['jarak_meter']) ? (float)$_POST['jarak_meter'] : 0;
    $total_harga = isset($_POST['total_harga']) ? (float)$_POST['total_harga'] : 0;
    
    // Konversi berat barang ke format database
    $berat_db = '';
    switch($berat_barang) {
        case 'ringan': $berat_db = 'kurang dari 2 kg'; break;
        case 'sedang': $berat_db = '2 sampai 4 kg'; break;
        case 'berat': $berat_db = 'lebih dari 4 kg'; break;
        default: $berat_db = 'kurang dari 2 kg';
    }
    
    // Handle upload multiple foto
    $foto_barang_array = [];
    if(isset($_FILES['foto_barang']) && count($_FILES['foto_barang']['name']) > 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB per file
        $max_total_files = 5;
        
        $file_count = min(count($_FILES['foto_barang']['name']), $max_total_files);
        $upload_dir = 'uploads/';
        
        if(!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        for($i = 0; $i < $file_count; $i++) {
            if($_FILES['foto_barang']['error'][$i] == 0) {
                if(in_array($_FILES['foto_barang']['type'][$i], $allowed_types)) {
                    if($_FILES['foto_barang']['size'][$i] <= $max_size) {
                        $foto_name = time() . '_' . $i . '_' . basename($_FILES['foto_barang']['name'][$i]);
                        $target_file = $upload_dir . $foto_name;
                        
                        if(move_uploaded_file($_FILES['foto_barang']['tmp_name'][$i], $target_file)) {
                            $foto_barang_array[] = $target_file;
                        }
                    }
                }
            }
        }
    }
    
    $foto_barang_json = !empty($foto_barang_array) ? json_encode($foto_barang_array) : '';
    
    // Validasi
    if(empty($jenis_barang) || empty($jumlah_barang) || empty($berat_barang) || empty($ambil) || empty($tujuan)) {
        $error = "Harap lengkapi semua field yang wajib diisi!";
    } elseif($jumlah_barang <= 0) {
        $error = "Jumlah barang harus lebih dari 0!";
    } elseif($lat_ambil == 0 || $lng_ambil == 0 || $lat_tujuan == 0 || $lng_tujuan == 0) {
        $error = "Harap tentukan lokasi pengambilan dan pengantaran di peta!";
    } elseif($jarak <= 0) {
        $error = "Jarak tidak valid! Pastikan telah menentukan lokasi di peta.";
    } else {
        // Insert ke database dengan struktur yang sesuai
        $query = "INSERT INTO orders (nama, jenis_barang, jumlah_barang, berat_barang, posisi_ambil, pengiriman, 
                  catatan, foto_barang, jarak_meter, harga, status, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = mysqli_prepare($conn, $query);
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "ssisssssdi", 
                $nama_user,
                $jenis_barang, 
                $jumlah_barang,
                $berat_db,
                $ambil,
                $tujuan,
                $catatan, 
                $foto_barang_json,
                $jarak,
                $total_harga
            );
            
            if(mysqli_stmt_execute($stmt)) {
                $last_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                header("Location: riwayat_pesanan.php?success=1&id=" . $last_id);
                exit();
            } else {
                $error = "Gagal membuat pesanan: " . mysqli_error($conn);
                mysqli_stmt_close($stmt);
            }
        } else {
            $error = "Error dalam persiapan query: " . mysqli_error($conn);
        }
    }
}

// Ambil 3 riwayat pesanan terbaru user
$riwayat_result = false;
$stmt_riwayat = false;

if(!empty($nama_user)) {
    $riwayat_query = "SELECT id, jenis_barang, jumlah_barang, berat_barang, 
                      status, created_at, jarak_meter, harga 
                      FROM orders 
                      WHERE nama = ? 
                      ORDER BY id DESC 
                      LIMIT 3";
    
    $stmt_riwayat = mysqli_prepare($conn, $riwayat_query);
    
    if($stmt_riwayat) {
        mysqli_stmt_bind_param($stmt_riwayat, "s", $nama_user);
        mysqli_stmt_execute($stmt_riwayat);
        $riwayat_result = mysqli_stmt_get_result($stmt_riwayat);
    }
}

// Fungsi helper
function getStatusIndo($status) {
    $statusMap = [
        'pending' => 'Menunggu',
        'processing' => 'Diproses',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan'
    ];
    return $statusMap[$status] ?? $status;
}

function getBeratLabel($berat) {
    $beratMap = [
        'kurang dari 2 kg' => '< 2 kg',
        '2 sampai 4 kg' => '2-4 kg',
        'lebih dari 4 kg' => '> 4 kg',
        'ringan' => '< 2 kg',
        'sedang' => '2-4 kg',
        'berat' => '> 4 kg'
    ];
    return $beratMap[$berat] ?? '-';
}

function getStatusClass($status) {
    switch($status) {
        case 'pending': return 'status-menunggu';
        case 'processing': return 'status-mengirim';
        case 'completed': return 'status-selesai';
        case 'cancelled': return 'status-dibatalkan';
        default: return 'status-menunggu';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Foto/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Pesanan - SmariDelivery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0A6A2A;
            --primary-dark: #087532;
            --light: #f8f9fa;
            --dark: #333333;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background-color: white;
            box-shadow: var(--shadow);
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 9999;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            height: 70px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            position: relative;
            padding: 5px 0;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: var(--primary);
            left: 0;
            bottom: 0;
            transition: var(--transition);
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .nav-links a.active {
            color: var(--primary);
            font-weight: 600;
        }

        .nav-links a.active::after {
            width: 100%;
        }

        .cta-button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .cta-button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            margin-top: 100px;
            padding-bottom: 50px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title h1 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .page-title p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        /* Form Container */
        .form-container {
            background-color: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: var(--shadow);
            margin-bottom: 40px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }

        .required {
            color: var(--danger);
        }

        .form-help {
            color: var(--gray);
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Roboto', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
            outline: none;
        }

        .form-group select {
            background-color: white;
            cursor: pointer;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(10, 106, 42, 0.1);
        }

        /* Price Box */
        .price-box {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            box-shadow: 0 5px 15px rgba(10, 106, 42, 0.2);
            color: white;
            text-align: center;
            display: none;
        }

        .price-box.visible {
            display: block;
        }

        .price-label {
            font-size: 1rem;
            margin-bottom: 10px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .price-total {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .price-info {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-top: 15px;
            font-style: italic;
        }

        /* Map Container */
        .map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            position: relative;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .location-info {
            background: white;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            border: 1px solid #ddd;
        }

        .location-details {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .location-item {
            text-align: center;
            flex: 1;
        }

        .distance-display {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--primary);
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            background: rgba(10, 106, 42, 0.1);
            border-radius: 5px;
        }

        /* Marker styles */
        .marker-pickup {
            background-color: var(--primary);
            border: 2px solid white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
        }

        .marker-delivery {
            background-color: var(--danger);
            border: 2px solid white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
        }

        /* Catatan Container */
        .catatan-container {
            position: relative;
            width: 100%;
        }

        .catatan-container textarea {
            width: 100%;
            padding: 12px;
            padding-bottom: 50px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            font-family: 'Roboto', sans-serif;
            resize: none;
            box-sizing: border-box;
            transition: var(--transition);
            outline: none;
            line-height: 1.5;
        }

        .catatan-container textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(10, 106, 42, 0.1);
        }

        /* Upload Button */
        .upload-btn-wrapper {
            position: absolute;
            bottom: 10px;
            right: 10px;
            z-index: 10;
        }

        .upload-btn {
            cursor: pointer;
            background: white;
            border: 1px solid var(--primary);
            padding: 6px 10px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: var(--primary);
            transition: var(--transition);
            box-shadow: 0 1px 3px rgba(10, 106, 42, 0.1);
        }

        .upload-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(10, 106, 42, 0.2);
        }

        .upload-icon {
            font-size: 14px;
        }

        .upload-text {
            font-size: 12px;
        }

        .file-input {
            display: none;
        }

        /* File Preview */
        .file-preview-container {
            margin-top: 15px;
            display: none;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .preview-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
            background: white;
        }

        .preview-image {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 3px;
            cursor: pointer;
        }

        .preview-info {
            font-size: 11px;
            color: var(--gray);
            margin-top: 3px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .remove-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .file-count {
            background: var(--primary);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 5px;
        }

        /* Messages */
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .message.success {
            background-color: rgba(40, 167, 69, 0.1);
            border: 1px solid var(--success);
            color: #155724;
        }

        .message.error {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid var(--danger);
            color: #721c24;
        }

        /* Submit Button */
        .form-submit {
            margin-top: 30px;
            text-align: right;
        }

        /* History Section */
        .history-container {
            background-color: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: var(--shadow);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-gray);
        }

        .history-title {
            font-size: 1.8rem;
            color: var(--dark);
        }

        .view-all {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .view-all:hover {
            color: var(--primary-dark);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th {
            background-color: var(--light-gray);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
        }

        .history-table td {
            padding: 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-menunggu {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-mengirim {
            background-color: #d4edda;
            color: #155724;
        }

        .status-selesai {
            background-color: #c3e6cb;
            color: #155724;
        }

        .status-dibatalkan {
            background-color: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ddd;
        }

        /* Preview Modal */
        .preview-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            overflow: auto;
        }

        .preview-modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: zoom 0.3s;
        }

        @keyframes zoom {
            from {transform: translate(-50%, -50%) scale(0.8); opacity: 0;}
            to {transform: translate(-50%, -50%) scale(1); opacity: 1;}
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            z-index: 10000;
        }

        .modal-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            transform: translateY(-50%);
        }

        .modal-nav-btn {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            font-size: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-nav-btn:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-top: 80px;
            }
            
            .form-container,
            .history-container {
                padding: 20px;
            }
            
            .map-container {
                height: 300px;
            }
            
            .location-details {
                flex-direction: column;
                gap: 10px;
            }
            
            .history-table {
                display: block;
                overflow-x: auto;
            }
            
            .price-total {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="nav">
                <a href="index.php" class="logo">
                    <img src="Foto/logo.png" alt="SmariDelivery Logo" height="50px" width="65px">
                    SmariDelivery
                </a>
                
                <div class="nav-links">
                    <a href="index.php">Beranda</a>
                    <a href="buat_pesanan.php" class="active">Buat Pesanan</a>
                    <a href="riwayat_pesanan.php">Riwayat Pesanan</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Title -->
            <div class="page-title">
                <h1>Buat Pesanan Baru</h1>
                <p>Isi formulir di bawah ini dan tentukan lokasi di peta</p>
            </div>

            <!-- Form Container -->
            <div class="form-container">
                <?php if($error): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" id="orderForm">
                    <!-- Jenis Barang -->
                    <div class="form-group">
                        <label for="jenis_barang">Jenis Barang <span class="required">*</span></label>
                        <input type="text" id="jenis_barang" name="jenis_barang" 
                               placeholder="Contoh: Makanan, Buku, Alat Tulis, DLL" 
                               value="<?php echo isset($_POST['jenis_barang']) ? htmlspecialchars($_POST['jenis_barang']) : ''; ?>" 
                               required>
                    </div>

                    <!-- Jumlah Barang -->
                    <div class="form-group">
                        <label for="jumlah_barang">Jumlah Barang <span class="required">*</span></label>
                        <input type="number" id="jumlah_barang" name="jumlah_barang" 
                               min="1" max="100" 
                               value="<?php echo isset($_POST['jumlah_barang']) ? htmlspecialchars($_POST['jumlah_barang']) : '1'; ?>" 
                               required>
                    </div>

                    <!-- Berat Barang -->
                    <div class="form-group">
                        <label for="berat_barang">Perkiraan Berat Barang <span class="required">*</span></label>
                        <select id="berat_barang" name="berat_barang" required onchange="calculatePrice()">
                            <option value="">Pilih Berat Barang</option>
                            <option value="ringan" <?php echo (isset($_POST['berat_barang']) && $_POST['berat_barang'] == 'ringan') ? 'selected' : ''; ?>>Ringan (kurang dari 2 kg)</option>
                            <option value="sedang" <?php echo (isset($_POST['berat_barang']) && $_POST['berat_barang'] == 'sedang') ? 'selected' : ''; ?>>Sedang (2 - 4 kg)</option>
                            <option value="berat" <?php echo (isset($_POST['berat_barang']) && $_POST['berat_barang'] == 'berat') ? 'selected' : ''; ?>>Berat (lebih dari 4 kg)</option>
                        </select>
                        <small class="form-help">Pilih perkiraan berat total barang yang akan dikirim</small>
                    </div>

                    <!-- Map Container -->
                    <div class="form-group">
                        <label>Tentukan Lokasi di Peta <span class="required">*</span></label>
                        <div class="map-container">
                            <div id="map"></div>
                            <div class="map-controls">
                                <button type="button" id="btnPickup" class="cta-button" style="padding: 8px 15px; font-size: 0.9rem; margin-bottom: 5px;">
                                    <i class="fas fa-map-marker-alt"></i> Set Lokasi Pengambilan
                                </button>
                                <button type="button" id="btnDelivery" class="cta-button" style="padding: 8px 15px; font-size: 0.9rem; background: var(--danger);">
                                    <i class="fas fa-flag-checkered"></i> Set Lokasi Pengantaran
                                </button>
                            </div>
                        </div>
                        
                        <!-- Distance Display -->
                        <div id="distanceDisplay" class="distance-display" style="display: none;">
                            Jarak: <span id="distanceValue">0</span> meter
                        </div>
                        
                        <!-- Location Info -->
                        <div class="location-info">
                            <div class="location-details">
                                <div class="location-item">
                                    <strong><i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> Pengambilan:</strong>
                                    <div id="pickupAddress">Belum ditentukan</div>
                                    <small id="pickupCoords"></small>
                                </div>
                                <div class="location-item">
                                    <strong><i class="fas fa-flag-checkered" style="color: var(--danger);"></i> Pengantaran:</strong>
                                    <div id="deliveryAddress">Belum ditentukan</div>
                                    <small id="deliveryCoords"></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden inputs -->
                        <input type="hidden" id="lat_ambil" name="lat_ambil" value="<?php echo isset($_POST['lat_ambil']) ? $_POST['lat_ambil'] : ''; ?>">
                        <input type="hidden" id="lng_ambil" name="lng_ambil" value="<?php echo isset($_POST['lng_ambil']) ? $_POST['lng_ambil'] : ''; ?>">
                        <input type="hidden" id="lat_tujuan" name="lat_tujuan" value="<?php echo isset($_POST['lat_tujuan']) ? $_POST['lat_tujuan'] : ''; ?>">
                        <input type="hidden" id="lng_tujuan" name="lng_tujuan" value="<?php echo isset($_POST['lng_tujuan']) ? $_POST['lng_tujuan'] : ''; ?>">
                        <input type="hidden" id="jarak_meter" name="jarak_meter" value="0">
                        <input type="hidden" id="total_harga" name="total_harga" value="0">
                    </div>

                    <!-- Price Box -->
                    <div id="priceBox" class="price-box">
                        <div class="price-label">
                            <i class="fas fa-money-bill-wave"></i> Total Biaya Pengiriman
                        </div>
                        <div class="price-total" id="totalCost">Rp 0</div>
                        <div class="price-info">
                            Harga berdasarkan jarak dan berat barang
                        </div>
                    </div>

                    <!-- Alamat Pengambilan -->
                    <div class="form-group">
                        <label for="ambil">Alamat Pengambilan <span class="required">*</span></label>
                        <input type="text" id="ambil" name="ambil" 
                               placeholder="Contoh: Kantin Sekolah, Warung Depan Sekolah, Perpustakaan" 
                               value="<?php echo isset($_POST['ambil']) ? htmlspecialchars($_POST['ambil']) : ''; ?>" 
                               required>
                    </div>
                    
                    <!-- Alamat Pengantaran -->
                    <div class="form-group">
                        <label for="tujuan">Alamat Pengantaran <span class="required">*</span></label>
                        <input type="text" id="tujuan" name="tujuan" 
                               placeholder="Contoh: Kirim ke Kelas X, Antar ke Lab Komputer, Ruang Guru" 
                               value="<?php echo isset($_POST['tujuan']) ? htmlspecialchars($_POST['tujuan']) : ''; ?>" 
                               required>
                    </div>

                    <!-- Catatan + Upload Foto -->
                    <div class="form-group">
                        <label for="catatan">Catatan Tambahan</label>
                        <div class="catatan-container">
                            <textarea id="catatan" name="catatan" rows="4" 
                                      placeholder="Tambahkan catatan atau instruksi khusus (opsional)"><?php echo isset($_POST['catatan']) ? htmlspecialchars($_POST['catatan']) : ''; ?></textarea>
                            
                            <div class="upload-btn-wrapper">
                                <label for="foto_barang" class="upload-btn">
                                    <span class="upload-icon">📷</span>
                                    <span class="upload-text">Upload Foto</span>
                                    <input type="file" id="foto_barang" name="foto_barang[]" 
                                           accept="image/*" 
                                           class="file-input"
                                           multiple
                                           onchange="previewMultipleFiles(this)">
                                </label>
                            </div>
                        </div>
                        <small class="form-help">Foto opsional (max 2MB per file, maks 5 foto)</small>
                        <div id="filePreview" class="file-preview-container"></div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-submit">
                        <button type="submit" class="cta-button" style="width: 100%; padding: 15px; font-size: 1.1rem;">
                            <i class="fas fa-paper-plane"></i> Kirim Pesanan
                        </button>
                    </div>
                </form>
            </div>

            <!-- History Section - 3 Pesanan Terbaru -->
            <div class="history-container">
                <div class="history-header">
                    <h2 class="history-title">Pesanan Terbaru</h2>
                    <a href="riwayat_pesanan.php" class="view-all">
                        Lihat Semua <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <?php if($riwayat_result && mysqli_num_rows($riwayat_result) > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Jenis Barang</th>
                                <th>Jumlah</th>
                                <th>Berat</th>
                                <th>Jarak</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($riwayat_result)): ?>
                            <tr>
                                <td>#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($row['jenis_barang'] ?? '-'); ?></td>
                                <td><?php echo ($row['jumlah_barang'] ?? '0') . ' pcs'; ?></td>
                                <td><?php echo getBeratLabel($row['berat_barang'] ?? ''); ?></td>
                                <td><?php echo !empty($row['jarak_meter']) ? number_format($row['jarak_meter'], 0) . ' m' : '-'; ?></td>
                                <td><?php echo !empty($row['harga']) ? 'Rp ' . number_format($row['harga'], 0, ',', '.') : '-'; ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusClass($row['status'] ?? 'pending'); ?>">
                                        <?php echo getStatusIndo($row['status'] ?? 'pending'); ?>
                                    </span>
                                </td>
                                <td><?php echo isset($row['created_at']) ? date('d/m/Y', strtotime($row['created_at'])) : ''; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Belum ada pesanan</h3>
                        <p>Buat pesanan pertama Anda sekarang!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Preview Modal -->
    <div id="previewModal" class="preview-modal">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <div class="modal-nav">
            <button class="modal-nav-btn" onclick="prevImage()">❮</button>
            <button class="modal-nav-btn" onclick="nextImage()">❯</button>
        </div>
        <img class="preview-modal-content" id="modalImage">
        <div id="modalCaption" style="text-align: center; color: white; padding: 20px;"></div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Koordinat titik awal
        const DEFAULT_LAT = -0.45006438692692813;
        const DEFAULT_LNG = 117.15694989632648;
        const DEFAULT_ZOOM = 17;
        
        // Harga pengiriman
        const PRICE_PER_10_METERS = 500;
        const WEIGHT_LIGHT = 1000;
        const WEIGHT_MEDIUM = 3000;
        const WEIGHT_HEAVY = 7000;
        
        // Inisialisasi peta
        let map = L.map('map').setView([DEFAULT_LAT, DEFAULT_LNG], DEFAULT_ZOOM);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        L.marker([DEFAULT_LAT, DEFAULT_LNG])
            .addTo(map)
            .bindPopup('<b>Lokasi Sekolah</b><br>SmariDelivery Service Point')
            .openPopup();
        
        // Variabel marker
        let pickupMarker = null;
        let deliveryMarker = null;
        let routeLine = null;
        let pickupCoords = null;
        let deliveryCoords = null;
        let isSettingPickup = false;
        let isSettingDelivery = false;
        
        // Fungsi hitung biaya
        function calculatePrice() {
            const beratBarang = document.getElementById('berat_barang').value;
            const jarakMeter = parseFloat(document.getElementById('jarak_meter').value) || 0;
            const priceBox = document.getElementById('priceBox');
            
            if (!beratBarang || jarakMeter <= 0) {
                priceBox.classList.remove('visible');
                document.getElementById('total_harga').value = 0;
                return;
            }
            
            let distanceCost = 0;
            if (jarakMeter > 0) {
                const distanceUnits = Math.ceil(jarakMeter / 10);
                distanceCost = distanceUnits * PRICE_PER_10_METERS;
            }
            
            let weightCost = 0;
            if (beratBarang) {
                switch(beratBarang) {
                    case 'ringan': weightCost = WEIGHT_LIGHT; break;
                    case 'sedang': weightCost = WEIGHT_MEDIUM; break;
                    case 'berat': weightCost = WEIGHT_HEAVY; break;
                }
            }
            
            const totalCost = distanceCost + weightCost;
            
            const formatRupiah = (amount) => {
                return 'Rp ' + amount.toLocaleString('id-ID');
            };
            
            document.getElementById('totalCost').textContent = formatRupiah(totalCost);
            priceBox.classList.add('visible');
            document.getElementById('total_harga').value = totalCost;
        }
        
        // Fungsi alamat
        async function getAddressFromCoords(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                const data = await response.json();
                return data.display_name || 'Alamat tidak ditemukan';
            } catch (error) {
                return 'Alamat tidak ditemukan';
            }
        }
        
        function getSimpleAddress(lat, lng) {
            const diffLat = Math.abs(lat - DEFAULT_LAT);
            const diffLng = Math.abs(lng - DEFAULT_LNG);
            
            if (diffLat < 0.005 && diffLng < 0.005) {
                if (lat > DEFAULT_LAT) return 'Area Utara Sekolah';
                if (lat < DEFAULT_LAT) return 'Area Selatan Sekolah';
                if (lng > DEFAULT_LNG) return 'Area Timur Sekolah';
                if (lng < DEFAULT_LNG) return 'Area Barat Sekolah';
            }
            return `Lokasi: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        }
        
        // Fungsi hitung jarak
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3;
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;
            
            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            
            return R * c;
        }
        
        // Fungsi gambar rute
        function drawRoute() {
            if (pickupCoords && deliveryCoords) {
                if (routeLine) map.removeLayer(routeLine);
                
                routeLine = L.polyline([pickupCoords, deliveryCoords], {
                    color: 'blue',
                    weight: 3,
                    opacity: 0.7,
                    dashArray: '10, 10'
                }).addTo(map);
                
                const distance = calculateDistance(
                    pickupCoords[0], pickupCoords[1],
                    deliveryCoords[0], deliveryCoords[1]
                );
                
                document.getElementById('distanceValue').textContent = Math.round(distance);
                document.getElementById('distanceDisplay').style.display = 'block';
                document.getElementById('jarak_meter').value = Math.round(distance);
                
                calculatePrice();
                map.fitBounds([pickupCoords, deliveryCoords], { padding: [50, 50] });
            }
        }
        
        // Event listener klik peta
        map.on('click', async function(e) {
            const coords = [e.latlng.lat, e.latlng.lng];
            
            if (isSettingPickup) {
                pickupCoords = coords;
                
                if (pickupMarker) map.removeLayer(pickupMarker);
                
                pickupMarker = L.marker(coords, {
                    icon: L.divIcon({
                        className: 'marker-pickup',
                        iconSize: [20, 20]
                    })
                }).addTo(map);
                
                pickupMarker.bindPopup('<b>Lokasi Pengambilan</b><br>Klik untuk detail').openPopup();
                
                try {
                    const address = await getAddressFromCoords(coords[0], coords[1]);
                    document.getElementById('pickupAddress').textContent = address;
                } catch {
                    document.getElementById('pickupAddress').textContent = getSimpleAddress(coords[0], coords[1]);
                }
                
                document.getElementById('pickupCoords').textContent = `${coords[0].toFixed(6)}, ${coords[1].toFixed(6)}`;
                document.getElementById('lat_ambil').value = coords[0];
                document.getElementById('lng_ambil').value = coords[1];
                
                isSettingPickup = false;
                document.getElementById('btnPickup').style.background = 'var(--primary)';
                
                if (deliveryCoords) drawRoute();
                
            } else if (isSettingDelivery) {
                deliveryCoords = coords;
                
                if (deliveryMarker) map.removeLayer(deliveryMarker);
                
                deliveryMarker = L.marker(coords, {
                    icon: L.divIcon({
                        className: 'marker-delivery',
                        iconSize: [20, 20]
                    })
                }).addTo(map);
                
                deliveryMarker.bindPopup('<b>Lokasi Pengantaran</b><br>Klik untuk detail').openPopup();
                
                try {
                    const address = await getAddressFromCoords(coords[0], coords[1]);
                    document.getElementById('deliveryAddress').textContent = address;
                } catch {
                    document.getElementById('deliveryAddress').textContent = getSimpleAddress(coords[0], coords[1]);
                }
                
                document.getElementById('deliveryCoords').textContent = `${coords[0].toFixed(6)}, ${coords[1].toFixed(6)}`;
                document.getElementById('lat_tujuan').value = coords[0];
                document.getElementById('lng_tujuan').value = coords[1];
                
                isSettingDelivery = false;
                document.getElementById('btnDelivery').style.background = 'var(--danger)';
                
                if (pickupCoords) drawRoute();
            }
        });
        
        // Event listener tombol
        document.getElementById('btnPickup').addEventListener('click', function() {
            isSettingPickup = true;
            isSettingDelivery = false;
            this.style.background = '#0A6A2A';
            document.getElementById('btnDelivery').style.background = 'var(--danger)';
            alert('Klik di peta untuk menandai lokasi pengambilan');
        });
        
        document.getElementById('btnDelivery').addEventListener('click', function() {
            isSettingDelivery = true;
            isSettingPickup = false;
            this.style.background = '#c82333';
            document.getElementById('btnPickup').style.background = 'var(--primary)';
            alert('Klik di peta untuk menandai lokasi pengantaran');
        });

        // Upload file
        let uploadedFiles = [];
        let currentImageIndex = 0;

        function previewMultipleFiles(input) {
            const previewContainer = document.getElementById('filePreview');
            
            if(input.files && input.files.length > 0) {
                uploadedFiles = [];
                const maxFiles = 5;
                
                if(input.files.length > maxFiles) {
                    previewContainer.innerHTML = `
                        <div style="color: var(--danger); background: #ffeaea; padding: 8px; border-radius: 4px;">
                            ⚠️ Maksimal ${maxFiles} foto yang dapat diupload
                        </div>
                    `;
                    previewContainer.style.display = 'block';
                    input.value = '';
                    return;
                }
                
                for(let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                    
                    if(fileSizeMB > 2) {
                        previewContainer.innerHTML = `
                            <div style="color: var(--danger); background: #ffeaea; padding: 8px; border-radius: 4px;">
                                ⚠️ File "${file.name}" terlalu besar (max 2MB)
                            </div>
                        `;
                        previewContainer.style.display = 'block';
                        input.value = '';
                        uploadedFiles = [];
                        return;
                    }
                    
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                    if(!allowedTypes.includes(file.type)) {
                        previewContainer.innerHTML = `
                            <div style="color: var(--danger); background: #ffeaea; padding: 8px; border-radius: 4px;">
                                ⚠️ File "${file.name}" bukan gambar
                            </div>
                        `;
                        previewContainer.style.display = 'block';
                        input.value = '';
                        uploadedFiles = [];
                        return;
                    }
                    
                    uploadedFiles.push(file);
                }
                
                displayFilePreviews();
            }
        }

        function displayFilePreviews() {
            const previewContainer = document.getElementById('filePreview');
            
            if(uploadedFiles.length === 0) {
                previewContainer.style.display = 'none';
                previewContainer.innerHTML = '';
                return;
            }
            
            let previewHTML = `
                <div style="color: var(--success); margin-bottom: 10px;">
                    📁 ${uploadedFiles.length} foto terpilih 
                    <span class="file-count">${uploadedFiles.length}/5</span>
                </div>
                <div class="preview-grid">
            `;
            
            uploadedFiles.forEach((file, index) => {
                const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
                const shortName = file.name.length > 15 ? 
                    file.name.substring(0, 12) + '...' : 
                    file.name;
                
                previewHTML += `
                    <div class="preview-item" id="preview-item-${index}">
                        <button type="button" class="remove-btn" onclick="removeFile(${index})">×</button>
                        <img id="preview-img-${index}" class="preview-image" 
                             alt="Preview" src="" 
                             onclick="openModal(${index})">
                        <div class="preview-info">${shortName}</div>
                        <div class="preview-info">${fileSizeMB} MB</div>
                    </div>
                `;
            });
            
            previewHTML += `</div>`;
            previewContainer.innerHTML = previewHTML;
            previewContainer.style.display = 'block';
            
            uploadedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imgElement = document.getElementById(`preview-img-${index}`);
                    if(imgElement) imgElement.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        function removeFile(index) {
            uploadedFiles.splice(index, 1);
            updateFileInput();
            displayFilePreviews();
        }

        function updateFileInput() {
            const fileInput = document.getElementById('foto_barang');
            const dataTransfer = new DataTransfer();
            uploadedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }

        // Modal functions
        function openModal(index) {
            currentImageIndex = index;
            const modal = document.getElementById("previewModal");
            const modalImg = document.getElementById("modalImage");
            const captionText = document.getElementById("modalCaption");
            const file = uploadedFiles[index];
            
            const reader = new FileReader();
            reader.onload = function(e) {
                modalImg.src = e.target.result;
                captionText.innerHTML = `${file.name} - ${(file.size / 1024 / 1024).toFixed(2)} MB - ${currentImageIndex + 1}/${uploadedFiles.length}`;
                modal.style.display = "block";
                document.body.style.overflow = "hidden";
            };
            reader.readAsDataURL(file);
        }

        function closeModal() {
            document.getElementById("previewModal").style.display = "none";
            document.body.style.overflow = "auto";
        }

        function nextImage() {
            currentImageIndex = (currentImageIndex + 1) % uploadedFiles.length;
            updateModalImage();
        }

        function prevImage() {
            currentImageIndex = (currentImageIndex - 1 + uploadedFiles.length) % uploadedFiles.length;
            updateModalImage();
        }

        function updateModalImage() {
            const modalImg = document.getElementById("modalImage");
            const captionText = document.getElementById("modalCaption");
            const file = uploadedFiles[currentImageIndex];
            
            const reader = new FileReader();
            reader.onload = function(e) {
                modalImg.src = e.target.result;
                captionText.innerHTML = `${file.name} - ${(file.size / 1024 / 1024).toFixed(2)} MB - ${currentImageIndex + 1}/${uploadedFiles.length}`;
            };
            reader.readAsDataURL(file);
        }

        // Event listeners modal
        document.getElementById('previewModal').addEventListener('click', function(event) {
            if (event.target === this) closeModal();
        });

        document.addEventListener('keydown', function(event) {
            const modal = document.getElementById("previewModal");
            if (event.key === "Escape" && modal.style.display === "block") closeModal();
            if (event.key === "ArrowRight" && modal.style.display === "block") nextImage();
            if (event.key === "ArrowLeft" && modal.style.display === "block") prevImage();
        });

        // Form validation
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            const jenisBarang = document.getElementById('jenis_barang').value.trim();
            const jumlahBarang = document.getElementById('jumlah_barang').value;
            const beratBarang = document.getElementById('berat_barang').value;
            const ambil = document.getElementById('ambil').value;
            const tujuan = document.getElementById('tujuan').value;
            const latAmbil = document.getElementById('lat_ambil').value;
            const lngAmbil = document.getElementById('lng_ambil').value;
            const latTujuan = document.getElementById('lat_tujuan').value;
            const lngTujuan = document.getElementById('lng_tujuan').value;
            
            let errors = [];
            
            if(!jenisBarang) errors.push('Jenis Barang wajib diisi');
            if(!jumlahBarang || jumlahBarang <= 0) errors.push('Jumlah Barang harus lebih dari 0');
            if(!beratBarang) errors.push('Berat Barang wajib dipilih');
            if(!ambil) errors.push('Alamat Pengambilan wajib diisi');
            if(!tujuan) errors.push('Alamat Pengantaran wajib diisi');
            if(!latAmbil || !lngAmbil) errors.push('Lokasi Pengambilan belum ditentukan di peta');
            if(!latTujuan || !lngTujuan) errors.push('Lokasi Pengantaran belum ditentukan di peta');
            
            if(errors.length > 0) {
                e.preventDefault();
                alert('Kesalahan:\n' + errors.join('\n'));
            }
        });
        
        document.getElementById('berat_barang').addEventListener('change', calculatePrice);
        
        // DOM Content Loaded
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('jenis_barang').focus();
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    const distanceFromSchool = calculateDistance(
                        userLat, userLng, 
                        DEFAULT_LAT, DEFAULT_LNG
                    );
                    
                    if (distanceFromSchool < 50000) {
                        map.setView([userLat, userLng], 15);
                    }
                }, function(error) {
                    map.setView([DEFAULT_LAT, DEFAULT_LNG], DEFAULT_ZOOM);
                });
            }
        });
    </script>
</body>
</html>
<?php 
if($stmt_riwayat) {
    mysqli_stmt_close($stmt_riwayat);
}
mysqli_close($conn); 
?>