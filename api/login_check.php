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

            session_start();

            $_SESSION['id_users']     = $id_users;
            $_SESSION['is_ent']       = $is_ent;
            
            // Додаємо час входу
            $SQLExecLog = "UPDATE USERS SET MTIME_LOGIN = NOW() WHERE ID=?";
            $stmtLog = mysqli_prepare($link, $SQLExecLog);
            mysqli_stmt_bind_param($stmtLog, "i", $id_users);
            mysqli_stmt_execute($stmtLog);
            
            $redirect = ($is_ent == 0 ? '/cabinet' : '/cabinet_ent');  

            echo json_encode([
               'success' => true,
               'redirect' => $redirect
            ]);
            exit;
        }
        // Якщо це юр.особа і пароль невірний потрібно перевірити чи вірний код активації 
        else if ($account_type == 1) {
            $SQLExec = "SELECT 
                        Res.ID, 
                        Res.ID_REF_COUNTERAGENT
                    FROM (
                        SELECT 
                            r.ID, 
                            r.ID_REF_COUNTERAGENT, 
                            a.ID_USERS
                        From ENT_REGISTRATION as r
                        LEFT JOIN ACCESS AS a ON (a.ID_ENT_REGISTRATION = r.ID)
                        WHERE r.DEL <> 1 and r.PHONE=? AND r.ACTIVATION_CODE=? AND r.ID_ORGANIZATIONS =?
                    ) AS Res

                    WHERE ID_USERS IS null";
            
            $stmt = mysqli_prepare($link, $SQLExec);
            mysqli_stmt_bind_param($stmt, "ssi", $phone, $password, $IDOrganizations);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($res) > 0) {
                session_start();

                $s_row = mysqli_fetch_array($res);
                $_SESSION['id_users']            = $id_users;
                $_SESSION['is_ent']              = $is_ent;
                $id_ref_account                  = 0;
                
                // Додаємо запис в ACCESS для нового підприємства
                $SQLExecAccess = "INSERT INTO ACCESS (ID_USERS, ID_REF_COUNTERAGENT, ID_REF_ACCOUNT, ID_ORGANIZATIONS, ID_ENT_REGISTRATION) VALUES (?, ?, ?, ?, ?)";
                $stmtAccess = mysqli_prepare($link, $SQLExecAccess);
                mysqli_stmt_bind_param($stmtAccess, "iiiii", $id_users, $s_row['ID_REF_COUNTERAGENT'], $id_ref_account, $IDOrganizations, $s_row['ID']);
                mysqli_stmt_execute($stmtAccess);
                
                // Додаємо час входу
                $SQLExecLog = "UPDATE USERS SET MTIME_LOGIN = NOW() WHERE ID=?";
                $stmtLog = mysqli_prepare($link, $SQLExecLog);
                mysqli_stmt_bind_param($stmtLog, "i", $id_users);
                mysqli_stmt_execute($stmtLog);
            
                echo json_encode([
                    'success' => true,
                    'redirect' => '/cabinet_ent'
                ]);    
                exit;
            }
            else { // Пароль НЕ вірний
                echo json_encode([
                   'success' => false
                ]);
                exit;
            }    
        }
        else { // Пароль НЕ вірний
            echo json_encode([
               'success' => false
            ]);
            exit;
        }    
    } 
      
    if ($record_exists == false and $account_type == 1) { // Якщо користувач ще відстутній (Перший вхід юр. особи по коду)
       $SQLExec = "select ID, ID_REF_COUNTERAGENT from ENT_REGISTRATION where PHONE='$phone' and ACTIVATION_CODE='$password' and ID_ORGANIZATIONS = $IDOrganizations"; 
       $res = mysqli_query($link, $SQLExec);
          
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