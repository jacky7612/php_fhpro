<?php
	include("db_tools.php");
	include("resize-class.php");
	include("security_tools.php");
	
	$headers =  apache_request_headers();
	$token = $headers['Authorization'];
	if(check_header($key, $token)==true)
	{
		;//echo "valid token";
	} else {
		;//echo "error token";
		$data = array();
		$data["status"]="false";
		$data["code"]="0x0209";
		$data["responseMessage"]="Invalid token!";	
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
		exit;							
	}

	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST["Person_id"])			? $_POST["Person_id"]			: '';
	$Front 					= isset($_POST["Front"])				? $_POST["Front"]				: '';//0: front, 1: back
	$PicId 					= isset($_POST["Pid_PicID"])			? $_POST["Pid_PicID"]			: '';

	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id);
	$Front 					= check_special_char($Front);

	if (($Insurance_no 			!= '' &&
		 $Remote_insurance_no 	!= '' &&
		 $Person_id 			!= '' &&
		 strlen($Person_id)>1) )
	{
		$image = addslashes(encrypt($key, $PicId)); //SQL Injection defence!
		//$image = ($PicId); //SQL Injection defence!
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link, "SET NAMES 'utf8'");

			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no);
			$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no);
			$Person_id  			= mysqli_real_escape_string($link, $Person_id);
			$Front  				= mysqli_real_escape_string($link, $Front);
			//$Mobile_no  			= mysqli_real_escape_string($link,$Mobile_no);
			//$Member_name  		= mysqli_real_escape_string($link,$Member_name);
			
			$Insurance_no 			= trim(stripslashes($Insurance_no));
			$Remote_insurance_no 	= trim(stripslashes($Remote_insurance_no));
			$Personid 				= trim(stripslashes($Person_id));
			
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			if ($Personid != "") {	
				$sql = $sql." and person_id='".$Personid."'";
			}

			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$mid=0;
					while($row = mysqli_fetch_array($result)){
						$mid = $row['mid'];
						//$membername = $row['member_name'];
					}	
					$mid = (int)str_replace(",", "", $mid);				
					try {
						$subWhere = "";
						if ($Insurance_no != "") {
							$subWhere = $subWhere." and insurance_no='".$Insuranceno."'";
						}
						if ($Remote_insurance_no != "") {
							$subWhere = $subWhere." and remote_insurance_no='".$Remote_insurance_no."'";
						}
						
						$sql = "SELECT * from `idphoto` where person_id = '".$Personid."'".$subWhere;
						$ret = mysqli_query($link, $sql);
						if (mysqli_num_rows($ret) > 0)
						{
							if($Front=="0")
								$sql2 = "UPDATE  `idphoto` set `front` = '{$image}', `updatedtime` = NOW() where `person_id`='".$Personid."'".$subWhere;
							else
								$sql2 = "UPDATE  `idphoto` set `back` = '{$image}', `updatedtime` = NOW() where `person_id`='".$Personid."'".$subWhere;
								
						} else {
							if($Front=="0")
								$sql2 = "INSERT INTO  `idphoto` ( `insurance_no`, `remote_insurance_no`, `person_id`, `front`, `updatedtime`) VALUES ('$Insuranceno', '$Remote_insurance_no', '$Personid', '{$image}', NOW()) ";
							else
								$sql2 = "INSERT INTO  `idphoto` ( `insurance_no`, `remote_insurance_no`, `person_id`, `back` , `updatedtime`)  VALUES ('$Insuranceno', '$Remote_insurance_no', '$Personid', '{$image}', NOW()) ";
						}

						mysqli_query($link, $sql2) or die(mysqli_error($link));
						
						//echo "user data change ok!";
						$data["status"]="true";
						$data["code"]="0x0200";
						$data["responseMessage"]="身分證上傳成功!";		
						
					} catch (Exception $e) {
						$data["status"]="false";
						$data["code"]="0x0202";
						$data["responseMessage"]="Exception error!";							
					}
				} else {
					$data["status"]="false";
					$data["code"]="0x0201";
					$data["responseMessage"]="無相同身份證資料,無法更新!".$Person_id;						
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
	}else{
		//echo "need mail and password!";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));			
	}
?>