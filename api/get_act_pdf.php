<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (file_exists('../config.php')) {
    include '../config.php';
} else {
    include 'config.php'; 
}

$userId = $_SESSION['id_users'] ?? 0;
$orgId = $_SESSION['id_organizations'] ?? ($IDOrganizations ?? 0); 

if (!$userId) {
    die("Доступ заборонено. Будь ласка, авторизуйтесь.");
}

$actId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$actId) {
    die("Не вказано номер документу.");
}

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');
    
// Отримуємо файл з нової таблиці, одразу перевіряючи права (ACCESS)
$sql = "
    SELECT di.DOC_PDF 
    FROM DOC_COUNTER_READINGS di
    INNER JOIN ACCESS acc 
        ON di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT 
       AND di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
    WHERE di.ID = ? 
      AND acc.ID_USERS = ?
      AND di.ID_ORGANIZATIONS = ?
    LIMIT 1
";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "iii", $actId, $userId, $orgId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $pdfContent);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($pdfContent) {
    if (ob_get_length()) ob_clean();
    $fileName = "Акт_показників_№_{$actId}.pdf";
    $encodedFileName = rawurlencode($fileName);
    header("Content-type: application/pdf");
    header("Content-Disposition: inline; filename=\"{$fileName}\"; filename*=UTF-8''{$encodedFileName}");
    echo $pdfContent;
    exit;
} else {
    echo "Документ не знайдено або у вас немає прав на його перегляд.";
}
?>