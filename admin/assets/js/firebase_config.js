import { initializeApp } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-app.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-auth.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/12.16.0/firebase-firestore.js";

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

export { app, auth, db };