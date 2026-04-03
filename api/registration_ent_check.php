<?php
    header('Content-Type: application/json');

    // Функція відправки листа активації
    function SendActivationMail($to, $token) {
        $subject = "Підтвердження реєстрації cv.kgonline.in.ua";
        $subject = '=?utf-8?B?'.base64_encode($subject).'?=';
      
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        // Вказати дійсну пошту домену
        //$headers .= "From: noreply@develop.kgonline.in.ua\r\n"; 
        
        $headers .= "X-Mailer: PHP\r\n";

        $link = "http://cv.kgonline.in.ua/confirm?token=$token&email=" . urlencode($to);

        $templatePath = '../templates/activation_mail.html';
        
        if (file_exists($templatePath)) {
            $message = file_get_contents($templatePath);
            $message = str_replace('{{LINK}}', $link, $message);
        } else {
            $message = "Для підтвердження реєстрації перейдіть за посиланням: $link";
        }

        mail($to, $subject, $message, $headers);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'error method']);
        exit;
    }
    
    session_start();
   
    if (!isset($_SESSION['id_ent_registration'])) {
        echo json_encode([
            'success' => false, 
            'code' => 1, 
            'message' => 'Invalid value id_ent_registration'
        ]);
        exit;
    }
   
    $email    = (isset($_POST['email']) ? trim($_POST['email']) : '');
    $password = (isset($_POST['password']) ? $_POST['password'] : '');    

    if (strlen($email)  > 90) { $email  = substr($email,0,90); }
    if (strlen($password) > 30) { $password = substr($password,0,30); }
   
    //====================================
    include ('../config.php');
  
    $link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
    mysqli_set_charset($link, 'utf8');
   
    $phone               = $_SESSION['phone'];
    $id_ent_registration = $_SESSION['id_ent_registration'];
    $id_ref_counteragent = $_SESSION['id_ref_counteragent'];
    $is_ent              = 1;
    $id_ref_account      = 0;

    // чи існує користувач з таким телефоном
    $SQLExecPhone = "SELECT ID FROM USERS WHERE PHONE='$phone' AND IS_ENT=1"; 
    $resPhone = mysqli_query($link, $SQLExecPhone);

    if (mysqli_num_rows($resPhone) > 0) {
        
        $row = mysqli_fetch_assoc($resPhone);
        $id_users = $row['ID'];

        // Додаємо запис в ACCESS для нового підприємства
        $SQLExecAccess = "INSERT INTO ACCESS (ID_USERS, ID_REF_COUNTERAGENT, ID_REF_ACCOUNT, ID_ORGANIZATIONS, ID_ENT_REGISTRATION) VALUES (?, ?, ?, ?, ?)";
        $stmtAccess = mysqli_prepare($link, $SQLExecAccess);
        mysqli_stmt_bind_param($stmtAccess, "iiiii", $id_users, $id_ref_counteragent, $id_ref_account, $IDOrganizations, $id_ent_registration);
        mysqli_stmt_execute($stmtAccess);

    } else {
        
        // користувач відсутній -- повна реєстрація

        // Перевіряємо, чи такий E-mail раптом не зайнятий іншим користувачем
        $SQLExecEmail = "SELECT ID FROM USERS WHERE EMAIL='$email' AND IS_ENT=1"; 
        $resEmail = mysqli_query($link, $SQLExecEmail);
        
        if (mysqli_num_rows($resEmail) > 0 || $email == '') {
            echo json_encode([
                'success' => false,
                'code'    => 2,
                'message' => 'Invalid email'
            ]);
            exit;
        }
        
        // Генеруємо хеш пароля та токен
        $passwd_hash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(16));
        
        // Вставляємо в USERS
        $SQLExecInsert = "INSERT INTO USERS (PHONE, PASSWD_HASH, EMAIL, IS_ENT, ID_ENT_REGISTRATION, RECOVERY_TOKEN, IS_CONFIRMED) VALUES (?, ?, ?, ?, ?, ?, 0)";
        $stmtInsert = mysqli_prepare($link, $SQLExecInsert);
        mysqli_stmt_bind_param($stmtInsert, "sssiis", $phone, $passwd_hash, $email, $is_ent, $id_ent_registration, $token);
        mysqli_stmt_execute($stmtInsert);

        $id_users = mysqli_insert_id($link);

        // Вставляємо в ACCESS
            $SQLExecAccess = "INSERT INTO ACCESS (ID_USERS, ID_REF_COUNTERAGENT, ID_REF_ACCOUNT, ID_ORGANIZATIONS, ID_ENT_REGISTRATION) VALUES (?, ?, ?, ?, ?)";
        $stmtAccess = mysqli_prepare($link, $SQLExecAccess);
        mysqli_stmt_bind_param($stmtAccess, "iiiii", $id_users, $id_ref_counteragent, $id_ref_account, $IDOrganizations, $id_ent_registration);
        mysqli_stmt_execute($stmtAccess);
        
        // Відправляємо лист
        SendActivationMail($email, $token);   
    }
        
    // Одразу авторизуємо користувача в сесії
    $_SESSION['id_users'] = $id_users;
    $_SESSION['is_ent']   = 1;

    echo json_encode([
        'success' => true,
        'redirect' => '/cabinet_ent'
    ]);   
?>