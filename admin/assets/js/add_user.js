import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/+esm';

const supabaseUrl = 'https://misuisycikabaafommxo.supabase.co';
const supabaseAnonKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im1pc3Vpc3ljaWthYmFhZm9tbXhvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODM2ODk0MjksImV4cCI6MjA5OTI2NTQyOX0.jxWDWn7l9KtREst0m2b9bWG8NLaE79IRGCt-dDL6QsE';

const supabase = createClient(supabaseUrl, supabaseAnonKey);

const roleSelect = document.getElementById('role');
const adminFields = document.getElementById('admin-fields');
const counselorFields = document.getElementById('counselor-fields');
const addUserForm = document.getElementById('addUserForm');
const messageDiv = document.getElementById('message');

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

function showStatus(text, color) {
  messageDiv.textContent = text;
  messageDiv.style.color = color;
}

// MAG-REGISTER NG ADMIN
async function createAdminWithTrigger(email, password, department) {
  const { data, error } = await supabase.auth.signUp({
    email: email,
    password: password,
    options: {
      data: {
        role_type: 'admin',
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
        role_type: 'counselor',
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