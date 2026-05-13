document.addEventListener('DOMContentLoaded', () => {
    // Tampilkan nama admin dari LocalStorage
    const adminNameEl = document.getElementById('admin-name');
    if (adminNameEl) {
        adminNameEl.textContent = "Halo, " + localStorage.getItem('user_name');
    }

    // Load semua data tabel saat halaman pertama dibuka
    fetchGyms();
    fetchExercises();
    fetchFoods(); // Panggilan baru untuk load makanan

    // ==========================================
    // 1. EVENT LISTENER: PENDAFTARAN GYM BARU
    // ==========================================
    const addGymForm = document.getElementById('add-gym-form');
    if (addGymForm) {
        addGymForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submit-gym-btn');
            btn.textContent = "SEDANG MEMPROSES...";
            btn.disabled = true;

            const payload = {
                gym_name: document.getElementById('gym_name').value,
                owner_name: document.getElementById('owner_name').value,
                address: document.getElementById('address').value,
                subscription_months: parseInt(document.getElementById('subscription_months').value, 10),
                email: document.getElementById('owner_email').value,
                password: document.getElementById('owner_password').value
            };

            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch(`${API_BASE_URL}/admin/gyms`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok) {
                    addGymForm.reset(); // Kosongkan form
                    fetchGyms(); // Refresh tabel gym
                    showCustomAlert("PENDAFTARAN BERHASIL", `Gym "${payload.gym_name}" telah aktif. Akun Owner siap digunakan.`, false);
                } else {
                    showCustomAlert("GAGAL", data.message || "Gagal mendaftarkan Gym.", true);
                }
            } catch (error) {
                showCustomAlert("ERROR", "Gagal terhubung ke server. Pastikan Backend menyala.", true);
            } finally {
                btn.textContent = "DAFTARKAN GYM";
                btn.disabled = false;
            }
        });
    }

    // ==========================================
    // 2. EVENT LISTENER: TAMBAH MASTER EXERCISE
    // ==========================================
    const addExerciseForm = document.getElementById('add-exercise-form');
    if (addExerciseForm) {
        addExerciseForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submit-ex-btn');
            btn.textContent = "MEMPROSES...";
            btn.disabled = true;

            const payload = {
                name: document.getElementById('ex_name').value,
                target_muscle: document.getElementById('ex_muscle').value,
                type: document.getElementById('ex_type').value,
                base_xp: parseInt(document.getElementById('ex_xp').value, 10)
            };

            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch(`${API_BASE_URL}/admin/master/exercises`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(payload)
                });

                if (response.ok) {
                    addExerciseForm.reset();
                    fetchExercises(); // Refresh tabel exercises
                    showCustomAlert("BERHASIL", `Gerakan "${payload.name}" telah masuk ke katalog global.`);
                } else {
                    showCustomAlert("GAGAL", "Gagal menambahkan latihan.", true);
                }
            } catch (error) {
                showCustomAlert("ERROR", "Kesalahan koneksi server.", true);
            } finally {
                btn.textContent = "TAMBAH KE KATALOG";
                btn.disabled = false;
            }
        });
    }

    // ==========================================
    // 3. EVENT LISTENER: TAMBAH MASTER FOOD
    // ==========================================
    const addFoodForm = document.getElementById('add-food-form');
    if (addFoodForm) {
        addFoodForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submit-food-btn');
            btn.textContent = "MEMPROSES...";
            btn.disabled = true;

            const payload = {
                name: document.getElementById('food_name').value,
                calories: parseFloat(document.getElementById('food_calories').value),
                protein: parseFloat(document.getElementById('food_protein').value),
                carbs: parseFloat(document.getElementById('food_carbs').value),
                fats: parseFloat(document.getElementById('food_fats').value),
                serving_size: document.getElementById('food_serving').value
            };

            try {
                const token = localStorage.getItem('auth_token');
                const response = await fetch(`${API_BASE_URL}/admin/master/foods`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify(payload)
                });

                // 1. TANGKAP PESAN DARI LARAVEL
                const data = await response.json(); 

                if (response.ok) {
                    addFoodForm.reset();
                    fetchFoods(); 
                    showCustomAlert("BERHASIL", `"${payload.name}" telah ditambahkan ke database nutrisi.`);
                } else {
                    // 2. TAMPILKAN ERROR ASLI KE MODAL KITA
                    let errorMsg = data.message || "Gagal menambahkan data makanan.";
                    if (data.errors) {
                        // Jika ada error validasi (misal: "calories must be a number")
                        errorMsg = Object.values(data.errors).flat().join('\n');
                    }
                    showCustomAlert("GAGAL DARI SERVER", errorMsg, true);
                    console.log("Error Detail:", data); // Munculkan di F12 Console juga
                }
            } catch (error) {
                showCustomAlert("ERROR", "Kesalahan koneksi server.", true);
            } finally {
                btn.textContent = "TAMBAH KE DATABASE";
                btn.disabled = false;
            }
        });
    }
});

