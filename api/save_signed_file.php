<?php
// Функція для підпису
function addStamp($pdf, $signatoryData, $y, $w, $h, $is_owner_document) {
    $text = '';
    $x = $is_owner_document ? 10 : 110;
       
    /*Юр особа*/
    if (!empty($signatoryData->subjEDRPOUCode) || !empty($signatoryData->subjDRFOCode)) {
        if (!empty($signatoryData->subjEDRPOUCode)) {
            $text .= trim($signatoryData->subjOrg);
        } elseif (!empty($signatoryData->subjDRFOCode)) {
            $text .= trim($signatoryData->subjCN);
        }
        $text .= PHP_EOL;
    }

    /*EDRPOU or DRFO*/
    if (!empty($signatoryData->subjEDRPOUCode) || !empty($signatoryData->subjDRFOCode)) {
        $text .= 'ЄДРПОУ/ІПН' . PHP_EOL;
        if (!empty($signatoryData->subjEDRPOUCode)) {
            $text .= $signatoryData->subjEDRPOUCode;
        } elseif (!empty($signatoryData->subjDRFOCode)) {
            $text .= $signatoryData->subjDRFOCode;
        }
    }

    $color = $is_owner_document ? [0, 87, 184] : [0, 0, 0];
    $style5 = array('width' => 1, 'dash' => '1', 'color' => $color);
    //Вставимо коло
    $pdf->Circle($x + $w / 2, $y + $w / 2, $w / 2, 0, 360, '', $style5);

    //Вставимо текст в коло
    $pdf->SetTextColor($color[0], $color[1], $color[2]);
    $pdf->SetFont('dejavusans', 'b', 7, '', 'default', true);

    $pdf->MultiCell($w, $h, $text, 0, "C", false, true, $x, $y, true, 0, false, true, $w, "M");
}
function addSignerInformation($pdf, $signatoryData, $y, $w, $h, $is_owner_document) {
    $x = $is_owner_document ? 10 : 110;
    $text = 'Електронний підпис' . PHP_EOL;
    date_default_timezone_set('Europe/Kyiv');
    
    //Дата підпису
    if (!empty($signatoryData->signTimeStamp) && strtotime($signatoryData->signTimeStamp)) {
        $text .= date("H:i d.m.Y", strtotime($signatoryData->signTimeStamp)) . PHP_EOL;
    }

    //EDRPOU or DRFO
    if (!empty($signatoryData->subjEDRPOUCode) || !empty($signatoryData->subjDRFOCode)) {
        $text .= 'ЄДРПОУ/ІПН: ';
        if (!empty($signatoryData->subjEDRPOUCode)) {
            $text .= trim($signatoryData->subjEDRPOUCode);
        } elseif (!empty($signatoryData->subjDRFOCode)) {
            $text .= trim($signatoryData->subjDRFOCode);
        }
        $text .= PHP_EOL;
    }

    //Юридична назва
    if (!empty($signatoryData->subjEDRPOUCode) || !empty($signatoryData->subjDRFOCode)) {
        if (!empty($signatoryData->subjEDRPOUCode)) {
            $text .= 'Юр. назва: ' . trim($signatoryData->subjOrg) . PHP_EOL;
            $text .= 'Керівник: ' . trim($signatoryData->subjCN);
        } elseif (!empty($signatoryData->subjDRFOCode)) {
            $text .= 'Керівник: ' . trim($signatoryData->subjCN);
        }
        $text .= PHP_EOL;
    }
     
    //Час перевірки ЕЦП
    if (!empty($signatoryData->signTimeStamp) && strtotime($signatoryData->signTimeStamp)) {
        $text .= 'Час перевірки КЕП/ЕЦП: ' . date("H:i d.m.Y", strtotime($signatoryData->signTimeStamp)) . PHP_EOL;
    }

    //Серійний номер
    if (!empty($signatoryData->serial)) {
        $text .= 'Серійний номер: ' . PHP_EOL . $signatoryData->serial . PHP_EOL;
    }

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('dejavusans', '', 7, '', 'default', true);

    $pdf->MultiCell(65, $h, $text, 0, "L", false, true, $x + $w, $y, true, 0, false, true, 0, "T");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require "../config.php";

// Перевірка авторизації
$userId = $_SESSION['id_users'] ?? 0;
if (!$userId) {
    die(json_encode(['status' => 'error', 'message' => 'Сесія завершена. Авторизуйтесь знову.']));
}

// Перевірка вхідних даних
$documentId = isset($_POST['DocumentId']) ? (int)$_POST['DocumentId'] : 0;
if (!$documentId || !isset($_FILES['SignedFile'])) {
    die(json_encode(['status' => 'error', 'message' => 'Недостатньо даних для збереження.']));
}

$InfoOwnerSignature = isset($_POST['InfoOwnerSignature']) ? $_POST['InfoOwnerSignature'] : '';

if ($InfoOwnerSignature == '') {
   exit; 
}

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

/////// накладання підпису на чистий PDF /////////////////////////////////////////////////////////

$signData_Counteragent = json_decode($InfoOwnerSignature);

require_once('../../www/libs/tcpdf/tcpdf.php');
require_once('../../www/libs/fpdi/src/autoload.php');

use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

$sql = "
    SELECT 
        di.DOC_PDF,
        di.DOC_PDF_SIGN_ORG_INFO
        
    FROM DOC_INVOICE di
    INNER JOIN ACCESS acc 
        ON di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT 
       AND di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
    WHERE di.ID = ? 
      AND acc.ID_USERS = ?
      AND di.ID_ORGANIZATIONS = ?
    LIMIT 1
";

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "iii", $documentId, $userId, $IDOrganizations);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $pdfBlob, $Str_signData);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Допустим, $pdfBlob — это твой PDF из базы или API
if (empty($pdfBlob)) {
     echo json_encode([
        'status' => 'error', 
        'message' => 'Відсутній PDF файл !'
    ]);
   exit;
}

