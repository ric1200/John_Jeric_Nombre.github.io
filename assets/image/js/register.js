// register.js
import { getAuth, createUserWithEmailAndPassword } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";
import { getFirestore, doc, setDoc } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";
import { app } from "./firebase-config.js"; // Siguraduhing tama ang path ng config mo

const auth = getAuth(app);
const db = getFirestore(app);

const registerForm = document.getElementById("registerForm");

registerForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    const studentId = document.getElementById("studentId").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    // 🚨 1. DOMAIN VALIDATION: I-check kung PHINMA email
    // Pwede mo rin idagdag ang '@student.phinmaed.com' kung sakaling magkaiba ang domain ng students at admin
    if (!email.endsWith("@phinmaed.com")) {
        alert("Registration failed: Tanging @phinmaed.com emails lamang ang pinapayagan.");
        return; // Ihihinto nito ang code, hindi itutuloy sa Firebase
    }

    try {
        // 🛠️ 2. CREATE USER SA FIREBASE AUTH
        const userCredential = await createUserWithEmailAndPassword(auth, email, password);
        const user = userCredential.user;

        // 🗂️ 3. I-SAVE ANG STUDENT ID (USERNAME) AT ROLE SA FIRESTORE
        await setDoc(doc(db, "users", user.uid), {
            username: studentId,    // Ang Student ID ang magiging username niya
            email: email,
            role: "STUDENT",        // Naka-set agad as student
            status: "ACTIVE",       // Pwedeng gawing PENDING kung gusto mo i-approve muna ng admin
            createdAt: new Date().toISOString()
        });

        alert("Registration Successful! Pwede ka na mag-login.");
        window.location.href = "../index.html"; // I-redirect sa login page

    } catch (error) {
        console.error("Error during registration:", error);
        alert("Registration Error: " + error.message);
    }
});