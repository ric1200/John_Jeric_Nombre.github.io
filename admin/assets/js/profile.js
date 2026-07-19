// ==========================================================
// 1. FIREBASE INITIALIZATION & IMPORTS
// ==========================================================
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { getFirestore, doc, getDoc } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";
import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";

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
            setupSidebarLogic(); // Tinawag natin yung function sa ibaba
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
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            sessionStorage.clear();
            auth.signOut().then(() => {
                window.location.href = '../index.html';
            });
        });
    }

    // Highlight Active Link (Ito yung mag-uupdate ng kulay sa gilid!)
    const currentPath = window.location.pathname;
    const links = document.querySelectorAll('.sidebar a');
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.replace('..', ''))) {
            link.classList.add('active');
        }
    });
}

// ==========================================================
// 3. FETCH AND RENDER PROFILE DATA
// ==========================================================
async function fetchProfileData(uid) {
    try {
        const userDocRef = doc(db, "users", uid);
        const userDocSnap = await getDoc(userDocRef);

        if (userDocSnap.exists()) {
            const data = userDocSnap.data();

            // 1. Format Full Name
            const firstName = data.first_name || '';
            const middleName = data.middle_name || '';
            const lastName = data.last_name || '';
            const fullName = `${firstName} ${middleName} ${lastName}`.trim() || 'Unknown Admin';

            // 2. Format Avatar (Fallback to default if empty)
            const avatarUrl = data.avatar_path || '../assets/image/default_admin.png';

            // 3. Update HTML Elements
            document.getElementById('profile-name').innerText = fullName;
            document.getElementById('profile-avatar').src = avatarUrl;
            document.getElementById('admin-level').innerText = data.role || data.admin_level || 'System Admin';
            document.getElementById('profile-email').innerText = data.email || 'N/A';
            document.getElementById('profile-phone').innerText = data.phone_number || 'Not Specified';
            
            // Format Account ID (Ginamit ang last 6 characters ng Firebase UID para mukhang malinis)
            document.getElementById('account-id').innerText = `#USR-${uid.substring(uid.length - 6).toUpperCase()}`;

            // Update Hrefs for buttons (Palitan ang .php ng .html kung nag-convert ka na rin)
            document.getElementById('edit-profile-btn').href = `edit_profile.html?id=${uid}`;
            document.getElementById('edit-avatar-btn').href = `upload_avatar.html?id=${uid}`;

            // 4. Show content, hide loading
            document.getElementById('loading-indicator').style.display = 'none';
            document.getElementById('profile-content').style.display = 'grid';

        } else {
            alert("User profile not found in the database.");
        }
    } catch (error) {
        console.error("Error fetching profile:", error);
        alert("Failed to load profile. Please try again.");
    }
}

// ==========================================================
// 4. INITIALIZE PAGE
// ==========================================================
document.addEventListener('DOMContentLoaded', () => {
    loadSidebar();

    // Hanapin kung may 'user_id' sa URL (para kung nag-click siya mula sa Manage Users list)
    const urlParams = new URLSearchParams(window.location.search);
    const urlUserId = urlParams.get('user_id') || urlParams.get('id');

    if (urlUserId) {
        // Kung may ID sa URL, yun ang i-load
        fetchProfileData(urlUserId);
    } else {
        // Kung wala sa URL, kunin ang nakalog-in na user
        onAuthStateChanged(auth, (user) => {
            if (user) {
                fetchProfileData(user.uid);
            } else {
                // Kung walang nakalog-in, ibalik sa login page
                window.location.href = '../login.html';
            }
        });
    }
});