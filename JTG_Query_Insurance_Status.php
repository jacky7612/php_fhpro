<?php
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
		return;							
	}

	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Sales_id 				= isset($_POST['Sales_id']) 			? $_POST['Sales_id'] 			: '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Mobile_no 				= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			: '';
	$Role 					= isset($_POST['Role']) 				? $_POST['Role'] 				: '1';

	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Sales_id 				= check_special_char($Sales_id);
	$Person_id 				= check_special_char($Person_id);
	$Mobile_no 				= check_special_char($Mobile_no);
	$Role 					= check_special_char($Role);

	if (($Insurance_no 			!= '') &&
		($Remote_insurance_no 	!= '') &&
		($Sales_id 				!= '') &&
		($Person_id 			!= '') &&
		($Mobile_no 			!= '') )
	{
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no);
			$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no);
			$Sales_id  				= mysqli_real_escape_string($link, $Sales_id);
			$Person_id  			= mysqli_real_escape_string($link, $Person_id);
			$Mobile_no  			= mysqli_real_escape_string($link, $Mobile_no);
			$Role  					= mysqli_real_escape_string($link, $Role);

			$Insuranceno 			= trim(stripslashes($Insurance_no));
			$Remote_insuranceno 	= trim(stripslashes($Remote_insurance_no));
			$Salesid 				= trim(stripslashes($Sales_id));
			$Personid 				= trim(stripslashes($Person_id));
			$Mobileno 				= trim(stripslashes($Mobile_no));
			$Role 					= trim(stripslashes($Role));

			$Mobileno 				= addslashes(encrypt($key,$Mobileno));
		
			$sql = "SELECT * FROM orderinfo where insurance_no='".$Insuranceno."' and remote_insuranceno='".$Remote_insuranceno."' and sales_id='".$Salesid."' and person_id='".$Personid."' and mobile_no='".$Mobileno."' and role='".$Role."' and order_trash=0 ";
			
			if ($result = mysqli_query($link, $sql)){
				if (mysqli_num_rows($result) > 0){
					//$mid=0;
					$order_status="";
					while($row = mysqli_fetch_array($result)){
						//$mid = $row['mid'];
						$order_status = $row['order_status'];
					}
					$order_status = str_replace(",", "", $order_status);
					try {
						//echo "user data change ok!";
						$data["status"]="true";
						$data["code"]="0x0200";
						$data["responseMessage"]=$order_status;		
						
					} catch (Exception $e) {
						$data["status"]="false";
						$data["code"]="0x0202";
						$data["responseMessage"]="Exception error!";							
					}
				}else{
					$data["status"]="false";
					$data["code"]="0x0201";
					$data["responseMessage"]="不存在此要保流水序號的資料!";						
				}
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
	} else {
		//echo "need mail and password!";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));			
	}
?>