<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input
    if (empty($_POST['nama']) || empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'Nama, Username, dan Password wajib diisi!';
    } elseif ($_POST['password'] !== $_POST['confirm_password']) {
        $error = 'Password dan Konfirmasi Password tidak cocok!';
    } elseif (strlen($_POST['password']) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Escape input
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
        $no_hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
        $password = $_POST['password'];
        
        // Cek apakah username sudah ada di tabel users
        $check_query = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (!$check_result) {
            $error = 'Error query: ' . mysqli_error($conn);
        } elseif (mysqli_num_rows($check_result) > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert ke tabel users
            $query = "INSERT INTO users (nama, username, email, password, no_telepon, jenis_kelamin, kelas) 
                     VALUES ('$nama', '$username', '$username', '$hashed_password', '$no_hp', '$jenis_kelamin', 'Belum diisi')";
            
            if (mysqli_query($conn, $query)) {
                $success = 'Pendaftaran berhasil! Silakan login.';
                
                // DEBUG: Tampilkan informasi
          
                // Kosongkan form
                $_POST = array();
            } else {
                $error = 'Terjadi kesalahan: ' . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register | SmariDelivery</title>
    <link rel="icon" href="Foto/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #178915 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            position: relative;
        }
        
        .auth-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .auth-subtitle {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-bottom: 30px;
        }
        
        form {
            display: flex;
            flex-direction: column;
        }
        
        label {
            font-size: 14px;
            font-weight: 600;
            color: #444;
            margin-bottom: 8px;
            margin-top: 15px;
        }
        
        /* Container untuk input password */
        .input-container {
            position: relative;
            margin-bottom: 5px;
        }
        
        input, select {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 5px;
            width: 100%;
        }
        
        /* Tombol show/hide password */
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 16px;
            padding: 4px 8px;
        }
        
        .toggle-password:hover {
            color: #178915;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        
        button[type="submit"] {
            background: linear-gradient(to right, #178915, #1a9c17);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        button[type="submit"]:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23, 137, 21, 0.3);
        }
        
        button.back-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        button.back-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .auth-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .auth-link a {
            color: #667eea;
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
        }
        
    </style>
</head>
<body>    
    <div class="auth-card">
        <div class="auth-title">
            <i class="fas fa-user-plus"></i> Register
        </div>
        <div class="auth-subtitle">Buat akun SmariDelivery untuk mulai memesan</div>
        
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

        <form method="POST" action="">
            <label for="nama">
                <i class="fas fa-user"></i> Nama Lengkap
            </label>
            <input type="text" id="nama" name="nama" placeholder="Masukkan nama lengkap" 
                   value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" required>
            
            <label for="username">
                <i class="fas fa-at"></i> Username
            </label>
            <input type="text" id="username" name="username" placeholder="Buat username" 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            
            <label for="jenis_kelamin">
                <i class="fas fa-venus-mars"></i> Jenis Kelamin
            </label>
            <select id="jenis_kelamin" name="jenis_kelamin" required>
                <option value="">Pilih jenis kelamin</option>
                <option value="Laki-laki" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                <option value="Perempuan" <?php echo (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
            </select>
            
            <label for="no_hp">
                <i class="fas fa-phone"></i> No. HP
            </label>
            <input type="tel" id="no_hp" name="no_hp" placeholder="08xxxxxxxxxx" 
                   value="<?php echo isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : ''; ?>" required>
            
            <label for="password">
                <i class="fas fa-lock"></i> Password
            </label>
            <!-- Container untuk password field -->
            <div class="input-container">
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                <button type="button" class="toggle-password" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <label for="confirm_password">
                <i class="fas fa-lock"></i> Konfirmasi Password
            </label>
            <!-- Container untuk confirm password field -->
            <div class="input-container">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Masukkan password lagi" required>
                <button type="button" class="toggle-password" id="toggleConfirmPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <div class="button-group">
                <button type="submit" class="submit-btn">
                    <i class="fas fa-user-plus"></i> Daftar
                </button>
                <button type="button" class="back-btn" onclick="window.location.href='index.php'">
                    <i class="fas fa-home"></i> Beranda
                </button>
            </div>
        </form>
        
        <div class="auth-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
    </div>

    <script>
        // Script untuk toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            // Fungsi untuk toggle password visibility
            function togglePasswordVisibility(passwordInputId, toggleButtonId) {
                const toggleButton = document.getElementById(toggleButtonId);
                const passwordInput = document.getElementById(passwordInputId);
                const eyeIcon = toggleButton.querySelector('i');
                
                toggleButton.addEventListener('click', function() {
                    // Toggle tipe input
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Toggle ikon mata
                    if (type === 'text') {
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                        toggleButton.setAttribute('title', 'Sembunyikan password');
                    } else {
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                        toggleButton.setAttribute('title', 'Tampilkan password');
                    }
                });
            }
            
            // Terapkan untuk kedua password field
            togglePasswordVisibility('password', 'togglePassword');
            togglePasswordVisibility('confirm_password', 'toggleConfirmPassword');
            
            // Validasi form client-side
            document.querySelector('form').addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const username = document.getElementById('username').value;
                
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
                
                // Validasi username tidak boleh mengandung spasi
                if (username.includes(' ')) {
                    e.preventDefault();
                    alert('Username tidak boleh mengandung spasi!');
                    return;
                }
            });
            
            // Auto-focus pada input pertama
            document.getElementById('nama').focus();
        });
    </script>
</body>
</html>