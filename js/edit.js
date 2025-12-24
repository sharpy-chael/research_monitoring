function openAddStudentModal() {
    document.getElementById('addStudentModal').style.display = 'flex';
}

function closeAddStudentModal() {
    document.getElementById('addStudentModal').style.display = 'none';
}

function addStudentsFromModal() {
    const ids = document.getElementById('modalStudentIds').value.trim();
    let group_id = document.getElementById('modalGroupSelect').value;
    const newGroup = document.getElementById('modalNewGroup').value.trim();
    if (!ids && !newGroup) return;

    fetch('add_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ school_ids: ids, group_id: group_id, new_group: newGroup })
    })
    .then(res => res.json())
    .then(data => {
        const msgContainer = document.getElementById('add-student-msg');
        msgContainer.textContent = data.message || 'Action completed';

        if (data.success && data.message.startsWith("Added")) {
            msgContainer.style.borderColor = "#2ecc71";
            msgContainer.style.background = "#e9f9f0";
            msgContainer.style.color = "#27ae60";
            setTimeout(() => location.reload(), 500);
        } else {
            msgContainer.style.borderColor = "#e74c3c";
            msgContainer.style.background = "#fceae9";
            msgContainer.style.color = "#c0392b";
        }

        msgContainer.classList.add('show');
        setTimeout(() => msgContainer.classList.remove('show'), 5000);
    })
    .catch(console.error);
}

function toggleMembers(el) {
    const list = el.closest('.group-item').querySelector('.members-list');
    list.style.display = (list.style.display === '' || list.style.display === 'none') ? 'flex' : 'none';
}

function deleteMember(studentId, el) {
    if (!confirm('Delete this member?')) return;

    fetch('add_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: studentId, delete_student: true })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) el.parentElement.remove();
        else alert(data.message || 'Failed to delete student');
    });
}

function promptAddMember(groupId) {
    const schoolId = prompt('Enter student school ID(s), comma-separated:');
    if (!schoolId) return;

    fetch('add_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ school_ids: schoolId, group_id: groupId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.message || 'Failed to add student');
    });
}

function deleteGroup(groupId, el) {
    if (!confirm('Are you sure you want to delete this group?')) return;

    fetch('add_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ delete_group_id: groupId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) el.closest('.group-item').remove();
        else alert(data.message || 'Failed to delete group');
    });
}

const editBtn = document.getElementById("editToggle");
const editModal = document.getElementById("editModal");
const cancelEdit = document.getElementById("cancelEdit");

const advisorIDInput = document.getElementById("advisorID");
const departmentInput = document.getElementById("department");
const emailInput = document.getElementById("email");
const addressInput = document.getElementById("address");

const _userId = typeof userId !== "undefined" && userId ? userId : "";
const key = (base) => `${base}_${_userId}`;


if (editBtn) {
    editBtn.addEventListener("click", (e) => {
        e.preventDefault();
        editModal.style.display = "flex";

        const emailDisplay = document.getElementById("displayEmail");
        const addressDisplay = document.getElementById("displayAddress");
        const advisorDisplay = document.getElementById("displayAdvisorID");
        const departmentDisplay = document.getElementById("displayDepartment");

        if (emailInput) emailInput.value = localStorage.getItem(key("studentEmail")) || emailDisplay?.value || "";
        if (addressInput) addressInput.value = localStorage.getItem(key("studentAddress")) || addressDisplay?.value || "";
        if (advisorIDInput) advisorIDInput.value = localStorage.getItem(key("advisorID")) || advisorDisplay?.value || "";
        if (departmentInput) departmentInput.value = localStorage.getItem(key("advisorDepartment")) || departmentDisplay?.value || "";
    });
}


if (cancelEdit) cancelEdit.addEventListener("click", () => (editModal.style.display = "none"));

window.addEventListener("click", (e) => {
    if (e.target === editModal) editModal.style.display = "none";
});


const genderRadios = document.querySelectorAll('input[name="gender"]');

