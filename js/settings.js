// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    
    const icon = type === 'success' ? 'ri-checkbox-circle-line' : 'ri-error-warning-line';
    
    toast.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('removing');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Tab switching
function switchTab(tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    event.target.closest('.tab-btn').classList.add('active');
    document.getElementById(tabName + '-tab').classList.add('active');
}

// Hide all form groups
function hideAllFormGroups() {
    const groups = [
        'programCodeGroup', 'programNameGroup', 'programDescGroup',
        'yearStartGroup', 'yearEndGroup', 'semesterGroup',
        'statusNameGroup', 'statusDescGroup', 'statusColorGroup', 'displayOrderGroup'
    ];
    groups.forEach(id => document.getElementById(id).style.display = 'none');
}

// Open modal for adding
function openModal(type) {
    document.getElementById('settingsModal').classList.add('show');
    document.getElementById('settingsForm').reset();
    document.getElementById('itemId').value = '';
    document.getElementById('itemType').value = type;
    document.getElementById('formAction').value = 'create';
    
    hideAllFormGroups();
    
    if (type === 'program') {
        document.getElementById('modalTitle').textContent = 'Add Program';
        document.getElementById('programCodeGroup').style.display = 'block';
        document.getElementById('programNameGroup').style.display = 'block';
        document.getElementById('programDescGroup').style.display = 'block';
        document.getElementById('programCode').required = true;
        document.getElementById('programName').required = true;
    } else if (type === 'academic-year') {
        document.getElementById('modalTitle').textContent = 'Add Academic Year';
        document.getElementById('yearStartGroup').style.display = 'block';
        document.getElementById('yearEndGroup').style.display = 'block';
        document.getElementById('semesterGroup').style.display = 'block';
        document.getElementById('yearStart').required = true;
        document.getElementById('yearEnd').required = true;
    } else if (type === 'research-status') {
        document.getElementById('modalTitle').textContent = 'Add Research Status';
        document.getElementById('statusNameGroup').style.display = 'block';
        document.getElementById('statusDescGroup').style.display = 'block';
        document.getElementById('statusColorGroup').style.display = 'block';
        document.getElementById('displayOrderGroup').style.display = 'block';
        document.getElementById('statusName').required = true;
    }
}

// Edit item
function editItem(type, item) {
    document.getElementById('settingsModal').classList.add('show');
    document.getElementById('itemId').value = item.id;
    document.getElementById('itemType').value = type;
    document.getElementById('formAction').value = 'update';
    
    hideAllFormGroups();
    
    if (type === 'program') {
        document.getElementById('modalTitle').textContent = 'Edit Program';
        document.getElementById('programCodeGroup').style.display = 'block';
        document.getElementById('programNameGroup').style.display = 'block';
        document.getElementById('programDescGroup').style.display = 'block';
        document.getElementById('programCode').value = item.code;
        document.getElementById('programName').value = item.name;
        document.getElementById('programDesc').value = item.description || '';
        document.getElementById('programCode').required = true;
        document.getElementById('programName').required = true;
    } else if (type === 'academic-year') {
        document.getElementById('modalTitle').textContent = 'Edit Academic Year';
        document.getElementById('yearStartGroup').style.display = 'block';
        document.getElementById('yearEndGroup').style.display = 'block';
        document.getElementById('semesterGroup').style.display = 'block';
        document.getElementById('yearStart').value = item.year_start;
        document.getElementById('yearEnd').value = item.year_end;
        document.getElementById('semester').value = item.semester;
        document.getElementById('yearStart').required = true;
        document.getElementById('yearEnd').required = true;
    } else if (type === 'research-status') {
        document.getElementById('modalTitle').textContent = 'Edit Research Status';
        document.getElementById('statusNameGroup').style.display = 'block';
        document.getElementById('statusDescGroup').style.display = 'block';
        document.getElementById('statusColorGroup').style.display = 'block';
        document.getElementById('displayOrderGroup').style.display = 'block';
        document.getElementById('statusName').value = item.name;
        document.getElementById('statusDesc').value = item.description || '';
        document.getElementById('statusColor').value = item.color;
        document.getElementById('displayOrder').value = item.display_order;
        document.getElementById('statusName').required = true;
    }
}

// Close modal
function closeModal() {
    document.getElementById('settingsModal').classList.remove('show');
}

// Form submission
document.getElementById('settingsForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('manage_settings.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Operation failed', 'error');
        }
    } catch (error) {
        showToast('Error: ' + error.message, 'error');
    }
});

// Toggle status
async function toggleStatus(type, id, newStatus) {
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('item_type', type);
    formData.append('item_id', id);
    formData.append('is_active', newStatus);
    
    try {
        const response = await fetch('manage_settings.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Operation failed', 'error');
        }
    } catch (error) {
        showToast('Error: ' + error.message, 'error');
    }
}

// Set active academic year (only one can be active)
async function setActiveAY(id, newStatus) {
    if (newStatus === 'true') {
        if (!confirm('This will deactivate all other academic years. Continue?')) {
            return;
        }
    }
    
    const formData = new FormData();
    formData.append('action', 'set_active_ay');
    formData.append('ay_id', id);
    formData.append('is_active', newStatus);
    
    try {
        const response = await fetch('manage_settings.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Operation failed', 'error');
        }
    } catch (error) {
        showToast('Error: ' + error.message, 'error');
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('settingsModal');
    if (event.target === modal) {
        closeModal();
    }
}