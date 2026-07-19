// ==========================================================
// 1. FIREBASE INITIALIZATION & IMPORTS
// ==========================================================
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-app.js";
import { getFirestore, doc, getDoc, updateDoc } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";
import { getAuth, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-auth.js";

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
const auth = getAuth(app);

let currentUserId = null;

// ==========================================================
// 2. DYNAMIC SIDEBAR LOAD & LOGIC
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
            console.error("Failed to load sidebar.");
        }
    } catch (err) {
        console.error("Error loading sidebar:", err);
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

    // Highlight Active Link (Iilaw pa rin ang 'Profile' kahit nasa Edit Profile ka)
    const currentPath = window.location.pathname;
    const links = document.querySelectorAll('.sidebar a');
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (href && (currentPath.includes(href.replace('..', '')) || currentPath.includes('edit_profile.html'))) {
            // Kung nasa edit_profile.html tayo, i-highlight ang profile.html
            if (href.includes('profile.html')) {
                link.classList.add('active');
            }
        }
    });
}

// ==========================================================
// 3. LOAD EXISTING USER DATA
// ==========================================================
async function loadUserData(uid) {
    try {
        const userDoc = await getDoc(doc(db, "users", uid));
        if (userDoc.exists()) {
            const data = userDoc.data();
            document.getElementById('first_name').value = data.first_name || '';
            document.getElementById('last_name').value = data.last_name || '';
            document.getElementById('phone_number').value = data.phone_number || '';
            document.getElementById('address').value = data.address || '';
        }
    } catch (error) {
        console.error("Error loading user data:", error);
    }
}

// ==========================================================
// 4. SAVE CHANGES TO FIRESTORE
// ==========================================================
const form = document.getElementById('edit-profile-form');
if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        try {
            await updateDoc(doc(db, "users", currentUserId), {
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                phone_number: document.getElementById('phone_number').value,
                address: document.getElementById('address').value
            });
            
            alert("Profile updated successfully!");
            window.location.href = "profile.html"; // Ibalik sa profile page pagkatapos mag-save
        } catch (error) {
            console.error("Error updating profile:", error);
            alert("Error updating profile.");
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Save Changes';
        }
    });
}

// ==========================================================
// 5. INITIALIZE PAGE
// ==========================================================
document.addEventListener('DOMContentLoaded', async () => {
    // 1. I-load muna ang sidebar
    await loadSidebar();

    // 2. I-check kung may nakalog-in, saka i-load ang data
    onAuthStateChanged(auth, (user) => {
        if (user) {
            currentUserId = user.uid;
            loadUserData(currentUserId);
        } else {
            // Kung walang naka-login, ibalik sa login page
            window.location.href = "../index.html";
        }
    });
});