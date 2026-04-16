<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php"; 

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
if (!$link) {
    echo "<div class='error-msg-padding text-negative'>Помилка з'єднання з базою даних.</div>";
    exit;
}
mysqli_set_charset($link, 'utf8');

$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? null;
$orgId = (int)($IDOrganizations ?? 1);
$userId = $_SESSION['id_users'] ?? 0;

$enterprises = [];

// Отримуємо назву підприємства-споживача (для шапки акту)
$counteragentName = "Назва організації не знайдена";
if ($selectedCounteragentId) {
    $q_ca = mysqli_query($link, "SELECT `NAME` FROM REF_COUNTERAGENT WHERE ID = $selectedCounteragentId");
    if ($row_ca = mysqli_fetch_assoc($q_ca)) {
        $counteragentName = $row_ca['NAME'];
    }
}

// Перевіряємо, чи є доступ до обраного підприємства (DEL = 0)
if ($selectedCounteragentId) {
    $checkSql = "SELECT ID FROM ACCESS WHERE ID_USERS = ? AND ID_ORGANIZATIONS = ? AND ID_REF_COUNTERAGENT = ? AND (DEL = 0 OR DEL IS NULL)";
    $checkStmt = mysqli_prepare($link, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "iii", $userId, $orgId, $selectedCounteragentId);
    mysqli_stmt_execute($checkStmt);
    $checkRes = mysqli_stmt_get_result($checkStmt);
    
    while ($row = mysqli_fetch_assoc($checkRes)) {
        $enterprises[] = $row;
    }
    
    if (empty($enterprises)) {
        $selectedCounteragentId = null;
        unset($_SESSION['selected_counteragent_id']);
    }
}

$isBlocked = false;
$meters_data = [];
$startAccepting = 1;
$endAccepting = 1;
$isInsidePeriod = false;
$allActsCreated = false;
$allActsSigned = false;
$periodTitle = "";
$hasUnsavedContracts = false; // Для відображення кнопки збереження

