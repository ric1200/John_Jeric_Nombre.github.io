import { db } from './firebase-config.js'; // Siguraduhing tama ang path ng firebase config mo
import { collection, getDocs, doc, updateDoc, query, orderBy } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";
import { db } from './firebase-config.js';
// Global States para sa Filtering at Pagination
let allUsers = [];
let filteredUsers = [];
let currentPage = 1;
const limit = 10;

// ==========================================================
// 1. AUTH GUARD LOGIC
// ==========================================================
function checkAuth() {
  // Bypassed pansamantala tulad ng ginawa natin sa dashboard para makapasok ka
  const userRole = sessionStorage.getItem('role') || 'ADMIN'; 
  if (!userRole) {
    window.location.href = '../login.html';
  }
}

// ==========================================================
// 2. SIDEBAR LOADER (May Cache Buster)
// ==========================================================
async function loadSidebar() {
  const container = document.getElementById('sidebar-container');
  if (!container) return;
  try {
    const cacheBuster = new Date().getTime();
    const response = await fetch(`../includes/sidebar.html?v=${cacheBuster}`);
    if (response.ok) {
      container.innerHTML = await response.text();
      // Maaari mong idagdag dito ang click event ng logout button kung kinakailangan
    }
  } catch (err) {
    console.error("Error loading sidebar:", err);
  }
}

// ==========================================================
// 3. FETCH DATA FROM FIRESTORE
// ==========================================================
async function fetchUsers() {
  const tbody = document.getElementById('usersTableBody');
  try {
    const usersRef = collection(db, "users");
    // Naka-order sa pinakabago parang sa PHP script mo (created_at DESC)
    const q = query(usersRef, orderBy("created_at", "desc"));
    const querySnapshot = await getDocs(q);
    
    allUsers = [];
    querySnapshot.forEach((doc) => {
      allUsers.push({
        id: doc.id,
        ...doc.data()
      });
    });

    // Sa unang load, ipantay ang filtered sa lahat ng users
    filteredUsers = [...allUsers];
    renderTable();

  } catch (error) {
    console.error("Error fetching users:", error);
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="empty-state" style="color: red;">
          <i class="fas fa-exclamation-triangle"></i><br>
          Database Error: ${error.message}
        </td>
      </tr>`;
  }
}

// ==========================================================
// 4. FILTER & SEARCH LOGIC (Dating PHP where clauses)
// ==========================================================
window.handleFilters = function() {
  const searchText = document.getElementById('searchInput').value.toLowerCase().trim();
  const selectedRole = document.getElementById('roleFilter').value;
  const selectedDivision = document.getElementById('divisionFilter').value;
  const resetContainer = document.getElementById('resetButtonContainer');

  // I-filter ang global array gamit ang client-side logic
  filteredUsers = allUsers.filter(user => {
    // 1. Text Search Condition (Matches first_name, last_name, email, or username)
    const fullName = `${user.first_name || ''} ${user.last_name || ''}`.toLowerCase();
    const email = (user.email || '').toLowerCase();
    const username = (user.username || '').toLowerCase();
    
    const matchesSearch = !searchText || 
      fullName.includes(searchText) || 
      email.includes(searchText) || 
      username.includes(searchText);

    // 2. Role Dropdown Condition
    const matchesRole = !selectedRole || user.role === selectedRole;

    // 3. Division Dropdown Condition
    const matchesDivision = !selectedDivision || user.division === selectedDivision;

    return matchesSearch && matchesRole && matchesDivision;
  });

  // Ipakita o itago ang Reset Button gaya ng PHP script mo
  if (searchText || selectedRole || selectedDivision) {
    resetContainer.innerHTML = `<button type="button" class="btn btn-reset" onclick="resetFilters()"><i class="fas fa-undo"></i> Reset</button>`;
  } else {
    resetContainer.innerHTML = '';
  }

  currentPage = 1; // Ibalik sa page 1 matapos mag-filter
  renderTable();
}

window.resetFilters = function() {
  document.getElementById('filterForm').reset();
  document.getElementById('resetButtonContainer').innerHTML = '';
  filteredUsers = [...allUsers];
  currentPage = 1;
  renderTable();
}

// ==========================================================
// 5. RENDER TABLE & PAGINATION (Dating HTML/PHP loops)
// ==========================================================
function renderTable() {
  const tbody = document.getElementById('usersTableBody');
  tbody.innerHTML = '';

  if (filteredUsers.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6" class="empty-state">
          <i class="fas fa-folder-open"></i><br>
          No users found matching your filters.
        </td>
      </tr>`;
    document.getElementById('paginationContainer').innerHTML = '';
    return;
  }

  // Pag-calculate ng Index para sa Pagination (LIMIT & OFFSET)
  const offset = (currentPage - 1) * limit;
  const paginatedItems = filteredUsers.slice(offset, offset + limit);
  const totalPages = Math.ceil(filteredUsers.length / limit);

  // Pag-append ng mga Rows sa Table
  paginatedItems.forEach(user => {
    const tr = document.createElement('tr');
    if (user.status === 'INACTIVE') {
      tr.classList.add('row-inactive');
    }

    // Dynamic Badge templates
    const statusBadge = user.status === 'ACTIVE' 
      ? `<span class="badge badge-success"><i class="fas fa-check-circle"></i> Active</span>`
      : `<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Inactive</span>`;

    const deactivateButton = user.status === 'ACTIVE'
      ? `<button class="action-btn btn-delete" onclick="deactivateUser('${user.id}')" title="Deactivate">
            <i class="fas fa-ban"></i> Deactivate
         </button>`
      : '';

    tr.innerHTML = `
      <td class="id-column">#${user.user_id || user.id.slice(0,5)}</td>
      <td>
        <strong>${user.last_name || ''}, ${user.first_name || ''}</strong><br>
        <span class="sub-text">${user.email || ''}</span>
      </td>
      <td>
        <span class="badge badge-role">${user.role || ''}</span><br>
        <span class="sub-text div-text">${user.division || ''}</span>
      </td>
      <td><strong>${user.username || ''}</strong></td>
      <td>${statusBadge}</td>
      <td class="actions-cell">
        <a href="user_form.html?id=${user.id}" class="action-btn btn-edit" title="Edit User">
          <i class="fas fa-edit"></i> Edit
        </a>
        ${deactivateButton}
      </td>
    `;
    tbody.appendChild(tr);
  });

  renderPagination(totalPages);
}

