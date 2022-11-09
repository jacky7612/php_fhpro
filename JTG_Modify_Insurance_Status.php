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
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		:  '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] :  '';
	$Sales_id 				= isset($_POST['Sales_id']) 			? $_POST['Sales_id'] 			:  '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			:  '';
	$Mobile_no 				= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			:  '';
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
		$data = result_message("false", "0x0203", "get data failure", "");
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
		
	wh_log($Insurance_no, $Remote_insurance_no, "Modify insurance status entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
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
		$data = modify_order_state($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Status_code, true, ($UpdateAllStatus == "true"), ($ChangeStatusAnyway == "true"));
	}
	else
	{
		$data = result_message("false", "0x0201", "Status_code is empty!", "");
	}
	wh_log($Insurance_no, $Remote_insurance_no, "Modify orderlog sop finish :".$data["responseMessage"], $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	wh_log($Insurance_no, $Remote_insurance_no, "Modify insurance status exit ->"."\r\n", $Person_id);
?>