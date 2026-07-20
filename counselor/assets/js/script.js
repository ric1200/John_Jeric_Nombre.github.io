document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const errorMessage = document.getElementById("error-message");

    // ==========================================
    // 1. URL Parameter Check (PHP $_GET Replacement)
    // ==========================================
    // This allows you to append ?error=invalid or ?error=empty to the URL 
    // to simulate the old PHP redirect behavior.
    const urlParams = new URLSearchParams(window.location.search);
    const errorParam = urlParams.get('error');

    if (errorParam === 'invalid') {
        showError("Invalid username or password.");
    } else if (errorParam === 'empty') {
        showError("Please fill in all fields.");
    }

    // ==========================================
    // 2. Form Submission Handling (PHP POST Replacement)
    // ==========================================
    loginForm.addEventListener("submit", (e) => {
        // Prevent the browser from refreshing the page
        e.preventDefault(); 

        const username = document.getElementById("username").value.trim();
        const password = document.getElementById("password").value.trim();

        // Error: Empty fields
        if (!username || !password) {
            showError("Please fill in all fields.");
            return;
        }

        // Simulated Database Check (Replace this later with Firebase or Fetch API)
        // We will hardcode user: "admin" and password: "password123" for testing.
        if (username === "admin" && password === "password123") {
            // Success
            errorMessage.style.display = "none";
            alert("Login successful! Welcome to the Counselor Dashboard.");
            // window.location.href = "dashboard.html"; // Redirect to your actual dashboard
        } else {
            // Failure
            showError("Invalid username or password.");
        }
    });

    // Helper function to display the error text and make it visible
    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = "block";
    }
});