document.addEventListener('DOMContentLoaded', async function() {
    
    // --- SETUP ---
    const API_BASE_URL = 'http://127.0.0.1:8000/api'; 
    const token = localStorage.getItem('auth_token');
    const mealContainer = document.getElementById('meal-ideas-container');

    // Cek Login
    if (!token) {
        window.location.href = 'login.html';
        return;
    }

    // --- EXECUTE ---
    await loadNutritionData();
    await fetchMealIdeas();

    // --- FUNGSI 1: Load Data Nutrisi Utama ---
    async function loadNutritionData() {
        try {
            const response = await fetch(`${API_BASE_URL}/nutrition/recommendations`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error("Gagal mengambil data");
            const data = await response.json();

            // Update UI
            const goalEl = document.getElementById('nutrition-goal-status');
            const phaseEl = document.getElementById('nutrition-goal-phase');
            if(goalEl) goalEl.textContent = data.goal_status || "On Track";
            if(phaseEl) phaseEl.textContent = data.phase || "Maintenance";
            
            const calEl = document.getElementById('nutrition-calorie-value');
            if(calEl) calEl.textContent = data.calories ? data.calories.toLocaleString() : "0";
            
            if(data.macros) {
                document.getElementById('macro-protein').textContent = data.macros.protein + "g";
                document.getElementById('macro-carbs').textContent = data.macros.carbs + "g";
                document.getElementById('macro-fat').textContent = data.macros.fat + "g";
            }

        } catch (error) {
            console.error("Error:", error);
            if(document.getElementById('nutrition-goal-phase')) {
                document.getElementById('nutrition-goal-phase').textContent = "--";
            }
        }
    }

    // --- FUNGSI 2: Load Meal Ideas Dinamis (UPDATE: Ada Protein) ---
    async function fetchMealIdeas() {
        try {
            const res = await fetch(`${API_BASE_URL}/nutrition/meal-ideas`, {
                method: 'GET',
                headers: { 
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json'
                }
            });
            
            if(!res.ok) throw new Error("Failed fetch meals");
            const foods = await res.json();
            
            // Reset Container
            mealContainer.innerHTML = ""; 

            if (foods.length === 0) {
                mealContainer.innerHTML = '<p style="color:#666; text-align:center;">No meal suggestions found.</p>';
                return;
            }

            // Render Kartu Makanan
            foods.forEach(food => {
                const card = document.createElement('div');
                card.className = 'meal-card'; 
                
                // --- UPDATE DISINI: Menampilkan Kalori DAN Protein ---
                // Pastikan backend mengirim field 'protein'. Jika tidak, pakai 0.
                console.log("Data Makanan: ", food);
                const proteinVal = food.protein_g || 0; 
                const servingSize = food.serving_size_g || 0;

                card.innerHTML = `
                    <div class="meal-info">
                        <h4>${food.name}</h4>
                        <span class="meal-cat">${food.category || 'General'}</span>
                    </div>
                    <div class="meal-stats-box">
                        <div class="meal-cals">
                            <i class="fas fa-fire-alt" style="font-size:0.8em; margin-right:3px;"></i> ${food.calories}
                        </div>
                        <div class="meal-prot">
                            <i class="fas fa-drumstick-bite"></i> ${proteinVal}g
                        </div>
                        <div class="serving-size">
                            <i class="fas fa-scale-balanced"></i> ${servingSize}g
                        </div>
                    </div>
                `;
                mealContainer.appendChild(card);
            });

        } catch (e) {
            console.error(e);
            mealContainer.innerHTML = '<p style="color:#ff6b6b; text-align:center;">Error loading meals.</p>';
        }
    }
});