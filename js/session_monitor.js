const SESSION_TIMEOUT_MS = SESSION_TIMEOUT_MINUTES * 60 * 1000;
const WARNING_BEFORE_EXPIRE_MS = 60 * 1000;

let sessionTimer;
let warningTimer;
let lastActivity = Date.now();

function resetSessionTimers() {
    lastActivity = Date.now();
    clearTimeout(sessionTimer);
    clearTimeout(warningTimer);
    
    warningTimer = setTimeout(showSessionWarning, SESSION_TIMEOUT_MS - WARNING_BEFORE_EXPIRE_MS);
    sessionTimer = setTimeout(expireSession, SESSION_TIMEOUT_MS);
}

function showSessionWarning() {
    const overlay = document.createElement('div');
    overlay.id = 'sessionWarningOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:999999;display:flex;align-items:center;justify-content:center;font-family:Arial,sans-serif;animation:fadeIn .3s ease-out';
    
    overlay.innerHTML = `
        <div style="background:#fff;padding:30px 40px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.3);max-width:420px;text-align:center;animation:slideIn .3s ease-out">
            <i class="ri-alarm-warning-line" style="font-size:64px;color:#ff9800;margin-bottom:20px;display:block"></i>
            <h2 style="margin:0 0 15px;color:#333;font-size:24px">Session Expiring Soon</h2>
            <p style="margin:0 0 25px;color:#666;font-size:15px;line-height:1.5">Your session will expire in <strong>1 minute</strong> due to inactivity. Click "Stay Logged In" to continue your session.</p>
            <div style="display:flex;gap:12px;justify-content:center">
                <button onclick="logoutNow()" style="padding:12px 24px;background:#6c757d;color:#fff;border:none;border-radius:6px;font-size:15px;font-weight:600;cursor:pointer">Log Out</button>
                <button onclick="stayLoggedIn()" style="padding:12px 24px;background:#28a745;color:#fff;border:none;border-radius:6px;font-size:15px;font-weight:600;cursor:pointer">Stay Logged In</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
}

function stayLoggedIn() {
    const overlay = document.getElementById('sessionWarningOverlay');
    if (overlay) overlay.remove();
    
    fetch('php/refresh_session.php', { method: 'POST' })
        .then(() => resetSessionTimers())
        .catch(() => expireSession());
}

function logoutNow() {
    const base = window.location.pathname.replace(/\/[^\/]+$/, '/');
    window.location.href = base + 'home.php';
}

function expireSession() {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:999999;display:flex;align-items:center;justify-content:center;font-family:Arial,sans-serif';
    
    const base = window.location.pathname.replace(/\/[^\/]+$/, '/');

    overlay.innerHTML = `
        <div style="background:#fff;padding:30px 40px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.3);max-width:400px;text-align:center;animation:slideIn .3s ease-out">
            <i class="ri-time-line" style="font-size:64px;color:#ffc107;margin-bottom:20px;display:block"></i>
            <h2 style="margin:0 0 15px;color:#333;font-size:24px">Session Expired</h2>
            <p style="margin:0 0 25px;color:#666;font-size:15px;line-height:1.5">Your session has expired due to inactivity. Please log in again to continue.</p>
            <button onclick="window.location.href='${base}home.php'" style="padding:12px 32px;background:#007bff;color:#fff;border:none;border-radius:6px;font-size:16px;font-weight:600;cursor:pointer">OK</button>
        </div>
    `;
    
    document.body.appendChild(overlay);
    
    setTimeout(() => {
        window.location.href = base + 'home.php';
    }, 5000);
}

['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
    document.addEventListener(event, resetSessionTimers, true);
});

const style = document.createElement('style');
style.textContent = `
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideIn{from{transform:translateY(-50px);opacity:0}to{transform:translateY(0);opacity:1}}
`;
document.head.appendChild(style);

resetSessionTimers();