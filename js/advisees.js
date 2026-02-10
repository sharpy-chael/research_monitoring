function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    const icon = type === 'success' ? 'ri-checkbox-circle-line' : 'ri-error-warning-line';
    toast.innerHTML = `<i class="${icon}"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

async function addItem(type) {
    const inputId = type === 'sdg' ? 'newSdgName' : 'newThrustName';
    const name = document.getElementById(inputId).value.trim();
    
    if (!name) {
        showToast('Please enter a name', 'error');
        return;
    }

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
            card.innerHTML = `
                <span class="item-name">${name}</span>
                <i class="ri-delete-bin-line delete-icon" onclick="deleteItem('${type}', ${data.id}, this)"></i>
            `;
            grid.appendChild(card);
            document.getElementById(inputId).value = '';
            showToast(`${type === 'sdg' ? 'SDG' : 'Research Thrust'} added successfully!`, 'success');
        } else {
            showToast(data.message || 'Failed to add item', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

async function deleteItem(type, id, elem) {
    if (!confirm(`Delete this ${type === 'sdg' ? 'SDG' : 'Research Thrust'}?`)) return;

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
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

let currentAssignContext = { type: null, groupId: null };

async function openAssignModal(type, groupId) {
    currentAssignContext = { type, groupId };
    const modal = document.getElementById('assignModal');
    const title = document.getElementById('assignModalTitle');
    const checkboxContainer = document.getElementById('assignCheckboxes');
    
    title.textContent = `Assign ${type === 'sdg' ? 'UN SDG' : 'Research Thrust'}`;
    
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
                checkboxContainer.innerHTML = '<p style="text-align: center; color: #999; padding: 20px;">No available items to assign. Create some first!</p>';
            } else {
                data.items.forEach(item => {
                    const label = document.createElement('label');
                    label.style.display = 'block';
                    label.style.padding = '8px';
                    label.style.cursor = 'pointer';
                    label.innerHTML = `<input type="checkbox" value="${item.id}" style="margin-right: 8px;">${item.name}`;
                    checkboxContainer.appendChild(label);
                });
            }
        }
    } catch (error) {
        console.error('Error:', error);
    }

    modal.classList.add('show');
}

function closeAssignModal() {
    document.getElementById('assignModal').classList.remove('show');
    currentAssignContext = { type: null, groupId: null };
}

async function submitAssignment() {
    const checkboxes = document.querySelectorAll('#assignCheckboxes input[type="checkbox"]:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);

    if (ids.length === 0) {
        showToast('Please select at least one item', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'assign');
    formData.append('type', currentAssignContext.type);
    formData.append('group_id', currentAssignContext.groupId);
    formData.append('ids', JSON.stringify(ids));

    try {
        const res = await fetch('php/manage_sdg_thrust.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            showToast('Assignment successful! Refreshing...', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to assign', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

async function removeAssignment(type, groupId, itemId, elem) {
    if (!confirm(`Remove this ${type === 'sdg' ? 'SDG' : 'Research Thrust'} assignment?`)) return;

    const formData = new FormData();
    formData.append('action', 'remove_assignment');
    formData.append('type', type);
    formData.append('group_id', groupId);
    formData.append('item_id', itemId);

    try {
        const res = await fetch('php/manage_sdg_thrust.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            elem.closest('.tag').remove();
            showToast('Assignment removed successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to remove assignment', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
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
    
    document.querySelectorAll('.menu-dropdown').forEach(m => { 
        if (m !== menu) {
            m.classList.remove('show');
            m.classList.remove('position-top');
        }
    });
    document.querySelectorAll('.upload-card').forEach(c => {
        if (c !== card) c.classList.remove('menu-open');
    });
    
    const isOpening = !menu.classList.contains('show');
    menu.classList.toggle('show');
    
    if (isOpening) {
        card.classList.add('menu-open');
        setTimeout(() => {
            const rect = menu.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            
            if (rect.bottom > windowHeight - 20) {
                menu.classList.add('position-top');
            } else {
                menu.classList.remove('position-top');
            }
            
            if (rect.right > window.innerWidth - 20) {
                menu.style.right = '0';
                menu.style.left = 'auto';
            }
            if (rect.left < 20) {
                menu.style.left = '0';
                menu.style.right = 'auto';
            }
        }, 10);
    } else {
        card.classList.remove('menu-open');
    }
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('.menu-wrapper')) {
        document.querySelectorAll('.menu-dropdown').forEach(menu => {
            menu.classList.remove('show');
            menu.classList.remove('position-top');
        });
        document.querySelectorAll('.upload-card').forEach(card => {
            card.classList.remove('menu-open');
        });
    }
});

function openCreateGroupModal() {
    document.getElementById('newGroupName').value = '';
    document.getElementById('createGroupModal').classList.add('show');
}

function closeCreateGroupModal() {
    document.getElementById('createGroupModal').classList.remove('show');
}

async function submitCreateGroup() {
    const groupName = document.getElementById('newGroupName').value.trim();
    if (!groupName) {
        showToast('Please enter a group name', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'create_group');
    formData.append('group_name', groupName);

    try {
        const response = await fetch('php/assign_group_roles.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            showToast('Group created successfully!', 'success');
            closeCreateGroupModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to create group', 'error');
        }
    } catch (error) {
        showToast('Error creating group: ' + error.message, 'error');
    }
}

let currentGroupIdForMembers = null;

function openAddMembersModal(groupId) {
    currentGroupIdForMembers = groupId;
    document.getElementById('leaderSchoolId').value = '';
    document.getElementById('memberSchoolIds').value = '';
    document.getElementById('addMembersModal').classList.add('show');
}

function closeAddMembersModal() {
    document.getElementById('addMembersModal').classList.remove('show');
    currentGroupIdForMembers = null;
}

async function submitAddMembers() {
    const leaderId = document.getElementById('leaderSchoolId').value.trim();
    const memberIds = document.getElementById('memberSchoolIds').value.trim();

    if (!leaderId && !memberIds) {
        showToast('Please enter at least a leader or member school IDs', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'add_members');
    formData.append('group_id', currentGroupIdForMembers);
    formData.append('leader_id', leaderId);
    formData.append('member_ids', memberIds);

    try {
        const response = await fetch('php/assign_group_roles.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            showToast(data.message || 'Members added successfully!', 'success');
            closeAddMembersModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to add members', 'error');
        }
    } catch (error) {
        showToast('Error adding members: ' + error.message, 'error');
    }
}

async function updateStatus(uploadId, status) {
    // CRITICAL: Check if element already has 'approved' class (client-side safety check)
    const uploadCard = document.querySelector(`[data-upload-id="${uploadId}"]`);
    if (uploadCard && uploadCard.classList.contains('approved')) {
        showToast('This document is already approved and cannot be modified', 'error');
        return;
    }
    
    if (!confirm(`Are you sure you want to ${status} this file?`)) return;
    
    const formData = new FormData();
    formData.append('upload_id', uploadId);
    formData.append('status', status);
    
    try {
        const response = await fetch('php/update_upload_status.php', { method: 'POST', body: formData });
        const data = await response.json();
        
        if (data.success) {
            showToast(`File ${status} successfully!`, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to update status', 'error');
        }
    } catch (error) {
        showToast('Error updating status: ' + error.message, 'error');
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
    
    if (!comment) {
        showToast('Please enter a comment', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('upload_id', currentUploadId);
    formData.append('comment', comment);
    
    try {
        const response = await fetch('php/update_upload_comment.php', { method: 'POST', body: formData });
        const data = await response.json();
        
        if (data.success) {
            showToast('Comment added successfully!', 'success');
            closeCommentModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to add comment', 'error');
        }
    } catch (error) {
        showToast('Error adding comment: ' + error.message, 'error');
    }
}

let currentMilestoneType = null;
let currentMilestoneGroupId = null;

function openMilestoneUploadModal(type, label, groupId) {
    currentMilestoneType = type;
    currentMilestoneGroupId = groupId;
    document.getElementById('milestoneUploadTitle').textContent = `Upload ${label}`;
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
    
    if (!fileInput.files[0]) {
        showToast('Please select a file', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('milestone_type', currentMilestoneType);
    formData.append('group_id', currentMilestoneGroupId);
    formData.append('action', 'upload_milestone');
    
    try {
        const response = await fetch('advisees.php', { method: 'POST', body: formData });
        const data = await response.json();
        
        if (data.success) {
            showToast('File uploaded successfully!', 'success');
            closeMilestoneUploadModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to upload file', 'error');
        }
    } catch (error) {
        showToast('Error uploading file: ' + error.message, 'error');
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
    
    if (!title) {
        showToast('Please enter a title', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('title', title);
    formData.append('milestone_type', 'title');
    formData.append('group_id', currentTitleGroupId);
    formData.append('action', 'upload_milestone');
    
    try {
        const response = await fetch('advisees.php', { method: 'POST', body: formData });
        const data = await response.json();
        
        if (data.success) {
            showToast('Title saved successfully!', 'success');
            closeAdvisorTitleModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Failed to save title', 'error');
        }
    } catch (error) {
        showToast('Error saving title: ' + error.message, 'error');
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
    
    previewContent.innerHTML = `
        <div class="preview-loading">
            <i class="ri-loader-4-line" style="font-size: 48px; animation: spin 1s linear infinite;"></i>
            <p>Loading preview...</p>
        </div>
    `;
    
    const extension = filePath.split('.').pop().toLowerCase();
    
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(extension)) {
        const img = new Image();
        img.onload = function() {
            previewContent.innerHTML = `<img src="${filePath}" alt="${fileName}" style="max-width: 100%; max-height: 100%; object-fit: contain;" />`;
        };
        img.onerror = function() {
            previewContent.innerHTML = `
                <div class="preview-error">
                    <i class="ri-image-line" style="font-size: 64px; color: #dc3545;"></i>
                    <h3 style="margin: 20px 0 10px 0; color: #333;">Failed to Load Image</h3>
                    <p style="color: #666; margin-bottom: 20px;">File: <strong>${fileName}</strong></p>
                    <a href="${filePath}" download="${fileName}" 
                       style="display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                        <i class="ri-download-line"></i> Download Image
                    </a>
                </div>
            `;
        };
        img.src = filePath;
        
    } else if (extension === 'pdf') {
        previewContent.innerHTML = `
            <iframe src="${filePath}#toolbar=1" 
                    type="application/pdf" 
                    style="width: 100%; height: 100%; border: none; background: white;">
            </iframe>
        `;
        
    } else if (['doc', 'docx'].includes(extension)) {
        previewContent.innerHTML = `
            <div style="text-align: center; padding: 50px 30px; max-width: 600px; margin: 0 auto;">
                <i class="ri-file-word-2-line" style="font-size: 100px; color: #2b579a; display: block; margin-bottom: 30px;"></i>
                <h3 style="color: #2b579a; margin-bottom: 15px; font-size: 24px; font-weight: 600;">Microsoft Word Document</h3>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <p style="color: #495057; margin-bottom: 10px; font-size: 15px; font-weight: 600;">
                        <i class="ri-file-text-line"></i> ${fileName}
                    </p>
                    <p style="color: #6c757d; margin: 0; font-size: 13px;">
                        Word documents need to be downloaded to view
                    </p>
                </div>
                <a href="${filePath}" 
                   download="${fileName}"
                   style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; background: #007bff; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; box-shadow: 0 2px 8px rgba(0,123,255,0.3);">
                    <i class="ri-download-cloud-line" style="font-size: 20px;"></i>
                    Download Document
                </a>
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                    <p style="color: #6c757d; font-size: 13px; margin-bottom: 8px;">
                        <i class="ri-information-line"></i> You can open this file with:
                    </p>
                    <p style="color: #868e96; font-size: 12px; margin: 0;">
                        Microsoft Word • Google Docs • LibreOffice • Pages
                    </p>
                </div>
            </div>
        `;
        
    } else if (['txt', 'csv', 'log'].includes(extension)) {
        fetch(filePath)
            .then(response => response.text())
            .then(text => {
                previewContent.innerHTML = `
                    <pre style="padding: 20px; background: white; overflow: auto; width: 100%; height: 100%; margin: 0; text-align: left; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.5; color: #333;">${escapeHtml(text)}</pre>
                `;
            })
            .catch(() => {
                previewContent.innerHTML = `
                    <div class="preview-error">
                        <i class="ri-file-text-line" style="font-size: 64px; color: #dc3545;"></i>
                        <h3 style="margin: 20px 0 10px 0; color: #333;">Failed to Load File</h3>
                        <p style="color: #666;">File: <strong>${fileName}</strong></p>
                    </div>
                `;
            });
            
    } else {
        previewContent.innerHTML = `
            <div class="preview-error">
                <i class="ri-file-warning-line" style="font-size: 64px; color: #ffc107;"></i>
                <h3 style="margin: 20px 0 10px 0; color: #333;">Preview Not Available</h3>
                <p style="color: #666; margin-bottom: 20px;">This file type (.${extension}) cannot be previewed in the browser.</p>
                <p style="color: #666;">File: <strong>${fileName}</strong></p>
                <a href="${filePath}" download="${fileName}" 
                   style="display: inline-block; margin-top: 20px; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                    <i class="ri-download-line"></i> Download File
                </a>
            </div>
        `;
    }
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function closePreviewModal() {
    const modal = document.getElementById('documentPreviewModal');
    modal.classList.remove('show');
}

document.addEventListener('click', function(event) {
    const modal = document.getElementById('documentPreviewModal');
    if (event.target === modal) {
        closePreviewModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('documentPreviewModal');
        if (modal && modal.classList.contains('show')) {
            closePreviewModal();
        }
    }
});