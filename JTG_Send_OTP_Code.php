<?php
	include("func.php");
	global $g_return_OTP_code_enable, $g_OTP_enable, $g_OTP_apiurl, $g_OTP_api_value;
	
	// initial
	$status_code_succeed 	= "H1"; // 成功狀態代碼
	$status_code_failure 	= "H0"; // 失敗狀態代碼
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
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	
	$Insurance_no 		= check_special_char($Insurance_no		);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Person_id 			= check_special_char($Person_id			);

	$OTP_time 			= isset($_POST['OTP_time']) 			? $_POST['OTP_time'] 		: '';
	$OTP_time 			= check_special_char($OTP_time);
	
	switch($OTP_time)
	{
		case "2":
			$status_code_succeed = "J1"; // 成功狀態代碼
			$status_code_failure = "J0"; // 失敗狀態代碼
			break;
		case "3":
			$status_code_succeed = "O1"; // 成功狀態代碼
			$status_code_failure = "O0"; // 失敗狀態代碼
			break;
	}
	
	// 模擬資料
	if ($g_test_mode)
	{
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
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."send otp exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "send otp entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Send_OTP_Code", "send otp exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	if ($Insurance_no 			!= '' &&
		$Remote_insurance_no 	!= '' &&
		$Sales_id 				!= '' &&
		$Person_id 				!= '' &&
		$Mobile_no 				!= '' &&
		$OTP_time 				!= '')
	{
		try 
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			$data = result_connect_error ($link);
			if ($data["status"] == "false")
			{
				JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."send otp exit ->"."\r\n", $Person_id);
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				return;
			}
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insurance_no	= mysqli_real_escape_string($link, $Remote_insurance_no	);
			$Sales_id  				= mysqli_real_escape_string($link, $Sales_id			);
			$Person_id  			= mysqli_real_escape_string($link, $Person_id			);
			$Mobile_no  			= mysqli_real_escape_string($link, $Mobile_no			);

			$Insuranceno 			= trim(stripslashes($Insurance_no)		);
			$Remoteinsuanceno 		= trim(stripslashes($Remote_insurance_no));
			$Salesid 				= trim(stripslashes($Sales_id)			);
			$Personid 				= trim(stripslashes($Person_id)			);
			$Mobileno 				= trim(stripslashes($Mobile_no)			);

			//$Mobileno 			= addslashes(encrypt($key,$Mobileno));
	
			$sql = "SELECT * FROM orderinfo where insurance_no='$Insuranceno' and remote_insurance_no='$Remoteinsuanceno' and sales_id='$Salesid' and person_id='$Personid' and order_trash=0";
			$sql = $sql.merge_sql_string_if_not_empty("mobile_no"	, $Mobileno);
			
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
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
						// 模擬資料
						if ($g_test_mode) $Mobileno = "0928512773";
						if ($g_OTP_enable)
						{
							$uriBase2 = $g_OTP_apiurl;
							$g_OTP_api_value["MSG"] .= $user_code;
							$g_OTP_api_value["DEST"] = $Mobileno;
							$fields2 = $g_OTP_api_value;
							$fields_string2 = http_build_query($fields2);	
							$ch2 = curl_init();
							curl_setopt($ch2,CURLOPT_URL, $uriBase2);
							curl_setopt($ch2,CURLOPT_POST, true);
							curl_setopt($ch2,CURLOPT_POSTFIELDS, $fields_string2);
							curl_setopt($ch2,CURLOPT_RETURNTRANSFER, true); 
							//execute post
							$result2 = curl_exec($ch2);
							JTG_wh_log($Insurance_no, $Remote_insurance_no, "sms result :".$result2, $Person_id);
						}
						else // return sample message
							$result2 = "1603.00,1,1,0,09c04df2-bb7b-4448-99eb-474660ec2af0";
						
						$ret_error_msg = "";
						if (strlen($result2) > 0)
						{
							$ret_array = array();
							try
							{
								$ret_array = explode(",", $result2);
							}
							catch (Exception $e)
							{
								$ret_error_msg = "回傳格式錯誤，未知的訊息";
							}
							if ($ret_array == null || count($ret_array) < 5)
							{
								$ret_error_msg = "回傳格式錯誤，未知的訊息";
							}
							
							// 舊程式 - start
							try
							{
								$ret_array = explode(",", $result2);
								if (count($ret_array) == 2)
								{
									//if ($ret_json->Status)
									//	$ret_error_msg = $ret_json->Msg;
								}
							}
							catch (Exception $e)
							{
								$ret_error_msg = "回傳格式錯誤，未知的訊息";
								$data = result_message("false", "0x0209", "parse json Exception error!", "");
								JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
							}
							$ret_json = json_decode($result2);
							// 舊程式 - end
							
							if (strlen($ret_error_msg) > 0)
							{
								$data = result_message("false", "0x0201", $ret_error_msg." :".$result2, "");
								JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"], $Person_id);
							}
						}
						
						if ($ret_error_msg == "")
						{
							// 更新 orderinfo table
							$sql2 = "update `orderinfo` set `verification_code`='$user_code' ,`updatedttime`=NOW() where insurance_no='$Insuranceno' and remote_insurance_no='$Remoteinsuanceno' and sales_id='$Salesid' and person_id='$Personid' and mobile_no='$Mobileno'  and order_trash=0";
							mysqli_query($link,$sql2) or die(mysqli_error($link));
							$data = result_message("true", "0x0200", "簡訊發送完成!", "");
							$status_code = $status_code_succeed;
						}
						else
						{
							$data = result_message("false", "0x0201", "簡訊發送異常-".$ret_error_msg, "");
							$status_code = $status_code_failure;
						}
					}
					catch (Exception $e)
					{
						$data = result_message("false", "0x0209", "簡訊發送未完成!", "");
						$status_code = $status_code_failure;
						JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
					}
				}
				else
				{
					$data = result_message("false", "0x0206", "讀取資料錯誤!", "");
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
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."send otp exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	if ($g_return_OTP_code_enable) $data["OTP_code"] = $user_code;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>