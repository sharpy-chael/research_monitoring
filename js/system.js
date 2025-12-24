// Toast Notification System
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icon = type === 'success' ? 'ri-check-line' : 'ri-close-line';
    
    toast.innerHTML = `
        <i class="${icon}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Modal System
function createModal(title, content, size = '') {
    const modalContainer = document.getElementById('modalContainer');
    const modalId = 'modal_' + Date.now();
    
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.id = modalId;
    modal.innerHTML = `
        <div class="modal-content ${size}">
            <div class="modal-header">
                <h2><i class="ri-${getIconForModal(title)}"></i> ${title}</h2>
                <button class="modal-close" onclick="closeModal('${modalId}')">&times;</button>
            </div>
            ${content}
        </div>
    `;
    
    modalContainer.appendChild(modal);
    setTimeout(() => modal.classList.add('active'), 10);
    
    // Close on outside click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal(modalId);
    });
    
    return modalId;
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        setTimeout(() => modal.remove(), 300);
    }
}

function getIconForModal(title) {
    const icons = {
        'Create Backup': 'save-line',
        'View Backups': 'folder-history-line',
        'System Logs': 'file-list-3-line',
        'Send Notification': 'notification-3-line',
        'View Notifications': 'inbox-line',
        'System Settings': 'settings-3-line',
        'Error Logs': 'bug-line'
    };
    return icons[title] || 'information-line';
}

// Backup Modal
function openBackupModal() {
    const content = `
        <form id="backupForm" onsubmit="createBackup(event)">
            <div class="form-group">
                <label for="backupName">Backup Name</label>
                <input type="text" id="backupName" name="backupName" 
                       value="backup_${new Date().toISOString().split('T')[0]}" required>
            </div>
            <div class="form-group">
                <label for="backupNotes">Notes (Optional)</label>
                <textarea id="backupNotes" name="backupNotes" 
                          placeholder="Enter any notes about this backup..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('${modalId}')">Cancel</button>
                <button type="submit" class="btn-submit">
                    <i class="ri-download-line"></i> Create Backup
                </button>
            </div>
        </form>
    `;
    
    const modalId = createModal('Create Backup', content);
}

async function createBackup(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('php/system_backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeModal(event.target.closest('.modal').id);
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Error creating backup: ' + error.message, 'error');
    }
}

// View Backups Modal
async function openViewBackupsModal() {
    try {
        const response = await fetch('php/system_backup.php?action=list');
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.message, 'error');
            return;
        }
        
        let backupsHtml = '<div class="table-container"><table><thead><tr>' +
            '<th>Backup Name</th><th>Type</th><th>Size</th><th>Date</th><th>Status</th><th>Actions</th>' +
            '</tr></thead><tbody>';
        
        if (data.backups.length > 0) {
            data.backups.forEach(backup => {
                const size = backup.file_size ? (backup.file_size / 1024 / 1024).toFixed(2) + ' MB' : 'N/A';
                backupsHtml += `
                    <tr>
                        <td>${backup.backup_name}</td>
                        <td><span class="badge ${backup.backup_type}">${backup.backup_type}</span></td>
                        <td>${size}</td>
                        <td>${new Date(backup.created_at).toLocaleString()}</td>
                        <td><span class="status-badge status-${backup.status}">${backup.status}</span></td>
                        <td>
                            <button class="btn-secondary" onclick="downloadBackup(${backup.id})" style="padding: 6px 12px; font-size: 0.85rem;">
                                <i class="ri-download-line"></i> Download
                            </button>
                            <button class="btn-danger" onclick="deleteBackup(${backup.id})" style="padding: 6px 12px; font-size: 0.85rem;">
                                <i class="ri-delete-bin-line"></i> Delete
                            </button>
                        </td>
                    </tr>
                `;
            });
        } else {
            backupsHtml += '<tr><td colspan="6" class="no-data">No backups available</td></tr>';
        }
        
        backupsHtml += '</tbody></table></div>';
        
        createModal('View Backups', backupsHtml, 'large');
    } catch (error) {
        showToast('Error loading backups: ' + error.message, 'error');
    }
}

async function deleteBackup(backupId) {
    if (!confirm('Are you sure you want to delete this backup?')) return;
    
    try {
        const response = await fetch('php/system_backup.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ backup_id: backupId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Error deleting backup: ' + error.message, 'error');
    }
}

// System Logs Modal
async function openLogsModal() {
    try {
        const response = await fetch('php/system_logs.php?action=list');
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.message, 'error');
            return;
        }
        
        let logsHtml = `
            <div class="form-group">
                <label>Filter by Action Type</label>
                <select id="logFilter" onchange="filterLogs()">
                    <option value="">All Actions</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="upload">Upload</option>
                    <option value="approve">Approve</option>
                    <option value="reject">Reject</option>
                    <option value="delete">Delete</option>
                </select>
            </div>
            <div class="table-container" id="logsTableContainer">
                <table>
                    <thead>
                        <tr>
                            <th>User Type</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">`;
        
        if (data.logs.length > 0) {
            data.logs.forEach(log => {
                logsHtml += `
                    <tr data-action="${log.action_type}">
                        <td><span class="badge ${log.user_type}">${log.user_type}</span></td>
                        <td>${log.action_type}</td>
                        <td>${log.description || 'N/A'}</td>
                        <td>${log.ip_address || 'N/A'}</td>
                        <td>${new Date(log.created_at).toLocaleString()}</td>
                    </tr>
                `;
            });
        } else {
            logsHtml += '<tr><td colspan="5" class="no-data">No logs available</td></tr>';
        }
        
        logsHtml += `
                    </tbody>
                </table>
            </div>
            <div class="modal-actions">
                <button class="btn-danger" onclick="clearLogs()">
                    <i class="ri-delete-bin-line"></i> Clear All Logs
                </button>
            </div>
        `;
        
        createModal('System Logs', logsHtml, 'large');
    } catch (error) {
        showToast('Error loading logs: ' + error.message, 'error');
    }
}

