<?php
	header('Content-Type: application/json');

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
	    echo json_encode([
	        'exists' => false,
	        'message' => 'error method'
	    ]);
	    exit;
	}
	$email = trim((isset($_POST['email']) ? $_POST['email'] : ''));
	if (strlen($email)  > 90) { $login  = substr($email,0,90); }
 
	session_start();
	
	$is_ent = -1;
	
	if (isset($_SESSION['id_ent_registration'])) {
            $is_ent = 1;
	}
			
	//Якщо не визначили тип користувача
	if ($is_ent < 0) {
		echo json_encode([
  			'exists' => false,
  			'message' => 'error exucute'
		]);
		exit;
	}
	
	//======================================================
	include ('../config.php');
  
	$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
	mysqli_set_charset($link, 'utf8');
   
	$SQLExec = "select ID from USERS where EMAIL='$email' and IS_ENT=$is_ent"; 
	$res = mysqli_query($link, $SQLExec);
    
	$exists = mysqli_num_rows($res) > 0;
	
    echo json_encode([
  		'exists' => $exists
	]);
 
?>