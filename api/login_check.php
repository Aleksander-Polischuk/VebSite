<?php
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'error method'
        ]);
        exit;
    }
    
   $account_type = (isset($_POST['account_type']) ? $_POST['account_type'] : 0);
   $phone        = trim((isset($_POST['phone']) ? $_POST['phone'] : ''));
   $password     = (isset($_POST['password']) ? $_POST['password'] : '');    

   if (strlen($phone)  > 20) { $login  = substr($phone,0,20); }
   if (strlen($password) > 30) { $passwd = substr($password,0,30); }
   
   if (!is_numeric($account_type)) {
       echo json_encode([
            'success' => false,
            'message' => 'Invalid value account_type'
        ]);
        exit;
   }
   
   /////////////////////////////////////////////////////////////////////////////////////////////
   include ('../config.php');
  
   $link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
   mysqli_set_charset($link, 'utf8');
   
   $SQLExec = "select ID, PHONE, PASSWD_HASH, IS_ENT, IS_CONFIRMED from USERS where PHONE='$phone' and IS_ENT=$account_type"; 
   $res = mysqli_query($link, $SQLExec);
    
   $record_exists = false; 
   if (mysqli_num_rows($res) > 0) {
     $record_exists = true;
        
    $s_row = mysqli_fetch_array($res);
    $id_users      = $s_row['ID'];
    $is_ent        = $s_row['IS_ENT'];
    $password_hash = $s_row['PASSWD_HASH'];
    $is_confirmed  = $s_row['IS_CONFIRMED']; 
     
    if (password_verify($password, $password_hash)) {  // Пароль вірний
        
        // 2. ПЕРЕВІРЯЄМО ЧИ ПІДТВЕРДЖЕНА ПОШТА
        // Якщо це юр. особа (is_ent=1) і пошта не підтверджена (is_confirmed=0)
        /*
        if ($is_ent == 1 && $is_confirmed == 0) {
             echo json_encode([
               'success' => false,
               'message' => 'Ваш обліковий запис не активовано. Перевірте пошту.' 
            ]);
            exit;
        }
        */
        session_start();
        
        $_SESSION['id_users']     = $id_users;
        $_SESSION['is_ent']       = $is_ent;
        
        $redirect = ($is_ent == 0 ? '/cabinet' : '/cabinet_ent');  
        
        echo json_encode([
           'success' => true,
           'redirect' => $redirect
        ]);
        exit;
    }
    else {                                       // Пароль НЕ вірний
        echo json_encode([
           'success' => false
        ]);
        exit;
    }    
   } 
   elseif ($account_type != 1) {
    echo json_encode([
           'success' => false
    ]);
        exit;
   }
   
   else { // Якщо користувач ще відстутній (Перший вхід юр. особи по коду)
    $SQLExec = "select ID, ID_REF_COUNTERAGENT from ENT_REGISTRATION where PHONE='$phone' and ACTIVATION_CODE='$password' and ID_ORGANIZATIONS = $IDOrganizations"; 
       $res = mysqli_query($link, $SQLExec);
   
       $record_exists = false; 
       if (mysqli_num_rows($res) > 0) {
            session_start();
            
            $s_row = mysqli_fetch_array($res);
            $_SESSION['id_ent_registration'] = $s_row['ID'];
            $_SESSION['id_ref_counteragent'] = $s_row['ID_REF_COUNTERAGENT'];    
            $_SESSION['phone']               = $phone;
                
            echo json_encode([
            'success' => true,
            'redirect' => '/registration_ent'
        ]);    
            exit;
       }
        
    else {                        // Якщо код активації відсутній
        echo json_encode([
           'success' => false
        ]);
            exit;
    }   
   }
?>