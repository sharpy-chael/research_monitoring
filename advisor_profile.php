<?php
error_reporting(0);
session_start();
include("connect.php");
include('php/get_setting.php');
include("check_session.php");

if (!isset($_SESSION['submit'])){
    header('Location: home.php');
    exit;
}
$advisor_name = $_SESSION['name'] ?? '';
$notificationsStmt = $con->prepare("
    SELECT id, title, message, priority, created_at, status
    FROM system_notifications
    WHERE (
        recipient_type = 'all' 
        OR recipient_type = 'advisors'
        OR (recipient_type = 'specific' AND recipient_id = :user_id)
    )
    AND status != 'deleted'
    ORDER BY created_at DESC
    LIMIT 10
");
$notificationsStmt->execute([
    'user_id' => $_SESSION['id']
]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($advisor_name)) {
    header('Location: home.php');
    exit;
}

$stmt = $con->prepare("SELECT * FROM advisor WHERE name = :name");
$stmt->execute(['name' => $advisor_name]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $_SESSION['images']     = $row['images'] ?? '';
    $_SESSION['program']    = $row['program'] ?? '';
    $_SESSION['advisor_id'] = $row['advisor_id'] ?? '';
    $_SESSION['gender']     = $row['gender'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Advisor Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/profile.css">
  <style>
    .edit-modal,
    .change-password {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }

    .edit-modal.active,
    .change-password.active {
      display: flex;
    }

    .edit-modal-content,
    .change-modal-content {
      background: #fff;
      border-radius: 12px;
      padding: 36px 32px 28px;
      width: 100%;
      max-width: 480px;
      position: relative;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
      max-height: 90vh;
      overflow-y: auto;
    }

    .edit-modal-content h2,
    .change-modal-content h2 {
      font-size: 1.25rem;
      font-weight: 700;
      color: #8b0000;
      margin-bottom: 22px;
      text-align: center;
    }

    .edit-modal-content label,
    .change-modal-content label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: #555;
      margin-bottom: 4px;
      margin-top: 14px;
    }

    .edit-modal-content input[type="text"],
    .edit-modal-content input[type="email"],
    .edit-modal-content input[type="password"],
    .change-modal-content input[type="password"] {
      width: 100%;
      padding: 9px 12px;
      border: 1px solid #ddd;
      border-radius: 7px;
      font-size: 0.9rem;
      background: #f9f9f9;
      box-sizing: border-box;
      transition: border-color 0.2s;
    }

    .edit-modal-content input:focus,
    .change-modal-content input:focus {
      outline: none;
      border-color: #8b0000;
      background: #fff;
    }

    .edit-modal-content input[readonly] {
      background: #efefef;
      color: #888;
      cursor: not-allowed;
    }

    .edit-modal-content .file-label {
      display: flex;
      align-items: center;
      gap: 10px;
      border: 1.5px dashed #ccc;
      border-radius: 7px;
      padding: 10px 12px;
      background: #fafafa;
      cursor: pointer;
      font-size: 0.85rem;
      color: #777;
      margin-top: 4px;
    }

    .edit-modal-content input[type="file"] {
      display: none;
    }

    .button-row {
      display: flex;
      gap: 12px;
      margin-top: 24px;
      justify-content: flex-end;
    }

    .btn-outline {
      padding: 9px 22px;
      border: 2px solid #8b0000;
      background: transparent;
      color: #8b0000;
      border-radius: 7px;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background 0.2s, color 0.2s;
    }

    .btn-outline:hover {
      background: #8b0000;
      color: #fff;
    }

    .btn-solid {
      padding: 9px 22px;
      border: none;
      background: #8b0000;
      color: #fff;
      border-radius: 7px;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background 0.2s;
    }

    .btn-solid:hover {
      background: #6a0000;
    }

    .close-btn {
      position: absolute;
      top: 14px;
      right: 18px;
      font-size: 1.4rem;
      cursor: pointer;
      color: #aaa;
      line-height: 1;
      transition: color 0.2s;
    }

    .close-btn:hover {
      color: #333;
    }
  </style>
</head>
<body>

<?php include("templates/aside_advisor.html"); ?>

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
        <p>Advisor</p>
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
      <input type="text" id="displayEmail" readonly value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>">
    </div>

    <div class="form-group">
      <label>Address</label>
      <input type="text" id="displayAddress" readonly value="<?php echo htmlspecialchars($row['address'] ?? ''); ?>">
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Advisor ID</label>
        <input type="text" id="displayAdvisorID" readonly value="<?php echo htmlspecialchars($row['advisor_id'] ?? ''); ?>">
      </div>
      <div class="form-group">
        <label>Department</label>
        <input type="text" id="displayDepartment" readonly value="<?php echo htmlspecialchars($row['department'] ?? ''); ?>">
      </div>
    </div>

  </section>
</main>

<!-- Edit Modal -->
<div id="editModal" class="edit-modal">
  <div class="edit-modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h2>Edit Personal Information</h2>

    <form id="editForm" action="php/advisor_update.php" method="POST" enctype="multipart/form-data">
      <label>Full Name</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" required>

      <label>Advisor ID</label>
      <input type="text" name="advisor_id" id="advisorID" value="<?php echo htmlspecialchars($row['advisor_id'] ?? ''); ?>" readonly>

      <label>Gender</label>
      <div class="gender-row">
        <label><input type="radio" name="gender" value="Male" <?php echo ($_SESSION['gender'] ?? '') === 'Male' ? 'checked' : ''; ?>> Male</label>
        <label><input type="radio" name="gender" value="Female" <?php echo ($_SESSION['gender'] ?? '') === 'Female' ? 'checked' : ''; ?>> Female</label>
      </div>

      <label>Department Advisee</label>
      <input type="text" name="department" id="department" value="<?php echo htmlspecialchars($row['department'] ?? ''); ?>" placeholder="Enter the Department" required>

      <label>Email</label>
      <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>" placeholder="Enter your email" required>

      <label>Address</label>
      <input type="text" name="address" id="address" value="<?php echo htmlspecialchars($row['address'] ?? ''); ?>" placeholder="Enter your Address" required>

      <label>Profile Picture</label>
      <label class="file-label" for="newProfileImage">
        <i class="ri-upload-2-line"></i>
        <span id="fileNameDisplay">Choose an image (JPG, PNG)</span>
      </label>
      <input type="file" name="profile_image" id="newProfileImage" accept=".jpg,.jpeg,.png"
             onchange="document.getElementById('fileNameDisplay').textContent = this.files[0]?.name || 'Choose an image (JPG, PNG)'">

      <div class="button-row">
        <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-solid">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Change Password Modal -->
<div id="changePassword" class="change-password">
  <div class="change-modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h2>Change Password</h2>

    <form id="changePasswordForm" method="POST">
      <label>Current Password</label>
      <input type="password" id="currentPassword" required>

      <label>New Password</label>
      <input type="password" id="newPassword" required>

      <label>Confirm Password</label>
      <input type="password" id="confirmPassword" required>

      <div class="button-row">
        <button type="button" class="btn-outline" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-solid">Save New Password</button>
      </div>
    </form>

  </div>
</div>

<script>
  const userId = "<?php echo htmlspecialchars($_SESSION['id'] ?? ''); ?>";
</script>

<script src="js/edit.js"></script>
<script src="js/timeout.js"></script>
<script>
  const SESSION_TIMEOUT_MINUTES = <?= getSettingInt($con, 'session_timeout', 30) ?>;
</script>
<script src="js/session_monitor.js"></script>
</body>
</html>