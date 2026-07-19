// ==========================================================
// 1. FIREBASE INITIALIZATION & IMPORTS (Kopya sa Dashboard mo)
// ==========================================================
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-app.js";
import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-auth.js";
import { getFirestore, collection, query, getDocs, orderBy, limit } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";

const firebaseConfig = {
  apiKey: "AIzaSyD6PsiCJWwMamIn-XXUcYccgJMU-D4wdh0",
  authDomain: "ricproject-bb8fc.firebaseapp.com",
  projectId: "ricproject-bb8fc",
  storageBucket: "ricproject-bb8fc.firebasestorage.app",
  messagingSenderId: "1055032684339",
  appId: "1:1055032684339:web:fea2712ffeee1008299846"
};

const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const db = getFirestore(app);

// ==========================================================
// 2. GLOBAL VARIABLES PARA SA LOGS
// ==========================================================
let allLogs = []; 
let filteredLogs = []; 

const tableBody = document.getElementById("auditLogsTableBody");
const filterForm = document.getElementById("filterForm");
const clearFilterBtn = document.getElementById("clearFilterBtn");

// ==========================================================
// 3. HELPER FUNCTIONS PARA SA TABLE (UPDATED PARA SA FIRESTORE TIMESTAMP)
// ==========================================================
function formatDateTime(timestampField) {
    if (!timestampField) return { date: '-', time: '-' };
    
    let d;
    // Tinitingnan kung ito ay isang Firestore Timestamp object na may .toDate()
    if (timestampField && typeof timestampField.toDate === 'function') {
        d = timestampField.toDate();
    } else {
        // Kung ito ay ISO String o standard date string
        d = new Date(timestampField);
    }
    
    // Check kung valid date ang kinalabasan
    if (isNaN(d.getTime())) {
        return { date: 'Invalid Date', time: '' };
    }

    return {
        date: d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }),
        time: d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
    };
}

function formatChangedData(dataObj) {
    if (!dataObj || typeof dataObj !== 'object') return `<span class="text-muted">-</span>`;
    
    let html = '<div class="data-list">';
    for (const [key, value] of Object.entries(dataObj)) {
        let displayValue = value;
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

function getBadgeClass(action) {
    const act = action ? action.toUpperCase() : '';
    if (act.includes('DELETE') || act.includes('FAIL')) return 'badge-danger';
    if (act.includes('SUCCESS') || act.includes('LOGIN') || act.includes('CREATE')) return 'badge-success';
    if (act.includes('UPDATE')) return 'badge-warning';
    return 'badge-info';
}

// RENDER TABLE (TINANGGAL ANG TABLE AT OBJ ID COLUMNS)
function renderTable(logsToRender) {
    if (!tableBody) return;
    tableBody.innerHTML = '';

    if (logsToRender.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="empty-state" style="text-align:center; padding: 30px;">
                    <i class="fas fa-folder-open" style="font-size: 2rem; margin-bottom:10px;"></i>
                    <p>No logs found.</p>
                </td>
            </tr>`;
        return;
    }

    logsToRender.forEach(log => {
        // Ginawang fallback kung timestamp o created_at ang field name sa database
        const dt = formatDateTime(log.timestamp || log.created_at); 
        const actionBadge = getBadgeClass(log.action);
        
        // Dynamic search sa iba't ibang posibleng pangalan ng IP Field sa database
        const ipAddress = log.ip_address || log.ipAddress || log.ip || 'Unknown';

        const tr = document.createElement("tr");
        tr.innerHTML = `
            <td class="time-cell">
                ${dt.date}<br>
                <small>${dt.time}</small>
            </td>
            <td>
                <div class="user-cell">
                    <strong>${log.username || log.details?.email || 'System / Guest'}</strong>
                </div>
            </td>
            <td>
                <span class="badge ${actionBadge}">${log.action || 'UNKNOWN'}</span>
            </td>
            <!-- Tinanggal na dito ang Table at Obj ID columns -->
            <td class="data-cell">${formatChangedData(log.changed_data || log.details)}</td>
            <td class="ip-cell"><i class="fas fa-network-wired"></i> ${ipAddress}</td>
        `;
        tableBody.appendChild(tr);
    });
}

// ==========================================================
// 4. FETCH AUDIT LOGS FROM FIRESTORE
// ==========================================================
async function fetchLogs() {
    if (!tableBody) return;
    try {
        const logsRef = collection(db, "audit_logs");
        const q = query(logsRef, orderBy("timestamp", "desc"), limit(100)); 
        const querySnapshot = await getDocs(q);
        
        allLogs = [];
        querySnapshot.forEach((doc) => {
            allLogs.push({ id: doc.id, ...doc.data() });
        });

        filteredLogs = [...allLogs];
        renderTable(filteredLogs);

    } catch (error) {
        console.error("Error fetching logs: ", error);
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">Error loading logs: ${error.message}</td></tr>`;
    }
}

// ==========================================================
// 5. EVENT LISTENERS FOR FILTER
// ==========================================================
if (filterForm) {
    filterForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const actionFilter = document.getElementById("filterAction").value.trim().toLowerCase();
        const keywordFilter = document.getElementById("filterKeyword").value.trim().toLowerCase();

        filteredLogs = allLogs.filter(log => {
            const matchAction = actionFilter === "" || (log.action && log.action.toLowerCase().includes(actionFilter));
            const matchKeyword = keywordFilter === "" || 
                                (log.username && log.username.toLowerCase().includes(keywordFilter)) || 
                                (log.table_name && log.table_name.toLowerCase().includes(keywordFilter)) ||
                                JSON.stringify(log.changed_data || log.details).toLowerCase().includes(keywordFilter);
            
            return matchAction && matchKeyword;
        });
        renderTable(filteredLogs);
    });
}

if (clearFilterBtn) {
    clearFilterBtn.addEventListener("click", () => {
        document.getElementById("filterAction").value = "";
        document.getElementById("filterKeyword").value = "";
        filteredLogs = [...allLogs];
        renderTable(filteredLogs);
    });
}

// ==========================================================
// 6. DYNAMIC SIDEBAR LOAD & LOGIC
// ==========================================================
async function loadSidebar() {
  const container = document.getElementById('sidebar-container');
  if (!container) return;

  try {
    const cacheBuster = new Date().getTime();
    const response = await fetch(`../includes/sidebar.html?v=${cacheBuster}`);
    
    if (response.ok) {
      container.innerHTML = await response.text();
      setupSidebarLogic();
    } else {
      console.error("Failed to load sidebar. Check if ../includes/sidebar.html exists.");
    }
  } catch (err) {
    console.error("Error drawing sidebar component:", err);
  }
}

function setupSidebarLogic() {
  const logoutBtn = document.getElementById('logout-btn'); 
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      await signOut(auth);
      sessionStorage.clear();
      window.location.href = '../../index.html'; 
    });
  }

  const currentPath = window.location.pathname;
  const links = document.querySelectorAll('.sidebar a');
  links.forEach(link => {
    if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').replace(/\.\./g, '').replace(/\//g, ''))) {
      link.classList.add('active');
    }
  });
}

// ==========================================================
// 7. BOOTSTRAP INITIALIZATION
// ==========================================================
async function init() {
  await loadSidebar();
  await fetchLogs();
}

init();