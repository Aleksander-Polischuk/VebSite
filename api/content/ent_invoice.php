<?php

$forceSign = isset($_GET['force_sign']) && $_GET['force_sign'] == '1';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config.php";

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$userId = $_SESSION['id_users'] ?? 0;
$orgId = $IDOrganizations ?? 1; 
$selectedCounteragentId = $_SESSION['selected_counteragent_id'] ?? 0;

$enterprises = [];

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
    
    // Якщо доступу немає, очищаємо сесію
    if (empty($enterprises)) {
        $selectedCounteragentId = null;
        unset($_SESSION['selected_counteragent_id']);
    }
}

function getUkrMonth($dateStr) {
    if (!$dateStr) return '';
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

$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon icon-no-pointer" alt="">';

$years = [];
$selectedYear = date('Y');
$treeData = [];

// Якщо доступ є, завантажуємо роки і дані рахунків
if (!empty($enterprises)) {
    // ОТРИМАННЯ СПИСКУ ДОСТУПНИХ РОКІВ для рахунків та актів
    $sqlYears = "
        SELECT DISTINCT YEAR(PERIOD) as y FROM (
            SELECT di.PERIOD 
            FROM ACCESS acc
            INNER JOIN DOC_INVOICE di ON di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT AND di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
            WHERE acc.ID_USERS = ? AND acc.ID_ORGANIZATIONS = ? AND acc.ID_REF_COUNTERAGENT = ?
            
            UNION
            
            SELECT da.MTIME as PERIOD 
            FROM ACCESS acc
            INNER JOIN DOC_COUNTER_READINGS da ON da.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT AND da.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
            WHERE acc.ID_USERS = ? AND acc.ID_ORGANIZATIONS = ? AND acc.ID_REF_COUNTERAGENT = ?
        ) as combined_years
        ORDER BY y DESC
    ";

    $stmtY = mysqli_prepare($link, $sqlYears);
    mysqli_stmt_bind_param($stmtY, "iiiiii", $userId, $orgId, $selectedCounteragentId, $userId, $orgId, $selectedCounteragentId);
    mysqli_stmt_execute($stmtY);
    $resY = mysqli_stmt_get_result($stmtY);

    while($rowY = mysqli_fetch_assoc($resY)) {
        if (!empty($rowY['y'])) {
            $years[] = $rowY['y'];
        }
    }

    if (empty($years)) {
        $years[] = date('Y');
    }

    $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $years[0];

    // ОТРИМАННЯ РАХУНКІВ ТА АКТІВ (З LEFT JOIN ДЛЯ ДОГОВОРІВ)
    $sql = "
        SELECT 
            rc.ID as ContractID,
            rc.`NAME` as ContractName,
            di.PERIOD,
            di.ID as InvoiceID,
            di.DOC_SUM_TAX,
            (di.DOC_PDF IS NOT NULL) AS has_pdf,
            (di.DOC_PDF_SIGN_ORG IS NOT NULL) AS sign_org,
            (di.DOC_PDF_SIGN_COUNTERAGENT IS NOT NULL) AS sign_ca,
            'invoice' as DocType
        FROM ACCESS acc
        
        INNER JOIN DOC_INVOICE di 
        ON di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS AND 
           di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT 
           
        LEFT JOIN REF_CONTRACT rc 
        ON rc.ID = di.ID_REF_CONTRACT AND 
           rc.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
        
        WHERE acc.ID_USERS = ? AND 
              acc.ID_ORGANIZATIONS = ? AND 
              acc.ID_REF_COUNTERAGENT = ? AND 
              YEAR(di.PERIOD) = ?
        
        UNION ALL
        
        SELECT 
            rc.ID as ContractID,
            rc.`NAME` as ContractName,
            da.MTIME as PERIOD,
            da.ID as InvoiceID,
            0 as DOC_SUM_TAX,
            (da.DOC_PDF IS NOT NULL) AS has_pdf,
            1 AS sign_org, 
            (da.DOC_PDF_SIGN_COUNTERAGENT IS NOT NULL) AS sign_ca,
            'act' as DocType
        FROM ACCESS acc
        
        INNER JOIN DOC_COUNTER_READINGS da 
        ON da.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS AND 
           da.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT 
           
        LEFT JOIN REF_CONTRACT rc 
        ON rc.ID = da.ID_REF_CONTRACT AND 
           rc.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
           
        WHERE acc.ID_USERS = ? AND 
              acc.ID_ORGANIZATIONS = ? AND 
              acc.ID_REF_COUNTERAGENT = ? AND 
              YEAR(da.MTIME) = ?
        
        ORDER BY PERIOD DESC
    ";

    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "iiiiiiii", 
        $userId, $orgId, $selectedCounteragentId, $selectedYear,
        $userId, $orgId, $selectedCounteragentId, $selectedYear
    );
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    //ГРУПУВАННЯ ДАНИХ
    while ($row = mysqli_fetch_assoc($result)) {
        // Якщо договір не знайдено, встановлюємо 0
        $cID = $row['ContractID'] ? $row['ContractID'] : 0;
        $cName = $row['ContractName'] ? $row['ContractName'] : 'Без договору';

        if (!isset($treeData[$cID])) {
            $treeData[$cID] = [
                'ContractName' => $cName,
                'invoices' => []
            ];
        }
        
        $treeData[$cID]['invoices'][] = [
            'period'   => $row['PERIOD'],
            'number'   => $row['InvoiceID'],
            'sum_tax'  => $row['DOC_SUM_TAX'],
            'has_pdf'  => $row['has_pdf'],
            'sign_org' => $row['sign_org'],
            'sign_ca'  => $row['sign_ca'],
            'type'     => $row['DocType'] // invoice або act
        ];
    }
}
?>