try {
    $signData_Org = json_decode($Str_signData);
    $pdf = new Fpdi();

    $stream    = StreamReader::createByString($pdfBlob);
    $pageCount = $pdf->setSourceFile($stream);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $size       = $pdf->getTemplateSize($templateId);
        
        $pdf->AddPage($size['orientation'], $size);

        $pdf->useTemplate($templateId);

        $y = 245;
        $w = 30;
        $h = $w;

        $is_owner_document = true;
        addStamp($pdf, $signData_Org, $y, $w, $h, $is_owner_document);
        addSignerInformation($pdf, $signData_Org, $y, $w, $h, $is_owner_document);
        
        $is_owner_document = false;
        addStamp($pdf, $signData_Counteragent, $y, $w, $h, $is_owner_document);
        addSignerInformation($pdf, $signData_Counteragent, $y, $w, $h, $is_owner_document);
    }
  //  if (ob_get_level()) ob_end_clean();
    
    $doc_pdf = $pdf->Output('', 'S');

} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
   exit;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*$sql = "
    UPDATE DOC_INVOICE di
    INNER JOIN ACCESS acc ON 
        di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT AND 
        di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
    SET di.DOC_PDF_SIGN_COUNTERAGENT = ?,
        di.DOC_PDF = ?
    WHERE di.ID = ? 
      AND acc.ID_USERS = ? 
      AND di.ID_ORGANIZATIONS = ?
";*/

// Отримуємо вміст підписаного файлу
$fileData = file_get_contents($_FILES['SignedFile']['tmp_name']);

$sql = "
    UPDATE DOC_INVOICE di
    INNER JOIN ACCESS acc ON 
        di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT AND 
        di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
    SET di.DOC_PDF_SIGN_COUNTERAGENT = ?,
        di.DOC_PDF = ?
    WHERE di.ID = ?
      AND acc.ID_USERS = ? 
      AND di.ID_ORGANIZATIONS = ?
";

$stmt = mysqli_prepare($link, $sql);

$null = NULL;
mysqli_stmt_bind_param($stmt, "bbiii", $null, $null, $documentId, $userId, $IDOrganizations);
mysqli_stmt_send_long_data($stmt, 0, $fileData);
mysqli_stmt_send_long_data($stmt, 1, $doc_pdf);

if (mysqli_stmt_execute($stmt)) {
    $_POST['id'] = $documentId;

    echo json_encode([
        'status' => 'success', 
        'message' => 'Документ успішно підписано, електронну печатку накладено!'
        
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Виникла помилка при збереженні підпису в базі даних.'
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($link);
?>