<?php
session_start();
require_once 'config.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    exit('Unauthorized');
}

$driver_id = (int)$_GET['id'];
$query = "SELECT * FROM drivers WHERE id = $driver_id";
$result = mysqli_query($conn, $query);
$driver = mysqli_fetch_assoc($result);
?>

<div class="driver-details">
    <div class="detail-row">
        <div class="detail-label">Nama Lengkap:</div>
        <div class="detail-value"><?php echo htmlspecialchars($driver['nama']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Username:</div>
        <div class="detail-value"><?php echo htmlspecialchars($driver['username']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Email:</div>
        <div class="detail-value"><?php echo htmlspecialchars($driver['email']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">No. Kartu Pelajar:</div>
        <div class="detail-value"><?php echo htmlspecialchars($driver['no_kartu_pelajar']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">No. HP/WA:</div>
        <div class="detail-value"><?php echo htmlspecialchars($driver['no_hp']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Alamat:</div>
        <div class="detail-value"><?php echo htmlspecialchars($driver['alamat']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Sekolah:</div>
        <div class="detail-value"><?php echo htmlspecialchars($driver['sekolah']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Kelas:</div>
        <div class="detail-value"><?php echo htmlspecialchars($driver['kelas']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Jenis Kendaraan:</div>
        <div class="detail-value"><?php echo htmlspecialchars($driver['jenis_kendaraan']); ?></div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Status:</div>
        <div class="detail-value">
            <span class="status-badge status-<?php echo $driver['status']; ?>">
                <?php echo ucfirst($driver['status']); ?>
            </span>
        </div>
    </div>
    <div class="detail-row">
        <div class="detail-label">Tanggal Daftar:</div>
        <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($driver['created_at'])); ?></div>
    </div>
    
    <?php if($driver['alasan_penolakan']): ?>
    <div class="detail-row">
        <div class="detail-label">Alasan Penolakan:</div>
        <div class="detail-value" style="color: var(--danger);">
            <?php echo htmlspecialchars($driver['alasan_penolakan']); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <h4 style="margin-top: 20px; margin-bottom: 10px;">Foto Dokumentasi:</h4>
    <div class="photo-grid">
        <div class="photo-item">
            <strong>Kartu Pelajar:</strong><br>
            <?php if($driver['foto_kartu_pelajar']): ?>
                <img src="uploads/drivers/kartu_pelajar/<?php echo htmlspecialchars($driver['foto_kartu_pelajar']); ?>" 
                     alt="Kartu Pelajar" 
                     onclick="window.open('uploads/drivers/kartu_pelajar/<?php echo htmlspecialchars($driver['foto_kartu_pelajar']); ?>')"
                     style="cursor: pointer;">
            <?php else: ?>
                <p style="color: var(--gray); font-style: italic;">Tidak ada foto</p>
            <?php endif; ?>
        </div>
        <div class="photo-item">
            <strong>Foto Diri:</strong><br>
            <?php if($driver['foto_diri']): ?>
                <img src="uploads/drivers/diri/<?php echo htmlspecialchars($driver['foto_diri']); ?>" 
                     alt="Foto Diri" 
                     onclick="window.open('uploads/drivers/diri/<?php echo htmlspecialchars($driver['foto_diri']); ?>')"
                     style="cursor: pointer;">
            <?php else: ?>
                <p style="color: var(--gray); font-style: italic;">Tidak ada foto</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if($driver['status'] == 'pending'): ?>
    <div style="margin-top: 20px; display: flex; gap: 10px;">
        <button class="btn btn-primary" onclick="approveDriver(<?php echo $driver['id']; ?>)">
            <i class="fas fa-check"></i> Setujui Driver
        </button>
        <button class="btn btn-danger" onclick="rejectDriver(<?php echo $driver['id']; ?>)">
            <i class="fas fa-times"></i> Tolak Driver
        </button>
    </div>
    <?php endif; ?>
</div>