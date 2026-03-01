<?php
// config.php

// KONEKSI DATABASE
$host = "localhost";
$username = "root";
$password = "";
$database = "smaridelivery";

// Koneksi ke database
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("<div style='padding: 20px; background: #ffcccc; color: red; border-radius: 10px;'>
        <h3>Error: Koneksi Database Gagal</h3>
        <p>" . mysqli_connect_error() . "</p>
        <p><strong>Solusi:</strong></p>
        <ol>
            <li>Pastikan XAMPP/WAMPP MySQL berjalan</li>
            <li>Cek di phpMyAdmin apakah database 'smaridelivery' ada</li>
            <li>Restart XAMPP jika perlu</li>
        </ol>
    </div>");
}

// Set character set
mysqli_set_charset($conn, "utf8mb4");


// Set character set
mysqli_set_charset($conn, "utf8mb4");
// Proses login
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : '';    
    
    // Di file login.php, tambahkan kondisi untuk admin login
    if ($user_type == 'admin') {
        // Hardcoded admin credentials (sebaiknya disimpan di database)
        $admin_users = [
            'admin' => ['password' => 'admin123', 'name' => 'Administrator'],
            'smariadmin' => ['password' => 'Smari2024!', 'name' => 'Smari Admin']
        ];
        
        if (isset($admin_users[$username]) && $password === $admin_users[$username]['password']) {
            $_SESSION['role'] = 'admin';
            $_SESSION['admin_name'] = $admin_users[$username]['name'];
            $_SESSION['admin_username'] = $username;
            
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $error = 'Kredensial admin salah!';
        }
    } 
    // Tambahkan kondisi untuk user biasa (customer/kurir) di sini
    else if ($user_type == 'customer') {
        // Query untuk autentikasi customer dari database
        $sql = "SELECT * FROM customers WHERE username = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Verifikasi password (gunakan password_verify jika menggunakan hash)
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = 'customer';
                $_SESSION['name'] = $row['name'];
                
                header('Location: customer_dashboard.php');
                exit();
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username/email tidak ditemukan!';
        }
    }
    // Tambahkan tipe user lain sesuai kebutuhan
}
?>