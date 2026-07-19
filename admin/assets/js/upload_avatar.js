// ==========================================================
// 1. FIREBASE INITIALIZATION & IMPORTS
// ==========================================================
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-app.js";
import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-auth.js";
import { getFirestore, doc, getDoc, updateDoc } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";

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

// NAKALAGAY NA ANG IYONG IMGBB API KEY DITO
const IMGBB_API_KEY = "1122e0bd2e83b03410374a48f485a532"; 

let currentUserId = null;
let selectedFile = null;

const urlParams = new URLSearchParams(window.location.search);
const targetUserId = urlParams.get('id') || urlParams.get('user_id');

// ==========================================================
// 2. DYNAMIC SIDEBAR LOAD
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
        }
    } catch (err) {
        console.error("Error drawing sidebar:", err);
    }
}

function setupSidebarLogic() {
    const currentPath = window.location.pathname;
    document.querySelectorAll('.sidebar a').forEach(link => {
        const href = link.getAttribute('href');
        if (href && (currentPath.includes(href.replace('..', '')) || currentPath.includes('upload_avatar.html'))) {
            if (href.includes('profile.html')) link.classList.add('active');
        }
    });
}

// ==========================================================
// 3. UI INTERACTIONS & DRAG AND DROP LOGIC
// ==========================================================
const dropZone = document.getElementById('drop-zone');
const fileInput = document.getElementById('avatarInput');
const imgPreview = document.getElementById('imgPreview');
const uploadBtn = document.getElementById('upload-btn');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#3b82f6';
    dropZone.style.background = '#eff6ff';
});

dropZone.addEventListener('dragleave', () => {
    dropZone.style.borderColor = '#cbd5e1';
    dropZone.style.background = '#f8fafc';
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#cbd5e1';
    dropZone.style.background = '#f8fafc';
    
    const files = e.dataTransfer.files;
    if (files.length > 0) handleFileSelection(files[0]);
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) handleFileSelection(e.target.files[0]);
});

function handleFileSelection(file) {
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    
    if (!allowedTypes.includes(file.type)) {
        alert("Invalid file type. JPG, JPEG, and PNG only.");
        return;
    }

    if (file.size > 2 * 1024 * 1024) { 
        alert("File is too large. Maximum size is 2MB.");
        return;
    }

    selectedFile = file;
    imgPreview.src = URL.createObjectURL(file);
    uploadBtn.disabled = false; 
}

// ==========================================================
// 4. IMGBB API UPLOAD LOGIC
// ==========================================================
document.getElementById('avatar-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!selectedFile || !currentUserId) return;

    if (!IMGBB_API_KEY) {
        alert("Paki-lagay muna ang iyong ImgBB API Key sa code!");
        return;
    }

    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

    try {
        const formData = new FormData();
        formData.append("image", selectedFile);

        const response = await fetch(`https://api.imgbb.com/1/upload?key=${IMGBB_API_KEY}`, {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            const downloadURL = data.data.url;

            await updateDoc(doc(db, "users", currentUserId), {
                avatar_path: downloadURL
            });

            alert("Profile picture updated successfully!");
            window.location.href = targetUserId ? `profile.html?id=${targetUserId}` : "profile.html";
        } else {
            throw new Error(data.error.message || "Failed to upload to ImgBB");
        }

    } catch (error) {
        console.error("Upload error details:", error);
        alert("Upload Failed: " + error.message);
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
    }
});

// ==========================================================
// 5. INITIALIZE AND PRE-LOAD CURRENT AVATAR
// ==========================================================
document.addEventListener('DOMContentLoaded', async () => {
    await loadSidebar();

    onAuthStateChanged(auth, async (user) => {
        if (user) {
            currentUserId = targetUserId ? targetUserId : user.uid;
            
            const userDoc = await getDoc(doc(db, "users", currentUserId));
            if (userDoc.exists()) {
                const data = userDoc.data();
                if (data.first_name) document.getElementById('user-greeting').innerText = data.first_name;
                if (data.avatar_path) imgPreview.src = data.avatar_path;
            }
        } else {
            window.location.href = "../index.html";
        }
    });
});