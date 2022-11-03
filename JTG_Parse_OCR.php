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
	// add code at here
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	wh_log("SSO_Login", $remote_ip4filename, "SSO Login for get insurance json exit ->");
?>