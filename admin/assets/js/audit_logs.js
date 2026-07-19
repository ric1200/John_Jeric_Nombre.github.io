import { getFirestore, collection, getDocs, query, orderBy, limit } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";
import { app } from "./firebase-config.js"; // Siguraduhing tama ang path ng config mo

const db = getFirestore(app);
let allLogs = []; // Dito natin ise-save ang lahat ng na-fetch na logs
let filteredLogs = []; // Para sa display after i-filter

const tableBody = document.getElementById("auditLogsTableBody");
const filterForm = document.getElementById("filterForm");
const clearFilterBtn = document.getElementById("clearFilterBtn");

// Helper function para sa Date Formatting
function formatDateTime(isoString) {
    if (!isoString) return { date: '-', time: '-' };
    const d = new Date(isoString);
    return {
        date: d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }),
        time: d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
    };
}

// Helper function para i-format ang Changed Data (at itago ang passwords)
function formatChangedData(dataObj) {
    if (!dataObj || typeof dataObj !== 'object') return `<span class="text-muted">-</span>`;
    
    let html = '<div class="data-list">';
    for (const [key, value] of Object.entries(dataObj)) {
        let displayValue = value;
        
        // Censor passwords
        if (key.toLowerCase().includes('password') || key.toLowerCase() === 'token') {
            displayValue = '********';
        } else if (typeof value === 'object') {
            displayValue = JSON.stringify(value);
        }

        const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        
        html += `
            <div class='data-item'>
                <span class='data-key'>${formattedKey}:</span> 
                <span class='data-val'>${displayValue}</span>
            </div>`;
    }
    html += '</div>';
    return html;
}

// Helper function para pumili ng kulay ng Badge
function getBadgeClass(action) {
    const act = action.toUpperCase();
    if (act.includes('DELETE') || act.includes('FAIL')) return 'badge-danger';
    if (act.includes('SUCCESS') || act.includes('LOGIN') || act.includes('CREATE')) return 'badge-success';
    if (act.includes('UPDATE')) return 'badge-warning';
    return 'badge-info';
}

// I-render ang logs sa HTML Table
function renderTable(logsToRender) {
    tableBody.innerHTML = '';

    if (logsToRender.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fas fa-folder-open" style="font-size: 2rem; margin-bottom:10px;"></i>
                    <p>No logs found matching your criteria.</p>
                </td>
            </tr>`;
        return;
    }

    logsToRender.forEach(log => {
        const dt = formatDateTime(log.created_at);
        const actionBadge = getBadgeClass(log.action || '');

        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td class="time-cell">
                ${dt.date}<br>
                <small>${dt.time}</small>
            </td>
            <td>
                <div class="user-cell">
                    <strong>${log.username || 'System / Guest'}</strong>
                    <span class="user-meta">ID: ${log.user_id || 'N/A'}</span>
                </div>
            </td>
            <td>
                <span class="badge ${actionBadge}">${log.action || 'UNKNOWN'}</span>
            </td>
            <td><strong>${log.table_name || '-'}</strong></td>
            <td>${log.object_id || '-'}</td>
            <td class="data-cell">${formatChangedData(log.changed_data)}</td>
            <td class="ip-cell"><i class="fas fa-network-wired"></i> ${log.ip_address || 'Unknown'}</td>
        `;
        tableBody.appendChild(tr);
    });
}

// Kunin ang logs mula sa Firestore
async function fetchLogs() {
    try {
        const logsRef = collection(db, "audit_logs");
        // Kukuha ng pinakabagong 100 logs (pwede mong lakihan kung kailangan)
        const q = query(logsRef, orderBy("created_at", "desc"), limit(100)); 
        const querySnapshot = await getDocs(q);
        
        allLogs = [];
        querySnapshot.forEach((doc) => {
            allLogs.push({ id: doc.id, ...doc.data() });
        });

        filteredLogs = [...allLogs];
        renderTable(filteredLogs);

    } catch (error) {
        console.error("Error fetching logs: ", error);
        tableBody.innerHTML = `<tr><td colspan="7" class="empty-state text-danger">Error loading logs: ${error.message}</td></tr>`;
    }
}

// Filter Logic
filterForm.addEventListener("submit", (e) => {
    e.preventDefault();
    
    const actionFilter = document.getElementById("filterAction").value.trim().toLowerCase();
    const keywordFilter = document.getElementById("filterKeyword").value.trim().toLowerCase();

    filteredLogs = allLogs.filter(log => {
        const matchAction = actionFilter === "" || (log.action && log.action.toLowerCase().includes(actionFilter));
        const matchKeyword = keywordFilter === "" || 
                            (log.username && log.username.toLowerCase().includes(keywordFilter)) || 
                            (log.table_name && log.table_name.toLowerCase().includes(keywordFilter)) ||
                            JSON.stringify(log.changed_data).toLowerCase().includes(keywordFilter);
        
        return matchAction && matchKeyword;
    });

    renderTable(filteredLogs);
});

// Clear Filter Logic
clearFilterBtn.addEventListener("click", () => {
    document.getElementById("filterAction").value = "";
    document.getElementById("filterKeyword").value = "";
    filteredLogs = [...allLogs];
    renderTable(filteredLogs);
});

// Initialize
window.addEventListener("DOMContentLoaded", fetchLogs);