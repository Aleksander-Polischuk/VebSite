<?php
    $link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
    mysqli_set_charset($link, 'utf8');
?>

<link href="../../css/list_enterprise.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header" id="history-start">
     <h3 style="margin: 0;">Список підприємств</h3>    
</div>

<div class="table-container">
    <table class="data-table simple-list">
        <thead>
            <tr>
                <th style="text-align: center">Назва</th>
                <th>ЄДРПОУ</th>
            </tr>
        </thead>
        <tbody>
<?php        
    $SQLExec = "
        SELECT 
            RC.ID,
            RC.`NAME`,
            RC.EDRPOU
        FROM ACCESS AS A
        INNER JOIN REF_COUNTERAGENT AS RC ON (RC.ID = A.ID_REF_COUNTERAGENT AND RC.ID_ORGANIZATIONS = A.ID_ORGANIZATIONS)
        WHERE 
           A.ID_USERS = " . (int)($_SESSION['id_users'] ?? 0) . " AND 
           A.ID_ORGANIZATIONS = " . (int)($IDOrganizations ?? 0); 

    $s_res = mysqli_query($link, $SQLExec);
    
    $isFirst = true; 

    while ($s_row = mysqli_fetch_array($s_res)) {
        // Логіка автовибору (опціонально, якщо ви це ще не додали)
        if (empty($_SESSION['selected_counteragent_id']) && $isFirst) {
            $_SESSION['selected_counteragent_id'] = $s_row['ID'];
            $isFirst = false; 
        }
?>
            <tr>
                <td><?php echo $s_row['NAME']; ?></td>
                <td><?php echo $s_row['EDRPOU']; ?></td>
            </tr>   
<?php
    }
?>
        </tbody>
    </table>
</div>