// ==========================================================
// 1. FIREBASE INITIALIZATION & IMPORTS (v10.8.0)
// ==========================================================
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { getFirestore, collection, doc, getDoc, addDoc, updateDoc, setDoc, serverTimestamp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";
import { getAuth, createUserWithEmailAndPassword, sendPasswordResetEmail, signOut } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";

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
const auth = getAuth(app); // Idinagdag para makapag-send ng email

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
    document.getElementById('status-group').style.display = 'block';

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
    document.getElementById('form-header-desc').innerText = 'Fill in the details. An email will be sent to the user to set their password.';
    document.getElementById('submit-btn-text').innerText = 'Create User & Send Invite';
  }
}

// ==========================================================
// 4. FORM SUBMIT HANDLER (SAVE TO FIRESTORE & SEND EMAIL)
// ==========================================================
document.getElementById('userForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const submitBtn = document.getElementById('submit-btn');
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving & Sending Email...';

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
      // ==========================================
      // UPDATE DOCUMENT (Edit Mode)
      // ==========================================
      userData.status = document.getElementById('status').value;
      const docRef = doc(db, "users", userId);
      await updateDoc(docRef, userData);
      
      await logAudit("USER_UPDATE", `Updated user: ${userData.username}`);
      showMessage("User updated successfully! Redirecting...", "success");

    } else {
      // ==========================================
      // INSERT NEW DOCUMENT & SEND EMAIL (Add Mode)
      // ==========================================
      userData.status = 'ACTIVE';
      userData.created_at = serverTimestamp();
      
      // 1. Gumawa ng mahirap na temporary password na papasa sa security ng Firebase
      const tempPassword = Math.random().toString(36).slice(-10) + "A1@b";

      // 2. Gumawa ng "Secondary App" para hindi ma-logout ang nakaupong Admin
      const secondaryApp = initializeApp(firebaseConfig, "SecondaryApp");
      const secondaryAuth = getAuth(secondaryApp);

      // 3. I-register ang user sa Authentication
      const userCredential = await createUserWithEmailAndPassword(secondaryAuth, userData.email, tempPassword);
      const newUserId = userCredential.user.uid; // Kunin ang UID mula sa Auth

      // 4. I-save sa Firestore (ginamit natin ang setDoc para parehas ang ID ng Auth at Firestore)
      await setDoc(doc(db, "users", newUserId), userData);

      // 5. I-send ang Password Reset Email (Ito ang magiging Setup Password Link nila)
      await sendPasswordResetEmail(auth, userData.email);

      // 6. I-logout ang secondary app para malinis
      await signOut(secondaryAuth);

      await logAudit("USER_CREATE", `Created new user and sent invite to: ${userData.email}`);
      showMessage("User created! Setup link sent to their email. Redirecting...", "success");
    }

    setTimeout(() => {
      window.location.href = 'manage_users.html';
    }, 2500); // Binigyan natin ng extra 1 second para mabasa yung success message

  } catch (error) {
    console.error("Error saving document: ", error);
    
    // Kung ang email ay nagamit na sa ibang account:
    if (error.code === 'auth/email-already-in-use') {
        showMessage("Error: This email address is already registered.", "error");
    } else {
        showMessage("Failed to save user: " + error.message, "error");
    }
    
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
      let activeUser = 'System Admin';

      // 1. PINAKASIGURADO: Kunin ang email mula mismo sa Firebase Auth
      if (auth.currentUser && auth.currentUser.email) {
          activeUser = auth.currentUser.email;
      } 
      // 2. FALLBACK: Kung walang auth state, hanapin sa iba't ibang posibleng session keys
      else if (sessionStorage.length > 0) {
          activeUser = sessionStorage.getItem('email') || 
                       sessionStorage.getItem('user_email') || 
                       sessionStorage.getItem('username') || 
                       sessionStorage.getItem('user') || 
                       'System Admin';
      }

      await addDoc(collection(db, "audit_logs"), {
          action: action,
          details: { message: message },
          user_id: activeUser,  // Nilagay ko lahat ng variations para siguradong babasahin ng table
          username: activeUser,
          email: activeUser,
          timestamp: serverTimestamp()
      });
  } catch (e) {
      console.error("Failed to insert audit log", e);
  }
}
// Simulan ang page
initForm();