<?php
	include("func.php");	
	
	//此API可強制變更狀態 由 modify_order_state 變數 ChangeStatusAnyway = true:強制; false:依狀態規則限制
	// initial
	//$status_code_succeed = "B2"; // 成功狀態代碼
	//$status_code_failure = ""; // 失敗狀態代碼
	$link					= null;
	$data 					= array();
	$array4json				= array();
	$Insurance_no 			= "";
	$Remote_insurance_no 	= "";
	$Person_id 				= "";
	$Sales_id 				= "";
	$Mobile_no 				= "";
	$Member_name 			= "";
	$Role 				 	= "";
	$order_status			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		:  '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] :  '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			:  '';
	$Status_code 			= isset($_POST['Status_code']) 			? $_POST['Status_code'] 		:  '';
	$UpdateAllStatus 		= isset($_POST['UpdateAllStatus']) 		? $_POST['UpdateAllStatus'] 	:  'false';
	$ChangeStatusAnyway		= isset($_POST['ChangeStatusAnyway']) 	? $_POST['ChangeStatusAnyway'] 	:  'false';

	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Sales_id 				= check_special_char($Sales_id);
	$Person_id 				= check_special_char($Person_id);
	$Mobile_no 				= check_special_char($Mobile_no);
	$Status_code 			= check_special_char($Status_code);
	$UpdateAllStatus 		= check_special_char($UpdateAllStatus);
	$ChangeStatusAnyway 	= check_special_char($ChangeStatusAnyway);
	
	$UpdateAllStatus 		= trim(strtolower($UpdateAllStatus));
	$ChangeStatusAnyway 	= trim(strtolower($ChangeStatusAnyway));
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
		$Mobile_no 			 = "0912-345-777";
		$Status_code 		 = "B2";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." Modify orderlog sop finish :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."Modify insurance status exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
		
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "Modify insurance status entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['accessToken']) ? $_POST['accessToken'] : '';
	$ret = protect_api("JTG_Modify_Insurance_Status", "Modify insurance status exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	if ($Status_code != "")
	{
		// 更新狀態
		$data = modify_order_state($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Status_code, false, ($UpdateAllStatus == "true"), ($ChangeStatusAnyway == "true"));
	}
	else
	{
		$data = result_message("false", "0x0206", "Status_code is empty!", "");
	}
	// 取得狀態
	$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, true);
	/*
	if ($get_data["status"] == "false")
	{
		$data = get_data;
	}
	*/
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." Modify orderlog sop finish :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."Modify insurance status exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>