<?php
require_once '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['id_users']) || !isset($_SESSION['selected_counteragent_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$selectedId = (int)$_SESSION['selected_counteragent_id'];
$orgId = (int)($IDOrganizations ?? 1);

$count = 0;

// Рахуємо АКТИ (статус = 1)
$sql = "SELECT COUNT(ID) as cnt FROM DOC_COUNTER_READINGS 
        WHERE ID_ORGANIZATIONS = $orgId 
          AND ID_REF_COUNTERAGENT = $selectedId 
          AND ID_ENUM_SIGN_STATUS = 1";
          
$res = mysqli_query($link, $sql);
if ($res && $row = mysqli_fetch_assoc($res)) {
    $count = (int)$row['cnt'];
}

$sql2 = "SELECT COUNT(ID) as cnt FROM DOC_INVOICE WHERE ID_ORGANIZATIONS = $orgId AND ID_REF_COUNTERAGENT = $selectedId AND ID_ENUM_SIGN_STATUS = 1";
$res2 = mysqli_query($link, $sql2);
if ($res2 && $row2 = mysqli_fetch_assoc($res2)) {
    $count += (int)$row2['cnt'];
}

echo json_encode(['count' => $count]);
mysqli_close($link);