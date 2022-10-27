<?php
	include("db_tools.php"); 
	include("security_tools.php");
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
	
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Sales_id 			= isset($_POST['Sales_id']) 			? $_POST['Sales_id'] 			: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Mobile_no 			= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			: '';
	//$Member_type 		= isset($_POST['Member_type']) 			? $_POST['Member_type'] 		: '1';
	$Verification_Code 	= isset($_POST['Verification_Code']) 	? $_POST['Verification_Code'] 	: '1'; //Verification_Code

	$Insurance_no 		= check_special_char($Insurance_no);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Sales_id 			= check_special_char($Sales_id);
	$Person_id 			= check_special_char($Person_id);
	$Mobile_no 			= check_special_char($Mobile_no);
	$Verification_Code 	= check_special_char($Verification_Code);

	if (($Insurance_no 			!= '') &&
		($Remote_insuance_no 	!= '') &&
		($Sales_id 				!= '') &&
		($Person_id 			!= '') &&
		($Mobile_no 			!= '') )
	{

		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insuance_no  	= mysqli_real_escape_string($link, $Remote_insuance_no	);
			$Sales_id  				= mysqli_real_escape_string($link, $Sales_id			);
			$Person_id  			= mysqli_real_escape_string($link, $Person_id			);
			$Mobile_no  			= mysqli_real_escape_string($link, $Mobile_no			);
			//$Member_type  		= mysqli_real_escape_string($link, $Member_type			);
			$Verification_Code  	= mysqli_real_escape_string($link, $Verification_Code	);

			$Insuranceno 		= trim(stripslashes($Insurance_no)		);
			$Remote_insuance_no = trim(stripslashes($Remote_insuance_no));
			$Salesid 			= trim(stripslashes($Sales_id)			);
			$Personid 			= trim(stripslashes($Person_id)			);
			$Mobileno 			= trim(stripslashes($Mobile_no)			);
			$VerificationCode 	= trim(stripslashes($Verification_Code)	);

			//$Personid = encrypt($key,($Personid));
			$Mobileno = addslashes(encrypt($key, ($Mobileno
			
			$sql = "SELECT * FROM orderinfo where order_no='$Insuranceno' and remote_insuance_no='$Remote_insuance_no' and sales_id='$Salesid' and person_id='$Personid' and mobile_no='$Mobileno'  and order_trash=0";
			//and member_type=$Member_type
			//if ($Insurance_no != "") {
			//	$sql = $sql." and order_no='".$Insurance_no."'";
			//}

			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$mid=0;
					while($row = mysqli_fetch_array($result)){
						$rid = $row['rid'];
						$code = $row['verification_code'];
					}	
					$code = str_replace(",", "", $code);	
					
					if ($VerificationCode == $code) {
						$data["status"]="true";
						$data["code"]="0x0200";
						$data["responseMessage"]="驗證碼正確!";	

						$sql2 = "update `orderinfo` set `verification_code`='' where order_no='$Insuranceno' and sales_id='$Salesid' and person_id='$Personid' and mobile_no='$Mobileno' and order_trash=0";
						//and member_type=$Member_type 
						mysqli_query($link,$sql2) or die(mysqli_error($link));
						
					}else{
						$data["status"]="false";
						$data["code"]="0x0201";
						$data["responseMessage"]="驗證碼錯誤!";	
					
					}
				} else {
					$data["status"]="false";
					$data["code"]="0x0205";
					$data["responseMessage"]="流水要保序號錯誤!";						
				}
			} else {
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