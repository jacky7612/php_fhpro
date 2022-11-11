<?php
	include "func.php";
	
	// initial
	$status_code_succeed 	= "D4"; // 成功狀態代碼
	$status_code_failure 	= "D3"; // 失敗狀態代碼
	$data 					= array();
	$fields2   				= array();
	$userList  				= array();
	$numbering 				= "";
	$link					= null;
	$Insurance_no 			= ""; // *
	$Remote_insurance_no 	= ""; // *
	$Person_id 				= ""; // *
	$Mobile_no 				= "";
	$json_Person_id 		= "";
	$Sales_id 				= "";
	$status_code 			= "";
	$Member_name			= "";
	$Role 					= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Policy_time 			 = "1";
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
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "OCR entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Send_Policy_Code", "OCR exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	$data = $ret;
	// add code at here
						
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "OCR exit ->"."\r\n", $Person_id);
?>