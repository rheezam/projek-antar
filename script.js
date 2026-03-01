// ===== DOM ELEMENTS =====
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const navLinks = document.getElementById('navLinks');
const orderForm = document.getElementById('orderForm');
const messageContainer = document.getElementById('messageContainer');
const currentYearSpan = document.getElementById('currentYear');

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    // Set current year in footer
    if (currentYearSpan) {
        currentYearSpan.textContent = new Date().getFullYear();
    }
    
    // Set default time for delivery
    const timeField = document.getElementById('waktu_pengantaran');
    if (timeField) {
        const now = new Date();
        now.setMinutes(now.getMinutes() + 30); // Set default 30 minutes from now
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        timeField.value = `${hours}:${minutes}`;
    }
    
    // Initialize animations
    initAnimations();
});

// ===== MOBILE MENU =====
if (mobileMenuBtn && navLinks) {
    mobileMenuBtn.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        mobileMenuBtn.innerHTML = navLinks.classList.contains('active') 
            ? '<i class="fas fa-times"></i>' 
            : '<i class="fas fa-bars"></i>';
    });
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (navLinks.classList.contains('active') && 
            !navLinks.contains(e.target) && 
            !mobileMenuBtn.contains(e.target)) {
            navLinks.classList.remove('active');
            mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        }
    });
}

// ===== SMOOTH SCROLLING =====
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            // Close mobile menu if open
            if (navLinks && navLinks.classList.contains('active')) {
                navLinks.classList.remove('active');
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            }
            
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

// ===== FORM HANDLING =====
if (orderForm) {
    orderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateOrderForm()) {
            // Show loading message
            showMessage('Mengirim pesanan...', 'success');
            
            // Simulate API call
            setTimeout(() => {
                showMessage('Pesanan berhasil dikirim! Kami akan menghubungi Anda segera.', 'success');
                
                // Reset form
                orderForm.reset();
                
                // Reset time field
                const timeField = document.getElementById('waktu_pengantaran');
                if (timeField) {
                    const now = new Date();
                    now.setMinutes(now.getMinutes() + 30);
                    const hours = now.getHours().toString().padStart(2, '0');
                    const minutes = now.getMinutes().toString().padStart(2, '0');
                    timeField.value = `${hours}:${minutes}`;
                }
            }, 1500);
        }
    });
}

function validateOrderForm() {
    const nama = document.getElementById('nama_lengkap')?.value.trim();
    const telepon = document.getElementById('no_telepon')?.value.trim();
    const kelas = document.getElementById('kelas')?.value.trim();
    const layanan = document.getElementById('jenis_layanan')?.value;
    const detail = document.getElementById('detail_pesanan')?.value.trim();
    const waktu = document.getElementById('waktu_pengantaran')?.value;
    
    if (!nama || !telepon || !kelas || !layanan || !detail || !waktu) {
        showMessage('Harap lengkapi semua field yang wajib diisi!', 'error');
        return false;
    }
    
    // Validate phone number
    const phoneRegex = /^[0-9+\-\s()]{10,20}$/;
    if (!phoneRegex.test(telepon)) {
        showMessage('Nomor telepon tidak valid!', 'error');
        return false;
    }
    
    return true;
}

// ===== SHOW MESSAGE FUNCTION =====
function showMessage(text, type) {
    if (!messageContainer) return;
    
    messageContainer.innerHTML = `
        <div class="message ${type}">
            ${text}
        </div>
    `;
    
    // Add basic message styling if not in CSS
    const messageDiv = messageContainer.querySelector('.message');
    if (messageDiv) {
        messageDiv.style.padding = '15px';
        messageDiv.style.borderRadius = '8px';
        messageDiv.style.marginBottom = '20px';
        messageDiv.style.fontSize = '0.95rem';
        
        if (type === 'success') {
            messageDiv.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
            messageDiv.style.border = '1px solid #28a745';
            messageDiv.style.color = '#155724';
        } else if (type === 'error') {
            messageDiv.style.backgroundColor = 'rgba(220, 53, 69, 0.1)';
            messageDiv.style.border = '1px solid #dc3545';
            messageDiv.style.color = '#721c24';
        }
    }
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageContainer.innerHTML = '';
    }, 5000);
}

// ===== ANIMATIONS =====
function initAnimations() {
    const fadeElements = document.querySelectorAll('.fade-in');
    
    const checkFade = () => {
        fadeElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementTop < windowHeight - 100) {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }
        });
    };
    
    window.addEventListener('scroll', checkFade);
    window.addEventListener('load', checkFade);
    checkFade(); // Initial check
}

// ===== SERVICE CARD CLICK =====
document.querySelectorAll('.service-card').forEach(card => {
    card.addEventListener('click', function() {
        const serviceName = this.querySelector('h3').textContent;
        const jenisLayanan = document.getElementById('jenis_layanan');
        
        if (jenisLayanan) {
            // Find matching option
            for (let option of jenisLayanan.options) {
                if (option.textContent === serviceName) {
                    jenisLayanan.value = option.value;
                    break;
                }
            }
            
            // Scroll to form
            document.getElementById('order').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ===== FORM REAL-TIME VALIDATION =====
if (orderForm) {
    const formInputs = orderForm.querySelectorAll('input, textarea, select');
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.borderColor = '#ddd';
            }
        });
        
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#ddd';
            }
        });
    });
}