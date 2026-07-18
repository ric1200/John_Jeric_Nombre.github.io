// 1. I-import ang mga kinakailangang Firebase modules
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-app.js";
import { getAuth, signInWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-auth.js";
import { getFirestore, doc, getDoc } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";

// 2. Ang iyong Firebase Configuration
const firebaseConfig = {
  apiKey: "AIzaSyD6PsiCJWwMamIn-XXUcYccgJMU-D4wdh0",
  authDomain: "ricproject-bb8fc.firebaseapp.com",
  projectId: "ricproject-bb8fc",
  storageBucket: "ricproject-bb8fc.firebasestorage.app",
  messagingSenderId: "1055032684339",
  appId: "1:1055032684339:web:fea2712ffeee1008299846"
};

// 3. I-initialize ang Firebase, Auth, at Database
const app = initializeApp(firebaseConfig);
const auth = getAuth(app);
const db = getFirestore(app);

// 4. Kunin ang mga elements mula sa HTML
const loginForm = document.getElementById('loginForm');
const emailInput = document.getElementById('username');
const passwordInput = document.getElementById('password');
const errorMessage = document.getElementById('error-message');
const loginBtn = document.getElementById('loginBtn');
const showPasswordCheckbox = document.getElementById('showPassword');

// 5. Logic para sa "Show Password" checkbox
showPasswordCheckbox.addEventListener('change', function() {
    if (this.checked) {
        passwordInput.type = 'text';
    } else {
        passwordInput.type = 'password';
    }
});

// 6. Logic kapag pinindot ang "Login" button
loginForm.addEventListener('submit', async (e) => {
    e.preventDefault(); // Pigilan ang pag-refresh ng page
    
    const email = emailInput.value;
    const password = passwordInput.value;

    // Itago muna ang error message at palitan ang text ng button
    errorMessage.style.display = 'none';
    loginBtn.innerText = "Logging in...";
    loginBtn.disabled = true;

    try {
        // Step A: Mag-login sa Firebase Authentication
        const userCredential = await signInWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;

        // Step B: Kunin ang data ng user sa Firestore para malaman ang Role
        const userDocRef = doc(db, "users", user.uid);
        const userDocSnap = await getDoc(userDocRef);

        if (userDocSnap.exists()) {
            const userData = userDocSnap.data();
            const role = userData.role;

            // Kapag successful ang login:
            errorMessage.style.display = 'block';
            errorMessage.style.backgroundColor = '#d4edda';
            errorMessage.style.color = '#155724';
            errorMessage.style.borderColor = '#c3e6cb';
            errorMessage.innerText = `Login successful! Redirecting ${role}...`;

            // Step C: I-redirect ang user sa Dashboard (PALITAN MO ANG URL NA ITO)
            setTimeout(() => {
                // Halimbawa: window.location.href = "sysad/dashboard.html";
                // Ilagay dito ang tamang file path ng dashboard mo:
                window.location.href = "dashboard.html"; 
            }, 1500);

        } else {
            throw new Error("User data not found in database.");
        }

    } catch (error) {
        // Kapag may mali sa email o password
        console.error("Login Error:", error);
        errorMessage.style.display = 'block';
        errorMessage.style.backgroundColor = '#ffe6e6';
        errorMessage.style.color = '#d9534f';
        errorMessage.style.borderColor = '#d9534f';
        
        if (error.code === 'auth/invalid-credential' || error.code === 'auth/user-not-found' || error.code === 'auth/wrong-password') {
            errorMessage.innerText = "Login Error: Invalid email or password.";
        } else {
            errorMessage.innerText = `Error: ${error.message}`;
        }
    } finally {
        // Ibalik sa normal ang button kahit nagka-error
        loginBtn.innerText = "Login";
        loginBtn.disabled = false;
    }
});