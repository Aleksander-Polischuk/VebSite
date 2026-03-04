<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$userId = $_SESSION['id_users'] ?? 0;
$orgId = $IDOrganizations ?? 1; 
$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;

if (!$selectedCounteragentId) {
    echo "<div style='padding:20px;'>Будь ласка, оберіть підприємство у списку зверху.</div>";
    exit;
}

$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon" width="16" height="16" alt="" style="pointer-events: none;">';
$warning_text_msg = '<span style="color: #d32f2f; font-weight: normal; font-size: 12px; margin-left: 10px;">— Термін повірки спливає або минув!</span>';

$SQLExec = "
    SELECT 
        rc.ID as ContractID,
        rc.`NAME` as ContractName,
        CONCAT(IFNULL(rci.`NAME`, ''), ', ', IFNULL(rs.`NAME`, ''), ', буд. ', IFNULL(rh.`NAME`, '')) AS Address,
        rcn.ID as CounterID,
        rcn.FIRM_NUM as CounterNum,
        rtc.NAME as CounterType,
        rcn.DATE_NEXT_VERIFICATION,
        iaac.LAST_INDICATION,
        iaac.LAST_DATE_INDICATION
    FROM INF_ACTIVE_ACCOUNT_COUNTER iaac
    

    INNER JOIN REF_ACCOUNT ra 
    ON (ra.id = iaac.ID_REF_ACCOUNT)

    INNER JOIN REF_CONTRACT rc 
    ON (rc.id = ra.ID_REF_CONTRACT)
    
    INNER JOIN REF_COUNTER rcn 
    ON (rcn.id = iaac.ID_REF_COUNTER)
    
    INNER JOIN ACCESS acc ON (
        acc.ID_REF_COUNTERAGENT = rc.ID_REF_COUNTERAGENT AND 
        acc.ID_ORGANIZATIONS = iaac.ID_ORGANIZATIONS
    )
    
    LEFT JOIN REF_TYPE_COUNTER rtc 
    ON (rtc.id = rcn.ID_REF_TYPE_COUNTER)
    
    LEFT JOIN REF_HOUSE rh 
    ON (rh.id = ra.ID_REF_HOUSE)
    
    LEFT JOIN REF_STREET rs 
    ON (rs.id = rh.ID_REF_STREET)
    
    LEFT JOIN REF_CITY rci 
    ON (rci.id = rs.ID_REF_CITY)
    
    WHERE acc.ID_USERS = " . (int)$userId . " 
      AND iaac.ID_ORGANIZATIONS = " . (int)$orgId . "
      AND acc.ID_REF_COUNTERAGENT = " . (int)$selectedCounteragentId . "
    ORDER BY rc.ID, Address, CounterNum";

$s_res = mysqli_query($link, $SQLExec);

// --- ЛОГІКА ОБРОБКИ (3 РІВНІ) ---
$treeData = [];
$dateThreshold = date('Y-m-d', strtotime('+1 month'));

while ($row = mysqli_fetch_assoc($s_res)) {
    $cID = $row['ContractID'];
    $addr = $row['Address'] ?: 'Адреса не вказана';
    
    if (!isset($treeData[$cID])) {
        $treeData[$cID] = ['name' => $row['ContractName'] ?? 'Без номера', 'addresses' => []];
    }
    
    if (!isset($treeData[$cID]['addresses'][$addr])) {
        $treeData[$cID]['addresses'][$addr] = ['meters' => [], 'min_date' => null, 'has_warning' => false];
    }
    
    if (!empty($row['CounterID'])) {
        $currDate = $row['DATE_NEXT_VERIFICATION'];
        $isWarning = ($currDate && $currDate <= $dateThreshold);
        
        $treeData[$cID]['addresses'][$addr]['meters'][] = [
            'name' => ($row['CounterType'] ?? 'Лічильник') . ' №' . $row['CounterNum'],
            'date' => $currDate,
            'is_warning' => $isWarning,
            'last_val' => $row['LAST_INDICATION'],
            'last_date' => $row['LAST_DATE_INDICATION']
        ];

        if ($currDate) {
            if ($treeData[$cID]['addresses'][$addr]['min_date'] === null || $currDate < $treeData[$cID]['addresses'][$addr]['min_date']) {
                $treeData[$cID]['addresses'][$addr]['min_date'] = $currDate;
            }
            if ($isWarning) $treeData[$cID]['addresses'][$addr]['has_warning'] = true;
        }
    }
}
?>

