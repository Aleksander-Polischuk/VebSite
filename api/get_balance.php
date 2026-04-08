<?php
session_start();
include "../config.php";

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$orgId = (int)$IDOrganizations;
$selectedId = $_SESSION['selected_counteragent_id'] ?? 0;

$result = [
    'balance' => '0,00',
    'date' => date('d.m.Y')
];

if ($selectedId > 0) {
    $sql = "
        SELECT 
            SUM(ems.END_DEBT) AS summa, 
            org.DATE_UPDATE_ENT_MUTUAL_SETTLEMENTS  as  PERIOD
        FROM ENT_MUTUAL_SETTLEMENTS ems

        INNER JOIN ORGANIZATIONS org
        ON(org.id = ems.ID_ORGANIZATIONS)

        WHERE 
            ems.ID_ORGANIZATIONS = $orgId
            AND ems.ID_REF_COUNTERAGENT = $selectedId
        GROUP BY ems.PERIOD
        ORDER BY ems.PERIOD DESC
        LIMIT 1";
    
    $res = mysqli_query($link, $sql);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $result['balance'] = number_format($row['summa'] ?? 0, 2, ',', ' ');
        if ($row['PERIOD']) {
            $result['date'] = date('d.m.Y', strtotime($row['PERIOD']));
        }
    }
}

header('Content-Type: application/json');
echo json_encode($result);