// ==========================================
// FUNGSI RENDER TABEL (GET) & DELETE UNTUK GYM
// ==========================================
async function fetchGyms() {
    const tbody = document.getElementById('gym-list-body');
    const token = localStorage.getItem('auth_token');

    try {
        const response = await fetch(`${API_BASE_URL}/admin/gyms`, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const res = await response.json();

        if (response.ok) {
            tbody.innerHTML = ''; 
            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Belum ada Gym yang terdaftar.</td></tr>';
                return;
            }
            res.data.forEach(gym => {
                const row = `
                    <tr>
                        <td style="color: #FFB800; font-weight: bold;">G-${gym.id}</td>
                        <td>
                            <div style="font-weight: 600; font-size: 1.05rem;">${gym.name}</div>
                            <div style="font-size: 0.8rem; color: #888; margin-top: 4px;">${gym.address}</div>
                        </td>
                        <td>${gym.owner_name}</td>
                        <td><span class="status-badge ${gym.status === 'active' ? 'active-status' : ''}">${gym.status}</span></td>
                        <td style="color: #ccc;">${new Date(gym.subscription_ends_at).toLocaleDateString('id-ID')}</td>
                        <td>
                            <button class="action-btn edit-btn" onclick="editGym(${gym.id}, '${gym.name}')">Edit</button>
                            <button class="action-btn delete-btn" onclick="deleteGym(${gym.id})">Hapus</button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: red;">Gagal memuat data dari server.</td></tr>';
    }
}

// Fitur Edit Gym (Modal)
async function editGym(gymId) {
    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`${API_BASE_URL}/admin/gyms`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const res = await response.json();
        const gym = res.data.find(g => g.id === gymId);
        
        document.getElementById('edit_gym_id').value = gym.id;
        document.getElementById('edit_gym_name').value = gym.name;
        document.getElementById('edit_address').value = gym.address;
        document.getElementById('edit_owner_name').value = gym.owner_name;
        document.getElementById('edit_owner_email').value = gym.users[0]?.email || '';
        
        openModal('editModal');
    } catch (e) {
        showCustomAlert("Error", "Gagal mengambil data terbaru.", true);
    }
}

// Fitur Delete Gym (Confirm Browser)
async function deleteGym(gymId) {
    if (!confirm("HATI-HATI! Yakin ingin menghapus Gym ini secara permanen?")) return;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`${API_BASE_URL}/admin/gyms/${gymId}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });

        if (response.ok) {
            fetchGyms();
            showCustomAlert("BERHASIL", "Gym telah dihapus.");
        }
    } catch (error) {
        console.error(error);
    }
}

// Submit Form Edit Gym
const editGymForm = document.getElementById('edit-gym-form');
if (editGymForm) {
    editGymForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('edit_gym_id').value;
        const token = localStorage.getItem('auth_token');
        
        const payload = {
            name: document.getElementById('edit_gym_name').value,
            address: document.getElementById('edit_address').value,
            owner_name: document.getElementById('edit_owner_name').value,
            email: document.getElementById('edit_owner_email').value,
            password: document.getElementById('edit_owner_password').value || null
        };

        try {
            const response = await fetch(`${API_BASE_URL}/admin/gyms/${id}`, {
                method: 'PUT',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                closeModal('editModal');
                showCustomAlert("SUKSES", "Data Gym telah diperbarui.");
                fetchGyms();
            } else {
                showCustomAlert("GAGAL", "Periksa kembali input Anda.", true);
            }
        } catch (err) {
            showCustomAlert("ERROR", "Kesalahan koneksi server.", true);
        }
    });
}

