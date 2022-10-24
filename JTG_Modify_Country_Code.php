<?php
	//include("header_check.php");
	include("db_tools.php");
	include("security_tools.php");
	/* jacky mark 2022-10-24
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
	*/
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Country_code 			= isset($_POST['Country_code']) 		? $_POST['Country_code'] 		: '';

	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id);
	$Country_code 			= check_special_char($Country_code);

	if (($Person_id 	!= '') &&
		($Insurance_no 	!= '') &&
		($Country_code 	!= ''))
	{
		try {
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
			if (mysqli_num_rows($result) > 0){
				
				$sql = "UPDATE countrylog SET countrycode='$Country_code' where person_id='$Person_id' and insurance_no= '$Insurance_no' and remote_insurance_no= '$Remote_insurance_no' ";
			}
			else
			{
				$sql = "Insert into countrylog (person_id, insurance_no, remote_insurance_no, countrycode, updatetime ) VALUES ('$Person_id', '$Insurance_no', '$Remote_insurance_no', '$Country_code', NOW() )  ";
			}

			if ($result = mysqli_query($link, $sql)){
					$data["status"]="true";
					$data["code"]="0x0200";
					$data["responseMessage"]="更新成功!";
			}else {
				$data["status"]="false";
				$data["code"]="0x0204";
				$data["responseMessage"]="SQL fail!";					
			}
			mysqli_close($link);
		} catch (Exception $e) {
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="Exception error!";				
		}
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
	}else{
		//echo "參數錯誤 !";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
	}
?>