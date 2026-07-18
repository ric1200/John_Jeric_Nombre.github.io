// ==========================================================
// 1. FIREBASE INITIALIZATION & IMPORTS
// ==========================================================
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-app.js";
import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-auth.js";
import { getFirestore, collection, query, where, getCountFromServer, getDocs, orderBy, limit } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";

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
// 2. CLIENT-SIDE AUTH GUARDsda
// ==========================================================
function checkAuth() {
  return new Promise((resolve) => {
    onAuthStateChanged(auth, (user) => {
      if (!user) {
        window.location.href = '../index.html';
        resolve(null);
      } else {
        // PANSAMANTALANG NAKA-DISABLE ANG ROLE CHECK
        // const role = sessionStorage.getItem('role');
        // if (role !== 'SUPER_ADMIN' && role !== 'ADMIN') {
        //   alert("Unauthorized access.");
        //   signOut(auth).then(() => {
        //     window.location.href = '../index.html';
        //   });
        //   resolve(null);
        // } else {
          resolve(user);
        // }
      }
    });
  });
}
// ==========================================================
// 3. DYNAMIC SIDEBAR LOAD & LOGIC
// ==========================================================
async function loadSidebar() {
  const container = document.getElementById('sidebar-container');
  if (!container) return;

  try {
    // Kinukuha ang hiwalay na sidebar.html file
    const response = await fetch('../includes/sidebar.html');
    
    if (response.ok) {
      container.innerHTML = await response.text();
      setupSidebarLogic(); // Tatawagin lang ito kapag nai-load na ang HTML
    } else {
      console.error("Failed to load sidebar. Check if ../includes/sidebar.html exists.");
    }
  } catch (err) {
    console.error("Error drawing sidebar component:", err);
  }
}

function setupSidebarLogic() {
  // Setup Logout Button
  const logoutBtn = document.getElementById('logout-btn'); 
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      await signOut(auth);
      sessionStorage.clear();
      window.location.href = '../index.html';
    });
  }

  // Highlight Active Link
  const currentPath = window.location.pathname;
  const links = document.querySelectorAll('.sidebar a');
  links.forEach(link => {
    if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').replace('..', ''))) {
      link.classList.add('active');
    }
  });
}

// ==========================================================
// 4. FETCH METRICS
// ==========================================================
async function fetchDashboardStats() {
  try {
    const usersRef = collection(db, "users");

    const [totalUsersSnap, adminsSnap, studentsSnap, counselorsSnap] = await Promise.all([
      getCountFromServer(usersRef),
      getCountFromServer(query(usersRef, where("role", "==", "ADMIN"))),
      getCountFromServer(query(usersRef, where("role", "==", "STUDENT"))),
      getCountFromServer(query(usersRef, where("role", "==", "COUNSELOR")))
    ]);

    updateStatElement('total-users', totalUsersSnap.data().count);
    updateStatElement('total-admins', adminsSnap.data().count);
    updateStatElement('total-students', studentsSnap.data().count);
    updateStatElement('total-counselors', counselorsSnap.data().count);

  } catch (err) {
    console.error("Database Count Fetch Error:", err);
  }
}

function updateStatElement(id, value) {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = value ?? 0;
    el.classList.remove('loading-placeholder');
  }
}

// ==========================================================
// 5. RECENT SYSTEM ACTIVITY
// ==========================================================
async function fetchRecentLogs() {
  const tbody = document.getElementById('logs-tbody');
  
  try {
    const logsQuery = query(collection(db, "audit_logs"), orderBy("timestamp", "desc"), limit(5));
    const logsSnap = await getDocs(logsQuery);

    if (logsSnap.empty) {
      tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">No recent activity.</td></tr>`;
      return;
    }

    tbody.innerHTML = '';

    logsSnap.forEach(docSnap => {
      const log = docSnap.data();
      
      const dateObj = log.timestamp ? log.timestamp.toDate() : new Date();
      const dateStr = dateObj.toLocaleString('en-US', {
        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false
      });

      let badgeClass = 'badge-info';
      if (log.action && log.action.includes('DELETE')) badgeClass = 'badge-danger';
      else if (log.action && (log.action.includes('CREATE') || log.action.includes('SUCCESS'))) badgeClass = 'badge-success';

      const emailUser = log.details?.email || log.user_id || 'System';
      const actionName = log.action || 'System Event';
      const targetDetail = log.details?.message || log.details?.reason || '';

      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${dateStr}</td>
        <td><strong>${escapeHtml(emailUser)}</strong></td>
        <td><span class="badge ${badgeClass}">${escapeHtml(actionName)}</span></td>
        <td style="color:#666;">${escapeHtml(targetDetail)}</td>
      `;
      tbody.appendChild(row);
    });

  } catch (err) {
    console.error("Audit Logs Error:", err);
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#d9534f;">Failed to retrieve active log trace.</td></tr>`;
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return str.toString()
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// ==========================================================
// 6. BOOTSTRAP INITIALIZATION
// ==========================================================
async function init() {
  const user = await checkAuth();
  if (!user) return; 

  // Hinihintay munang ma-load ang sidebar HTML bago gawin ang iba
  await loadSidebar();

  await Promise.all([
    fetchDashboardStats(),
    fetchRecentLogs()
  ]);
}

init();