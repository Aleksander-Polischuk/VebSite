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
    $checkSql = "SELECT ID "
              . "FROM ACCESS "
              . "WHERE ID_USERS = ? AND "
                    . "ID_ORGANIZATIONS = ? AND "
                    . "ID_REF_COUNTERAGENT = ? AND "
                    . "(DEL = 0 OR DEL IS NULL)";
    $checkStmt = mysqli_prepare($link, $checkSql);
    mysqli_stmt_bind_param($checkStmt, "iii", $userId, $orgId, $selectedCounteragentId);
    mysqli_stmt_execute($checkStmt);
    $checkRes = mysqli_stmt_get_result($checkStmt);
    
    while ($row = mysqli_fetch_assoc($checkRes)) {
        $enterprises[] = $row;
    }
    
    // Якщо масив порожній (доступу немає), очищаємо сесію
    if (empty($enterprises)) {
        $selectedCounteragentId = null;
        unset($_SESSION['selected_counteragent_id']);
    }
}

$isBlocked = false;
$meters_data = [];
$startAccepting = 1;
$endAccepting = 1;

// Якщо доступ є, перевіряємо період і завантажуємо дані
if (!empty($enterprises)) {
    // 1. Отримуємо налаштування організації: період прийому та ліміти об'ємів
    $orgQuery = "SELECT "
                . " CNT_READING_DAY_START, "
                . "CNT_READING_DAY_END, "
                . "ENT_MAX_VOL_BY_CNT, "
                . "ENT_MAX_VOL_BY_CNT_WARNING "
              . "FROM ORGANIZATIONS "
              . "WHERE ID = ?";
    $stmtOrg = mysqli_prepare($link, $orgQuery);
    mysqli_stmt_bind_param($stmtOrg, "i", $orgId);
    mysqli_stmt_execute($stmtOrg);
    $orgRes = mysqli_stmt_get_result($stmtOrg);
    $orgData = mysqli_fetch_assoc($orgRes);

    $startAccepting = (int)($orgData['CNT_READING_DAY_START'] ?? 1); 
    $endAccepting = (int)($orgData['CNT_READING_DAY_END'] ?? 1);
    $maxVolLimit = (float)($orgData['ENT_MAX_VOL_BY_CNT'] ?? 0); 
    $warningVolLimit = (float)($orgData['ENT_MAX_VOL_BY_CNT_WARNING'] ?? 0);

    $currentDay = (int)date('j');

    // 2. Універсальна перевірка періоду прийому
    $isInsidePeriod = false;
    if ($startAccepting <= $endAccepting) {
        if ($currentDay >= $startAccepting && $currentDay <= $endAccepting) {
            $isInsidePeriod = true;
        }
    } else {
        if ($currentDay >= $startAccepting || $currentDay <= $endAccepting) {
            $isInsidePeriod = true;
        }
    }

    //$isBlocked = !$isInsidePeriod;
     $isBlocked = false;    
    if (!$isBlocked) {
        /**
         * Функція отримання даних лічильників
         */
        function getMetersData($link, $counteragentId, $userId, $orgId) {
            $sql = "
                SELECT 
                    rc.`NAME` as ContractName,
                    CONCAT(IFNULL(rci.`NAME`, ''), ', ', IFNULL(rs.`NAME`, ''), ', буд. ', IFNULL(rh.`NAME`, '')) AS Address,
                    rcn.ID as CounterID,
                    rtc.`NAME` AS MeterMark,
                    rcn.FIRM_NUM AS MeterNum,
                    CONCAT(IFNULL(rtc.`NAME`, ''), ' №', IFNULL(rcn.FIRM_NUM, '')) AS CounterName,
                    iaac.LAST_INDICATION as LastVal,
                    (SELECT CNT_CURRENT 
                     FROM INF_NEW_COUNTER_READINGS incr 
                     WHERE incr.ID_REF_COUNTER = iaac.ID_REF_COUNTER 
                       AND incr.ID_ORGANIZATIONS = iaac.ID_ORGANIZATIONS 
                       AND incr.ID_USERS = ? 
                       AND MONTH(incr.MTIME) = MONTH(CURRENT_DATE())
                       AND YEAR(incr.MTIME) = YEAR(CURRENT_DATE())
                     ORDER BY incr.MTIME DESC LIMIT 1) AS CurrentVal,
                    iaac.ID_REF_ACCOUNT, iaac.ID_REF_COUNTER, iaac.ID_REF_SERVICE 
                
                FROM INF_ACTIVE_ACCOUNT_COUNTER iaac
                
                INNER JOIN REF_ACCOUNT ra 
                ON (ra.id = iaac.ID_REF_ACCOUNT)
                
                INNER JOIN REF_CONTRACT rc 
                ON (rc.id = ra.ID_REF_CONTRACT)
                
                INNER JOIN REF_COUNTER rcn 
                ON (rcn.id = iaac.ID_REF_COUNTER)
                
                INNER JOIN ACCESS acc 
                ON (acc.ID_REF_COUNTERAGENT = rc.ID_REF_COUNTERAGENT AND 
                    acc.ID_ORGANIZATIONS = iaac.ID_ORGANIZATIONS)
                    
                LEFT JOIN REF_HOUSE rh 
                ON (rh.id = ra.ID_REF_HOUSE)
                
                LEFT JOIN REF_STREET rs 
                ON (rs.id = rh.ID_REF_STREET)
                
                LEFT JOIN REF_CITY rci 
                ON (rci.id = rs.ID_REF_CITY)
                
                LEFT JOIN REF_TYPE_COUNTER rtc 
                ON (rtc.id = rcn.ID_REF_TYPE_COUNTER)
                
                WHERE acc.ID_USERS = ? AND 
                      iaac.ID_ORGANIZATIONS = ? AND 
                      acc.ID_REF_COUNTERAGENT = ?
                ORDER BY rc.ID, Address, CounterName";

            $stmt = mysqli_prepare($link, $sql);
            mysqli_stmt_bind_param($stmt, "iiii", $userId, $userId, $orgId, $counteragentId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $tree = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $tree[$row['ContractName'] ?: 'Без договору']['addresses'][$row['Address'] ?: 'Без адреси'][] = $row;
            }
            return $tree;
        }

        $meters_data = getMetersData($link, $selectedCounteragentId, $userId, $orgId);
    }
}

