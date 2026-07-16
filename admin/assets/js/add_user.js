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

// MAG-REGISTER NG ADMIN (Corrected Metadata)
async function createAdminWithTrigger(email, password, department) {
  // Kunin ang username mula sa email (bago mag-@)
  const username = email.split('@')[0];

  const { data, error } = await supabase.auth.signUp({
    email: email,
    password: password,
    options: {
      data: {
        role_type: 'ADMIN', // TAMA: Naka-uppercase para tumugma sa Postgres ENUM
        department: department,
        first_name: 'Admin', // Default metadata para sa database table
        last_name: 'User',
        username: username
      }
    }
  });

  if (error) {
    showStatus("Error: " + error.message, "red");
  } else {
    showStatus("Success! Admin account created. Profile linked automatically.", "green");
    addUserForm.reset();
    adminFields.style.display = 'none';
  }
}

// MAG-REGISTER NG COUNSELOR (Corrected Metadata)
async function createCounselorWithTrigger(email, password, spec, office) {
  // Kunin ang username mula sa email
  const username = email.split('@')[0];

  const { data, error } = await supabase.auth.signUp({
    email: email,
    password: password,
    options: {
      data: {
        role_type: 'COUNSELOR', // TAMA: Naka-uppercase para tumugma sa Postgres ENUM
        specialization: spec,
        office_location: office,
        first_name: 'Counselor',
        last_name: 'User',
        username: username
      }
    }
  });

  if (error) {
    showStatus("Error: " + error.message, "red");
  } else {
    showStatus("Success! Counselor account created. Profile linked automatically.", "green");
    addUserForm.reset();
    counselorFields.style.display = 'none';
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