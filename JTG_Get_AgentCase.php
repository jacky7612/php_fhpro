<?php
	include "comm.php";
	include("func.php");

	// initial
	$status_code_succeed 	= "C1"; // 成功狀態代碼
	$status_code_failure 	= "C0"; // 失敗狀態代碼
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
	$order_status			= "";
	$appId 					= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: ''; // update order_start use
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] 	: ''; // update order_start use
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$token 					= isset($_POST['accessToken']) 			? $_POST['accessToken'] 		: '';

	$token 					= check_special_char($token);
	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id);

	// 模擬資料
	if ($g_test_mode)
	{
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
	
	//$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	//$App_type = "0";//業務員
	$appId = ($Role == 'agentOne') ? "Q3RRdLWTwYo8fVtP" : "HKgWyfYQv30ZE6AM";
	
	
	wh_log($Insurance_no, $Remote_insurance_no, "get agent case entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Get_AgentCase", "get agent case exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	// 模擬資料
	if ($g_test_mode)
	{
		$token 		 = "FCM_content";
	}

	// start
	if ($Insurance_no 			!= '' &&
		$Remote_insurance_no 	!= '' &&
		$Person_id 				!= '')
	{
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");
				
			$Person_id  = mysqli_real_escape_string($link, $Person_id);
			$token  	= mysqli_real_escape_string($link, $token	 );
			
			$Person_id2 = trim(stripslashes($Person_id)	);
			$token2 	= trim(stripslashes($token)		);
			
			if (1) // if ($result = mysqli_query($link, $sql))
			{
				if($token2 != '')
				{
					//return;
					//LDI-003
					$url = $g_mpost_url. "ldi/agent-case";
					
					$data_input['appId']= $appId ;					
					$jsondata 			= json_encode($data_input);
					$out 				= CallAPI("POST", $url, $jsondata, $token2, false);
					if (strlen($out) > 0)
						$data = result_message("true", "0x0200", "succeed", $out);
					else
						$data = result_message("false", "0x0201", "failure", $out);
				}
				else
				{
					$data = result_message("false", "0x0204", "token failure", "");
				}
			}
			else
			{
				$data = result_message("false", "0x0204", "沒有此會員!", "");
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "系統異常", "");
        }
		finally
		{
			wh_log($Insurance_no, $Remote_insurance_no, "finally procedure", $Person_id);
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
			catch(Exception $e)
			{
				$data = result_message("false", "0x0207", "Exception error: disconnect!", "");
			}
			wh_log($Insurance_no, $Remote_insurance_no, "finally complete - status:".$status_code, $Person_id);
		}
	}
	else
	{
		$data = result_message("false", "0x0202", "API parameter is required!", "");
		$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, true);
	}
	wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["responseMessage"]."\r\n".$g_exit_symbol."get agent case exit ->"."\r\n", $Person_id);
	$data["order_status"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>