<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php"; 
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$userId = $_SESSION['id_users'] ?? 0;

if (!$userId) {
    echo "<div style='padding:20px; color:red'>Помилка: Авторизуйтесь для зворотного зв'язку.</div>";
    exit;
}
?>

<link href="/css/feedback.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/css/feedback.css'); ?>" rel="stylesheet" type="text/css"/>
<link href="/css/personal_acc.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/css/personal_acc.css'); ?>" rel="stylesheet" type="text/css"/>
<link href="/css/quill.snow.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/css/quill.snow.css'); ?>" rel="stylesheet" type="text/css"/>


<div class="feedback-page-wrapper">
    <div class="profile-section">
        <form id="feedbackForm" class="profile-form" onsubmit="sendFeedback(event)" novalidate>
            <div class="feedback-row">
                <input type="text" id="feedbackSubject" name="subject" placeholder="Тема" class="gmail-input" autocomplete="off">
                <div class="error-message"></div>
            </div>

            <div class="editor-outer-container">
                <div id="editor-container" style="height: 350px;"></div>
                <div class="error-message" id="editor-error"></div>
            </div>

            <div class="feedback-action-bar">
                <div class="action-left">
                    <button type="submit" class="btn-send-blue">Надіслати</button>
                    <label class="action-icon-label" for="fileInput" title="Прикріпити файли">
                        <img src="/img/attach.svg" width="20" alt="attach">
                    </label>
                    <input type="file" id="fileInput" multiple style="display:none" onchange="handleFileSelect(event)">
                </div>
                <div id="attachedFilesList" class="attached-files-row"></div>
            </div>
        </form>
    </div>
</div>

