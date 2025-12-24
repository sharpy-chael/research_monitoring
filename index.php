<?php
include("connect.php");
session_start();

if (!isset($_SESSION['submit'])) {
    header('Location: home.php');
    exit;
}

$school_id = $_SESSION['school_id'];

/* ===========================
   HANDLE TITLE SUBMISSION
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_title') {
    header('Content-Type: application/json');

    $newTitle = trim($_POST['title'] ?? '');
    $group_id_post = $_POST['group_id'] ?? null;

    if (!$newTitle) {
        echo json_encode(['success' => false, 'message' => 'Title cannot be empty']);
        exit;
    }

    try {
        // Only allow leader to update
        $stmtLeader = $con->prepare("SELECT is_leader FROM student WHERE school_id = :school_id LIMIT 1");
        $stmtLeader->execute(['school_id' => $school_id]);
        $user = $stmtLeader->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['is_leader']) {
            echo json_encode(['success' => false, 'message' => 'Only the group leader can propose a title']);
            exit;
        }

        // Update the group's title and set status to 'pending'
        $updateStmt = $con->prepare("
            UPDATE groups 
            SET research_title = :title, title_status = 'pending' 
            WHERE id = :group_id
        ");
        $updateStmt->execute([
            'title' => $newTitle,
            'group_id' => $group_id_post
        ]);

        $_SESSION['research_title'] = $newTitle;

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ===========================
   HANDLE FILE UPLOAD
=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
    header('Content-Type: application/json');

    $document_type = $_POST['document_type'] ?? '';
    $group_id_post = $_POST['group_id'] ?? null;

    if (!$document_type || !in_array($document_type, ['UREC Form', 'UREC Clearance'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid document type']);
        exit;
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit;
    }

    try {
        // Only allow leader to upload
        $stmtLeader = $con->prepare("SELECT is_leader FROM student WHERE school_id = :school_id LIMIT 1");
        $stmtLeader->execute(['school_id' => $school_id]);
        $user = $stmtLeader->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['is_leader']) {
            echo json_encode(['success' => false, 'message' => 'Only the group leader can upload documents']);
            exit;
        }

        // File handling
        $file = $_FILES['file'];
        $originalFilename = basename($file['name']);
        $fileExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        
        // Validate file type (PDF only)
        if ($fileExtension !== 'pdf') {
            echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed']);
            exit;
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $uniqueFilename = uniqid() . '_' . time() . '.pdf';
        $filePath = $uploadDir . $uniqueFilename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save file']);
            exit;
        }

        // Save to database
        $insertStmt = $con->prepare("
            INSERT INTO urec_documents (group_id, school_id, document_type, file_path, original_filename, status, uploaded_at)
            VALUES (:group_id, :school_id, :document_type, :file_path, :original_filename, 'pending', NOW())
        ");
        $insertStmt->execute([
            'group_id' => $group_id_post,
            'school_id' => $school_id,
            'document_type' => $document_type,
            'file_path' => $filePath,
            'original_filename' => $originalFilename
        ]);

        echo json_encode(['success' => true, 'message' => 'File uploaded successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ===========================
   GET GROUP & ROLE
=========================== */
$stmt = $con->prepare("
    SELECT s.group_id, s.is_leader, g.research_title, g.title_status
    FROM student s
    LEFT JOIN groups g ON s.group_id = g.id
    WHERE s.school_id = :school_id
");
$stmt->execute(['school_id' => $school_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$group_id = $user['group_id'];
$is_leader = $user['is_leader'];
$groupTitle = $user['research_title'] ?? '';
$titleStatus = $user['title_status'] ?? 'missing';

$_SESSION['research_title'] = $groupTitle;

/* ===========================
   GET UREC DOCUMENTS STATUS
=========================== */
$urecFormStatus = 'missing';
$urecClearanceStatus = 'missing';

if ($group_id) {
    // Get latest UREC Form status
    $urecFormStmt = $con->prepare("
        SELECT status FROM urec_documents 
        WHERE group_id = :group_id AND document_type = 'UREC Form'
        ORDER BY uploaded_at DESC LIMIT 1
    ");
    $urecFormStmt->execute(['group_id' => $group_id]);
    $urecForm = $urecFormStmt->fetch(PDO::FETCH_ASSOC);
    if ($urecForm) {
        $urecFormStatus = $urecForm['status'];
    }

    // Get latest UREC Clearance status
    $urecClearanceStmt = $con->prepare("
        SELECT status FROM urec_documents 
        WHERE group_id = :group_id AND document_type = 'UREC Clearance'
        ORDER BY uploaded_at DESC LIMIT 1
    ");
    $urecClearanceStmt->execute(['group_id' => $group_id]);
    $urecClearance = $urecClearanceStmt->fetch(PDO::FETCH_ASSOC);
    if ($urecClearance) {
        $urecClearanceStatus = $urecClearance['status'];
    }
}

/* ===========================
   CALCULATE GROUP PROGRESS
=========================== */
$progressStmt = $con->prepare("
    SELECT task_name, status
    FROM uploads
    WHERE school_id IN (
        SELECT school_id FROM student WHERE group_id = :group_id
    )
    ORDER BY uploaded_at DESC
");
$progressStmt->execute(['group_id' => $group_id]);
$allUploads = $progressStmt->fetchAll(PDO::FETCH_ASSOC);

/* latest upload per task */
$uploadMap = [];
foreach ($allUploads as $upload) {
    if (!isset($uploadMap[$upload['task_name']])) {
        $uploadMap[$upload['task_name']] = $upload;
    }
}

/* count approved */
$approvedCount = 0;
foreach ($uploadMap as $upload) {
    if ($upload['status'] === 'approved') {
        $approvedCount++;
    }
}

$progressPercentage = round(($approvedCount / 6) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/home.css">
    <title>Student Page</title>
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
        }
        .modal.open {
            display: block;
        }
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            position: absolute;
            margin: auto;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 8px;
            min-width: 200px;
            max-width: 50%;
        }
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        .modal-close:hover {
            color: #333;
        }
        .file-upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload-area:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }
        .file-upload-area.dragover {
            border-color: #007bff;
            background: #e7f3ff;
        }
        .file-info {
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            display: none;
        }
        .file-info.show {
            display: block;
        }
        #fileInput {
            display: none;
        }
        button {
            padding: 5px 10px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            color: white;
            background: #a00;
        }
        #cancelUploadBtn {
            background: #6c757d;
            color: white;
        }
        #submitUploadBtn {
            background: #007bff;
            color: white;
        }
        #submitUploadBtn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>

<?php include("templates/aside_student.html"); ?>

<main class="main-content">
    <h1>Dashboard</h1>

    <div class="info">
        <p class="welcome">
            <strong>Welcome,</strong> <?= htmlspecialchars($_SESSION['name']) ?>.
        </p>
        <p class="research-title">
            <strong>Research Title:</strong>
            <?= htmlspecialchars($groupTitle ?: 'No title yet') ?>
        </p>
    </div>

    <div class="card">
        <h2>Research Progress</h2>
        <div class="progress-text">
            <?= $approvedCount ?> of 5 chapters approved (<?= $progressPercentage ?>%)
        </div>
        <div class="progress-bar">
            <div class="progress-bar-fill" style="width: <?= $progressPercentage ?>%;"><?= $progressPercentage > 0 ? $progressPercentage . '%' : '' ?></div>
        </div>
    </div>

    <div class="status">
        <?php
        $progressItems = [
            "Title" => ['status' => $titleStatus],
            "Proposal" => ['status' => 'missing'],
            "UREC Form" => ['status' => $urecFormStatus],
            "UREC Clearance" => ['status' => $urecClearanceStatus],
            "Final Defense" => ['status' => 'missing'],
            "Copyright / IP" => ['status' => 'missing']
        ];
        
        foreach ($progressItems as $label => $data):
            $statusClass = 'status-missing';
            $statusText = 'Missing';
            if ($data['status'] === 'pending') { $statusClass = 'status-pending'; $statusText = 'Pending'; }
            elseif ($data['status'] === 'approved') { $statusClass = 'status-approved'; $statusText = 'Approved'; }
            elseif ($data['status'] === 'rejected') { $statusClass = 'status-rejected'; $statusText = 'Rejected'; }
        ?>
            <div class="cards">
                <h4><?= $label ?></h4>
                <p>Status: <span class="status-badge <?= $statusClass ?>" id="<?= str_replace(' ', '', $label) ?>StatusBadge"><?= $statusText ?></span></p>
                <?php if($is_leader): ?>
                    <?php if($label === "Title"): ?>
                        <button class="edit-title-btn">Set / Edit Title</button>
                    <?php elseif($label === "UREC Form" || $label === "UREC Clearance"): ?>
                        <button class="upload-doc-btn" data-doc-type="<?= $label ?>">Upload</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="chart-wrapper">
        <div id="root" style="margin-top: 40px;"></div>
        <script type="module" src="./react-app/dist/assets/student-DV9ATBan.js" defer></script>
    </div>
    <div class="space"></div>
</main>

<!-- Title Modal -->
<div class="modal" id="titleModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3>Research Title</h3>
        <input type="text" id="titleInput" placeholder="Enter your research title">
        <div style="margin-top:15px; text-align:right;">
            <button id="cancelTitleBtn">Cancel</button>
            <button id="submitTitleBtn">Submit</button>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal" id="uploadModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h3 id="uploadModalTitle">Upload Document</h3>
        
        <div class="file-upload-area" id="fileUploadArea">
            <i class="ri-upload-cloud-line" style="font-size: 48px; color: #007bff;"></i>
            <p style="margin: 10px 0;">Click to browse or drag and drop your file here</p>
            <p style="font-size: 12px; color: #999;">Only PDF files are allowed</p>
        </div>
        
        <input type="file" id="fileInput" accept=".pdf">
        
        <div class="file-info" id="fileInfo">
            <i class="ri-file-pdf-line"></i>
            <span id="fileName"></span>
            <span id="fileSize" style="color: #999; font-size: 12px;"></span>
        </div>
        
        <div style="margin-top:15px; text-align:right;">
            <button id="cancelUploadBtn">Cancel</button>
            <button id="submitUploadBtn" disabled>Upload</button>
        </div>
    </div>
</div>

<script>
const titleModal = document.getElementById('titleModal');
const titleInput = document.getElementById('titleInput');
const titleStatusBadge = document.getElementById('TitleStatusBadge');

const uploadModal = document.getElementById('uploadModal');
const uploadModalTitle = document.getElementById('uploadModalTitle');
const fileUploadArea = document.getElementById('fileUploadArea');
const fileInput = document.getElementById('fileInput');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');
const submitUploadBtn = document.getElementById('submitUploadBtn');

let currentDocType = '';
let selectedFile = null;

// Title Modal
document.querySelector('.edit-title-btn').addEventListener('click', () => {
    titleInput.value = "<?= htmlspecialchars($groupTitle) ?>";
    titleModal.classList.add('open');
});

titleModal.querySelector('.modal-overlay').addEventListener('click', () => titleModal.classList.remove('open'));
titleModal.querySelector('.modal-close').addEventListener('click', () => titleModal.classList.remove('open'));
document.getElementById('cancelTitleBtn').addEventListener('click', () => titleModal.classList.remove('open'));

document.getElementById('submitTitleBtn').addEventListener('click', async () => {
    const newTitle = titleInput.value.trim();
    if (!newTitle) return alert("Title cannot be empty");

    const formData = new FormData();
    formData.append('title', newTitle);
    formData.append('group_id', <?= $group_id ?>);
    formData.append('action', 'update_title');

    try {
        const res = await fetch('index.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert('Title submitted successfully!');
            titleStatusBadge.textContent = 'Pending';
            titleStatusBadge.className = 'status-badge status-pending';
            titleModal.classList.remove('open');
        } else {
            alert(data.message || 'Failed to submit title');
        }
    } catch (e) {
        alert('Error: ' + e.message);
    }
});

// Upload Modal
document.querySelectorAll('.upload-doc-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        currentDocType = btn.getAttribute('data-doc-type');
        uploadModalTitle.textContent = `Upload ${currentDocType}`;
        selectedFile = null;
        fileInput.value = '';
        fileInfo.classList.remove('show');
        submitUploadBtn.disabled = true;
        uploadModal.classList.add('open');
    });
});

