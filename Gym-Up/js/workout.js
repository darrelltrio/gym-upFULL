document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // KONFIGURASI API
    // ==========================================
    // Gunakan http://127.0.0.1:8000/api jika menjalankan 'php artisan serve'
    // Gunakan http://gymup-backend.test/api jika menggunakan Laragon Virtual Host
    
    const API_BASE_URL = 'http://127.0.0.1:8000/api'; 

    // =======================================================
    // BAGIAN 1: SELEKSI ELEMEN & VARIABEL GLOBAL
    // =======================================================
    const durationTimerElement = document.querySelector('.duration-timer');
    const workoutContent = document.querySelector('.workout-content');
    const addExerciseBtn = document.querySelector('.add-exercise-btn');
    const discardBtn = document.getElementById('discard-btn');
    const finishBtn = document.getElementById('finish-btn');
    
    const confirmModal = document.getElementById('confirm-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalText = document.getElementById('modal-text');
    const confirmNoBtn = document.getElementById('confirm-no-btn');
    const completedScreen = document.getElementById('completed-screen');

    const catalogOverlay = document.getElementById('catalog-overlay');
    const closeCatalogBtn = document.getElementById('close-catalog-btn');
    const searchBar = document.getElementById('search-bar');
    const filterContainer = document.getElementById('filter-container');
    const catalogList = document.getElementById('catalog-list');
    const addSelectedBtn = document.getElementById('add-selected-btn');

    const createExerciseModal = document.getElementById('create-exercise-modal');
    const openCreateBtn = document.getElementById('open-create-modal-btn');
    const cancelCreateBtn = document.getElementById('cancel-create-btn');
    const saveExerciseBtn = document.getElementById('save-exercise-btn');

    const modalIcon = document.getElementById('modal-icon');
    const modalConfirmActions = document.getElementById('modal-confirm-actions');
    const modalAlertActions = document.getElementById('modal-alert-actions');
    const alertOkBtn = document.getElementById('alert-ok-btn');

    let startTime;
    let durationInterval;
    let restTimerInterval = null;
    let selectedExercises = []; 
    let exerciseCatalog = [];
    let isEditing = false;
    let editingExerciseId = null;

    // =======================================================
    // BAGIAN 2: FUNGSI-FUNGSI UTAMA
    // =======================================================

    function formatTime(number) { return number.toString().padStart(2, '0'); }
    function startDurationTimer() { startTime = startTime || Date.now(); if (durationInterval) clearInterval(durationInterval); durationInterval = setInterval(updateTimer, 1000); }
    function updateTimer() {
        const elapsedTime = Date.now() - startTime;
        const seconds = Math.floor((elapsedTime / 1000) % 60);
        const minutes = Math.floor((elapsedTime / (1000 * 60)) % 60);
        const hours = Math.floor((elapsedTime / (1000 * 60 * 60)) % 24);
        durationTimerElement.textContent = `${formatTime(hours)}:${formatTime(minutes)}:${formatTime(seconds)}`;
    }

    function saveWorkoutState() {
        const exerciseCards = document.querySelectorAll('.exercise-card');
        const exercisesData = [];
        exerciseCards.forEach(card => {
            const exerciseTitle = card.querySelector('.exercise-title').textContent;
            const exerciseId = card.dataset.exerciseId;
            const setRows = card.querySelectorAll('.log-row');
            const setsData = [];
            setRows.forEach(row => {
                const inputs = row.querySelectorAll('input');
                setsData.push({ kg: inputs[0].value, reps: inputs[1].value, completed: row.classList.contains('completed') });
            });
            exercisesData.push({ id: exerciseId, name: exerciseTitle, sets: setsData });
        });
        if (exercisesData.length > 0) {
            const workoutState = { startTime: startTime, exercises: exercisesData };
            sessionStorage.setItem('activeWorkout', JSON.stringify(workoutState));
        } else {
            sessionStorage.removeItem('activeWorkout');
        }
    }

    function loadWorkoutState() {
        const savedStateJSON = sessionStorage.getItem('activeWorkout');
        if (!savedStateJSON) { startDurationTimer(); return; }
        const savedState = JSON.parse(savedStateJSON);
        startTime = savedState.startTime;
        savedState.exercises.forEach(exerciseData => {
            const exercise = exerciseCatalog.find(ex => (ex.exercise_id || ex.id) == exerciseData.id);
            const exerciseName = exercise ? exercise.name : exerciseData.name || 'Unknown Exercise';
            const newCard = addExerciseCard(exerciseData.id, exerciseName, false);
            const setLogContainer = newCard.querySelector('.set-log');
            setLogContainer.querySelector('.log-row').remove();
            exerciseData.sets.forEach((setData, index) => {
                const nextSetNumber = index + 1;
                const newRow = document.createElement('div');
                newRow.classList.add('log-row');
                newRow.innerHTML = `<span class="set-number">${nextSetNumber}</span><input type="number" value="${setData.kg || 0}"><input type="number" value="${setData.reps || 0}"><button class="check-btn">✓</button><button class="delete-set-btn">×</button>`;
                setLogContainer.appendChild(newRow);
                if (setData.completed) {
                    const button = newRow.querySelector('.check-btn');
                    const inputs = newRow.querySelectorAll('input');
                    newRow.classList.add('completed');
                    button.classList.add('checked');
                    inputs.forEach(input => input.disabled = true);
                }
            });
        });
        startDurationTimer();
    }

    function startRestTimer(card) {
        if (restTimerInterval) clearInterval(restTimerInterval);
        const timerDisplay = card.querySelector('.timer-display');
        let restSeconds = 90;
        timerDisplay.textContent = '1m 30s';
        restTimerInterval = setInterval(() => {
            restSeconds--;
            const minutes = Math.floor(restSeconds / 60);
            const seconds = restSeconds % 60;
            timerDisplay.textContent = `${minutes}m ${formatTime(seconds)}s`;
            if (restSeconds <= 0) { clearInterval(restTimerInterval); timerDisplay.textContent = "Time's Up!"; }
        }, 1000);
    }
    
    function stopRestTimer(card) {
        if (restTimerInterval) clearInterval(restTimerInterval);
        const timerDisplay = card.querySelector('.timer-display');
        if (timerDisplay) timerDisplay.textContent = '';
    }
    
    function createConfetti() {
        const confettiCount = 100;
        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.classList.add('confetti');
            confetti.style.top = '50%';
            confetti.style.left = '50%';
            const x = (Math.random() - 0.5) * window.innerWidth * 1.5;
            const y = (Math.random() - 0.5) * window.innerHeight * 1.5;
            confetti.style.setProperty('--x', x + 'px');
            confetti.style.setProperty('--y', y + 'px');
            const colors = ['var(--primary-gold)', '#FFD700', '#FFFFFF'];
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            completedScreen.appendChild(confetti);
        }
    }
    
    function showConfirmModal(title, text, onConfirm) {
        modalTitle.textContent = title;
        modalText.textContent = text;
        modalIcon.innerHTML = '❓'; // Ikon Tanya
        modalIcon.className = '';   // Reset class warna

        // Tampilkan tombol Yes/No, Sembunyikan OK
        modalConfirmActions.style.display = 'flex';
        modalAlertActions.style.display = 'none';
        
        confirmModal.classList.add('active');

        // Reset Listener Tombol Yes
        const currentYesBtn = document.getElementById('confirm-yes-btn');
        const newYesBtn = currentYesBtn.cloneNode(true);
        currentYesBtn.parentNode.replaceChild(newYesBtn, currentYesBtn);
        newYesBtn.addEventListener('click', onConfirm);
    }

    // FUNGSI 2: CUSTOM ALERT (SUCCESS/ERROR) - PENGGANTI ALERT BROWSER
    function showCustomAlert(title, text, type = 'success') {
        modalTitle.textContent = title;
        modalText.textContent = text;
        
        // Tentukan Ikon & Warna
        if (type === 'success') {
            modalIcon.innerHTML = '✓'; // Centang
            modalIcon.className = 'icon-success';
        } else if (type === 'error') {
            modalIcon.innerHTML = '⚠'; // Segitiga Seru
            modalIcon.className = 'icon-error';
        } else {
            modalIcon.innerHTML = 'ℹ'; // Info
            modalIcon.className = '';
        }

        // Sembunyikan Yes/No, Tampilkan OK
        modalConfirmActions.style.display = 'none';
        modalAlertActions.style.display = 'flex';

        confirmModal.classList.add('active');

        // Logic Tombol OK (Tutup Modal)
        alertOkBtn.onclick = function() {
            confirmModal.classList.remove('active');
        };
    }

    // --- FUNGSI HELPER MODAL CREATE/EDIT (YANG SEBELUMNYA HILANG) ---
    // --- FUNGSI HELPER MODAL CREATE/EDIT (DIPERBAIKI) ---
    function openCreateModal() {
        isEditing = false;
        editingExerciseId = null;
        
        // Reset Form
        document.getElementById('new-exercise-name').value = "";
        document.getElementById('new-exercise-muscle').value = "Chest";
        document.getElementById('new-exercise-equipment').value = "Barbell";
        
        // PERBAIKAN DI SINI: Ubah 'h3' menjadi 'h4' sesuai HTML Anda
        const titleElement = document.querySelector('#create-exercise-modal h4');
        if (titleElement) titleElement.textContent = "Create New Exercise";

        // Ubah teks tombol simpan
        saveExerciseBtn.textContent = "Save Exercise";
        
        // Munculkan Modal
        createExerciseModal.classList.add('active');
    }

    function openEditModal(exercise) {
        isEditing = true;
        editingExerciseId = exercise.exercise_id || exercise.id;
        
        // Isi Form dengan data lama
        document.getElementById('new-exercise-name').value = exercise.name;
        document.getElementById('new-exercise-muscle').value = exercise.muscle_group;
        document.getElementById('new-exercise-equipment').value = exercise.equipment;
        
        // PERBAIKAN DI SINI: Ubah 'h3' menjadi 'h4'
        const titleElement = document.querySelector('#create-exercise-modal h4');
        if (titleElement) titleElement.textContent = "Edit Exercise";

        // Ubah teks tombol simpan
        saveExerciseBtn.textContent = "Update Exercise";
        
        // Munculkan Modal
        createExerciseModal.classList.add('active');
    }
    // -------------------------------------------------------------

    // FUNGSI API & KATALOG (DIPERBAIKI UNTUK DEBUGGING)
    async function fetchExercises() {
        try {
            // 1. Ambil Token dari penyimpanan browser
            const token = localStorage.getItem('auth_token');
            
            // (Opsional) Jika tidak ada token, lempar ke login
            if (!token) {
                console.warn("No token found, redirecting to login...");
                window.location.href = 'login.html';
                return;
            }

            console.log("Menghubungi API:", `${API_BASE_URL}/exercises`);
            
            // 2. Tambahkan Header Authorization
            const response = await fetch(`${API_BASE_URL}/exercises`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`, // <--- INI KUNCINYA
                    'Accept': 'application/json'
                }
            });
            
            // Cek apakah server memberikan respon OK (status 200)
            if (!response.ok) {
                // Jika 401 Unauthorized (Token Kedaluwarsa/Salah)
                if (response.status === 401) {
                    alert("Sesi habis. Silakan login kembali.");
                    localStorage.removeItem('auth_token');
                    window.location.href = 'login.html';
                    return;
                }
                
                const text = await response.text();
                throw new Error(`Server Error: ${response.status} ${text}`);
            }

            const data = await response.json();
            
            // Handle jika Laravel membungkus data dalam properti 'data' (API Resource)
            exerciseCatalog = Array.isArray(data) ? data : (data.data || []);
            
            console.log("Exercises loaded:", exerciseCatalog);
            populateCatalog();
            
        } catch (error) {
            console.error("Gagal mengambil data latihan:", error);
            const listEl = document.getElementById('catalog-list'); // Pastikan ID ini benar
            if(listEl) {
                listEl.innerHTML = `<p style='color:red; text-align:center; padding:20px;'>
                    <b>Gagal Memuat Data!</b><br>
                    <small>${error.message}</small>
                </p>`;
            }
        }
    }

    // GANTI FUNGSI PENYIMPANAN ANDA DENGAN INI:
    // GANTI FUNGSI SIMPAN ANDA DENGAN INI:
    async function handleSaveExercise() {
        const name = document.getElementById('new-exercise-name').value;
        const muscle = document.getElementById('new-exercise-muscle').value;
        const equipment = document.getElementById('new-exercise-equipment').value;
        
        if (!name) { alert("Please enter an exercise name."); return; }
        
        // 1. Ambil Token (Wajib)
        const token = localStorage.getItem('auth_token');
        if (!token) {
            alert("Session expired. Please login again.");
            window.location.href = 'login.html';
            return;
        }

        const originalText = saveExerciseBtn.textContent;
        // Ubah teks tombol sesuai status
        saveExerciseBtn.textContent = isEditing ? "Updating..." : "Saving...";
        saveExerciseBtn.disabled = true;

        try {
            // 2. Tentukan URL & Method secara Dinamis
            // Gunakan variabel API_BASE_URL jika ada, atau fallback manual
            let baseUrl = typeof API_BASE_URL !== 'undefined' ? `${API_BASE_URL}/exercises` : 'http://127.0.0.1:8000/api/exercises';
            let url = baseUrl;
            let method = 'POST'; // Default: Create

            // JIKA SEDANG EDIT: Ubah URL dan Method
            if (isEditing && editingExerciseId) {
                url = `${baseUrl}/${editingExerciseId}`; // Tambah ID di ujung URL
                method = 'PUT'; // Ubah method jadi Update
            }

            console.log(`Sending ${method} to ${url}`); // Debugging di Console

            const response = await fetch(url, {
                method: method,
                headers: { 
                    'Content-Type': 'application/json', 
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}` // <--- Token disematkan
                },
                body: JSON.stringify({ 
                    name: name, 
                    muscle_group: muscle, 
                    equipment: equipment 
                })
            });

            const result = await response.json();

            if (response.ok) {
                // Tampilkan Pesan Sukses yang Sesuai
                const msg = isEditing ? "Exercise updated successfully!" : "Exercise created successfully!";
                
                if (typeof showCustomAlert === 'function') {
                    showCustomAlert("Success!", msg, "success");
                } else {
                    alert(msg);
                }
                
                createExerciseModal.classList.remove('active');
                document.getElementById('new-exercise-name').value = ""; // Reset form
                
                // Refresh data katalog
                await fetchExercises(); 
            } else {
                // Tampilkan Error dari Backend
                const msg = result.message || "Operation failed";
                if (typeof showCustomAlert === 'function') {
                    showCustomAlert("Failed", msg, "error");
                } else {
                    alert("Error: " + msg);
                }
            }
        } catch (error) {
            console.error(error);
            alert("Connection error! Check backend.");
        } finally {
            saveExerciseBtn.textContent = originalText;
            saveExerciseBtn.disabled = false;
        }
    }

    function populateCatalog(searchTerm = '', filterGroup = 'all') {
        catalogList.innerHTML = "";
        
        // Safety check jika exerciseCatalog bukan array
        if (!Array.isArray(exerciseCatalog)) {
            console.warn("Catalog data is not an array:", exerciseCatalog);
            return;
        }

        const filteredList = exerciseCatalog.filter(ex => {
            const matchesSearch = ex.name.toLowerCase().includes(searchTerm.toLowerCase());
            const matchesGroup = (filterGroup === 'all' || (ex.muscle_group && ex.muscle_group.toLowerCase() === filterGroup.toLowerCase()));
            return matchesSearch && matchesGroup;
        });

        if (filteredList.length === 0) {
            catalogList.innerHTML = "<p style='color:#888; text-align:center; margin-top:20px;'>No exercises found.</p>";
            return;
        }

        filteredList.forEach(ex => {
            const id = ex.exercise_id || ex.id;
            const isSelected = selectedExercises.includes(id);

            catalogList.innerHTML += `
                <div class="catalog-item ${isSelected ? 'selected' : ''}" data-id="${id}" data-name="${ex.name}">
                    <div style="flex-grow:1;">
                        <h4>${ex.name}</h4>
                        <p style="color: #888; font-size: 0.85rem; margin-top: 2px;">
                        ${ex.muscle_group || 'General'} <span style="color: var(--primary-gold); margin: 0 5px;">•</span> ${ex.equipment || 'Bodyweight'}
                    </p>
                    </div>
                    <div class="item-actions" style="display:flex; gap:10px;">
                        <button class="edit-item-btn" title="Edit" style="background:none; border:none; cursor:pointer; font-size:1.2rem;">✎</button>
                        <button class="delete-item-btn" title="Delete" style="background:none; border:none; cursor:pointer; font-size:1.2rem;">🗑️</button>
                    </div>
                </div>`;
        });
    }

    function updateFabVisibility() { addSelectedBtn.style.display = selectedExercises.length > 0 ? 'flex' : 'none'; }

    function addExerciseCard(exerciseId, exerciseName, shouldSave = true) {
        const newCard = document.createElement('div');
        newCard.classList.add('exercise-card');
        newCard.dataset.exerciseId = exerciseId;
        newCard.innerHTML = `
            <div class="card-header">
                <h3 class="exercise-title">${exerciseName}</h3>
                <button class="delete-exercise-btn">×</button>
            </div>
            <div class="rest-timer-toggle"><div><span>Rest Timer</span><span class="timer-display"></span></div><label class="switch"><input type="checkbox" checked><span class="slider"></span></label></div>
            <div class="set-log">
                <div class="log-header"><span>KG</span><span>REPS</span></div>
                <div class="log-row"><span class="set-number">1</span><input type=\"number\" placeholder=\"0\"><input type=\"number\" placeholder=\"0\"><button class=\"check-btn\">✓</button><button class=\"delete-set-btn\">×</button></div>
            </div>
            <button class="add-set-btn">+ Add Set</button>
        `;
        workoutContent.appendChild(newCard);
        if (shouldSave) saveWorkoutState();
        return newCard;
    }


    // =======================================================
    // BAGIAN 4: EVENT LISTENERS
    // =======================================================

    workoutContent.addEventListener('click', function(event) {
        let stateNeedsSaving = false;
        
        if (event.target.matches('.add-set-btn')) {
            const card = event.target.closest('.exercise-card');
            const setLogContainer = card.querySelector('.set-log');
            const nextSetNumber = setLogContainer.querySelectorAll('.log-row').length + 1;
            const newRow = document.createElement('div');
            newRow.classList.add('log-row');
            newRow.innerHTML = `<span class="set-number">${nextSetNumber}</span><input type="number" placeholder="0"><input type="number" placeholder="0"><button class="check-btn">✓</button><button class="delete-set-btn">×</button>`;
            setLogContainer.appendChild(newRow);
            stateNeedsSaving = true;
        }
        
        if (event.target.matches('.check-btn')) {
            const button = event.target;
            const row = button.closest('.log-row');
            const card = button.closest('.exercise-card');
            const inputs = row.querySelectorAll('input');
            const restTimerToggle = card.querySelector('.switch input[type="checkbox"]');
            
            if (row.classList.contains('completed')) {
                row.classList.remove('completed');
                button.classList.remove('checked');
                inputs.forEach(input => input.disabled = false);
                stopRestTimer(card);
            } else {
                row.classList.add('completed');
                button.classList.add('checked');
                inputs.forEach(input => input.disabled = true);
                if (restTimerToggle.checked) { startRestTimer(card); }
            }
            stateNeedsSaving = true;
        }
        
        if (event.target.matches('.delete-set-btn')) {
            const rowToDelete = event.target.closest('.log-row');
            const card = event.target.closest('.exercise-card');
            rowToDelete.remove();
            const allRows = card.querySelectorAll('.log-row');
            allRows.forEach((row, index) => { row.querySelector('.set-number').textContent = index + 1; });
            stateNeedsSaving = true;
        }
        
        if (event.target.matches('.delete-exercise-btn')) {
            const cardToDelete = event.target.closest('.exercise-card');
            cardToDelete.remove();
            stateNeedsSaving = true;
        }
        
        if (stateNeedsSaving) saveWorkoutState();
    });
    
    addExerciseBtn.addEventListener('click', () => { 
        populateCatalog(); 
        catalogOverlay.classList.add('active'); 
        updateFabVisibility(); 
    });

    discardBtn.addEventListener('click', () => {
        showConfirmModal('Discard Workout?', 'Your progress will be lost. Are you sure?', () => {
            sessionStorage.removeItem('activeWorkout');
            window.location.href = 'index.html';
        });
    });

    finishBtn.addEventListener('click', () => {
        saveWorkoutState();
        showConfirmModal('Finish Workout?', 'Your workout log will be saved.', () => {
            sessionStorage.removeItem('activeWorkout');
            confirmModal.classList.remove('active');
            completedScreen.classList.add('active');
            if (durationInterval) clearInterval(durationInterval);
            if (restTimerInterval) clearInterval(restTimerInterval);
            createConfetti();
        });
    });

    confirmNoBtn.addEventListener('click', () => { confirmModal.classList.remove('active'); });

    closeCatalogBtn.addEventListener('click', () => catalogOverlay.classList.remove('active'));
    
    searchBar.addEventListener('input', () => {
        const searchTerm = searchBar.value;
        const activeFilter = filterContainer.querySelector('.filter-btn.active').dataset.group;
        populateCatalog(searchTerm, activeFilter);
    });
    
    filterContainer.addEventListener('click', (event) => {
        if (event.target.matches('.filter-btn')) {
            filterContainer.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            const searchTerm = searchBar.value;
            const activeFilter = event.target.dataset.group;
            populateCatalog(searchTerm, activeFilter);
        }
    });

    catalogList.addEventListener('click', async (event) => {
        // A. Cek tombol EDIT
        if (event.target.closest('.edit-item-btn')) {
            event.stopPropagation(); 
            const id = parseInt(event.target.closest('.catalog-item').dataset.id);
            const exercise = exerciseCatalog.find(ex => (ex.exercise_id || ex.id) === id);
            
            if (exercise) openEditModal(exercise); // Sekarang fungsi ini sudah ADA
            return;
        }

        // B. Cek tombol DELETE
        if (event.target.closest('.delete-item-btn')) {
            event.stopPropagation(); // Stop agar tidak memilih item
            const id = parseInt(event.target.closest('.catalog-item').dataset.id);
            
            // 1. Ambil Token (Wajib)
            const token = localStorage.getItem('auth_token');
            if (!token) {
                if (typeof showCustomAlert === 'function') {
                    showCustomAlert("Access Denied", "Please log in to delete exercises.", "error");
                } else {
                    alert("Please log in first.");
                }
                return;
            }

            // 2. Tampilkan Modal Konfirmasi
            showConfirmModal(
                'Delete Exercise?',                           
                'Are you sure? This action cannot be undone.', 
                async () => {                                 
                    try {
                        // Gunakan API_BASE_URL yang benar
                        const url = typeof API_BASE_URL !== 'undefined' ? `${API_BASE_URL}/exercises/${id}` : `http://127.0.0.1:8000/api/exercises/${id}`;

                        // 3. Panggil API Delete dengan Token
                        const response = await fetch(url, { 
                            method: 'DELETE',
                            headers: { 
                                'Accept': 'application/json',
                                'Authorization': `Bearer ${token}` // <--- INI KUNCINYA
                            }
                        });

                        // 4. Cek Hasil
                        if (response.ok) {
                            // Sukses: Hapus dari UI & Array Lokal
                            exerciseCatalog = exerciseCatalog.filter(ex => (ex.exercise_id || ex.id) !== id);
                            populateCatalog(searchBar.value);

                            if (typeof showCustomAlert === 'function') {
                                showCustomAlert("Deleted!", "The exercise has been removed.", "success");
                            } else {
                                alert("Deleted successfully.");
                            }
                        } else {
                            // Gagal (Misal: Coba hapus latihan Global)
                            const result = await response.json();
                            const msg = result.message || "Could not delete exercise.";
                            
                            if (typeof showCustomAlert === 'function') {
                                showCustomAlert("Failed", msg, "error"); // Tampilkan pesan error dari Backend
                            } else {
                                alert(msg);
                            }
                        }
                    } catch (e) { 
                        console.error(e);
                        if (typeof showCustomAlert === 'function') {
                            showCustomAlert("Connection Error", "Check your internet or server.", "error"); 
                        } else {
                            alert("Connection error.");
                        }
                    }
                }
            );
            return;
        }

        const clickedItem = event.target.closest('.catalog-item');
        if (!clickedItem) return;
        
        const exerciseId = parseInt(clickedItem.dataset.id);
        clickedItem.classList.toggle('selected');
        
        if (selectedExercises.includes(exerciseId)) { 
            selectedExercises = selectedExercises.filter(id => id !== exerciseId); 
        } else { 
            selectedExercises.push(exerciseId); 
        }
        updateFabVisibility();
    });

    addSelectedBtn.addEventListener('click', () => {
        selectedExercises.forEach(id => {
            const exercise = exerciseCatalog.find(ex => (ex.exercise_id || ex.id) == id);
            if (exercise) { 
                const exId = exercise.exercise_id || exercise.id;
                addExerciseCard(exId, exercise.name); 
            }
        });
        selectedExercises = [];
        catalogOverlay.classList.remove('active');
        document.querySelectorAll('.catalog-item.selected').forEach(el => el.classList.remove('selected'));
        saveWorkoutState();
    });

    openCreateBtn.addEventListener('click', openCreateModal); 
    cancelCreateBtn.addEventListener('click', () => createExerciseModal.classList.remove('active'));
    saveExerciseBtn.addEventListener('click', handleSaveExercise);

    // INISIALISASI
    fetchExercises().then(() => { loadWorkoutState(); });
});