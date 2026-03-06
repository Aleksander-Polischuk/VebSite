<?php
// api/content/bills.php

$forceSign = isset($_GET['force_sign']) && $_GET['force_sign'] == '1';

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
    echo "<div class='error-msg-padding text-negative'>Будь ласка, оберіть підприємство у списку зверху.</div>";
    exit;
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

// SVG іконка для дерева
$caret_icon = '<img src="/img/caret-down-fill.svg" class="tree-icon icon-no-pointer" alt="">';

// -------------------------------------------------------------------------
// 1. ОТРИМАННЯ СПИСКУ ДОСТУПНИХ РОКІВ
// -------------------------------------------------------------------------
$years = [];
$sqlYears = "
    SELECT DISTINCT YEAR(di.PERIOD) as y
    FROM ACCESS acc
    INNER JOIN DOC_INVOICE di 
        ON di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT 
       AND di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
    WHERE acc.ID_USERS = ?
      AND acc.ID_ORGANIZATIONS = ?
      AND acc.ID_REF_COUNTERAGENT = ?
    ORDER BY y DESC
";

$stmtY = mysqli_prepare($link, $sqlYears);
mysqli_stmt_bind_param($stmtY, "iii", $userId, $orgId, $selectedCounteragentId);
mysqli_stmt_execute($stmtY);
$resY = mysqli_stmt_get_result($stmtY);

while($rowY = mysqli_fetch_assoc($resY)) {
    $years[] = $rowY['y'];
}

// Якщо років немає в базі, додаємо поточний
if (empty($years)) {
    $years[] = date('Y');
}

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $years[0];


// -------------------------------------------------------------------------
// 2. Додано перевірку на наявність підписів
// -------------------------------------------------------------------------
$sql = "
    SELECT 
        rc.ID as ContractID,
        rc.`NAME` as ContractName,
        di.PERIOD,
        di.ID as InvoiceID,
        di.DOC_SUM_TAX,
        (di.DOC_PDF IS NOT NULL) AS has_pdf,
        (di.DOC_PDF_SIGN_ORG IS NOT NULL) AS sign_org,
        (di.DOC_PDF_SIGN_COUNTERAGENT IS NOT NULL) AS sign_ca
    FROM ACCESS acc
    INNER JOIN DOC_INVOICE di ON 
            di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
        AND di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT 
    inner JOIN REF_CONTRACT rc ON 
        di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS 
        and rc.ID = di.ID_REF_CONTRACT 
    WHERE acc.ID_USERS = ?
      AND acc.ID_ORGANIZATIONS = ?
      AND acc.ID_REF_COUNTERAGENT = ?
      AND YEAR(di.PERIOD) = ?
    ORDER BY rc.ID, di.PERIOD DESC
";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "iiii", $userId, $orgId, $selectedCounteragentId, $selectedYear);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// === ГРУПУВАННЯ ДАНИХ ===
$treeData = [];

while ($row = mysqli_fetch_assoc($result)) {
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
        'sign_ca'  => $row['sign_ca']
    ];
}
?>

<link href="../../css/ent_invoice.css" rel="stylesheet" type="text/css"/>

<div class="table-header-row sticky-header bills-header">
    <h3>Рахунки та акти</h3>
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
</div>

<div class="table-container">
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
                        $windowName = "invoice_view_" . $invoice['number'];
                        
                        // Логіка підписів
                        $requiredSignatures = ['has_pdf', 'sign_org', 'sign_ca'];
                        $isFullySigned = true; 

                        if (!empty($requiredSignatures)) {
                            foreach ($requiredSignatures as $req) {
                                if (empty($invoice[$req])) {
                                    $isFullySigned = false;
                                    break;
                                }
                            }
                        }
                        
                        // Статуси
                        $statusHtml = '<span class="status-badge status-unsigned">Не підписано</span>';
                        if ($invoice['has_pdf'] && $invoice['sign_org'] && $invoice['sign_ca']) {
                            $statusHtml = '<span class="status-badge status-signed-ca">Підписано контрагентом</span>';
                        } elseif ($invoice['sign_org']) {
                            $statusHtml = '<span class="status-badge status-signed-org">Готовий до підпису</span>';
                        }
                        
                        $isButtonDisabled = $isFullySigned && !$forceSign;
                ?>
                    <tr class="child-row show <?php echo $cGroupId; ?> detail-row">
                        <td>
                            <span class="bullet-icon">•</span> 
                            <?php echo getUkrMonth($invoice['period']); ?>
                        </td>
                        <td class="text-dark">
                            <?php echo htmlspecialchars($invoice['number']); ?>
                        </td>
                        <td class="text-bold">
                            <?php echo number_format($invoice['sum_tax'], 2, ',', ' '); ?>
                        </td>
                        
                        <td>
                            <a href="/api/get_ent_invoice.php?id=<?php echo htmlspecialchars($invoice['number']); ?>" 
                               target="<?php echo $windowName; ?>" 
                               class="btn-action btn-view" 
                               title="Переглянути рахунок">
                                Переглянути
                            </a>
                        </td>
                        
                        <td>
                            <button type="button" 
                                class="btn-action btn-sign" 
                                title="<?php echo $isButtonDisabled ? 'Документ вже підписаний обома сторонами' : 'Підписати документ'; ?>" 
                                onclick="<?php echo $isButtonDisabled ? 'return false;' : 'window.open(\'/api/content/SigningDocs.php?id=' . htmlspecialchars($invoice['number']) . '\', \'sign_window_' . htmlspecialchars($invoice['number']) . '\')'; ?>"
                                <?php echo $isButtonDisabled ? 'disabled' : ''; ?>>
                            Підписати
                        </button>
                        </td>

                        <td>
                            <?php echo $statusHtml; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="../../js/bills.js" type="text/javascript"></script>