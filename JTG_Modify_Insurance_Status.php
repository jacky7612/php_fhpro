<?php
	include("db_tools.php"); 
	include("security_tools.php");
	include("func.php");	
	
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		:  '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] :  '';
	$Sales_id 				= isset($_POST['Sales_id']) 			? $_POST['Sales_id'] 			:  '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			:  '';
	$Mobile_no 				= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			:  '';
	$Member_type 			= isset($_POST['Member_type']) 			? $_POST['Member_type'] 		: '1';
	$Status_code 			= isset($_POST['Status_code']) 			? $_POST['Status_code'] 		:  '';

	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Sales_id 				= check_special_char($Sales_id);
	$Person_id 				= check_special_char($Person_id);
	$Mobile_no 				= check_special_char($Mobile_no);
	$Member_type 			= check_special_char($Member_type);
	$Status_code 			= check_special_char($Status_code);
	
	$headers =  apache_request_headers();
	$token = $headers['Authorization'];
	if(check_header($key, $token)==true)
	{
		wh_log($Insurance_no, $Remote_insurance_no, "security token succeed", $Person_id);
	}
	else
	{
		;//echo "error token";
		$data = array();
		$data["status"]="false";
		$data["code"]="0x0209";
		$data["responseMessage"]="Invalid token!";	
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(X) security token failure", $Person_id);
		exit;							
	}
	
	wh_log($Insurance_no, $Remote_insurance_no, "Modify insurance status entry <-", $Person_id);
	$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $status_code, $link);
	wh_log($Insurance_no, $Remote_insurance_no, "modify countrylog sop finish :".$data["responseMessage"], $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	wh_log($Insurance_no, $Remote_insurance_no, "Modify insurance status exit ->", $Person_id);
?>