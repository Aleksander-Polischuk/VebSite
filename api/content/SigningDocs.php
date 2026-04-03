<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../../config.php";

// Отримуємо ID документа
$iddoc = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$iddoc) {
    die("<div style='padding: 20px; color: red;'>Помилка: Не вказано номер документа для підпису.</div>");
}

// =========================================================================
// Перевірка прав доступу та статусу документа
// =========================================================================

$userId = $_SESSION['id_users'] ?? 0;
$orgId = $_SESSION['id_organizations'] ?? ($IDOrganizations ?? 0); 

if (!$userId) {
    die("<div style='padding: 20px; color: red;'>Помилка: Сесія завершена. Авторизуйтесь знову.</div>");
}

$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
mysqli_set_charset($link, 'utf8');

$sqlCheck = "
    SELECT di.ID, di.DOC_PDF_SIGN_COUNTERAGENT, rc.EDRPOU
    FROM DOC_INVOICE di
    INNER JOIN ACCESS acc ON 
        di.ID_REF_COUNTERAGENT = acc.ID_REF_COUNTERAGENT AND 
        di.ID_ORGANIZATIONS = acc.ID_ORGANIZATIONS
    INNER JOIN REF_COUNTERAGENT rc ON 
	     rc.ID_ORGANIZATIONS = di.ID_ORGANIZATIONS and
	     rc.ID = di.ID_REF_COUNTERAGENT    
    WHERE di.ID = ? 
      AND acc.ID_USERS = ? 
      AND di.ID_ORGANIZATIONS = ?
";

$stmtCheck = mysqli_prepare($link, $sqlCheck);
mysqli_stmt_bind_param($stmtCheck, "iii", $iddoc, $userId, $orgId);
mysqli_stmt_execute($stmtCheck);
$resCheck = mysqli_stmt_get_result($stmtCheck);
$docData = mysqli_fetch_assoc($resCheck);

mysqli_stmt_close($stmtCheck);
mysqli_close($link);

