document.addEventListener('DOMContentLoaded', function() {

    // =======================================================
    // BAGIAN 1: DATA DUMMY (NANTI DIGANTI API)
    // =======================================================

    const dummyData = {
        user: {
            name: "Darrell",
            initial: "D",
            level: 50,
            streak: 52,
            totalVolume: "125,400 kg",
            rank: "Advanced",
            workoutsCompleted: 52
        },
        quests: [
            { title: "Complete 3 workouts this week", progress: "66%", text: "2 / 3" },
            { title: "Lift 10,000 kg total volume", progress: "54%", text: "5.4k / 10k kg" },
            { title: "Perform 5 sets of Squats", progress: "0%", text: "0 / 5" }
        ],
        miniLeaderboard: [
            { rank: 1, name: "Alex", volume: "8,500 kg" },
            { rank: 2, name: "Sarah", volume: "8,100 kg" },
            { rank: 3, name: "Darrell", volume: "7,800 kg" }
        ],
        fullLeaderboard: [
            { rank: 1, name: "Alex", volume: "152,300 kg", isCurrentUser: false },
            { rank: 2, name: "Sarah", volume: "141,800 kg", isCurrentUser: false },
            { rank: 3, name: "Darrell", volume: "125,400 kg", isCurrentUser: true },
            { rank: 4, name: "Mike", volume: "110,150 kg", isCurrentUser: false },
            { rank: 5, name: "Jenny", volume: "98,000 kg", isCurrentUser: false }
        ],
        history: [
            { date: "October 8, 2025", duration: "01:15:30", exercises: [
                { name: "Bench Press", sets: ["Set 1: 30 kg x 8 reps", "Set 2: 40 kg x 6 reps", "Set 3: 40 kg x 6 reps"] },
                { name: "Overhead Press", sets: ["Set 1: 20 kg x 10 reps", "Set 2: 20 kg x 10 reps"] }
            ]},
            { date: "October 6, 2025", duration: "01:30:10", exercises: [
                { name: "Squat", sets: ["Set 1: 60 kg x 8 reps", "Set 2: 80 kg x 5 reps"] },
                { name: "Deadlift", sets: ["Set 1: 100 kg x 5 reps"] }
            ]}
        ]
    };

    // =======================================================
    // BAGIAN 2: SELEKSI ELEMEN KONTAINER
    // =======================================================
    
    const questListContainer = document.getElementById('quest-list-container');
    const miniLeaderboardContainer = document.getElementById('mini-leaderboard-container');
    const historyListContainer = document.getElementById('history-list-container');
    const fullLeaderboardContainer = document.getElementById('full-leaderboard-container');

    // =======================================================
    // BAGIAN 3: FUNGSI PEMUAT DATA DINAMIS
    // =======================================================

    function loadDashboardData() {
        const user = dummyData.user;
        document.getElementById('user-level').textContent = `LVL ${user.level}`;
        document.getElementById('user-streak').textContent = `🔥 ${user.streak}`;
        document.getElementById('user-initial').textContent = user.initial;
        document.getElementById('user-welcome').textContent = `Welcome Back, ${user.name}!`;
        document.getElementById('user-total-volume').textContent = user.totalVolume;
        document.getElementById('user-rank').textContent = user.rank;

        // Muat juga data untuk halaman profil
        document.getElementById('profile-initial').textContent = user.initial;
        document.getElementById('profile-name').textContent = user.name;
        document.getElementById('profile-rank').textContent = user.rank;
        document.getElementById('profile-total-volume').textContent = user.totalVolume;
        document.getElementById('profile-workouts-completed').textContent = user.workoutsCompleted;
    }

    function loadQuestData() {
        questListContainer.innerHTML = ""; // Kosongkan placeholder
        dummyData.quests.forEach(quest => {
            questListContainer.innerHTML += `
                <div class="quest-item">
                    <p class="quest-title">${quest.title}</p>
                    <div class="quest-progress">
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fg" style="width: ${quest.progress};"></div>
                        </div>
                        <span class="quest-progress-text">${quest.text}</span>
                    </div>
                </div>
            `;
        });
    }

    function loadMiniLeaderboard() {
        miniLeaderboardContainer.innerHTML = ""; // Kosongkan placeholder
        dummyData.miniLeaderboard.forEach(item => {
            miniLeaderboardContainer.innerHTML += `<li>${item.rank}. ${item.name} - ${item.volume}</li>`;
        });
    }

    function loadHistoryData() {
        historyListContainer.innerHTML = ""; // Kosongkan placeholder
        dummyData.history.forEach(session => {
            let exercisesHtml = "";
            session.exercises.forEach(ex => {
                let setsHtml = ex.sets.map(set => `<li>${set}</li>`).join("");
                exercisesHtml += `<div class="exercise-log"><h4>${ex.name}</h4><ul>${setsHtml}</ul></div>`;
            });
            historyListContainer.innerHTML += `
                <div class="history-card">
                    <div class="history-card-header">
                        <span class="date">${session.date}</span>
                        <span class="duration">Duration: ${session.duration}</span>
                    </div>
                    <div class="history-card-body">${exercisesHtml}</div>
                </div>
            `;
        });
    }

    function loadFullLeaderboard() {
        fullLeaderboardContainer.innerHTML = ""; // Kosongkan placeholder
        dummyData.fullLeaderboard.forEach(item => {
            const isCurrentUserClass = item.isCurrentUser ? 'current-user' : '';
            fullLeaderboardContainer.innerHTML += `
                <li class="leaderboard-item ${isCurrentUserClass}">
                    <span class="rank">${item.rank}.</span>
                    <span class="name">${item.name}</span>
                    <span class="volume">${item.volume}</span>
                </li>
            `;
        });
    }


    // =======================================================
    // BAGIAN 4: LOGIKA APLIKASI
    // =======================================================

    // --- LOGIKA UNTUK NAVIGASI SPA ---
    const navLinks = document.querySelectorAll('.nav-link');
    const pages = document.querySelectorAll('.page');

    function handleNavClick(event) {
        event.preventDefault();
        navLinks.forEach(link => link.classList.remove('active'));
        pages.forEach(page => page.classList.remove('active'));

        const clickedLink = event.currentTarget;
        clickedLink.classList.add('active');

        const targetId = clickedLink.getAttribute('href').substring(1);
        const targetPage = document.getElementById(targetId);

        if (targetPage) {
            targetPage.classList.add('active');
            
            // Periksa halaman apa yang di-klik dan muat datanya
            if (targetId === 'history') {
                loadHistoryData();
            } else if (targetId === 'leaderboard') {
                loadFullLeaderboard();
            }
        }
    }

    navLinks.forEach(link => {
        link.addEventListener('click', handleNavClick);
    });

    // --- LOGIKA UNTUK TOMBOL GOAL DI HALAMAN PROFIL ---
    const goalButtons = document.querySelectorAll('.goal-btn');

    function syncGoalButtons() {
        const savedGoal = localStorage.getItem('currentUserGoal') || 'bulk'; 
        goalButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.trim().toLowerCase() === savedGoal) {
                btn.classList.add('active');
            }
        });
    }

    goalButtons.forEach(button => {
        button.addEventListener('click', function() {
            const selectedGoal = this.textContent.trim().toLowerCase();
            localStorage.setItem('currentUserGoal', selectedGoal);
            syncGoalButtons();
            console.log("Goal saved:", selectedGoal);
        });
    });

    // --- LOGIKA UNTUK RESUME BANNER ---
    const resumeBanner = document.getElementById('resume-workout-banner');
    if (sessionStorage.getItem('activeWorkout')) {
        if(resumeBanner) {
            resumeBanner.style.display = 'flex';
        }
    }
    
    // --- LOGIKA MODAL QUESTS ---
    const questsModal = document.getElementById('quests-modal');
    const openQuestsBtn = document.getElementById('quests-btn');
    const closeQuestsBtn = document.getElementById('close-quests-modal-btn');

    openQuestsBtn.addEventListener('click', function() {
        questsModal.classList.add('active');
    });

    closeQuestsBtn.addEventListener('click', function() {
        questsModal.classList.remove('active');
    });

    // --- LOGIKA SIMPAN PROFILE (DATA FISIK) ---
    const saveProfileBtn = document.getElementById('save-profile-btn');
    const notifModal = document.getElementById('notification-modal');
    const notifTitle = document.getElementById('notif-title');
    const notifText = document.getElementById('notif-text');
    const notifOkBtn = document.getElementById('notif-ok-btn');

    // Fungsi Helper untuk menampilkan modal
    function showNotification(title, message) {
        if(notifModal) {
            notifTitle.textContent = title;
            notifText.textContent = message;
            notifModal.classList.add('active');
        } else {
            alert(message); // Fallback jika modal tidak ada
        }
    }

    // Event listener tombol OK di modal
    if (notifOkBtn) {
        notifOkBtn.addEventListener('click', function() {
            notifModal.classList.remove('active');
        });
    }
    
    if(saveProfileBtn) {
        saveProfileBtn.addEventListener('click', function() {
            const profileData = {
                age: document.getElementById('user-age').value,
                gender: document.getElementById('user-gender').value,
                height: document.getElementById('user-height').value,
                weight: document.getElementById('user-weight').value,
                activity: document.getElementById('user-activity').value
            };
            
            localStorage.setItem('userProfileData', JSON.stringify(profileData));
            
            // GANTI ALERT DENGAN MODAL BARU
            showNotification('Profile Saved!', 'Your body stats have been updated. Nutrition recommendations will be recalculated.');
        });

        const savedProfile = JSON.parse(localStorage.getItem('userProfileData'));
        if (savedProfile) {
            if(document.getElementById('user-age')) document.getElementById('user-age').value = savedProfile.age;
            if(document.getElementById('user-gender')) document.getElementById('user-gender').value = savedProfile.gender;
            if(document.getElementById('user-height')) document.getElementById('user-height').value = savedProfile.height;
            if(document.getElementById('user-weight')) document.getElementById('user-weight').value = savedProfile.weight;
            if(document.getElementById('user-activity')) document.getElementById('user-activity').value = savedProfile.activity;
        }
    }

    // =======================================================
    // PEMUATAN DATA AWAL
    // =======================================================
    loadDashboardData();
    loadQuestData();
    loadMiniLeaderboard();
    syncGoalButtons();
});