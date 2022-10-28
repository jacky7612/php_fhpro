<?php
	include "db_tools.php";
	include("security_tools.php");
	include("func.php");
	
	$headers =  apache_request_headers();
	$token = $headers['Authorization'];
	if(check_header($key, $token)==true)
	{
		;//echo "valid token";
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
		exit;							
	}

	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
																											 //0:業務員  1:要保人 2:被保人 3: 法定代理人
	$Role 				= isset($_POST['Role']) 	 			? $_POST['Role'] 	   			: 'proposer';//proposer：要保人, insured：被保人, legalRepresentative：法定代理人, agentOne：業務員一
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';

	$Insurance_no 		= check_special_char($Insurance_no);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Role 				= check_special_char($Role);
	$Person_id 			= check_special_char($Person_id);

	/*
	proposer：要保人
	insured：被保人  
	legalRepresentative：法定代理人
	agentOne:業務
	*/
	if (($Role 		!= '') &&
		($Person_id != ''))
	{
		//check 帳號/密碼
		//$host = 'localhost';
		//$user = 'tglmember_user';
		//$passwd = 'tglmember210718';
		//$database = 'tglmemberdb';
		//echo $sql;
		//exit;
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Role  		= mysqli_real_escape_string($link,$Role);
			$Person_id  = mysqli_real_escape_string($link,$Person_id);

			$Role = trim(stripslashes($Role));
			$Personid = trim(stripslashes($Person_id));

			if($Role != "agentOne") // 業務員
			{
				$data["status"]="false";
				$data["code"]="0x0204";
				$data["responseMessage"]="無此權限";
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));					
				exit;
			}
			
			$sql = "SELECT * FROM memberinfo where role='agentOne' and member_trash=0 "; // get sales $sql = "SELECT * FROM salesinfo where sales_trash=0 ";
			if ($Insurance_no != "") {	
				$sql = $sql." and insurance_no='".$Insurance_no."'";
			}
			if ($Remote_insuance_no != "") {	
				$sql = $sql." and remote_insuance_no='".$Remote_insuance_no."'";
			}
			if ($Person_id != "")
			{
				$sql = $sql." and person_id='".$Personid."'";
			}

			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$max = 0;
					while($row = mysqli_fetch_array($result)){
						$sql = "select SUM(count) as max from gomeeting where 1";
						$result = mysqli_query($link, $sql);
						while($row = mysqli_fetch_array($result)){
							$max = $row['max'];
						}
						$max = (int)str_replace(",", "", $max);
					}
					$data["status"]="true";
					$data["code"]="0x0200";
					$data["count"]=$max;
					header('Content-Type: application/json');
					echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
					exit;				
				} else {
					//沒有權限
					$data["status"]="false";
					$data["code"]="0x0201";
					$data["responseMessage"]="沒有呼叫此API的權限!";
					header('Content-Type: application/json');
					echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
					exit;
				}
			} else {
				//sql failed
				$data["status"]="false";
				$data["code"]="0x0202";
				$data["responseMessage"]="SQL Failed!";
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
				exit;
			}			
		} catch (Exception $e) {
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="Exception error!";				
        }
	} else {
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
		exit;
	}
?>