// ==========================================================
// 1. FIREBASE IMPORTS
// ==========================================================
import { db } from "./firebase_config.js";
import { collection, getDocs } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";

// Global Chart Instances (para maiwasan ang render overlap)
let barChartInstance = null;
let pieChartInstance = null;

// ==========================================================
// 2. FETCH DASHBOARD DATA FROM FIRESTORE
// ==========================================================
async function loadDashboardData() {
    try {
        const casesRef = collection(db, "cases");
        const querySnapshot = await getDocs(casesRef);

        let totalCases = 0;
        let inProgress = 0;
        let resolved = 0;
        let upcoming = 0;

        // Container para sa Cases per Month (Jan - Dec)
        const monthCounts = {
            Jan: 0, Feb: 0, Mar: 0, Apr: 0, May: 0, Jun: 0,
            Jul: 0, Aug: 0, Sep: 0, Oct: 0, Nov: 0, Dec: 0
        };

        querySnapshot.forEach((doc) => {
            const data = doc.data();
            totalCases++;

            // A. Count By Status
            const status = data.status ? data.status.trim() : '';

            if (status === 'In Progress') {
                inProgress++;
            } else if (status === 'Resolved' || status === 'Closed') {
                resolved++;
            } else if (status === 'Open' || status === 'Pending') {
                upcoming++;
            }

            // B. Aggregate Cases per Month (base sa created_at field)
            if (data.created_at) {
                let dateObj;
                // Kung Firebase Timestamp
                if (data.created_at.toDate) {
                    dateObj = data.created_at.toDate();
                } else {
                    dateObj = new Date(data.created_at);
                }

                if (!isNaN(dateObj)) {
                    const monthName = dateObj.toLocaleString('en-US', { month: 'short' });
                    if (monthCounts[monthName] !== undefined) {
                        monthCounts[monthName]++;
                    }
                }
            }
        });

        // ==========================================================
        // 3. UPDATE DOM UI STAT CARDS
        // ==========================================================
        document.getElementById('stat-total').innerText = totalCases;
        document.getElementById('stat-progress').innerText = inProgress;
        document.getElementById('stat-resolved').innerText = resolved;
        document.getElementById('stat-upcoming').innerText = upcoming;

        // ==========================================================
        // 4. RENDER CHARTS
        // ==========================================================
        const months = Object.keys(monthCounts);
        const counts = Object.values(monthCounts);

        renderBarChart(months, counts);
        renderPieChart(inProgress, resolved, upcoming);

    } catch (error) {
        console.error("Error loading dashboard statistics:", error);
    }
}

// ==========================================================
// 5. CHART RENDERING FUNCTIONS
// ==========================================================
function renderBarChart(labels, dataValues) {
    const ctx = document.getElementById('myChart').getContext('2d');
    
    if (barChartInstance) barChartInstance.destroy();

    barChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cases',
                data: dataValues,
                backgroundColor: '#90caf9',
                borderColor: '#42a5f5',
                borderWidth: 1
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
}

function renderPieChart(inProgress, resolved, upcoming) {
    const ctx = document.getElementById('pieChart').getContext('2d');

    if (pieChartInstance) pieChartInstance.destroy();

    pieChartInstance = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['In Progress', 'Resolved', 'Open/Pending'],
            datasets: [{
                data: [inProgress, resolved, upcoming],
                backgroundColor: ['#ffcc80', '#a5d6a7', '#90caf9']
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false 
        }
    });
}

// Patakbuhin kapag ready na ang DOM
document.addEventListener('DOMContentLoaded', loadDashboardData);