<?php
session_start();
require_once 'config.php';

// Cek apakah user adalah admin
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Hitung statistik
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM drivers) as total_drivers,
        (SELECT COUNT(*) FROM drivers WHERE status = 'pending') as pending_drivers,
        (SELECT COUNT(*) FROM drivers WHERE status = 'approved') as active_drivers,
        (SELECT COUNT(*) FROM orders) as total_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed_orders,
        COALESCE((SELECT SUM(harga) FROM orders WHERE status = 'completed'), 0) as total_revenue
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Ambil data terbaru
$recent_orders_query = "SELECT o.*, u.nama as nama_pemesan 
                        FROM orders o 
                        LEFT JOIN users u ON o.nama = u.nama 
                        ORDER BY o.created_at DESC LIMIT 10";
$recent_orders = mysqli_query($conn, $recent_orders_query);

$pending_drivers_query = "SELECT * FROM drivers WHERE status = 'pending' ORDER BY created_at DESC";
$pending_drivers = mysqli_query($conn, $pending_drivers_query);

// Handle actions
// Di bagian Handle actions, tambahkan kondisi untuk delete
if(isset($_POST['action'])) {
    $driver_id = (int)$_POST['driver_id'];
    $action = $_POST['action'];
    
    if($action == 'approve') {
        $update_query = "UPDATE drivers SET status = 'approved' WHERE id = $driver_id";
        $message = "Driver berhasil disetujui!";
    } elseif($action == 'reject') {
        $alasan = mysqli_real_escape_string($conn, $_POST['alasan_penolakan']);
        $update_query = "UPDATE drivers SET status = 'rejected', alasan_penolakan = '$alasan' WHERE id = $driver_id";
        $message = "Driver berhasil ditolak!";
    } elseif($action == 'update_order_status') {
        $order_id = (int)$_POST['order_id'];
        $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
        $update_query = "UPDATE orders SET status = '$new_status' WHERE id = $order_id";
        $message = "Status pesanan berhasil diupdate!";
    } elseif($action == 'delete_driver') {  // TAMBAHKAN INI
        // Hapus foto terlebih dahulu
        $query_foto = "SELECT foto_kartu_pelajar, foto_diri FROM drivers WHERE id = $driver_id";
        $result_foto = mysqli_query($conn, $query_foto);
        if($driver = mysqli_fetch_assoc($result_foto)) {
            // Hapus file foto kartu pelajar
            if($driver['foto_kartu_pelajar'] && file_exists('uploads/drivers/kartu_pelajar/' . $driver['foto_kartu_pelajar'])) {
                unlink('uploads/drivers/kartu_pelajar/' . $driver['foto_kartu_pelajar']);
            }
            // Hapus file foto diri
            if($driver['foto_diri'] && file_exists('uploads/drivers/diri/' . $driver['foto_diri'])) {
                unlink('uploads/drivers/diri/' . $driver['foto_diri']);
            }
        }
        
        $update_query = "DELETE FROM drivers WHERE id = $driver_id";
        $message = "Driver berhasil dihapus!";
    }
    
    if(isset($update_query) && mysqli_query($conn, $update_query)) {
        header("Location: admin_dashboard.php?success=" . urlencode($message));
        exit();
    }
}

