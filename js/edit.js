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

    fetch('php/add_student.php', {
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

    fetch('php/add_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: studentId, delete_student: true })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) el.parentElement.remove();
        else showToast(data.message || 'Failed to delete student', 'error');
    });
}

function promptAddMember(groupId) {
    const schoolId = prompt('Enter student school ID(s), comma-separated:');
    if (!schoolId) return;

    fetch('php/add_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ school_ids: schoolId, group_id: groupId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else showToast(data.message || 'Failed to add student', 'error');
    });
}

function deleteGroup(groupId, el) {
    if (!confirm('Are you sure you want to delete this group?')) return;

    fetch('php/add_student.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ delete_group_id: groupId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) el.closest('.group-item').remove();
        else showToast(data.message || 'Failed to delete group', 'error');
    });
}

function buildFullName() {
    const ln = document.getElementById('editLastname')?.value.trim() || '';
    const fn = document.getElementById('editFirstname')?.value.trim() || '';
    const mn = document.getElementById('editMiddlename')?.value.trim() || '';
    const full = [fn, mn, ln].filter(Boolean).join(' ');
    const display = document.querySelector('.profile-info h3');
    if (display) display.textContent = full;
}

const editBtn = document.getElementById("editToggle");
const editModal = document.getElementById("editModal");
const cancelEdit = document.getElementById("cancelEdit");

const departmentInput = document.getElementById("department");
const emailInput = document.getElementById("email");
const addressInput = document.getElementById("address");

if (editBtn) {
    editBtn.addEventListener("click", (e) => {
        e.preventDefault();
        editModal.style.display = "flex";

        const emailDisplay = document.getElementById("displayEmail");
        const addressDisplay = document.getElementById("displayAddress");
        const departmentDisplay = document.getElementById("displayDepartment");

        if (emailInput) emailInput.value = emailDisplay?.value || "";
        if (addressInput) addressInput.value = addressDisplay?.value || "";
        if (departmentInput) departmentInput.value = departmentDisplay?.value || "";
    });
}

if (cancelEdit) cancelEdit.addEventListener("click", () => (editModal.style.display = "none"));

window.addEventListener("click", (e) => {
    if (e.target === editModal) editModal.style.display = "none";
});

const genderRadios = document.querySelectorAll('input[name="gender"]');

window.addEventListener("DOMContentLoaded", () => {
    const lastnameInput  = document.getElementById('editLastname');
    const firstnameInput = document.getElementById('editFirstname');
    const middleInput    = document.getElementById('editMiddlename');

    [lastnameInput, firstnameInput, middleInput].forEach(el => {
        if (el) el.addEventListener('input', buildFullName);
    });

    genderRadios.forEach((radio) => {
        if (radio.dataset.saved) radio.checked = true;
    });
});

genderRadios.forEach((radio) => {
    radio.addEventListener("change", () => {
        genderRadios.forEach(r => r.removeAttribute("data-saved"));
        radio.setAttribute("data-saved", "true");
    });
});

const editForm = document.getElementById("editForm");

if (editForm) {
    editForm.addEventListener("submit", (e) => {
        try {
            const emailDisplay = document.getElementById("displayEmail");
            const addressDisplay = document.getElementById("displayAddress");
            const departmentDisplay = document.getElementById("displayDepartment");

            if (emailDisplay && emailInput) emailDisplay.value = emailInput.value.trim();
            if (addressDisplay && addressInput) addressDisplay.value = addressInput.value.trim();
            if (departmentDisplay && departmentInput) departmentDisplay.value = departmentInput.value.trim();

            showToast("Personal Info Updated Successfully", "success");
        } catch (err) {
            e.preventDefault();
            showToast("An error occurred.", "error");
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

function showToast(text, type = "success") {
    const existing = document.querySelector(".toast-notif");
    if (existing) existing.remove();

    const toast = document.createElement("div");
    toast.className = `toast-notif toast-${type}`;
    toast.textContent = text;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        requestAnimationFrame(() => toast.classList.add("toast-show"));
    });

    setTimeout(() => {
        toast.classList.remove("toast-show");
        toast.addEventListener("transitionend", () => toast.remove());
    }, 4000);
}

function showModalError(message) {
    const msg = document.getElementById("modalErrorMsg");
    msg.innerText = message;
    msg.style.display = "block";
    setTimeout(() => {
        msg.style.display = "none";
        msg.innerText = "";
    }, 5000);
}

const changePasswordForm = document.getElementById("changePasswordForm");

if (changePasswordForm) {
    changePasswordForm.addEventListener("submit", (e) => {
        e.preventDefault();
        const current = document.getElementById("currentPassword").value;
        const newPass = document.getElementById("newPassword").value;
        const confirm = document.getElementById("confirmPassword").value;

        fetch("php/change_password.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "current_password=" + encodeURIComponent(current) +
                  "&new_password="     + encodeURIComponent(newPass) +
                  "&confirm_password=" + encodeURIComponent(confirm)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                closeModal();
                showToast(data.message, "success");
                changePasswordForm.reset();
            } else {
                showToast(data.message, "error");
            }
        })
        .catch(() => showToast("An error occurred. Please try again.", "error"));
    });
}

function submitGroupAssignment() {
    const students  = document.getElementById("modalStudentIds").value;
    const leaderId  = document.getElementById("modalLeaderId").value;
    const adviserId = document.getElementById("modalAdviserId").value;
    const groupId   = document.getElementById("modalGroupSelect").value;
    const newGroup  = document.getElementById("modalNewGroup").value;

    fetch("php/assign_group_roles.php", {
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
        if (data.status === "error") showModalError(data.message);
        else setTimeout(() => location.reload(), 300);
    });
}