// 1. Якщо запит нічого не повернув — це чужий документ або вигаданий ID
if (!$docData) {
    die("
    <div style='font-family: sans-serif; padding: 40px; text-align: center; background: #f8d7da; color: #721c24; border-bottom: 3px solid #f5c6cb;'>
        <h2>Помилка доступу</h2>
        <p>Документ не знайдено, або у вас немає прав на його перегляд та підпис.</p>
    </div>");
}

// 2. Якщо поле DOC_PDF_SIGN_COUNTERAGENT не пусте — документ вже підписано

if (!empty($docData['DOC_PDF_SIGN_COUNTERAGENT'])) {
    die("
    <div style='font-family: sans-serif; padding: 40px; text-align: center; background: #d4edda; color: #155724; border-bottom: 3px solid #c3e6cb;'>
        <h2>Документ вже підписано!</h2>
        <p>Цей рахунок вже має ваш електронний підпис. Повторне підписання неможливе.</p>
    </div>");
}

// =========================================================================
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>IIT Підпис - Рахунок № <?php echo $iddoc; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 20px; }
        .modal-content { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { background: #3C9ADC; color: white; padding: 15px 20px; font-size: 1.25rem; font-weight: bold; }
        .modal-body { padding: 20px; }
        .stage { margin-bottom: 20px; border: 1px solid #e3e6f0; border-radius: 5px; padding: 15px; }
        .stage-header { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .stage-header__num { font-size: 12px; color: #888; text-transform: uppercase; }
        .stage-header__title { display: block; font-size: 18px; font-weight: bold; color: #333; }
        .stage-group { margin-bottom: 15px; }
        .stage-group__title { display: block; margin-bottom: 5px; font-weight: 600; font-size: 14px; }
        select, input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { cursor: pointer; padding: 10px 20px; border-radius: 4px; border: none; font-weight: bold; transition: 0.3s; }
        .btn_blue { background: #3C9ADC; color: white; }
        .btn_blue:hover { background: #2b78b0; }
        .btn-danger { background: #dc3545; color: white; }
        .fn_iit_module_status { display: block; margin-bottom: 10px; padding: 10px; background: #e8f4fd; color: #0c5460; border-left: 4px solid #3C9ADC; font-size: 14px; }
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

        .custom-file-label.file-selected::before {
            content: '📄'; /*🔐 📝 */
        }

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
            width: 24px;
            height: 24px;
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

        .toggle-password:hover {
            opacity: 1; 
        }

        .toggle-password.show {
            background-image: url("../../img/no-view.svg"); 
        }
    </style>
    <link href="../../css/CustomAlert.css" rel="stylesheet">
</head>
<body>
<?php include "../../CustomAlert.php"; ?>
<div id="fn_signature_modal">
    <div class="modal-content">
        <div class="card-header">
            <div class="heading_modal">Підпис файлу</div>
        </div>
        <div class="modal-body">
            
            <div class="popup_wrapper" id="fn_iit_module_popup">
                <span class="fn_iit_module_status">Очікування...</span>

                <div id="fn_iit_module_init_key_stage" class="stage">
                    <div class="stage-header">
                        <span class="stage-header__num">Крок 1 з 4</span>
                        <span class="stage-header__title">Встановлення особистого ключа</span>
                    </div>

                    <div class="stage-group">
                        <span class="stage-group__title">Тип носія:</span>
                        <label><input type="radio" id="pkTypeFile" name="pkType" checked> Файловий</label>
                        <label><input type="radio" id="pkTypeKSP" name="pkType"> Хмарний (Дія/SmartID)</label>
                    </div>

                    <div id="pkFileBlock">
                        <div class="stage-group">
                            <span class="stage-group__title">Оберіть ЦСК:</span>
                            <select id="pkCASelect"></select>
                        </div>
                        <div class="stage-group">
                            <span class="stage-group__title">Файл ключа:</span>
                            <div class="file-input-wrapper">
                                <input type="file" id="pkReadFileInput">
                                <label for="pkReadFileInput" class="custom-file-label" id="pkReadFileLabel">Натисніть, щоб обрати файл ключа...</label>
                            </div>
                        </div>
                        <div class="stage-group">
                            <span class="stage-group__title">Пароль:</span>
                            <div class="password-wrap">
                                <input type="password" id="pkReadFilePasswordTextField" placeholder="Введіть пароль ключа" autocomplete="new-password">
                                <button type="button" class="toggle-password" id="toggleKeyPassword" title="Показати/приховати пароль"></button>
                            </div>
                        </div>
                        <button type="button" id="customReadFileButton" class="btn btn_blue">Зчитати ключ</button>
                        <button id="pkReadFileButton" style="display: none;"></button>
                    </div>

                    <div id="pkKSPBlock" class="stage-group" style="display:none">
                        <span class="stage-group__title">Тип сервісу:</span>
                        <select id="pkKSPSelect"></select>
                        <button id="pkReadKSPSelect" class="btn btn_blue" style="margin-top:10px;">Зчитати</button>
                    </div>
                </div>
                
                <div id="fn_iit_module_init_qr_code" class="stage" style="display:none;">
                    <div class="stage-header">
                        <div class="stage-header__center">
                            <span class="stage-header__title">QR-код</span>
                            <span class="fn_stage_status stage-header__status"></span>
                        </div>
                    </div>
                    <div id="pkKSPQRBlock" style="text-align: center; margin-top: 15px;"></div>
                    <div id="pkKSPQRTimerBlock" style="text-align: center; margin-top: 10px; font-weight: bold; color: #dc3545;">
                        <label id="pkKSPQRTimerLabel"></label>
                    </div>
                </div>
                
                <div id="fn_iit_module_data_verification_stage" class="stage" style="display:none;">
                    <div class="stage-header">
                        <span class="stage-header__num">Крок 2 з 4</span>
                        <span class="stage-header__title">Перевірка даних</span>
                    </div>
                    <div class="stage-group">
                        <span class="stage-group__title">Власник:</span>
                        <div id="PKeyOwnerInfoSubjCN" style="font-weight:bold;">-</div>
                    </div>
                    <div class="stage-group">
                        <span class="stage-group__title">Організація:</span>
                        <div id="PKeyOwnerInfoSubjOrg" style="font-weight:bold;">-</div>
                    </div>
                    <div class="stage-group">
                        <span class="stage-group__title">ІПН / Код:</span>
                        <div id="PKeyOwnerInfoSubjDRFOCodeSelect" style="display:none;"><?php echo $docData['EDRPOU']; ?></div>
                        <div id="PKeyOwnerInfoSubjDRFOCode">-</div>
                                                
                        <div id="PKeyOwnerInfoSubjDRFOCodeDescr" style="display:none; font-weight:bold; margin-top: 10px;">Зверніть увагу ІПН/Код не збігається з обраною організацією</div>
                    </div>
                    <button class="btn btn-danger" onclick="app.NextStage('fn_iit_module_data_verification_stage','fn_iit_module_init_key_stage')">Повернутись</button>
                    <button class="btn btn_blue" id="BtnVerificationNext" onclick="app.NextStage('fn_iit_module_data_verification_stage','fn_iit_module_file_signature_stage')">Все вірно</button>
                </div>

                <div id="fn_iit_module_file_signature_stage" class="stage" style="display:none;">
                    <div class="stage-header">
                        <span class="stage-header__num">Крок 3 з 4</span>
                        <span class="stage-header__title">Підпис файлів</span>
                    </div>
                    <div class="stage-group">
                        <span class="stage-group__title">Документ:</span>
                        <span id="FileTypeName">Рахунок № <?php echo htmlspecialchars($iddoc); ?> </span>
                        
                        <a href="/api/get_ent_invoice.php?id=<?php echo htmlspecialchars($iddoc); ?>" target="_blank" class="btn" style="background:#eee; color:#333; text-decoration:none; display:inline-block; margin-bottom:10px;">Переглянути</a>
                    </div>
                    
                    <a id="FileToSign" href="/api/get_ent_invoice.php?type=1&id=<?php echo htmlspecialchars($iddoc); ?>" data-filename="invoice_<?php echo htmlspecialchars($iddoc); ?>.pdf.p7s"></a>
                    <br>
                    <button id="SignFileButton" class="btn btn_blue" onclick="app.SignFile()">Підписати</button>
                </div>

                <div id="fn_iit_module_sending_signed_file_stage" class="stage" style="display:none;">
                    <form id="fn_sending_signed_file" method="POST">
                        <input id="SignedFile" type="file" name="SignedFile" class="hidden">
                        <input id="InfoOwnerSignature" type="hidden" name="InfoOwnerSignature">
                        <input id="FileTypeId" type="hidden" name="FileTypeId">
                        <input id="DocumentId" type="hidden" name="DocumentId" value="<?php echo htmlspecialchars($iddoc); ?>">
                        <input type="hidden" name="SignedFile" value="1">
                        <div class="stage-header">
                            <span class="stage-header__num">Крок 4 з 4</span>
                            <span class="stage-header__title">Відправка підписаного файлу</span>
                        </div>
                        <div class="stage-group">
                            <span class="stage-group__title">Файл готовий:</span>
                            <span id="SignedFileName" style="color:green; font-weight:bold;"></span>
                        </div>
                        
                        <button class="btn btn_blue" type="submit">Відправити на сервер</button>
                    </form>
                </div>

            </div>

            <div class="hidden">
                <span id="PKeyFileName"></span>
                <span class="fn_stage_status"></span>
                <div class="fn_error"><span></span></div>
                <div id="PKeyFileInputDropZone"></div>
                <div id="PKeyFileReadBlock"></div>
                <div id="PKeyFileReadSelectedBlock"></div>
                <div id="pkReadFileSelectAliasBlock"></div>
                <select id="pkReadFileAliasSelect"></select>
                <div id="pkKSPUserIdBlock"></div>
                <input id="pkKSPUserId">
                </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../../js/CustomAlert.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>
    var rootUrl = window.location.origin;

    window.setStatus = function(message) {
        $('.fn_iit_module_status').text(message);
    };

    async function loadIITScripts() {
        const path = '/iit-v2/js/';
        const scripts = ['promise.min.js', 'euscp.js', 'main.js'];
        
        for (let file of scripts) {
            await new Promise((resolve, reject) => {
                const s = document.createElement('script');
                s.src = path + file + '?v=' + Date.now();
                s.async = false;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }
        console.log("IIT Library fully loaded");
    }

    $(document).ready(function() {
        // ==========================================
        // 1. ПОДІЯ: ЗМІНА ФАЙЛУ КЛЮЧА
        // ==========================================
        $('#pkReadFileInput').on('change', function() {
            const fileName = $(this).val().split('\\').pop(); 
            const label = $('#pkReadFileLabel');

            if (fileName) {
                // Якщо файл обрано: показуємо ім'я, прибираємо помилку, ДОДАЄМО КЛАС ІКОНКИ
                label.text(fileName);
                label.removeClass('input-error');
                label.addClass('file-selected'); // Змінює 📂 на 📄
            } else {
                // Якщо вибір скасовано: повертаємо текст, ЗНІМАЄМО КЛАС ІКОНКИ
                label.text('Натисніть, щоб обрати файл ключа...');
                label.removeClass('file-selected'); // Повертає 📂
            }
        });

        // ==========================================
        // 2. ПОДІЯ: ЗНЯТТЯ ПОМИЛОК ПРИ ВВЕДЕННІ
        // ==========================================
        $('#pkCASelect, #pkReadFilePasswordTextField').on('input change', function() {
            $(this).removeClass('input-error');
        });

        // ==========================================
        // 3. ПОДІЯ: КЛІК ПО КНОПЦІ "ЗЧИТАТИ КЛЮЧ"
        // ==========================================
        $('#customReadFileButton').on('click', function(e) {
            e.preventDefault();
            let hasError = false;

            // Спочатку скидаємо всі попередні підсвічування (УВАГА: тут тепер pkReadFileLabel)
            $('#pkCASelect, #pkReadFileLabel, #pkReadFilePasswordTextField').removeClass('input-error');

            // Перевіряємо селект ЦСК
            if (!$('#pkCASelect').val()) {
                $('#pkCASelect').addClass('input-error');
                hasError = true;
            }

            // Перевіряємо файл ключа
            const fileInput = document.getElementById('pkReadFileInput');
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                $('#pkReadFileLabel').addClass('input-error');
                hasError = true;
            }

            // Перевіряємо пароль
            const password = $('#pkReadFilePasswordTextField').val();
            if (!password || password.trim() === '') {
                $('#pkReadFilePasswordTextField').addClass('input-error');
                hasError = true;
            }

            // Якщо є помилки - зупиняємось
            if (hasError) {
                return; 
            }

            // Передаємо команду бібліотеці ІІТ
            $('#pkReadFileButton').trigger('click');
        });
        
        // ==========================================
        // ІНІЦІАЛІЗАЦІЯ
        // ==========================================
        loadIITScripts();
        
        $('#fn_sending_signed_file').on('submit', function(e) {
            e.preventDefault();
            setStatus('Відправка на сервер...');
            let formData = new FormData(this);

            $.ajax({
                url: '/api/save_signed_file.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        let res = typeof response === 'object' ? response : JSON.parse(response);
                        if (res.status === 'success') {
                            showAlert(res.message, 'success'); 
                            setTimeout(function() { window.close(); }, 3000);
                        } else {
                            showAlert(res.message, 'error');
                        }
                    } catch (e) {
                        console.error("Помилка парсингу:", response);
                        showAlert("Помилка відповіді сервера", 'error');
                    }
                },
                error: function() {
                    showAlert('Помилка зв\'язку з сервером', 'error');
                }
            });
        });
        
        setupSignature($('#FileToSign').attr('href'), $('#FileTypeName').text(), <?php echo $iddoc; ?>);
        
        // ==========================================
        // КНОПКА ПОКАЗАТИ/ПРИХОВАТИ ПАРОЛЬ
        // ==========================================
        $('#toggleKeyPassword').on('click', function() {
            const pwdInput = $('#pkReadFilePasswordTextField');
            const iconBtn = $(this);
            
            // Якщо зараз пароль - робимо текст, якщо текст - робимо пароль
            if (pwdInput.attr('type') === 'password') {
                pwdInput.attr('type', 'text');
                iconBtn.addClass('show'); // Змінюємо іконку
            } else {
                pwdInput.attr('type', 'password');
                iconBtn.removeClass('show'); // Повертаємо початкову іконку
            }
        });
        
    });

    function setupSignature(fileUrl, typeName, docId) {
        $('#FileToSign').attr('href', fileUrl);
        $('#FileTypeName').text(typeName);
        $('#DocumentId').val(docId);
        $('#fn_iit_module_popup').show();
    }
</script>

</body>
</html>