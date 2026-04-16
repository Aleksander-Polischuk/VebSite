<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config.php";

// Приймаємо один ID або масив через кому (?ids=49,50,51)
$idsRaw = isset($_GET['ids']) ? $_GET['ids'] : (isset($_GET['id']) ? $_GET['id'] : '');
$doctype = $_GET['doctype'] ?? 'invoice'; 

$idsArray = array_values(array_unique(array_filter(array_map('trim', explode(',', $idsRaw)))));

if (empty($idsArray)) {
    die("<div style='padding: 20px; color: red;'>Помилка: Не вказано жодного документа для підпису.</div>");
}

$tableName = ($doctype === 'act') ? 'DOC_COUNTER_READINGS' : 'DOC_INVOICE';
$docNameUI = ($doctype === 'act') ? 'Акт' : 'Рахунок';

$userId = $_SESSION['id_users'] ?? 0;
$orgId = $_SESSION['id_organizations'] ?? ($IDOrganizations ?? 0); 

if (!$userId) die("<div style='padding: 20px; color: red;'>Помилка: Сесія завершена. Авторизуйтесь знову.</div>");

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$documentsQueue = [];
$alreadySigned = [];
$accessDenied = [];
$firstEDRPOU = "";

$sqlCheck = "
    SELECT di.ID, di.DOC_PDF_SIGN_COUNTERAGENT, rc.EDRPOU
    FROM {$tableName} di
    INNER JOIN ACCESS acc ON di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT AND di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
    INNER JOIN REF_COUNTERAGENT rc ON rc.ID_ORGANIZATIONS = di.ID_ORGANIZATIONS and rc.ID = di.ID_REF_COUNTERAGENT   
    WHERE di.ID = ? AND acc.ID_USERS = ? AND di.ID_ORGANIZATIONS = ?
";
$stmtCheck = mysqli_prepare($link, $sqlCheck);

foreach ($idsArray as $iddoc) {
    $iddoc = (int)$iddoc;
    mysqli_stmt_bind_param($stmtCheck, "iii", $iddoc, $userId, $orgId);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $docData = mysqli_fetch_assoc($resCheck);
    
    if (!$docData) {
        $accessDenied[] = $iddoc;
    } else if (!empty($docData['DOC_PDF_SIGN_COUNTERAGENT'])) {
        $alreadySigned[] = $iddoc;
    } else {
        if (empty($firstEDRPOU)) $firstEDRPOU = $docData['EDRPOU'];
        
        $documentsQueue[] = [
            'id' => $iddoc,
            'url' => ($doctype === 'act' ? "/api/get_act_pdf.php?type=1&id=" : "/api/get_ent_invoice.php?type=1&id=") . $iddoc,
            'filename' => ($doctype === 'act' ? "act_" : "invoice_") . $iddoc . ".pdf",
            'title' => $docNameUI . " № " . $iddoc
        ];
    }
}
mysqli_stmt_close($stmtCheck);
mysqli_close($link);

