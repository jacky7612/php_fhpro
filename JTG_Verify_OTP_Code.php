<?php
	include("func.php");
	
	// initial
	$status_code_succeed 	= "H3"; // 成功狀態代碼
	$status_code_failure 	= "H2"; // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	$link					= null;
	$Insurance_no 			= ""; // *
	$Remote_insurance_no 	= ""; // *
	$Person_id 				= ""; // *
	$Mobile_no 				= "";
	$json_Person_id 		= "";
	$Sales_id 				= "";
	$status_code 			= "";
	$Member_name			= "";
	$base64image			= "";
	$Role 					= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Sales_id 			= isset($_POST['Sales_id']) 			? $_POST['Sales_id'] 			: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Mobile_no 			= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			: '';
	$Verification_Code 	= isset($_POST['Verification_Code']) 	? $_POST['Verification_Code'] 	: '1'; //Verification_Code
	
	$Insurance_no 		= check_special_char($Insurance_no);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Sales_id 			= check_special_char($Sales_id);
	$Person_id 			= check_special_char($Person_id);
	$Mobile_no 			= check_special_char($Mobile_no);
	$Verification_Code 	= check_special_char($Verification_Code);
	
	$OTP_time 			= isset($_POST['OTP_time']) 			? $_POST['OTP_time'] 		: '';
	$OTP_time 			= check_special_char($OTP_time);

	switch($OTP_time)
	{
		case "2":
			$status_code_succeed = "J3"; // 成功狀態代碼
			$status_code_failure = "J2"; // 失敗狀態代碼
			break;
		case "3":
			$status_code_succeed = "O3"; // 成功狀態代碼
			$status_code_failure = "O2"; // 失敗狀態代碼
			break;
	}
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Verification_Code 	 = "681134";
		$OTP_time 			 = "1";
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	wh_log($Insurance_no, $Remote_insurance_no, "verify otp entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Send_OTP_Code", "send otp exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	// start
	if ($OTP_time 				!= '' &&
		$Insurance_no 			!= '' &&
		$Remote_insurance_no 	!= '' &&
		$Sales_id 				!= '' &&
		$Person_id 				!= '' &&
		$Mobile_no 				!= '' )
	{

		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no	);
			$Sales_id  				= mysqli_real_escape_string($link, $Sales_id			);
			$Person_id  			= mysqli_real_escape_string($link, $Person_id			);
			$Mobile_no  			= mysqli_real_escape_string($link, $Mobile_no			);
			//$Member_type  		= mysqli_real_escape_string($link, $Member_type			);
			$Verification_Code  	= mysqli_real_escape_string($link, $Verification_Code	);

			$Insuranceno 		 = trim(stripslashes($Insurance_no)			);
			$Remote_insurance_no = trim(stripslashes($Remote_insurance_no)	);
			$Salesid 			 = trim(stripslashes($Sales_id)				);
			$Personid 			 = trim(stripslashes($Person_id)			);
			$Mobileno 			 = trim(stripslashes($Mobile_no)			);
			$VerificationCode 	 = trim(stripslashes($Verification_Code)	);

			//$Personid = encrypt($key,($Personid));
			//$Mobileno = addslashes(encrypt($key, ($Mobileno
			
			$sql = "SELECT * FROM orderinfo where insurance_no='$Insuranceno' and remote_insurance_no='$Remote_insurance_no' and sales_id='$Salesid' and person_id='$Personid' and mobile_no='$Mobileno'  and order_trash=0";
			//and member_type=$Member_type
			//if ($Insurance_no != "") {
			//	$sql = $sql." and order_no='".$Insurance_no."'";
			//}

			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$mid=0;
					while($row = mysqli_fetch_array($result))
					{
						$rid = $row['rid'];
						$code = $row['verification_code'];
					}	
					$code = str_replace(",", "", $code);	
					
					if ($VerificationCode == $code) {
						$data["status"]="true";
						$data["code"]="0x0200";
						$data["responseMessage"]="驗證碼正確!";	

						$sql2 = "update `orderinfo` set `verification_code`='' where insurance_no='$Insuranceno' and remote_insurance_no='$Remote_insuance_no' and sales_id='$Salesid' and person_id='$Personid' and mobile_no='$Mobileno' and order_trash=0";
						//and member_type=$Member_type 
						mysqli_query($link,$sql2) or die(mysqli_error($link));
						$status_code = $status_code_succeed;
						
					}
					else
					{
						$data["status"]			= "false";
						$data["code"]			= "0x0201";
						$data["responseMessage"]= "驗證碼錯誤!";
						$status_code = $status_code_failure;
					}
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0205";
					$data["responseMessage"]= "流水要保序號錯誤!";
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
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";					
        }
		finally
		{
			wh_log($Insurance_no, $Remote_insurance_no, "finally procedure", $Person_id);
			try
			{
				if ($status_code != "")
					$data_status = modify_order_state($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $status_code, false);
				if (count($data_status) > 0 && $data_status["status"] == "false")
					$data = $data_status;
				
				if ($link != null)
				{
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
			wh_log($Insurance_no, $Remote_insurance_no, "finally complete - status:".$status_code, $Person_id);
		}
	}
	else
	{
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n".$g_exit_symbol."verify otp exit ->"."\r\n", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>