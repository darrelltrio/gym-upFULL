document.addEventListener('DOMContentLoaded', function() {

    // =======================================================
    // KONFIGURASI & CEK LOGIN (WAJIB PERTAMA)
    // =======================================================
    const API_BASE_URL = 'http://127.0.0.1:8000/api';
    const token = localStorage.getItem('auth_token');
    
    // Cek apakah user sudah login?
    if (!token) {
        window.location.href = 'login.html';
        return; // Hentikan script
    }

    // Ambil data user dari LocalStorage (disimpan saat Login/Register)
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
    
    // Containers
    const questListContainer = document.getElementById('quest-list-container');
    const miniLeaderboardContainer = document.getElementById('mini-leaderboard-container');
    const historyListContainer = document.getElementById('history-list-container');
    const fullLeaderboardContainer = document.getElementById('full-leaderboard-container');

    // =======================================================
    // BAGIAN 2: FUNGSI UTAMA (DATA ASLI)
    // =======================================================

    // 1. Tampilkan Data User ke UI
    function renderUserData(user) {
        // Hitung Inisial (Huruf Depan)
        const initial = user.username ? user.username.charAt(0).toUpperCase() : '?';
        
        // Data Header & Dashboard
        if(userLevelEl) userLevelEl.textContent = `LVL ${user.level || 1}`;
        if(userStreakEl) userStreakEl.textContent = `🔥 ${user.current_streak || 0}`;
        if(userInitialEl) userInitialEl.textContent = initial;
        if(userWelcomeEl) userWelcomeEl.textContent = `Welcome Back, ${user.username}!`;
        if(userTotalVolumeEl) userTotalVolumeEl.textContent = `${(user.total_volume || 0).toLocaleString()} kg`;
        
        // Logika Rank Sederhana (Bisa dipercanggih nanti)
        let rankName = "Newbie";
        if (user.rank_points > 100) rankName = "Intermediate";
        if (user.rank_points > 500) rankName = "Advanced";
        if (user.rank_points > 1000) rankName = "Elite";
        
        if(userRankEl) userRankEl.textContent = rankName;

        // Data Halaman Profile
        if(profileInitialEl) profileInitialEl.textContent = initial;
        if(profileNameEl) profileNameEl.textContent = user.username;
        if(profileRankEl) profileRankEl.textContent = rankName;
        if(profileTotalVolumeEl) profileTotalVolumeEl.textContent = `${(user.total_volume || 0).toLocaleString()} kg`;
        // TODO: workouts_completed belum ada di tabel users, sementara pakai dummy atau hitung dari history nanti
        if(profileWorkoutsCompletedEl) profileWorkoutsCompletedEl.textContent = "0"; 

        // Isi Form Profile (Body Stats)
        if(inputAge) inputAge.value = user.age || '';
        if(inputGender) inputGender.value = user.gender || 'male';
        if(inputHeight) inputHeight.value = user.height_cm || '';
        if(inputWeight) inputWeight.value = user.weight_kg || '';
        if(inputActivity) inputActivity.value = user.activity_level || 'sedentary';
        
        // Update Tombol Goal
        const goalButtons = document.querySelectorAll('.goal-btn');
        goalButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.trim().toLowerCase() === (user.goal || 'maintain')) {
                btn.classList.add('active');
            }
        });
    }

    // 2. Fetch Data Terbaru dari Server (Agar Sinkron)
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
                // Update LocalStorage agar data tetap tersimpan
                localStorage.setItem('user_data', JSON.stringify(freshData));
                userData = freshData;
                renderUserData(userData);
            } else {
                // Jika token kadaluarsa (Unauthorized), tendang ke login
                if (response.status === 401) {
                    logout();
                }
            }
        } catch (error) {
            console.error("Gagal mengambil data user terbaru:", error);
        }
    }

    // 3. Fungsi Logout
    function logout() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        window.location.href = 'login.html';
    }

    // =======================================================
    // BAGIAN 3: PLACEHOLDER FITUR LAIN (SEMENTARA)
    // =======================================================
    // Karena Backend untuk Quest & Leaderboard belum siap 100%,
    // kita biarkan kosong atau pesan loading dulu.

    function loadQuestData() {
        if(questListContainer) questListContainer.innerHTML = "<p style='text-align:center; color:#888;'>No active quests yet.</p>";
    }

    function loadMiniLeaderboard() {
        if(miniLeaderboardContainer) miniLeaderboardContainer.innerHTML = "<p style='text-align:center; color:#888;'>Leaderboard coming soon.</p>";
    }

    // =======================================================
    // BAGIAN 4: EVENT LISTENERS & NAVIGASI
    // =======================================================

    // Navigasi SPA (Single Page Application) Sederhana
    const navLinks = document.querySelectorAll('.nav-link');
    const pages = document.querySelectorAll('.page');

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            navLinks.forEach(l => l.classList.remove('active'));
            pages.forEach(p => p.classList.remove('active'));

            link.classList.add('active');
            const targetId = link.getAttribute('href').substring(1);
            document.getElementById(targetId).classList.add('active');
        });
    });

    // Tombol Logout (Jika nanti ditambahkan di HTML)
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            // Panggil API Logout (Best Practice)
            try {
                await fetch(`${API_BASE_URL}/logout`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${token}` }
                });
            } catch(e) { console.log("Logout offline"); }
            logout();
        });
    }

    // Fitur Resume Workout Banner
    const resumeBanner = document.getElementById('resume-workout-banner');
    if (sessionStorage.getItem('activeWorkout') && resumeBanner) {
        resumeBanner.style.display = 'flex';
    }

    // Fitur Modal Quests
    const questsModal = document.getElementById('quests-modal');
    const openQuestsBtn = document.getElementById('quests-btn');
    const closeQuestsBtn = document.getElementById('close-quests-modal-btn');

    if(openQuestsBtn) openQuestsBtn.addEventListener('click', () => questsModal.classList.add('active'));
    if(closeQuestsBtn) closeQuestsBtn.addEventListener('click', () => questsModal.classList.remove('active'));


    // =======================================================
    // INISIALISASI
    // =======================================================
    
    // 1. Render data yang ada di LocalStorage dulu (Instan)
    renderUserData(userData);

    // 2. Fetch data terbaru dari server (Background)
    fetchLatestUserData();
    
    // 3. Load Placeholder lainnya
    loadQuestData();
    loadMiniLeaderboard();
});