window.addEventListener("DOMContentLoaded", () => {
    const advisorDisplay = document.getElementById("displayAdvisorID");
    const departmentDisplay = document.getElementById("displayDepartment");

    const savedAdvisorID = localStorage.getItem(key("advisorID"));
    const savedDepartment = localStorage.getItem(key("advisorDepartment"));

    if (advisorDisplay && savedAdvisorID !== null) {
        advisorDisplay.value = savedAdvisorID;
    }
    if (departmentDisplay && savedDepartment !== null) {
        departmentDisplay.value = savedDepartment;
    }

    const savedGender = localStorage.getItem(key("selectedGender"));
    if (savedGender) {
        genderRadios.forEach((radio) => (radio.checked = radio.value === savedGender));
    }
    const emailDisplay = document.getElementById("displayEmail");
    const addressDisplay = document.getElementById("displayAddress");

    const savedEmail = localStorage.getItem(key("studentEmail"));
    const savedAddress = localStorage.getItem(key("studentAddress"));

    if (emailDisplay && savedEmail !== null) {
        emailDisplay.value = savedEmail;
    }
    if (addressDisplay && savedAddress !== null) {
        addressDisplay.value = savedAddress;
    }
});

genderRadios.forEach((radio) => {
    radio.addEventListener("change", () => localStorage.setItem(key("selectedGender"), radio.value));
});


const editForm = document.getElementById("editForm");

if (editForm) {
    editForm.addEventListener("submit", (e) => {
        try {
            if (emailInput) localStorage.setItem(key("studentEmail"), emailInput.value.trim());
            if (addressInput) localStorage.setItem(key("studentAddress"), addressInput.value.trim());
            if (advisorIDInput) localStorage.setItem(key("advisorID"), advisorIDInput.value.trim());
            if (departmentInput) localStorage.setItem(key("advisorDepartment"), departmentInput.value.trim());

            const emailDisplay = document.getElementById("displayEmail");
            const addressDisplay = document.getElementById("displayAddress");
            const advisorDisplay = document.getElementById("displayAdvisorID");
            const departmentDisplay = document.getElementById("displayDepartment");

            if (emailDisplay) emailDisplay.value = emailInput.value.trim();
            if (addressDisplay) addressDisplay.value = addressInput.value.trim();
            if (advisorDisplay) advisorDisplay.value = advisorIDInput.value.trim();
            if (departmentDisplay) departmentDisplay.value = departmentInput.value.trim();

            showGlobalMessage("success", "Personal Info Updated Successfully");
        } catch (err) {
            e.preventDefault();
            showGlobalMessage("error", "An error occurred.");
        }
    });
}


const newProfileInput = document.getElementById("newProfileImage");
const editProfileImg = document.getElementById("editProfileImage");

if (newProfileInput && editProfileImg) {
    newProfileInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (ev) => (editProfileImg.src = ev.target.result);
            reader.readAsDataURL(file);
        }
    });
}

const changePasswordBtn = document.getElementById("changePasswordBtn");
const changePasswordModal = document.getElementById("changePassword");

if (changePasswordBtn) {
    changePasswordBtn.addEventListener("click", (e) => {
        e.preventDefault();
        changePasswordModal.style.display = "flex";
    });
}

window.addEventListener("click", (e) => {
    if (e.target === changePasswordModal) changePasswordModal.style.display = "none";
});

function closeModal() {
    if (editModal) editModal.style.display = "none";
    if (changePasswordModal) changePasswordModal.style.display = "none";
}

function showGlobalMessage(type, text) {
    const msg = document.getElementById("globalMessage");
    if (!msg) return;

    msg.textContent = text;
    msg.className = `global-message ${type} show`;

    setTimeout(() => msg.classList.remove("show"), 5000);
}

function showModalError(message){
    const msg = document.getElementById("modalErrorMsg");
    msg.innerText = message;
    msg.style.display = "block";

    setTimeout(() => {
        msg.style.display = "none";
        msg.innerText = "";
    }, 5000);
}

function submitGroupAssignment(){
    const students  = document.getElementById("modalStudentIds").value;
    const leaderId  = document.getElementById("modalLeaderId").value;
    const adviserId = document.getElementById("modalAdviserId").value;
    const groupId   = document.getElementById("modalGroupSelect").value;
    const newGroup  = document.getElementById("modalNewGroup").value;

    fetch("assign_group_roles.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body:
            "students=" + encodeURIComponent(students) +
            "&leader_id=" + encodeURIComponent(leaderId) +
            "&adviser_id=" + encodeURIComponent(adviserId) +
            "&group_id=" + encodeURIComponent(groupId) +
            "&new_group=" + encodeURIComponent(newGroup)
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === "error"){
            showModalError(data.message);
        } else {
            setTimeout(() => location.reload(), 300);

        }
    });
}


