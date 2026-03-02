<?php
//
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

        $pdf->MultiCell($w, $h,
            $text, 0, "C",
            false, true,
            $x, $y,
            true, 0,
            false, true,
            $w, "M"
        );
    }

function addSignerInformation($pdf, $signatoryData, $y, $w, $h, $is_owner_document) {
        $x = $is_owner_document ? 10 : 110;
        $text = 'Електронний підпис' . PHP_EOL;
//        foreach ((array)$signatoryData as $fill => $value) {
//            $text .= $fill . ' : ' . $value . PHP_EOL;
//        }

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
        date_default_timezone_set('Europe/Kyiv'); 
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

        $pdf->MultiCell(65, $h,
            $text, 0, "L",
            false, true,
            $x + $w, $y,
            true, 0,
            false, true,
            0, "T"
        );
}

$InfoOwnerSignature = '{"isFilled":true,"issuer":"O=ТОВ \"Центр сертифікації ключів \"Україна\";CN=КНЕДП ТОВ \"Центр сертифікації ключів \"Україна\";Serial=UA-36865753-2401;C=UA;L=Київ;OI=NTRUA-36865753","issuerCN":"КНЕДП ТОВ \"Центр сертифікації ключів \"Україна\"","serial":"70CAF70700000000000000000000000000000001","subject":"O=ТОВ ІАЦ \"ЗЕНІТ\";CN=Закревський Сергій Володимирович;SN=Закревський;GivenName=Сергій Володимирович;Serial=TINUA-2923519791;C=UA;L=місто СВІТЛОВОДСЬК;ST=КІРОВОГРАДСЬКА;OI=NTRUA-31252419","subjCN":"Закревський Сергій Володимирович","subjOrg":"ТОВ ІАЦ \"ЗЕНІТ\"","subjOrgUnit":"","subjTitle":"","subjState":"КІРОВОГРАДСЬКА","subjLocality":"місто СВІТЛОВОДСЬК","subjFullName":"Закревський Сергій Володимирович","subjAddress":"","subjPhone":"","subjEMail":"office@zenit.org.ua","subjDNS":"","subjEDRPOUCode":"31252419","subjDRFOCode":"2923519791","version":2,"isTimeAvail":true,"isTimeStamp":true,"time":"2026-02-25T08:11:58.000Z","isSignTimeStampAvail":true,"signTimeStamp":"2026-02-25T08:11:59.000Z"}';
$signData_Counteragent = json_decode($InfoOwnerSignature);

require_once('../../www/libs/tcpdf/tcpdf.php');
require_once('../../www/libs/fpdi/src/autoload.php');

use setasign\Fpdi\Tcpdf\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

session_start();

$userId = $_SESSION['id_users'] ?? 0;
$invoiceId = 37278;

require '../config.php';

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

// Отримуємо тільки сам файл
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
mysqli_stmt_bind_param($stmt, "iii", $invoiceId, $userId, $IDOrganizations);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $pdfBlob, $Str_signData);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);


// Допустим, $pdfBlob — это твой PDF из базы или API
if (empty($pdfBlob)) die('Відсутній PDF файл !');

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
    if (ob_get_level()) ob_end_clean();
   
    $resultBlob = $pdf->Output('', 'S');

} catch (\Exception $e) {
    die('Ошибка: ' . $e->getMessage());
}

header('Content-Type: application/pdf');
echo $resultBlob;

?>