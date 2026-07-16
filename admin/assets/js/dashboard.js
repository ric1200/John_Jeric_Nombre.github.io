import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/+esm';

// Replace these with your actual Supabase project URL and ANON KEY
const supabaseUrl = 'YOUR_SUPABASE_URL';
const supabaseAnonKey = 'YOUR_SUPABASE_ANON_KEY';
const supabase = createClient(supabaseUrl, supabaseAnonKey);

// ==========================================================
// 1. CLIENT-SIDE AUTH GUARD (Replaces auth_guard.php)
// ==========================================================
async function checkAuth() {
  const { data: { session } } = await supabase.auth.getSession();
  
  // If user is not logged in, boot them to index.html
  if (!session) {
    window.location.href = '../index.html';
    return null;
  }

  // Double check authorization role from login's sessionStorage
  const role = sessionStorage.getItem('role');
  if (role !== 'SUPER_ADMIN' && role !== 'ADMIN') {
    alert("Unauthorized access.");
    window.location.href = '../index.html';
    return null;
  }

  return session.user;
}

// ==========================================================
// 2. DYNAMIC SIDEBAR LOAD (Replaces include 'sidebar.php')
// ==========================================================
async function loadSidebar() {
  const container = document.getElementById('sidebar-container');
  if (!container) return;

  try {
    const response = await fetch('../includes/sidebar.html');
    if (response.ok) {
      container.innerHTML = await response.text();
      setupLogoutButton();
      highlightActiveSidebarLink();
    } else {
      console.error("Failed to load sidebar structure.");
    }
  } catch (err) {
    console.error("Error drawing sidebar component:", err);
  }
}

function setupLogoutButton() {
  const logoutBtn = document.getElementById('logout-btn'); // Ensure your sidebar.html element uses this ID
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      await supabase.auth.signOut();
      sessionStorage.clear();
      window.location.href = '../index.html';
    });
  }
}

function highlightActiveSidebarLink() {
  // Finds links in your sidebar matching current URL pathname and adds 'active' class
  const currentPath = window.location.pathname;
  const links = document.querySelectorAll('#sidebar-container a');
  links.forEach(link => {
    if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').replace('..', ''))) {
      link.classList.add('active');
    }
  });
}

// ==========================================================
// 3. FETCH METRICS (Replaces PHP SELECT COUNT Queries)
// ==========================================================
async function fetchDashboardStats() {
  try {
    // Queries counts concurrently in parallel for optimized load times
    const [
      { count: totalUsers, error: errTotal },
      { count: totalAdmins, error: errAdmins },
      { count: totalStudents, error: errStudents },
      { count: totalCounselors, error: errCounselors }
    ] = await Promise.all([
      supabase.from('users').select('*', { count: 'exact', head: true }),
      supabase.from('users').select('*', { count: 'exact', head: true }).eq('role', 'ADMIN'),
      supabase.from('users').select('*', { count: 'exact', head: true }).eq('role', 'STUDENT'),
      supabase.from('users').select('*', { count: 'exact', head: true }).eq('role', 'COUNSELOR')
    ]);

    if (errTotal || errAdmins || errStudents || errCounselors) {
      throw new Error("Query fetch failed on table: users");
    }

    // Populate elements and remove placeholder skeleton animation classes
    updateStatElement('total-users', totalUsers);
    updateStatElement('total-admins', totalAdmins);
    updateStatElement('total-students', totalStudents);
    updateStatElement('total-counselors', totalCounselors);

  } catch (err) {
    console.error("Database Count Fetch Error:", err);
  }
}

function updateStatElement(id, value) {
  const el = document.getElementById(id);
  if (el) {
    el.textContent = value ?? 0;
    el.classList.remove('loading-placeholder');
  }
}

// ==========================================================
// 4. RECENT SYSTEM ACTIVITY (Replaces Join SQL & Table Render)
// ==========================================================
async function fetchRecentLogs() {
  const tbody = document.getElementById('logs-tbody');
  
  try {
    // Queries audit logs and matches profile username details via Foreign Keys inside Postgres.
    // Ensure audit_logs.user_id points to users.user_id (or profile table) via a foreign key relation.
    const { data: logs, error } = await supabase
      .from('audit_logs')
      .select(`
        created_at,
        action,
        table_name,
        object_id,
        users (
          username
        )
      `)
      .order('created_at', { ascending: false })
      .limit(5);

    if (error) throw error;

    if (!logs || logs.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;">No recent activity.</td></tr>`;
      return;
    }

    tbody.innerHTML = ''; // Clear local placeholder loaders

    logs.forEach(log => {
      // Clean formatted date string logic: "Jul 16, 18:59"
      const dateStr = new Date(log.created_at).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      });

      // Map action rules to CSS styling badges
      let badgeClass = 'badge-info';
      if (log.action.includes('DELETE')) badgeClass = 'badge-danger';
      else if (log.action.includes('CREATE')) badgeClass = 'badge-success';

      const username = log.users?.username || 'System';
      const tableName = log.table_name || 'System Event';
      const objectId = log.object_id ? ` (ID: ${log.object_id})` : '';

      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${dateStr}</td>
        <td><strong>${escapeHtml(username)}</strong></td>
        <td><span class="badge ${badgeClass}">${escapeHtml(log.action)}</span></td>
        <td style="color:#666;">${escapeHtml(tableName)}${escapeHtml(objectId)}</td>
      `;
      tbody.appendChild(row);
    });

  } catch (err) {
    console.error("Audit Logs Error:", err);
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#d9534f;">Failed to retrieve active log trace.</td></tr>`;
  }
}

// Security: Sanitizer to defend against DOM manipulation / XSS
function escapeHtml(str) {
  if (!str) return '';
  return str.toString()
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// ==========================================================
// 5. BOOTSTRAP INITIALIZATION
// ==========================================================
async function init() {
  const user = await checkAuth();
  if (!user) return; // Terminate rendering processes if unauthorized redirect triggered

  // Run DOM components and Supabase queries together
  await Promise.all([
    loadSidebar(),
    fetchDashboardStats(),
    fetchRecentLogs()
  ]);
}

init();