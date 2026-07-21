import { auth } from "./firebase_config.js";
import { signOut } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";

async function loadHeader() {
    const headerPlaceholder = document.getElementById('header-placeholder');
    if (!headerPlaceholder) return;

    try {
        // 1. I-fetch ang laman ng header.html mula sa includes folder
        const response = await fetch('../includes/header.html');
        if (!response.ok) throw new Error("Failed to load header.html");
        
        const headerHTML = await response.text();
        headerPlaceholder.innerHTML = headerHTML;

        // 2. Alamin kung anong page ang kasalukuyang nakabukas
        const currentPath = window.location.pathname.split("/").pop() || "dashboard.html";

        // 3. I-highlight ang Active Link
        const links = document.querySelectorAll('.nav-links a');
        links.forEach(link => {
            if (link.getAttribute('data-page') === currentPath) {
                link.classList.add('active');
            }
        });

        // 4. Mobile Menu Toggle Logic
        const navToggle = document.getElementById('navToggle');
        const navLinks = document.getElementById('navLinks');
        if (navToggle && navLinks) {
            navToggle.addEventListener('click', () => {
                navLinks.classList.toggle('show');
            });
        }

        // 5. Firebase Logout Logic
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                const confirmLogout = confirm("Are you sure you want to logout?");
                if (confirmLogout) {
                    try {
                        await signOut(auth);
                        sessionStorage.clear();
                        window.location.href = "../index.html"; // Redirect pabalik sa login screen
                    } catch (error) {
                        console.error("Logout Error:", error);
                        alert("Failed to log out. Please try again.");
                    }
                }
            });
        }

    } catch (error) {
        console.error("Error loading header:", error);
    }
}

// Patakbuhin ang function kapag handa na ang pahina
document.addEventListener('DOMContentLoaded', loadHeader);