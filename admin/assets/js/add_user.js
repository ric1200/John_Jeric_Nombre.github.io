// 1. I-import ang mga kinakailangang Firebase modules
import { initializeApp } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-app.js";
import { getAuth, createUserWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-auth.js";
import { getFirestore, doc, setDoc } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";

// 2. Ang iyong Firebase Configuration (mula sa iyong screenshot)
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
const roleSelect = document.getElementById('role');
const adminFields = document.getElementById('admin-fields');
const counselorFields = document.getElementById('counselor-fields');
const addUserForm = document.getElementById('addUserForm');
const messageDiv = document.getElementById('message');

// 5. Logic para ipakita/itago ang mga fields depende sa piniling Role
roleSelect.addEventListener('change', (e) => {
  const selectedRole = e.target.value;
  if (selectedRole === 'admin') {
    adminFields.style.display = 'block';
    counselorFields.style.display = 'none';
  } else if (selectedRole === 'counselor') {
    adminFields.style.display = 'none';
    counselorFields.style.display = 'block';
  } else {
    adminFields.style.display = 'none';
    counselorFields.style.display = 'none';
  }
});

// 6. Logic kapag pinindot ang "Register User" button
addUserForm.addEventListener('submit', async (e) => {
  e.preventDefault(); // Pigilan ang pag-refresh ng page

  // Kunin ang mga values sa form
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  const role = roleSelect.value;

  messageDiv.style.color = 'blue';
  messageDiv.innerText = "Registering user... Please wait.";

  try {
    // Step A: Gumawa ng account sa Firebase Authentication
    const userCredential = await createUserWithEmailAndPassword(auth, email, password);
    const user = userCredential.user;

    // Step B: Ihanda ang mga data na ise-save sa Firestore Database
    let userData = {
      email: email,
      role: role,
      createdAt: new Date().toISOString()
    };

    // Idagdag ang mga specific na data depende sa role
    if (role === 'admin') {
      userData.department = document.getElementById('department').value;
    } else if (role === 'counselor') {
      userData.specialization = document.getElementById('specialization').value;
      userData.office_location = document.getElementById('office_location').value;
    }

    // Step C: I-save ang user profile sa "users" collection sa Firestore
    await setDoc(doc(db, "users", user.uid), userData);

    // Kapag naging successful ang lahat:
    messageDiv.style.color = 'green';
    messageDiv.innerText = `Success! ${role.toUpperCase()} account created successfully.`;
    
    // Linisin ang form pagkatapos
    addUserForm.reset();
    adminFields.style.display = 'none';
    counselorFields.style.display = 'none';

  } catch (error) {
    // Kapag may error, dito babagsak at ipapakita natin ang totoong dahilan!
    console.error("Firebase Error:", error);
    messageDiv.style.color = 'red';
    
    // Gawing mas madaling intindihin ang mga common Firebase errors
    if (error.code === 'auth/email-already-in-use') {
      messageDiv.innerText = "Error: Ang email na ito ay rehistrado na.";
    } else if (error.code === 'auth/weak-password') {
      messageDiv.innerText = "Error: Masyadong maikli ang password (dapat 6 characters pataas).";
    } else {
      messageDiv.innerText = `Error: ${error.message}`;
    }
  }
});