// Konfigurasi API (Sesuaikan dengan port backend Anda)
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
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok) {
                    // Simpan Token & Data User
                    localStorage.setItem('auth_token', data.access_token);
                    localStorage.setItem('user_data', JSON.stringify(data.user));
                    
                    // Redirect ke Dashboard (index.html atau workout.html)
                    window.location.href = 'index.html'; 
                } else {
                    showAlert("Login Failed", data.message || "Invalid credentials");
                }
            } catch (error) {
                console.error(error);
                showAlert("Error", "Cannot connect to server.");
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
    }

    // --- 2. LOGIC REGISTER ---
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('register-btn');
            const originalText = btn.textContent;
            btn.textContent = "Creating Account...";
            btn.disabled = true;

            // Ambil semua data form
            const formData = {
                username: document.getElementById('username').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                gender: document.getElementById('gender').value,
                age: parseInt(document.getElementById('age').value),
                height_cm: parseInt(document.getElementById('height').value),
                weight_kg: parseFloat(document.getElementById('weight').value),
                goal: document.getElementById('goal').value,
                activity_level: document.getElementById('activity_level').value
            };

            try {
                const response = await fetch(`${API_BASE_URL}/register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (response.ok) {
                    // Auto Login setelah Register
                    localStorage.setItem('auth_token', data.access_token);
                    localStorage.setItem('user_data', JSON.stringify(data.user));
                    
                    // Redirect
                    window.location.href = 'index.html'; 
                } else {
                    // Tampilkan error validasi jika ada
                    let msg = data.message || "Registration failed";
                    if (data.errors) {
                        msg = Object.values(data.errors).flat().join('\n');
                    }
                    showAlert("Registration Failed", msg);
                }
            } catch (error) {
                console.error(error);
                showAlert("Error", "Cannot connect to server.");
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
    }
});

// Helper Alert
function showAlert(title, message) {
    document.getElementById('alert-title').textContent = title;
    document.getElementById('alert-message').innerText = message; // innerText agar \n terbaca baris baru
    document.getElementById('custom-alert').style.display = 'flex';
}