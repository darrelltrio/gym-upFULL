document.addEventListener('DOMContentLoaded', () => {
    // Tampilkan nama admin dari LocalStorage
    document.getElementById('admin-name').textContent = "Halo, " + localStorage.getItem('user_name');

    // Load data tabel saat halaman pertama dibuka
    fetchGyms();

    // Event Listener untuk Form Pendaftaran Gym
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
                addGymForm.reset(); // Kosongkan form di halaman
                fetchGyms(); // Refresh tabel di bawahnya
                
                // Ganti alert browser dengan Modal Custom Emas
                showCustomAlert(
                    "PENDAFTARAN BERHASIL", 
                    `Gym "${payload.gym_name}" telah aktif. Akun Owner untuk ${payload.owner_name} siap digunakan.`,
                    false // Bukan error (Emas)
                );
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
});

// Fungsi untuk menarik data dari Backend dan merendernya ke HTML (GET)
async function fetchGyms() {
    const tbody = document.getElementById('gym-list-body');
    const token = localStorage.getItem('auth_token');

    try {
        const response = await fetch(`${API_BASE_URL}/admin/gyms`, {
            method: 'GET',
            headers: { 
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}` 
            }
        });

        const res = await response.json();

        if (response.ok) {
            tbody.innerHTML = ''; // Kosongkan loading text

            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Belum ada Gym yang terdaftar.</td></tr>';
                return;
            }

            res.data.forEach(gym => {
                // Tampilkan di HTML menggunakan Template Literal
                const row = `
                    <tr>
                        <td style="color: #FFB800; font-weight: bold;">G-${gym.id}</td>
                        <td>
                            <div style="font-weight: 600; font-size: 1.05rem;">${gym.name}</div>
                            <div style="font-size: 0.8rem; color: #888; margin-top: 4px;">${gym.address}</div>
                        </td>
                        <td>${gym.owner_name}</td>
                        <td>
                            <span class="status-badge ${gym.status === 'active' ? 'active-status' : ''}">
                                ${gym.status}
                            </span>
                        </td>
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
        console.error("Gagal mengambil data gym:", error);
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: red;">Gagal memuat data dari server.</td></tr>';
    }
}
// Variable bantuan untuk menyimpan ID yang akan dihapus
let gymIdToDelete = null;

// --- FUNGSI TRIGGER MODAL HAPUS ---
function deleteGym(id) {
    gymIdToDelete = id; // Simpan ID sementara
    openModal('deleteConfirmModal');
}

// --- LOGIKA EKSEKUSI HAPUS (Gunakan Event Listener) ---
document.getElementById('final-delete-btn').addEventListener('click', async () => {
    if (!gymIdToDelete) return;

    const btn = document.getElementById('final-delete-btn');
    const token = localStorage.getItem('auth_token');
    
    btn.textContent = "MENGHAPUS...";
    btn.disabled = true;

    try {
        const response = await fetch(`${API_BASE_URL}/admin/gyms/${gymIdToDelete}`, {
            method: 'DELETE',
            headers: { 
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json' 
            }
        });

        if (response.ok) {
            closeModal('deleteConfirmModal');
            // Tampilkan Modal Sukses (yang sudah kita buat kemarin)
            showCustomAlert("BERHASIL", "Gym telah dihapus secara permanen dari database.");
            fetchGyms(); // Refresh tabel
        } else {
            showCustomAlert("GAGAL", "Gagal menghapus data. Coba lagi nanti.", true);
        }
    } catch (error) {
        showCustomAlert("ERROR", "Kesalahan koneksi server.", true);
    } finally {
        btn.textContent = "YA, HAPUS PERMANEN";
        btn.disabled = false;
        gymIdToDelete = null; // Reset ID
    }
});

/// --- FUNGSI OPEN/CLOSE MODAL ---
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// --- CUSTOM ALERT (Ganti alert browser) ---
function showCustomAlert(title, message, isError = false) {
    document.getElementById('custom-alert-title').textContent = title;
    document.getElementById('custom-alert-message').textContent = message;
    document.getElementById('alert-icon').textContent = isError ? '✖' : '✔';
    document.getElementById('alert-icon').style.color = isError ? '#ff4757' : '#FFB800';
    openModal('alertModal');
}

// --- FUNGSI EDIT: Ambil Data & Buka Modal ---
async function editGym(gymId) {
    const token = localStorage.getItem('auth_token');
    
    // Kita butuh data email owner, jadi kita fetch detailnya dulu
    try {
        const response = await fetch(`${API_BASE_URL}/admin/gyms`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const res = await response.json();
        const gym = res.data.find(g => g.id === gymId);
        
        // Isi form modal dengan data yang ada
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

// --- HANDLE SUBMIT EDIT ---
document.getElementById('edit-gym-form').addEventListener('submit', async (e) => {
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
            showCustomAlert("SUKSES", "Data Gym dan Owner telah diperbarui.");
            fetchGyms();
        } else {
            showCustomAlert("GAGAL", "Periksa kembali input Anda (Email mungkin duplikat).", true);
        }
    } catch (err) {
        showCustomAlert("ERROR", "Kesalahan koneksi server.", true);
    }
});