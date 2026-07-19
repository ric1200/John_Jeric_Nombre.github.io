import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { getAuth, sendPasswordResetEmail } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";
import { getFirestore, collection, query, where, getDocs } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

// Ang iyong Firebase Configuration
const firebaseConfig = {
  apiKey: "AIzaSyD6PsiCJWwMamIn-XXUcYccgJMU-D4wdh0",
  authDomain: "ricproject-bb8fc.firebaseapp.com",
  projectId: "ricproject-bb8fc",
  storageBucket: "ricproject-bb8fc.firebasestorage.app",
  messagingSenderId: "1055032684339",
  appId: "1:1055032684339:web:fea2712ffeee1008299846"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const db = getFirestore(app);

// Get UI Elements
const form = document.getElementById('forgotPasswordForm');
const defaultView = document.getElementById('default-view');
const successView = document.getElementById('success-view');
const identifierInput = document.getElementById('identifier');
const submitBtn = document.getElementById('submitBtn');

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const identifier = identifierInput.value.trim();
    if (!identifier) return;

    // Change button state
    submitBtn.disabled = true;
    submitBtn.innerText = "Sending Link...";

    try {
        let targetEmail = identifier;

        // Kung hindi email ang tinype (walang @), ibig sabihin Username ito
        // Hahanapin natin ang email ng username na ito sa Firestore
        if (!identifier.includes('@')) {
            const usersRef = collection(db, "users");
            const q = query(usersRef, where("username", "==", identifier));
            const querySnapshot = await getDocs(q);

            if (!querySnapshot.empty) {
                // Kunin ang email mula sa database
                targetEmail = querySnapshot.docs[0].data().email;
            } else {
                // Security Best Practice: Kahit walang nahanap na username,
                // papalayasin natin na parang successful para hindi malaman ng hacker kung sino ang mga users
                showSuccess();
                return;
            }
        }

        // Send ang Firebase Password Reset Link
        await sendPasswordResetEmail(auth, targetEmail);

    } catch (error) {
        console.error("Error sending reset email:", error);
        // Kahit may error sa firebase (tulad ng 'user-not-found'), itatago natin sa success screen
        // para sa security (katulad ng ginawa mo sa PHP)
    } finally {
        showSuccess();
    }
});

function showSuccess() {
    defaultView.style.display = 'none';
    successView.style.display = 'block';
}