// Handle export
if(isset($_GET['export'])) {
    $type = $_GET['export'];
    
    if($type == 'orders') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Pemesan', 'Jenis Barang', 'Jumlah', 'Status', 'Total Harga', 'Tanggal'));
        
        $query = "SELECT o.*, u.nama as nama_pemesan FROM orders o LEFT JOIN users u ON o.nama = u.nama ORDER BY o.created_at DESC";
        $result = mysqli_query($conn, $query);
        
        while($row = mysqli_fetch_assoc($result)) {
            fputcsv($output, array(
                $row['id'],
                $row['nama_pemesan'] ?: $row['nama'],
                $row['jenis_barang'],
                $row['jumlah_barang'],
                $row['status'],
                'Rp ' . number_format($row['harga'], 0, ',', '.'),
                $row['created_at']
            ));
        }
        fclose($output);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | SmariDelivery</title>
        <link rel="icon" href="Foto/logo.png">
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

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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

        .admin-info {
            text-align: center;
            padding: 15px;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            margin: 0 15px 20px;
        }

        .admin-info h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .admin-info p {
            font-size: 0.9rem;
            opacity: 0.8;
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

        /* Main Content */
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

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #283593;
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
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

        /* Stats Cards */
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

        .stat-icon.users { background: rgba(40, 167, 69, 0.1); color: var(--success); }
        .stat-icon.drivers { background: rgba(26, 35, 126, 0.1); color: var(--secondary); }
        .stat-icon.orders { background: rgba(255, 193, 7, 0.1); color: var(--warning); }
        .stat-icon.revenue { background: rgba(23, 162, 184, 0.1); color: var(--info); }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
        }

        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Tables */
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
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-completed { background: #65cf0e; color: #000000; }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }


        .btn-delete {
        background: linear-gradient(to right, #dc3545, #c82333);
        color: white;
    }

    .btn-delete:hover {
        background: linear-gradient(to right, #c82333, #bd2130);
        transform: translateY(-2px);
    }
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--dark);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: vertical;
            min-height: 100px;
        }

        .modal-footer {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Driver Details */
        .driver-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: 600;
            width: 150px;
            color: var(--gray);
        }

        .detail-value {
            flex: 1;
        }

        .photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .photo-item {
            text-align: center;
        }

        .photo-item img {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-bottom: 5px;
        }

        /* Success/Error Messages */
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

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                width: 200px;
            }
            .main-content {
                margin-left: 200px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .admin-container {
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
            table {
                display: block;
                overflow-x: auto;
            }
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
        }

        .tab {
            padding: 12px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: var(--gray);
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: var(--transition);
        }

        .tab:hover {
            color: var(--primary);
        }

        .tab.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h2>
                    <i class="fas fa-cogs"></i>
                    Admin Panel
                </h2>
            </div>
            
            <div class="admin-info">
                <h3><?php echo htmlspecialchars($admin_name); ?></h3>
                <p>Administrator</p>
            </div>
            
            <ul class="nav-links">
                <li><a href="#" class="active" data-tab="dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                <li><a href="#" data-tab="orders">
                    <i class="fas fa-shopping-cart"></i> Pesanan
                </a></li>
                <li><a href="#" data-tab="drivers">
                    <i class="fas fa-motorcycle"></i> Drivers
                </a></li>
                <li><a href="#" data-tab="users">
                    <i class="fas fa-users"></i> Pengguna
                </a></li>
                <li><a href="#" data-tab="reports">
                    <i class="fas fa-chart-bar"></i> Laporan
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
                    Dashboard Admin
                </h1>
                <div class="header-actions">
                    <a href="?export=orders" class="btn btn-primary">
                        <i class="fas fa-download"></i> Export Data
                    </a>
                    <span style="padding: 8px 15px; background: var(--light-gray); border-radius: 6px;">
                        <?php echo date('d F Y'); ?>
                    </span>
                </div>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> 
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total Pengguna</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon drivers">
                            <i class="fas fa-motorcycle"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_drivers']; ?></div>
                        <div class="stat-label">Total Drivers</div>
                        <small style="color: var(--warning);">Pending: <?php echo $stats['pending_drivers']; ?></small>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orders">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                        <div class="stat-label">Total Pesanan</div>
                        <small style="color: var(--warning);">Pending: <?php echo $stats['pending_orders']; ?></small>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon revenue">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value">Rp <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-history"></i> Pesanan Terbaru</h2>
                    </div>
                    
                    <?php if(mysqli_num_rows($recent_orders) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pemesan</th>
                                    <th>Jenis Barang</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['nama'] ?: $order['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($order['jenis_barang']); ?></td>
                                        <td><?php echo $order['jumlah_barang']; ?> pcs</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>Rp <?php echo number_format($order['harga'], 0, ',', '.'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="new_status" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                                <input type="hidden" name="action" value="update_order_status">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: var(--gray);">
                            <i class="fas fa-inbox" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                            Belum ada pesanan
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Pending Drivers -->
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-clock"></i> Driver Menunggu Verifikasi</h2>
                    </div>
                    
                    <?php if(mysqli_num_rows($pending_drivers) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Sekolah</th>
                                    <th>Kelas</th>
                                    <th>Kendaraan</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($driver = mysqli_fetch_assoc($pending_drivers)): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($driver['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($driver['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($driver['sekolah']); ?></td>
                                        <td><?php echo htmlspecialchars($driver['kelas']); ?></td>
                                        <td><?php echo htmlspecialchars($driver['jenis_kendaraan']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($driver['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-success btn-sm" onclick="viewDriverDetails(<?php echo $driver['id']; ?>)">
                                                <i class="fas fa-eye"></i> Lihat
                                            </button>
                                            <button class="btn btn-primary btn-sm" onclick="approveDriver(<?php echo $driver['id']; ?>)">
                                                <i class="fas fa-check"></i> Setujui
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="rejectDriver(<?php echo $driver['id']; ?>)">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: var(--gray);">
                            <i class="fas fa-check-circle" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                            Semua driver sudah diverifikasi
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Orders Tab -->
            <div id="orders" class="tab-content">
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-shopping-cart"></i> Semua Pesanan</h2>
                        <a href="?export=orders" class="btn btn-primary">
                            <i class="fas fa-download"></i> Export CSV
                        </a>
                    </div>
                    
                    <?php 
                    $all_orders_query = "SELECT o.*, u.nama as nama_pemesan 
                                         FROM orders o 
                                         LEFT JOIN users u ON o.nama = u.nama 
                                         ORDER BY o.created_at DESC";
                    $all_orders = mysqli_query($conn, $all_orders_query);
                    ?>
                    
                    <?php if(mysqli_num_rows($all_orders) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pemesan</th>
                                    <th>Jenis Barang</th>
                                    <th>Jumlah</th>
                                    <th>Berat</th>
                                    <th>Lokasi</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = mysqli_fetch_assoc($all_orders)): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['nama_pemesan'] ?: $order['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($order['jenis_barang']); ?></td>
                                        <td><?php echo $order['jumlah_barang']; ?> pcs</td>
                                        <td><?php echo $order['berat_barang']; ?></td>
                                        <td><?php echo htmlspecialchars($order['posisi_ambil']); ?> → <?php echo htmlspecialchars($order['pengiriman']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>Rp <?php echo number_format($order['harga'], 0, ',', '.'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <select name="new_status" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                </select>
                                                <input type="hidden" name="action" value="update_order_status">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: var(--gray);">
                            <i class="fas fa-inbox" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                            Belum ada pesanan
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Drivers Tab -->
            <div id="drivers" class="tab-content">
                <div class="tabs">
                    <button class="tab active" onclick="switchDriverTab('all')">Semua Driver</button>
                    <button class="tab" onclick="switchDriverTab('pending')">Pending (<?php echo $stats['pending_drivers']; ?>)</button>
                    <button class="tab" onclick="switchDriverTab('active')">Aktif (<?php echo $stats['active_drivers']; ?>)</button>
                </div>
                
                <div id="drivers-content">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>

            <!-- Users Tab -->
            <div id="users" class="tab-content">
                <div class="table-container">
                    <div class="table-header">
                        <h2><i class="fas fa-users"></i> Semua Pengguna</h2>
                    </div>
                    
                    <?php 
                    $all_users_query = "SELECT * FROM users ORDER BY created_at DESC";
                    $all_users = mysqli_query($conn, $all_users_query);
                    ?>
                    
                    <?php if(mysqli_num_rows($all_users) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>No. HP</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Total Pesanan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($user = mysqli_fetch_assoc($all_users)): 
                                    $order_count_query = "SELECT COUNT(*) as total FROM orders WHERE nama = '" . $user['nama'] . "'";
                                    $order_count_result = mysqli_query($conn, $order_count_query);
                                    $order_count = mysqli_fetch_assoc($order_count_result)['total'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['nama']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['no_telepon']); ?></td>
                                        <td><?php echo htmlspecialchars($user['jenis_kelamin']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td><?php echo $order_count; ?> pesanan</td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: var(--gray);">
                            <i class="fas fa-users" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                            Belum ada pengguna terdaftar
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reports Tab -->
            <div id="reports" class="tab-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon revenue">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value">Rp <?php echo number_format($stats['total_revenue'] ?? 0, 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orders">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
                        <div class="stat-label">Pesanan Selesai</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon drivers">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-value"><?php echo $stats['active_drivers']; ?></div>
                        <div class="stat-label">Driver Aktif</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon users">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <?php 
                        $new_users_query = "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()";
                        $new_users_result = mysqli_query($conn, $new_users_query);
                        $new_users = mysqli_fetch_assoc($new_users_result)['total'];
                        ?>
                        <div class="stat-value"><?php echo $new_users; ?></div>
                        <div class="stat-label">Pengguna Baru Hari Ini</div>
                    </div>
                </div>
                
                <div class="table-container">
                    <h3 style="margin-bottom: 20px;">Rekapitulasi Pesanan Bulan Ini</h3>
                    <?php 
                    $monthly_query = "
                        SELECT 
                            DATE(created_at) as tanggal,
                            COUNT(*) as jumlah_pesanan,
                            SUM(harga) as total_pendapatan
                        FROM orders 
                        WHERE MONTH(created_at) = MONTH(CURDATE()) 
                        AND YEAR(created_at) = YEAR(CURDATE())
                        GROUP BY DATE(created_at)
                        ORDER BY tanggal DESC
                    ";
                    $monthly_result = mysqli_query($conn, $monthly_query);
                    ?>
                    
                    <?php if(mysqli_num_rows($monthly_result) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah Pesanan</th>
                                    <th>Total Pendapatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($monthly_result)): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                        <td><?php echo $row['jumlah_pesanan']; ?></td>
                                        <td>Rp <?php echo number_format($row['total_pendapatan'], 0, ',', '.'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="text-align: center; padding: 30px; color: var(--gray);">
                            <i class="fas fa-chart-bar" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                            Belum ada data bulan ini
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Driver Details -->
    <div id="driverModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detail Driver</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div id="driverDetails"></div>
        </div>
    </div>

    <!-- Modal for Reject Driver -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tolak Driver</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" id="rejectForm">
                <input type="hidden" name="driver_id" id="rejectDriverId">
                <input type="hidden" name="action" value="reject">
                <div class="form-group">
                    <label for="alasan_penolakan">Alasan Penolakan</label>
                    <textarea id="alasan_penolakan" name="alasan_penolakan" required placeholder="Berikan alasan penolakan..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Driver</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Tab Navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-links a');
            const tabContents = document.querySelectorAll('.tab-content');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const tabId = this.getAttribute('data-tab');
                    
                    // Update active nav link
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show selected tab
                    tabContents.forEach(tab => {
                        tab.classList.remove('active');
                        if(tab.id === tabId) {
                            tab.classList.add('active');
                        }
                    });
                    
                    // Load drivers tab content if needed
                    if(tabId === 'drivers') {
                        loadDrivers('all');
                    }
                });
            });
            
            // Load initial drivers data
            loadDrivers('all');
        });
        
        // Driver Tabs
        function switchDriverTab(tab) {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            loadDrivers(tab);
        }
        
        function loadDrivers(status) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `get_drivers.php?status=${status}`, true);
            xhr.onload = function() {
                if(this.status === 200) {
                    document.getElementById('drivers-content').innerHTML = this.responseText;
                }
            };
            xhr.send();
        }
        
        // Driver Actions
        function viewDriverDetails(driverId) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `get_driver_details.php?id=${driverId}`, true);
            xhr.onload = function() {
                if(this.status === 200) {
                    document.getElementById('driverDetails').innerHTML = this.responseText;
                    document.getElementById('driverModal').style.display = 'flex';
                }
            };
            xhr.send();
        }
        
        function approveDriver(driverId) {
            if(confirm('Setujui driver ini?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const driverIdInput = document.createElement('input');
                driverIdInput.type = 'hidden';
                driverIdInput.name = 'driver_id';
                driverIdInput.value = driverId;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'approve';
                
                form.appendChild(driverIdInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function rejectDriver(driverId) {
            document.getElementById('rejectDriverId').value = driverId;
            document.getElementById('rejectModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('driverModal').style.display = 'none';
            document.getElementById('rejectModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if(event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        };
        
        // Export functions
        function exportData(type) {
            window.location.href = `admin_dashboard.php?export=${type}`;
        }

        
    // Tambahkan function ini di bagian JavaScript

    function deleteDriver(driverId) {
        if(confirm('Apakah Anda yakin ingin menghapus driver ini? Data yang dihapus tidak dapat dikembalikan!')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const driverIdInput = document.createElement('input');
            driverIdInput.type = 'hidden';
            driverIdInput.name = 'driver_id';
            driverIdInput.value = driverId;
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_driver';
            
            form.appendChild(driverIdInput);
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
        }
    }



    </script>
</body>
</html>