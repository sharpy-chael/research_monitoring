function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    const iconMap = { success: 'ri-checkbox-circle-line', error: 'ri-error-warning-line', warning: 'ri-alert-line' };
    toast.innerHTML = `<i class="${iconMap[type] || iconMap.success}"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function showConfirmToast(message, confirmLabel = 'Confirm', danger = false) {
    return new Promise(resolve => {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:30000;display:flex;align-items:center;justify-content:center;';
        overlay.innerHTML = `
            <div style="background:#fff;padding:26px 30px;border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,.22);min-width:290px;max-width:400px;text-align:center;">
                <p style="margin:0 0 20px;font-size:15px;color:#333;line-height:1.5;">${message}</p>
                <div style="display:flex;gap:10px;justify-content:center;">
                    <button id="cfmNo" style="padding:9px 22px;border:1px solid #ddd;border-radius:6px;cursor:pointer;background:#f5f5f5;color:#555;font-size:14px;font-weight:600;">Cancel</button>
                    <button id="cfmYes" style="padding:9px 22px;border:none;border-radius:6px;cursor:pointer;background:${danger ? '#dc3545' : '#198754'};color:#fff;font-size:14px;font-weight:600;">${confirmLabel}</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
        overlay.querySelector('#cfmYes').onclick = () => { overlay.remove(); resolve(true); };
        overlay.querySelector('#cfmNo').onclick = () => { overlay.remove(); resolve(false); };
    });
}

