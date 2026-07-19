import { initializeApp } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-app.js";
import { getFirestore, doc, getDoc, updateDoc } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";
import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-auth.js";

// Initialize Firebase (gamitin ang parehong config)
const db = getFirestore();
const auth = getAuth();

let currentUserId = null;

// 1. I-load ang existing data
async function loadUserData(uid) {
    const userDoc = await getDoc(doc(db, "users", uid));
    if (userDoc.exists()) {
        const data = userDoc.data();
        document.getElementById('first_name').value = data.first_name || '';
        document.getElementById('middle_name').value = data.middle_name || '';
        document.getElementById('last_name').value = data.last_name || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('department').value = data.department || '';
        document.getElementById('phone_number').value = data.phone_number || '';
    }
}

// 2. I-save ang changes sa Firestore
const form = document.getElementById('edit-profile-form');
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('save-btn');
    btn.disabled = true;
    btn.innerText = "Saving...";

    try {
        await updateDoc(doc(db, "users", currentUserId), {
            first_name: document.getElementById('first_name').value,
            middle_name: document.getElementById('middle_name').value,
            last_name: document.getElementById('last_name').value,
            email: document.getElementById('email').value,
            department: document.getElementById('department').value,
            phone_number: document.getElementById('phone_number').value
        });
        alert("Profile updated successfully!");
        window.location.href = "profile.html";
    } catch (error) {
        console.error(error);
        alert("Error updating profile.");
    } finally {
        btn.disabled = false;
        btn.innerText = "Save Changes";
    }
});

// 3. Auth Check
onAuthStateChanged(auth, (user) => {
    if (user) {
        currentUserId = user.uid;
        loadUserData(currentUserId);
    } else {
        window.location.href = "../login.html";
    }
});