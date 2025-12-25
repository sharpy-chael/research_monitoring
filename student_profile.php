<?php
error_reporting(0);
include("connect.php");
session_start();
if (!isset($_SESSION['submit'])){
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
$notificationsStmt->execute([
    'user_id' => $_SESSION['id']
]);
$notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Count unread notifications
$unreadCount = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));

$stmt = $con->prepare("SELECT * FROM student WHERE school_id = :school_id");
$stmt->execute(['school_id' => $school_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $_SESSION['images'] = $row['images'];
    $_SESSION['research_title'] = $row['research_title'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Profile</title>
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
    <label><input type="radio" name="gender" value="Male"> Male</label>
    <label><input type="radio" name="gender" value="Female"> Female</label>
  </div>

  <div class="form-group">
    <label>Full Name</label>
    <input type="text" readonly value="<?php echo htmlspecialchars($_SESSION['name']); ?>">
  </div>

  <div class="form-group">
    <label>Email</label>
    <input type="text" id="displayEmail" readonly value="">
  </div>

  <div class="form-group">
    <label>Address</label>
    <input type="text" id="displayAddress" readonly value="">
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>School ID</label>
      <input type="text" readonly value="<?php echo htmlspecialchars($_SESSION['school_id']); ?>">
    </div>
    <div class="form-group">
      <label>Course</label>
      <input type="text" readonly value="<?php echo htmlspecialchars($_SESSION['program']); ?>">
    </div>
  </div>

  <h3 class="recent-head">Recent Uploads</h3>

  <div class="uploads-container">
    <div class="recent-upload-grid">

    <?php
    // Fetch uploads
    $stmtUploads = $con->prepare("
        SELECT upload_id, task_name, file_path, original_filename, uploaded_at 
        FROM uploads 
        WHERE school_id = :school_id
        ORDER BY uploaded_at DESC
    ");
    $stmtUploads->execute(['school_id' => $school_id]);
    $uploads = $stmtUploads->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($uploads)):
        foreach ($uploads as $row):
    ?>
        <div class="upload-card">
            <div class="upload-card-header">
                <i class="ri-file-3-line file-icon"></i>

                <div class="menu-wrapper">
                    <i class="ri-more-2-fill menu-toggle"></i>
                    <div class="menu-dropdown">
                        <a href="<?= $row['file_path'] ?>" download>Download</a>
                        <button class="delete delete-btn" data-id="<?= $row['upload_id'] ?>">Delete</button>
                    </div>
                </div>
            </div>

            <div class="upload-card-body">
              <?php 
                    $filename = $row['original_filename'];
                ?>
                <span class="file-title">
                    <?= htmlspecialchars($row['task_name']) ?> 
                </span>

                <p class="file-filename">
                    <?= htmlspecialchars($filename) ?>
                </p>

                <p class="file-date">
                    <?= date("M d, Y • h:i A", strtotime($row['uploaded_at'])) ?>
                </p>
            </div>
        </div>
    <?php
        endforeach;
    else:
        echo "<p class='no-upload-text'>No uploads yet.</p>";
    endif;
    ?>

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
      btn.addEventListener("click", function (e) {
          e.preventDefault();

          let uploadId = this.getAttribute("data-id");

          if (!confirm("Delete this file?")) return;

          fetch("delete_upload.php", {
              method: "POST",
              headers: { "Content-Type": "application/x-www-form-urlencoded" },
              body: "upload_id=" + uploadId
          })
          .then(res => res.json())
          .then(data => {
              if (data.status === "success") {
                  alert("File deleted successfully!");
                  location.reload();
              } else {
                  alert(data.message);
              }
          });
      });
  });

  </script>

</section>
</main>

<!-- Edit Modal -->
<div id="editModal" class="edit-modal">
  <div class="edit-modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h2>Edit Personal Information</h2>

    <form id="editForm" action="update_profile.php" method="POST" enctype="multipart/form-data">
      <label>Full Name</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" required>

      <label>Program</label>
      <input type="text" name="program" value="<?php echo htmlspecialchars($_SESSION['program']); ?>" required>

      <label>Email</label>
      <input type="email" id="email" placeholder="Enter your email" required>

      <label>Address</label>
      <input type="text" id="address" placeholder="Enter your Address" required>

      <label>Profile Picture</label>
      <input type="file" name="profile_image" id="newProfileImage">

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
      <label>Password</label>
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
  const userId = "<?php echo isset($_SESSION['school_id']) ? $_SESSION['school_id'] : ''; ?>";
</script>

<script src="js/edit.js"></script>
<script src="js/timeout.js"></script>

</body>
</html>
