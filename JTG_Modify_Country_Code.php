<?php
	include("db_tools.php");
	include("security_tools.php");
	include("func.php");
	
	
	$Mobile_no 				= "";
	$Sales_id 				= "";
	
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Country_code 			= isset($_POST['Country_code']) 		? $_POST['Country_code'] 		: '';

	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id);
	$Country_code 			= check_special_char($Country_code);

	wh_log($Insurance_no, $Remote_insurance_no, "Country Code entry <-", $Person_id);
	
	// 驗證 security token
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
	
	$status_code = "";
	if (($Person_id 			!= '') &&
		($Insurance_no 			!= '') &&
		($Remote_insurance_no 	!= '') &&
		($Country_code 			!= ''))
	{
		try {
			$link = null;
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no);
			$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no);
			$Person_id  			= mysqli_real_escape_string($link, $Person_id);
			$Country_code  			= mysqli_real_escape_string($link, $Country_code);
			
			$Insurance_no 			= trim(stripslashes($Insurance_no));
			$Remote_insurance_no 	= trim(stripslashes($Remote_insurance_no));
			$Person_id 				= trim(stripslashes($Person_id));
			$Country_code 			= trim(stripslashes($Country_code));

			$sql = "SELECT * from countrylog where person_id='$Person_id' and insurance_no= '$Insurance_no' and remote_insurance_no= '$Remote_insurance_no' ";
			$result = mysqli_query($link, $sql);
			if (mysqli_num_rows($result) > 0) {
				$sql = "UPDATE countrylog SET countrycode='$Country_code' WHERE person_id='$Person_id' and insurance_no= '$Insurance_no' and remote_insurance_no= '$Remote_insurance_no' ";
			} else {
				$sql = "INSERT INTO countrylog (person_id, insurance_no, remote_insurance_no, countrycode, updatetime ) VALUES ('$Person_id', '$Insurance_no', '$Remote_insurance_no', '$Country_code', NOW() )  ";
			}
			wh_log($Insurance_no, $Remote_insurance_no, "modify countrylog table prepare", $Person_id);

			if ($result = mysqli_query($link, $sql)){
				$data["status"]			= "true";
				$data["code"]			= "0x0200";
				$data["responseMessage"]= "更新成功!";
				$status_code 			= "B0";
			} else {
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";
				$status_code 			= "B1";
			}
			$symbol4log = ($status_code == "B1") ? "(X) ": "";
			$sql = ($status_code == "B1") ? " :".$sql : "";
			wh_log($Insurance_no, $Remote_insurance_no, symbol4log."modify countrylog table result :".$data["responseMessage"].$sql, $Person_id);
			$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $status_code, $link);
			if ($link != null)
				mysqli_close($link);
			wh_log($Insurance_no, $Remote_insurance_no, "modify countrylog sop finish :".$data["responseMessage"], $Person_id);
		}
		catch (Exception $e)
		{
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="Exception error!";
			wh_log($Insurance_no, $Remote_insurance_no, "(X) modify countrylog sop catch :".$data["responseMessage"]."\r\n"."error detail :".$e.getMessage(), $Person_id);			
		}
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
	}
	else
	{
		//echo "參數錯誤 !";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(!)".$data["responseMessage"], $Person_id);	
	}
	wh_log($Insurance_no, $Remote_insurance_no, "Country Code exit ->", $Person_id);
?>