if (empty($documentsQueue)) {
    die("
    <div style='font-family: sans-serif; padding: 40px; text-align: center; background: #f8d7da; color: #721c24;'>
        <h2>Увага</h2>
        <p>Не знайдено документів, доступних для підпису. Можливо, вони вже підписані раніше.</p>
    </div>");
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Підпис документів</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="/css/CustomAlert.css" rel="stylesheet" type="text/css"/>
    <style>
        /* ВАШІ ОРИГІНАЛЬНІ СТИЛІ */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f0f2f5; 
            padding: 20px; 
        }
        
        .modal-content { 
            max-width: 600px; 
            margin: 0 auto; 
            background: #fff; 
            border-radius: 8px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            overflow: hidden; 
        }
        
        .card-header { 
            background: #3C9ADC; 
            color: white; 
            padding: 15px 20px; 
            font-size: 1.25rem; 
            font-weight: bold; 
        }
        
        .modal-body { padding: 20px; }
        
        .stage { 
            margin-bottom: 20px; 
            border: 1px solid #e3e6f0; 
            border-radius: 5px; 
            padding: 15px; 
        }
        
        .stage-header { 
            margin-bottom: 15px; 
            border-bottom: 1px solid #eee; 
            padding-bottom: 10px; 
        }
        
        .stage-header__num { 
            font-size: 12px; 
            color: #888; 
            text-transform: uppercase; 
        }
        
        .stage-header__title { 
            display: block; 
            font-size: 18px; 
            font-weight: bold; 
            color: #333; 
        }
        
        .stage-group { margin-bottom: 15px; }
        
        .stage-group__title { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 600; 
            font-size: 14px; 
        }
        
        select, input[type="text"], input[type="password"] { 
            width: 100%; 
            padding: 10px;
            border: 1px solid #ddd; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        
        .btn { 
            cursor: pointer; 
            padding: 10px 20px; 
            border-radius: 4px; 
            border: none; 
            font-weight: bold; 
            transition: 0.3s; 
        }
        
        .btn_blue { 
            background: #3C9ADC; 
            color: white; 
        }
        
        .btn_blue:hover { background: #2b78b0; }
        
        .btn-danger { 
            background: #dc3545; 
            color: white; 
        }
        
        .fn_iit_module_status { 
            display: block; 
            margin-bottom: 10px; 
            padding: 10px; 
            background: #e8f4fd; 
            color: #0c5460; 
            border-left: 4px solid #3C9ADC; 
            font-size: 14px; 
        }
        
        .hidden { display: none; }
        
        .input-error { 
            border-color: #ff8fa3 !important; 
            background-color: #fffcfc !important; 
            box-shadow: 0 0 8px rgba(255, 143, 163, 0.8) !important; 
            border-radius: 6px !important; 
            transition: box-shadow 0.3s ease, border-color 0.3s ease, background-color 0.3s ease !important; 
        }
        
        .file-input-wrapper { 
            position: relative; 
            width: 100%; 
            display: inline-block; 
        }
        
        #pkReadFileInput { 
            position: absolute; 
            left: 0; 
            top: 0; 
            opacity: 0; 
            width: 100%; 
            height: 100%; 
            cursor: pointer; 
            z-index: 10; 
        }

        .custom-file-label { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            width: 100%; 
            padding: 10px 15px; 
            font-family: inherit; 
            font-weight: 500; 
            color: #3C9ADC; 
            background-color: #fff; 
            border: 2px dashed #3C9ADC; 
            border-radius: 6px; 
            text-align: center; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            box-sizing: border-box; 
        }
        
        .custom-file-label:hover { 
            background-color: #f0f8ff; 
            border-style: solid; 
        }
        
        .custom-file-label::before { 
            content: '📂'; 
            margin-right: 10px; 
            font-size: 1.2em; 
        }
        
        .custom-file-label.file-selected::before { content: '📄'; }
        
        .custom-file-label.input-error { 
            border-color: #ff8fa3 !important; 
            border-style: solid !important; 
            background-color: #fffcfc !important; 
            box-shadow: 0 0 8px rgba(255, 143, 163, 0.4) !important; 
            color: #d63384 !important; 
        }
        
        .password-wrap { 
            position: relative; 
            display: flex; 
            align-items: center; 
        }
        
        .password-wrap input { 
            width: 100%; 
            padding-right: 40px !important; 
        }
        
        .toggle-password { 
            position: absolute; 
            right: 10px; 
            background-color: transparent !important; 
            border: none !important; 
            width: 24px; height: 24px; 
            cursor: pointer; 
            background-image: url("../../img/view.svg"); 
            background-repeat: no-repeat; 
            background-position: center; 
            background-size: contain; 
            opacity: 0.6; 
            transition: opacity 0.2s; 
            padding: 0 !important; 
            box-shadow: none !important; 
        }
        
        .toggle-password:hover { opacity: 1; }
        
        .toggle-password.show { background-image: url("../../img/no-view.svg"); }

        /* ДОДАТКОВІ СТИЛІ ДЛЯ СПИСКУ ТА ПРОГРЕС-БАРУ */
        .documents-list-container {
            margin-top: 15px;
            margin-bottom: 25px;
            background: #f8f9fc;
            border: 1px solid #d1d3e2;
            border-radius: 6px;
            padding: 15px;
            max-height: 250px;
            overflow-y: auto;
        }
        .document-item {
            font-size: 16px; 
            font-weight: 600;
            color: #3C9ADC;
            padding: 8px 10px;
            border-bottom: 1px solid #eaecf4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .document-item:last-child { border-bottom: none; }
        .document-item a {
            font-size: 14px;
            color: #858796;
            text-decoration: underline;
            font-weight: normal;
        }
        .document-item a:hover { color: #2b78b0; }
        
        .batch-progress-wrapper {
            width: 100%;
            background: #eaecf4;
            border-radius: 5px;
            height: 25px;
            margin: 15px 0;
            overflow: hidden;
        }
        #batchProgressBar {
            width: 0%;
            height: 100%;
            background: #1cc88a;
            color: #fff;
            text-align: center;
            line-height: 25px;
            font-weight: bold;
            transition: width 0.3s;
        }
    </style>
</head>
<body>

<?php include "../../CustomAlert.php"; ?>

<div id="fn_signature_modal">
    <div class="modal-content">
        <div class="card-header">
            Накладання КЕП (<?php echo count($documentsQueue); ?> док.)
        </div>
        <div class="modal-body">
            
            <div class="popup_wrapper" id="fn_iit_module_popup">
                <span class="fn_iit_module_status">Очікування...</span>

                <div id="fn_iit_module_init_key_stage" class="stage">
                    <div class="stage-header">
                        <span class="stage-header__num">Крок 1 з 3</span>
                        <span class="stage-header__title">Встановлення особистого ключа</span>
                    </div>
                    <div class="fn_error" style="display:none; margin-bottom: 15px; padding: 10px; background: #f8d7da; color: #721c24; border-radius: 4px;"><span></span></div>

                    <div class="stage-group">
                        <label style="margin-right: 15px;"><input type="radio" id="pkTypeFile" name="pkType" checked> Файловий</label>
                        <label><input type="radio" id="pkTypeKSP" name="pkType"> Хмарний</label>
                    </div>

                    <div id="pkFileBlock">
                        <div class="stage-group">
                            <span class="stage-group__title">Оберіть ЦСК:</span>
                            <select id="pkCASelect"></select>
                        </div>
                        <div class="stage-group">
                            <div class="file-input-wrapper">
                                <input type="file" id="pkReadFileInput">
                                <label for="pkReadFileInput" class="custom-file-label" id="pkReadFileLabel">Обрати файл ключа...</label>
                            </div>
                        </div>
                        <div class="stage-group">
                            <div class="password-wrap">
                                <input type="password" id="pkReadFilePasswordTextField" placeholder="Введіть пароль ключа">
                                <button type="button" class="toggle-password" id="toggleKeyPassword"></button>
                            </div>
                        </div>
                        <button type="button" id="customReadFileButton" class="btn btn_blue">Зчитати ключ</button>
                        <button id="pkReadFileButton" style="display: none;"></button>
                    </div>

                    <div id="pkKSPBlock" class="stage-group" style="display:none">
                        <select id="pkKSPSelect"></select>
                        <button id="pkReadKSPSelect" class="btn btn_blue" style="margin-top:10px;">Зчитати</button>
                    </div>
                </div>
                
                <div id="fn_iit_module_init_qr_code" class="stage" style="display:none;">
                    <div id="pkKSPQRBlock" style="display: none; text-align: center;"></div>
                </div>
                
                <div id="fn_iit_module_data_verification_stage" class="stage" style="display:none;">
                    <div class="stage-header">
                        <span class="stage-header__num">Крок 2 з 3</span>
                        <span class="stage-header__title">Перевірка даних</span>
                    </div>
                    <div class="stage-group"><b>Власник:</b> <span id="PKeyOwnerInfoSubjCN">-</span></div>
                    <div class="stage-group"><b>Організація:</b> <span id="PKeyOwnerInfoSubjOrg">-</span></div>
                    <div class="stage-group">
                        <b>ІПН / Код:</b> <span id="PKeyOwnerInfoSubjDRFOCode">-</span>
                        <div id="PKeyOwnerInfoSubjDRFOCodeSelect" style="display:none;"><?php echo $firstEDRPOU; ?></div>
                        <div id="PKeyOwnerInfoSubjDRFOCodeDescr" style="display:none; color:red; font-size:12px;">Код не збігається з організацією!</div>
                    </div>
                    
                    <div class="stage-group" style="margin-top: 15px;">
                        <span class="stage-group__title" style="font-size: 16px;">Документи для підпису:</span>
                        
                        <div class="documents-list-container">
                            <?php foreach($documentsQueue as $doc): ?>
                                <div class="document-item">
                                    <span>📄 <?php echo $doc['title']; ?></span>
                                    <a href="<?php echo $doc['url']; ?>" target="_blank">Переглянути PDF</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <button class="btn btn_blue" id="BtnVerificationNext" style="width: 100%;">Підписати всі документи</button>
                </div>

                <div id="fn_iit_module_sending_signed_file_stage" class="stage" style="display:none;">
                    <div class="stage-header">
                        <span class="stage-header__num">Крок 3 з 3</span>
                        <span class="stage-header__title">Прогрес підписання</span>
                    </div>
                    
                    <div style="text-align: center;">
                        <h4 id="batchCurrentDoc" style="color: #3C9ADC; margin-bottom: 15px;">Підготовка...</h4>
                        <div class="batch-progress-wrapper">
                            <div id="batchProgressBar">0%</div>
                        </div>
                        <p style="font-size: 12px; color: #dc3545;">Не закривайте сторінку до завершення процесу!</p>
                    </div>
                </div>

            </div>
            <div class="hidden">
                <div id="pkReadFileSelectAliasBlock"></div>
                <select id="pkReadFileAliasSelect"></select>
                <input id="pkKSPUserId">
            </div>
        </div>
    </div>
</div>
    
<script src="../../js/libs/jquery-3.6.0.min.js"></script>
<script src="../../js/CustomAlert.js"></script>
<script src="../../js/libs/select2.min.js.js"></script>

<script>
    const documentsQueue = <?php echo json_encode($documentsQueue); ?>;
    const docType = '<?php echo htmlspecialchars($doctype); ?>';

    window.setStatus = function(message) { $('.fn_iit_module_status').text(message); };

    async function loadIITScripts() {
        const path = '/iit-v2/js/';
        for (let file of ['promise.min.js', 'euscp.js', 'main.js']) {
            await new Promise((res, rej) => {
                const s = document.createElement('script');
                s.src = path + file + '?v=' + Date.now();
                s.onload = res; s.onerror = rej;
                document.head.appendChild(s);
            });
        }
    }

    $(document).ready(function() {
        loadIITScripts();

        $('#pkReadFileInput').on('change', function() {
            const fileInput = $(this);
            const label = $('#pkReadFileLabel');
            const fileName = fileInput.val().split('\\').pop();

            if (fileName) {
                label.addClass('file-selected').text(fileName);
            } else {
                label.removeClass('file-selected').text('Оберіть файл особистого ключа');
            }
        });

        $('#customReadFileButton').on('click', function(e) {
            e.preventDefault();
            $('#pkReadFileButton').trigger('click');
        });
        
        $('#toggleKeyPassword').on('click', function() {
            const pwdInput = $('#pkReadFilePasswordTextField');
            const iconBtn = $(this);
            if (pwdInput.attr('type') === 'password') {
                pwdInput.attr('type', 'text');
                iconBtn.addClass('show'); 
            } else {
                pwdInput.attr('type', 'password');
                iconBtn.removeClass('show'); 
            }
        });

        // -------------------------------------------------------------
        // Процес пакетного підпису 
        // -------------------------------------------------------------
        $('#BtnVerificationNext').on('click', async function() {
            if (!documentsQueue || documentsQueue.length === 0) return;
            
            $(this).prop('disabled', true);
            app.NextStage('fn_iit_module_data_verification_stage', 'fn_iit_module_sending_signed_file_stage');
            
            let successCount = 0;
            let total = documentsQueue.length;
            
            for (let i = 0; i < total; i++) {
                let doc = documentsQueue[i];
                $('#batchCurrentDoc').text(`Підписуємо: ${doc.title}`);
                setStatus(`Обробка документа ${i+1} з ${total}...`, 1);
                
                try {
                    let signResult = await app.SignBatchFile(doc.url, doc.filename);
                    
                    let formData = new FormData();
                    formData.append('SignedFile', signResult.signedFile);
                    formData.append('InfoOwnerSignature', signResult.ownerInfo);
                    formData.append('DocumentId', doc.id);
                    formData.append('DocType', docType);
                    
                    let response = await fetch('/api/save_signed_file.php', { method: 'POST', body: formData });
                    let res = await response.json();
                    
                    if (res.status !== 'success') throw new Error(res.message);
                    
                    successCount++;
                    let percent = Math.round((successCount / total) * 100);
                    $('#batchProgressBar').css('width', percent + '%').text(percent + '%');
                    
                } catch (err) {
                    if(typeof showAlert === 'function') {
                        showAlert(`Помилка при підписі ${doc.title}: ${err.message || err}`, 'error');
                    } else {
                        alert(`Помилка при підписі ${doc.title}: ${err.message || err}`);
                    }
                    $(this).prop('disabled', false);
                    app.NextStage('fn_iit_module_sending_signed_file_stage', 'fn_iit_module_data_verification_stage');
                    return; 
                }
            }
            
            setStatus('Всі документи успішно підписані!', 1);
            if(typeof showAlert === 'function') {
                showAlert('Всі обрані документи успішно підписані та збережені!', 'success');
            } else {
                alert('Всі обрані документи успішно підписані та збережені!');
            }
            
            // Вкладка сама закриється через 2.5 секунди
            if (window.opener && !window.opener.closed) {
                // Оновлюємо бейдж (кількість документів у меню)
                if (typeof window.opener.updateDocumentBadge === 'function') {
                    window.opener.updateDocumentBadge();
                }

                // Оновлюємо саму таблицю / лічильники
                if (typeof window.opener.refreshActiveContent === 'function') {
                    window.opener.refreshActiveContent();
                } else {
                    window.opener.location.reload();
                }
            }
            
            // 2. ЗАКРИВАЄМО ВІКНО ПІДПИСУ
            window.close();
        });
    });
</script>
</body>
</html>