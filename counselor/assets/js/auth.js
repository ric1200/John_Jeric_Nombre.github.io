// 1. I-import ang mga kinakailangang Firebase modules (v10.8.0)
import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { getAuth, signInWithEmailAndPassword, signOut } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";
import { getFirestore, doc, getDoc } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

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
  
  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();

  // Itago muna ang error message at palitan ang text ng button
  errorMessage.style.display = 'none';
  loginBtn.innerText = "Logging in...";
  loginBtn.disabled = true;

  try {
      // Step A: Mag-login sa Firebase Authentication
      const userCredential = await signInWithEmailAndPassword(auth, email, password);
      const user = userCredential.user;

      // Step B: Kunin ang data ng user sa Firestore para malaman ang Role at Status
      const userDocRef = doc(db, "users", user.uid);
      const userDocSnap = await getDoc(userDocRef);

      if (userDocSnap.exists()) {
          const userData = userDocSnap.data();

          // 🛑 HARANG 1: KAPAG INACTIVE ACCOUNT
          if (userData.status === 'INACTIVE') {
              await signOut(auth); // I-logout agad para walang matirang session
              throw new Error("deactivated-account");
          }

          // 🛑 HARANG 2: COUNSELOR ROLE CHECK (Strict Verification)
          // Tinitiyak nito na COUNSELOR lang ang makakapasok dito
          if (userData.role !== 'COUNSELOR') {
              await signOut(auth); // Piliting i-logout kapag hindi counselor
              throw new Error("unauthorized-role");
          }

          // Kapag successful ang login, ACTIVE, at COUNSELOR ang user:
          errorMessage.style.display = 'block';
          errorMessage.style.backgroundColor = '#d4edda';
          errorMessage.style.color = '#155724';
          errorMessage.style.borderColor = '#c3e6cb';
          errorMessage.innerText = `Login successful! Welcome Counselor. Redirecting...`;

          // Save role to session storage for UI awareness
          sessionStorage.setItem('userRole', userData.role);

          // Step C: I-redirect ang user sa Counselor Dashboard
          setTimeout(() => {
              window.location.href = "counselor/dashboard.html"; 
          }, 1200);

      } else {
          await signOut(auth);
          throw new Error("User data not found in database.");
      }

  } catch (error) {
      console.error("Login Error:", error);
      
      // BUBURA ANG LAMAN NG PASSWORD TEXTBOX KAPAG NAG-ERROR
      passwordInput.value = '';
      
      errorMessage.style.display = 'block';
      errorMessage.style.backgroundColor = '#ffe6e6';
      errorMessage.style.color = '#d9534f';
      errorMessage.style.borderColor = '#d9534f';
      
      // Ipakita ang tamang error message depende sa dahilan
      if (error.message === 'deactivated-account') {
          errorMessage.innerText = "Login Error: Your account has been deactivated. Please contact the administrator.";
      } else if (error.message === 'unauthorized-role') {
          errorMessage.innerText = "Access Denied: This login portal is strictly reserved for Counselors.";
      } else if (error.code === 'auth/invalid-credential' || error.code === 'auth/user-not-found' || error.code === 'auth/wrong-password') {
          errorMessage.innerText = "Login Error: Invalid email or password.";
      } else {
          errorMessage.innerText = `Error: ${error.message}`;
      }
  } finally {
      // Ibalik sa normal ang button
      loginBtn.innerText = "Login";
      loginBtn.disabled = false;
  }
});