<?php
session_start();
// Cek apakah user sudah login
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmariDelivery - We Serve Your Needs</title>
    <link rel="icon" href="Foto/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- ===== HEADER ===== -->
    <header id="header">
        <div class="container">
            <nav>
                <a href="#home" class="logo">
                    <img src="Foto/logo.png" alt="SmariDelivery Logo" height="80px" width="80px">
                    <span>SmariDelivery</span>
                </a>
                
                <div class="nav-links" id="navLinks">
                    <a href="#home">Beranda</a>
                    <a href="#services">Layanan</a>
                    <a href="#how-it-works">Cara Kerja</a>
                    <?php if($isLoggedIn): ?>
                        <a href="buat_pesanan.php">Buat Pesanan</a>
                        <a href="riwayat_pesanan.php">Pesanan Saya</a>
                    <?php endif; ?>
                    <a href="#testimonials">Ulasan</a>
                </div>
                
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="auth-buttons" id="authButtons">
                    <?php if($isLoggedIn): ?>
                        <span style="margin-right: 15px; font-weight: 500; color: var(--dark);">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['nama'] ?? 'User'); ?>
                        </span>
                        <a href="logout.php" class="cta-button logout-btn">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="cta-button login-btn">
                            <i class="fas fa-sign-in-alt"></i> Akun Pengguna
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- ===== HERO SECTION ===== -->
<section class="hero" id="home">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text fade-in">
                <h1>We Serves <span>Your Needs</span></h1>
                <p>Butuh barang diambil dari kantin, toko, atau teman sekelas? Kami siap membantu! SmariDelivery memberikan solusi pengambilan barang cepat dan terpercaya di lingkungan sekolah Anda.</p>
                <div style="display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap;">
                    <?php if($isLoggedIn): ?>
                        <a href="buat_pesanan.php" class="cta-button">
                            <i class="fas fa-plus-circle"></i> Buat Pesanan Baru
                        </a>
                        <a href="riwayat_pesanan.php" class="cta-button" style="background: #17a2b8;">
                            <i class="fas fa-list"></i> Lihat Pesanan Saya
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="cta-button">
                            <i class="fas fa-shopping-cart"></i> Pesan Sekarang
                        </a>
                        <a href="register.php" class="cta-button" style="background: #3a56d4;">
                            <i class="fas fa-user-plus"></i> Daftar Sekarang
                        </a>
                    <?php endif; ?>
                </div>
            </div>

           <div class="hero-slider fade-in">
                <div class="slider-container">
                    <div class="slides">
                        <!-- Slide 1 -->
                        <div class="slide active">
                            <div class="slide-image">
                                <img src="Foto/adji.jpeg" alt="SmariDelivery Service 1">
                            </div>
                            <div class="slide-caption">Pengiriman Cepat & Terpercaya</div>
                        </div>
                        
                        <!-- Slide 2 -->
                        <div class="slide">
                            <div class="slide-image">
                                <img src="Foto/Orgganteng.jpg" alt="SmariDelivery Service 2">
                            </div>
                            <div class="slide-caption">Layanan Antar Barang Sekolah</div>
                        </div>
                        
                        <!-- Slide 3 -->
                        <div class="slide">
                            <div class="slide-image">
                                <img src="Foto/kcuinghitam.jpg" alt="SmariDelivery Service 3">
                            </div>
                            <div class="slide-caption">Membantu Siswa & Guru</div>
                        </div>
                        
                        <!-- Slide 4 -->
                        <div class="slide">
                            <div class="slide-image">
                                <img src="Foto/arjunpening.png" alt="SmariDelivery Service 4">
                            </div>
                            <div class="slide-caption">Solusi Pengambilan Barang</div>
                        </div>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <button class="slider-nav prev-btn">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="slider-nav next-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- ===== SERVICES SECTION ===== -->
    <section class="services" id="services">
        <div class="container">
            <div class="section-title fade-in">
                <h2>Layanan Kami</h2>
                <p>Kami menyediakan berbagai layanan pengambilan barang di sekitar lingkungan sekolah untuk memudahkan aktivitas sehari-hari Anda</p>
            </div>
            
            <div class="services-grid">
                <div class="service-card fade-in">
                    <div class="service-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3>Ambil Makanan</h3>
                    <p>Pesan makanan dari kantin atau warung sekitar sekolah, kami akan mengantarkannya ke lokasi Anda.</p>
                </div>
                
                <div class="service-card fade-in">
                    <div class="service-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <h3>
                        Antar Barang
                    </h3>
                <p> Malas ambil dan angkat barang? Kami akan mengambilnya dan mengantarkannya untuk Anda.</p>
                </div>
                
                <div class="service-card fade-in">
                    <div class="service-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h3>Belanja Kebutuhan</h3>
                    <p>Lupa membawa alat tulis atau perlengkapan sekolah? Kami bisa membelikannya untuk Anda.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== HOW IT WORKS ===== -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-title fade-in">
                <h2>Bagaimana Cara Kerjanya?</h2>
                <p>Hanya dengan 3 langkah mudah, barang yang Anda butuhkan akan segera sampai</p>
            </div>
            
            <div class="steps">
                <div class="step fade-in">
                    <div class="step-number">1</div>
                    <h3>Pesan Layanan</h3>
                    <p>Isi formulir pesanan dengan detail barang yang perlu diambil dan lokasi pengambilan dan pengantaran.</p>
                </div>
                
                <div class="step fade-in">
                    <div class="step-number">2</div>
                    <h3>Kami Ambil Barang</h3>
                    <p>Kurir kami akan mengambil dan mengantar barang sesuai pesanan Anda di lingkungan sekolah.</p>
                </div>
                
                <div class="step fade-in">
                    <div class="step-number">3</div>
                    <h3>Terima Barang</h3>
                    <p>Barang akan diantarkan ke lokasi Anda.</p>
                </div>
            </div>
            
            <!-- CTA Button di bawah steps -->
            <div style="text-align: center; margin-top: 50px;">
                <?php if($isLoggedIn): ?>
                    <a href="buat_pesanan.php" class="cta-button" style="padding: 15px 30px; font-size: 1.1rem;">
                        <i class="fas fa-shopping-cart"></i> Mulai Buat Pesanan
                    </a>
                <?php else: ?>
                    <a href="register.php" class="cta-button" style="padding: 15px 30px; font-size: 1.1rem;">
                        <i class="fas fa-user-plus"></i> Daftar untuk Memesan
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== TESTIMONIALS SECTION ===== -->
<section class="testimonials" id="testimonials">
    <div class="container">
        <div class="section-title fade-in">
            <h2>Ulasan Pengguna</h2>
            <p>Lihat apa yang dikatakan siswa dan guru tentang pengalaman mereka menggunakan SmariDelivery</p>
        </div>
        
        <div class="testimonials-grid">
            <!-- Testimonial 1 -->
            <div class="testimonial-card fade-in">
                <div class="testimonial-content">
                    <div class="testimonial-avatar">
                        <!-- Ganti src dengan foto profil Anda -->
                        <img src="Foto/orgganteng.jpg" alt="Nama Anda">
                    </div>
                    <div class="testimonial-text">
                        "Layanan yang luar biasa! Kurirnya sangat ramah dan cepat. Sekarang saya tidak perlu keluar kelas untuk ambil makanan dari kantin."
                    </div>
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="testimonial-author">
                        <h4>Athar Juna Syahputra</h4>
                        <p>Siswa Kelas X IPA 1</p>
                    </div>
                </div>
            </div>
            
            <!-- Testimonial 2 -->
            <div class="testimonial-card fade-in">
                <div class="testimonial-content">
                    <div class="testimonial-avatar">
                        <!-- Ganti src dengan foto profil Anda -->
                        <img src="Foto/orgganteng.jpg" alt="Nama Anda">
                    </div>
                    <div class="testimonial-text">
                        "Sebagai guru, saya sangat terbantu dengan layanan ini. Buku dan alat tulis yang tertinggal di ruang guru bisa diantar langsung ke kelas."
                    </div>
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <div class="testimonial-author">
                        <h4>Affan Norman Prasetyo</h4>
                        <p>Guru Matematika</p>
                    </div>
                </div>
            </div>
            
            <!-- Testimonial 3 -->
            <div class="testimonial-card fade-in">
                <div class="testimonial-content">
                    <div class="testimonial-avatar">
                        <!-- Ganti src dengan foto profil Anda -->
                        <img src="Foto/kcuinghitam.jpg" alt="Nama Anda">
                    </div>
                    <div class="testimonial-text">
                        "Pelayanan sangat memuaskan! Pesanan makanan sampai tepat waktu dan masih hangat. Sangat recommended untuk siswa yang sibuk."
                    </div>
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="testimonial-author">
                        <h4>Kucing hitam</h4>
                        <p>Siswa Kelas XII IPS</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- ===== CTA SECTION ===== -->
    <section class="cta-section">
        <div class="container">
            <h2 class="fade-in">Ada Masalah? Hubungi Kami!</h2>
            <p class="fade-in">Customer Service Siap Melayani Anda 9 to 5.</p>
            <a href="https://wa.me/6281522646080" class="cta-button light fade-in" target="_blank">
                <i class="fab fa-whatsapp"></i> Hubungi 0815-2264-6080
            </a>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3>
                        <i class="fas fa-box-open"></i>
                        SmariDelivery
                    </h3>
                    <p>Layanan pengambilan barang terpercaya di lingkungan sekolah. Membantu siswa dan guru mendapatkan apa yang mereka butuhkan dengan cepat dan mudah.</p>
                    <div class="social-icons">
                        <a href="https://www.instagram.com/nodcgamin/?utm_source=ig_web_button_share_sheet"><i class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/6281522646080" target="_blank"><i class="fab fa-whatsapp"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                        <a href="https://www.facebook.com/profile.php?id=61559194883610"><i class="fab fa-facebook-f"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3>Link Cepat</h3>
                    <ul class="footer-links">
                        <li><a href="#home"><i class="fas fa-home"></i> Beranda</a></li>
                        <li><a href="#services"><i class="fas fa-concierge-bell"></i> Layanan</a></li>
                        <li><a href="#how-it-works"><i class="fas fa-cogs"></i> Cara Kerja</a></li>
                        <?php if($isLoggedIn): ?>
                            <li><a href="buat_pesanan.php"><i class="fas fa-shopping-cart"></i> Buat Pesanan</a></li>
                            <li><a href="riwayat_pesanan.php"><i class="fas fa-list"></i> Pesanan Saya</a></li>
                        <?php else: ?>
                            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                            <li><a href="register.php"><i class="fas fa-user-plus"></i> Daftar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Kontak Kami</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> <a href="https://maps.app.goo.gl/4Ah7vpwVwDiJTvd69">Jl. PM. Noor No.1, RT.52, Sempaja Sel., Kec. Samarinda Utara, Kota Samarinda, Kalimantan Timur 75242</a></li>
                        <li><i class="fas fa-phone"></i> <a href="tel:+6281258237131">0815-2264-6080
            </  </div></a></li>
                        <li><i class="fas fa-envelope"></i> info@smari.id</li>
                        <li><i class="fas fa-clock"></i> Senin - Jumat: 09.00 - 17.00</li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <span id="currentYear"></span> SmariDelivery. Semua hak dilindungi.</p>
                <p>Layanan Pengambilan Barang Sekolah</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
    <script>
        // Set current year in footer
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('currentYear').textContent = new Date().getFullYear();
            
            // Hero Slider - Versi Sederhana
            const slides = document.querySelectorAll('.slide');
            const slidesContainer = document.querySelector('.slides');
            const totalSlides = slides.length;
            
            let currentSlide = 0;
            let slideInterval;
            const slideDuration = 3000; // 3 detik
            
            // Inisialisasi slider
            function initSlider() {
                // Tampilkan slide pertama
                goToSlide(0);
                startAutoSlide();
                
                // Setup event listeners untuk navigasi
                const prevBtn = document.querySelector('.prev-btn');
                const nextBtn = document.querySelector('.next-btn');
                
                if (prevBtn) {
                    prevBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        prevSlide();
                        resetAutoSlide();
                    });
                }
                
                if (nextBtn) {
                    nextBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        nextSlide();
                        resetAutoSlide();
                    });
                }
                
                // Pause saat hover
                slidesContainer.addEventListener('mouseenter', () => {
                    clearInterval(slideInterval);
                });
                
                slidesContainer.addEventListener('mouseleave', () => {
                    startAutoSlide();
                });
            }
            
            // Pergi ke slide tertentu
            function goToSlide(slideIndex) {
                currentSlide = slideIndex;
                
                // Reset ke 0 jika melebihi total
                if (currentSlide >= totalSlides) {
                    currentSlide = 0;
                }
                if (currentSlide < 0) {
                    currentSlide = totalSlides - 1;
                }
                
                // Geser container
                slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
                updateActiveClass();
            }
            
            // Update kelas aktif
            function updateActiveClass() {
                slides.forEach((slide, index) => {
                    slide.classList.toggle('active', index === currentSlide);
                });
            }
            
            // Slide berikutnya
            function nextSlide() {
                goToSlide(currentSlide + 1);
            }
            
            // Slide sebelumnya
            function prevSlide() {
                goToSlide(currentSlide - 1);
            }
            
            // Mulai slide otomatis
            function startAutoSlide() {
                slideInterval = setInterval(nextSlide, slideDuration);
            }
            
            // Reset slide otomatis
            function resetAutoSlide() {
                clearInterval(slideInterval);
                startAutoSlide();
            }
            
            // Inisialisasi
            initSlider();
        });
    </script>
