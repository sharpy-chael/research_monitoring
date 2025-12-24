<?php
    session_start();
    if (!isset($_SESSION['submit'])) {
        header("Location: home.php");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/documents.css">
</head>
<body>
    <?php include("templates/aside_student.html"); ?>
    <div class="main-content">
        <div class="download-wrapper">
            <h1 class="doc-title">Download Documents</h1>
            <div class="doc-list">
                <div class="doc-item">
                    <div class="left">
                        <i class="ri-clipboard-line"></i>
                        <span>Document 1</span>
                    </div>
                    <a href="files/document1.docx" download class="download-btn">Download</a>
                </div>
                <div class="doc-item">
                    <div class="left">
                        <i class="ri-clipboard-line"></i>
                        <span>Document 2</span>
                    </div>
                    <a href="files/document2.docx" download class="download-btn">Download</a>
                </div>
                <div class="doc-item">
                    <div class="left">
                        <i class="ri-clipboard-line"></i>
                        <span>Document 3</span>
                    </div>
                    <a href="files/document3.docx" download class="download-btn">Download</a>
                </div>
                <div class="doc-item">
                    <div class="left">
                        <i class="ri-clipboard-line"></i>
                        <span>Document 4</span>
                    </div>
                    <a href="files/document4.docx" download class="download-btn">Download</a>
                </div>
                <div class="doc-item">
                    <div class="left">
                        <i class="ri-clipboard-line"></i>
                        <span>Document 5</span>
                    </div>
                    <a href="files/document5.docx" download class="download-btn">Download</a>
                </div>
                <div class="doc-item">
                    <div class="left">
                        <i class="ri-clipboard-line"></i>
                        <span>Document 6</span>
                    </div>
                    <a href="files/document6.docx" download class="download-btn">Download</a>
                </div>
                <div class="doc-item">
                    <div class="left">
                        <i class="ri-clipboard-line"></i>
                        <span>Document 7</span>
                    </div>
                    <a href="files/document7.docx" download class="download-btn">Download</a>
                </div>
            </div>
        </div>
        <div class="space"></div>
    </div>
    
</body>
</html>
