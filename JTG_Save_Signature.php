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
		exit;							
	}
	
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Signature_pic 		= isset($_POST['Signature_pic']) 		? $_POST['Signature_pic'] 		: '';

	$Insurance_no 		= check_special_char($Insurance_no);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Person_id 			= check_special_char($Person_id);

	//$Mobile_no = isset($_POST['Mobile_no']) ? $_POST['Mobile_no'] : '';
	//$Member_name = isset($_POST['Member_name']) ? $_POST['Member_name'] : '';

	//$Person_id = "{$_REQUEST["Person_id"]}";

	//$image_name = addslashes($_FILES['image']['name']);
	//$sql = "INSERT INTO `product_images` (`id`, `image`, `image_name`) VALUES ('1', '{$image}', '{$image_name}')";
	//if (!mysql_query($sql)) { // Error handling
	//    echo "Something went wrong! :("; 
	//}

	if (($Insurance_no 			!= '' &&
		 $Remote_insuance_no 	!= '' &&
		 $Person_id 			!= '' &&
		 strlen($Person_id) > 1) )
	{
		//$image = addslashes(file_get_contents($_FILES['Signature_pic']['tmp_name'])); //SQL Injection defence!
		//$image = addslashes(encrypt($key,base64_encode(file_get_contents($_FILES['Signature_pic']['tmp_name'])))); //SQL Injection defence!		
		$image = addslashes(encrypt($key, $Signature_pic)); //SQL Injection defence!
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  		= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insuance_no = mysqli_real_escape_string($link, $Remote_insuance_no	);
			$Person_id  		= mysqli_real_escape_string($link, $Person_id			);
			//$Mobile_no  = mysqli_real_escape_string($link,$Mobile_no);
			//$Member_name  = mysqli_real_escape_string($link,$Member_name);
			$Insurance_no 		= trim(stripslashes($Insurance_no)		);
			$Remote_insuance_no = trim(stripslashes($Remote_insuance_no));
			$Personid 			= trim(stripslashes($Person_id)			);
			
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			if ($Insurance_no != "") {	
				$sql = $sql." and insurance_no='".$Insurance_no."'";
			}
			if ($Remote_insuance_no != "") {	
				$sql = $sql." and remote_insuance_no='".$Remote_insuance_no."'";
			}
			if ($Personid != "") {	
				$sql = $sql." and person_id='".$Personid."'";
			}

			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$mid=0;
					while ($row = mysqli_fetch_array($result))
					{
						$mid = $row['mid'];
						//$membername = $row['member_name'];
					}	
					$mid = (int)str_replace(",", "", $mid);
					try {
						$sql2 = "update `memberinfo` set `signature_pic`='{$image}', `updatedttime`=NOW() where mid=$mid;";
						mysqli_query($link,$sql2) or die(mysqli_error($link));
						//echo "user data change ok!";
						$data["status"]="true";
						$data["code"]="0x0200";
						$data["responseMessage"]="簽名檔上傳成功!";
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
	} else {
		//echo "need mail and password!";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));			
	}
?>