</body>
</html>

<style>
    /* ===== GLOBAL STYLES ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #0A6A2A;
    --primary-dark: #087532;
    --secondary: #ff7e5f;
    --light: #f4f6fb;
    --dark: #222222;
    --gray: #666666;
    --light-gray: #e9ecef;
    --success: #28a745;
    --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

html {
    scroll-behavior: smooth;
    width: 100%;
    overflow-x: hidden;
}

body {
    font-family: 'Roboto', sans-serif;
    background: #ffffff;
    color: var(--dark);
    line-height: 1.6;
    overflow-x: hidden;
    width: 100%;
    min-height: 100vh;
}

h1, h2, h3, h4, h5 {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    line-height: 1.3;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* ===== HEADER & NAVIGATION ===== */
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
    text-decoration: none;
    font-weight: 700;
    font-size: 1.4rem;
    color: var(--primary);
    gap: 10px;
}

.logo img {
    height: 50px;
    width: auto;
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

.auth-buttons {
    display: flex;
    gap: 10px;
    align-items: center;
}

/* ===== BUTTON STYLES ===== */
.cta-button {
    background-color: var(--primary);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    white-space: nowrap;
}

.cta-button:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(10, 106, 42, 0.2);
}

.cta-button.light {
    background-color: white;
    color: var(--primary);
    border: 2px solid var(--primary);
}

