<?php
error_reporting(0);
include("connect.php");
session_start();

if (!isset($_SESSION['submit'])){
    header('Location: home.php');
    exit;
}
$advisor_name = $_SESSION['name'] ?? '';

if (empty($advisor_name)) {
    header('Location: home.php');
    exit;
}

$stmt = $con->prepare("SELECT * FROM advisor WHERE name = :name");
$stmt->execute(['name' => $advisor_name]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $_SESSION['images'] = $row['images'] ?? '';
    $_SESSION['program'] = $row['program'] ?? ''; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Advisor Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
  <link rel="stylesheet" href="css/home.css">
  <link rel="stylesheet" href="css/profile.css">
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
      <label><input type="radio" name="gender" value="Male"> Male</label>
      <label><input type="radio" name="gender" value="Female"> Female</label>
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
        <input type="text" id="displayAdvisorID" readonly>
      </div>
      <div class="form-group">
        <label>Department</label>
        <input type="text" id="displayDepartment" readonly>
      </div>
    </div>

  </section>
</main>

<!-- Edit Modal -->
<div id="editModal" class="edit-modal">
  <div class="edit-modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <h2>Edit Personal Information</h2>

    <form id="editForm" action="advisor_update.php" method="POST" enctype="multipart/form-data">
      <label>Full Name</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($_SESSION['name']); ?>" required>

      <label>Advisor ID</label>
      <input type="text" id="advisorID" placeholder="Enter your Advisor ID">

      <label>Department Advisee</label>
      <input type="text" id="department" placeholder="Enter the Department" required>

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
  const userId = "<?php echo htmlspecialchars($_SESSION['id'] ?? ''); ?>";
</script>

<script src="js/edit.js"></script>
<script src="js/timeout.js"></script>

</body>
</html>