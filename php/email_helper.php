<?php
/**
 * Email Helper Functions
 * Handles sending email notifications to users
 */

// Fix: use __DIR__ so require always works, even if included from another folder
require_once 'get_setting.php';

/**
 * Send email notification
 * @param PDO $con Database connection
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @return bool Success status
 */
function sendEmailNotification($con, $to, $subject, $message) {
    // Check if email notifications are enabled
    if (!getSettingBool($con, 'enable_email_notifications', false)) {
        return false;
    }
    
    // Get email configuration
    $fromEmail = getSettingString($con, 'system_email', 'noreply@researchmonitor.edu');
    $fromName = getSettingString($con, 'system_name', 'Research Monitoring System');
    
    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        "From: {$fromName} <{$fromEmail}>",
        "Reply-To: {$fromEmail}",
        'X-Mailer: PHP/' . phpversion()
    ];
    
    // Add email template wrapper
    $htmlMessage = getEmailTemplate($message, $subject);
    
    // Send email
    $success = mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
    
    // Log email attempt
    if ($success) {
        logEmailSent($con, $to, $subject);
    } else {
        logEmailFailed($con, $to, $subject);
    }
    
    return $success;
}

/**
 * Get email template wrapper
 */
function getEmailTemplate($content, $title) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #999; }
            .button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>{$title}</h2>
            </div>
            <div class='content'>
                {$content}
            </div>
            <div class='footer'>
                <p>This is an automated message from Research Monitoring System</p>
                <p>Please do not reply to this email</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Send notification for upload status change (Student)
 */
function notifyStudentUploadStatus($con, $studentId, $taskName, $status, $comment = '') {
    $stmt = $con->prepare("SELECT email, name FROM student WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student || !$student['email']) return false;
    
    $statusText = ucfirst($status);
    $statusColor = $status === 'approved' ? '#28a745' : '#dc3545';
    
    $subject = "Upload {$statusText}: {$taskName}";
    
    $message = "
        <p>Hello <strong>{$student['name']}</strong>,</p>
        <p>Your submission for <strong>{$taskName}</strong> has been <strong style='color:{$statusColor}'>{$statusText}</strong> by your advisor.</p>
    ";
    
    if ($comment) {
        $message .= "<p><strong>Advisor's Comment:</strong><br>{$comment}</p>";
    }
    
    $message .= "
        <p>Please log in to view the details:</p>
        <a href='" . getCurrentUrl() . "/index.php' class='button'>View Dashboard</a>
    ";
    
    return sendEmailNotification($con, $student['email'], $subject, $message);
}

/**
 * Send notification for title status change (Student)
 */
function notifyStudentTitleStatus($con, $groupId, $status) {
    $stmt = $con->prepare("
        SELECT s.email, s.name, g.research_title 
        FROM student s
        JOIN groups g ON s.group_id = g.id
        WHERE g.id = ? AND s.is_leader = TRUE
    ");
    $stmt->execute([$groupId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student || !$student['email']) return false;
    
    $statusText = ucfirst($status);
    $statusColor = $status === 'approved' ? '#28a745' : '#dc3545';
    
    $subject = "Research Title {$statusText}";
    
    $message = "
        <p>Hello <strong>{$student['name']}</strong>,</p>
        <p>Your proposed research title has been <strong style='color:{$statusColor}'>{$statusText}</strong>:</p>
        <p><em>\"{$student['research_title']}\"</em></p>
        <a href='" . getCurrentUrl() . "/index.php' class='button'>View Dashboard</a>
    ";
    
    return sendEmailNotification($con, $student['email'], $subject, $message);
}

/**
 * Send notification for new upload (Advisor)
 */
function notifyAdvisorNewUpload($con, $advisorId, $studentName, $taskName) {
    $stmt = $con->prepare("SELECT email, name FROM advisor WHERE id = ?");
    $stmt->execute([$advisorId]);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$advisor || !$advisor['email']) return false;
    
    $subject = "New Upload: {$taskName}";
    
    $message = "
        <p>Hello <strong>{$advisor['name']}</strong>,</p>
        <p><strong>{$studentName}</strong> has submitted a new document for review:</p>
        <p><strong>{$taskName}</strong></p>
        <a href='" . getCurrentUrl() . "/advisor.php' class='button'>Review Submission</a>
    ";
    
    return sendEmailNotification($con, $advisor['email'], $subject, $message);
}

/**
 * Send notification for title proposal (Advisor)
 */
function notifyAdvisorTitleProposal($con, $advisorId, $groupName, $title) {
    $stmt = $con->prepare("SELECT email, name FROM advisor WHERE id = ?");
    $stmt->execute([$advisorId]);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$advisor || !$advisor['email']) return false;
    
    $subject = "New Title Proposal: {$groupName}";
    
    $message = "
        <p>Hello <strong>{$advisor['name']}</strong>,</p>
        <p>Group <strong>{$groupName}</strong> has proposed a research title:</p>
        <p><em>\"{$title}\"</em></p>
        <a href='" . getCurrentUrl() . "/advisor.php' class='button'>Review Title</a>
    ";
    
    return sendEmailNotification($con, $advisor['email'], $subject, $message);
}

/**
 * Send notification for UREC document upload (Advisor)
 */
function notifyAdvisorUrecUpload($con, $advisorId, $groupName, $docType) {
    $stmt = $con->prepare("SELECT email, name FROM advisor WHERE id = ?");
    $stmt->execute([$advisorId]);
    $advisor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$advisor || !$advisor['email']) return false;
    
    $subject = "New UREC Document: {$docType}";
    
    $message = "
        <p>Hello <strong>{$advisor['name']}</strong>,</p>
        <p>Group <strong>{$groupName}</strong> has uploaded a <strong>{$docType}</strong> for review.</p>
        <a href='" . getCurrentUrl() . "/advisor.php' class='button'>Review Document</a>
    ";
    
    return sendEmailNotification($con, $advisor['email'], $subject, $message);
}

/**
 * Send system notification (Coordinator/Admin)
 */
function notifyCoordinator($con, $subject, $message) {
    $stmt = $con->query("SELECT email FROM coordinator WHERE is_active = TRUE");
    $coordinators = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $successCount = 0;
    foreach ($coordinators as $coord) {
        if ($coord['email']) {
            if (sendEmailNotification($con, $coord['email'], $subject, $message)) {
                $successCount++;
            }
        }
    }
    
    return $successCount > 0;
}

/**
 * Helper functions for logging
 */
function logEmailSent($con, $to, $subject) {
    try {
        $stmt = $con->prepare("
            INSERT INTO email_logs (recipient, subject, status, sent_at)
            VALUES (?, ?, 'sent', NOW())
        ");
        $stmt->execute([$to, $subject]);
    } catch (Exception $e) {
        // Silent fail - don't break email sending
    }
}

function logEmailFailed($con, $to, $subject) {
    try {
        $stmt = $con->prepare("
            INSERT INTO email_logs (recipient, subject, status, sent_at)
            VALUES (?, ?, 'failed', NOW())
        ");
        $stmt->execute([$to, $subject]);
    } catch (Exception $e) {
        // Silent fail
    }
}

function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}