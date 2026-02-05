<?php
	header('Content-Type: application/json');

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // якщо чужак 
	    echo json_encode([
	        'success' => false,
	        'message' => 'error method'
	    ]);
	    exit;
	}
    
   	session_start();
   
   	if (!isset($_SESSION['id_ent_registration'])) {
		echo json_encode([
	        'success' => false,
	        'code'    => 1,
	        'message' => 'Invalid value id_ent_registration'
	    ]);
	    exit;
	}
   
   	$email     = (isset($_POST['email']) ? $_POST['email'] : 0);
   	$password  = (isset($_POST['password']) ? $_POST['password'] : '');	

   	if (strlen($email)  > 90) { $email  = substr($email,0,90); }
   	if (strlen($password) > 30) { $passwd = substr($password,0,30); }
   
   	//====================================
	include ('../config.php');
  
	$link = mysqli_connect($dbhostname, $dbusername, $dbpassword, $dbName);
	mysqli_set_charset($link, 'utf8');
   
	$SQLExec = "select ID from USERS where EMAIL='$email' and IS_ENT=1"; 
	$res = mysqli_query($link, $SQLExec);
    
	$exists = mysqli_num_rows($res) > 0;  
	
	if ($exists or $email == '') {
		echo json_encode([
	        'success' => false,
	        'code'    => 2,
	        'message' => 'Invalid email'
	    ]);
	    exit;
	}
	
	//=== 
	$phone               = $_SESSION['phone'];
	$id_ent_registration = $_SESSION['id_ent_registration'];
	$id_ref_counteragent = $_SESSION['id_ref_counteragent'];
	$passwd_hash         = password_hash($password, PASSWORD_DEFAULT);
        $is_ent              = 1;
	$id_ref_account      = 0;
	
	$SQLExec = "INSERT INTO USERS (PHONE, PASSWD_HASH, EMAIL, IS_ENT, ID_ENT_REGISTRATION) VALUES (?, ?, ?, ?, ?)";
	$stmt = mysqli_prepare( $link, $SQLExec);
	
    mysqli_stmt_bind_param($stmt, "sssii", $phone, $passwd_hash, $email, $is_ent, $id_ent_registration);
    mysqli_stmt_execute($stmt);

    $id_users = mysqli_insert_id($link);

    $SQLExec = "INSERT INTO ACCESS (ID_USERS, ID_REF_COUNTERAGENT, ID_REF_ACCOUNT, ID_ORGANIZATIONS, ID_ENT_REGISTRATION) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare( $link, $SQLExec);
	
    mysqli_stmt_bind_param($stmt, "iiiii", $id_users, $id_ref_counteragent, $id_ref_account, $IDOrganizations, $id_ent_registration);
    mysqli_stmt_execute($stmt);
	
    $_SESSION['id_users'] = $id_users;
    $_SESSION['is_ent']   = $is_ent;
	
	echo json_encode([
		'success' => true,
		'redirect' => '/cabinet_ent'
	]);	
?>