// ==========================================================
// 1. FIREBASE IMPORTS (Mula sa centralized config)
// ==========================================================
// I-import ang nag-iisang config file natin
import { auth, db } from "./firebase_config.js"; 

// I-import ang mga kailangang functions lang para sa file na ito
import { sendPasswordResetEmail } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-auth.js";
import { collection, query, where, getDocs } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";

// ==========================================================
// 2. UI ELEMENTS SELECTORS
// ==========================================================
const form = document.getElementById('forgotPasswordForm');
const defaultView = document.getElementById('default-view');
const successView = document.getElementById('success-view');
const identifierInput = document.getElementById('identifier');
const submitBtn = document.getElementById('submitBtn');

// ==========================================================
// 3. FORM SUBMISSION LOGIC
// ==========================================================
form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const identifier = identifierInput.value.trim();
    if (!identifier) return;

    submitBtn.disabled = true;
    submitBtn.innerText = "Sending Link...";

    try {
        let targetEmail = identifier;

        if (!identifier.includes('@')) {
            const usersRef = collection(db, "users");
            const q = query(usersRef, where("username", "==", identifier));
            const querySnapshot = await getDocs(q);

            if (!querySnapshot.empty) {
                targetEmail = querySnapshot.docs[0].data().email;
            } else {
                showSuccess();
                return;
            }
        }

        await sendPasswordResetEmail(auth, targetEmail);
        showSuccess();
    } catch (error) {
        console.error("Error sending reset email:", error);
        
        alert("System Error: " + error.message); 
        
        submitBtn.disabled = false;
        submitBtn.innerText = "Send Reset Link";
    }
});

// Helper function para magpalit ng view sa UI
function showSuccess() {
    defaultView.style.display = 'none';
    successView.style.display = 'block';
}