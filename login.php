<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'Username dan Password wajib diisi!';
    } else {
        // Escape input
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = $_POST['password'];
        $user_type = $_POST['user_type']; // 'user', 'driver', atau 'admin'
        
        if ($user_type == 'user') {
            // Login sebagai user biasa
            $query = "SELECT * FROM users WHERE username = ? OR email = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) == 0) {
                $error = 'Username/Email tidak ditemukan!';
            } else {
                $user = mysqli_fetch_assoc($result);
                
                // Cek password
                if (password_verify($password, $user['password'])) {
                    // Simpan data session - GUNAKAN NAMA sebagai user_id
                    $_SESSION['user_id'] = $user['nama']; // <-- PENTING: Simpan NAMA di user_id
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'] ?? 'user';
                    
                    // Redirect ke halaman beranda
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Password salah!';
                }
            }
            mysqli_stmt_close($stmt);
            
        } elseif ($user_type == 'driver') {
            // Login sebagai driver
            $query = "SELECT * FROM drivers WHERE username = ? OR email = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) == 0) {
                $error = 'Akun driver tidak ditemukan!';
            } else {
                $driver = mysqli_fetch_assoc($result);
                
                // Cek status driver
                if ($driver['status'] == 'pending') {
                    $error = 'Akun Anda masih dalam proses verifikasi oleh admin.';
                } elseif ($driver['status'] == 'rejected') {
                    $error = 'Akun Anda ditolak. Alasan: ' . ($driver['alasan_penolakan'] ?? 'Tidak disebutkan');
                } elseif ($driver['status'] == 'approved') {
                    // Cek password
                    if (password_verify($password, $driver['password'])) {
                        // Simpan data session driver
                        $_SESSION['driver_id'] = $driver['id'];
                        $_SESSION['driver_name'] = $driver['nama'];
                        $_SESSION['driver_username'] = $driver['username'];
                        $_SESSION['driver_email'] = $driver['email'];
                        $_SESSION['role'] = 'driver';
                        
                        // Redirect ke dashboard driver
                        header('Location: driver_dashboard.php');
                        exit();
                    } else {
                        $error = 'Password salah!';
                    }
                }
            }
            mysqli_stmt_close($stmt);
            
        } elseif ($user_type == 'admin') {
            // Login sebagai admin
            $query = "SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'admin'";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) == 0) {
                $error = 'Akun admin tidak ditemukan!';
            } else {
                $admin = mysqli_fetch_assoc($result);
                
                // Cek password
                if (password_verify($password, $admin['password'])) {
                    // Simpan data session admin
                    $_SESSION['admin_id'] = $admin['nama']; // Simpan nama sebagai ID
                    $_SESSION['admin_name'] = $admin['nama'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['role'] = 'admin';
                    
                    // Redirect ke dashboard admin
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    $error = 'Password salah!';
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login | SmariDelivery</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .password-container {
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
            transition: all 0.3s;
        }
        
        select {
            background-color: white;
            cursor: pointer;
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
            font-size: 16px;
            padding: 4px 8px;
        }
        
        .toggle-password:hover {
            color: #178915;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #178915;
            box-shadow: 0 0 0 3px rgba(23, 137, 21, 0.1);
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
            transition: all 0.3s;
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        button[type="submit"]:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23, 137, 21, 0.3);
        }
        
        .auth-links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .auth-link {
            color: #666;
            font-size: 14px;
        }
        
        .auth-link a {
            color: #178915;
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
            font-size: 14px;
        }
        
        .success {
            background: #e6ffe6;
            color: #178915;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #178915;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: #178915;
            background-color: white;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: 0.3s;
        }   
        
        .back-home:hover {
            color: #0a6a2a;
            transform: translateX(-3px);
        }
        
        .user-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .user-type-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .user-type-btn.active {
            border-color: #178915;
            background: rgba(23, 137, 21, 0.1);
            color: #178915;
        }
        
        .user-type-btn:hover:not(.active) {
            border-color: #178915;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">
        <i class="fas fa-arrow-left"></i> Kembali ke Beranda
    </a>
    
    <div class="auth-card">
        <div class="auth-title">
            <i class="fas fa-sign-in-alt"></i> Login SmariDelivery
        </div>
        <div class="auth-subtitle">Masuk ke akun Anda sebagai pengguna, driver, atau admin</div>
        
        <?php if(!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['registered']) && $_GET['registered'] == 1): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> Registrasi berhasil! Silakan login.
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['registered']) && $_GET['registered'] == 'driver'): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> Pendaftaran driver berhasil! Silakan tunggu verifikasi admin.
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- User Type Selector -->
            <div class="user-type-selector">
                <div class="user-type-btn active" data-type="user">
                    <i class="fas fa-user"></i> Pengguna
                </div>
                <div class="user-type-btn" data-type="driver">
                    <i class="fas fa-motorcycle"></i> Driver
                </div>
                <div class="user-type-btn" data-type="admin">
                    <i class="fas fa-cogs"></i> Admin
                </div>
            </div>
            <input type="hidden" id="user_type" name="user_type" value="user">
            
            <label for="username">
                <i class="fas fa-user"></i> Username/Email
            </label>
            <input type="text" id="username" name="username" placeholder="Masukkan username atau email" required autofocus>
            
            <label for="password">
                <i class="fas fa-lock"></i> Password
            </label>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
                <button type="button" class="toggle-password" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>
        
        <div class="auth-links">
            <div class="auth-link">
                Belum punya akun? 
                <a href="register.php" id="registerLink">Daftar sebagai Pengguna</a>
            </div>
            <div class="auth-link">
                <a href="register_driver.php" id="driverRegisterLink">Daftar sebagai Driver</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userTypeBtns = document.querySelectorAll('.user-type-btn');
            const userTypeInput = document.getElementById('user_type');
            const registerLink = document.getElementById('registerLink');
            const driverRegisterLink = document.getElementById('driverRegisterLink');
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const eyeIcon = togglePassword.querySelector('i');
            
            // User type selection
            userTypeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const type = this.getAttribute('data-type');
                    
                    // Update active button
                    userTypeBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update hidden input
                    userTypeInput.value = type;
                    
                    // Update register links based on type
                    if (type === 'driver') {
                        registerLink.style.display = 'none';
                        driverRegisterLink.style.display = 'inline';
                        registerLink.parentElement.style.display = 'none';
                        driverRegisterLink.parentElement.style.display = 'inline';
                    } else if (type === 'admin') {
                        registerLink.style.display = 'none';
                        driverRegisterLink.style.display = 'none';
                        registerLink.parentElement.style.display = 'none';
                        driverRegisterLink.parentElement.style.display = 'none';
                    } else {
                        registerLink.style.display = 'inline';
                        driverRegisterLink.style.display = 'inline';
                        registerLink.parentElement.style.display = 'inline';
                        driverRegisterLink.parentElement.style.display = 'inline';
                        registerLink.href = 'register.php';
                        registerLink.textContent = 'Daftar sebagai Pengguna';
                        driverRegisterLink.href = 'register_driver.php';
                        driverRegisterLink.textContent = 'Daftar sebagai Driver';
                    }
                });
            });
            
            // Password toggle
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                if (type === 'text') {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                } else {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                }
            });
            
            // Auto-focus
            document.getElementById('username').focus();
            
            // Initialize register links visibility
            driverRegisterLink.parentElement.style.display = 'inline';
        });
    </script>
</body>
</html>