<?php
session_start();
require_once 'config.php';

// CEK AKSES - Bisa diakses oleh user, driver, atau admin
$is_authorized = false;
$user_nama = null;
$driver_id = null;
$is_admin = false;

// Cek apakah user login sebagai customer
if(isset($_SESSION['nama'])) {
    $is_authorized = true;
    $user_nama = $_SESSION['nama'];
    $user_role = 'customer';
}
// Cek apakah user login sebagai driver
else if(isset($_SESSION['role']) && $_SESSION['role'] == 'driver' && isset($_SESSION['driver_id'])) {
    $is_authorized = true;
    $driver_id = $_SESSION['driver_id'];
    $user_role = 'driver';
}
// Cek apakah user login sebagai admin
else if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    $is_authorized = true;
    $is_admin = true;
    $user_role = 'admin';
}
else {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($order_id == 0) {
    header("Location: " . ($user_role == 'driver' ? 'driver_dashboard.php' : 'riwayat_pesanan.php'));
    exit();
}

// Query untuk mengambil detail pesanan dengan informasi driver
$query = "SELECT o.*, 
          d.id as driver_id, 
          d.nama as driver_nama, 
          d.no_hp as driver_telepon, 
          d.jenis_kendaraan,
          d.plat_kendaraan, 
          d.foto_diri as driver_foto,
          d.sekolah as driver_sekolah,
          u.nama as user_nama,
          u.no_telepon as user_telepon,
          u.kelas as user_kelas
          FROM orders o 
          LEFT JOIN drivers d ON o.driver_id = d.id 
          LEFT JOIN users u ON o.nama = u.nama 
          WHERE o.id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    // Order tidak ditemukan
    if($user_role == 'driver') {
        header("Location: driver_dashboard.php");
    } else if($user_role == 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: riwayat_pesanan.php");
    }
    exit();
}

$order = mysqli_fetch_assoc($result);

// VALIDASI AKSES - Pastikan user hanya bisa lihat order miliknya
if($user_role == 'customer') {
    // Customer hanya bisa lihat order miliknya sendiri
    if($order['nama'] != $user_nama) {
        header("Location: riwayat_pesanan.php");
        exit();
    }
} else if($user_role == 'driver') {
    // Driver hanya bisa lihat order yang diassign kepadanya
    if($order['driver_id'] != $driver_id) {
        header("Location: driver_dashboard.php");
        exit();
    }
}
// Admin bisa lihat semua order

// Query untuk mengambil history status pesanan
$history_query = "SELECT * FROM order_status_history 
                  WHERE order_id = ? 
                  ORDER BY created_at ASC";
$stmt_history = mysqli_prepare($conn, $history_query);
mysqli_stmt_bind_param($stmt_history, "i", $order_id);
mysqli_stmt_execute($stmt_history);
$history_result = mysqli_stmt_get_result($stmt_history);

$status_history = [];
while($history = mysqli_fetch_assoc($history_result)) {
    $status_history[] = $history;
}

// Fungsi untuk mendapatkan status dalam Bahasa Indonesia
function getStatusIndo($status) {
    $statusMap = [
        'pending' => 'Menunggu Konfirmasi',
        'processing' => 'Diproses',
        'picking_up' => 'Menuju Lokasi Pengambilan',
        'picked_up' => 'Barang Telah Diambil',
        'delivering' => 'Sedang Dikirim',
        'completed' => 'Pesanan Selesai',
        'cancelled' => 'Pesanan Dibatalkan'
    ];
    return $statusMap[$status] ?? $status;
}

// Fungsi untuk mendapatkan icon status
function getStatusIcon($status) {
    $icons = [
        'pending' => 'fa-clock',
        'processing' => 'fa-cog',
        'picking_up' => 'fa-motorcycle',
        'picked_up' => 'fa-box',
        'delivering' => 'fa-truck',
        'completed' => 'fa-check-circle',
        'cancelled' => 'fa-times-circle'
    ];
    return $icons[$status] ?? 'fa-circle';
}