async function addItem(type) {
    const inputId = type === 'sdg' ? 'newSdgName' : 'newThrustName';
    const name = document.getElementById(inputId).value.trim();
    if (!name) { showToast('Please enter a name', 'error'); return; }
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('type', type);
    formData.append('name', name);
    try {
        const res = await fetch('php/manage_sdg_thrust.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            const grid = document.getElementById(type === 'sdg' ? 'sdgGrid' : 'thrustGrid');
            const card = document.createElement('div');
            card.className = 'item-card';
            card.dataset.id = data.id;
            card.innerHTML = `<span class="item-name">${name}</span><i class="ri-delete-bin-line delete-icon" onclick="deleteItem('${type}', ${data.id}, this)"></i>`;
            grid.appendChild(card);
            document.getElementById(inputId).value = '';
            showToast(`${type === 'sdg' ? 'SDG' : 'Research Thrust'} added successfully!`, 'success');
        } else {
            showToast(data.message || 'Failed to add item', 'error');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    }
}

async function deleteItem(type, id, elem) {
    const confirmed = await showConfirmToast(`Delete this ${type === 'sdg' ? 'SDG' : 'Research Thrust'}?`, 'Delete', true);
    if (!confirmed) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('type', type);
    formData.append('id', id);
    try {
        const res = await fetch('php/manage_sdg_thrust.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            elem.closest('.item-card').remove();
            showToast(`${type === 'sdg' ? 'SDG' : 'Research Thrust'} deleted successfully!`, 'success');
        } else {
            showToast(data.message || 'Failed to delete item', 'error');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    }
}

let currentAssignContext = { type: null, groupId: null };

async function openAssignModal(type, groupId) {
    currentAssignContext = { type, groupId };
    const modal = document.getElementById('assignModal');
    document.getElementById('assignModalTitle').textContent = `Assign ${type === 'sdg' ? 'UN SDG' : 'Research Thrust'}`;
    const checkboxContainer = document.getElementById('assignCheckboxes');
    const formData = new FormData();
    formData.append('action', 'get_available');
    formData.append('type', type);
    formData.append('group_id', groupId);
    try {
        const res = await fetch('php/manage_sdg_thrust.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            checkboxContainer.innerHTML = '';
            if (data.items.length === 0) {
                checkboxContainer.innerHTML = '<p style="text-align:center;color:#999;padding:20px;">No available items to assign. Create some first!</p>';
            } else {
                data.items.forEach(item => {
                    const label = document.createElement('label');
                    label.style.cssText = 'display:block;padding:8px;cursor:pointer;';
                    label.innerHTML = `<input type="checkbox" value="${item.id}" style="margin-right:8px;">${item.name}`;
                    checkboxContainer.appendChild(label);
                });
            }
        }
    } catch (e) {}
    modal.classList.add('show');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.remove('show');
    currentAssignContext = { type: null, groupId: null };
}

async function submitAssignment() {
    const checkboxes = document.querySelectorAll('#assignCheckboxes input[type="checkbox"]:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    if (ids.length === 0) { showToast('Please select at least one item', 'error'); return; }
    const formData = new FormData();
    formData.append('action', 'assign');
    formData.append('type', currentAssignContext.type);
    formData.append('group_id', currentAssignContext.groupId);
    formData.append('ids', JSON.stringify(ids));
    try {
        const res = await fetch('php/manage_sdg_thrust.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            showToast('Assignment successful!', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Failed to assign', 'error');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    }
}

async function removeAssignment(type, groupId, itemId, elem) {
    const confirmed = await showConfirmToast(`Remove this ${type === 'sdg' ? 'SDG' : 'Research Thrust'} assignment?`, 'Remove', true);
    if (!confirmed) return;
    const formData = new FormData();
    formData.append('action', 'remove_assignment');
    formData.append('type', type);
    formData.append('group_id', groupId);
    formData.append('item_id', itemId);
    try {
        const res = await fetch('php/manage_sdg_thrust.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            elem.closest('.tag-compact, .tag').remove();
            showToast('Assignment removed successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to remove assignment', 'error');
        }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    }
}

function toggleGroupDetails(icon) {
    const card = icon.closest('.group-card');
    const details = card.querySelector('.group-details');
    icon.classList.toggle('expanded');
    details.classList.toggle('expanded');
}

function toggleMenu(event, element) {
    event.stopPropagation();
    const menu = element.nextElementSibling;
    const card = element.closest('.upload-card');
    document.querySelectorAll('.menu-dropdown').forEach(m => { if (m !== menu) { m.classList.remove('show', 'position-top'); } });
    document.querySelectorAll('.upload-card').forEach(c => { if (c !== card) c.classList.remove('menu-open'); });
    const isOpening = !menu.classList.contains('show');
    menu.classList.toggle('show');
    if (isOpening) {
        card.classList.add('menu-open');
        setTimeout(() => {
            const rect = menu.getBoundingClientRect();
            if (rect.bottom > window.innerHeight - 20) menu.classList.add('position-top');
            else menu.classList.remove('position-top');
            if (rect.right > window.innerWidth - 20) { menu.style.right = '0'; menu.style.left = 'auto'; }
            if (rect.left < 20) { menu.style.left = '0'; menu.style.right = 'auto'; }
        }, 10);
    } else {
        card.classList.remove('menu-open');
    }
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.menu-wrapper')) {
        document.querySelectorAll('.menu-dropdown').forEach(m => m.classList.remove('show', 'position-top'));
        document.querySelectorAll('.upload-card').forEach(c => c.classList.remove('menu-open'));
    }
});

function openCreateGroupModal() {
    document.getElementById('newGroupName').value = '';
    document.getElementById('newGroupTitle').value = '';
    document.getElementById('createGroupModal').style.display = 'flex';
}

function closeCreateGroupModal() {
    document.getElementById('createGroupModal').style.display = 'none';
}

async function submitCreateGroup() {
    const name = document.getElementById('newGroupName').value.trim();
    const title = document.getElementById('newGroupTitle').value.trim();
    if (!name) { showToast('Group name is required.', 'error'); return; }
    try {
        const res = await fetch('php/add_student.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ create_group: true, group_name: name, research_title: title })
        });
        const data = await res.json();
        if (data.success) { showToast('Group created successfully!', 'success'); closeCreateGroupModal(); setTimeout(() => location.reload(), 800); }
        else { showToast(data.message || 'Failed to create group.', 'error'); }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    }
}

let leaderDebounce, memberDebounce;
let selectedLeader = null;
let selectedMembers = [];
let currentAddGroupId = null;

function openAddMembersModal(groupId) {
    currentAddGroupId = groupId;
    selectedLeader = null;
    selectedMembers = [];
    document.getElementById('leaderSearchInput').value = '';
    document.getElementById('memberSearchInput').value = '';
    document.getElementById('leaderSuggestions').style.display = 'none';
    document.getElementById('memberSuggestions').style.display = 'none';
    document.getElementById('leaderSelectedBox').innerHTML = '';
    document.getElementById('membersSelectedBox').innerHTML = '';
    document.getElementById('leaderSchoolId').value = '';
    document.getElementById('memberSchoolIds').value = '';
    document.getElementById('addMembersModal').style.display = 'flex';
}

function closeAddMembersModal() { document.getElementById('addMembersModal').style.display = 'none'; }

function handleLeaderSearch(val) {
    clearTimeout(leaderDebounce);
    if (!val.trim()) { document.getElementById('leaderSuggestions').style.display = 'none'; return; }
    leaderDebounce = setTimeout(() => fetchStudents(val, 'leader'), 200);
}

