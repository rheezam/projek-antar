<?php
ob_start();
session_start();
require_once 'config.php';

// Cek apakah user adalah driver
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'driver') {
    header("Location: login_driver.php");
    exit();
}

$driver_id = $_SESSION['driver_id'];
$driver_name = $_SESSION['driver_name'] ?? 'Driver';

// Ambil data driver
$query_driver = "SELECT * FROM drivers WHERE id = $driver_id";
$result_driver = mysqli_query($conn, $query_driver);
$driver = mysqli_fetch_assoc($result_driver);

// ========== KOORDINAT TETAP SAMARINDA ==========
define('DEFAULT_LAT', -0.45026400793080024);
define('DEFAULT_LNG', 117.15697213359016);

// ========== DATABASE KOORDINAT SEDERHANA ==========
// Ini simulasi - nanti bisa diganti dengan tabel database sungguhan
$koordinat_db = [];

// Fungsi untuk mendapatkan koordinat berdasarkan alamat (KONSISTEN)
function getKoordinatDariAlamat($alamat) {
    global $koordinat_db;
    
    // Kalau sudah pernah di-generate, pakai yang lama
    if(isset($koordinat_db[$alamat])) {
        return $koordinat_db[$alamat];
    }
    
    // Generate koordinat berdasarkan hash alamat (KONSISTEN, tidak berubah)
    $hash = crc32($alamat);
    
    // Setiap alamat dapat offset yang TETAP
    $lat_offset = (($hash % 200) - 100) / 8000;  // Sekitar 0-250 meter
    $lng_offset = ((($hash >> 8) % 200) - 100) / 8000;
    
    $koordinat = [
        'lat' => DEFAULT_LAT + $lat_offset,
        'lng' => DEFAULT_LNG + $lng_offset,
        'nama' => $alamat
    ];
    
    // Simpan ke "database"
    $koordinat_db[$alamat] = $koordinat;
    
    return $koordinat;
}

// ========== LOAD SEMUA KOORDINAT DARI DATABASE ==========
// Ambil semua alamat unik dari orders
$alamat_query = "SELECT DISTINCT posisi_ambil, pengiriman FROM orders";
$alamat_result = mysqli_query($conn, $alamat_query);
while($row = mysqli_fetch_assoc($alamat_result)) {
    if(!empty($row['posisi_ambil'])) {
        getKoordinatDariAlamat($row['posisi_ambil']);
    }
    if(!empty($row['pengiriman'])) {
        getKoordinatDariAlamat($row['pengiriman']);
    }
}

// ========== FILE JSON UNTUK SIMPAN DATA DRIVER ==========
$json_file = "driver_data_{$driver_id}.json";

// Fungsi baca data dari JSON
function bacaDataDriver($file, $driver_id) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        if(is_array($data)) {
            return $data;
        }
    }
    // Default data
    return [
        'driver_id' => $driver_id,
        'taken_orders' => [],
        'completed_orders' => [],
        'total_earnings' => 0
    ];
}