uploadModal.querySelector('.modal-overlay').addEventListener('click', () => uploadModal.classList.remove('open'));
uploadModal.querySelector('.modal-close').addEventListener('click', () => uploadModal.classList.remove('open'));
document.getElementById('cancelUploadBtn').addEventListener('click', () => uploadModal.classList.remove('open'));

// File upload area click
fileUploadArea.addEventListener('click', () => {
    fileInput.click();
});

// Drag and drop
fileUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    fileUploadArea.classList.add('dragover');
});

fileUploadArea.addEventListener('dragleave', () => {
    fileUploadArea.classList.remove('dragover');
});

fileUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    fileUploadArea.classList.remove('dragover');
    
    if (e.dataTransfer.files.length > 0) {
        handleFileSelect(e.dataTransfer.files[0]);
    }
});

// File input change
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFileSelect(e.target.files[0]);
    }
});

function handleFileSelect(file) {
    // Validate file type
    if (file.type !== 'application/pdf') {
        alert('Only PDF files are allowed');
        return;
    }
    
    // Validate file size (max 10MB)
    if (file.size > 10 * 1024 * 1024) {
        alert('File size must be less than 10MB');
        return;
    }
    
    selectedFile = file;
    fileName.textContent = file.name;
    fileSize.textContent = `(${(file.size / 1024).toFixed(2)} KB)`;
    fileInfo.classList.add('show');
    submitUploadBtn.disabled = false;
}

// Submit upload
submitUploadBtn.addEventListener('click', async () => {
    if (!selectedFile) return;
    
    submitUploadBtn.disabled = true;
    submitUploadBtn.textContent = 'Uploading...';
    
    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('document_type', currentDocType);
    formData.append('group_id', <?= $group_id ?>);
    formData.append('action', 'upload_file');
    
    try {
        const res = await fetch('index.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            alert('File uploaded successfully!');
            
            // Update status badge
            const badgeId = currentDocType.replace(' ', '') + 'StatusBadge';
            const badge = document.getElementById(badgeId);
            if (badge) {
                badge.textContent = 'Pending';
                badge.className = 'status-badge status-pending';
            }
            
            uploadModal.classList.remove('open');
        } else {
            alert(data.message || 'Failed to upload file');
        }
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        submitUploadBtn.disabled = false;
        submitUploadBtn.textContent = 'Upload';
    }
});
</script>

</body>
</html>