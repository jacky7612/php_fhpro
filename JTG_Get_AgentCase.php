<?php
	include "comm.php";
	include "db_tools.php";
	include("security_tools.php");
	include("func.php");

	// Api ------------------------------------------------------------------------------------------------------------------------
	$App_type 			= isset($_POST['App_type']) 			? $_POST['App_type'] 			: '';
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: ''; // update order_start use
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: ''; // update order_start use
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$token 				= isset($_POST['accessToken']) 			? $_POST['accessToken'] 		: '';

	$token 				= check_special_char($token);
	$Insurance_no 		= check_special_char($Insurance_no);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$App_type 			= check_special_char($App_type);
	$Person_id 			= check_special_char($Person_id);

	//$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	//$App_type = "0";//業務員
	if($App_type == '0')
		$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
	if($App_type == '1')
		$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫
	
	if (($Person_id != ''))
	{
		
		//check 帳號/密碼
	
		//$host = 'localhost';
		//$host = '10.67.70.153';		
		//$user = 'tglmember_user';
		//$passwd = 'tglmember210718';
		//$database = 'tglmemberdb';
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");
				
			$Person_id  = mysqli_real_escape_string($link, $Person_id);
			$App_type  	= mysqli_real_escape_string($link, $App_type );	
			$token  	= mysqli_real_escape_string($link, $token	 );
			
			$Person_id2 = trim(stripslashes($Person_id)	);
			$App_type2 	= trim(stripslashes($App_type)	);
			$token2 	= trim(stripslashes($token)		);


			//$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			//if ($Person_id != "") {	
				//$sql = $sql." and person_id='".$Person_id."'";
			//}

			if (1) // if ($result = mysqli_query($link, $sql))
			{
					$data = array();
					if($token2 != '')
					{
						//exit;
						//LDI-003
						$url = $g_mpost_url. "ldi/agent-case";
						
						$data['appId']= $appId ;					
						$jsondata = json_encode($data);
						//echo $jsondata;
						$out = CallAPI("POST", $url, $jsondata, $token2, false);
						//echo "PDF:".$out;
						echo $out;
						exit;
					} else {
						$data["status"]			= "false";
						$data["code"]			= "0x0204";
						$data["responseMessage"]= "token fail";	
						
					}
				} else {
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "沒有此會員!";						
				}
			mysqli_close($link);
		} catch (Exception $e) {
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "系統異常";					
        }
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		
	}else{
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	}
?>