.cta-button.light:hover {
    background-color: #f8f9fa;
}

.login-btn {
    background-color: var(--primary);
}

.logout-btn {
    background-color: #dc3545;
}

.logout-btn:hover {
    background-color: #c82333;
}

.register-btn {
    background-color: #3a56d4;
}

.register-btn:hover {
    background-color: #2a46c4;
}

/* ===== MOBILE MENU ===== */
.mobile-menu-btn {
    display: none;
    font-size: 1.5rem;
    background: none;
    border: none;
    color: var(--dark);
    cursor: pointer;
    padding: 5px;
}

/* ===== HERO SECTION ===== */
.hero {
    padding: 120px 0 60px;
    background-color: var(--light);
    margin-top: 70px;
}

.hero-content {
    display: flex;
    align-items: center;
    gap: 40px;
    flex-wrap: wrap;
}

.hero-text {
    flex: 1;
    min-width: 300px;
}

.hero-text h1 {
    font-size: 2.5rem;
    margin-bottom: 20px;
    color: var(--dark);
}

.hero-text h1 span {
    color: var(--primary);
}

.hero-text p {
    font-size: 1.1rem;
    color: var(--gray);
    margin-bottom: 30px;
    line-height: 1.6;
}

/* ===== HERO SLIDER STYLES ===== */
.hero-slider {
    flex: 1;
    min-width: 300px;
    position: relative;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--shadow);
    width: 100%;
}

