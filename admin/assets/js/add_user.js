import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/+esm';

// Palitan ng iyong totoong Supabase Keys
const supabaseUrl = 'YOUR_SUPABASE_URL';
const supabaseAnonKey = 'YOUR_SUPABASE_ANON_KEY';
const supabase = createClient(supabaseUrl, supabaseAnonKey);

const roleSelect = document.getElementById('role');
const adminFields = document.getElementById('admin-fields');
const counselorFields = document.getElementById('counselor-fields');
const addUserForm = document.getElementById('addUserForm');
const messageDiv = document.getElementById('message');

// ==========================================
// 1. DYNAMIC FIELDS (Show/Hide dependende sa Role)
// ==========================================
roleSelect.addEventListener('change', (e) => {
  const selectedRole = e.target.value;
  if (selectedRole === 'admin') {
    adminFields.style.display = 'block';
    counselorFields.style.display = 'none';
  } else if (selectedRole === 'counselor') {
    adminFields.style.display = 'none';
    counselorFields.style.display = 'block';
  }
});

// ==========================================
// 2. ANG IYONG MGA SUPABASE FUNCTIONS
// ==========================================

// MAG-REGISTER NG ADMIN
async function createAdminWithTrigger(email, password, department) {
  const { data, error } = await supabase.auth.signUp({
    email: email,
    password: password,
    options: {
      data: {
        role_type: 'admin',      // Babasahin ito ng iyong Postgres trigger
        department: department
      }
    }
  });

  if (error) {
    showStatus("Error: " + error.message, "red");
  } else {
    showStatus("Success! Admin account created. Profile linked automatically.", "green");
    addUserForm.reset();
  }
}

// MAG-REGISTER NG COUNSELOR
async function createCounselorWithTrigger(email, password, spec, office) {
  const { data, error } = await supabase.auth.signUp({
    email: email,
    password: password,
    options: {
      data: {
        role_type: 'counselor',  // Babasahin ito ng iyong Postgres trigger
        specialization: spec,
        office_location: office
      }
    }
  });

  if (error) {
    showStatus("Error: " + error.message, "red");
  } else {
    showStatus("Success! Counselor account created. Profile linked automatically.", "green");
    addUserForm.reset();
  }
}

// Helper function para sa success/error status
function showStatus(text, color) {
  messageDiv.textContent = text;
  messageDiv.style.color = color;
}

// ==========================================
// 3. FORM SUBMISSION EVENT LISTENER
// ==========================================
addUserForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  showStatus("Processing registration...", "blue");

  const role = roleSelect.value;
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;

  if (role === 'admin') {
    const department = document.getElementById('department').value || 'General Department';
    await createAdminWithTrigger(email, password, department);
  } else if (role === 'counselor') {
    const spec = document.getElementById('specialization').value || 'General Counselor';
    const office = document.getElementById('office_location').value || 'TBD';
    await createCounselorWithTrigger(email, password, spec, office);
  }
});