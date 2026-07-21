import { db, auth } from "./firebase_config.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";
import { collection, getDocs, query, orderBy } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

// Global States
let allCases = [];
let filteredCases = [];
let currentPage = 1;
const itemsPerPage = 10;

// DOM Elements
const casesTableBody = document.getElementById('casesTableBody');
const searchInput = document.getElementById('searchInput');
const priorityFilter = document.getElementById('priorityFilter');
const statusFilter = document.getElementById('statusFilter');
const monthFilter = document.getElementById('monthFilter');
const btnFilter = document.getElementById('btnFilter');
const btnClear = document.getElementById('btnClear');
const paginationContainer = document.getElementById('paginationContainer');

// 1. Auth Guard & Initial Fetch
onAuthStateChanged(auth, async (user) => {
    if (user) {
        await fetchCases();
    } else {
        window.location.href = "../index.html";
    }
});

// 2. Fetch Cases from Firestore
async function fetchCases() {
    try {
        const casesRef = collection(db, "cases");
        const q = query(casesRef, orderBy("created_at", "desc"));
        const querySnapshot = await getDocs(q);

        allCases = [];
        querySnapshot.forEach((doc) => {
            const data = doc.data();
            
            // Format created_at field
            let dateObj = null;
            if (data.created_at && data.created_at.toDate) {
                dateObj = data.created_at.toDate();
            } else if (data.created_at) {
                dateObj = new Date(data.created_at);
            }

            allCases.push({
                docId: doc.id,
                case_id: data.case_id || doc.id,
                system_user_id: data.student_user_id || 'N/A',
                student_id: data.student_id || data.profile_student_id || 'N/A',
                student_name: data.student_name || (data.first_name ? `${data.first_name} ${data.last_name || ''}` : 'N/A'),
                title: data.title || 'N/A',
                priority: data.priority || 'Low',
                status: data.status || 'Pending',
                rawDate: dateObj,
                dateCreated: dateObj ? dateObj.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) : 'N/A',
                monthYear: dateObj ? `${dateObj.getFullYear()}-${String(dateObj.getMonth() + 1).padStart(2, '0')}` : ''
            });
        });

        applyFilters();

    } catch (error) {
        console.error("Error fetching cases:", error);
        casesTableBody.innerHTML = `<tr><td colspan="9" style="color:red; text-align:center; padding: 20px;">Error loading data: ${error.message}</td></tr>`;
    }
}

// 3. Filter Logic (Instant Client-Side Filtering)
function applyFilters() {
    const searchVal = searchInput.value.toLowerCase().trim();
    const priorityVal = priorityFilter.value;
    const statusVal = statusFilter.value;
    const monthVal = monthFilter.value;

    // Is Clear button visible?
    if (searchVal || priorityVal || statusVal || monthVal) {
        btnClear.style.display = 'inline-block';
    } else {
        btnClear.style.display = 'none';
    }

    filteredCases = allCases.filter(item => {
        const matchesSearch = !searchVal || 
            item.student_name.toLowerCase().includes(searchVal) ||
            item.student_id.toLowerCase().includes(searchVal) ||
            item.case_id.toLowerCase().includes(searchVal) ||
            item.title.toLowerCase().includes(searchVal);

        const matchesPriority = !priorityVal || item.priority === priorityVal;
        const matchesStatus = !statusVal || item.status === statusVal;
        const matchesMonth = !monthVal || item.monthYear === monthVal;

        return matchesSearch && matchesPriority && matchesStatus && matchesMonth;
    });

    currentPage = 1;
    renderTable();
}

// 4. Render Table Rows
function renderTable() {
    if (filteredCases.length === 0) {
        casesTableBody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align:center; padding: 40px; color: #8c92a0;">
                    No cases found matching your filters.
                </td>
            </tr>`;
        paginationContainer.innerHTML = '';
        return;
    }

    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageItems = filteredCases.slice(startIndex, endIndex);

    let rowsHTML = '';
    pageItems.forEach(item => {
        // Priority Badge Class
        let priorityClass = 'badge-normal';
        if (['High', 'Urgent', 'Critical'].includes(item.priority)) priorityClass = 'badge-high';
        else if (item.priority === 'Medium') priorityClass = 'badge-progress';

        // Status Badge Class
        let statusClass = 'badge-normal';
        if (['Resolved', 'Closed'].includes(item.status)) statusClass = 'badge-resolved';
        else if (item.status === 'In Progress') statusClass = 'badge-progress';
        else if (item.status === 'Pending') statusClass = 'badge-high';

        rowsHTML += `
            <tr>
                <td><strong>CAS-${escapeHTML(item.case_id)}</strong></td>
                <td>${escapeHTML(item.system_user_id)}</td>
                <td>${escapeHTML(item.student_id)}</td>
                <td>${escapeHTML(item.student_name)}</td>
                <td>${escapeHTML(item.title)}</td>
                <td><span class="badge ${priorityClass}">${escapeHTML(item.priority)}</span></td>
                <td><span class="badge ${statusClass}">${escapeHTML(item.status)}</span></td>
                <td>${item.dateCreated}</td>
                <td class="hide-on-print">
                    <a href="view_case.html?id=${encodeURIComponent(item.docId)}" class="btn-sm">View Details</a>
                </td>
            </tr>
        `;
    });

    casesTableBody.innerHTML = rowsHTML;
    renderPagination();
}

// 5. Render Pagination
function renderPagination() {
    const totalPages = Math.ceil(filteredCases.length / itemsPerPage);
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    let paginationHTML = '';

    if (currentPage > 1) {
        paginationHTML += `<span class="page-link" data-page="${currentPage - 1}">&laquo; Prev</span>`;
    }

    for (let i = 1; i <= totalPages; i++) {
        paginationHTML += `
            <span class="page-link ${i === currentPage ? 'active' : ''}" data-page="${i}">
                ${i}
            </span>
        `;
    }

    if (currentPage < totalPages) {
        paginationHTML += `<span class="page-link" data-page="${currentPage + 1}">Next &raquo;</span>`;
    }

    paginationContainer.innerHTML = paginationHTML;

    // Attach Click Events to Pagination Links
    document.querySelectorAll('.pagination .page-link').forEach(button => {
        button.addEventListener('click', (e) => {
            currentPage = parseInt(e.target.getAttribute('data-page'));
            renderTable();
        });
    });
}

// Helper to prevent XSS
function escapeHTML(str) {
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

// Event Listeners for Filtering
btnFilter.addEventListener('click', applyFilters);
searchInput.addEventListener('input', applyFilters);
priorityFilter.addEventListener('change', applyFilters);
statusFilter.addEventListener('change', applyFilters);
monthFilter.addEventListener('change', applyFilters);

btnClear.addEventListener('click', () => {
    searchInput.value = '';
    priorityFilter.value = '';
    statusFilter.value = '';
    monthFilter.value = '';
    applyFilters();
});