<link href="../../css/ent_list_accounts.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header" id="history-start">
    <h3 style="margin: 0; flex-grow: 1;">Список адрес та лічильників</h3>  
    <div class="header-controls">
        <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути рівень">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="Згорнути" style="pointer-events: none;">
        </button>
        <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути рівень">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="Розгорнути" style="pointer-events: none;">
        </button>
    </div>
</div>

<div class="table-container" id="history-container">
    <table class="data-table tree-table shadow-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 12px; width: 40%;">Об'єкти</th>
                <th style="text-align: center; width: 20%;">Термін повірки</th>
                <th style="text-align: center; width: 20%;">Останній показник</th>
                <th style="text-align: center; width: 20%;">Дата передачі</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($treeData)): ?>
                <tr><td colspan="4" style="padding: 30px; text-align: center; color: #666;">Даних не знайдено.</td></tr>
            <?php else: 
                $c_idx = 0;
                foreach ($treeData as $cID => $contract): 
                    $c_idx++;
                    $cId = "c_" . $c_idx;
            ?>
                    <tr class="parent-row open" onclick="toggleTree(this, '<?php echo $cId; ?>')">
                        <td style="border-bottom: 1px solid #eee;">
                            <?php echo $caret_icon; ?> 
                            <strong>Договір:</strong> <?php echo htmlspecialchars($contract['name']); ?>
                        </td>
                        <td style="border-bottom: 1px solid #eee; border-left: 1px solid #eee;"></td>
                        <td style="border-bottom: 1px solid #eee; border-left: 1px solid #eee;"></td>
                        <td style="border-bottom: 1px solid #eee; border-left: 1px solid #eee;"></td>
                    </tr>

                    <?php 
                    $a_idx = 0;
                    foreach ($contract['addresses'] as $addressName => $addrData): 
                        $a_idx++;
                        $mId = $cId . "_m" . $a_idx;
                        $addrDate = ($addrData['min_date']) ? date('d.m.Y', strtotime($addrData['min_date'])) : '-';
                    ?>
                        <tr class="child-row <?php echo $cId; ?> sub-parent show" onclick="toggleTree(this, '<?php echo $mId; ?>')">
                            <td style="padding-left: 30px;">
                                <?php echo $caret_icon; ?>
                                <?php echo htmlspecialchars($addressName); ?>
                            </td>
                            <td align="center">
                                <?php if($addrData['has_warning']): ?>
                                    <span style="color: #d32f2f; font-weight: bold;"><?php echo $addrDate; ?></span>
                                <?php else: echo $addrDate; endif; ?>
                            </td>
                            <td align="center">---</td>
                            <td align="center">---</td>
                        </tr>

                        <?php foreach ($addrData['meters'] as $meter): 
                            $mDate = ($meter['date']) ? date('d.m.Y', strtotime($meter['date'])) : '-';
                            $lastVal = ($meter['last_val'] !== null) ? number_format($meter['last_val'], 3, '.', '') : '-';
                            $lastDate = ($meter['last_date']) ? date('d.m.Y', strtotime($meter['last_date'])) : '-';
                        ?>
                            <tr class="child-row <?php echo $mId; ?> detail-row">
                                <td style="padding-left: 65px; font-size: 13px; color: #666;">
                                    <span style="color: #4a76f2; margin-right: 8px;">•</span> 
                                    <?php echo htmlspecialchars($meter['name']); ?>
                                </td>
                                <td align="center">
                                    <?php if($meter['is_warning']): ?>
                                        <span style="color: #d32f2f;"><?php echo $mDate; ?></span><?php echo $warning_text_msg; ?>
                                    <?php else: echo $mDate; endif; ?>
                                </td>
                                <td align="center"><?php echo $lastVal; ?></td>
                                <td align="center"><?php echo $lastDate; ?></td>
                            </tr>
                        <?php endforeach; ?>

                    <?php endforeach; ?>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="../../js/CustomAlert.js" type="text/javascript"></script>