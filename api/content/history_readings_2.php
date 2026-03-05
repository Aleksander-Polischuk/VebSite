<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Збереження стану в сесію через AJAX
/*if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_state') {
    if (isset($_POST['c'])) $_SESSION['h2_contract'] = $_POST['c'];
    if (isset($_POST['a'])) $_SESSION['h2_address'] = $_POST['a'];
    exit('OK');
}*/

include "config.php";

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$userId = $_SESSION['id_users'] ?? 0;
$orgId = $IDOrganizations ?? 1; 
$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? 0;

if (!$selectedCounteragentId) {
    echo "<div style='padding:20px; color:red'>Error: No counteragent selected.</div>";
    exit;
}

// 1. --- ОТРИМАННЯ ДОСТУПНИХ РОКІВ ---
$years = [];
$sqlYears = "
    SELECT DISTINCT YEAR(PERIOD) as y
    FROM ENT_MUTUAL_SETTLEMENTS 
    WHERE ID_ORGANIZATIONS = ? AND 
          ID_REF_COUNTERAGENT = ? 
    ORDER BY y DESC";
$stmtY = mysqli_prepare($link, $sqlYears);
mysqli_stmt_bind_param($stmtY, "ii", $orgId, $selectedCounteragentId);
mysqli_stmt_execute($stmtY);
$resY = mysqli_stmt_get_result($stmtY);
while($rowY = mysqli_fetch_assoc($resY)) {
    if (!empty($rowY['y'])) $years[] = $rowY['y'];
}
if (empty($years)) $years[] = date('Y');

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $years[0];

// 2. --- ОСНОВНИЙ SQL ЗАПИТ ---
$sql = "
    SELECT DISTINCT
        rc.ID as ContractID,
        rc.NAME as ContractName,
        CONCAT(IFNULL(rci.NAME, ''), ', ', IFNULL(rs.NAME, ''), ', буд. ', IFNULL(rh.NAME, '')) AS Address,
        rcn.ID as CounterID,
        rcn.FIRM_NUM as CounterNum,
        rtc.NAME as CounterType,
        icr._DATE as ReadingDate,
        icr.CNT_CURRENT as ReadingValue,
        icr.CNT_LAST as PreviousValue,
        src.NAME as SourceName
    FROM INF_COUNTER_READINGS icr
    
    JOIN REF_ACCOUNT ra 
    ON (ra.ID = icr.ID_REF_ACCOUNT AND ra.ID_ORGANIZATIONS = icr.ID_ORGANIZATIONS)
    
    JOIN REF_COUNTER rcn 
    ON (rcn.ID = icr.ID_REF_COUNTER AND rcn.ID_ORGANIZATIONS = icr.ID_ORGANIZATIONS)
    
    LEFT JOIN REF_TYPE_COUNTER rtc 
    ON (rtc.id = rcn.ID_REF_TYPE_COUNTER)
    
    LEFT JOIN REF_HOUSE rh 
    ON (rh.id = ra.ID_REF_HOUSE)
    
    LEFT JOIN REF_STREET rs 
    ON (rs.id = rh.ID_REF_STREET)
    
    LEFT JOIN REF_CITY rci 
    ON (rci.id = rs.ID_REF_CITY)
    
    JOIN REF_CONTRACT rc 
    ON (rc.ID = ra.ID_REF_CONTRACT)
    
    LEFT JOIN REF_COUNTERREADINGSOURCE src 
    ON (src.ID = icr.ID_REF_COUNTERREADINGSOURCE AND src.ID_ORGANIZATIONS = icr.ID_ORGANIZATIONS)
    
    JOIN ACCESS acc 
    ON (acc.ID_REF_COUNTERAGENT = rc.ID_REF_COUNTERAGENT AND acc.ID_ORGANIZATIONS = icr.ID_ORGANIZATIONS)
    
    WHERE icr.ID_ORGANIZATIONS = ? 
      AND YEAR(icr._DATE) = ? 
      AND acc.ID_USERS = ? 
      AND acc.ID_REF_COUNTERAGENT = ?
    ORDER BY rc.NAME, Address, rcn.FIRM_NUM, icr._DATE DESC
";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "iiii", $orgId, $selectedYear, $userId, $selectedCounteragentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);


$treeData = [];
$addressMap = []; 

while ($row = mysqli_fetch_assoc($result)) {
    $cID = (string)$row['ContractID'];
    $cName = preg_replace('/\s+/', ' ', trim($row['ContractName']));
    if (!isset($treeData[$cID])) $treeData[$cID] = ['name' => $cName, 'addresses' => []];

    $addrRaw = trim($row['Address'], ", ");
    $addrName = ($addrRaw == "" || $addrRaw == ",") ? "Адреса не вказана" : $addrRaw;
    $addrKey = md5($addrName);
    
    if (!isset($treeData[$cID]['addresses'][$addrKey])) {
        $treeData[$cID]['addresses'][$addrKey] = ['name' => $addrName, 'meters' => []];
        $addressMap[$cID][$addrKey] = $addrName;
    }

    if (!empty($row['CounterID']) && !empty($row['ReadingDate'])) {
        $cntID = (string)$row['CounterID'];
        $cntName = ($row['CounterType'] ?? 'Лічильник') . ' №' . $row['CounterNum'];
        if (!isset($treeData[$cID]['addresses'][$addrKey]['meters'][$cntID])) {
            $treeData[$cID]['addresses'][$addrKey]['meters'][$cntID] = ['name' => $cntName, 'readings' => []];
        }
        
        $val = $row['ReadingValue'] - $row['PreviousValue'];
        $treeData[$cID]['addresses'][$addrKey]['meters'][$cntID]['readings'][] = [
            'date' => date('d.m.Y', strtotime($row['ReadingDate'])),
            'val' => number_format($val, 3, '.', ''),
            'curr' => number_format($row['ReadingValue'], 3, '.', ''),
            'prev' => number_format($row['PreviousValue'], 3, '.', ''),
            'source' => $row['SourceName'] ?? 'Не вказано'
        ];
    }
}

