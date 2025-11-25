document.addEventListener('DOMContentLoaded', function() {
    // Ambil goal yang tersimpan, atau gunakan 'maintain' sebagai default
    const currentUserGoal = localStorage.getItem('currentUserGoal') || 'maintain';

    // Data dummy untuk setiap goal
    const nutritionData = {
        bulk: {
            status: "You're on", phase: "BULKING PHASE", calories: "3,000",
            protein: "180g", proteinPercent: "75%",
            carbs: "300g", carbsPercent: "80%",
            fat: "80g", fatPercent: "70%"
        },
        cut: {
            status: "You're on", phase: "CUTTING PHASE", calories: "2,200",
            protein: "190g", proteinPercent: "85%",
            carbs: "150g", carbsPercent: "50%",
            fat: "70g", fatPercent: "65%"
        },
        maintain: {
            status: "You're on", phase: "MAINTENANCE PHASE", calories: "2,600",
            protein: "160g", proteinPercent: "70%",
            carbs: "250g", carbsPercent: "70%",
            fat: "75g", fatPercent: "68%"
        }
    };

    // Pilih data yang sesuai dengan goal
    const currentData = nutritionData[currentUserGoal];

    // Update elemen-elemen di halaman
    document.getElementById('nutrition-goal-status').textContent = currentData.status;
    document.getElementById('nutrition-goal-phase').textContent = currentData.phase;
    document.getElementById('nutrition-calorie-value').textContent = currentData.calories;
    
    document.getElementById('macro-protein').textContent = currentData.protein;
    document.getElementById('macro-carbs').textContent = currentData.carbs;
    document.getElementById('macro-fat').textContent = currentData.fat;
    
    document.getElementById('macro-protein-bar').style.width = currentData.proteinPercent;
    document.getElementById('macro-carbs-bar').style.width = currentData.carbsPercent;
    document.getElementById('macro-fat-bar').style.width = currentData.fatPercent;
});