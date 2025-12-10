document.addEventListener('DOMContentLoaded', function() {

    // =======================================================
    // KONFIGURASI & CEK LOGIN (WAJIB PERTAMA)
    // =======================================================
    // Sesuaikan URL ini dengan environment Anda (Laravel Serve / Laragon)
    const API_BASE_URL = 'http://127.0.0.1:8000/api';
    const token = localStorage.getItem('auth_token');

    // 1. Cek apakah user sudah login?
    if (!token) {
        window.location.href = 'login.html';
        return; // Hentikan script jika tidak ada token
    }

    // 2. Ambil data user dari LocalStorage (disimpan saat Login/Register)
    let userData = JSON.parse(localStorage.getItem('user_data') || '{}');

    // =======================================================
    // BAGIAN 1: SELEKSI ELEMEN UI
    // =======================================================
    
    // Dashboard Elements
    const userLevelEl = document.getElementById('user-level');
    const userStreakEl = document.getElementById('user-streak');
    const userInitialEl = document.getElementById('user-initial');
    const userWelcomeEl = document.getElementById('user-welcome');
    const userTotalVolumeEl = document.getElementById('user-total-volume');
    const userRankEl = document.getElementById('user-rank');

    // Profile Elements
    const profileInitialEl = document.getElementById('profile-initial');
    const profileNameEl = document.getElementById('profile-name');
    const profileRankEl = document.getElementById('profile-rank');
    const profileTotalVolumeEl = document.getElementById('profile-total-volume');
    const profileWorkoutsCompletedEl = document.getElementById('profile-workouts-completed');

    // Profile Form Inputs
    const inputAge = document.getElementById('user-age');
    const inputGender = document.getElementById('user-gender');
    const inputHeight = document.getElementById('user-height');
    const inputWeight = document.getElementById('user-weight');
    const inputActivity = document.getElementById('user-activity');

    // Notifikasi Modal Elements
    const notifModal = document.getElementById('notification-modal');
    const notifTitle = document.getElementById('notif-title');
    const notifText = document.getElementById('notif-text');
    const notifIcon = document.getElementById('notif-icon');
    const notifOkBtn = document.getElementById('notif-ok-btn');

    // Containers (Leaderboard & History)
    const questListContainer = document.getElementById('quest-list-container');
    const miniLeaderboardContainer = document.getElementById('mini-leaderboard-container');
    const leaderboardList = document.getElementById('full-leaderboard-container');
    const historyContainer = document.getElementById('history-list-container');

    // =======================================================
    // BAGIAN 2: INISIALISASI HALAMAN KHUSUS
    // =======================================================
    
    // Jika elemen Leaderboard ada di halaman ini, muat datanya
    if (leaderboardList) {
        loadLeaderboard();
    }

    // Jika elemen History ada di halaman ini, muat datanya
    if (historyContainer) {
        loadHistory();
    }

    // =======================================================
    // BAGIAN 3: FUNGSI API (FETCH DATA)
    // =======================================================

    // --- A. LOAD LEADERBOARD ---
    async function loadLeaderboard() {
        const tableBody = document.querySelector('#full-leaderboard-container');
        if (!tableBody) return;

        try {
            const response = await fetch(`${API_BASE_URL}/leaderboard`, {
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const result = await response.json();

            if (response.ok) {
                tableBody.innerHTML = ''; 
                
                result.data.forEach((user, index) => {
                    const rank = index + 1;
                    const volume = new Intl.NumberFormat('id-ID').format(user.total_volume);
                    const initial = user.username.charAt(0).toUpperCase();

                    // Style khusus untuk Top 3 (Emas, Perak, Perunggu)
                    let rankClass = 'rank-other';
                    if (rank === 1) rankClass = 'rank-1';
                    if (rank === 2) rankClass = 'rank-2';
                    if (rank === 3) rankClass = 'rank-3';

                    const row = `
                        <div class="leaderboard-item">
                            <div class="rank-badge ${rankClass}">${rank}</div>
                            <div class="user-avatar">${initial}</div>
                            <div class="user-details">
                                <span class="username">${user.username}</span>
                                <span class="user-level">Lvl ${user.level} • ${user.goal || 'Athlete'}</span>
                            </div>
                            <div class="user-score">
                                <span class="score-val">${volume}</span>
                                <span class="score-unit">kg</span>
                            </div>
                        </div>
                    `;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
            }
        } catch (error) {
            console.error('Error leaderboard:', error);
        }
    }

    // --- B. LOAD HISTORY ---
    async function loadHistory() {
        const container = document.querySelector('#history-list-container');
        if (!container) return;

        try {
            const response = await fetch(`${API_BASE_URL}/workouts/history`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.data.data.length > 0) {
                container.innerHTML = ''; // Bersihkan container

                result.data.data.forEach(session => {
                    // 1. Format Tanggal & Durasi
                    const date = new Date(session.session_date).toLocaleDateString('id-ID', {
                        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                    });
                    const duration = Math.floor(session.duration_seconds / 60);

                    // 2. LOGIKA GROUPING: Kelompokkan logs berdasarkan Nama Latihan
                    const groupedLogs = {};
                    session.logs.forEach(log => {
                        const exerciseName = log.exercise ? log.exercise.name : 'Unknown';
                        if (!groupedLogs[exerciseName]) {
                            groupedLogs[exerciseName] = [];
                        }
                        groupedLogs[exerciseName].push(log);
                    });

                    // 3. Buat HTML untuk setiap grup latihan
                    let exercisesHTML = '';
                    for (const [name, logs] of Object.entries(groupedLogs)) {
                        exercisesHTML += `
                            <div class="history-exercise-group">
                                <h4 class="exercise-name">${name}</h4>
                                <ul class="set-list">
                                    ${logs.map(log => `
                                        <li class="set-item">
                                            <span class="set-num">Set ${log.set_number}</span>
                                            <span class="set-val">${log.weight_kg} kg &times; ${log.reps} reps</span>
                                        </li>
                                    `).join('')}
                                </ul>
                            </div>
                        `;
                    }

                    // 4. Susun Kartu Utama
                    const card = `
                        <div class="history-card">
                            <div class="history-header">
                                <div class="history-date">
                                    <span class="icon">📅</span> ${date}
                                </div>
                                <div class="history-duration">
                                    ⏱ ${duration} min
                                </div>
                            </div>
                            <div class="history-body">
                                ${exercisesHTML}
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', card);
                });
            } else {
                container.innerHTML = '<div class="empty-state">Belum ada riwayat latihan.</div>';
            }
        } catch (error) {
            console.error('Error loading history:', error);
        }
    }

    // --- C. FETCH DATA USER TERBARU ---
    async function fetchLatestUserData() {
        try {
            const response = await fetch(`${API_BASE_URL}/user`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const freshData = await response.json();
                localStorage.setItem('user_data', JSON.stringify(freshData));
                userData = freshData;
                renderUserData(userData);
            } else {
                if (response.status === 401) logout();
            }
        } catch (error) {
            console.error("Gagal mengambil data user terbaru:", error);
        }
    }

    // --- D. UPDATE PROFILE ---
    async function updateUserProfile(dataToUpdate) {
        try {
            const response = await fetch(`${API_BASE_URL}/user/profile`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify(dataToUpdate)
            });

            const result = await response.json();

            if (response.ok) {
                localStorage.setItem('user_data', JSON.stringify(result.user));
                userData = result.user;
                renderUserData(userData);
                showNotification("Success!", "Profile updated successfully!", "success");
            } else {
                showNotification("Failed", result.message || "Unknown error", "error");
            }
        } catch (error) {
            console.error("Error updating profile:", error);
            showNotification("Error", "Connection error. Check backend.", "error");
        }
    }

    // =======================================================
    // BAGIAN 4: UI RENDERING & HELPER
    // =======================================================

    function renderUserData(user) {
        const initial = user.username ? user.username.charAt(0).toUpperCase() : '?';

        // Dashboard UI
        if (userLevelEl) userLevelEl.textContent = `LVL ${user.level || 1}`;
        if (userStreakEl) userStreakEl.textContent = `🔥 ${user.current_streak || 0}`;
        if (userInitialEl) userInitialEl.textContent = initial;
        if (userWelcomeEl) userWelcomeEl.textContent = `Welcome Back, ${user.username}!`;
        if (userTotalVolumeEl) userTotalVolumeEl.textContent = `${(user.total_volume || 0).toLocaleString()} kg`;

        // Rank Logic
        let rankName = "Newbie";
        if (user.rank_points > 100) rankName = "Intermediate";
        if (user.rank_points > 500) rankName = "Advanced";
        if (user.rank_points > 1000) rankName = "Elite";

        if (userRankEl) userRankEl.textContent = rankName;

        // Profile UI
        if (profileInitialEl) profileInitialEl.textContent = initial;
        if (profileNameEl) profileNameEl.textContent = user.username;
        if (profileRankEl) profileRankEl.textContent = rankName;
        if(profileTotalVolumeEl) profileTotalVolumeEl.textContent = `${(user.total_volume || 0).toLocaleString()} kg`;
    
    // PERBAIKAN: Gunakan data asli dari Accessor Laravel
        if(profileWorkoutsCompletedEl) {
        // Jika backend mengirim 'workouts_completed', pakai itu. Jika tidak, 0.
        const count = user.workouts_completed !== undefined ? user.workouts_completed : 0;
        profileWorkoutsCompletedEl.textContent = count;
        }

        // Form Inputs
        if (inputAge) inputAge.value = user.age || '';
        if (inputGender) inputGender.value = user.gender || 'male';
        if (inputHeight) inputHeight.value = user.height_cm || '';
        if (inputWeight) inputWeight.value = user.weight_kg || '';
        if (inputActivity) inputActivity.value = user.activity_level || 'sedentary';

        // Update Active Goal Button
        const goalButtons = document.querySelectorAll('.goal-btn');
        goalButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.trim().toLowerCase() === (user.goal || 'maintain')) {
                btn.classList.add('active');
            }
        });
    }

    function showNotification(title, message, type = 'success') {
        if (notifModal) {
            notifTitle.textContent = title;
            notifText.textContent = message;

            if (type === 'success') {
                notifIcon.innerHTML = '✓';
                notifIcon.className = 'icon-success';
            } else {
                notifIcon.innerHTML = '⚠';
                notifIcon.className = 'icon-error';
            }
            notifModal.classList.add('active');
        } else {
            alert(`${title}: ${message}`);
        }
    }

    function logout() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        window.location.href = 'login.html';
    }

    // Placeholders
    function loadQuestData() {
        if (questListContainer) questListContainer.innerHTML = "<p style='text-align:center; color:#888;'>No active quests yet.</p>";
    }
    async function loadMiniLeaderboard() {
        const container = document.getElementById('mini-leaderboard-container');
        if (!container) return; // Stop jika elemen tidak ada (misal bukan di Dashboard)

        try {
            const response = await fetch(`${API_BASE_URL}/leaderboard/weekly`, {
                headers: { 
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok) {
                if (result.data.length === 0) {
                    container.innerHTML = '<p style="text-align:center; color:#888; font-size:0.9rem; padding:10px;">Belum ada latihan minggu ini. Jadilah yang pertama! 💪</p>';
                    return;
                }

                container.innerHTML = ''; // Bersihkan loading

                result.data.forEach((user, index) => {
                    const rank = index + 1;
                    const volume = new Intl.NumberFormat('id-ID').format(user.weekly_volume); // Pakai volume mingguan
                    
                    // Style badge sederhana untuk Mini Leaderboard
                    let rankColor = '#444'; 
                    if (rank === 1) rankColor = '#FFD700'; // Emas
                    if (rank === 2) rankColor = '#C0C0C0'; // Perak
                    if (rank === 3) rankColor = '#CD7F32'; // Perunggu

                    const row = `
                        <div class="mini-leaderboard-item" style="display:flex; justify-content:space-between; align-items:center; padding: 10px 0; border-bottom: 1px solid #333;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span style="font-weight:bold; color:${rank === 1 ? '#000' : '#fff'}; background:${rankColor}; width:24px; height:24px; border-radius:50%; display:flex; justify-content:center; align-items:center; font-size:0.8rem;">
                                    ${rank}
                                </span>
                                <span style="font-weight:500; color:#fff;">${user.username}</span>
                            </div>
                            <span style="color:var(--primary-gold, #ffc107); font-weight:bold; font-size:0.9rem;">
                                ${volume} kg
                            </span>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', row);
                });
            }
        } catch (error) {
            console.error('Gagal ambil weekly leaderboard:', error);
            container.innerHTML = '<p style="text-align:center; color:red; font-size:0.8rem;">Gagal memuat data.</p>';
        }
    }

    // =======================================================
    // BAGIAN 5: EVENT LISTENERS
    // =======================================================

    // Notifikasi Modal Close
    if (notifOkBtn) {
        notifOkBtn.addEventListener('click', () => notifModal.classList.remove('active'));
    }

    // Navigasi SPA
    const navLinks = document.querySelectorAll('.nav-link');
    const pages = document.querySelectorAll('.page');
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            navLinks.forEach(l => l.classList.remove('active'));
            pages.forEach(p => p.classList.remove('active'));
            link.classList.add('active');
            const targetId = link.getAttribute('href').substring(1);
            const targetEl = document.getElementById(targetId);
            if (targetEl) targetEl.classList.add('active');
        });
    });

    // Logout
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                await fetch(`${API_BASE_URL}/logout`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}` }
                });
            } catch (e) { console.log("Logout offline"); }
            logout();
        });
    }

    // Goal Buttons Logic
    const goalButtons = document.querySelectorAll('.goal-btn');
    goalButtons.forEach(button => {
        button.addEventListener('click', function() {
            const selectedGoal = this.textContent.trim().toLowerCase();
            updateUserProfile({ goal: selectedGoal });
        });
    });

    // Save Profile Button
    const saveProfileBtn = document.getElementById('save-profile-btn');
    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', function() {
            const btnOriginalText = this.textContent;
            this.textContent = "Saving...";
            this.disabled = true;

            const statsData = {
                age: document.getElementById('user-age').value,
                gender: document.getElementById('user-gender').value,
                height_cm: document.getElementById('user-height').value,
                weight_kg: document.getElementById('user-weight').value,
                activity_level: document.getElementById('user-activity').value
            };

            updateUserProfile(statsData).finally(() => {
                this.textContent = btnOriginalText;
                this.disabled = false;
            });
        });
    }

    // Modal Quests
    const questsModal = document.getElementById('quests-modal');
    const openQuestsBtn = document.getElementById('quests-btn');
    const closeQuestsBtn = document.getElementById('close-quests-modal-btn');
    if (openQuestsBtn) openQuestsBtn.addEventListener('click', () => questsModal.classList.add('active'));
    if (closeQuestsBtn) closeQuestsBtn.addEventListener('click', () => questsModal.classList.remove('active'));


    // =======================================================
    // EKSEKUSI AWAL
    // =======================================================
    renderUserData(userData); // Load Cache
    fetchLatestUserData();    // Refresh Data
    loadQuestData();
    loadMiniLeaderboard();
});