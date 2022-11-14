<?php
	include("func.php");
	
	// initial
	$status_code_succeed 	= "E1"; // 成功狀態代碼
	$status_code_failure 	= "E0"; // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	$array4json				= array();
	$link					= null;
	$Insurance_no 			= ""; // *
	$Remote_insurance_no 	= ""; // *
	$Person_id 				= ""; // *
	$Mobile_no 				= "";
	$json_Person_id 		= "";
	$Sales_id 				= "";
	$status_code 			= "";
	$Member_name			= "";
	$FCM_Token 				= "";
	$base64image			= "";
	$Role 					= "";
	$imageFileType 			= "";
	$order_status 			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
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
	
	//另外一組 for 身分證圖檔存檔 update section
	$front 			= isset($_POST['front']) 	 ? $_POST['front'] 		: ''; // update section 0: front, 1: back
	$base64imageID 	= isset($_POST['Pid_PicID']) ? $_POST['Pid_PicID']  : ''; // update section
	$front 			= trim(stripslashes($front)); // update section
	$front 			= check_special_char($front); // update section
	
	// 模擬資料
	if ($g_test_mode)
	{
		$App_type				= "1";		// 0:業務;1:客戶
		$token 		 			= "Ins1996";
		$Insurance_no 		 	= "Ins1996";
		$Remote_insurance_no 	= "appl2022";
		$Person_id 			 	= "A123456789";
		$systemCode 			= "A123456789";
		$userId 			 	= "A123456789";
		$applyCode 			 	= "A123456789";
		$applyDate		 		= "2022/11/03";
		$issueSiteId	 		= "2022/11/03";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"].$g_exit_symbol."\r\n"."gov exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}

	JTG_wh_log($Insurance_no, $Remote_insurance_no, "gov entry <-", $Person_id);
	
	//$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	//$App_type = "0";//業務員
	//$Person_id = "Y120446048";
	if($App_type == '0')
		$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
	if($App_type == '1')
		$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫
	//$Apply_no = "7300000022SN001";

	// 驗證 security token
	$token = isset($_POST['accessToken']) ? $_POST['accessToken'] : '';
	$ret = protect_api("JTG_Gov_ID", "gov exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	// start
	if ($Person_id 				!= '' &&
		$Insurance_no  			!= '' &&
		$Remote_insurance_no  	!= '' &&
		$systemCode 			!= '' &&
		$userId 				!= '' &&
		$applyCode 				!= '' &&
		$applyDate 				!= '' &&
		$issueSiteId 			!= '')
	{
		//check 帳號/密碼
		//$host = 'localhost';
		//$host = '10.67.70.153';	
		//$user = 'tglmember_user';
		//$passwd = 'tglmember210718';
		//$database = 'tglmemberdb';

		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			$data = result_connect_error ($link);
			if ($data["status"] == "false")
			{
				JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"].$g_exit_symbol."\r\n"."gov exit ->"."\r\n", $Person_id);
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				return;
			}
			mysqli_query($link,"SET NAMES 'utf8'");
				
			$Person_id  			= mysqli_real_escape_string($link, $Person_id);
			$App_type  				= mysqli_real_escape_string($link, $App_type);	
			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no);
			$Remote_insurance_no 	= mysqli_real_escape_string($link, $Remote_insurance_no);
			$systemCode  			= mysqli_real_escape_string($link, $systemCode);
			$userId  				= mysqli_real_escape_string($link, $userId);
			$applyCode  			= mysqli_real_escape_string($link, $applyCode);
			$applyDate  			= mysqli_real_escape_string($link, $applyDate);
			$issueSiteId  			= mysqli_real_escape_string($link, $issueSiteId);

			$Personid2 				= trim(stripslashes($Person_id));
			$App_type2 				= trim(stripslashes($App_type));
			$Insurance_no2 			= trim(stripslashes($Insurance_no));
			$Remote_insuance_no2 	= trim(stripslashes($Remote_insurance_no));
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
				if ($token2 != '')
				{
					//return;
					//LDI-016
					$url = $g_mpost_url. "ldi/check-idno";
					$input_data = array();
					//$data['appId']= $appId ;
					$input_data['systemCode']			= $systemCode2;
					$input_data['userId']				= $userId2;
					$input_data['personId']				= $Personid2;
					$input_data['applyCode']			= $applyCode2;
					$input_data['applyDate']			= $applyDate2;
					$input_data['issueSiteId']			= $issueSiteId2;
					$input_data['insurance_no']			= $Insurance_no2;
					$input_data['remote_insuance_no']	= $Remote_insuance_no2;	
					$jsondata = json_encode($input_data);
					//echo $jsondata;
					$out = CallAPI("POST", $url, $jsondata, $token2, false);
					
					$data = result_message("true", "0x0200", "發送成功", $out);
					$status_code = $status_code_succeed;
					if ($out == null || $out = "")
					{
						$data = result_message("false", "0x0201", "GOV 無回應!", "");
						$status_code = $status_code_failure;
					}
				}
				else
				{
					$data = result_message("false", "0x0204", "token fail", "");
					$status_code = $status_code_failure;
				}
			}
			else
			{
				$data = result_message("false", "0x0208", "SQL fail", "");
				$status_code = $status_code_failure;				
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "系統異常", "");
			$status_code = $status_code_failure;
			JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
        }
		finally
		{
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "finally procedure", $Person_id);
			try
			{
				if ($status_code != "")
				{
					$data_status = modify_order_state($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $status_code, false);
					if (count($data_status) > 0 && $data_status["status"] == "false")
						$data = $data_status;
				}
				$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
				
				if ($link != null)
				{
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$link = null;
				}
			}
			catch (Exception $e)
			{
				$data = result_message("false", "0x0207", "Exception error: disconnect!", "");
				JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
			}
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "finally complete - status:".$status_code, $Person_id);
		}
	}
	else
	{
		$data = result_message("false", "0x0202", "API parameter is required!", "");
		$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, true);
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"].$g_exit_symbol."\r\n"."gov exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>