<link href="../../css/ent_invoice.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header bills-header">
    <h3>Документи</h3>
    <?php if (!empty($enterprises)): ?>
    <div class="header-controls">
        <button type="button" class="btn-tree-custom" onclick="stepTree(-1)" title="Згорнути все">
            <img src="/img/arrow-up.svg" width="16" height="16" alt="Згорнути" class="icon-no-pointer">
        </button>
        <button type="button" class="btn-tree-custom" onclick="stepTree(1)" title="Розгорнути все">
            <img src="/img/arrow-down.svg" width="16" height="16" alt="Розгорнути" class="icon-no-pointer">
        </button>
        <select class="year-select-custom bills-year-select" onchange="changeYear(this.value)" title="Оберіть рік">
            <?php foreach($years as $y): ?>
                <option value="<?php echo $y; ?>" <?php echo ($y == $selectedYear) ? 'selected' : ''; ?>>
                    <?php echo $y; ?> рік
                </option>
            <?php endforeach; ?>
        </select>
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
    <?php else: ?>
        <table class="data-table tree-table shadow-table bills-table">
            <thead>
                <tr>
                    <th>Договір / Період</th>
                    <th>Номер</th>
                    <th>Сума з ПДВ</th>
                    <th></th> 
                    <th></th> 
                    <th>Статус</th> 
                </tr>
            </thead>
            <tbody>
                <?php if (empty($treeData)): ?>
                    <tr>
                        <td colspan="6" class="cell-no-data">
                            За <?php echo $selectedYear; ?> рік даних не знайдено.
                        </td>
                    </tr>
                <?php else: 
                    $c_idx = 0;
                    foreach ($treeData as $contractData): 
                        $c_idx++;
                        $cGroupId = "bill_c_" . $c_idx;
                ?>
                    <tr class="parent-row open" onclick="toggleTree(this, '<?php echo $cGroupId; ?>')">
                        <td>
                            <?php echo $caret_icon; ?>
                            <strong>Договір:</strong> <?php echo htmlspecialchars($contractData['ContractName']); ?>
                        </td>
                        <td></td><td></td><td></td><td></td><td></td>
                    </tr>

                    <?php 
                    if (!empty($contractData['invoices'])): 
                        foreach ($contractData['invoices'] as $invoice): 
                            $isAct = ($invoice['type'] === 'act');
                            $docLabel = $isAct ? 'Акт' : 'Рахунок';
                            $viewUrl = $isAct ? '/api/get_act_pdf.php' : '/api/get_ent_invoice.php';
                            $windowName = ($isAct ? "act_view_" : "invoice_view_") . $invoice['number'];

                            // Статуси
                            $statusHtml = '<span class="status-badge status-unsigned">Не підписано</span>';
                            if ($invoice['has_pdf'] && $invoice['sign_org'] && $invoice['sign_ca']) {
                                $statusHtml = '<span class="status-badge status-signed-ca">Підписано</span>';
                            } elseif ($invoice['sign_org']) {
                                $statusHtml = '<span class="status-badge status-signed-org">Готовий до підпису</span>';
                            }

                            $isFullySigned = ($invoice['has_pdf'] && $invoice['sign_org'] && $invoice['sign_ca']);
                            $isButtonDisabled = $isFullySigned && !$forceSign;
                        ?>
                        <tr class="child-row show <?php echo $cGroupId; ?> detail-row">
                            <td>
                                <span class="bullet-icon">•</span> 
                                <?php echo $docLabel . ': ' . getUkrMonth($invoice['period']); ?>
                            </td>
                            <td class="text-dark"><?php echo htmlspecialchars($invoice['number']); ?></td>
                            <td class="text-bold">
                                <?php echo ($invoice['sum_tax'] > 0) ? number_format($invoice['sum_tax'], 2, ',', ' ') . ' грн' : '---'; ?>
                            </td>

                            <td>
                                <a href="<?php echo $viewUrl; ?>?id=<?php echo htmlspecialchars($invoice['number']); ?>" 
                                   target="<?php echo $windowName; ?>" 
                                   class="btn-action btn-view">Переглянути</a>
                            </td>

                            <td>
                                <button type="button" 
                                    class="btn-action btn-sign" 
                                    onclick="<?php echo $isButtonDisabled ? 'return false;' : "window.open('/api/content/SigningDocs.php?id=" . htmlspecialchars($invoice['number']) . "&doctype=" . $invoice['type'] . "', 'sign_window_" . htmlspecialchars($invoice['number']) . "')"; ?>"
                                    <?php echo $isButtonDisabled ? 'disabled' : ''; ?>>
                                    Підписати
                                </button>
                            </td>

                            <td><?php echo $statusHtml; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script src="../../js/bills.js" type="text/javascript"></script>