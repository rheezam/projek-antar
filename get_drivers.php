<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    exit('Unauthorized');
}

$status = $_GET['status'] ?? 'all';

$query = "SELECT * FROM drivers";
if($status != 'all') {
    $query .= " WHERE status = '$status'";
}
$query .= " ORDER BY created_at DESC";

$result = mysqli_query($conn, $query);
?>

<?php if(mysqli_num_rows($result) > 0): ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>Sekolah</th>
                    <th>Kelas</th>
                    <th>Kendaraan</th>
                    <th>Status</th>
                    <th>Tanggal Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($driver = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#<?php echo str_pad($driver['id'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($driver['nama']); ?></td>
                        <td><?php echo htmlspecialchars($driver['sekolah']); ?></td>
                        <td><?php echo htmlspecialchars($driver['kelas']); ?></td>
                        <td><?php echo htmlspecialchars($driver['jenis_kendaraan']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $driver['status']; ?>">
                                <?php echo ucfirst($driver['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($driver['created_at'])); ?></td>
                        <td class="action-buttons">
                            <button class="btn btn-success btn-sm" onclick="viewDriverDetails(<?php echo $driver['id']; ?>)">
                                <i class="fas fa-eye"></i> Lihat
                            </button>
                            <?php if($driver['status'] == 'pending'): ?>
                                <button class="btn btn-primary btn-sm" onclick="approveDriver(<?php echo $driver['id']; ?>)">
                                    <i class="fas fa-check"></i> Setujui
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="rejectDriver(<?php echo $driver['id']; ?>)">
                                    <i class="fas fa-times"></i> Tolak
                                </button>
                            <?php elseif($driver['status'] == 'approved'): ?>
                                <span style="color: var(--success);"><i class="fas fa-check-circle"></i> Aktif</span>
                            <?php endif; ?>
                            <!-- TOMBOL HAPUS untuk semua status -->
                            <button class="btn btn-danger btn-sm" onclick="deleteDriver(<?php echo $driver['id']; ?>)">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p style="text-align: center; padding: 30px; color: var(--gray);">
        <i class="fas fa-users" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
        Tidak ada data driver
    </p>
<?php endif; ?>