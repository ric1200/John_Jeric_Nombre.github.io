// ==========================================================
// 1. FIREBASE INITIALIZATION & IMPORTS
// ==========================================================
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-app.js";
import { getFirestore, collection, doc, getDoc, addDoc, updateDoc, serverTimestamp } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";

const firebaseConfig = {
  apiKey: "AIzaSyD6PsiCJWwMamIn-XXUcYccgJMU-D4wdh0",
  authDomain: "ricproject-bb8fc.firebaseapp.com",
  projectId: "ricproject-bb8fc",
  storageBucket: "ricproject-bb8fc.firebasestorage.app",
  messagingSenderId: "1055032684339",
  appId: "1:1055032684339:web:fea2712ffeee1008299846"
};

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

// Kunin ang ID mula sa URL (e.g., user_form.html?id=12345)
const urlParams = new URLSearchParams(window.location.search);
const userId = urlParams.get('id');

// ==========================================================
// 2. AUTH GUARD & SIDEBAR
// ==========================================================
function checkAuth() {
  const userRole = sessionStorage.getItem('role') || 'ADMIN'; 
  if (!userRole) window.location.href = '../login.html';
}

async function loadSidebar() {
  const container = document.getElementById('sidebar-container');
  if (!container) return;
  try {
    const cacheBuster = new Date().getTime();
    const response = await fetch(`../includes/sidebar.html?v=${cacheBuster}`);
    if (response.ok) {
      container.innerHTML = await response.text();
      setupSidebarLogic();
    }
  } catch (err) {
    console.error("Error loading sidebar:", err);
  }
}

function setupSidebarLogic() {
  const logoutBtn = document.getElementById('logout-btn'); 
  if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      sessionStorage.clear();
      window.location.href = '../index.html';
    });
  }
  const currentPath = window.location.pathname;
  const links = document.querySelectorAll('.sidebar a');
  links.forEach(link => {
    if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').replace('..', ''))) {
      link.classList.add('active');
    }
  });
}

// ==========================================================
// 3. PAGE INITIALIZATION (ADD OR EDIT MODE)
// ==========================================================
async function initForm() {
  checkAuth();
  loadSidebar();

  if (userId) {
    // EDIT MODE
    document.getElementById('page-title').innerText = 'Edit User | Admin';
    document.getElementById('form-header-title').innerText = 'Edit User Profile';
    document.getElementById('form-header-desc').innerText = 'Update the details and permissions of this account.';
    document.getElementById('submit-btn-text').innerText = 'Update User Account';
    document.getElementById('status-group').style.display = 'block'; // Ipakita ang status dropdown

    try {
      const docRef = doc(db, "users", userId);
      const docSnap = await getDoc(docRef);

      if (docSnap.exists()) {
        const data = docSnap.data();
        document.getElementById('first_name').value = data.first_name || '';
        document.getElementById('middle_name').value = data.middle_name || '';
        document.getElementById('last_name').value = data.last_name || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('username').value = data.username || '';
        document.getElementById('role').value = data.role || 'STUDENT';
        document.getElementById('division').value = data.division || 'STUDENT';
        document.getElementById('status').value = data.status || 'ACTIVE';
      } else {
        showMessage("User not found.", "error");
      }
    } catch (error) {
      console.error("Error fetching user data:", error);
      showMessage("Database Error: " + error.message, "error");
    }
  } else {
    // ADD MODE
    document.getElementById('page-title').innerText = 'Add User | Admin';
    document.getElementById('form-header-title').innerText = 'Add New User';
    document.getElementById('form-header-desc').innerText = 'Fill in the details below to create a new system account.';
    document.getElementById('submit-btn-text').innerText = 'Create User Account';
  }
}

// ==========================================================
// 4. FORM SUBMIT HANDLER (SAVE TO FIRESTORE)
// ==========================================================
document.getElementById('userForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const submitBtn = document.getElementById('submit-btn');
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

  // Kunin ang mga nilagay sa form
  const userData = {
    first_name: document.getElementById('first_name').value.trim(),
    middle_name: document.getElementById('middle_name').value.trim(),
    last_name: document.getElementById('last_name').value.trim(),
    email: document.getElementById('email').value.trim(),
    username: document.getElementById('username').value.trim(),
    role: document.getElementById('role').value,
    division: document.getElementById('division').value
  };

  try {
    if (userId) {
      // UPDATE DOCUMENT
      userData.status = document.getElementById('status').value; // isama ang status pag edit
      const docRef = doc(db, "users", userId);
      await updateDoc(docRef, userData);
      
      // Audit Log para sa Update
      await logAudit("USER_UPDATE", `Updated user: ${userData.username}`);
      
    } else {
      // INSERT NEW DOCUMENT
      userData.status = 'ACTIVE'; // Default value pag bago
      userData.created_at = serverTimestamp(); // Gayahin ang PHP auto-timestamp
      
      const newDocRef = await addDoc(collection(db, "users"), userData);
      
      // Audit Log para sa Create
      await logAudit("USER_CREATE", `Created new user: ${userData.username}`);
    }

    showMessage("User saved successfully! Redirecting...", "success");
    setTimeout(() => {
      window.location.href = 'manage_users.html';
    }, 1500);

  } catch (error) {
    console.error("Error saving document: ", error);
    showMessage("Failed to save user: " + error.message, "error");
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-save"></i> Save User';
  }
});

// ==========================================================
// 5. HELPER FUNCTIONS
// ==========================================================
function showMessage(msg, type) {
  const container = document.getElementById('message-container');
  container.style.display = 'block';
  container.className = type === 'error' ? 'alert alert-danger' : 'alert alert-success';
  container.innerHTML = type === 'error' 
    ? `<i class="fas fa-exclamation-circle"></i> ${msg}`
    : `<i class="fas fa-check-circle"></i> ${msg}`;
}

async function logAudit(action, message) {
  try {
    await addDoc(collection(db, "audit_logs"), {
      action: action,
      details: { message: message },
      user_id: sessionStorage.getItem('email') || 'Admin', // Kung sino ang gumawa
      timestamp: serverTimestamp()
    });
  } catch (e) {
    console.error("Failed to insert audit log", e);
  }
}

// Simulan ang page
initForm();