// ==========================================
// FUNGSI RENDER TABEL (GET) & DELETE UNTUK EXERCISES
// ==========================================
async function fetchExercises() {
    const tbody = document.getElementById('exercise-list-body');
    const token = localStorage.getItem('auth_token');

    try {
        const response = await fetch(`${API_BASE_URL}/admin/master/exercises`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        const res = await response.json();

        if (response.ok) {
            tbody.innerHTML = '';
            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #777;">Kamus latihan masih kosong.</td></tr>';
                return;
            }

            res.data.forEach(ex => {
                const row = `
                    <tr>
                        <td style="font-weight: 600; color: white;">${ex.name}</td>
                        <td style="text-transform: capitalize; color: #aaa;">${ex.target_muscle.replace('_', ' ')}</td>
                        <td><span class="status-badge" style="background: #333; color: var(--accent-gold);">${ex.type.toUpperCase()}</span></td>
                        <td style="color: #2ecc71; font-weight: bold;">+${ex.base_xp} XP</td>
                        <td>
                            <button class="action-btn delete-btn" onclick="deleteExercise(${ex.id})">Hapus</button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Gagal memuat data.</td></tr>';
    }
}

async function deleteExercise(id) {
    if (!confirm("Hapus latihan ini dari Katalog Global?")) return;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`${API_BASE_URL}/admin/master/exercises/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });

        if (response.ok) {
            fetchExercises();
        }
    } catch (e) {
        console.error(e);
    }
}

// ==========================================
// FUNGSI RENDER TABEL (GET) & DELETE UNTUK FOODS
// ==========================================

//FOODS BERMASALAH DENGAN DATABASE, ENTAH KENAPA GAADA TABEL NYA
async function fetchFoods() {
    const tbody = document.getElementById('food-list-body');
    const token = localStorage.getItem('auth_token');

    try {
        const response = await fetch(`${API_BASE_URL}/admin/master/foods`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        const res = await response.json();

        if (response.ok) {
            tbody.innerHTML = '';
            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #777;">Database nutrisi masih kosong.</td></tr>';
                return;
            }

            res.data.forEach(food => {
                const row = `
                    <tr>
                        <td style="font-weight: 600;">${food.name}</td>
                        <td style="color: var(--accent-gold);">${food.calories} kcal</td>
                        <td style="font-size: 0.85rem; color: #aaa;">
                            <span style="color: #2ecc71;">P: ${food.protein}g</span> | 
                            <span style="color: #3498db;">C: ${food.carbs}g</span> | 
                            <span style="color: #e67e22;">L: ${food.fats}g</span>
                        </td>
                        <td>${food.serving_size}</td>
                        <td>
                            <button class="action-btn delete-btn" onclick="deleteFood(${food.id})">Hapus</button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Gagal memuat data.</td></tr>';
    }
}

async function deleteFood(id) {
    if (!confirm("Hapus makanan ini dari Database Global?")) return;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`${API_BASE_URL}/admin/master/foods/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });

        if (response.ok) {
            fetchFoods();
        }
    } catch (e) {
        console.error(e);
    }
}

// ==========================================
// SISTEM MODAL & ALERT GLOBAL
// ==========================================
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function showCustomAlert(title, message, isError = false) {
    document.getElementById('custom-alert-title').textContent = title;
    document.getElementById('custom-alert-message').textContent = message;
    
    const alertIcon = document.getElementById('alert-icon');
    if (alertIcon) {
        alertIcon.textContent = isError ? '✖' : '✔';
        alertIcon.style.color = isError ? '#ff4757' : '#FFB800';
    }
    
    openModal('alertModal');
}