if (!empty($enterprises)) {
    // 1. Отримуємо налаштування підприємства
    $orgQuery = "SELECT 
                    CNT_READING_DAY_START, 
                    CNT_READING_DAY_END, 
                    ENT_MAX_VOL_BY_CNT, 
                    ENT_MAX_VOL_BY_CNT_WARNING 
                FROM ORGANIZATIONS WHERE ID = ?";
    $stmtOrg = mysqli_prepare($link, $orgQuery);
    mysqli_stmt_bind_param($stmtOrg, "i", $orgId);
    mysqli_stmt_execute($stmtOrg);
    $orgRes = mysqli_stmt_get_result($stmtOrg);
    $orgData = mysqli_fetch_assoc($orgRes);

    $startAccepting = (int)($orgData['CNT_READING_DAY_START'] ?? 1); 
    $endAccepting = (int)($orgData['CNT_READING_DAY_END'] ?? 1);
    $maxVolLimit = (float)($orgData['ENT_MAX_VOL_BY_CNT'] ?? 0); 
    $warningVolLimit = (float)($orgData['ENT_MAX_VOL_BY_CNT_WARNING'] ?? 0);

    // 2. Розрахунок періодів та розрахункового місяця
    $currentDay = (int)date('j');
    $cY = (int)date('Y');
    $cM = (int)date('n'); // Формат 1-12

    $periodStartStr = '';
    $periodEndStr = '';
    $billingMonth = $cM;
    $billingYear = $cY;

    if ($startAccepting <= $endAccepting) {
        // Звичайний період (наприклад, з 1 по 25)
        if ($currentDay >= $startAccepting && $currentDay <= $endAccepting) {
            $isInsidePeriod = true;
            $periodStartStr = sprintf("%04d-%02d-%02d 00:00:00", $cY, $cM, $startAccepting);
            $periodEndStr   = sprintf("%04d-%02d-%02d 23:59:59", $cY, $cM, $endAccepting);
        }
    } else {
        // Перехідний період (наприклад, з 9 по 2)
        if ($currentDay >= $startAccepting) {
            $isInsidePeriod = true;
            $periodStartStr = sprintf("%04d-%02d-%02d 00:00:00", $cY, $cM, $startAccepting);
            $nextMonthTime = mktime(0, 0, 0, $cM + 1, 1, $cY);
            $periodEndStr   = sprintf("%04d-%02d-%02d 23:59:59", date('Y', $nextMonthTime), date('m', $nextMonthTime), $endAccepting);
        } elseif ($currentDay <= $endAccepting) {
            $isInsidePeriod = true;
            $prevMonthTime = mktime(0, 0, 0, $cM - 1, 1, $cY);
            $periodStartStr = sprintf("%04d-%02d-%02d 00:00:00", date('Y', $prevMonthTime), date('m', $prevMonthTime), $startAccepting);
            $periodEndStr   = sprintf("%04d-%02d-%02d 23:59:59", $cY, $cM, $endAccepting);
            
            // Якщо ми у "хвості" періоду (напр. 2 травня), розрахунковий місяць - квітень
            $billingMonth = (int)date('n', $prevMonthTime);
            $billingYear = (int)date('Y', $prevMonthTime);
        }
    }

    // 3. Формуємо назву місяця
    $monthsUA = [
        1 => 'січень', 2 => 'лютий', 3 => 'березень', 4 => 'квітень', 
        5 => 'травень', 6 => 'червень', 7 => 'липень', 8 => 'серпень', 
        9 => 'вересень', 10 => 'жовтень', 11 => 'листопад', 12 => 'грудень'
    ];
    $periodTitle = $monthsUA[$billingMonth] . " " . $billingYear . " р.";

    // Функція для отримання лічильників
    function getMetersData($link, $counteragentId, $userId, $orgId) {
        $sql = "SELECT rc.ID as ContractID, rc.`NAME` as ContractName, CONCAT(IFNULL(rci.`NAME`, ''), ', ', IFNULL(rs.`NAME`, ''), ', буд. ', IFNULL(rh.`NAME`, '')) AS Address, rcn.ID as CounterID, rtc.`NAME` AS MeterMark, rcn.FIRM_NUM AS MeterNum, CONCAT(IFNULL(rtc.`NAME`, ''), ' №', IFNULL(rcn.FIRM_NUM, '')) AS CounterName, iaac.LAST_INDICATION as LastVal, (SELECT CNT_CURRENT FROM INF_NEW_COUNTER_READINGS incr WHERE incr.ID_REF_COUNTER = iaac.ID_REF_COUNTER AND incr.ID_ORGANIZATIONS = iaac.ID_ORGANIZATIONS AND incr.ID_USERS = ? AND MONTH(incr.MTIME) = MONTH(CURRENT_DATE()) AND YEAR(incr.MTIME) = YEAR(CURRENT_DATE()) ORDER BY incr.MTIME DESC LIMIT 1) AS CurrentVal, iaac.ID_REF_ACCOUNT, iaac.ID_REF_COUNTER, iaac.ID_REF_SERVICE FROM INF_ACTIVE_ACCOUNT_COUNTER iaac INNER JOIN REF_ACCOUNT ra ON (ra.id = iaac.ID_REF_ACCOUNT) INNER JOIN REF_CONTRACT rc ON (rc.id = ra.ID_REF_CONTRACT) INNER JOIN REF_COUNTER rcn ON (rcn.id = iaac.ID_REF_COUNTER) INNER JOIN ACCESS acc ON (acc.ID_REF_COUNTERAGENT = rc.ID_REF_COUNTERAGENT AND acc.ID_ORGANIZATIONS = iaac.ID_ORGANIZATIONS) LEFT JOIN REF_HOUSE rh ON (rh.id = ra.ID_REF_HOUSE) LEFT JOIN REF_STREET rs ON (rs.id = rh.ID_REF_STREET) LEFT JOIN REF_CITY rci ON (rci.id = rs.ID_REF_CITY) LEFT JOIN REF_TYPE_COUNTER rtc ON (rtc.id = rcn.ID_REF_TYPE_COUNTER) WHERE acc.ID_USERS = ? AND iaac.ID_ORGANIZATIONS = ? AND acc.ID_REF_COUNTERAGENT = ? ORDER BY rc.ID, Address, CounterName";
        $stmt = mysqli_prepare($link, $sql);
        mysqli_stmt_bind_param($stmt, "iiii", $userId, $userId, $orgId, $counteragentId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $tree = [];
        while ($row = mysqli_fetch_assoc($result)) { $tree[$row['ContractName'] ?: 'Без договору']['addresses'][$row['Address'] ?: 'Без адреси'][] = $row; }
        return $tree;
    }

    $contractStatuses = []; 
    $totalContracts = 0;

    // 4. Якщо період відкритий - дістаємо дані та перевіряємо акти
    if ($isInsidePeriod) {
        $meters_data = getMetersData($link, $selectedCounteragentId, $userId, $orgId);
        $totalContracts = count($meters_data);

        if ($selectedCounteragentId) {
            // Дістаємо статуси ВСІХ актів по договорах за цей період
            $checkActSql = "SELECT ID_REF_CONTRACT, ID_ENUM_SIGN_STATUS FROM DOC_COUNTER_READINGS WHERE ID_ORGANIZATIONS = ? AND ID_REF_COUNTERAGENT = ? AND MTIME >= ? AND MTIME <= ?";
            $stmtCheck = mysqli_prepare($link, $checkActSql);
            mysqli_stmt_bind_param($stmtCheck, "iiss", $orgId, $selectedCounteragentId, $periodStartStr, $periodEndStr);
            mysqli_stmt_execute($stmtCheck);
            $resCheck = mysqli_stmt_get_result($stmtCheck);
            
            while ($actRow = mysqli_fetch_assoc($resCheck)) {
                $cid = $actRow['ID_REF_CONTRACT'];
                $status = $actRow['ID_ENUM_SIGN_STATUS'];
                $contractStatuses[$cid] = [
                    'is_signed' => ($status == 2)
                ];
            }
            mysqli_stmt_close($stmtCheck);
        }

        // Перевіряємо, чи всі договори закриті актами
        $signedContracts = 0;
        foreach($contractStatuses as $cs) {
            if($cs['is_signed']) $signedContracts++;
        }
        
        $allActsCreated = ($totalContracts > 0 && count($contractStatuses) >= $totalContracts);
        $allActsSigned = ($allActsCreated && $signedContracts >= $totalContracts);
    }

    $isBlocked = !$isInsidePeriod || $allActsCreated;

    // Перевіряємо, чи є хоча б один договір без акту (щоб показати кнопку "Сформувати")
    if (!$isBlocked) {
        foreach ($meters_data as $contractName => $contractData) {
            $firstAddr = reset($contractData['addresses']);
            $firstMeter = reset($firstAddr);
            $contractId = $firstMeter['ContractID'] ?? 0;
            if (!isset($contractStatuses[$contractId])) {
                $hasUnsavedContracts = true;
                break;
            }
        }
    }
}

$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon icon-no-pointer" width="16" height="16" alt="">';
?>

<link href="/css/input_meters.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/css/input_meters.css'); ?>" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header" id="history-start">
    <h3>Передача показників</h3>
    <?php if (!empty($enterprises) && !$isBlocked): ?>
    <div class="header-controls">
        <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути рівень">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="Згорнути">
        </button>
        <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути рівень">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="Розгорнути">
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="table-container">
    <?php if (empty($enterprises)): ?>
        <div class="blocking-notice">
            <div class="notice-icon-box">
                <img src="/img/exclamation-triangle-fill.svg" class="notice-icon" alt="Увага">
            </div>
            <h4 class="notice-title">У вас немає доступних підприємств</h4>
            <p class="notice-text">Наразі за вашим обліковим записом не закріплено жодного активного підприємства.</p>
        </div>

    <?php elseif (!$isInsidePeriod): ?>
        <div class="blocking-notice period-closed">
            <div class="notice-icon-box">
                <img src="/img/exclamation-circle_red.svg" class="notice-icon" alt="Увага">
            </div>
            <h4 class="notice-title">Прийом показників призупинено</h4>
            <p class="notice-text">
                Згідно з графіком підприємства, прийом показників здійснюється у період з <b><?= $startAccepting ?>-го</b> по <b><?= $endAccepting ?>-те</b> число.
            </p>
        </div>

    <?php elseif ($allActsCreated && $allActsSigned): ?>
        <div class="blocking-notice act-success">
            <div class="notice-icon-box">
                <img src="/img/check-square-fill.svg" class="notice-icon icon-success" alt="Успіх">
            </div>
            <div class="notice-text">
                <p style="text-align: center; margin-top: 10px;">
                    Ви успішно сформували та підписали акти передачі показників <b>за <?= $periodTitle ?></b>. <br>
                    Переглянути їх можна у розділі <a href="Документи" style="color: #0056b3;">«Документи»</a>.
                </p>
            </div>
        </div>

    <?php elseif ($allActsCreated): ?>
        <div class="blocking-notice act-exists">
            <div class="notice-icon-box">
                <img src="/img/check-square-fill.svg" class="notice-icon icon-success" alt="Успіх">
            </div>
            <div class="notice-text">
                <p style="text-align: center; margin-bottom: 20px;">
                    Ви вже успішно сформували акти передачі показників <b>за <?= $periodTitle ?></b>. Переглянути їх можна у розділі <a href="Документи" style="color: #0056b3;"><b>«Документи»</b></a>.
                </p>
                <div style="text-align: left;">
                    <p><b>Проблеми, які у Вас могли виникнути:</b></p>
                    <ol style="line-height: 1.6; margin-left: 20px;">
                        <li style="margin-bottom: 1.5em;">
                            <b>Сформували акт з показниками, але забули його підписати:</b><br>
                            Перейдіть на вкладку <a href="Документи" style="color: #0056b3; text-decoration: underline;">Документи</a> та знайдіть акти за необхідний період. У випадку, якщо їх там немає, зверніться на гарячу лінію КП «ЧернівціВодоканал» для вирішення проблеми. 
                        </li>
                        <li style="margin-bottom: 1.5em;">
                            <b>Сформували акт з невірними показниками, але ще НЕ підписали:</b><br>
                            Зайдіть на сторінку <a href="Документи" style="color: #0056b3; text-decoration: underline;">Документи</a> та видаліть необхідний акт.
                        </li>
                    </ol>
                </div>
            </div>
        </div>

    <?php else: ?>
        <table class="data-table tree-table shadow-table fixed-layout">
            <thead>
                <tr>
                    <th>Період / Об'єкт</th>
                    <th>Попередні</th>
                    <th>Поточні</th>
                    <th>Різниця</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($meters_data)): ?>
                    <tr><td colspan="4" class="cell-no-data">Немає активних лічильників.</td></tr>
                <?php else: 
                    $c_idx = 0; $a_idx = 0;
                    foreach ($meters_data as $contractName => $contractData): 
                        $c_idx++; $cId = "c_" . $c_idx; 
                        
                        // Визначаємо статус конкретно цього договору
                        $firstAddr = reset($contractData['addresses']);
                        $firstMeter = reset($firstAddr);
                        $contractId = $firstMeter['ContractID'] ?? 0;
                        
                        $contractHasAct = isset($contractStatuses[$contractId]);
                        $contractIsSigned = $contractHasAct && $contractStatuses[$contractId]['is_signed'];
                ?>
                        <tr class="parent-row open" onclick="toggleTree(this, '<?= $cId ?>')">
                            <td><?= $caret_icon ?> Договір <?= htmlspecialchars($contractName) ?></td>
                            <td></td><td></td>
                            <td><span id="sum_contract_<?= $c_idx ?>" class="font-bold">0,000</span></td>
                        </tr>

                        <?php if ($contractHasAct): ?>
                            <tr class="child-row show <?= $cId ?>">
                                <td colspan="4" style="text-align: center; padding: 20px; background: #f8f9fa;">
                                    <?php if ($contractIsSigned): ?>
                                        <span style="color: #28a745;">✔ Акт по цьому договору сформовано та підписано.</span>
                                    <?php else: ?>
                                        <span style="color: #d39e00;">⚠️ Акт сформовано. Перейдіть у розділ <a href="Документи" style="font-weight: bold; text-decoration: underline;">«Документи»</a> для підпису.</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contractData['addresses'] as $addrName => $meters): 
                                $a_idx++; $aId = $cId . "_a_" . $a_idx; ?>
                                <tr class="child-row show <?= $cId ?> sub-parent open" onclick="toggleTree(this, '<?= $aId ?>')">
                                    <td><?= $caret_icon ?> <?= htmlspecialchars($addrName) ?></td>
                                    <td></td><td></td>
                                    <td><span id="sum_address_<?= $a_idx ?>" class="sub-total-val contract-group-<?= $c_idx ?> font-bold text-muted">0,000</span></td>
                                </tr>
                                <?php foreach ($meters as $meter):
                                    $inputId = "reading_" . $meter['CounterID']; $diffId = "diff_" . $meter['CounterID'];
                                    $rawPrevVal = number_format($meter['LastVal'], 3, '.', ''); $curVal = $meter['CurrentVal']; $hasCurrent = ($curVal !== null); ?>
                                    <tr class="child-row show <?= $aId ?> detail-row">
                                        <td><span class="bullet-icon">•</span> <?= htmlspecialchars($meter['CounterName']) ?></td>
                                        <td><?= number_format($meter['LastVal'], 3, ',', ' ') ?></td>
                                        <td>
                                            <div class="input-wrapper">
                                                <input type="text" inputmode="decimal" id="<?= $inputId ?>" 
                                                    class="input-reading address-group-<?= $a_idx ?> <?= $hasCurrent ? 'warning' : '' ?>" 
                                                    value="<?= $hasCurrent ? number_format($curVal, 3, '.', '') : '' ?>" 
                                                    placeholder="0,000" 
                                                    data-prev="<?= $rawPrevVal ?>" 
                                                    data-contract-id="<?= htmlspecialchars($meter['ContractID'] ?? 0) ?>" 
                                                    data-contract-name="<?= htmlspecialchars($contractName) ?>" 
                                                    data-address-name="<?= htmlspecialchars($addrName) ?>" 
                                                    data-object-name="<?= htmlspecialchars($contractName) ?>" 
                                                    data-meter-mark="<?= htmlspecialchars($meter['MeterMark'] ?? '---') ?>" 
                                                    data-meter-num="<?= htmlspecialchars($meter['MeterNum'] ?? '---') ?>" 
                                                    data-counteragent="<?= htmlspecialchars($counteragentName) ?>" 
                                                    data-max-vol="<?= $maxVolLimit ?>" 
                                                    data-warning-vol="<?= $warningVolLimit ?>" 
                                                    data-account="<?= $meter['ID_REF_ACCOUNT'] ?>" 
                                                    data-service="<?= $meter['ID_REF_SERVICE'] ?>" 
                                                    data-counter="<?= $meter['ID_REF_COUNTER'] ?>" 
                                                    data-counter-name="<?= htmlspecialchars($meter['CounterName']) ?>" 
                                                    onblur="formatOnBlur(this)" 
                                                    oninput="handleMeterInput(this, <?= $rawPrevVal ?>, '<?= $diffId ?>', <?= $a_idx ?>, <?= $c_idx ?>)
                                                ">
                                                <div class="error-icon" <?= $hasCurrent ? 'style="display:flex; opacity:1; visibility:visible; background-color:#f39c12;"' : '' ?>>
                                                    <?= $hasCurrent ? 'i' : '!' ?>
                                                    <span class="error-tooltip"><?= $hasCurrent ? 'Показник вже передано' : 'Помилка' ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span id="<?= $diffId ?>" class="diff-val"><?= $hasCurrent ? number_format($curVal - $meter['LastVal'], 3, ',', ' ') : '0,000' ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if (!empty($meters_data) && !empty($enterprises) && $hasUnsavedContracts): ?>
<div class="bottom-controls">
    <button class="btn-save" onclick="confirmGenerateAct()">Сформувати акт передачі показників</button>
</div>
<?php endif; ?>