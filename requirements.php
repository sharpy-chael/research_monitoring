<?php
include('connect.php');
session_start();

if (!isset($_SESSION['submit'])){
    header('Location: home.php');
    exit;
}

$school_id = $_SESSION['school_id'];

// Fetch student's group and adviser information
$studentStmt = $con->prepare("
    SELECT s.group_id, a.name as adviser_name
    FROM student s
    LEFT JOIN groups g ON s.group_id = g.id
    LEFT JOIN advisor a ON g.adviser_id = a.id
    WHERE s.school_id = :school_id
    LIMIT 1
");
$studentStmt->execute(['school_id' => $school_id]);
$studentInfo = $studentStmt->fetch(PDO::FETCH_ASSOC);

$adviserName = $studentInfo && !empty($studentInfo['adviser_name']) ? $studentInfo['adviser_name'] : 'Adviser';
$group_id = $studentInfo['group_id'];

// Fetch assigned SDGs for the group
$sdgsStmt = $con->prepare("
    SELECT us.name
    FROM un_sdgs us
    JOIN group_sdgs gs ON us.id = gs.sdg_id
    WHERE gs.group_id = :group_id
    ORDER BY us.name
");
$sdgsStmt->execute(['group_id' => $group_id]);
$assignedSdgs = $sdgsStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch assigned Research Thrusts for the group
$thrustsStmt = $con->prepare("
    SELECT rt.name
    FROM research_thrusts rt
    JOIN group_thrusts gt ON rt.id = gt.thrust_id
    WHERE gt.group_id = :group_id
    ORDER BY rt.name
");
$thrustsStmt->execute(['group_id' => $group_id]);
$assignedThrusts = $thrustsStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch uploads
$uploadsStmt = $con->prepare("
    SELECT u.task_name, u.status, u.comment, u.file_path, u.original_filename, u.uploaded_at
    FROM uploads u
    JOIN student s ON u.school_id = s.school_id
    WHERE s.group_id = :group_id
    ORDER BY u.uploaded_at DESC
");
$uploadsStmt->execute(['group_id' => $group_id]);
$uploads = $uploadsStmt->fetchAll(PDO::FETCH_ASSOC);

// Latest upload per task
$uploadMap = [];
foreach ($uploads as $upload) {
    if (!isset($uploadMap[$upload['task_name']])) {
        $uploadMap[$upload['task_name']] = $upload;
    }
}

// Task order
$tasks = [
    "Chapter 1",
    "Chapter 2",
    "Chapter 3",
    "Chapter 4",
    "Chapter 5",
    "Final Research Output"
];

// Determine active (unlocked) task
$activeTask = null;
foreach ($tasks as $task) {
    if (!isset($uploadMap[$task]) || $uploadMap[$task]['status'] !== 'approved') {
        $activeTask = $task;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requirements</title>

    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/requirements.css">
</head>

<body>

<?php include("templates/aside_student.html"); ?>

<div class="wrapper">

    <h1 class="req-title">Requirements</h1>

    <!-- SDGs and Thrusts Banner -->
    <?php if (!empty($assignedSdgs) || !empty($assignedThrusts)): ?>
    <div class="assignments-banner">
        <h2><i class="ri-bookmark-line"></i> Your Research Assignments</h2>
        
        <div class="assignments-grid">
            <?php if (!empty($assignedSdgs)): ?>
            <div class="assignment-section">
                <h3><i class="ri-global-line"></i> UN SDGs</h3>
                <div class="assignment-tags">
                    <?php foreach ($assignedSdgs as $sdg): ?>
                        <span class="assignment-tag">
                            <i class="ri-checkbox-circle-fill"></i>
                            <?= htmlspecialchars($sdg) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($assignedThrusts)): ?>
            <div class="assignment-section">
                <h3><i class="ri-flashlight-line"></i> Research Thrusts</h3>
                <div class="assignment-tags">
                    <?php foreach ($assignedThrusts as $thrust): ?>
                        <span class="assignment-tag thrust">
                            <i class="ri-focus-3-line"></i>
                            <?= htmlspecialchars($thrust) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="req-container">

        <?php foreach ($tasks as $task): 
            $taskUpload = $uploadMap[$task] ?? null;
            $status = $taskUpload['status'] ?? 'missing';
            $comment = $taskUpload['comment'] ?? '';

            $statusClass = 'status-missing';
            $statusText = 'Missing';

            if ($status === 'approved') {
                $statusClass = 'status-approved';
                $statusText = 'Approved';
            } elseif ($status === 'rejected') {
                $statusClass = 'status-rejected';
                $statusText = 'Rejected';
            } elseif ($status === 'pending') {
                $statusClass = 'status-pending';
                $statusText = 'Pending';
            }

            $isActive = ($task === $activeTask);
            $isApproved = ($status === 'approved');
        ?>

        <div class="req-card <?= !$isActive && !$isApproved ? 'locked' : '' ?>">
            <div class="req-header">
                <span class="req-title-text"><?= $task ?></span>

                <?php if ($isActive): ?>
                    <button class="add-btn">+ Add Work</button>
                <?php else: ?>
                    <button class="add-btn disabled" disabled>
                        <?= $isApproved ? 'Completed' : 'Locked' ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="req-body">
                <div class="left-info">
                    <i class="ri-file-list-3-line"></i>
                    <span class="status-text">
                        Status:
                        <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                    </span>
                </div>

                <form action="upload_handler.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="task_name" value="<?= $task ?>">
                    <input type="hidden" name="school_id" value="<?= $school_id ?>">

                    <label class="choose-file-btn">
                        Choose File
                        <input type="file"
                               name="file_upload"
                               <?= $isActive ? '' : 'disabled' ?>
                               onchange="this.form.submit()">
                    </label>

                    <button type="submit" style="display:none;"></button>
                </form>
            </div>

            <div class="add-comment"
                data-task="<?= htmlspecialchars($task) ?>"
                data-comment="<?= htmlspecialchars($comment) ?>"
                data-filename="<?= $taskUpload ? htmlspecialchars($taskUpload['original_filename']) : '' ?>"
                data-filedate="<?= $taskUpload ? date("M d, Y", strtotime($taskUpload['uploaded_at'])) : '' ?>"
                data-filepath="<?= $taskUpload ? htmlspecialchars($taskUpload['file_path']) : '' ?>"
                data-adviser="<?= htmlspecialchars($adviserName) ?>">
                View Comment
            </div>
        </div>

        <?php endforeach; ?>

    </div>

    <p class="note">Note: Chapter 1 – Chapter 5 should be drafts</p>

</div>

<!-- Comment Modal -->
<div class="modal" id="commentModal">
    <div class="modal-overlay"></div>

    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3 id="modalTitle">Comment</h3>

        <div class="modal-body">
            <div class="comment-section">
                <p class="comment-empty">No comments yet.</p>

                <div class="comment-with-data" style="display:none;">
                    <div class="comment-text-wrapper">
                        <p><span class="adviser-name"></span><span class="comment-text"></span></p>
                    </div>

                    <a href="#" class="file-info-modal" id="fileDownloadLink" download>
                        <i class="ri-folder-line"></i>
                        <span class="file-name"></span>
                        <span class="file-date"></span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('commentModal');

document.querySelectorAll('.add-comment').forEach(btn => {
    btn.addEventListener('click', () => {
        const task = btn.dataset.task;
        const comment = btn.dataset.comment;
        const filename = btn.dataset.filename;
        const filedate = btn.dataset.filedate;
        const filepath = btn.dataset.filepath;
        const adviser = btn.dataset.adviser;

        document.getElementById('modalTitle').textContent = task + " – Adviser Comment";

        if (comment && comment.trim() !== "") {
            modal.querySelector('.adviser-name').textContent = adviser + ": ";
            modal.querySelector('.comment-text').textContent = comment;
            document.getElementById('fileDownloadLink').href = filepath;
            modal.querySelector('.file-name').textContent = filename;
            modal.querySelector('.file-date').textContent = filedate;

            modal.querySelector('.comment-with-data').style.display = "block";
            modal.querySelector('.comment-empty').style.display = "none";
        } else {
            modal.querySelector('.comment-empty').style.display = "block";
            modal.querySelector('.comment-with-data').style.display = "none";
        }

        modal.classList.add('open');
    });
});

modal.querySelector('.modal-overlay').onclick =
modal.querySelector('.modal-close').onclick = () => modal.classList.remove('open');
</script>

</body>
</html>