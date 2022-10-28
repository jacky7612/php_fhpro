<?php
	include "comm.php";
	include "db_tools.php";
	include "func.php";	

	// Api ------------------------------------------------------------------------------------------------------------------------
	$token 				= isset($_POST['accessToken']) 			? $_POST['accessToken'] 		: '';			
	$App_type 			= isset($_POST['App_type']) 			? $_POST['App_type'] 			: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$mobilePhone 		= isset($_POST['mobilePhone']) 			? $_POST['mobilePhone'] 		: '';
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: ''; // for update status
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: ''; // for update status


	//$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	//$App_type = "0";//業務員
	//$Person_id = "Y120446048";
	if($App_type == '0')
		$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
	if($App_type == '1')
		$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫
	//$Apply_no = "7300000022SN001";
	if (($Person_id 			!= '' &&
		 $Insurance_no 			!= '' &&
		 $Remote_insuance_no 	!= ''))
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
				
			$Person_id  			= mysqli_real_escape_string($link, $Person_id			);
			$App_type  				= mysqli_real_escape_string($link, $App_type			);
			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insuance_no  	= mysqli_real_escape_string($link, $Remote_insuance_no	);
			$mobilePhone  			= mysqli_real_escape_string($link, $mobilePhone			);
			$token  				= mysqli_real_escape_string($link, $token				);
			
			$Person_id2 			= trim(stripslashes($Person_id)			);
			$App_type2 				= trim(stripslashes($App_type)			);
			$Insurance_no2 			= trim(stripslashes($Insurance_no)		);
			$Remote_insuance_no2 	= trim(stripslashes($Remote_insuance_no));
			$mobilePhone2 			= trim(stripslashes($mobilePhone)		);
			$token2 				= trim(stripslashes($token)				);

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
					//LDI-014
					$url = $g_mpost_url. "ldi/otp/getOne";
					
					$data['appId']				= $appId ;
					$data['Insurance_no']		= $Insurance_no2 ;
					$data['Remote_insuance_no']	= $Remote_insuance_no2 ;
					$data['mobilePhone']		= $mobilePhone2 ;
					$data['idno']				= $Person_id2 ;
					$data['sendType']			= "0001" ;
					$jsondata 					= json_encode($data);
					//echo $jsondata;
					$out = CallAPI("POST", $url, $jsondata, $token2, false);
					echo $out;
					exit;
				}
				else
				{
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