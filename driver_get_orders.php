<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'driver') {
    exit('Unauthorized');
}

$driver_id = $_SESSION['driver_id'];
$status = $_GET['status'] ?? 'all';

$query = "SELECT o.*, u.nama as customer_name, u.alamat as customer_alamat, u.no_hp as customer_phone 
          FROM orders o 
          JOIN users u ON o.nama = u.nama 
          WHERE o.driver_id = $driver_id";

if($status != 'all') {
    $query .= " AND o.status = '$status'";
}

$query .= " ORDER BY o.created_at DESC";

$result = mysqli_query($conn, $query);
?>

<?php if(mysqli_num_rows($result) > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID Order</th>
                    <th>Customer</th>
                    <th>Jenis Barang</th>
                    <th>Jumlah</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($order = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#ORD-<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['jenis_barang']); ?></td>
                        <td><?php echo $order['jumlah_barang']; ?> pcs</td>
                        <td>Rp <?php echo number_format($order['harga'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        <td class="action-buttons">
                            <?php if($order['status'] == 'pending'): ?>
                                <button class="btn btn-warning btn-sm" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'processing')">
                                    <i class="fas fa-box"></i> Ambil
                                </button>
                            <?php elseif($order['status'] == 'processing'): ?>
                                <button class="btn btn-success btn-sm" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'completed')">
                                    <i class="fas fa-check-circle"></i> Selesai
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="table-container">
        <p style="text-align: center; padding: 30px; color: var(--gray);">
            <i class="fas fa-box-open" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
            Tidak ada pesanan
        </p>
    </div>
<?php endif; ?>