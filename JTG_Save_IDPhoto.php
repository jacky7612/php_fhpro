<?php
	include("func.php");
	global $g_OCR_front_type_code, $g_OCR_back_type_code;
	
	// initial
	$status_code_succeed 	= "D1"; // 成功狀態代碼
	$status_code_failure 	= "D0"; // 失敗狀態代碼
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
	$FCM_Token 				= "";
	$base64image			= "";
	$Role 					= "";
	$imageFileType 			= "";
	$Front					= "";
	$order_status			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	api_get_post_param($token, $Insurance_no, $Remote_insurance_no, $Person_id);
	$Front	= isset($_POST["Front"])		? $_POST["Front"]		: '';//0: front, 1: back
	$PicId	= isset($_POST["Pid_PicID"])	? $_POST["Pid_PicID"]	: '';
	$Front 	= check_special_char($Front);
	
	if ($Front == $g_OCR_back_type_code)
	{
		$status_code_succeed 	= "D2"; // 成功狀態代碼
		$status_code_failure 	= "D3"; // 失敗狀態代碼
	}
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
		$Front				 = $g_OCR_front_type_code;
		$PicId		 		 = "data:image/jpeg;base64,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";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"].$g_exit_symbol."\r\n"."save idphoto exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "save idphoto entry <-", $Person_id);

	// 驗證 security token
	$ret = protect_api("JTG_Save_IDPhoto", "save idphoto exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	if ($Insurance_no 			!= '' &&
		$Remote_insurance_no 	!= '' &&
		$Person_id 				!= '' &&
		strlen($Person_id) 		 > 1)
	{
		$image = addslashes(encrypt($key, $PicId)); //SQL Injection defence!
		//$image = ($PicId); //SQL Injection defence!
		try
		{
			$data = create_connect($link, $Insurance_no, $Remote_insurance_no, $Person_id);
			if ($data["status"] == "false") return;

			$data = save_idphoto_table_info($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Front, $PicId, false);
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "Exception error!", "");
			$status_code = $status_code_failure;
			JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
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
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"].$g_exit_symbol."\r\n"."save idphoto exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>