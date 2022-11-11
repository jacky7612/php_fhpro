<?php
	include("func.php");
	global $g_Policy_enable, $g_Policy_apiurl;
	
	// initial
	$status_code_succeed 	= "Y1"; // 成功狀態代碼
	$status_code_failure 	= "Y0"; // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	$applicationData		= array();
	$uploadData				= array();
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
	$Policy_no 				= "";
	$order_status			= "";
	
	$cxPolicy = new CXpolicy();
	$cxPolicy->init();
	
	// Api ------------------------------------------------------------------------------------------------------------------------
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

	$Policy_time 			= isset($_POST['Policy_time']) 			? $_POST['Policy_time'] 		: '';
	$Policy_time 			= check_special_char($Policy_time);
	
	switch($Policy_time)
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
		$Policy_time 			 = "1";
		$Policy_no 				 = "Ins1996";
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "send otp entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Send_Policy", "send otp exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
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
		$Policy_time 				!= '')
	{
		try 
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
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
						// 從pdflog table取得pdf資料
						$ret_pdflog = get_pdflog_table_info($link, $Insurance_no, $Remote_insurance_no, $Person_id, "", false, true);
						if ($ret_pdflog["status"] == "true")
						{
							$pdflog_info  = json_decode($ret_pdflog["json"]);
							for ($i = 0; $i < count($pdflog_info); $i++)
							{
								$pdflog_name 	= $pdflog_info[$i]->pdf_name;
								$pdflog_content = $pdflog_info[$i]->pdf_data;
								$cxPolicy->applicationData[$i]["attacheCode"] 	= $i;
								$cxPolicy->applicationData[$i]["attacheName"] 	= $pdflog_name;
								$cxPolicy->applicationData[$i]["attacheContent"]= $pdflog_content;
								$cxPolicy->applicationData[$i]["agentFlag"] 	= "Y";
							}
						}
						
						$ret_attachment = get_attachment_table_info($link, $Insurance_no, $Remote_insurance_no, "", false, true);
						if ($ret_attachment["status"] == "true")
						{
							$attachment_info  = json_decode($ret_attachment["json"]);
							for ($i = 0; $i < count($attachment_info); $i++)
							{
								$name 	= $attachment_info[$i]->attache_title;
								$content = $attachment_info[$i]->attach_graph;
								$cxPolicy->uploadData[$i]["attacheCode"] 		= $i;
								$cxPolicy->uploadData[$i]["attacheName"] 		= $name;
								$cxPolicy->uploadData[$i]["attacheContent"] 	= $content;
							}
						}
						
						$cxPolicy->acceptId 		= $Insurance_no; // 行動投保序號
						$cxPolicy->policyNo 		= $Policy_no; // 保單號碼
						$json_data = json_encode($cxPolicy);
						//echo $json_data."\r\n\r\n";
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
						$result2 = "";
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
							$data = result_message("false", "0x0209", "parse json Exception error!", "");
							$ret_error_msg = "";
							JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
						}
						
						if ($ret_error_msg == "")
						{
							// 更新 orderinfo table
							$sql2 = "update `orderinfo` set `policy_number`='$Policy_no' ,`updatedttime`=NOW() where insurance_no='$Insuranceno' and remote_insurance_no='$Remoteinsuanceno' and order_trash=0";
							mysqli_query($link,$sql2) or die(mysqli_error($link));
							$data = result_message("true", "0x0200", "回傳保單資訊至雲端達人成功!", "");
							$status_code = $status_code_succeed;
						}
						else
						{
							$data = result_message("false", "0x0201", "回傳保單資訊至雲端達人異常 :".$ret_error_msg, "");
							$status_code = $status_code_failure;
						}
					}
					catch (Exception $e)
					{
						$data = result_message("false", "0x0209", "回傳保單資訊至雲端達人未完成!", "");
						$status_code = $status_code_failure;
						JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
					}
				}
				else
				{
					$data = result_message("false", "0x0204", "回傳保單資訊至雲端達人錯誤, 無相應資料!", "");
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
				
				if (count($data_status) > 0 && $data_status["status"] == "false")
					$data = $data_status;
				
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
	$data["order_status"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>