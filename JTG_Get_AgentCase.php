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
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$App_type 			= isset($_POST['App_type']) 			? $_POST['App_type'] 			: '';
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: ''; // update order_start use
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: ''; // update order_start use
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$token 				= isset($_POST['accessToken']) 			? $_POST['accessToken'] 		: '';

	$token 				= check_special_char($token);
	$Insurance_no 		= check_special_char($Insurance_no);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$App_type 			= check_special_char($App_type);
	$Person_id 			= check_special_char($Person_id);

	//$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	//$App_type = "0";//業務員
	if ($App_type == '0')
		$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
	if ($App_type == '1')
		$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
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
	
	// start
	if ($Insurance_no 			!= '' &&
		$Remote_insuance_no 	!= '' &&
		$Person_id 				!= '')
	{
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");
				
			$Person_id  = mysqli_real_escape_string($link, $Person_id);
			$App_type  	= mysqli_real_escape_string($link, $App_type );	
			$token  	= mysqli_real_escape_string($link, $token	 );
			
			$Person_id2 = trim(stripslashes($Person_id)	);
			$App_type2 	= trim(stripslashes($App_type)	);
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
					
					$data = result_message("true", "0x0200", "succeed", $out);
					return;
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
					$data_status = modify_order_state($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $status_code, false);
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
				$data = result_message("false", "0x0207", "Exception error: disconnect!", "");
			}
			wh_log($Insurance_no, $Remote_insurance_no, "finally complete - status:".$status_code, $Person_id);
		}
	}
	else
	{
		$data = result_message("false", "0x0202", "API parameter is required!", "");
	}
	wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["responseMessage"]."\r\n".$g_exit_symbol."get agent case exit ->"."\r\n", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>