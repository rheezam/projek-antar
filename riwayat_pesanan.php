<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if(!isset($_SESSION['nama'])) {
    header("Location: login.php");
    exit();
}

// Ambil nama user dari session
$nama_user = $_SESSION['nama'];
$success = isset($_GET['success']) ? (int)$_GET['success'] : 0;
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query untuk mengambil riwayat pesanan berdasarkan NAMA user
$riwayat_query = "SELECT id, jenis_barang, jumlah_barang, berat_barang, posisi_ambil, 
                  pengiriman, status, created_at, jarak_meter, harga 
                  FROM orders 
                  WHERE nama = ? 
                  ORDER BY id DESC";

$stmt_riwayat = mysqli_prepare($conn, $riwayat_query);
if($stmt_riwayat) {
    mysqli_stmt_bind_param($stmt_riwayat, "s", $nama_user);
    mysqli_stmt_execute($stmt_riwayat);
    $riwayat_result = mysqli_stmt_get_result($stmt_riwayat);
} else {
    die("Error dalam query: " . mysqli_error($conn));
}

// Fungsi untuk mendapatkan status dalam Bahasa Indonesia
function getStatusIndo($status) {
    $statusMap = [
        'pending' => 'Menunggu',
        'processing' => 'Diproses',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan'
    ];
    return $statusMap[$status] ?? $status;
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
    return $beratMap[$berat] ?? '-';
}

// Fungsi untuk mendapatkan class status
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
    <title>Riwayat Pesanan - SmariDelivery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        .user-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .user-detail h3 {
            color: var(--dark);
            margin-bottom: 5px;
        }

        .user-detail p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Success Message */
        .success-alert {
            background-color: rgba(40, 167, 69, 0.1);
            border: 1px solid var(--success);
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* History Container */
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

        .order-count {
            background: var(--light-gray);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .order-count span {
            font-weight: bold;
            color: var(--primary);
            margin-left: 5px;
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
            padding: 60px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--dark);
        }

        .empty-state p {
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-top: 80px;
            }
            
            .history-container {
                padding: 20px;
            }
            
            .history-table {
                display: block;
                overflow-x: auto;
            }
            
            .history-table th,
            .history-table td {
                white-space: nowrap;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
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
                    <a href="buat_pesanan.php">Buat Pesanan</a>
                    <a href="riwayat_pesanan.php" class="active">Riwayat Pesanan</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Title -->
            <div class="page-title">
                <h1>Riwayat Pesanan</h1>
                <p>Lihat semua pesanan yang telah Anda buat</p>
            </div>

            <?php if($success && $order_id): ?>
                <div class="success-alert">
                    <i class="fas fa-check-circle"></i> 
                    <span>Pesanan berhasil dibuat! ID Pesanan: #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></span>
                </div>
            <?php endif; ?>

            <!-- User Info -->
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($nama_user, 0, 1)); ?>
                </div>
                <div class="user-detail">
                    <h3><?php echo htmlspecialchars($nama_user); ?></h3>
                    <p><i class="fas fa-user"></i> Member</p>
                </div>
            </div>

            <!-- History Container -->
            <div class="history-container">
                <div class="history-header">
                    <h2 class="history-title">Semua Pesanan</h2>
                    <div class="order-count">
                        Total Pesanan <span>
                            <?php 
                            if($riwayat_result) {
                                echo mysqli_num_rows($riwayat_result); 
                            } else {
                                echo '0';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <?php if($riwayat_result && mysqli_num_rows($riwayat_result) > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Jenis Barang</th>
                                <th>Jumlah</th>
                                <th>Berat</th>
                                <th>Jarak</th>
                                <th>Total Harga</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            while($row = mysqli_fetch_assoc($riwayat_result)): 
                            ?>
                            <tr>
                                <td>#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($row['jenis_barang'] ?? '-'); ?></td>
                                <td><?php echo ($row['jumlah_barang'] ?? '0') . ' pcs'; ?></td>
                                <td><?php echo getBeratLabel($row['berat_barang'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                    if(!empty($row['jarak_meter']) && $row['jarak_meter'] > 0) {
                                        echo number_format($row['jarak_meter'], 0, ',', '.') . ' m';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if(!empty($row['harga']) && $row['harga'] > 0) {
                                        echo 'Rp ' . number_format($row['harga'], 0, ',', '.');
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo getStatusClass($row['status'] ?? 'pending'); ?>">
                                        <?php echo getStatusIndo($row['status'] ?? 'pending'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if(isset($row['created_at'])) {
                                        echo date('d/m/Y H:i', strtotime($row['created_at']));
                                    }
                                    ?>
                                </td>
                            
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>Belum ada riwayat pesanan</h3>
                        <p>Mulai buat pesanan pertama Anda sekarang juga!</p>
                        <a href="buat_pesanan.php" class="cta-button">
                            <i class="fas fa-plus"></i> Buat Pesanan Baru
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Auto-hide success message after 5 seconds
        <?php if($success): ?>
        setTimeout(function() {
            const alert = document.querySelector('.success-alert');
            if(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
<?php 
// Tutup statement jika ada
if(isset($stmt_riwayat)) {
    mysqli_stmt_close($stmt_riwayat);
}
mysqli_close($conn); 
?>