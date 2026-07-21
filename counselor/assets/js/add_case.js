import { db, auth } from "./firebase_config.js";
import { onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-auth.js";
import { collection, addDocs, getDocs, query, where, serverTimestamp, addDoc } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

// DOM Elements
const counselorInfo = document.getElementById('counselorInfo');
const studentSelect = document.getElementById('studentSelect');
const addCaseForm = document.getElementById('addCaseForm');
const btnSubmit = document.getElementById('btnSubmit');

const successMsg = document.getElementById('successMsg');
const errorMsg = document.getElementById('errorMsg');
const errorText = document.getElementById('errorText');

let currentCounselorId = null;

// 1. Auth Guard
onAuthStateChanged(auth, async (user) => {
    if (user) {
        currentCounselorId = user.uid;
        counselorInfo.innerText = `Recording as Counselor ID: ${user.uid}`;
        await loadStudents();
    } else {
        window.location.href = "../index.html"; // Balik sa login kapag walang session
    }
});

// 2. Fetch Students from Firestore
async function loadStudents() {
    try {
        const usersRef = collection(db, "users");
        // I-a-assume natin na ang role ay nakasave bilang 'STUDENT' sa users table mo
        const q = query(usersRef, where("role", "==", "STUDENT")); 
        const querySnapshot = await getDocs(q);

        let optionsHTML = '<option value=""></option>';

        querySnapshot.forEach((doc) => {
            const data = doc.data();
            const dbId = doc.id;
            
            const firstName = data.first_name || '';
            const lastName = data.last_name || '';
            const fullName = `${firstName} ${lastName}`.trim() || data.username || 'Unknown User';
            
            // Hanapin ang ID na idi-display (kung may student_id o username na nakasave)
            const displayId = data.student_id || data.username || dbId;

            // Inilagay natin ang pangalan at school ID sa data attributes para madaling kunin mamaya
            optionsHTML += `
                <option value="${dbId}" 
                        data-name="${fullName}" 
                        data-studentid="${displayId}">
                    ${displayId} - ${fullName}
                </option>
            `;
        });

        // I-inject ang options sa select dropdown
        studentSelect.innerHTML = optionsHTML;

        // I-initialize ang Select2 (Searchable Dropdown) gamit ang jQuery
        $(document).ready(function() {
            $('#studentSelect').select2({
                placeholder: "Search Student ID or Name",
                allowClear: true
            });
        });

    } catch (error) {
        console.error("Error loading students:", error);
        studentSelect.innerHTML = '<option value="">Error loading students</option>';
    }
}

// 3. Handle Form Submission
addCaseForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Reset Messages
    successMsg.style.display = 'none';
    errorMsg.style.display = 'none';
    
    // Kunin ang napiling student sa Select2 dropdown
    const selectedOption = studentSelect.options[studentSelect.selectedIndex];
    const studentDbId = selectedOption.value;

    if (!studentDbId) {
        showError("Please select a student.");
        return;
    }

    // Kunin ang mga value galing sa form
    const studentName = selectedOption.getAttribute('data-name');
    const studentSchoolId = selectedOption.getAttribute('data-studentid');
    const title = document.getElementById('titleInput').value.trim();
    const priority = document.getElementById('prioritySelect').value;
    const description = document.getElementById('descInput').value.trim();
    const status = document.getElementById('statusSelect').value;

    // Loading State
    const originalBtnText = btnSubmit.innerHTML;
    btnSubmit.innerHTML = "Saving...";
    btnSubmit.disabled = true;

    try {
        // I-save sa 'cases' collectionas
        const docRef = await addDoc(collection(db, "cases"), {
            counselor_id: currentCounselorId,
            student_user_id: studentDbId,
            student_id: studentSchoolId, // Denormalized data para sa mabilis na pag-display
            student_name: studentName,   // Denormalized data para sa mabilis na pag-display
            title: title,
            description: description,
            priority: priority,
            status: status,
            created_at: serverTimestamp() // Firestore timestamp
        });

        // I-update natin ang doc para magkaroon din siya ng 'case_id' field gaya ng nasa lumang code mo
        // Gumagawa tayo ng unique CASE ID gamit ang first 6 characters ng document ID
        const shortId = docRef.id.substring(0, 6).toUpperCase();
        
        // Success
        successMsg.style.display = 'block';
        addCaseForm.reset();
        
        // I-reset ang Select2 field
        $('#studentSelect').val(null).trigger('change');

        // Optional: I-scroll pababa papunta sa success message
        window.scrollTo({ top: 0, behavior: 'smooth' });

    } catch (error) {
        console.error("Error saving case:", error);
        showError(error.message);
    } finally {
        // Ibalik sa normal ang button
        btnSubmit.innerHTML = originalBtnText;
        btnSubmit.disabled = false;
    }
});

function showError(message) {
    errorText.innerText = message;
    errorMsg.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}