$savedContract = $_SESSION['h2_contract'] ?? '';
$savedAddress  = $_SESSION['h2_address'] ?? '';
?>

<link href="/css/history_readings_2.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header" style="flex-direction: column; align-items: stretch;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 20px;">
        <h3 style="margin: 0;">Історія показників</h3>
        <div class="header-controls">      
            <select id="yearSelect" class="year-select-custom" onchange="changeYear(this.value)">
                <?php foreach($years as $y): ?>
                    <option value="<?php echo $y; ?>" <?php echo ($y == $selectedYear) ? 'selected' : ''; ?>>
                        <?php echo $y; ?> рік
                    </option>
                <?php endforeach; ?>
            </select> 
        </div>
    </div>

    <?php if (!empty($treeData)): ?>
        <div class="filter-group">
            <label for="sel_contract">1. Договір:</label>
            <select id="sel_contract">
                <?php foreach ($treeData as $cID => $cData): ?>
                    <option value="<?php echo $cID; ?>"><?php echo htmlspecialchars($cData['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group" style="margin-bottom: 5px;">
            <label for="sel_address">2. Адреса:</label>
            <select id="sel_address" disabled></select>
        </div>
    <?php endif; ?>
</div>

<div class="history-container">
    <textarea id="address_map_data" style="display:none;"><?php echo json_encode($addressMap, JSON_UNESCAPED_UNICODE); ?></textarea>
    <input type="hidden" id="php_saved_contract" value="<?php echo htmlspecialchars($savedContract); ?>">
    <input type="hidden" id="php_saved_address" value="<?php echo htmlspecialchars($savedAddress); ?>">

    <?php if (empty($treeData)): ?>
        <p style="font-size: 18px; color: #666;">Дані за <?php echo $selectedYear; ?> рік відсутні.</p>
    <?php else: ?>
       

        <div class="table-container">
            <table class="data-table tree-table shadow-table" style="width: 100%; border-collapse: collapse;">
                <thead style="background:#f2f2f2">
                    <tr>
                        <th style="padding:12px; text-align:center">Лічильник</th>
                        <th style="padding:12px; text-align:center">Дата</th>
                        <th style="padding:12px; text-align:center">Попередні</th>
                        <th style="padding:12px; text-align:center">Поточні</th>
                        <th style="padding:12px; text-align:center">Різниця, куб.м</th>
                        <th style="padding:12px; text-align:center">Джерело</th>
                    </tr>
                </thead>
                <tbody id="history_tbody">
                    <?php 
                    foreach ($treeData as $cID => $cData) {
                        foreach ($cData['addresses'] as $aKey => $aData) {
                            if (!empty($aData['meters'])) {

                                $meterIndex = 0; // Рахуємо лічильники за цією адресою

                                foreach ($aData['meters'] as $mID => $mData) {
                                    $meterIndex++;

                                    if (!empty($mData['readings'])) {
                                        $readingIndex = 0; // Рахуємо рядки (показники) для поточного лічильника

                                        foreach ($mData['readings'] as $r) {
                                            $readingIndex++;

                                            // Якщо це перший рядок нового лічильника (і він не найперший у списку загалом)
                                            $topBorder = ($meterIndex > 1 && $readingIndex === 1) 
                                                ? 'border-top: 2px solid #3C9ADC;' // Малюємо синю лінію розділювача
                                                : '';
                                            ?>
                                            <tr class="child-row show history-data-row" data-contract="<?php echo $cID; ?>" data-address="<?php echo $aKey; ?>" style="display: none;">
                                                <td style="padding:10px; border-bottom:1px solid #ddd; <?php echo $topBorder; ?> text-align:center; font-weight:600; color:#333;">
                                                    <?php echo htmlspecialchars($mData['name']); ?>
                                                </td>
                                                <td style="padding:10px; border-bottom:1px solid #ddd; <?php echo $topBorder; ?> text-align:center">
                                                    <?php echo $r['date']; ?>
                                                </td>
                                                <td style="padding:10px; border-bottom:1px solid #ddd; <?php echo $topBorder; ?> text-align:center; color: #666;">
                                                    <?php echo $r['prev']; ?>
                                                </td>
                                                <td style="padding:10px; border-bottom:1px solid #ddd; <?php echo $topBorder; ?> text-align:center; color: #666;">
                                                    <?php echo $r['curr']; ?>
                                                </td>
                                                <td style="padding:10px; border-bottom:1px solid #ddd; <?php echo $topBorder; ?> text-align:center">
                                                    <strong><?php echo $r['val']; ?></strong>
                                                </td>
                                                <td style="padding:10px; border-bottom:1px solid #ddd; <?php echo $topBorder; ?> text-align:center; font-style: italic; color: #555;">
                                                    <?php echo htmlspecialchars($r['source']); ?>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                }
                            }
                        }
                    }
                    ?>
                    <tr id="history_no_data" style="display: none;">
                        <td colspan="6" style="padding:15px; text-align:center; color:#777;">Показників за цією адресою не знайдено</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
    <?php endif; ?>
</div>