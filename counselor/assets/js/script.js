// ==========================================================
// 1. FIREBASE IMPORTS (Mula sa centralized config)
// ==========================================================
import { auth, db } from "./firebase_config.js"; 
import { signInWithEmailAndPassword, signOut } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-auth.js";
import { collection, query, where, getDocs } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";

const loginForm = document.getElementById("loginForm");
const errorMessage = document.getElementById("error-message");
const submitBtn = document.querySelector(".login-btn");

// ==========================================================
// 2. URL PARAMETER CHECK (PHP $_GET Replacement)
// ==========================================================
const urlParams = new URLSearchParams(window.location.search);
const errorParam = urlParams.get('error');

if (errorParam === 'invalid') {
    showError("Invalid username or password.");
} else if (errorParam === 'empty') {
    showError("Please fill in all fields.");
}

// ==========================================================
// 3. FORM SUBMISSION LOGIC (Firebase Login & Role Check)
// ==========================================================
loginForm.addEventListener("submit", async (e) => {
    e.preventDefault(); 

    const identifier = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();

    if (!identifier || !password) {
        showError("Please fill in all fields.");
        return;
    }

    // I-disable ang button habang naglo-load
    submitBtn.disabled = true;
    submitBtn.innerText = "Verifying...";
    errorMessage.style.display = "none";

    try {
        let targetEmail = identifier;

        // HAKBANG 1: Kung Username ang tinype (walang @), kunin ang Email sa Firestore
        if (!identifier.includes('@')) {
            const usersRef = collection(db, "users");
            const q = query(usersRef, where("username", "==", identifier));
            const querySnapshot = await getDocs(q);

            if (!querySnapshot.empty) {
                targetEmail = querySnapshot.docs[0].data().email;
            } else {
                throw new Error("invalid-credential"); // Para pareho ang error message kung mali
            }
        }

        // HAKBANG 2: Mag-Login gamit ang Firebase Auth
        const userCredential = await signInWithEmailAndPassword(auth, targetEmail, password);
        const user = userCredential.user;

        // HAKBANG 3: I-verify ang Role sa Firestore
        const usersRef = collection(db, "users");
        // Hahanapin natin yung document na may UID na ito para mabilis
        const qRole = query(usersRef, where("email", "==", targetEmail));
        const roleSnapshot = await getDocs(qRole);

        if (!roleSnapshot.empty) {
            const userData = roleSnapshot.docs[0].data();

            if (userData.role === "COUNSELOR") {
                // SUCCESS! Counselor nga siya.
                sessionStorage.setItem('role', userData.role);
                window.location.href = "counselor/dashboard.html"; // I-redirect mo sa totoong dashboard mo
            } else {
                // FAILURE: Tama ang password pero HINDI siya Counselor (Hal. Admin/Student)
                await signOut(auth); // Pilitin silang i-logout sa background
                showError("Access Denied. This login is strictly for Counselors only.");
            }
        } else {
             throw new Error("invalid-credential");
        }

    } catch (error) {
        console.error("Login Error:", error);
        
        // Patalastas kung mali ang password o email/username
        if (error.code === 'auth/invalid-credential' || error.message === 'invalid-credential') {
            showError("Invalid username or password.");
        } else {
            showError("System Error: " + error.message);
        }
    } finally {
        // I-balik sa normal ang button kapag tapos na (success man o error)
        submitBtn.disabled = false;
        submitBtn.innerText = "Login";
    }
});

// Helper function to display the error text
function showError(message) {
    errorMessage.textContent = message;
    errorMessage.style.display = "block";
}