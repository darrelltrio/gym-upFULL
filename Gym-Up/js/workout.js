document.addEventListener('DOMContentLoaded', function() {

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
    // Kita hapus referensi confirmYesBtn global agar tidak error
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

    let startTime;
    let durationInterval;
    let restTimerInterval = null;
    let selectedExercises = []; 
    let exerciseCatalog = [];

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
            const exercise = exerciseCatalog.find(ex => ex.id == exerciseData.id);
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
    
    // FUNGSI MODAL YANG LEBIH STABIL
    function showConfirmModal(title, text, onConfirm) {
        modalTitle.textContent = title;
        modalText.textContent = text;
        confirmModal.classList.add('active');

        // Selalu ambil elemen tombol terbaru dari DOM untuk menghindari referensi mati
        const currentYesBtn = document.getElementById('confirm-yes-btn');
        
        // Kloning tombol untuk menghapus event listener lama
        const newYesBtn = currentYesBtn.cloneNode(true);
        currentYesBtn.parentNode.replaceChild(newYesBtn, currentYesBtn);
        
        // Pasang event listener baru
        newYesBtn.addEventListener('click', onConfirm);
    }
    function showAlert(title, text, onOk = null) {
        modalTitle.textContent = title;
        modalText.textContent = text;
        confirmModal.classList.add('active');

        // Sembunyikan tombol Yes/No, Tampilkan tombol OK
        confirmActions.style.display = 'none';
        alertActions.style.display = 'flex';

        // Reset event listener tombol OK
        const newOkBtn = alertOkBtn.cloneNode(true);
        alertOkBtn.parentNode.replaceChild(newOkBtn, alertOkBtn);

        newOkBtn.addEventListener('click', () => {
            confirmModal.classList.remove('active');
            if (onOk) onOk();
        });
    }

    // FUNGSI API & KATALOG
    async function fetchExercises() {
        try {
            const response = await fetch('http://gymup-backend.test/api/exercises');
            exerciseCatalog = await response.json();
            console.log("Exercises loaded:", exerciseCatalog);
        } catch (error) {
            console.error("Gagal mengambil data latihan:", error);
            exerciseCatalog = [
                { exercise_id: 1, name: "Bench Press (Offline)", muscle_group: "Chest" },
                { exercise_id: 2, name: "Squat (Offline)", muscle_group: "Legs" }
            ];
        }
    }

    async function createNewExercise() {
        const name = document.getElementById('new-exercise-name').value;
        const muscle = document.getElementById('new-exercise-muscle').value;
        const equipment = document.getElementById('new-exercise-equipment').value;
        if (!name) { alert("Please enter an exercise name."); return; }
        
        const originalText = saveExerciseBtn.textContent;
        saveExerciseBtn.textContent = "Saving...";
        saveExerciseBtn.disabled = true;

        try {
            const response = await fetch('http://gymup-backend.test/api/exercises', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ name: name, muscle_group: muscle, equipment: equipment })
            });
            const result = await response.json();
            if (response.ok) {
                alert("Exercise created!");
                createExerciseModal.classList.remove('active');
                document.getElementById('new-exercise-name').value = "";
                await fetchExercises(); 
                populateCatalog(searchBar.value);
            } else {
                alert("Error: " + (result.message || "Failed to create exercise"));
            }
        } catch (error) {
            console.error(error);
            alert("Connection error! Check if backend is running.");
        } finally {
            saveExerciseBtn.textContent = originalText;
            saveExerciseBtn.disabled = false;
        }
    }

    function populateCatalog(searchTerm = '', filterGroup = 'all') {
        catalogList.innerHTML = "";
        const filteredList = exerciseCatalog.filter(ex => {
            const matchesSearch = ex.name.toLowerCase().includes(searchTerm.toLowerCase());
            const matchesGroup = (filterGroup === 'all' || (ex.muscle_group && ex.muscle_group.toLowerCase() === filterGroup.toLowerCase()));
            return matchesSearch && matchesGroup;
        });
        if (filteredList.length === 0) { catalogList.innerHTML = "<p style='color:#888; text-align:center; margin-top:20px;'>No exercises found.</p>"; return; }
        filteredList.forEach(ex => {
            const id = ex.exercise_id || ex.id;
            const isSelected = selectedExercises.includes(id);
            catalogList.innerHTML += `<div class="catalog-item ${isSelected ? 'selected' : ''}" data-id="${id}" data-name="${ex.name}"><h4>${ex.name}</h4><p>${ex.muscle_group || 'General'}</p></div>`;
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
    
    addExerciseBtn.addEventListener('click', () => { populateCatalog(); catalogOverlay.classList.add('active'); updateFabVisibility(); });

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
    catalogList.addEventListener('click', (event) => {
        const clickedItem = event.target.closest('.catalog-item');
        if (!clickedItem) return;
        const exerciseId = parseInt(clickedItem.dataset.id);
        clickedItem.classList.toggle('selected');
        if (selectedExercises.includes(exerciseId)) { selectedExercises = selectedExercises.filter(id => id !== exerciseId); }
        else { selectedExercises.push(exerciseId); }
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

    openCreateBtn.addEventListener('click', () => createExerciseModal.classList.add('active'));
    cancelCreateBtn.addEventListener('click', () => createExerciseModal.classList.remove('active'));
    saveExerciseBtn.addEventListener('click', createNewExercise);

    // INISIALISASI
    fetchExercises().then(() => { loadWorkoutState(); });
});