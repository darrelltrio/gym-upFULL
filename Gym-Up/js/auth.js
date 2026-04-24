// Konfigurasi API (Sesuaikan dengan port backend Laravel Anda)
const API_BASE_URL = 'http://127.0.0.1:8000/api';

document.addEventListener('DOMContentLoaded', () => {
    
    // --- 1. LOGIC LOGIN ---
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('login-btn');
            const originalText = btn.textContent;
            btn.textContent = "Logging in...";
            btn.disabled = true;

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                const response = await fetch(`${API_BASE_URL}/login`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json' 
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok) {
                    // 1. Simpan Token & Data Penting ke LocalStorage
                    localStorage.setItem('auth_token', data.access_token);
                    localStorage.setItem('user_role', data.role);
                    localStorage.setItem('user_name', data.user.name);
                    
                    // Simpan gym_id jika ada (Member/Owner)
                    if(data.user.gym_id) {
                        localStorage.setItem('gym_id', data.user.gym_id);
                    }
                    
                    // 2. Redirect Berdasarkan Role (Traffic Controller)
                    if (data.role === 'member') {
                        window.location.href = 'index.html'; // Masuk ke SPA Member
                    } else if (data.role === 'gym_owner') {
                        window.location.href = 'owner-dashboard.html';
                    } else if (data.role === 'super_admin') {
                        window.location.href = 'admin-dashboard.html';
                    }
                } else {
                    showAlert("Login Failed", data.message || "Invalid credentials");
                }
            } catch (error) {
                console.error(error);
                showAlert("Error", "Cannot connect to server. Is Laravel running?");
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
    }

    // --- 2. LOGIC REGISTER (DISABLED FOR B2B2C) ---
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            showAlert("Registration Disabled", "Dalam sistem Gym-Up B2B2C, pendaftaran member dilakukan langsung di meja kasir Gym masing-masing.");
        });
    }
});

// --- HELPER FUNCTIONS (Bisa dipanggil di file HTML/JS lain) ---

// Helper Alert Modal
function showAlert(title, message) {
    const alertTitle = document.getElementById('alert-title');
    const alertMessage = document.getElementById('alert-message');
    const customAlert = document.getElementById('custom-alert');
    
    if (alertTitle && alertMessage && customAlert) {
        alertTitle.textContent = title;
        alertMessage.innerText = message; 
        customAlert.style.display = 'flex';
    } else {
        // Fallback jika UI alert belum dirender
        alert(`${title}\n${message}`);
    }
}

// Helper: Penjaga Halaman (Panggil ini di script paling atas index.html dll)
function checkAuth(requiredRole = null) {
    const token = localStorage.getItem('auth_token');
    const role = localStorage.getItem('user_role');

    if (!token) {
        window.location.href = 'login.html';
        return;
    }

    if (requiredRole && role !== requiredRole) {
        alert("Akses Ditolak! Anda tidak memiliki izin untuk halaman ini.");
        logoutUser();
    }
}

// Helper: Logout
function logoutUser() {
    localStorage.clear();
    window.location.href = 'login.html';
}