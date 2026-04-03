<?php
   exit;
   include "../config.php";

   $link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
   mysqli_set_charset($link, 'utf8');
   
   $SQLExec = "select TMP_USERS.PHONE, TMP_USERS.PASSWD, TMP_USERS.EMAIL, USERS.ID from TMP_USERS LEFT JOIN USERS ON (USERS.PHONE = TMP_USERS.PHONE)"; 
   $stmt = mysqli_prepare($link, $SQLExec);
   mysqli_stmt_execute($stmt);
   $result = mysqli_stmt_get_result($stmt);

   while ($row = mysqli_fetch_assoc($result)) {
        $is_ent      = 1;
      //  $token       = bin2hex(random_bytes(16));
      //  $passwd_hash = password_hash($row['PASSWD'], PASSWORD_DEFAULT);       
        $phone       = $row['PHONE'];
        $email       = $row['EMAIL'];
        $id_users    = $row['ID'];
        
        /*$SQLExec = "select ID from ENT_REGISTRATION where ID_ORGANIZATIONS = 1 and PHONE='$phone' LIMIT 1"; 
        $res = mysqli_query($link, $SQLExec);
        $s_row = mysqli_fetch_array($res);
        $id_ent_registration = $s_row['ID'];
        
        // Вставляємо в USERS
        $SQLExecInsert = "INSERT INTO USERS (PHONE, PASSWD_HASH, EMAIL, IS_ENT, ID_ENT_REGISTRATION, RECOVERY_TOKEN, IS_CONFIRMED) VALUES (?, ?, ?, ?, ?, ?, 0)";
        $stmtInsert = mysqli_prepare($link, $SQLExecInsert);
        mysqli_stmt_bind_param($stmtInsert, "sssiis", $phone, $passwd_hash, $email, $is_ent, $id_ent_registration, $token);
        mysqli_stmt_execute($stmtInsert);

        $id_users = mysqli_insert_id($link);
        */
        //-----------------------------------------------------
        
        $SQLExec = "select ID, ID_REF_COUNTERAGENT from ENT_REGISTRATION where ID_ORGANIZATIONS = 1 and PHONE='$phone'"; 
        $stmt_rg = mysqli_prepare($link, $SQLExec);
        mysqli_stmt_execute($stmt_rg);
        $res_rg = mysqli_stmt_get_result($stmt_rg);

        while ($row_rg = mysqli_fetch_assoc($res_rg)) {
           $id_ref_counteragent = $row_rg['ID_REF_COUNTERAGENT'];
           $id_ent_registration = $row_rg['ID'];
           $id_ref_account      = 0;        
           // Вставляємо в ACCESS
           $SQLExecAccess = "INSERT INTO ACCESS (ID_USERS, ID_REF_COUNTERAGENT, ID_REF_ACCOUNT, ID_ORGANIZATIONS, ID_ENT_REGISTRATION) VALUES (?, ?, ?, ?, ?)";
           $stmtAccess = mysqli_prepare($link, $SQLExecAccess);
           mysqli_stmt_bind_param($stmtAccess, "iiiii", $id_users, $id_ref_counteragent, $id_ref_account, $IDOrganizations, $id_ent_registration);
           mysqli_stmt_execute($stmtAccess);
        }    
   }
?>