.slider-container {
    position: relative;
    width: 100%;
    height: 560px;
    border-radius: 15px;
    overflow: hidden;
    user-select: none;
}

.slides {
    display: flex;
    width: 100%;
    height: 100%;
    transition: transform 0.5s ease-in-out;
}

.slide {
    flex: 0 0 100%;
    width: 100%;
    height: 100%;
    position: relative;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.slide-image {
    flex: 1;
    overflow: hidden;
    width: 100%;
    height: 100%;
}

.slide-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    border-radius: 15px 15px 0 0;
}

.slide-caption {
    background: white;
    color: var(--dark);
    padding: 15px;
    text-align: center;
    font-weight: 500;
    font-size: 1.1rem;
    border-radius: 0 0 15px 15px;
    border-top: 2px solid var(--light-gray);
}

/* Navigation buttons */
.slider-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.8);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s ease;
    font-size: 1rem;
    color: var(--dark);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.slider-nav:hover {
    background: white;
    transform: translateY(-50%) scale(1.1);
}

.slider-nav.prev-btn {
    left: 15px;
}

.slider-nav.next-btn {
    right: 15px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .slider-container {
        height: 350px;
    }
    
    .slide-caption {
        font-size: 1rem;
        padding: 12px;
    }
}

@media (max-width: 576px) {
    .slider-container {
        height: 300px;
    }
    
    .slider-nav {
        width: 35px;
        height: 35px;
    }
}

