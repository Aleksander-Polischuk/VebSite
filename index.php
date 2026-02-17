<?php
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	
	$account_type = filter_input(INPUT_GET, 'account_type', FILTER_VALIDATE_INT) ?? -1;
	if ($account_type != 0 and $account_type != 1) {
		$account_type = -1;
	}  

	session_start();
	
       /* Блок перевірки  */ 
        
        
        
       /*      ====       */
        
	if ($path == '/login') {
		include "login.php";	
	} 
	
        elseif ($path == '/logout') {
            
	$param = ($account_type >= 0 ? '?account_type='.$account_type : '');   		
	  header('Location: /login'.$param);	
	}
        
	elseif ($path == '/registration_ent') {
		include "registration_ent.php";
	}
        
        elseif ($path == '/forgotpassword') {
            include "forgot_password.php";
        }
        
        elseif ($path == '/recovery') {
            include "recovery.php";
        }
        
        elseif ($path == '/confirm') {
            include "confirm.php";
        }
	
	elseif ($path == '/cabinet') {
		echo "cabinet";
	}
    
        elseif ($path == '/cabinet_ent') {
                    include "cabinet_ent.php";
            }
	
	else {
	  $param = ($account_type >= 0 ? '?account_type='.$account_type : '');   		
	  header('Location: /login'.$param);
	}
?>
