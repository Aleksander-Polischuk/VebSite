<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;

if (!$selectedCounteragentId) {
    echo "<div class='faq-error-msg'>Будь ласка, оберіть підприємство у списку зверху.</div>";
    exit;
}

$sql = "SELECT question, content_data FROM REF_POPULAR_QUESTIONS WHERE is_active = 1 ORDER BY sort_order ASC";
$res = mysqli_query($link, $sql);

$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon faq-pointer-none" width="16" height="16" alt="">';
?>

<link href="../../css/Popular_Questions.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header">
    <h3 class="faq-header-title">Поширені запитання</h3>
</div>

<div class="faq-list-container">
    <?php if ($res && mysqli_num_rows($res) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($res)): ?>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <div class="faq-icon-wrapper"><?php echo $caret_icon; ?></div>
                    <?php echo htmlspecialchars($row['question']); ?>
                </div>
                
                <div class="faq-answer">
                    <div class="quill-content">
                        <?php 
                            $cleanHtml = str_replace(['about:/uploads', 'about:blank/uploads'], '/uploads', $row['content_data']);
                            echo $cleanHtml; 
                        ?>    
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>