function filterLogs() {
    const filter = document.getElementById('logFilter').value;
    const rows = document.querySelectorAll('#logsTableBody tr');
    
    rows.forEach(row => {
        if (!filter || row.dataset.action === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

async function exportLogs() {
    try {
        const response = await fetch('php/system_logs.php?action=export');
        const blob = await response.blob();
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `system_logs_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showToast('Logs exported successfully', 'success');
    } catch (error) {
        showToast('Error exporting logs: ' + error.message, 'error');
    }
}

async function clearLogs() {
    if (!confirm('Are you sure you want to clear all system logs? This action cannot be undone.')) return;
    
    try {
        const response = await fetch('php/system_logs.php', {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Error clearing logs: ' + error.message, 'error');
    }
}

// Notification Modal
function openNotificationModal() {
    const content = `
        <form id="notificationForm" onsubmit="sendNotification(event)">
            <div class="form-group">
                <label for="recipientType">Send To</label>
                <select id="recipientType" name="recipientType" required onchange="toggleRecipientId()">
                    <option value="all">All Users</option>
                    <option value="students">All Students</option>
                    <option value="advisors">All Advisors</option>
                    <option value="specific">Specific User</option>
                </select>
            </div>
            <div class="form-group" id="recipientIdGroup" style="display:none;">
                <label for="recipientId">User ID</label>
                <input type="number" id="recipientId" name="recipientId">
            </div>
            <div class="form-group">
                <label for="notifTitle">Title</label>
                <input type="text" id="notifTitle" name="notifTitle" required>
            </div>
            <div class="form-group">
                <label for="notifMessage">Message</label>
                <textarea id="notifMessage" name="notifMessage" required></textarea>
            </div>
            <div class="form-group">
                <label for="notifPriority">Priority</label>
                <select id="notifPriority" name="notifPriority">
                    <option value="low">Low</option>
                    <option value="normal" selected>Normal</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('${modalId}')">Cancel</button>
                <button type="submit" class="btn-submit">
                    <i class="ri-send-plane-line"></i> Send Notification
                </button>
            </div>
        </form>
    `;
    
    const modalId = createModal('Send Notification', content);
}

function toggleRecipientId() {
    const recipientType = document.getElementById('recipientType').value;
    const recipientIdGroup = document.getElementById('recipientIdGroup');
    
    if (recipientType === 'specific') {
        recipientIdGroup.style.display = 'block';
        document.getElementById('recipientId').required = true;
    } else {
        recipientIdGroup.style.display = 'none';
        document.getElementById('recipientId').required = false;
    }
}

async function sendNotification(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('php/system_notifications.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeModal(event.target.closest('.modal').id);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Error sending notification: ' + error.message, 'error');
    }
}

// View Notifications Modal
async function openViewNotificationsModal() {
    try {
        const response = await fetch('php/system_notifications.php?action=list');
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.message, 'error');
            return;
        }
        
        let notifsHtml = '<div class="table-container"><table><thead><tr>' +
            '<th>Title</th><th>Recipient</th><th>Priority</th><th>Status</th><th>Date</th>' +
            '</tr></thead><tbody>';
        
        if (data.notifications.length > 0) {
            data.notifications.forEach(notif => {
                notifsHtml += `
                    <tr>
                        <td>${notif.title}</td>
                        <td><span class="badge">${notif.recipient_type}</span></td>
                        <td><span class="badge ${notif.priority}">${notif.priority}</span></td>
                        <td><span class="status-badge status-${notif.status}">${notif.status}</span></td>
                        <td>${new Date(notif.created_at).toLocaleString()}</td>
                    </tr>
                `;
            });
        } else {
            notifsHtml += '<tr><td colspan="5" class="no-data">No notifications sent yet</td></tr>';
        }
        
        notifsHtml += '</tbody></table></div>';
        
        createModal('View Notifications', notifsHtml, 'large');
    } catch (error) {
        showToast('Error loading notifications: ' + error.message, 'error');
    }
}

// System Settings Modal
async function openSettingsModal() {
    try {
        const response = await fetch('php/system_settings.php?action=list');
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.message, 'error');
            return;
        }
        
        let settingsHtml = '<form id="settingsForm" onsubmit="saveSettings(event)">';
        
        data.settings.forEach(setting => {
            settingsHtml += `
                <div class="form-group">
                    <label for="setting_${setting.setting_key}">${setting.description}</label>`;
            
            if (setting.setting_type === 'boolean') {
                const checked = setting.setting_value === 'true' ? 'checked' : '';
                settingsHtml += `
                    <input type="checkbox" 
                           id="setting_${setting.setting_key}" 
                           name="${setting.setting_key}" 
                           ${checked}>
                `;
            } else {
                settingsHtml += `
                    <input type="${setting.setting_type === 'integer' ? 'number' : 'text'}" 
                           id="setting_${setting.setting_key}" 
                           name="${setting.setting_key}" 
                           value="${setting.setting_value}">
                `;
            }
            
            settingsHtml += '</div>';
        });
        
        settingsHtml += `
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('${modalId}')">Cancel</button>
                <button type="submit" class="btn-submit">
                    <i class="ri-save-line"></i> Save Settings
                </button>
            </div>
        </form>`;
        
        const modalId = createModal('System Settings', settingsHtml);
    } catch (error) {
        showToast('Error loading settings: ' + error.message, 'error');
    }
}

async function saveSettings(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('php/system_settings.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeModal(event.target.closest('.modal').id);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Error saving settings: ' + error.message, 'error');
    }
}

// Error Logs Modal
async function openErrorLogsModal() {
    try {
        const response = await fetch('php/system_logs.php?action=errors');
        const data = await response.json();
        
        if (!data.success) {
            showToast(data.message, 'error');
            return;
        }
        
        let errorsHtml = '<div class="table-container"><table><thead><tr>' +
            '<th>Type</th><th>Message</th><th>File</th><th>Line</th><th>Date</th>' +
            '</tr></thead><tbody>';
        
        if (data.errors.length > 0) {
            data.errors.forEach(error => {
                errorsHtml += `
                    <tr>
                        <td><span class="badge">${error.error_type}</span></td>
                        <td>${error.error_message}</td>
                        <td>${error.error_file || 'N/A'}</td>
                        <td>${error.error_line || 'N/A'}</td>
                        <td>${new Date(error.created_at).toLocaleString()}</td>
                    </tr>
                `;
            });
        } else {
            errorsHtml += '<tr><td colspan="5" class="no-data">No errors logged</td></tr>';
        }
        
        errorsHtml += `
                </tbody>
            </table>
        </div>
        <div class="modal-actions">
            <button class="btn-danger" onclick="clearErrorLogs()">
                <i class="ri-delete-bin-line"></i> Clear Error Logs
            </button>
        </div>`;
        
        createModal('Error Logs', errorsHtml, 'large');
    } catch (error) {
        showToast('Error loading error logs: ' + error.message, 'error');
    }
}

async function clearErrorLogs() {
    if (!confirm('Are you sure you want to clear all error logs?')) return;
    
    try {
        const response = await fetch('php/system_logs.php?action=clear_errors', {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        showToast('Error clearing error logs: ' + error.message, 'error');
    }
}