/** AUTO FADE-OUT FOR GENERIC MESSAGES **/
['.error-message', '.success-message'].forEach(selector => {
    setTimeout(() => {
        const msg = document.querySelector(selector);
        if (msg) {
            msg.style.animation = 'fadeOut 0.5s ease forwards';
            setTimeout(() => msg.remove(), 500);
        }
    }, 5000);
});


/** PASSWORD CHANGE MESSAGE HANDLING **/
document.getElementById("changePasswordForm")?.addEventListener("submit", async function (e) {
    e.preventDefault();

    const currentPassword = document.getElementById("currentPassword").value.trim();
    const newPassword = document.getElementById("newPassword").value.trim();
    const confirmPassword = document.getElementById("confirmPassword").value.trim();
    const message = document.getElementById("passwordMessage");

    message.style.display = "none";

    try {
        const response = await fetch("change_password.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword,
            }),
        });

        const data = await response.json();

        if (data.status === "success") {
            message.textContent = data.message;
            message.className = "password-message success";
            message.style.display = "block";

            setTimeout(() => {
                message.style.display = "none";
                closeModal();
            }, 2000);

            e.target.reset();
        } else {
            message.textContent = data.message || "Wrong password or mismatch.";
            message.className = "password-message error";
            message.style.display = "block";
        }
    } catch (error) {
        message.textContent = "Something went wrong.";
        message.className = "password-message error";
        message.style.display = "block";
    }
});