// Fungsi untuk mendapatkan label berat
function getBeratLabel($berat) {
    $beratMap = [
        'kurang dari 2 kg' => '< 2 kg',
        '2 sampai 4 kg' => '2-4 kg',
        'lebih dari 4 kg' => '> 4 kg',
        'ringan' => '< 2 kg',
        'sedang' => '2-4 kg',
        'berat' => '> 4 kg'
    ];
    return $beratMap[$berat] ?? $berat;
}

// Format tanggal
$created_at = date('d M Y H:i', strtotime($order['created_at']));
$updated_at = isset($order['updated_at']) ? date('d M Y H:i', strtotime($order['updated_at'])) : '-';

// Status steps untuk progress bar
$status_steps = [
    'pending' => 1,
    'processing' => 2,
    'picking_up' => 3,
    'picked_up' => 4,
    'delivering' => 5,
    'completed' => 6
];

$current_step = $status_steps[$order['status']] ?? 1;
$is_cancelled = ($order['status'] == 'cancelled');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="Foto/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?> - SmariDelivery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    
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
            --info: #17a2b8;
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
            z-index: 1000;
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

        .cta-button-outline {
            background-color: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 8px 18px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .cta-button-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        /* Main Content */
        .main-content {
            margin-top: 100px;
            padding-bottom: 50px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title h1 {
            font-size: 2rem;
            color: var(--dark);
        }

        .order-id {
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .back-button:hover {
            color: var(--primary);
            transform: translateX(-3px);
        }

        /* Tracking Card */
        .tracking-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .tracking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .tracking-status {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-badge-large {
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-picking_up, .status-delivering {
            background-color: #d4edda;
            color: #155724;
        }

        .status-picked_up {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .status-completed {
            background-color: #c3e6cb;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .estimated-time {
            background: var(--light-gray);
            padding: 10px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Progress Bar */
        .progress-container {
            margin: 40px 0;
            position: relative;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            background: white;
            border: 3px solid var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: var(--gray);
            font-size: 20px;
            transition: var(--transition);
        }

        .step-icon.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
        }

        .step-icon.completed {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }

        .step-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .step-time {
            font-size: 0.8rem;
            color: var(--gray);
        }

        .progress-line {
            position: absolute;
            top: 25px;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--light-gray);
            z-index: 1;
        }

        .progress-line-fill {
            height: 3px;
            background: var(--success);
            width: 0;
            transition: width 0.5s ease;
        }

        /* Order Details */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .detail-section {
            background: var(--light);
            padding: 20px;
            border-radius: 10px;
        }

        .detail-section h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .detail-list {
            list-style: none;
        }

        .detail-list li {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .detail-list li:last-child {
            border-bottom: none;
        }

        .detail-label {
            width: 120px;
            color: var(--gray);
            font-weight: 500;
        }

        .detail-value {
            flex: 1;
            color: var(--dark);
            font-weight: 500;
        }

        /* Driver Info */
        .driver-card {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            border: 1px solid var(--light-gray);
        }

        .driver-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .driver-avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: var(--gray);
        }

        .driver-info {
            flex: 1;
        }

        .driver-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .driver-vehicle {
            color: var(--gray);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .driver-contact {
            display: flex;
            gap: 15px;
        }

        .contact-button {
            padding: 8px 15px;
            border: 1px solid var(--primary);
            border-radius: 20px;
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .contact-button:hover {
            background: var(--primary);
            color: white;
        }

        /* Location Info */
        .location-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid var(--light-gray);
        }

        .location-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--light-gray);
        }

        .location-item:last-child {
            border-bottom: none;
        }

        .location-icon {
            width: 40px;
            height: 40px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .location-detail {
            flex: 1;
        }

        .location-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .location-address {
            color: var(--gray);
            font-size: 0.95rem;
        }

        .distance-info {
            margin-top: 15px;
            padding: 15px;
            background: rgba(10, 106, 42, 0.1);
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Status History */
        .history-timeline {
            margin-top: 20px;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-left: 2px solid var(--light-gray);
            margin-left: 15px;
            padding-left: 30px;
            position: relative;
        }

        .timeline-item:before {
            content: '';
            width: 12px;
            height: 12px;
            background: var(--primary);
            border-radius: 50%;
            position: absolute;
            left: -7px;
            top: 20px;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .timeline-time {
            font-size: 0.85rem;
            color: var(--gray);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                margin-top: 80px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .progress-steps {
                flex-direction: column;
                gap: 20px;
            }
            
            .progress-step {
                display: flex;
                align-items: center;
                gap: 15px;
                text-align: left;
            }
            
            .step-icon {
                margin: 0;
            }
            
            .progress-line {
                display: none;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .driver-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .driver-contact {
                justify-content: center;
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
                    <?php if($user_role == 'customer'): ?>
                        <a href="index.php">Beranda</a>
                        <a href="buat_pesanan.php">Buat Pesanan</a>
                        <a href="riwayat_pesanan.php">Riwayat Pesanan</a>
                    <?php elseif($user_role == 'driver'): ?>
                        <a href="driver_dashboard.php">Dashboard</a>
                        <a href="driver_orders.php">Pesanan</a>
                    <?php elseif($user_role == 'admin'): ?>
                        <a href="admin_dashboard.php">Dashboard</a>
                        <a href="admin_orders.php">Semua Pesanan</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title">
                    <?php if($user_role == 'customer'): ?>
                        <a href="riwayat_pesanan.php" class="back-button">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    <?php elseif($user_role == 'driver'): ?>
                        <a href="driver_orders.php" class="back-button">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    <?php elseif($user_role == 'admin'): ?>
                        <a href="admin_orders.php" class="back-button">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    <?php endif; ?>
                    <h1>Detail Pesanan</h1>
                </div>
                <div class="order-id">
                    #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>
                </div>
            </div>

            <!-- Tracking Card -->
            <div class="tracking-card">
                <div class="tracking-header">
                    <div class="tracking-status">
                        <?php 
                        $status_class = 'status-' . $order['status'];
                        $status_icon = getStatusIcon($order['status']);
                        $status_text = getStatusIndo($order['status']);
                        ?>
                        <div class="status-badge-large <?php echo $status_class; ?>">
                            <i class="fas <?php echo $status_icon; ?>"></i>
                            <?php echo $status_text; ?>
                        </div>
                        
                        <?php if($order['status'] == 'completed'): ?>
                            <div class="estimated-time">
                                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                                Pesanan selesai pada <?php echo date('d M Y H:i', strtotime($order['updated_at'])); ?>
                            </div>
                        <?php elseif($order['status'] == 'delivering'): ?>
                            <div class="estimated-time">
                                <i class="fas fa-clock" style="color: var(--warning);"></i>
                                Estimasi sampai: 15-30 menit
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progress Bar -->
                <?php if(!$is_cancelled): ?>
                <div class="progress-container">
                    <div class="progress-line">
                        <div class="progress-line-fill" style="width: <?php echo ($current_step - 1) * 20; ?>%;"></div>
                    </div>
                    <div class="progress-steps">
                        <div class="progress-step">
                            <div class="step-icon <?php echo $current_step >= 1 ? 'completed' : ''; ?>">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="step-label">Menunggu</div>
                            <div class="step-time">
                                <?php echo date('H:i', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div class="progress-step">
                            <div class="step-icon <?php echo $current_step >= 2 ? ($current_step > 2 ? 'completed' : 'active') : ''; ?>">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="step-label">Diproses</div>
                            <div class="step-time">
                                <?php 
                                foreach($status_history as $history) {
                                    if($history['status'] == 'processing') {
                                        echo date('H:i', strtotime($history['created_at']));
                                        break;
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="progress-step">
                            <div class="step-icon <?php echo $current_step >= 3 ? ($current_step > 3 ? 'completed' : 'active') : ''; ?>">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <div class="step-label">Menuju Lokasi</div>
                            <div class="step-time">
                                <?php 
                                foreach($status_history as $history) {
                                    if($history['status'] == 'picking_up') {
                                        echo date('H:i', strtotime($history['created_at']));
                                        break;
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="progress-step">
                            <div class="step-icon <?php echo $current_step >= 4 ? ($current_step > 4 ? 'completed' : 'active') : ''; ?>">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="step-label">Barang Diambil</div>
                            <div class="step-time">
                                <?php 
                                foreach($status_history as $history) {
                                    if($history['status'] == 'picked_up') {
                                        echo date('H:i', strtotime($history['created_at']));
                                        break;
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="progress-step">
                            <div class="step-icon <?php echo $current_step >= 5 ? ($current_step > 5 ? 'completed' : 'active') : ''; ?>">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div class="step-label">Sedang Dikirim</div>
                            <div class="step-time">
                                <?php 
                                foreach($status_history as $history) {
                                    if($history['status'] == 'delivering') {
                                        echo date('H:i', strtotime($history['created_at']));
                                        break;
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        <div class="progress-step">
                            <div class="step-icon <?php echo $current_step >= 6 ? 'completed' : ''; ?>">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="step-label">Selesai</div>
                            <div class="step-time">
                                <?php 
                                foreach($status_history as $history) {
                                    if($history['status'] == 'completed') {
                                        echo date('H:i', strtotime($history['created_at']));
                                        break;
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f8d7da; border-radius: 10px; color: #721c24;">
                    <i class="fas fa-times-circle" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <h3>Pesanan Dibatalkan</h3>
                    <p>Pesanan ini telah dibatalkan pada <?php echo date('d M Y H:i', strtotime($order['updated_at'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Driver Information (Jika sudah diassign) -->
            <?php if(!empty($order['driver_id']) && !$is_cancelled): ?>
            <div class="tracking-card">
                <h3 style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <i class="fas fa-motorcycle" style="color: var(--primary);"></i>
                    Informasi Driver
                </h3>
                <div class="driver-card">
                    <?php if(!empty($order['driver_foto'])): ?>
                        <img src="uploads/drivers/diri/<?php echo htmlspecialchars($order['driver_foto']); ?>" 
                             alt="Driver" class="driver-avatar">
                    <?php else: ?>
                        <div class="driver-avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                    <div class="driver-info">
                        <div class="driver-name">
                            <?php echo htmlspecialchars($order['driver_nama'] ?? 'Driver'); ?>
                        </div>
                        <div class="driver-vehicle">
                            <i class="fas fa-motorcycle"></i>
                            <?php echo htmlspecialchars($order['jenis_kendaraan'] ?? 'Motor'); ?> 
                            (<?php echo htmlspecialchars($order['plat_kendaraan'] ?? '-'); ?>)
                        </div>
                        <div class="driver-vehicle">
                            <i class="fas fa-school"></i>
                            Sekolah: <?php echo htmlspecialchars($order['driver_sekolah'] ?? '-'); ?>
                        </div>
                        <?php if($user_role == 'customer' || $user_role == 'admin'): ?>
                        <div class="driver-contact">
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $order['driver_telepon']); ?>" 
                               class="contact-button" target="_blank">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <a href="tel:<?php echo htmlspecialchars($order['driver_telepon']); ?>" class="contact-button">
                                <i class="fas fa-phone"></i> Telepon
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Order Details Grid -->
            <div class="details-grid">
                <!-- Detail Pesanan -->
                <div class="detail-section">
                    <h3>
                        <i class="fas fa-box"></i>
                        Detail Pesanan
                    </h3>
                    <ul class="detail-list">
                        <li>
                            <span class="detail-label">Jenis Barang</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['jenis_barang'] ?? '-'); ?></span>
                        </li>
                        <li>
                            <span class="detail-label">Jumlah</span>
                            <span class="detail-value"><?php echo ($order['jumlah_barang'] ?? '0') . ' pcs'; ?></span>
                        </li>
                        <li>
                            <span class="detail-label">Berat</span>
                            <span class="detail-value"><?php echo getBeratLabel($order['berat_barang'] ?? ''); ?></span>
                        </li>
                        <li>
                            <span class="detail-label">Jarak</span>
                            <span class="detail-value">
                                <?php 
                                if(!empty($order['jarak_meter']) && $order['jarak_meter'] > 0) {
                                    echo number_format($order['jarak_meter'], 0, ',', '.') . ' meter';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </span>
                        </li>
                        <li>
                            <span class="detail-label">Total Harga</span>
                            <span class="detail-value" style="color: var(--primary); font-weight: 700; font-size: 1.2rem;">
                                <?php 
                                if(!empty($order['harga']) && $order['harga'] > 0) {
                                    echo 'Rp ' . number_format($order['harga'], 0, ',', '.');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </span>
                        </li>
                        <li>
                            <span class="detail-label">Tanggal Order</span>
                            <span class="detail-value"><?php echo $created_at; ?></span>
                        </li>
                        <?php if(!empty($order['catatan'])): ?>
                        <li>
                            <span class="detail-label">Catatan</span>
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($order['catatan'])); ?></span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Lokasi -->
                <div class="detail-section">
                    <h3>
                        <i class="fas fa-map-marker-alt"></i>
                        Lokasi Pengiriman
                    </h3>
                    <div class="location-card">
                        <div class="location-item">
                            <div class="location-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="location-detail">
                                <div class="location-title">Lokasi Pengambilan</div>
                                <div class="location-address">
                                    <?php echo htmlspecialchars($order['posisi_ambil'] ?? '-'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="location-item">
                            <div class="location-icon">
                                <i class="fas fa-flag-checkered"></i>
                            </div>
                            <div class="location-detail">
                                <div class="location-title">Lokasi Pengantaran</div>
                                <div class="location-address">
                                    <?php echo htmlspecialchars($order['pengiriman'] ?? '-'); ?>
                                </div>
                            </div>
                        </div>
                        <?php if(!empty($order['jarak_meter']) && $order['jarak_meter'] > 0): ?>
                        <div class="distance-info">
                            <span><i class="fas fa-route"></i> Total Jarak</span>
                            <span style="font-weight: 700; color: var(--primary);">
                                <?php echo number_format($order['jarak_meter'], 0, ',', '.') . ' meter'; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Timeline Status -->
                <div class="detail-section">
                    <h3>
                        <i class="fas fa-history"></i>
                        Riwayat Status
                    </h3>
                    <div class="history-timeline">
                        <?php if(count($status_history) > 0): ?>
                            <?php foreach($status_history as $history): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon">
                                    <i class="fas <?php echo getStatusIcon($history['status']); ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        <?php echo getStatusIndo($history['status']); ?>
                                    </div>
                                    <div class="timeline-time">
                                        <?php echo date('d M Y H:i', strtotime($history['created_at'])); ?>
                                    </div>
                                    <?php if(!empty($history['keterangan'])): ?>
                                    <div style="font-size: 0.9rem; color: var(--gray); margin-top: 5px;">
                                        <?php echo htmlspecialchars($history['keterangan']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 30px; color: var(--gray);">
                                <i class="fas fa-info-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <p>Belum ada riwayat status</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div style="display: flex; gap: 15px; margin-top: 30px; justify-content: center;">
                <?php if($user_role == 'customer'): ?>
                    <a href="riwayat_pesanan.php" class="cta-button-outline">
                        <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
                    </a>
                    <?php if($order['status'] == 'pending'): ?>
                    <a href="batalkan_pesanan.php?id=<?php echo $order['id']; ?>" class="cta-button" style="background: var(--danger);" onclick="return confirm('Yakin ingin membatalkan pesanan?')">
                        <i class="fas fa-times"></i> Batalkan Pesanan
                    </a>
                    <?php endif; ?>
                <?php elseif($user_role == 'driver'): ?>
                    <a href="driver_orders.php" class="cta-button-outline">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Pesanan
                    </a>
                <?php elseif($user_role == 'admin'): ?>
                    <a href="admin_orders.php" class="cta-button-outline">
                        <i class="fas fa-arrow-left"></i> Kembali ke Admin Panel
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Auto-refresh halaman setiap 30 detik untuk update status
        <?php if(in_array($order['status'], ['processing', 'picking_up', 'picked_up', 'delivering'])): ?>
        setTimeout(function() {
            location.reload();
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
<?php
mysqli_stmt_close($stmt);
if(isset($stmt_history)) {
    mysqli_stmt_close($stmt_history);
}
mysqli_close($conn);
?>