function renderPagination(totalPages) {
  const container = document.getElementById('paginationContainer');
  if (totalPages <= 1) {
    container.innerHTML = '';
    return;
  }

  container.innerHTML = `
    <button class="page-btn ${currentPage <= 1 ? 'disabled' : ''}" ${currentPage <= 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
      <i class="fas fa-chevron-left"></i> Prev
    </button>
    <span class="page-info">Page <strong>${currentPage}</strong> of <strong>${totalPages}</strong></span>
    <button class="page-btn ${currentPage >= totalPages ? 'disabled' : ''}" ${currentPage >= totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
      Next <i class="fas fa-chevron-right"></i>
    </button>
  `;
}

window.changePage = function(pageNumber) {
  currentPage = pageNumber;
  renderTable();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ==========================================================
// 6. DEACTIVATE USER FUNCTION (Dating delete_user.php)
// ==========================================================
window.deactivateUser = async function(docId) {
  if (confirm('Are you sure you want to deactivate this user?')) {
    try {
      const userDocRef = doc(db, "users", docId);
      await updateDoc(userDocRef, {
        status: "INACTIVE"
      });
      alert("User successfully deactivated!");
      
      // I-update ang local data arrays para mag-reflect agad sa screen nang hindi nagre-reload ang page
      allUsers = allUsers.map(user => user.id === docId ? { ...user, status: "INACTIVE" } : user);
      handleFilters(); 
      
    } catch (error) {
      console.error("Error deactivating user:", error);
      alert("Failed to deactivate user: " + error.message);
    }
  }
}

// Run functions on page load
checkAuth();
loadSidebar();
fetchUsers();