/* ===== SERVICES SECTION ===== */
.services {
    padding: 80px 0;
    background-color: white;
}

.section-title {
    text-align: center;
    margin-bottom: 50px;
}

.section-title h2 {
    font-size: 2.2rem;
    color: var(--dark);
    margin-bottom: 15px;
}

.section-title p {
    color: var(--gray);
    max-width: 700px;
    margin: 0 auto;
    font-size: 1.1rem;
}

.services-grid {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: nowrap; /* Pastikan tidak wrap ke bawah */
}

.service-card {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: var(--shadow);
    transition: var(--transition);
    text-align: center;
    border: 1px solid #f0f0f0;
    flex: 1;
    min-width: 300px; /* Lebar minimum setiap card */
    max-width: 350px; /* Lebar maksimum setiap card */
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border-color: var(--primary);
}

.service-icon {
    width: 70px;
    height: 70px;
    background-color: rgba(10, 106, 42, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.service-icon i {
    font-size: 1.8rem;
    color: var(--primary);
}

.service-card h3 {
    font-size: 1.3rem;
    margin-bottom: 15px;
    color: var(--dark);
}

.service-card p {
    color: var(--gray);
    line-height: 1.6;
}

/* Responsive design untuk layar kecil */
@media (max-width: 992px) {
    .services-grid {
        flex-wrap: wrap; /* Di layar kecil, wrap diperbolehkan */
        justify-content: center;
    }
    
    .service-card {
        min-width: calc(50% - 15px); /* 2 card per baris di tablet */
        max-width: calc(50% - 15px);
    }
}

@media (max-width: 768px) {
    .services-grid {
        flex-direction: column;
        align-items: center;
    }
    
    .service-card {
        min-width: 100%;
        max-width: 100%;
    }
}

/* ===== HOW IT WORKS ===== */
.how-it-works {
    padding: 80px 0;
    background-color:var(--light);
}

.steps {
    display: flex;
    justify-content: space-between;
    gap: 30px;
    margin-top: 40px;
    flex-wrap: wrap;
}

.step {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: var(--shadow);
    text-align: center;
    flex: 1;
    min-width: 250px;
    transition: var(--transition);
}

.step:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.step-number {
    width: 50px;
    height: 50px;
    background-color: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0 auto 20px;
}

.step h3 {
    font-size: 1.2rem;
    margin-bottom: 15px;
    color: var(--dark);
}

.step p {
    color: var(--gray);
    line-height: 1.6;
}

/* ===== TESTIMONIALS / REVIEWS SECTION ===== */
.testimonials {
    padding: 100px 0;
    background-color: white;
}

.testimonials-grid {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-top: 60px;
    align-items: stretch; /* opsional: membuat tinggi kartu seragam */
}

.testimonial-card {
    background: white;
    border-radius: 20px;
    padding: 40px 35px;
    box-shadow: var(--shadow);
    max-width: 380px;
    text-align: center;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    border: 2px solid transparent;
}

.testimonial-card:hover {
    transform: translateY(-10px);
    border-color: var(--primary);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.testimonial-card::before {
    content: '"';
    position: absolute;
    top: 20px;
    right: 30px;
    font-size: 6rem;
    color: rgba(10, 106, 42, 0.1);
    font-family: serif;
    line-height: 1;
    z-index: 1;
}

.testimonial-content {
    position: relative;
    z-index: 2;
}

.testimonial-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    margin: 0 auto 25px;
    overflow: hidden;
    border: 4px solid white;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    position: relative;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 2.2rem;
}

.testimonial-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.testimonial-text {
    font-size: 1.05rem;
    line-height: 1.7;
    color: var(--dark);
    margin-bottom: 25px;
    font-style: italic;
    min-height: 120px;
    display: flex;
    align-items: center;
}

.testimonial-rating {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-bottom: 20px;
}

.testimonial-rating i {
    color: #FFC107;
    font-size: 1.2rem;
}

.testimonial-author h4 {
    font-size: 1.25rem;
    color: var(--dark);
    margin-bottom: 5px;
    font-weight: 600;
}

.testimonial-author p {
    font-size: 0.95rem;
    color: var(--gray);
    margin-bottom: 8px;
}

.testimonial-date {
    font-size: 0.85rem;
    color: #999;
    font-style: italic;
}

/* Quote mark positioning */
.testimonial-card:nth-child(odd)::before {
    top: 15px;
    right: 25px;
}

.testimonial-card:nth-child(even)::before {
    top: 25px;
    right: 35px;
    font-size: 5.5rem;
}

/* Responsive design */
@media (max-width: 1200px) {
    .testimonials-grid {
        gap: 30px;
    }
    
    .testimonial-card {
        max-width: 350px;
    }
}

@media (max-width: 992px) {
    .testimonials-grid {
        gap: 25px;
    }
    
    .testimonial-card {
        max-width: 320px;
        padding: 35px 30px;
    }
    
    .testimonial-avatar {
        width: 90px;
        height: 90px;
        font-size: 2rem;
    }
    
    .testimonial-text {
        font-size: 1rem;
        min-height: 110px;
    }
}

@media (max-width: 768px) {
    .testimonials-grid {
        flex-direction: column;
        align-items: center;
    }
    
    .testimonial-card {
        max-width: 450px;
        width: 100%;
    }
    
    .testimonial-card::before {
        font-size: 5rem;
        right: 25px;
        top: 15px;
    }
}

@media (max-width: 576px) {
    .testimonial-card {
        padding: 30px 25px;
    }
    
    .testimonial-avatar {
        width: 80px;
        height: 80px;
        font-size: 1.8rem;
        margin-bottom: 20px;
    }
    
    .testimonial-text {
        min-height: auto;
        margin-bottom: 20px;
    }
    
    .testimonial-rating i {
        font-size: 1rem;
    }
}







/* ===== CTA SECTION ===== */
.cta-section {
    padding: 80px 0;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    text-align: center;
}

.cta-section h2 {
    font-size: 2rem;
    margin-bottom: 20px;
}

.cta-section p {
    font-size: 1.1rem;
    margin-bottom: 30px;
    max-width: 700px;
    margin: 0 auto 30px;
}

/* ===== FOOTER ===== */
footer {
    background-color: var(--dark);
    color: white;
    padding: 60px 0 30px;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 40px;
    margin-bottom: 40px;
}

.footer-col {
    flex: 1;
    min-width: 250px;
}

.footer-col h3 {
    font-size: 1.2rem;
    margin-bottom: 20px;
    color: white;
}

.footer-col p {
    color: #bbb;
    margin-bottom: 20px;
    line-height: 1.6;
    font-size: 0.95rem;
}

.footer-links {
    list-style: none;
    padding: 0;
}

.footer-links li {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.footer-links i {
    color: var(--primary);
    width: 20px;
}

.footer-links a {
    color: #bbb;
    text-decoration: none;
    transition: var(--transition);
    font-size: 0.95rem;
}

.footer-links a:hover {
    color: white;
}

.social-icons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.social-icons a {
    width: 36px;
    height: 36px;
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: var(--transition);
}

.social-icons a:hover {
    background-color: var(--primary);
    transform: translateY(-3px);
}

.copyright {
    text-align: center;
    padding-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    color: #bbb;
    font-size: 0.9rem;
}

/* ===== ANIMATIONS ===== */
.fade-in {
    opacity: 0;
    animation: fadeIn 0.8s ease forwards;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== RESPONSIVE DESIGN ===== */
@media (max-width: 992px) {
    .hero-content {
        flex-direction: column;
        text-align: center;
    }
    
    .hero-text, .hero-slider {
        width: 100%;
    }
    
    .steps {
        flex-direction: column;
    }
}

@media (max-width: 768px) {
    .mobile-menu-btn {
        display: block;
    }
    
    .nav-links {
        position: fixed;
        top: 70px;
        left: -100%;
        width: 250px;
        height: calc(100vh - 70px);
        background-color: white;
        flex-direction: column;
        padding: 30px;
        box-shadow: var(--shadow);
        transition: var(--transition);
        gap: 20px;
    }
    
    .nav-links.active {
        left: 0;
    }
    
    .auth-buttons {
        display: flex;
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1001;
    }
    
    .hero-text h1 {
        font-size: 2rem;
    }
    
    .section-title h2 {
        font-size: 1.8rem;
    }
    
    .footer-content {
        flex-direction: column;
    }
}

@media (max-width: 576px) {
    .hero {
        padding: 100px 0 40px;
    }
    
    .services, .how-it-works, .cta-section {
        padding: 60px 0;
    }
    
    .service-card, .step {
        padding: 20px;
    }
    
    .cta-section h2 {
        font-size: 1.6rem;
    }
    
    .footer-col {
        min-width: 100%;
    }
}
</style>