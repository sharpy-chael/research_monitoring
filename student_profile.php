<?php
error_reporting(0);
session_start();
include("connect.php");
include("php/get_setting.php");
include("check_session.php");
if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

$school_id = $_SESSION['school_id'];

$notificationsStmt = $con->prepare("
    SELECT id, title, message, priority, created_at, status
    FROM system_notifications
    WHERE (
        recipient_type = 'all'
        OR recipient_type = 'students'
        OR (recipient_type = 'specific' AND recipient_id = :user_id)
    )
    AND status != 'deleted'
    ORDER BY created_at DESC
    LIMIT 10
");
$notificationsStmt->execute(['user_id' => $_SESSION['id']]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);
$unreadCount   = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));

$stmt = $con->prepare("
    SELECT s.id, s.school_id, s.firstname, s.middlename, s.lastname,
           s.email, s.address, s.gender, s.images,
           p.code AS program
    FROM students s
    LEFT JOIN programs p ON s.program_id = p.id
    WHERE s.school_id = :school_id
");
$stmt->execute(['school_id' => $school_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $_SESSION['images']     = $row['images'];
    $_SESSION['email']      = $row['email'];
    $_SESSION['address']    = $row['address'];
    $_SESSION['gender']     = $row['gender'];
    $_SESSION['lastname']   = $row['lastname'];
    $_SESSION['firstname']  = $row['firstname'];
    $_SESSION['middlename'] = $row['middlename'];
    $_SESSION['program']    = $row['program'];

    $fn = trim($row['firstname']  ?? '');
    $mn = trim($row['middlename'] ?? '');
    $ln = trim($row['lastname']   ?? '');
    $_SESSION['name'] = trim($fn . ($mn ? ' ' . $mn : '') . ($ln ? ' ' . $ln : ''));
}

$studentId = $row['id'] ?? null;
$group_id  = null;

if ($studentId) {
    $sgStmt = $con->prepare("SELECT group_id FROM student_groups WHERE student_id = :student_id ORDER BY id ASC LIMIT 1");
    $sgStmt->execute(['student_id' => $studentId]);
    $sgRow    = $sgStmt->fetch(PDO::FETCH_ASSOC);
    $group_id = $sgRow['group_id'] ?? null;
}

$uploadsStmt = $con->prepare("
    SELECT upload_id, task_name, file_path, original_filename, uploaded_at
    FROM uploads
    WHERE group_id = :group_id
    ORDER BY uploaded_at DESC
");
$uploadsStmt->execute(['group_id' => $group_id]);
$uploads = $uploadsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/profile.css">
</head>
<body>

<?php include("templates/aside_student.html"); ?>
<p id="passwordMessage" class="password-message"></p>
<div id="globalMessage" class="global-message"></div>

<main class="profile-wrapper">

<section class="profile-sidebar">
  <div class="profile-header">
    <?php
        $profileImage = !empty($_SESSION['images'])
          ? 'uploads/' . htmlspecialchars($_SESSION['images'])
          : 'images/default-avatar.png';
    ?>
    <img src="<?php echo $profileImage; ?>" alt="Profile Picture">
    <div class="profile-info">
      <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
      <p>Student</p>
    </div>
  </div>

  <div class="profile-menu">
    <a href="#" id="editToggle" class="active"><i class="ri-user-line"></i> Edit Personal Info</a>
    <a href="#" id="changePasswordBtn"><i class="ri-lock-line"></i> Change Password</a>
  </div>
</section>

<section class="profile-details">
  <h2>Personal Information</h2>

  <div class="gender-row">
    <label><input type="radio" name="gender" disabled value="Male" <?php echo ($_SESSION['gender'] ?? '') === 'Male' ? 'checked' : ''; ?>> Male</label>
    <label><input type="radio" name="gender" disabled value="Female" <?php echo ($_SESSION['gender'] ?? '') === 'Female' ? 'checked' : ''; ?>> Female</label>
  </div>

  <div class="form-group">
    <label>Full Name</label>
    <input type="text" readonly value="<?php echo htmlspecialchars($_SESSION['name']); ?>">
  </div>

  <div class="form-group">
    <label>Email</label>
    <input type="text" id="displayEmail" readonly value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
  </div>

  <div class="form-group">
    <label>Address</label>
    <input type="text" id="displayAddress" readonly value="<?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?>">
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>School ID</label>
      <input type="text" readonly value="<?php echo htmlspecialchars($_SESSION['school_id']); ?>">
    </div>
    <div class="form-group">
      <label>Course</label>
      <input type="text" readonly value="<?php echo htmlspecialchars($_SESSION['program'] ?? ''); ?>">
    </div>
  </div>

  <h3 class="recent-head">Recent Uploads</h3>

  <div class="uploads-container">
    <div class="recent-upload-grid">
    <?php if (!empty($uploads)): ?>
        <?php foreach ($uploads as $upload): ?>
        <div class="upload-card">
            <div class="upload-card-header">
                <i class="ri-file-3-line file-icon"></i>
                <div class="menu-wrapper">
                    <i class="ri-more-2-fill menu-toggle"></i>
                    <div class="menu-dropdown">
                        <a href="<?= htmlspecialchars($upload['file_path']) ?>" download>Download</a>
                        <button class="delete delete-btn" data-id="<?= $upload['upload_id'] ?>">Delete</button>
                    </div>
                </div>
            </div>
            <div class="upload-card-body">
                <span class="file-title"><?= htmlspecialchars($upload['task_name']) ?></span>
                <p class="file-filename"><?= htmlspecialchars($upload['original_filename']) ?></p>
                <p class="file-date"><?= date("M d, Y • h:i A", strtotime($upload['uploaded_at'])) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class='no-upload-text'>No uploads yet.</p>
    <?php endif; ?>
    </div>
  </div>

  <script>
  document.querySelectorAll(".menu-toggle").forEach(btn => {
      btn.addEventListener("click", (e) => {
          e.stopPropagation();
          const menu = btn.nextElementSibling;
          document.querySelectorAll(".menu-dropdown").forEach(m => {
              if (m !== menu) m.classList.remove("show");
          });
          menu.classList.toggle("show");
      });
  });

  document.addEventListener("click", () => {
      document.querySelectorAll(".menu-dropdown").forEach(menu => menu.classList.remove("show"));
  });

  document.querySelectorAll(".delete-btn").forEach(btn => {
      btn.addEventListener("click", function(e) {
          e.preventDefault();
          const uploadId = this.getAttribute("data-id");
          if (!confirm("Delete this file?")) return;
          fetch("php/delete_upload.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: "upload_id=" + uploadId
          })
          .then(res => res.json())
          .then(data => {
              if (data.status === "success") {
                  showToast("File deleted successfully!", "success");
                  setTimeout(() => location.reload(), 1000);
              } else {
                  showToast(data.message, "error");
              }
          });
      });
  });
  </script>

</section>
</main>

<div id="editModal" class="edit-modal">
  <div class="edit-modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <div class="modal-header">
      <h2>Edit Personal Information</h2>
    </div>
    <form id="editForm" action="php/update_profile.php" method="POST" enctype="multipart/form-data" style="display:contents;">
      <div class="modal-body">
        <label>Last Name</label>
        <input type="text" name="lastname" id="editLastname" value="<?php echo htmlspecialchars($_SESSION['lastname'] ?? ''); ?>" required>

        <label>First Name</label>
        <input type="text" name="firstname" id="editFirstname" value="<?php echo htmlspecialchars($_SESSION['firstname'] ?? ''); ?>" required>

        <label>Middle Name</label>
        <input type="text" name="middlename" id="editMiddlename" value="<?php echo htmlspecialchars($_SESSION['middlename'] ?? ''); ?>">

        <label>Gender</label>
        <div class="gender-row">
          <label><input type="radio" name="gender" value="Male" <?php echo ($_SESSION['gender'] ?? '') === 'Male' ? 'checked' : ''; ?>> Male</label>
          <label><input type="radio" name="gender" value="Female" <?php echo ($_SESSION['gender'] ?? '') === 'Female' ? 'checked' : ''; ?>> Female</label>
        </div>

        <label>Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required>

        <label>Address</label>
        <input type="text" id="address" name="address" placeholder="Enter your Address" value="<?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?>" required>

        <label>Profile Picture</label>
        <input type="file" name="profile_image" id="newProfileImage">
      </div>
      <div class="modal-footer">
        <div class="button-row">
          <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn-solid">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="changePassword" class="change-password">
  <div class="change-modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <div class="modal-header">
      <h2>Change Password</h2>
    </div>
    <form id="changePasswordForm" method="POST" style="display:contents;">
      <div class="modal-body">
        <label>Password</label>
        <input type="password" id="currentPassword" required>
        <label>New Password</label>
        <input type="password" id="newPassword" required>
        <label>Confirm Password</label>
        <input type="password" id="confirmPassword" required>
      </div>
      <div class="modal-footer">
        <div class="button-row">
          <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn-solid">Save New Password</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
  const userId = "<?php echo isset($_SESSION['school_id']) ? $_SESSION['school_id'] : ''; ?>";
  const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
</script>
<script src="js/edit.js"></script>
<script src="js/timeout.js"></script>
<script src="js/session_monitor.js"></script>
</body>
</html>