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
$orgId = $IDOrganizations ?? 0;

// Перевірка авторизації
if (!$userId) {
    die("Доступ заборонено. Будь ласка, авторизуйтесь.");
}

$invoiceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? (int)$_GET['type'] : 0;

if (!$invoiceId) {
    die("Не вказано номер документу.");
}

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

if ($Type == 0) {
    $FieldName = "di.DOC_PDF";	
} else if ($Type == 1) {
    $FieldName = "di.DOC_PDF_SIGN_ORG";
} else if ($Type == 2) {	
    $FieldName = "di.DOC_PDF_SIGN_COUNTERAGENT";	
} else {
  exit;  
}
    
// Отримуємо тільки сам файл
$sql = "
    SELECT ".$FieldName." 
    FROM DOC_INVOICE di
    INNER JOIN ACCESS acc 
        ON di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT 
       AND di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
    WHERE di.ID = ? 
      AND acc.ID_USERS = ?
      AND di.ID_ORGANIZATIONS = ?
    LIMIT 1
";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "iii", $invoiceId, $userId, $orgId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $pdfContent);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($pdfContent) {
    // Очищаємо буфер
    if (ob_get_length()) ob_clean();
    
    // Формуємо назву
    $fileName = "Рахунок-Акт № {$invoiceId}.pdf";
    
    $encodedFileName = rawurlencode($fileName);
    
    // Заголовки для відкриття PDF
    header("Content-type: application/pdf");
    
    // Передача назви документа браузеру
    header("Content-Disposition: inline; filename=\"{$fileName}\"; filename*=UTF-8''{$encodedFileName}");
    
    echo $pdfContent;
    exit;
} else {
    echo "Документ не знайдено або у вас немає прав на його перегляд.";
}
?>