$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon icon-no-pointer" width="16" height="16" alt="">';
?>

<link href="../../css/input_meters.css" rel="stylesheet" type="text/css"/>

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
            <div class="blocking-notice-icon-wrapper">
                <img src="/img/exclamation-triangle-fill.svg" class="blocking-notice-icon" alt="Увага">
            </div>
            <h4 class="blocking-notice-title">У вас немає доступних підприємств</h4>
            <p class="blocking-notice-text">
                Наразі за вашим обліковим записом не закріплено жодного активного підприємства, або доступ було призупинено. Дякуємо за розуміння!
            </p>
        </div>

    <?php elseif ($isBlocked): ?>
        <div class="blocking-notice">
            <div class="blocking-notice-icon-wrapper">
                <img src="/img/exclamation-circle_red.svg" class="blocking-notice-icon" alt="Увага">
            </div>
            <h4 class="blocking-notice-title">Прийом показників призупинено</h4>
            <p class="blocking-notice-text">
                Згідно з графіком підприємства, прийом показників здійснюється у період з <b><?= $startAccepting ?>-го</b> по <b><?= $endAccepting ?>-те</b> число. <br>
                Наступний період прийому розпочнеться <b><?= $startAccepting ?>-го</b> числа. Дякуємо за розуміння!
            </p>
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
                        $c_idx++; $cId = "c_" . $c_idx; ?>
                        <tr class="parent-row open" onclick="toggleTree(this, '<?= $cId ?>')">
                            <td><?= $caret_icon ?> Договір <?= htmlspecialchars($contractName) ?></td>
                            <td></td><td></td>
                            <td><span id="sum_contract_<?= $c_idx ?>" class="font-bold">0,000</span></td>
                        </tr>
                        <?php foreach ($contractData['addresses'] as $addrName => $meters): 
                            $a_idx++; $aId = $cId . "_a_" . $a_idx; ?>
                            <tr class="child-row show <?= $cId ?> sub-parent open" onclick="toggleTree(this, '<?= $aId ?>')">
                                <td><?= $caret_icon ?> <?= htmlspecialchars($addrName) ?></td>
                                <td></td><td></td>
                                <td><span id="sum_address_<?= $a_idx ?>" class="sub-total-val contract-group-<?= $c_idx ?> font-bold text-muted">0,000</span></td>
                            </tr>
                            <?php foreach ($meters as $meter):
                                $inputId = "reading_" . $meter['CounterID'];
                                $diffId = "diff_" . $meter['CounterID'];
                                $rawPrevVal = number_format($meter['LastVal'], 3, '.', '');
                                $curVal = $meter['CurrentVal'];
                                $hasCurrent = ($curVal !== null); ?>
                                <tr class="child-row show <?= $aId ?> detail-row">
                                    <td><span class="bullet-icon">•</span> <?= htmlspecialchars($meter['CounterName']) ?></td>
                                    <td><?= number_format($meter['LastVal'], 3, ',', ' ') ?></td>
                                    <td>
                                        <div class="input-wrapper">
                                            <input type="text" inputmode="decimal" id="<?= $inputId ?>" 
                                                class="input-reading address-group-<?= $a_idx ?> <?= $hasCurrent ? 'warning' : '' ?>" 
                                                placeholder="0.000" autocomplete="off"
                                                value="<?= $hasCurrent ? number_format($curVal, 3, '.', '') : '' ?>" 
                                                data-prev="<?= $rawPrevVal ?>"

                                                data-contract-name="<?= htmlspecialchars($contractName) ?>"
                                                data-address-name="<?= htmlspecialchars($addrName) ?>"
                                                data-object-name="<?= htmlspecialchars($contractName) ?>" data-meter-mark="<?= htmlspecialchars($meter['MeterMark'] ?? '---') ?>"
                                                data-meter-num="<?= htmlspecialchars($meter['MeterNum'] ?? '---') ?>"
                                                data-counteragent="<?= htmlspecialchars($counteragentName) ?>"

                                                data-max-vol="<?= $maxVolLimit ?>"
                                                   data-warning-vol="<?= $warningVolLimit ?>"
                                                   data-account="<?= $meter['ID_REF_ACCOUNT'] ?>"
                                                   data-service="<?= $meter['ID_REF_SERVICE'] ?>"
                                                   data-counter="<?= $meter['ID_REF_COUNTER'] ?>"
                                                   data-contract-name="<?= htmlspecialchars($contractName) ?>"
                                                   data-address-name="<?= htmlspecialchars($addrName) ?>"
                                                   data-counter-name="<?= htmlspecialchars($meter['CounterName']) ?>"
                                                   onblur="formatOnBlur(this)"
                                                   oninput="handleMeterInput(this, <?= $rawPrevVal ?>, '<?= $diffId ?>', <?= $a_idx ?>, <?= $c_idx ?>)">
                                            
                                            <div class="error-icon" <?= $hasCurrent ? 'style="display:flex; opacity:1; visibility:visible; background-color:#f39c12;"' : '' ?>>
                                                <?= $hasCurrent ? 'i' : '!' ?>
                                                <span class="error-tooltip"><?= $hasCurrent ? 'Показник за поточний місяць вже передано' : 'Помилка' ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span id="<?= $diffId ?>" class="diff-val"><?= $hasCurrent ? number_format($curVal - $meter['LastVal'], 3, ',', ' ') : '0,000' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div style="position: absolute; left: -9999px; top: 0; width: 800px; z-index: -1;">
    <div id="act-template" style="padding: 40px; font-family: 'Arial', sans-serif; color: #000; background: #fff; width: 100%; box-sizing: border-box;">
        <h2 style="text-align: center; margin-bottom: 5px;">Акт приймання-передачі показників</h2>
        
        <p style="text-align: center; margin-top: 0; color: #555;">від <span id="tpl-date"></span></p>
        
        <div style="margin-top: 30px; margin-bottom: 20px;">
            <p><strong>Виконавець:</strong> ТОВ "Водоканал" (або ваша назва)</p>
            <p><strong>Споживач (ЄДРПОУ):</strong> <span id="tpl-edrpou"></span></p>
        </div>

        <p>Цей акт підтверджує, що споживач передав, а виконавець прийняв наступні показники лічильників:</p>

        <table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th>№ Лічильника</th>
                    <th>Попередній показник</th>
                    <th>Поточний показник</th>
                </tr>
            </thead>
            <tbody id="tpl-table-body">
                </tbody>
        </table>

        <div style="margin-top: 50px; display: flex; justify-content: space-between;">
            <div>
                <p><strong>Від Виконавця:</strong></p>
                <p>____________________</p>
            </div>
            <div>
                <p><strong>Від Споживача:</strong></p>
                <p>Підписано КЕП</p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($meters_data) && !empty($enterprises) && !$isBlocked): ?>
<div class="bottom-controls" style="display: flex; gap: 15px;">
    <!-- <button class="btn-save" onclick="saveReadings()">Зберегти показники</button> -->
    <button class="btn-save" onclick="confirmGenerateAct()">Сформувати акт передачі показників</button>
</div>
<?php endif; ?>

<div id="pdf-preview-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Попередній перегляд акту</h3>
            <button class="modal-close-btn" onclick="closePdfModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <iframe id="pdf-iframe" src=""></iframe>
        </div>
        
        <div class="modal-footer">
            <button class="btn-save btn-cancel-kep" onclick="closePdfModal()">Скасувати</button>
            <button class="btn-save btn-sign-kep" id="btn-sign-act">Підписати КЕП</button>
        </div>
    </div>
</div>
