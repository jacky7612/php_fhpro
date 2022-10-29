<?php
	include "comm.php";
	include("func.php");
	
	$token 		 		= isset($_POST['accessToken']) 			? $_POST['accessToken'] 		: '';		
	$App_type 	 		= isset($_POST['App_type']) 			? $_POST['App_type'] 			: '';
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$systemCode  		= isset($_POST['systemCode']) 			? $_POST['systemCode'] 			: '';
	$userId 	 		= isset($_POST['userId']) 				? $_POST['userId'] 				: '';
	$personId 	 		= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$applyCode   		= isset($_POST['applyCode']) 			? $_POST['applyCode'] 			: '';
	$applyDate 	 		= isset($_POST['applyDate']) 			? $_POST['applyDate'] 			: '';
	$issueSiteId 		= isset($_POST['issueSiteId']) 			? $_POST['issueSiteId'] 		: '';
	
	$token 		 		= check_special_char($token				);
	$App_type 	 		= check_special_char($App_type			);
	$Insurance_no 	 	= check_special_char($Insurance_no		);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$systemCode  		= check_special_char($systemCode		);
	$userId 	 		= check_special_char($userId			);
	$personId 	 		= check_special_char($personId			);
	$applyCode 	 		= check_special_char($applyCode			);
	$applyDate 	 		= check_special_char($applyDate			);
	$issueSiteId 		= check_special_char($issueSiteId		);

	$status_code_succeed = "E1"; // 成功狀態代碼
	$status_code_failure = "E0"; // 失敗狀態代碼
	$status_code = "";
	wh_log($Insurance_no, $Remote_insurance_no, "gov entry <-", $Person_id);
	
	//$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	//$App_type = "0";//業務員
	//$Person_id = "Y120446048";
	if($App_type == '0')
		$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
	if($App_type == '1')
		$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫
	//$Apply_no = "7300000022SN001";

	// 當資料不齊全時，從資料庫取得
	if (($Member_name 	== '') ||
		($Mobile_no 	== '') )
	{
		$memb 		 = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id);
		$Mobile_no 	 = $memb["Mobile_no"];
		$Member_name = $memb["Member_name"];
	}
	$Sales_Id = get_sales_id($Insurance_no, $Remote_insurance_no);
	
	if (($personId 				!= '' &&
		 $Insurance_no  		!= '' &&
		 $Remote_insuance_no  	!= '' &&
		 $systemCode 			!= '' &&
		 $userId 				!= '' &&
		 $applyCode 			!= '' &&
		 $applyDate 			!= '' &&
		 $issueSiteId 			!= ''))
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
				
			$personId  			= mysqli_real_escape_string($link, $personId);
			$App_type  			= mysqli_real_escape_string($link, $App_type);	
			$Insurance_no  		= mysqli_real_escape_string($link, $Insurance_no);
			$Remote_insuance_no = mysqli_real_escape_string($link, $Remote_insuance_no);
			$systemCode  		= mysqli_real_escape_string($link, $systemCode);
			$userId  			= mysqli_real_escape_string($link, $userId);
			$applyCode  		= mysqli_real_escape_string($link, $applyCode);
			$applyDate  		= mysqli_real_escape_string($link, $applyDate);
			$issueSiteId  		= mysqli_real_escape_string($link, $issueSiteId);

			$Personid2 				= trim(stripslashes($personId));
			$App_type2 				= trim(stripslashes($App_type));
			$Insurance_no2 			= trim(stripslashes($Insurance_no));
			$Remote_insuance_no2 	= trim(stripslashes($Remote_insuance_no));
			$systemCode2 			= trim(stripslashes($systemCode));
			$userId2 				= trim(stripslashes($userId));
			$applyCode2 			= trim(stripslashes($applyCode));
			$applyDate2 			= trim(stripslashes($applyDate));
			$issueSiteId2 			= trim(stripslashes($issueSiteId));

			$token2 = trim(stripslashes($token));

			//$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			//if ($personId != "") {	
				//$sql = $sql." and person_id='".$personId."'";
			//}

			if (1)//if ($result = mysqli_query($link, $sql))
			{
				$data = array();
				if ($token2 != '')
				{
					//exit;
					//LDI-016
					$url = $g_mpost_url. "ldi/check-idno";
					
					//$data['appId']= $appId ;					
					$data['systemCode']			= $systemCode2;
					$data['userId']				= $userId2;
					$data['personId']			= $Personid2;
					$data['applyCode']			= $applyCode2;
					$data['applyDate']			= $applyDate2;
					$data['issueSiteId']		= $issueSiteId2;
					$data['insurance_no']		= $Insurance_no2;
					$data['remote_insuance_no']	= $Remote_insuance_no2;	
					$jsondata = json_encode($data);
					//echo $jsondata;
					$out = CallAPI("POST", $url, $jsondata, $token2, false);
					echo $out;
					$status_code = $status_code_succeed;
					
					if ($status_code != "")
						$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, "", $status_code, $link);
	
					wh_log($Insurance_no, $Remote_insurance_no, "gov exit ->", $Person_id);
					exit;
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0204";
					$data["responseMessage"]= "token fail";
					$status_code = $status_code_failure;
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";	
			$status_code = $status_code_failure;				
			}
			mysqli_close($link);
		}
		catch (Exception $e)
		{
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "系統異常";
			$status_code = $status_code_failure;				
        }
		finally
		{
			try
			{
				if ($link != null)
				{
					if ($status_code != "")
						$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, "", $status_code, $link);
	
					mysqli_close($link);
					$link = null;
				}
			}
			catch(Exception $e)
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0202";
				$data["responseMessage"]= "Exception error: disconnect!";
			}
		}
	}
	else
	{
		//echo "need mail and password!";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."gov exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>