function handleMemberSearch(val) {
    clearTimeout(memberDebounce);
    if (!val.trim()) { document.getElementById('memberSuggestions').style.display = 'none'; return; }
    memberDebounce = setTimeout(() => fetchStudents(val, 'member'), 200);
}

async function fetchStudents(q, type) {
    try {
        const res = await fetch(`php/search_student.php?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        renderSuggestions(data, type);
    } catch (e) {}
}

function renderSuggestions(students, type) {
    const boxId = type === 'leader' ? 'leaderSuggestions' : 'memberSuggestions';
    const box = document.getElementById(boxId);
    box.innerHTML = '';
    if (!students.length) { box.style.display = 'none'; return; }
    students.forEach(s => {
        const div = document.createElement('div');
        div.className = 'autocomplete-item';
        div.innerHTML = `<span><span class="student-id">${s.school_id}</span> <span class="student-name">– ${s.name}</span></span><span class="student-prog">${s.program || ''}</span>`;
        div.onclick = () => selectStudent(s, type);
        box.appendChild(div);
    });
    box.style.display = 'block';
}

function selectStudent(s, type) {
    if (type === 'leader') {
        selectedLeader = s;
        document.getElementById('leaderSchoolId').value = s.school_id;
        document.getElementById('leaderSearchInput').value = '';
        document.getElementById('leaderSuggestions').style.display = 'none';
        document.getElementById('leaderSelectedBox').innerHTML = `<span class="selected-tag"><i class="ri-star-fill" style="color:#f59e0b;font-size:12px;"></i> ${s.school_id} – ${s.name} <span class="remove-tag" onclick="clearLeader()">×</span></span>`;
    } else {
        if (selectedMembers.find(m => m.school_id === s.school_id)) { document.getElementById('memberSuggestions').style.display = 'none'; document.getElementById('memberSearchInput').value = ''; return; }
        selectedMembers.push(s);
        document.getElementById('memberSchoolIds').value = selectedMembers.map(m => m.school_id).join(',');
        document.getElementById('memberSearchInput').value = '';
        document.getElementById('memberSuggestions').style.display = 'none';
        renderSelectedMembers();
    }
}

function clearLeader() {
    selectedLeader = null;
    document.getElementById('leaderSchoolId').value = '';
    document.getElementById('leaderSelectedBox').innerHTML = '';
}

function renderSelectedMembers() {
    const box = document.getElementById('membersSelectedBox');
    box.innerHTML = '';
    selectedMembers.forEach((s, i) => {
        const span = document.createElement('span');
        span.className = 'selected-tag';
        span.innerHTML = `${s.school_id} – ${s.name} <span class="remove-tag" onclick="removeSelectedMember(${i})">×</span>`;
        box.appendChild(span);
    });
}

function removeSelectedMember(idx) {
    selectedMembers.splice(idx, 1);
    document.getElementById('memberSchoolIds').value = selectedMembers.map(m => m.school_id).join(',');
    renderSelectedMembers();
}

async function submitAddMembers() {
    const groupId = currentAddGroupId;
    const leaderSchoolId = document.getElementById('leaderSchoolId').value.trim();
    const memberIds = selectedMembers.map(m => m.school_id).filter(id => id !== leaderSchoolId);
    if (!leaderSchoolId && memberIds.length === 0) { showToast('Please select at least one student.', 'error'); return; }
    try {
        const res = await fetch('php/add_student.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ group_id: groupId, leader_school_id: leaderSchoolId, member_school_ids: memberIds })
        });
        const data = await res.json();
        if (data.success) { showToast('Members added successfully!', 'success'); closeAddMembersModal(); setTimeout(() => location.reload(), 800); }
        else { showToast(data.message || 'Failed to add members.', 'error'); }
    } catch (e) {
        showToast('Network error. Please try again.', 'error');
    }
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('#leaderWrapper')) document.getElementById('leaderSuggestions').style.display = 'none';
    if (!e.target.closest('#membersWrapper')) document.getElementById('memberSuggestions').style.display = 'none';
});

async function updateStatus(uploadId, status) {
    const uploadCard = document.querySelector(`[data-upload-id="${uploadId}"]`);
    if (uploadCard && uploadCard.classList.contains('approved')) {
        showToast('This document is already approved and cannot be modified', 'error');
        return;
    }
    const confirmed = await showConfirmToast(`${status === 'approved' ? 'Approve' : 'Reject'} this file?`, status === 'approved' ? 'Approve' : 'Reject', status === 'rejected');
    if (!confirmed) return;
    const formData = new FormData();
    formData.append('upload_id', uploadId);
    formData.append('status', status);
    try {
        const response = await fetch('php/update_upload_status.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { showToast(`File ${status} successfully!`, 'success'); setTimeout(() => location.reload(), 800); }
        else { showToast(data.message || 'Failed to update status', 'error'); }
    } catch (e) {
        showToast('Error updating status: ' + e.message, 'error');
    }
}

let currentUploadId = null;

function openCommentModal(uploadId, taskName, currentComment) {
    currentUploadId = uploadId;
    document.getElementById('commentModalTitle').textContent = `Comment on ${taskName}`;
    document.getElementById('commentText').value = currentComment || '';
    document.getElementById('commentModal').classList.add('show');
}

function closeCommentModal() {
    document.getElementById('commentModal').classList.remove('show');
    currentUploadId = null;
}

async function submitComment() {
    const comment = document.getElementById('commentText').value.trim();
    if (!comment) { showToast('Please enter a comment', 'error'); return; }
    const formData = new FormData();
    formData.append('upload_id', currentUploadId);
    formData.append('comment', comment);
    try {
        const response = await fetch('php/update_upload_comment.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { showToast('Comment added successfully!', 'success'); closeCommentModal(); setTimeout(() => location.reload(), 800); }
        else { showToast(data.message || 'Failed to add comment', 'error'); }
    } catch (e) {
        showToast('Error adding comment: ' + e.message, 'error');
    }
}

let currentMilestoneType = null;
let currentMilestoneGroupId = null;

function openMilestoneUploadModal(type, label, groupId, evidenceLabel = '') {
    currentMilestoneType = type;
    currentMilestoneGroupId = groupId;
    document.getElementById('milestoneUploadTitle').textContent = `Upload ${label}`;
    document.getElementById('evidenceText').textContent = evidenceLabel;
    document.getElementById('milestoneFileInput').value = '';
    document.getElementById('milestoneUploadModal').classList.add('show');
}

function closeMilestoneUploadModal() {
    document.getElementById('milestoneUploadModal').classList.remove('show');
    currentMilestoneType = null;
    currentMilestoneGroupId = null;
}

async function submitMilestoneUpload() {
    const fileInput = document.getElementById('milestoneFileInput');
    const file = fileInput.files[0];
    if (!file) { showToast('Please select a file', 'error'); return; }
    if (file.type !== 'application/pdf') { showToast('Only PDF files are allowed', 'error'); return; }
    if (file.size > 10 * 1024 * 1024) { showToast('File size must be less than 10MB', 'error'); return; }
    const formData = new FormData();
    formData.append('file', file);
    formData.append('milestone_type', currentMilestoneType);
    formData.append('group_id', currentMilestoneGroupId);
    formData.append('action', 'upload_milestone');
    try {
        const response = await fetch('advisees.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { showToast('File uploaded successfully!', 'success'); closeMilestoneUploadModal(); setTimeout(() => location.reload(), 800); }
        else { showToast(data.message || 'Failed to upload file', 'error'); }
    } catch (e) {
        showToast('Error uploading file: ' + e.message, 'error');
    }
}

let currentTitleGroupId = null;

function openTitleModal(groupId, currentTitle) {
    currentTitleGroupId = groupId;
    document.getElementById('advisorTitleInput').value = currentTitle || '';
    document.getElementById('advisorTitleModal').classList.add('show');
}

function closeAdvisorTitleModal() {
    document.getElementById('advisorTitleModal').classList.remove('show');
    currentTitleGroupId = null;
}

async function submitAdvisorTitle() {
    const title = document.getElementById('advisorTitleInput').value.trim();
    if (!title) { showToast('Please enter a title', 'error'); return; }
    const formData = new FormData();
    formData.append('title', title);
    formData.append('milestone_type', 'title');
    formData.append('group_id', currentTitleGroupId);
    formData.append('action', 'upload_milestone');
    try {
        const response = await fetch('advisees.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) { showToast('Title saved successfully!', 'success'); closeAdvisorTitleModal(); setTimeout(() => location.reload(), 800); }
        else { showToast(data.message || 'Failed to save title', 'error'); }
    } catch (e) {
        showToast('Error saving title: ' + e.message, 'error');
    }
}

let currentMilestoneActionGroupId = null;
let currentMilestoneActionType = null;
let currentMilestoneActionLabel = null;

function openMilestoneActionModal(groupId, milestoneType, milestoneLabel, currentComment) {
    currentMilestoneActionGroupId = groupId;
    currentMilestoneActionType = milestoneType;
    currentMilestoneActionLabel = milestoneLabel;
    document.getElementById('milestoneActionTitle').textContent = milestoneLabel;
    document.getElementById('milestoneActionComment').value = currentComment || '';
    document.getElementById('milestoneActionModal').style.display = 'flex';
}

function closeMilestoneActionModal() {
    document.getElementById('milestoneActionModal').style.display = 'none';
    currentMilestoneActionGroupId = null;
    currentMilestoneActionType = null;
    currentMilestoneActionLabel = null;
}

async function submitMilestoneAction(status) {
    const comment = document.getElementById('milestoneActionComment').value.trim();
    if (status === 'rejected' && !comment) { showToast('Please provide a reason for rejection', 'error'); return; }
    const formData = new FormData();
    formData.append('action', 'milestone_action');
    formData.append('group_id', currentMilestoneActionGroupId);
    formData.append('milestone_type', currentMilestoneActionType);
    formData.append('status', status);
    formData.append('comment', comment);
    try {
        const response = await fetch('advisees.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            showToast(`Milestone ${status === 'completed' ? 'approved' : 'rejected'} successfully!`, 'success');
            closeMilestoneActionModal();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Failed to update milestone', 'error');
        }
    } catch (e) {
        showToast('Error: ' + e.message, 'error');
    }
}

function openPreview(filePath, fileName) {
    const modal = document.getElementById('documentPreviewModal');
    const previewContent = document.getElementById('previewContent');
    const previewTitle = document.getElementById('previewTitle');
    const downloadBtn = document.getElementById('downloadLinkBtn');
    previewTitle.textContent = fileName;
    downloadBtn.href = filePath;
    downloadBtn.download = fileName;
    modal.classList.add('show');
    previewContent.innerHTML = `<div class="preview-loading"><i class="ri-loader-4-line" style="font-size:48px;animation:spin 1s linear infinite;"></i><p>Loading preview...</p></div>`;
    const extension = filePath.split('.').pop().toLowerCase();
    if (extension === 'pdf') {
        previewContent.innerHTML = `<iframe src="${filePath}#toolbar=1" type="application/pdf" style="width:100%;height:100%;border:none;background:white;"></iframe>`;
    } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
        const img = new Image();
        img.onload = () => { previewContent.innerHTML = `<img src="${filePath}" alt="${fileName}" style="max-width:100%;max-height:100%;object-fit:contain;" />`; };
        img.onerror = () => { previewContent.innerHTML = `<div class="preview-error"><i class="ri-image-line" style="font-size:64px;color:#dc3545;"></i><h3>Failed to Load Image</h3><a href="${filePath}" download="${fileName}" style="display:inline-block;margin-top:20px;padding:12px 24px;background:#007bff;color:white;text-decoration:none;border-radius:5px;">Download</a></div>`; };
        img.src = filePath;
    } else if (['doc', 'docx'].includes(extension)) {
        previewContent.innerHTML = `<div style="text-align:center;padding:50px 30px;"><i class="ri-file-word-2-line" style="font-size:100px;color:#2b579a;display:block;margin-bottom:30px;"></i><h3 style="color:#2b579a;">Microsoft Word Document</h3><p style="color:#6c757d;margin:10px 0 20px;">${fileName}</p><a href="${filePath}" download="${fileName}" style="display:inline-flex;align-items:center;gap:10px;padding:14px 28px;background:#007bff;color:white;text-decoration:none;border-radius:8px;font-weight:600;"><i class="ri-download-cloud-line"></i> Download Document</a></div>`;
    } else {
        previewContent.innerHTML = `<div class="preview-error"><i class="ri-file-warning-line" style="font-size:64px;color:#ffc107;"></i><h3>Preview Not Available</h3><p>File: <strong>${fileName}</strong></p><a href="${filePath}" download="${fileName}" style="display:inline-block;margin-top:20px;padding:12px 24px;background:#007bff;color:white;text-decoration:none;border-radius:5px;">Download File</a></div>`;
    }
}

function closePreviewModal() {
    document.getElementById('documentPreviewModal').classList.remove('show');
}

document.addEventListener('click', (e) => {
    const modal = document.getElementById('documentPreviewModal');
    if (e.target === modal) closePreviewModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modal = document.getElementById('documentPreviewModal');
        if (modal && modal.classList.contains('show')) closePreviewModal();
    }
});