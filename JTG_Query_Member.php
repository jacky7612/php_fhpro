<?php
	include("func.php");
	
	// initial
	$status_code_succeed 	= "C1"; // 成功狀態代碼
	$status_code_failure 	= "C0"; // 失敗狀態代碼
	$data 					= array();
	$data_create			= array();
	$data_status			= array();
	$array4json				= array();
	// $link					= null;
	$Insurance_no 			= ""; // *
	$Remote_insurance_no 	= ""; // *
	$Person_id 				= ""; // *
	$Member_name			= "";
	$Mobile_no 				= "";
	$json_Person_id 		= "";
	$Sales_id 				= "";
	$status_code 			= "";
	$Role 					= "";
	$Image_pid_pic 			= "";
	$FCM_Token				= "";
	$DoCreateMember_Flag 	= "false";
	$order_status			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	api_get_post_param($token, $Insurance_no, $Remote_insurance_no, $Person_id);
	$FCM_Token 				= isset($_POST['FCM_Token']) 			? $_POST['FCM_Token'] 			: ''	 ;
	$DoCreateMember_Flag	= isset($_POST['CreateFlag']) 			? $_POST['CreateFlag'] 			: 'false';
	$DoCreateMember_Flag 	= check_special_char($DoCreateMember_Flag);
	$DoCreateMember_Flag 	= strtolower($DoCreateMember_Flag);
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "E123456789";
		$DoCreateMember_Flag = "true";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."query member exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "query member entry <-", $Person_id);
	
	// 驗證 security token
	$ret = protect_api("JTG_Query_Member", "query member exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	// start
	if ($Insurance_no 			!= '' &&
		$Remote_insurance_no 	!= '' &&
		$Person_id 				!= '')
	{
		try
		{
			$data = create_connect($link, $Insurance_no, $Remote_insurance_no, $Person_id);
			if ($data["status"] == "false") return;
			
			$Person_id  = mysqli_real_escape_string($link, $Person_id);
			$Person_id = trim(stripslashes($Person_id));

			$sql = "SELECT * FROM memberinfo where member_trash=0 and insurance_no= '".$Insurance_no."' and remote_insurance_no= '".$Remote_insurance_no."'";
			$sql = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);
			
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "query memberinfo table prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					// 正式取得SSO Token
					$SSO_token = generate_SSO_token($Insurance_no, $Remote_insurance_no, $Person_id);
					
					// login ok
					// user id 取得
					$mid = 0;
					while ($row = mysqli_fetch_array($result))
					{
						$mid = $row['mid'];
						$Role = $row['role'];
					}
					$data = result_message("true", "0x0200", "查詢身份證資料成功!", $SSO_token);
					$status_code = $status_code_succeed;
				}
				else
				{
					$json_Person_id = $Person_id;
					// 當資料不齊全時，從資料庫取得
					$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $json_Person_id, $Role, $Sales_id, $Mobile_no, $Member_name, false);
					if (!$ret_code)
					{
						$data = result_message("false", "0x0206", "get json - map person data failure", "");
						$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
						$data["order_status"] = $order_status;
						
						header('Content-Type: application/json');
						echo (json_encode($data, JSON_UNESCAPED_UNICODE));
						return;
					}
					else
					{
						if ($Person_id == $json_Person_id)
						{
							$data = result_message("true", "0x0200", "查詢身份證資料成功!", "");
							$status_code = $status_code_succeed;
						}
						else
						{
							$data = result_message("false", "0x0201", "查無身份證資料，請重新輸入正確的身份證字號，才可進行下一步操作!", "");
							$status_code = $status_code_failure;
						}
					}	
				}
			}
			else
			{
				$data = result_message("false", "0x0208", "SQL fail!", "");
			}
			JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])."query memberinfo table result :".$data["responseMessage"].$sql, $Person_id);
			
			// 儲存資料至資料庫
			if ($status_code == $status_code_succeed && $DoCreateMember_Flag == "true")
			{
				$data_create = modify_member($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Member_name, $Mobile_no, $FCM_Token, $Image_pid_pic, $status_code, false);
				if ($data["status"] 	   == "true" &&
					$data_create["status"] == "false")
				{
					$data["responseMessage"] .= $data_create["responseMessage"];
				}
			}
			
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "query memberinfo sop finish :".$data["responseMessage"], $Person_id);
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
		JTG_wh_log($Insurance_no, $Remote_insurance_no, "(!)".$data["responseMessage"], $Person_id);
		$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, true);
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."query member exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>