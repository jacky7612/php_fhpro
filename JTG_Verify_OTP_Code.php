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
	$order_status			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	common_post_param($token, $Insurance_no, $Remote_insurance_no, $Person_id);
	$Verification_Code 	= isset($_POST['Verification_Code']) 	? $_POST['Verification_Code'] 	: ''; //Verification_Code
	$OTP_time 			= isset($_POST['OTP_time']) 			? $_POST['OTP_time'] 			: '';
	$Verification_Code 	= check_special_char($Verification_Code);
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
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["responseMessage"]."\r\n".$g_exit_symbol."verify otp exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "verify otp entry <-", $Person_id);
	
	// 驗證 security token
	$ret = protect_api("JTG_Verify_OTP_Code", "verify otp exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
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
			$data = result_connect_error ($link);
			if ($data["status"] == "false")
			{
				JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["responseMessage"]."\r\n".$g_exit_symbol."verify otp exit ->"."\r\n", $Person_id);
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				return;
			}
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

			$sql = "SELECT * FROM orderinfo where insurance_no='$Insuranceno' and remote_insurance_no='$Remote_insurance_no' and sales_id='$Salesid' and person_id='$Personid' and mobile_no='$Mobileno'  and order_trash=0";
			
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
						$data = result_message("true", "0x0200", "驗證碼正確!", "");
						$sql2 = "update `orderinfo` set `verification_code`='' where insurance_no='$Insuranceno' and remote_insurance_no='$Remote_insuance_no' and sales_id='$Salesid' and person_id='$Personid' and mobile_no='$Mobileno' and order_trash=0";
						//and member_type=$Member_type 
						mysqli_query($link,$sql2) or die(mysqli_error($link));
						$status_code = $status_code_succeed;
						
					}
					else
					{
						$data = result_message("false", "0x0201", "驗證碼錯誤!", "");
						$status_code = $status_code_failure;
					}
				}
				else
				{
					$data = result_message("false", "0x0204", "資料不存在！", "");
					$status_code = $status_code_failure;
				}
			}
			else
			{
				$data = result_message("false", "0x0208", "SQL fail!", "");
				$status_code = $status_code_failure;					
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "Exception error!", "");
			JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
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
					mysqli_close($link);
					$link = null;
				}
			}
			catch (Exception $e)
			{
				$data = result_message("false", "0x0207", "Exception error: disconnect!", "");
				JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
			}
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "finally complete - status:".$status_code, $Person_id);
		}
	}
	else
	{
		$data = result_message("false", "0x0202", "API parameter is required!", "");
		$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, true);
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["responseMessage"]."\r\n".$g_exit_symbol."verify otp exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>