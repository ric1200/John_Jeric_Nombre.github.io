// Import the Supabase client from CDN
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/+esm';

// Replace these with your actual Supabase project URL and ANON KEY
const supabaseUrl = 'YOUR_SUPABASE_URL';
const supabaseAnonKey = 'YOUR_SUPABASE_ANON_KEY';
const supabase = createClient(supabaseUrl, supabaseAnonKey);

const loginForm = document.getElementById('loginForm');
const errorDiv = document.getElementById('error-message');
const loginBtn = document.getElementById('loginBtn');

// Helper function to log audit events
async function logAudit(userId, action, detailsObj) {
  try {
    await supabase.from('audit_logs').insert([
      {
        user_id: userId,
        action: action,
        details: detailsObj,
        // Note: Getting IP address via pure client-side JS requires a 3rd party API.
        // We will leave it null here, or you can capture it in Postgres using default functions.
      }
    ]);
  } catch (error) {
    console.error("Audit Log Error:", error);
  }
}

// Function to display errors in the UI
function showError(message) {
  errorDiv.textContent = message;
  errorDiv.style.display = 'block';
  loginBtn.textContent = 'Login';
  loginBtn.disabled = false;
}

loginForm.addEventListener('submit', async (e) => {
  e.preventDefault(); // Prevent page reload
  
  errorDiv.style.display = 'none';
  loginBtn.textContent = 'Logging in...';
  loginBtn.disabled = true;

  const email = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value;

  if (!email || !password) {
    showError("Please enter email and password.");
    return;
  }

  // 1. Authenticate with Supabase Auth
  const { data: authData, error: authError } = await supabase.auth.signInWithPassword({
    email: email,
    password: password
  });

  if (authError) {
    await logAudit(null, 'LOGIN_FAILED', {
      attempted_email: email,
      error_reason: authError.message
    });
    showError("Unauthorized access. Check your email and password.");
    return;
  }

  const user = authData.user;

  // 2. Check if user exists in admin_profiles
  const { data: adminProfile, error: profileError } = await supabase
    .from('admin_profiles')
    .select('department, access_level')
    .eq('id', user.id)
    .single();

  if (profileError || !adminProfile) {
    // Valid Supabase user, but NOT an admin
    await logAudit(user.id, 'LOGIN_FAILED_UNAUTHORIZED', {
      email: email,
      reason: 'User exists but is not an Admin.'
    });
    
    // Sign them out immediately since they aren't authorized
    await supabase.auth.signOut();
    showError("Unauthorized access. You do not have admin privileges.");
    return;
  }

  // 3. SUCCESSFUL LOGIN & AUTHORIZED ADMIN
  await logAudit(user.id, 'LOGIN_SUCCESS', {
    message: 'Admin logged in successfully',
    email: email,
    access_level: adminProfile.access_level
  });

  // Store profile data in sessionStorage (similar to PHP $_SESSION)
  // Note: Supabase automatically stores the access token securely.
  sessionStorage.setItem('role', adminProfile.access_level);
  sessionStorage.setItem('division', adminProfile.department);

  // Redirect to dashboard (Make sure dashboard is .html, not .php!)
  window.location.href = '/sysad/dashboard.html'; 
});