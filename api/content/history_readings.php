<?php
// Перевірка сесії
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";
$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

// Вхідні параметри
$userId = $_SESSION['id_users'] ?? 0;
$orgId = $IDOrganizations ?? 1; 
$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;

if (!$selectedCounteragentId) {
    echo "<div style='padding:20px;'>Будь ласка, оберіть підприємство у списку зверху.</div>";
    exit;
}

// Допоміжна функція для назв місяців українською
function getUkrMonth($dateStr) {
    $months = [
        1 => 'Січень', 2 => 'Лютий', 3 => 'Березень', 4 => 'Квітень',
        5 => 'Травень', 6 => 'Червень', 7 => 'Липень', 8 => 'Серпень',
        9 => 'Вересень', 10 => 'Жовтень', 11 => 'Листопад', 12 => 'Грудень'
    ];
    $ts = strtotime($dateStr);
    $m = (int)date('n', $ts);
    $y = date('Y', $ts);
    return $months[$m] . ' ' . $y;
}

// SVG іконка
$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon" width="16" height="16" alt="" style="pointer-events: none;">';

// SQL ЗАПИТ (Без змін)
$sql = "
    SELECT 
        rc.ID as ContractID,
        rc.`NAME` as ContractName,
        
        CONCAT(
            IFNULL(rci.`NAME`, ''), ', ',
            IFNULL(rs.`NAME`, ''), ', буд. ',
            IFNULL(rh.`NAME`, '')
        ) AS Address,
        
        rcn.ID as CounterID,
        CONCAT(
            IFNULL(rtc.`NAME`, ''), ' №',
            IFNULL(rcn.FIRM_NUM, '')
        ) AS CounterName,
        
        
        icr.MTIME as ReadingDate,
        icr.CNT_CURRENT as ReadingValue,
        icr.CNT_LAST as PreviousValue,
        icr.ID_USERS as ReaderUser

    FROM ACCESS acc
    LEFT JOIN REF_COUNTERAGENT rct ON (rct.id = acc.ID_REF_COUNTERAGENT)
    LEFT JOIN REF_CONTRACT rc ON (rc.ID_REF_COUNTERAGENT = rct.id)
    LEFT JOIN REF_ACCOUNT ra ON (ra.ID_REF_CONTRACT = rc.id)
    LEFT JOIN REF_HOUSE rh ON (rh.id = ra.ID_REF_HOUSE)
    LEFT JOIN REF_STREET rs ON (rs.id = rh.ID_REF_STREET)
    LEFT JOIN REF_CITY rci ON (rci.id = rs.ID_REF_CITY)
    LEFT JOIN REF_COUNTER rcn ON (rcn.ID_REF_ACCOUNT = ra.id)
    LEFT JOIN REF_TYPE_COUNTER rtc ON (rtc.id = rcn.ID_REF_TYPE_COUNTER)
    LEFT JOIN REF_SERVICE rsr ON (rsr.id = rcn.ID_REF_SERVICE)
    
    LEFT JOIN INF_COUNTER_READINGS icr ON (icr.ID_REF_COUNTER = rcn.ID)

    WHERE acc.ID_USERS = ?
      AND acc.ID_ORGANIZATIONS = ?
      AND acc.ID_REF_COUNTERAGENT = ?
      AND rcn.ID IS NOT NULL

    ORDER BY rc.ID, Address, rcn.ID, icr.MTIME DESC
";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "iii", $userId, $orgId, $selectedCounteragentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// === ГРУПУВАННЯ ДАНИХ ===
$treeData = [];

while ($row = mysqli_fetch_assoc($result)) {
    $cID = $row['ContractID'];
    $addr = $row['Address'];
    $cntID = $row['CounterID'];

    // Рівень 1
    if (!isset($treeData[$cID])) {
        $treeData[$cID] = [
            'ContractName' => $row['ContractName'] ?? 'Без номера',
            'addresses' => []
        ];
    }
    // Рівень 2
    if (!isset($treeData[$cID]['addresses'][$addr])) {
        $treeData[$cID]['addresses'][$addr] = [
            'meters' => []
        ];
    }
    // Рівень 3
    if (!isset($treeData[$cID]['addresses'][$addr]['meters'][$cntID])) {
        $treeData[$cID]['addresses'][$addr]['meters'][$cntID] = [
            'CounterName' => $row['CounterName'],
            'readings' => []
        ];
    }
    // Рівень 4
    if (!empty($row['ReadingDate'])) {
        $treeData[$cID]['addresses'][$addr]['meters'][$cntID]['readings'][] = [
            'date' => $row['ReadingDate'],
            'val'  => $row['ReadingValue'],
            'prev' => $row['PreviousValue']
        ];
    }
}
?>

