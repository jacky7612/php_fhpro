<?php
	include "comm.php";
	include "db_tools.php";
	include("func.php");
	
	$headers =  apache_request_headers();
	$token = $headers['Authorization'];
	if(check_header($key, $token)==true)
	{
		;//echo "valid token";
		
	}
	else
	{
		;//echo "error token";
		$data = array();
		$data["status"]="false";
		$data["code"]="0x0209";
		$data["responseMessage"]="Invalid token!";	
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
		exit;							
	}
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$App_type 			= isset($_POST['App_type']) 			? $_POST['App_type'] 			: '';	
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_Insuance_no = isset($_POST['Remote_Insuance_no']) 	? $_POST['Remote_Insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$token 				= isset($_POST['accessToken']) 			? $_POST['accessToken'] 		: '';

	//$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	//$Sso_token = "u0K2w1L0roUR8p1k3UJgZtlRbR6DD9BZHyXkDNvCALSY4zea11svxYSjoYP4iURR";
	//$App_type = "0";//業務員
	//$Apply_no="7300000022SN001";
	if($App_type == '0')
		$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
	if($App_type == '1')
		$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫
	
	if (($Person_id 		 != '') &&
		($Insurance_no 		 != '') &&
		($Remote_Insuance_no != ''))
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
			
			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_Insuance_no  	= mysqli_real_escape_string($link, $Remote_Insuance_no	);
			$App_type  				= mysqli_real_escape_string($link, $App_type			);	
			$token  				= mysqli_real_escape_string($link, $token				);
			
			$Insurance_no2 			= trim(stripslashes($Insurance_no)		);
			$Remote_Insuance_no2 	= trim(stripslashes($Remote_Insuance_no));
			$App_type2 				= trim(stripslashes($App_type)			);
			$token2 				= trim(stripslashes($token)				);
							
			//$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			//if ($Person_id != "") {	
				////$sql = $sql." and person_id='".$Person_id."'";
			//}

			if (1) // if ($result = mysqli_query($link, $sql))
			{
					$data = array();
					if ($token2 != '')
					{
						//exit;
						//LDI-005
						//$url = $g_mpost_url. "ldi/proposal/pdf";
						//LDI-020
						$url = $g_mpost_url. "ldi/getPdf";
						$data['Insurance_no']		= $Insurance_no2;
						$data['Remote_Insuance_no']	= $Remote_Insuance_no2;
						$data['appId']				= $appId ;
					
						//$jsondata = json_encode($data);
						//$out = CallAPI("POST", $url, $jsondata, $token, true);
						
						$out = CallAPI("GET", $url, $data, $token2, true);
						//echo "PDF:".$out;
						//$data = array();
						//$data["status"]="true";
						//$data["code"]="0x0200";						
						//$data["pdf"]=$out;
						//header('Content-Type: application/json');
						//echo (json_encode($data, JSON_UNESCAPED_UNICODE));
						echo $out;
						exit;
					} else {
						$data["status"]="false";
						$data["code"]="0x0204";
						$data["responseMessage"]="token fail";	
						
					}
					
			} else {
				$data["status"]="false";
				$data["code"]="0x0204";
				$data["responseMessage"]="SQL fail!";					
			}
			mysqli_close($link);
		} catch (Exception $e) {
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="系統異常";					
        }
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	} else {
		//echo "need mail and password!";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	}
?>