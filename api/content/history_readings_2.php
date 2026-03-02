<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// SQL QUERY
$sql = "
    SELECT 
        rc.ID as ContractID,
        rc.NAME as ContractName,
        CONCAT(IFNULL(rci.NAME, ''), ', ', IFNULL(rs.NAME, ''), ', буд. ', IFNULL(rh.NAME, '')) AS Address,
        rcn.ID as CounterID,
        rcn.FIRM_NUM as CounterNum,
        rtc.NAME as CounterType,
        icr.MTIME as ReadingDate,
        icr.CNT_CURRENT as ReadingValue,
        icr.CNT_LAST as PreviousValue
    FROM ACCESS acc
    JOIN REF_CONTRACT rc ON (rc.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT)
    LEFT JOIN REF_ACCOUNT ra ON (ra.ID_REF_CONTRACT = rc.id)
    LEFT JOIN REF_HOUSE rh ON (rh.id = ra.ID_REF_HOUSE)
    LEFT JOIN REF_STREET rs ON (rs.id = rh.ID_REF_STREET)
    LEFT JOIN REF_CITY rci ON (rci.id = rs.ID_REF_CITY)
    LEFT JOIN REF_COUNTER rcn ON (rcn.ID_REF_ACCOUNT = ra.id)
    LEFT JOIN REF_TYPE_COUNTER rtc ON (rtc.id = rcn.ID_REF_TYPE_COUNTER)
    LEFT JOIN INF_COUNTER_READINGS icr ON (icr.ID_REF_COUNTER = rcn.ID)
    WHERE acc.ID_USERS = ? AND acc.ID_ORGANIZATIONS = ? AND acc.ID_REF_COUNTERAGENT = ?
    ORDER BY rc.NAME, Address, rcn.FIRM_NUM, icr.MTIME DESC
";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "iii", $userId, $orgId, $selectedCounteragentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// DATA PROCESSING
$treeData = [];
while ($row = mysqli_fetch_assoc($result)) {
    $cID = (string)$row['ContractID'];
    $cName = preg_replace('/\s+/', ' ', trim($row['ContractName']));
    
    if (!isset($treeData[$cID])) {
        $treeData[$cID] = ['id' => $cID, 'name' => $cName, 'addresses' => []];
    }

    $addrRaw = trim($row['Address'], ", ");
    $addrName = ($addrRaw == "" || $addrRaw == ",") ? "Address not specified" : $addrRaw;
    $addrKey = md5($addrName);

    if (!isset($treeData[$cID]['addresses'][$addrKey])) {
        $treeData[$cID]['addresses'][$addrKey] = ['name' => $addrName, 'meters' => []];
    }

    if (!empty($row['CounterID'])) {
        $cntID = (string)$row['CounterID'];
        $cntName = ($row['CounterType'] ?? 'Meter') . ' №' . $row['CounterNum'];
        
        if (!isset($treeData[$cID]['addresses'][$addrKey]['meters'][$cntID])) {
            $treeData[$cID]['addresses'][$addrKey]['meters'][$cntID] = ['id' => $cntID, 'name' => $cntName, 'readings' => []];
        }
        
        if (!empty($row['ReadingDate'])) {
            $ts = strtotime($row['ReadingDate']);
            if ($ts) {
                $val = $row['ReadingValue'] - $row['PreviousValue'];
                
                $treeData[$cID]['addresses'][$addrKey]['meters'][$cntID]['readings'][] = [
                    'date' => date('d.m.Y', $ts),
                    'val' => number_format($val, 3, '.', ''), //різниця
                    'curr' => number_format($row['ReadingValue'], 3, '.', ''), // Поточні
                    'prev' => number_format($row['PreviousValue'], 3, '.', '')  // Попередні
                ];
            }
        }
    }
}

function utf8ize($d) {
    if (is_array($d)) foreach ($d as $k => $v) $d[$k] = utf8ize($v);
    else if (is_string($d)) return mb_convert_encoding($d, 'UTF-8', 'UTF-8');
    return $d;
}

$jsonData = json_encode(utf8ize($treeData), JSON_UNESCAPED_UNICODE);


// --- ОТРИМАННЯ ДОСТУПНИХ РОКІВ З БД ---
$years = [];
$sqlYears = "
    SELECT DISTINCT YEAR(PERIOD) as y
    FROM ENT_MUTUAL_SETTLEMENTS
    WHERE ID_ORGANIZATIONS = ?
      AND ID_REF_COUNTERAGENT = ?
    ORDER BY y DESC
";
$stmtY = mysqli_prepare($link, $sqlYears);
mysqli_stmt_bind_param($stmtY, "ii", $orgId, $selectedCounteragentId);
mysqli_stmt_execute($stmtY);
$resY = mysqli_stmt_get_result($stmtY);

while($rowY = mysqli_fetch_assoc($resY)) {
    if (!empty($rowY['y'])) {
        $years[] = $rowY['y'];
    }
}

// Якщо даних про роки в базі немає, виводимо хоча б поточний рік
if (empty($years)) {
    $years[] = date('Y');
}

// Визначаємо обраний рік 
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $years[0];

?>

<div class="table-header-row sticky-header">
     <h3 style="margin: 0;">Історія показників</h3>
     <div class="header-controls">     
        <select id="yearSelect" class="year-select-custom" onchange="changeYear(this.value)" title="Оберіть рік">
            <?php foreach($years as $y): ?>
                <option value="<?php echo $y; ?>" <?php echo ($y == $selectedYear) ? 'selected' : ''; ?>>
                    <?php echo $y; ?> рік
                </option>
            <?php endforeach; ?>
        </select> 
    </div>
</div>

<div style="padding: 20px;">
    <textarea id="page_data_source" style="display:none;"><?php echo $jsonData; ?></textarea>

    <?php if (empty($treeData)): ?>
        <p>Дані відсутні.</p>
    <?php else: ?>
        <label>1. Договір:</label>
        <select id="sel_contract" class="form-control" style="width:100%; margin-bottom:15px; padding:5px;">
            <option value="">-- Оберіть договір --</option>
        </select>

        <label>2. Адреса:</label>
        <select id="sel_address" class="form-control" style="width:100%; margin-bottom:15px; padding:5px;" disabled></select>

        <label>3. Лічильник:</label>
        <select id="sel_meter" class="form-control" style="width:100%; margin-bottom:15px; padding:5px;" disabled></select>

        <div id="table_result" style="margin-top:20px;"></div>


        
    <?php endif; ?>
</div>