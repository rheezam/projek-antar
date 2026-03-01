<?php
require_once 'config.php';

$error = '';
$success = '';

// Create drivers table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    no_kartu_pelajar VARCHAR(20) NOT NULL,
    no_hp VARCHAR(15) NOT NULL,
    alamat TEXT NOT NULL,
    sekolah VARCHAR(100) NOT NULL,
    kelas VARCHAR(20) NOT NULL,
    jenis_kendaraan VARCHAR(50) NOT NULL,
    foto_kartu_pelajar VARCHAR(255) NOT NULL,
    foto_diri VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    alasan_penolakan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

mysqli_query($conn, $create_table);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input
    $required_fields = ['nama', 'username', 'email', 'password', 'confirm_password', 'no_kartu_pelajar', 'no_hp', 'alamat', 'sekolah', 'kelas', 'jenis_kendaraan'];
    
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $error = 'Semua field wajib diisi!';
        break;
    }
}
    
    if (!$error) {
        if ($_POST['password'] !== $_POST['confirm_password']) {
            $error = 'Password dan Konfirmasi Password tidak cocok!';
        } elseif (strlen($_POST['password']) < 6) {
            $error = 'Password minimal 6 karakter!';
        } elseif (!isset($_FILES['foto_kartu_pelajar']) || $_FILES['foto_kartu_pelajar']['error'] != 0) {
            $error = 'Foto Kartu Pelajar wajib diupload!';
        } elseif (!isset($_FILES['foto_diri']) || $_FILES['foto_diri']['error'] != 0) {
            $error = 'Foto Diri wajib diupload!';
        } else {
            // Escape input
            $nama = mysqli_real_escape_string($conn, $_POST['nama']);
            $username = mysqli_real_escape_string($conn, $_POST['username']);
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $no_kartu_pelajar = mysqli_real_escape_string($conn, $_POST['no_kartu_pelajar']);
            $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
            $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
            $sekolah = mysqli_real_escape_string($conn, $_POST['sekolah']);
            $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
            $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
            $password = $_POST['password'];
            
            // Cek hanya di tabel drivers
            $check_query = "SELECT id FROM drivers WHERE username = '$username' OR email = '$email'";
            $check_result = mysqli_query($conn, $check_query);
            
            if (!$check_result) {
                $error = 'Error query: ' . mysqli_error($conn);
            } elseif (mysqli_num_rows($check_result) > 0) {
                $error = 'Username atau email sudah digunakan!';
            } else {
                // Handle upload foto kartu pelajar
                $foto_kartu_pelajar_name = '';
                if ($_FILES['foto_kartu_pelajar']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    
                    if (in_array($_FILES['foto_kartu_pelajar']['type'], $allowed_types)) {
                        if ($_FILES['foto_kartu_pelajar']['size'] <= $max_size) {
                            $upload_dir = 'uploads/drivers/kartu_pelajar/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $foto_kartu_pelajar_name = time() . '_kartu_pelajar_' . basename($_FILES['foto_kartu_pelajar']['name']);
                            $target_file = $upload_dir . $foto_kartu_pelajar_name;
                            
                            if (!move_uploaded_file($_FILES['foto_kartu_pelajar']['tmp_name'], $target_file)) {
                                $error = 'Gagal upload foto kartu pelajar';
                            }
                        } else {
                            $error = 'Foto kartu pelajar maksimal 2MB';
                        }
                    } else {
                        $error = 'Foto kartu pelajar harus format JPG/PNG';
                    }
                }
                
                // Handle upload foto diri
                $foto_diri_name = '';
                if (!$error && $_FILES['foto_diri']['error'] == 0) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    
                    if (in_array($_FILES['foto_diri']['type'], $allowed_types)) {
                        if ($_FILES['foto_diri']['size'] <= $max_size) {
                            $upload_dir = 'uploads/drivers/diri/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $foto_diri_name = time() . '_diri_' . basename($_FILES['foto_diri']['name']);
                            $target_file = $upload_dir . $foto_diri_name;
                            
                            if (!move_uploaded_file($_FILES['foto_diri']['tmp_name'], $target_file)) {
                                $error = 'Gagal upload foto diri';
                            }
                        } else {
                            $error = 'Foto diri maksimal 2MB';
                        }
                    } else {
                        $error = 'Foto diri harus format JPG/PNG';
                    }
                }
                
                if (!$error) {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert ke tabel drivers
                    $query = "INSERT INTO drivers (nama, username, email, password, no_kartu_pelajar, no_hp, alamat, 
                              sekolah, kelas, jenis_kendaraan, foto_kartu_pelajar, foto_diri) 
                             VALUES ('$nama', '$username', '$email', '$hashed_password', '$no_kartu_pelajar', '$no_hp', 
                                     '$alamat', '$sekolah', '$kelas', '$jenis_kendaraan', '$foto_kartu_pelajar_name', '$foto_diri_name')";
                    
                    if (mysqli_query($conn, $query)) {
                        $success = 'Pendaftaran driver berhasil! Akun Anda sedang dalam proses verifikasi oleh admin.';
                        $_POST = array();
                    } else {
                        $error = 'Terjadi kesalahan: ' . mysqli_error($conn);
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Sebagai Driver - SmariDelivery</title>
    <link rel="icon" href="Foto/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a237e 0%, #0A6A2A 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .back-home {
            position: fixed;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: white;
            background-color: rgba(0,0,0,0.3);
            padding: 10px 15px;
            border-radius: 12px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
            z-index: 100;
        }
        
        .back-home:hover {
            background-color: rgba(0,0,0,0.5);
            transform: translateX(-3px);
        }
        
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            margin-top: 60px;
        }
        
        .register-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .register-subtitle {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a237e;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #1a237e;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            font-size: 14px;
            font-weight: 600;
            color: #444;
            margin-bottom: 5px;
            display: block;
        }
        
        .required {
            color: #dc3545;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #1a237e;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
        }
        
        .input-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 14px;
            padding: 4px 8px;
        }
        
        .file-upload-group {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .file-upload-group:hover {
            border-color: #1a237e;
            background-color: rgba(26, 35, 126, 0.05);
        }
        
        .file-input {
            display: none;
        }
        
        .file-label {
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            color: #666;
        }
        
        .file-label i {
            font-size: 40px;
            color: #1a237e;
        }
        
        .file-preview {
            margin-top: 15px;
            display: none;
        }
        
        .preview-image {
            max-width: 200px;
            max-height: 150px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin: 0 auto;
            display: block;
        }
        
        .file-info {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            text-align: center;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            grid-column: 1 / -1;
        }
        
        button[type="submit"] {
            background: linear-gradient(to right, #1a237e, #283593);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        button[type="submit"]:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }
        
        button.back-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        button.back-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }
        
        .auth-link {
            text-align: center;
            margin-top: 25px;
            color: #666;
            grid-column: 1 / -1;
        }
        
        .auth-link a {
            color: #1a237e;
            text-decoration: none;
            font-weight: bold;
        }
        
        .auth-link a:hover {
            text-decoration: underline;
        }
        
        .error {
            background: #ffe6e6;
            color: #d32f2f;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #d32f2f;
            display: flex;
            align-items: center;
            gap: 10px;
            grid-column: 1 / -1;
        }
        
        .success {
            background: #e6ffe6;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
            display: flex;
            align-items: center;
            gap: 10px;
            grid-column: 1 / -1;
        }
        
        .form-help {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: block;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .register-container {
                padding: 20px;
                margin-top: 40px;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">
        <i class="fas fa-arrow-left"></i> Kembali ke Beranda
    </a>
    
    <div class="container">
        <div class="register-container">
            <div class="register-title">
                <i class="fas fa-motorcycle"></i> Daftar Sebagai Driver
            </div>
            <div class="register-subtitle">
                Daftar sebagai driver SmariDelivery - khusus untuk pelajar
            </div>
            
            <?php if($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-grid">
                    <!-- Data Pribadi -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i> Data Pribadi
                        </h3>
                        
                        <div class="form-group">
                            <label for="nama">Nama Lengkap <span class="required">*</span></label>
                            <input type="text" id="nama" name="nama" 
                                   value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username <span class="required">*</span></label>
                            <input type="text" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required>
                            <small class="form-help">Username untuk login</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="no_kartu_pelajar">Nomor Kartu Pelajar <span class="required">*</span></label>
                            <input type="text" id="no_kartu_pelajar" name="no_kartu_pelajar" 
                                   value="<?php echo isset($_POST['no_kartu_pelajar']) ? htmlspecialchars($_POST['no_kartu_pelajar']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="no_hp">Nomor HP/WA <span class="required">*</span></label>
                            <input type="tel" id="no_hp" name="no_hp" 
                                   value="<?php echo isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="alamat">Alamat Lengkap <span class="required">*</span></label>
                            <textarea id="alamat" name="alamat" required><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Data Sekolah -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-school"></i> Data Sekolah
                        </h3>
                        
                        <div class="form-group">
                            <label for="sekolah">Nama Sekolah <span class="required">*</span></label>
                            <input type="text" id="sekolah" name="sekolah" 
                                   value="<?php echo isset($_POST['sekolah']) ? htmlspecialchars($_POST['sekolah']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="kelas">Kelas <span class="required">*</span></label>
                            <input type="text" id="kelas" name="kelas" 
                                   value="<?php echo isset($_POST['kelas']) ? htmlspecialchars($_POST['kelas']) : ''; ?>" 
                                   placeholder="Contoh: X IPA 1, XII IPS 3" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="jenis_kendaraan">Jenis Kendaraan <span class="required">*</span></label>
                            <select id="jenis_kendaraan" name="jenis_kendaraan" required>
                                <option value="">Pilih Jenis Kendaraan</option>
                                <option value="Sepeda Motor" <?php echo (isset($_POST['jenis_kendaraan']) && $_POST['jenis_kendaraan'] == 'Sepeda Motor') ? 'selected' : ''; ?>>Sepeda Motor</option>
                                <option value="Sepeda" <?php echo (isset($_POST['jenis_kendaraan']) && $_POST['jenis_kendaraan'] == 'Sepeda') ? 'selected' : ''; ?>>Sepeda</option>
                                <option value="Lainnya" <?php echo (isset($_POST['jenis_kendaraan']) && $_POST['jenis_kendaraan'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                        </div>
                        
                        <!-- Foto Kartu Pelajar -->
                        <div class="form-group">
                            <label for="foto_kartu_pelajar">Foto Kartu Pelajar <span class="required">*</span></label>
                            <small class="form-help">Upload foto kartu pelajar yang jelas dan terlihat</small>
                            <div class="file-upload-group">
                                <label for="foto_kartu_pelajar" class="file-label" id="foto_kartu_pelajar_label">
                                    <i class="fas fa-id-card"></i>
                                    <span>Klik untuk upload Foto Kartu Pelajar</span>
                                    <span style="font-size: 11px; color: #999;">Format: JPG/PNG, maks 2MB</span>
                                </label>
                                <input type="file" id="foto_kartu_pelajar" name="foto_kartu_pelajar" 
                                       accept="image/*" class="file-input"
                                       onchange="previewFile(this, 'preview_kartu_pelajar')">
                                <div class="file-preview" id="preview_kartu_pelajar">
                                    <img class="preview-image" src="" alt="Preview Kartu Pelajar">
                                    <div class="file-info"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Foto Diri -->
                        <div class="form-group">
                            <label for="foto_diri">Foto Diri <span class="required">*</span></label>
                            <small class="form-help">Upload foto selfie terbaru dengan jelas</small>
                            <div class="file-upload-group">
                                <label for="foto_diri" class="file-label" id="foto_diri_label">
                                    <i class="fas fa-camera"></i>
                                    <span>Klik untuk upload Foto Diri</span>
                                    <span style="font-size: 11px; color: #999;">Format: JPG/PNG, maks 2MB</span>
                                </label>
                                <input type="file" id="foto_diri" name="foto_diri" 
                                       accept="image/*" class="file-input"
                                       onchange="previewFile(this, 'preview_diri')">
                                <div class="file-preview" id="preview_diri">
                                    <img class="preview-image" src="" alt="Preview Diri">
                                    <div class="file-info"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div class="form-section" style="grid-column: 1 / -1;">
                        <h3 class="section-title">
                            <i class="fas fa-lock"></i> Keamanan Akun
                        </h3>
                        
                        <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                            <div class="form-group">
                                <label for="password">Password <span class="required">*</span></label>
                                <div class="input-container">
                                    <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="form-help">Minimal 6 karakter</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Konfirmasi Password <span class="required">*</span></label>
                                <div class="input-container">
                                    <input type="password" id="confirm_password" name="confirm_password" 
                                           placeholder="Masukkan password lagi" required>
                                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-user-plus"></i> Daftar sebagai Driver
                        </button>
                        <button type="button" class="back-btn" onclick="window.location.href='index.php'">
                            <i class="fas fa-home"></i> Kembali ke Beranda
                        </button>
                    </div>
                    
                    <div class="auth-link">
                        Sudah punya akun driver? <a href="login.php">Login di sini</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewFile(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.style.display = 'block';
                    preview.querySelector('img').src = e.target.result;
                    preview.querySelector('.file-info').textContent = 
                        `${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                };
                
                reader.readAsDataURL(file);
                
                // Update label
                const labelId = input.id + '_label';
                const label = document.getElementById(labelId);
                label.querySelector('span').textContent = 'File terpilih: ' + file.name;
            } else {
                preview.style.display = 'none';
            }
        }
        
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Validasi password minimal 6 karakter
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return;
            }
            
            // Validasi konfirmasi password
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan Konfirmasi Password tidak cocok!');
                return;
            }
            
            // Validasi file upload
            const fotoKartu = document.getElementById('foto_kartu_pelajar').files.length;
            const fotoDiri = document.getElementById('foto_diri').files.length;
            
            if (fotoKartu === 0) {
                e.preventDefault();
                alert('Foto Kartu Pelajar wajib diupload!');
                return;
            }
            
            if (fotoDiri === 0) {
                e.preventDefault();
                alert('Foto Diri wajib diupload!');
                return;
            }
        });
    </script>
</body>
</html>