// Import the Supabase client from CDN
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/+esm';

// Naka-hardcode na ang tamang credentials mo para mabasa ng GitHub Pages
const supabaseUrl = 'https://misuisycikabaafommxo.supabase.co';
const supabaseAnonKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im1pc3Vpc3ljaWthYmFhZm9tbXhvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODM2ODk0MjksImV4cCI6MjA5OTI2NTQyOX0.jxWDWn7l9KtREst0m2b9bWG8NLaE79IRGCt-dDL6QsE';

const supabase = createClient(supabaseUrl, supabaseAnonKey);

// Kunin ang mga HTML Elements
const loginForm = document.getElementById('loginForm');
const errorDiv = document.getElementById('error-message');
const loginBtn = document.getElementById('loginBtn');
const passwordInput = document.getElementById('password');
const showPasswordCheckbox = document.getElementById('showPassword');

// Helper function to log audit events
async function logAudit(userId, action, detailsObj) {
  try {
    await supabase.from('audit_logs').insert([
      {
        user_id: userId,
        action: action,
        details: detailsObj,
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

// ==========================================
// HAKBANG 1: LOGIN FORM SUBMISSION
// ==========================================
loginForm.addEventListener('submit', async (e) => {
  e.preventDefault(); // Pigilan ang pag-refresh ng page
  
  errorDiv.style.display = 'none';
  loginBtn.textContent = 'Logging in...';
  loginBtn.disabled = true;

  const email = document.getElementById('username').value.trim();
  const password = passwordInput.value;

  if (!email || !password) {
    showError("Please enter email and password.");
    return;
  }

  // 1. I-authenticate ang user sa Supabase Auth
  const { data: authData, error: authError } = await supabase.auth.signInWithPassword({
    email: email,
    password: password
  });

  // KUNG SUMABLAY ANG AUTHENTICATION (Maling Password, o hindi pa confirmed ang Email)
  if (authError) {
    await logAudit(null, 'LOGIN_FAILED', {
      attempted_email: email,
      error_reason: authError.message
    });
    // IPAPAKITA NATIN ANG TOTOONG REKLAMO NG SUPABASE PARA MADALING I-DEBUG
    showError("Login Error: " + authError.message);
    return;
  }

  const user = authData.user;

  // 2. I-check kung nage-exist ang user sa admin_profiles gamit ang TAMA at BAGONG Column Names
  const { data: adminProfile, error: profileError } = await supabase
    .from('admin_profiles')
    .select('department, admin_level') // TAMA: 'admin_level' na ang column sa bagong schema mo
    .eq('user_id', user.id)            // TAMA: 'user_id' na ang katapat ng user.id ngayon
    .single();

  if (profileError || !adminProfile) {
    await logAudit(user.id, 'LOGIN_FAILED_UNAUTHORIZED', {
      email: email,
      reason: 'User exists but has no Admin Profile.'
    });
    
    await supabase.auth.signOut();
    showError("Unauthorized access. You do not have admin privileges.");
    return;
  }

  // 3. SUCCESSFUL LOGIN
  await logAudit(user.id, 'LOGIN_SUCCESS', {
    message: 'Admin logged in successfully',
    email: email,
    access_level: adminProfile.admin_level
  });

  // I-save ang detalye sa Session Storage gamit ang tamang columns
  sessionStorage.setItem('role', adminProfile.admin_level);
  sessionStorage.setItem('division', adminProfile.department);

  // Redirect papuntang dashboard
  window.location.href = '../sysad/dashboard.html'; 
});


// ==========================================
// HAKBANG 2: SHOW/HIDE PASSWORD
// ==========================================
if (showPasswordCheckbox) {
  showPasswordCheckbox.addEventListener('change', function() {
    if (this.checked) {
      passwordInput.type = 'text'; // Ipapakita ang password
    } else {
      passwordInput.type = 'password'; // Itatago ulit ang password
    }
  });
}