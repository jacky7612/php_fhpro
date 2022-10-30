<?php
	include("func.php"); 
	
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Sales_id 			= isset($_POST['Sales_id']) 			? $_POST['Sales_id'] 			: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Mobile_no 			= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			: '';
	
	$Insurance_no 		= check_special_char($Insurance_no		);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Sales_id 			= check_special_char($Sales_id			);
	$Person_id 			= check_special_char($Person_id			);
	$Mobile_no 			= check_special_char($Mobile_no			);

	$OTP_time 			= isset($_POST['OTP_time']) 			? $_POST['OTP_time'] 		: '';
	$OTP_time 			= check_special_char($OTP_time);
	
	// 驗證 security token
	$headers = apache_request_headers();
	$token 	 = $headers['Authorization'];
	if(check_header($key, $token) == true)
	{
		wh_log($Insurance_no, $Remote_insurance_no, "security token succeed", $Person_id);
	}
	else
	{
		;//echo "error token";
		$data = array();
		$data["status"]			= "false";
		$data["code"]			= "0x0209";
		$data["responseMessage"]= "Invalid token!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(X) security token failure", $Person_id);
		exit;							
	}
	
	$status_code_succeed = "G1"; // 成功狀態代碼
	$status_code_failure = "G0"; // 失敗狀態代碼
	switch(OTP_time)
	{
		case 2:
			$status_code_succeed = "J1"; // 成功狀態代碼
			$status_code_failure = "J0"; // 失敗狀態代碼
			break;
		case 3:
			$status_code_succeed = "O1"; // 成功狀態代碼
			$status_code_failure = "O0"; // 失敗狀態代碼
			break;
	}
	$status_code = "";
	wh_log($Insurance_no, $Remote_insurance_no, "face compare entry <-", $Person_id);
	
	// 當資料不齊全時，從資料庫取得
	if (($Member_name 	== '') ||
		($Mobile_no 	== '') )
	{
		$memb 		 = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id);
		$Mobile_no 	 = $memb["Mobile_no"];
		$Member_name = $memb["Member_name"];
	}
	$Sales_Id = get_sales_id($Insurance_no, $Remote_insurance_no);
	if (($Insurance_no 	!= '') &&
		($Sales_id 		!= '') &&
		($Person_id 	!= '') &&
		($Mobile_no 	!= '') )
	{
		$link = null;
		try 
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  		= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insuance_no	= mysqli_real_escape_string($link, $Remote_insuance_no	);
			$Sales_id  			= mysqli_real_escape_string($link, $Sales_id			);
			$Person_id  		= mysqli_real_escape_string($link, $Person_id			);
			$Mobile_no  		= mysqli_real_escape_string($link, $Mobile_no			);

			$Insuranceno 		= trim(stripslashes($Insurance_no)		);
			$Remoteinsuanceno 	= trim(stripslashes($Remote_insuance_no));
			$Salesid 			= trim(stripslashes($Sales_id)			);
			$Personid 			= trim(stripslashes($Person_id)			);
			$Mobileno 			= trim(stripslashes($Mobile_no)			);

			//$Personid = encrypt($key,$Personid);
			$Mobileno 			= addslashes(encrypt($key,$Mobileno));
	
			$sql = "SELECT * FROM orderinfo where insurance_no='$Insuranceno' and remote_insuance_no='$Remoteinsuanceno' and sales_id='$Salesid' and person_id='$Personid' and mobile_no='$Mobileno' and order_trash=0";
			
			wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$mid = 0;
					try
					{
						// 呼叫 API
						$user_code = get_random_keys(6);
						$smsdata = "第一金遠距行動投保APP[一次性驗證碼簡訊],你的驗證碼為:".$user_code;

						$uriBase2 = ＄g_OTP_apiurl;
						$fields2 = [
							'phone_no'         => $Mobileno,
							'sms_data'         => $smsdata
						];
						$fields_string2 = http_build_query($fields2);	
						$ch2 = curl_init();
						curl_setopt($ch2,CURLOPT_URL, $uriBase2);
						curl_setopt($ch2,CURLOPT_POST, true);
						curl_setopt($ch2,CURLOPT_POSTFIELDS, $fields_string2);
						curl_setopt($ch2,CURLOPT_RETURNTRANSFER, true); 
						//execute post
						$result2 = curl_exec($ch2);		
		
						// 更新 orderinfo table
						$sql2 = "update `orderinfo` set `verification_code`='$user_code' ,`updatedttime`=NOW() where order_no='$Insuranceno' and remote_insuance_no='$Remoteinsuanceno' and sales_id='$Salesid' and person_id='$Personid' and mobile_no='$Mobileno'  and order_trash=0";
						mysqli_query($link,$sql2) or die(mysqli_error($link));
						
						$data["status"]			= "true";
						$data["code"]			= "0x0200";
						$data["responseMessage"]= "簡訊發送完成!";
						$status_code = $status_code_succeed;
					}
					catch (Exception $e)
					{
						//$this->_response(null, 401, $e->getMessage());
						//echo $e->getMessage();
						$data["status"]			= "false";
						$data["code"]			= "0x0201";
						$data["responseMessage"]= "簡訊發送未完成!";
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
			mysqli_close($link);
		}
		catch (Exception $e)
		{
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="Exception error!";					
        }
		finally
		{
			try
			{
				if ($link != null)
				{
					$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
					if ($data["code"] == "0x0200") $symbol_str = "";
					wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."face compare exit ->", $Person_id);
					
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
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
	}
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>