// Fungsi simpan data ke JSON
function simpanDataDriver($file, $data) {
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Baca data driver dari file JSON
$driver_data = bacaDataDriver($json_file, $driver_id);
$taken_orders = $driver_data['taken_orders'] ?? [];
$completed_orders = $driver_data['completed_orders'] ?? [];
$total_earnings = $driver_data['total_earnings'] ?? 0;

// Handle ambil pesanan
if(isset($_GET['take']) && is_numeric($_GET['take'])) {
    $order_id = (int)$_GET['take'];
    
    if(!in_array($order_id, $taken_orders) && !in_array($order_id, $completed_orders)) {
        $taken_orders[] = $order_id;
        $driver_data['taken_orders'] = $taken_orders;
        simpanDataDriver($json_file, $driver_data);
        header("Location: driver_dashboard.php?success=Pesanan berhasil diambil!");
        exit();
    } else {
        header("Location: driver_dashboard.php?error=Pesanan sudah diambil!");
        exit();
    }
}

// Handle selesaikan pesanan
if(isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $order_id = (int)$_GET['complete'];
    
    $key = array_search($order_id, $taken_orders);
    if($key !== false) {
        unset($taken_orders[$key]);
        $completed_orders[] = $order_id;
        
        $query_harga = "SELECT harga FROM orders WHERE id = $order_id";
        $result_harga = mysqli_query($conn, $query_harga);
        if($row = mysqli_fetch_assoc($result_harga)) {
            $total_earnings += $row['harga'];
        }
        
        $driver_data['taken_orders'] = $taken_orders;
        $driver_data['completed_orders'] = $completed_orders;
        $driver_data['total_earnings'] = $total_earnings;
        simpanDataDriver($json_file, $driver_data);
        
        header("Location: driver_dashboard.php?success=Pesanan selesai! +Rp " . number_format($row['harga'] ?? 0, 0, ',', '.'));
        exit();
    }
}

// Handle reset data
if(isset($_GET['reset'])) {
    if(file_exists($json_file)) {
        unlink($json_file);
    }
    header("Location: driver_dashboard.php?success=Data berhasil direset!");
    exit();
}

// Hitung statistik
$processing_orders = count($taken_orders);
$completed_orders_count = count($completed_orders);
$total_orders = $processing_orders + $completed_orders_count;

// Ambil semua pesanan dari tabel orders
$all_orders_query = "SELECT * FROM orders ORDER BY created_at DESC";
$all_orders = mysqli_query($conn, $all_orders_query);
$all_orders_list = [];
while($row = mysqli_fetch_assoc($all_orders)) {
    // Decode foto_barang dari JSON
    if(!empty($row['foto_barang'])) {
        $row['foto_barang_array'] = json_decode($row['foto_barang'], true);
    } else {
        $row['foto_barang_array'] = [];
    }
    
    // Tambahkan koordinat FIXED untuk setiap alamat
    if(!empty($row['posisi_ambil'])) {
        $row['pickup_coords'] = getKoordinatDariAlamat($row['posisi_ambil']);
    }
    if(!empty($row['pengiriman'])) {
        $row['delivery_coords'] = getKoordinatDariAlamat($row['pengiriman']);
    }
    
    $all_orders_list[] = $row;
}

// Filter pesanan yang sedang diantar
$taken_orders_list = [];
foreach($taken_orders as $id) {
    foreach($all_orders_list as $order) {
        if($order['id'] == $id) {
            $taken_orders_list[] = $order;
            break;
        }
    }
}

// Filter pesanan yang sudah selesai
$completed_orders_list = [];
foreach($completed_orders as $id) {
    foreach($all_orders_list as $order) {
        if($order['id'] == $id) {
            $completed_orders_list[] = $order;
            break;
        }
    }
}

// Filter pesanan yang tersedia
$available_orders = [];
foreach($all_orders_list as $order) {
    if($order['status'] == 'pending' && 
       !in_array($order['id'], $taken_orders) && 
       !in_array($order['id'], $completed_orders)) {
        $available_orders[] = $order;
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Driver Dashboard | SmariDelivery</title>
    
    <!-- Favicon -->
    <link rel="icon" href="Foto/logo.png" type="image/png">
    <link rel="shortcut icon" href="Foto/logo.png" type="image/png">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #0A6A2A;
            --primary-dark: #087532;
            --secondary: #1a237e;
            --light: #f8f9fa;
            --dark: #333333;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }
        .driver-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: var(--secondary);
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .logo h2 {
            font-size: 1.5rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .driver-info {
            text-align: center;
            padding: 15px;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            margin: 0 15px 20px;
        }
        .driver-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            color: white;
            border: 3px solid rgba(255,255,255,0.2);
        }
        .driver-info h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        .driver-info p {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            background: var(--success);
            color: white;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-top: 8px;
        }
        .nav-links {
            list-style: none;
            padding: 0 15px;
        }
        .nav-links li {
            margin-bottom: 5px;
        }
        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-size: 0.95rem;
        }
        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .nav-links i {
            width: 20px;
            text-align: center;
        }
        .logout-btn {
            margin-top: 20px;
            padding: 15px;
            text-align: center;
        }
        .logout-btn a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
            transition: var(--transition);
        }
        .logout-btn a:hover {
            background: rgba(255,255,255,0.2);
        }
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 1.8rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .btn-success {
            background: var(--success);
            color: white;
        }
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-warning {
            background: var(--warning);
            color: var(--dark);
        }
        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        .btn-info {
            background: var(--info);
            color: white;
        }
        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }
        .btn-outline:hover {
            background: var(--secondary);
            color: white;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        .stat-icon.orders { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .stat-icon.processing { background: rgba(23, 162, 184, 0.1); color: var(--info); }
        .stat-icon.completed { background: rgba(26, 35, 126, 0.1); color: var(--secondary); }
        .stat-icon.earnings { background: rgba(220, 53, 69, 0.1); color: var(--danger); }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 30px;
            overflow: hidden;
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .table-header h2 {
            font-size: 1.4rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: var(--light-gray);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        /* Style untuk foto barang */
        .photo-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid var(--light-gray);
            transition: var(--transition);
        }
        .photo-thumbnail:hover {
            transform: scale(1.1);
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }
        .photo-count {
            background: var(--secondary);
            color: white;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 0.7rem;
            margin-left: 5px;
        }
        
        /* Orders Grid */
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .order-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            transition: var(--transition);
        }
        .order-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-3px);
            border-color: var(--primary);
        }
        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .order-id {
            font-weight: 700;
            color: var(--secondary);
        }
        .order-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin: 15px 0;
            text-align: center;
        }
        
        /* Gallery Foto */
        .photo-gallery {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .photo-gallery-item {
            position: relative;
            width: 70px;
            height: 70px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid var(--light-gray);
            cursor: pointer;
        }
        .photo-gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        .photo-gallery-item:hover img {
            transform: scale(1.2);
        }
        .photo-gallery-item span {
            position: absolute;
            bottom: 0;
            right: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 6px;
            font-size: 0.7rem;
            border-radius: 5px 0 0 0;
        }
        
        /* Modal untuk peta */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: var(--secondary);
            color: white;
        }
        .modal-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }
        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 28px;
            cursor: pointer;
        }
        .close-modal:hover {
            color: var(--warning);
        }
        .modal-body {
            padding: 20px;
            overflow-y: auto;
        }
        .map-container {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .route-info {
            background: var(--light-gray);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .route-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .route-row i {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 50%;
            color: var(--secondary);
        }
        
        /* Modal lihat foto */
        .photo-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            align-items: center;
            justify-content: center;
        }
        .photo-modal-content {
            max-width: 90%;
            max-height: 90%;
        }
        .photo-modal-content img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 10px;
            border: 5px solid white;
        }
        .close-photo-modal {
            position: absolute;
            top: 20px;
            right: 40px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }
        .close-photo-modal:hover {
            color: var(--danger);
        }
        .photo-modal-caption {
            position: absolute;
            bottom: 20px;
            left: 0;
            width: 100%;
            text-align: center;
            color: white;
            font-size: 1.2rem;
            background: rgba(0,0,0,0.5);
            padding: 10px;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message.success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid var(--success);
            color: #155724;
        }
        .message.error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid var(--danger);
            color: #721c24;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        
        .location-badge {
            background: var(--info);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        @media (max-width: 768px) {
            .driver-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .orders-grid {
                grid-template-columns: 1fr;
            }
            .modal-content {
                width: 95%;
            }
            .map-container {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- MODAL LIHAT FOTO -->
    <div id="photoModal" class="photo-modal" onclick="closePhotoModal()">
        <span class="close-photo-modal" onclick="closePhotoModal()">&times;</span>
        <div class="photo-modal-content" onclick="event.stopPropagation()">
            <img id="modalImage" src="" alt="Foto Barang">
            <div id="modalCaption" class="photo-modal-caption"></div>
        </div>
    </div>

    <!-- MODAL PETA LOKASI -->
    <div id="mapModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-map-marked-alt"></i>
                    <span id="mapModalTitle">Peta Lokasi - Samarinda</span>
                </h3>
                <button class="close-modal" onclick="closeMapModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="mapContainer" class="map-container"></div>
                <div id="routeInfo" class="route-info">
                    <div class="route-row">
                        <i class="fas fa-map-marker-alt" style="color: var(--success);"></i>
                        <strong>Pengambilan:</strong> <span id="pickupAddress"></span>
                    </div>
                    <div class="route-row">
                        <i class="fas fa-flag-checkered" style="color: var(--danger);"></i>
                        <strong>Pengantaran:</strong> <span id="deliveryAddress"></span>
                    </div>
                    <div class="route-row">
                        <i class="fas fa-arrows-alt-h" style="color: var(--secondary);"></i>
                        <strong>Jarak:</strong> <span id="mapDistance"></span>
                    </div>
                    <div class="route-row">
                        <i class="fas fa-city" style="color: var(--info);"></i>
                        <strong>Wilayah:</strong> <span>Samarinda dan Sekitarnya</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="driver-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h2>
                    <i class="fas fa-motorcycle"></i>
                    Driver Panel
                </h2>
            </div>
            
            <div class="driver-info">
                <div class="driver-avatar">
                    <?php echo strtoupper(substr($driver['nama'], 0, 1)); ?>
                </div>
                <h3><?php echo htmlspecialchars($driver['nama']); ?></h3>
                <p><?php echo htmlspecialchars($driver['jenis_kendaraan']); ?></p>
                <span class="status-badge">
                    <i class="fas fa-check-circle"></i> Aktif
                </span>
            </div>
            
            <ul class="nav-links">
                <li><a href="#" class="active" data-tab="dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                <li><a href="#" data-tab="orders">
                    <i class="fas fa-clipboard-list"></i> Pesanan Saya
                </a></li>
                <li><a href="#" data-tab="available">
                    <i class="fas fa-search"></i> Cari Pesanan
                    <?php if(count($available_orders) > 0): ?>
                        <span style="background: var(--warning); color: var(--dark); padding: 2px 8px; border-radius: 10px; margin-left: auto; font-size: 0.7rem;">
                            <?php echo count($available_orders); ?>
                        </span>
                    <?php endif; ?>
                </a></li>
                <li><a href="#" data-tab="history">
                    <i class="fas fa-history"></i> Riwayat
                </a></li>
                <li><a href="#" data-tab="profile">
                    <i class="fas fa-user-cog"></i> Profil
                </a></li>
                <li><a href="#" data-tab="settings">
                    <i class="fas fa-cog"></i> Pengaturan
                </a></li>
            </ul>
            
            <div class="logout-btn">
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <div class="header">
                <h1>
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard Driver
                </h1>
                <div class="header-actions">
                    <span class="location-badge">
                        <i class="fas fa-map-pin"></i> Samarinda
                    </span>
                    <span style="padding: 8px 15px; background: var(--light-gray); border-radius: 6px;">
                        <i class="fas fa-calendar"></i> <?php echo date('d F Y'); ?>
                    </span>
                </div>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> 
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon orders">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-value"><?php echo $total_orders; ?></div>
                        <div class="stat-label">Total Pesanan</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon processing">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="stat-value"><?php echo $processing_orders; ?></div>
                        <div class="stat-label">Sedang Diantar</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon completed">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo $completed_orders_count; ?></div>
                        <div class="stat-label">Selesai</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon earnings">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value">Rp <?php echo number_format($total_earnings, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                </div>

                <!-- Pesanan Sedang Diantar -->
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-truck"></i> Pesanan Sedang Diantar</h2>
                        <span style="color: var(--info); font-size: 0.9rem;">
                            <i class="fas fa-map-pin"></i> Koordinat tetap per alamat
                        </span>
                    </div>
                    
                    <?php if(!empty($taken_orders_list)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Order</th>
                                    <th>Pemesan</th>
                                    <th>Barang</th>
                                    <th>Foto</th>
                                    <th>Lokasi</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($taken_orders_list as $order): ?>
                                    <tr>
                                        <td>#ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($order['jenis_barang']); ?> (<?php echo $order['jumlah_barang']; ?>)</td>
                                        <td>
                                            <?php if(!empty($order['foto_barang_array'])): ?>
                                                <div style="display: flex; gap: 5px;">
                                                    <?php foreach(array_slice($order['foto_barang_array'], 0, 2) as $index => $foto): ?>
                                                        <img src="<?php echo htmlspecialchars($foto); ?>" 
                                                             alt="Foto Barang" 
                                                             class="photo-thumbnail"
                                                             onclick="openPhotoModal('<?php echo htmlspecialchars($foto); ?>', 'Foto Barang - Order #ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>')">
                                                    <?php endforeach; ?>
                                                    <?php if(count($order['foto_barang_array']) > 2): ?>
                                                        <span class="photo-count">+<?php echo count($order['foto_barang_array']) - 2; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--gray); font-size: 0.8rem;">Tidak ada foto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-info btn-sm" onclick="showMap(
                                                '<?php echo htmlspecialchars($order['posisi_ambil']); ?>',
                                                '<?php echo htmlspecialchars($order['pengiriman']); ?>',
                                                <?php echo $order['pickup_coords']['lat']; ?>,
                                                <?php echo $order['pickup_coords']['lng']; ?>,
                                                <?php echo $order['delivery_coords']['lat']; ?>,
                                                <?php echo $order['delivery_coords']['lng']; ?>,
                                                <?php echo $order['jarak_meter']; ?>
                                            )">
                                                <i class="fas fa-map-marked-alt"></i> Lihat Peta
                                            </button>
                                        </td>
                                        <td>Rp <?php echo number_format($order['harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <a href="?complete=<?php echo $order['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Selesaikan pesanan ini?')">
                                                <i class="fas fa-check-circle"></i> Selesai
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: var(--gray);">
                            <i class="fas fa-box-open" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                            Tidak ada pesanan yang sedang diantar
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Orders Tab - Pesanan Saya -->
            <div id="orders" class="tab-content">
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-clipboard-list"></i> Semua Pesanan Saya</h2>
                    </div>
                    
                    <?php if(!empty($taken_orders_list) || !empty($completed_orders_list)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Order</th>
                                    <th>Pemesan</th>
                                    <th>Barang</th>
                                    <th>Foto</th>
                                    <th>Lokasi</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($taken_orders_list as $order): ?>
                                    <tr>
                                        <td>#ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($order['jenis_barang']); ?></td>
                                        <td>
                                            <?php if(!empty($order['foto_barang_array'])): ?>
                                                <div style="display: flex; gap: 5px;">
                                                    <?php foreach(array_slice($order['foto_barang_array'], 0, 2) as $foto): ?>
                                                        <img src="<?php echo htmlspecialchars($foto); ?>" 
                                                             class="photo-thumbnail"
                                                             onclick="openPhotoModal('<?php echo htmlspecialchars($foto); ?>', 'Foto Barang - Order #ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>')">
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--gray);">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-info btn-sm" onclick="showMap(
                                                '<?php echo htmlspecialchars($order['posisi_ambil']); ?>',
                                                '<?php echo htmlspecialchars($order['pengiriman']); ?>',
                                                <?php echo $order['pickup_coords']['lat']; ?>,
                                                <?php echo $order['pickup_coords']['lng']; ?>,
                                                <?php echo $order['delivery_coords']['lat']; ?>,
                                                <?php echo $order['delivery_coords']['lng']; ?>,
                                                <?php echo $order['jarak_meter']; ?>
                                            )">
                                                <i class="fas fa-map-marked-alt"></i> Peta
                                            </button>
                                        </td>
                                        <td>Rp <?php echo number_format($order['harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="order-status status-processing">
                                                Sedang Diantar
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?complete=<?php echo $order['id']; ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-check-circle"></i> Selesai
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php foreach($completed_orders_list as $order): ?>
                                    <tr>
                                        <td>#ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($order['jenis_barang']); ?></td>
                                        <td>
                                            <?php if(!empty($order['foto_barang_array'])): ?>
                                                <div style="display: flex; gap: 5px;">
                                                    <?php foreach(array_slice($order['foto_barang_array'], 0, 2) as $foto): ?>
                                                        <img src="<?php echo htmlspecialchars($foto); ?>" 
                                                             class="photo-thumbnail"
                                                             onclick="openPhotoModal('<?php echo htmlspecialchars($foto); ?>', 'Foto Barang - Order #ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>')">
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--gray);">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline btn-sm" onclick="showMap(
                                                '<?php echo htmlspecialchars($order['posisi_ambil']); ?>',
                                                '<?php echo htmlspecialchars($order['pengiriman']); ?>',
                                                <?php echo $order['pickup_coords']['lat']; ?>,
                                                <?php echo $order['pickup_coords']['lng']; ?>,
                                                <?php echo $order['delivery_coords']['lat']; ?>,
                                                <?php echo $order['delivery_coords']['lng']; ?>,
                                                <?php echo $order['jarak_meter']; ?>
                                            )">
                                                <i class="fas fa-map-marked-alt"></i> Peta
                                            </button>
                                        </td>
                                        <td>Rp <?php echo number_format($order['harga'], 0, ',', '.'); ?></td>
                                        <td>
                                            <span class="order-status status-completed">
                                                Selesai
                                            </span>
                                        </td>
                                        <td>-</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: var(--gray);">
                            <i class="fas fa-box-open" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                            Belum ada pesanan
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Available Orders Tab -->
            <div id="available" class="tab-content">
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-search"></i> Pesanan Tersedia</h2>
                        <span style="background: var(--info); color: white; padding: 5px 15px; border-radius: 20px;">
                            <?php echo count($available_orders); ?> pesanan tersedia
                        </span>
                    </div>
                    
                    <?php if(!empty($available_orders)): ?>
                        <div class="orders-grid">
                            <?php foreach($available_orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-card-header">
                                        <span class="order-id">#ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                                        <span style="background: var(--light-gray); padding: 3px 10px; border-radius: 15px; font-size: 0.8rem;">
                                            <i class="fas fa-arrows-alt-h"></i> <?php echo $order['jarak_meter']; ?> m
                                        </span>
                                    </div>
                                    <div style="margin-bottom: 15px;">
                                        <div style="margin-bottom: 5px;">
                                            <strong><i class="fas fa-user"></i> Pemesan:</strong> 
                                            <?php echo htmlspecialchars($order['nama']); ?>
                                        </div>
                                        <div style="margin-bottom: 5px;">
                                            <strong><i class="fas fa-box"></i> Barang:</strong> 
                                            <?php echo htmlspecialchars($order['jenis_barang']); ?> (<?php echo $order['jumlah_barang']; ?> pcs)
                                        </div>
                                        
                                        <!-- LOKASI PENGAMBILAN & PENGANTARAN -->
                                        <div style="margin-top: 10px; padding: 10px; background: var(--light-gray); border-radius: 8px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <i class="fas fa-map-marker-alt" style="color: var(--success);"></i>
                                                    <span><strong>Ambil:</strong> <?php echo htmlspecialchars($order['posisi_ambil']); ?></span>
                                                </div>
                                                <button class="btn-map" style="background: var(--secondary); color: white; padding: 4px 10px; border-radius: 15px; font-size: 0.75rem; border: none; cursor: pointer;" onclick="showMap(
                                                    '<?php echo htmlspecialchars($order['posisi_ambil']); ?>',
                                                    '<?php echo htmlspecialchars($order['pengiriman']); ?>',
                                                    <?php echo $order['pickup_coords']['lat']; ?>,
                                                    <?php echo $order['pickup_coords']['lng']; ?>,
                                                    <?php echo $order['delivery_coords']['lat']; ?>,
                                                    <?php echo $order['delivery_coords']['lng']; ?>,
                                                    <?php echo $order['jarak_meter']; ?>
                                                )">
                                                    <i class="fas fa-map"></i> Lihat Peta
                                                </button>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="fas fa-flag-checkered" style="color: var(--danger);"></i>
                                                <span><strong>Antar:</strong> <?php echo htmlspecialchars($order['pengiriman']); ?></span>
                                            </div>
                                        </div>
                                        
                                        <!-- FOTO BARANG -->
                                        <?php if(!empty($order['foto_barang_array'])): ?>
                                            <div style="margin-top: 10px;">
                                                <strong><i class="fas fa-camera"></i> Foto Barang:</strong>
                                                <div class="photo-gallery">
                                                    <?php foreach($order['foto_barang_array'] as $index => $foto): ?>
                                                        <div class="photo-gallery-item" onclick="openPhotoModal('<?php echo htmlspecialchars($foto); ?>', 'Foto Barang - Order #ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>')">
                                                            <img src="<?php echo htmlspecialchars($foto); ?>" alt="Foto Barang">
                                                            <span><?php echo $index + 1; ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($order['catatan'])): ?>
                                            <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border-left: 4px solid var(--warning); border-radius: 5px;">
                                                <strong><i class="fas fa-sticky-note"></i> Catatan:</strong> 
                                                <?php echo htmlspecialchars($order['catatan']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="order-price">
                                        Rp <?php echo number_format($order['harga'], 0, ',', '.'); ?>
                                    </div>
                                    <a href="?take=<?php echo $order['id']; ?>" class="btn btn-primary" style="width: 100%; justify-content: center;" onclick="return confirm('Ambil pesanan ini?')">
                                        <i class="fas fa-hand-holding-heart"></i> Ambil Pesanan
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: var(--gray);">
                            <i class="fas fa-box-open" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                            Tidak ada pesanan tersedia
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- History Tab -->
            <div id="history" class="tab-content">
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-history"></i> Riwayat Pesanan</h2>
                        <span style="background: var(--secondary); color: white; padding: 5px 15px; border-radius: 20px;">
                            Total Pendapatan: Rp <?php echo number_format($total_earnings, 0, ',', '.'); ?>
                        </span>
                    </div>
                    
                    <?php if(!empty($completed_orders_list)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID Order</th>
                                    <th>Pemesan</th>
                                    <th>Barang</th>
                                    <th>Foto</th>
                                    <th>Lokasi</th>
                                    <th>Total</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($completed_orders_list as $order): ?>
                                    <tr>
                                        <td>#ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($order['jenis_barang']); ?></td>
                                        <td>
                                            <?php if(!empty($order['foto_barang_array'])): ?>
                                                <div style="display: flex; gap: 5px;">
                                                    <?php foreach(array_slice($order['foto_barang_array'], 0, 2) as $foto): ?>
                                                        <img src="<?php echo htmlspecialchars($foto); ?>" 
                                                             class="photo-thumbnail"
                                                             onclick="openPhotoModal('<?php echo htmlspecialchars($foto); ?>', 'Foto Barang - Order #ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>')">
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: var(--gray);">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-outline btn-sm" onclick="showMap(
                                                '<?php echo htmlspecialchars($order['posisi_ambil']); ?>',
                                                '<?php echo htmlspecialchars($order['pengiriman']); ?>',
                                                <?php echo $order['pickup_coords']['lat']; ?>,
                                                <?php echo $order['pickup_coords']['lng']; ?>,
                                                <?php echo $order['delivery_coords']['lat']; ?>,
                                                <?php echo $order['delivery_coords']['lng']; ?>,
                                                <?php echo $order['jarak_meter']; ?>
                                            )">
                                                <i class="fas fa-map-marked-alt"></i> Peta
                                            </button>
                                        </td>
                                        <td>Rp <?php echo number_format($order['harga'], 0, ',', '.'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: var(--gray);">
                            <i class="fas fa-history" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                            Belum ada riwayat pesanan
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Profile Tab -->
            <div id="profile" class="tab-content">
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-user-cog"></i> Profil Driver</h2>
                    </div>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 30px;">
                        <div style="flex: 1; min-width: 300px;">
                            <table style="border: none; width: 100%;">
                                <tr>
                                    <td style="font-weight: 600; width: 150px; padding: 10px 0;">Nama Lengkap</td>
                                    <td style="padding: 10px 0;">: <?php echo htmlspecialchars($driver['nama']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 0;">Username</td>
                                    <td style="padding: 10px 0;">: <?php echo htmlspecialchars($driver['username']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 0;">Email</td>
                                    <td style="padding: 10px 0;">: <?php echo htmlspecialchars($driver['email']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 0;">No. HP</td>
                                    <td style="padding: 10px 0;">: <?php echo htmlspecialchars($driver['no_hp']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 0;">Sekolah</td>
                                    <td style="padding: 10px 0;">: <?php echo htmlspecialchars($driver['sekolah']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 0;">Kelas</td>
                                    <td style="padding: 10px 0;">: <?php echo htmlspecialchars($driver['kelas']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 0;">Jenis Kendaraan</td>
                                    <td style="padding: 10px 0;">: <?php echo htmlspecialchars($driver['jenis_kendaraan']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 0;">Alamat</td>
                                    <td style="padding: 10px 0;">: <?php echo htmlspecialchars($driver['alamat']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 0;">No. Kartu Pelajar</td>
                                    <td style="padding: 10px 0;">: <?php echo htmlspecialchars($driver['no_kartu_pelajar']); ?></td>
                                </tr>
                                <tr>
                                    <td style="font-weight: 600; padding: 10px 0;">Tanggal Daftar</td>
                                    <td style="padding: 10px 0;">: <?php echo date('d/m/Y', strtotime($driver['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div style="flex: 0 0 200px; text-align: center;">
                            <div style="background: var(--light-gray); padding: 20px; border-radius: 10px;">
                                <h3 style="margin-bottom: 15px;">Foto Diri</h3>
                                <?php if($driver['foto_diri']): ?>
                                    <img src="uploads/drivers/diri/<?php echo htmlspecialchars($driver['foto_diri']); ?>" 
                                         alt="Foto Diri" 
                                         style="max-width: 100%; max-height: 150px; border-radius: 10px; cursor: pointer; border: 3px solid white; box-shadow: var(--shadow);"
                                         onclick="openPhotoModal(this.src, 'Foto Diri - <?php echo htmlspecialchars($driver['nama']); ?>')">
                                <?php else: ?>
                                    <i class="fas fa-user-circle" style="font-size: 80px; color: var(--gray);"></i>
                                <?php endif; ?>
                                
                                <h3 style="margin: 20px 0 15px;">Kartu Pelajar</h3>
                                <?php if($driver['foto_kartu_pelajar']): ?>
                                    <img src="uploads/drivers/kartu_pelajar/<?php echo htmlspecialchars($driver['foto_kartu_pelajar']); ?>" 
                                         alt="Kartu Pelajar" 
                                         style="max-width: 100%; max-height: 150px; border-radius: 10px; cursor: pointer; border: 3px solid white; box-shadow: var(--shadow);"
                                         onclick="openPhotoModal(this.src, 'Kartu Pelajar - <?php echo htmlspecialchars($driver['nama']); ?>')">
                                <?php else: ?>
                                    <i class="fas fa-id-card" style="font-size: 80px; color: var(--gray);"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="settings" class="tab-content">
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-cog"></i> Pengaturan</h2>
                    </div>
                    
                    <div style="text-align: center; padding: 30px;">
                        <p style="margin-bottom: 20px; color: var(--gray);">
                            <i class="fas fa-info-circle" style="font-size: 3rem; display: block; margin-bottom: 15px;"></i>
                            Data pesanan Anda disimpan secara permanen.<br>
                            <strong>Total Pesanan:</strong> <?php echo $total_orders; ?> | 
                            <strong>Total Pendapatan:</strong> Rp <?php echo number_format($total_earnings, 0, ',', '.'); ?>
                        </p>
                        
                        <a href="?reset=1" class="btn btn-danger" onclick="return confirm('RESET SEMUA DATA? Semua pesanan dan pendapatan akan hilang!')">
                            <i class="fas fa-trash"></i> Reset Data Driver
                        </a>
                        
                        <p style="margin-top: 30px; font-size: 0.9rem; color: var(--gray);">
                            <i class="fas fa-map-pin"></i> Wilayah Operasi: Samarinda (-0.450264, 117.156972)<br>
                            <small>Koordinat setiap alamat bersifat tetap dan tidak berubah</small>
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Variabel global untuk menyimpan instance peta
        let mapInstance = null;
        
        // ========== FUNGSI UNTUK MODAL FOTO ==========
        function openPhotoModal(imageSrc, caption) {
            const modal = document.getElementById('photoModal');
            const modalImg = document.getElementById('modalImage');
            const modalCaption = document.getElementById('modalCaption');
            
            modal.style.display = 'flex';
            modalImg.src = imageSrc;
            modalCaption.innerHTML = caption || 'Foto Barang';
            document.body.style.overflow = 'hidden';
        }
        
        function closePhotoModal() {
            const modal = document.getElementById('photoModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // ========== FUNGSI UNTUK MODAL PETA ==========
        function showMap(pickupAddress, deliveryAddress, pickupLat, pickupLng, deliveryLat, deliveryLng, distance) {
            // Set judul dan alamat
            document.getElementById('mapModalTitle').innerHTML = 'Peta Lokasi - Samarinda';
            document.getElementById('pickupAddress').innerHTML = pickupAddress;
            document.getElementById('deliveryAddress').innerHTML = deliveryAddress;
            document.getElementById('mapDistance').innerHTML = distance + ' meter';
            
            // Tampilkan modal
            document.getElementById('mapModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Inisialisasi peta setelah modal tampil
            setTimeout(function() {
                initMap(pickupLat, pickupLng, deliveryLat, deliveryLng, pickupAddress, deliveryAddress);
            }, 200);
        }
        
        function closeMapModal() {
            document.getElementById('mapModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            
            if (mapInstance) {
                mapInstance.remove();
                mapInstance = null;
            }
        }
        
function initMap(pickupLat, pickupLng, deliveryLat, deliveryLng, pickupAddress, deliveryAddress) {
    if (mapInstance) {
        mapInstance.remove();
    }
    
    // HITUNG JARAK (meter)
    const R = 6371e3; // Radius bumi dalam meter
    const φ1 = pickupLat * Math.PI/180;
    const φ2 = deliveryLat * Math.PI/180;
    const Δφ = (deliveryLat - pickupLat) * Math.PI/180;
    const Δλ = (deliveryLng - pickupLng) * Math.PI/180;
    
    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const jarak = R * c; // dalam meter
    
    // TENTUKAN ZOOM LEVEL BERDASARKAN JARAK
    let zoomLevel = 15;
    if (jarak < 150) zoomLevel = 17;      // < 150 meter
    else if (jarak < 300) zoomLevel = 16; // 150-300 meter
    else if (jarak < 600) zoomLevel = 15; // 300-600 meter
    else zoomLevel = 14;                  // > 600 meter
    
    // Titik tengah
    const centerLat = (pickupLat + deliveryLat) / 2;
    const centerLng = (pickupLng + deliveryLng) / 2;
    
    // BUAT PETA DENGAN ZOOM MANUAL
    mapInstance = L.map('mapContainer').setView([centerLat, centerLng], zoomLevel);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapInstance);
    
    // MARKER SEDERHANA (tanpa icon custom biar ringan)
    L.marker([pickupLat, pickupLng]).addTo(mapInstance)
        .bindPopup('<b>📍 Pengambilan</b><br>' + pickupAddress);
    
    L.marker([deliveryLat, deliveryLng]).addTo(mapInstance)
        .bindPopup('<b>🏁 Pengantaran</b><br>' + deliveryAddress);
    
    // GARIS RUTE
    L.polyline([
        [pickupLat, pickupLng],
        [deliveryLat, deliveryLng]
    ], {
        color: '#1a237e',
        weight: 3,
        dashArray: '6, 8',
        opacity: 0.7
    }).addTo(mapInstance);
    
    // ✅ TIDAK PAKAI fitBounds() - PETA TIDAK PANJANG!
}
        
        // Close modal dengan tombol ESC
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                closePhotoModal();
                closeMapModal();
            }
        });
        
        window.onclick = function(event) {
            const mapModal = document.getElementById('mapModal');
            const photoModal = document.getElementById('photoModal');
            
            if (event.target === mapModal) {
                closeMapModal();
            }
            if (event.target === photoModal) {
                closePhotoModal();
            }
        };
        
        // Tab Navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-links a');
            const tabContents = document.querySelectorAll('.tab-content');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabId = this.getAttribute('data-tab');
                    
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    tabContents.forEach(tab => {
                        tab.classList.remove('active');
                        if(tab.id === tabId) {
                            tab.classList.add('active');
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>