<div class="table-header-row sticky-header">
     <h3 style="margin: 0;">Історія показників</h3>
     <div class="header-controls">
       <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути все">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="Згорнути">
        </button>
        <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути все">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="Розгорнути">
        </button>
    </div>
</div>

<div class="table-container">
    <table class="data-table tree-table shadow-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #3C9ADC; color: white;">
                <th style="text-align: left; padding: 12px;">Об'єкт / Період</th>
                <th style="text-align: center; width: 150px;">Показник</th>
                <th style="text-align: center; width: 150px;">Дата фіксації</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($treeData)): ?>
                <tr><td colspan="3" style="padding: 20px; text-align: center;">Даних не знайдено.</td></tr>
            <?php else: 
                $c_idx = 0;
                foreach ($treeData as $contractData): 
                    $c_idx++;
                    $cGroupId = "hist_c_" . $c_idx;
            ?>
                <tr class="parent-row open" onclick="toggleTree(this, '<?php echo $cGroupId; ?>')">
                    <td>
                        <?php echo $caret_icon; ?>
                        <strong>Договір:</strong> <?php echo htmlspecialchars($contractData['ContractName']); ?>
                    </td>
                    <td></td>
                    <td></td>
                </tr>

                <?php 
                $a_idx = 0;
                foreach ($contractData['addresses'] as $addrName => $addrData): 
                    $a_idx++;
                    $aGroupId = $cGroupId . "_a_" . $a_idx;
                ?>
                    <tr class="child-row show <?php echo $cGroupId; ?> sub-parent open" onclick="toggleTree(this, '<?php echo $aGroupId; ?>')">
                        <td style="padding-left: 30px; background-color: #F2F5FF;">
                            <?php echo $caret_icon; ?>
                            <?php echo htmlspecialchars($addrName); ?>
                        </td>
                        <td style="background-color: #F2F5FF;"></td>
                        <td style="background-color: #F2F5FF;"></td>
                    </tr>

                    <?php 
                    $m_idx = 0;
                    foreach ($addrData['meters'] as $meterId => $meterData): 
                        $m_idx++;
                        $mGroupId = $aGroupId . "_m_" . $m_idx;
                        $hasReadings = !empty($meterData['readings']);

                        $meterClass = "child-row show " . $aGroupId . " open";
                    ?>
                        <tr class="<?php echo $meterClass; ?>" 
                            style="cursor: pointer; background-color: #fff;"
                            onclick="toggleTree(this, '<?php echo $mGroupId; ?>')">

                            <td style="padding-left: 60px; font-weight: bold; color: #444;">
                                <?php echo $caret_icon; ?>
                                
                                <?php echo htmlspecialchars($meterData['CounterName']); ?> 
                            </td>
                            <td></td>
                            <td></td>
                        </tr>

                        <?php if ($hasReadings): ?>
                            <?php foreach ($meterData['readings'] as $reading): ?>
                                <tr class="child-row show <?php echo $mGroupId; ?> detail-row">
                                    <td style="padding-left: 100px; color: #555;">
                                        <span style="color: #3C9ADC;">•</span> 
                                        <?php echo getUkrMonth($reading['date']); ?>
                                    </td>
                                    <td style="text-align: center; border-left: 1px solid #eee;">
                                        <?php echo number_format($reading['val'], 3, ',', ' '); ?>
                                    </td>
                                    <td style="text-align: center; color: #777; font-size: 0.9em; border-left: 1px solid #eee;">
                                        <?php echo date('d.m.Y H:i', strtotime($reading['date'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="child-row show <?php echo $mGroupId; ?> detail-row">
                                <td style="padding-left: 100px; color: #999; font-style: italic;">
                                    Історія відсутня
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php endif; ?>

                    <?php endforeach; // кінець лічильників ?>
                <?php endforeach; // кінець адрес ?>
            <?php endforeach; // кінець договорів ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>