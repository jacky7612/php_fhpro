<?php
	include("func.php");
	global $g_PolicyNo_enable, $g_PolicyNo_apiurl;
	
	// initial
	$status_code_succeed 	= "V1"; // 成功狀態代碼
	$status_code_failure 	= "V0"; // 失敗狀態代碼
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
	$base64image			= "";
	$Role 					= "";
	$Policy_number			= "";
	$order_status			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	api_get_post_param($token, $Insurance_no, $Remote_insurance_no, $Person_id);
		
	// 模擬資料
	if ($g_test_mode)
	{
		$PolicyNo_time 			 = "1";
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get policy no exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "get policy no entry <-", $Person_id);
	
	// 驗證 security token
	$ret = protect_api("JTG_Send_PolicyNo", "get policy no exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
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
		$PolicyNo_time 			!= '')
	{
		try 
		{
			$data = create_connect($link, $Insurance_no, $Remote_insurance_no, $Person_id);
			if ($data["status"] == "false") return;

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
						/*
						$user_code = get_random_keys(6);
						$smsdata = "第一金遠距行動投保APP[一次性驗證碼簡訊],你的驗證碼為:".$user_code;
						// 模擬資料
						if ($g_test_mode) $Mobileno = "0928512773";
						if ($g_PolicyNo_enable)
						{
							$uriBase2 = $g_PolicyNo_apiurl;
							$fields2 = [
								$g_PolicyNo_UID_key			=> $g_PolicyNo_UID_value,
								$g_PolicyNo_PWD_key			=> $g_PolicyNo_PWD_value,
								$g_PolicyNo_subject_key		=> $g_PolicyNo_subject_value,
								$g_PolicyNo_message_key		=> $g_PolicyNo_message_value.$user_code,
								$g_PolicyNo_mobile_key		=> $Mobileno
							];
							$fields_string2 = http_build_query($fields2);	
							$ch2 = curl_init();
							curl_setopt($ch2,CURLOPT_URL, $uriBase2);
							curl_setopt($ch2,CURLOPT_POST, true);
							curl_setopt($ch2,CURLOPT_POSTFIELDS, $fields_string2);
							curl_setopt($ch2,CURLOPT_RETURNTRANSFER, true); 
							//execute post
							$result2 = curl_exec($ch2);
							JTG_wh_log($Insurance_no, $Remote_insurance_no, "sms result :".$result2, $Person_id);
							//1603.00,1,1,0,09c04df2-bb7b-4448-99eb-474660ec2af0
						}
						*/
						$result2 = ""; // testing
						$ret_json = json_decode($result2);
						
						$ret_error_msg = "";
						try
						{
							$ret_array = explode(",",$result2);
							if (count($ret_array) == 2)
							{
								if ($ret_json->Status)
									$ret_error_msg = $ret_json->Msg;
							}
						}
						catch (Exception $e)
						{
							$ret_error_msg = "";
						}
						
						if ($ret_error_msg == "")
						{
							// 更新 orderinfo table
							$sql2 = "update `orderinfo` set `policy_number`='$Policy_number' ,`updatedttime`=NOW() where insurance_no='$Insuranceno' and remote_insurance_no='$Remoteinsuanceno' and order_trash=0";
							mysqli_query($link,$sql2) or die(mysqli_error($link));
							$data = result_message("true", "0x0200", "取得保單號成功!", "");
							$status_code = $status_code_succeed;
						}
						else
						{
							$data = result_message("false", "0x0201", "取得保單號異常 :".$ret_error_msg, "");
							$status_code = $status_code_failure;
						}
					}
					catch (Exception $e)
					{
						$data = result_message("false", "0x0209", "取得保單號未完成!", "");
						$status_code = $status_code_failure;
						JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
					}
				}
				else
				{
					$data = result_message("true", "0x0206", "取得保單號錯誤!", "");
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
			$data_close_conn = close_connection_finally($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $status_code);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
	}
	else
	{
		$data = result_message("false", "0x0202", "API parameter is required!", "");
		$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, true);
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get policy no exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>