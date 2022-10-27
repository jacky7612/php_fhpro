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
	
	$Person_id = isset($_POST['Person_id']) ? $_POST['Person_id'] : '';
	$Person_id = check_special_char($Person_id);

	if ($Person_id != '')
	{
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Person_id  = mysqli_real_escape_string($link,$Person_id);
			//$member_pwd  = mysqli_real_escape_string($link,$member_pwd);
			//$shopping_area  = mysqli_real_escape_string($link,$shopping_area);
			//$store_type  = mysqli_real_escape_string($link,$store_type);
			$Person_id = trim(stripslashes($Person_id));

			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			if ($Person_id != "") {	
				$sql = $sql." and person_id='".$Person_id."'";
			}

			if ($result = mysqli_query($link, $sql)) {
				if (mysqli_num_rows($result) > 0) {
					// login ok
					// user id 取得
					$mid=0;
					while($row = mysqli_fetch_array($result)){
						$mid = $row['mid'];
						//$membername = $row['member_name'];
					}
					$data["status"]="true";
					$data["code"]="0x0200";
					$data["responseMessage"]="已經有相同身份證資料!";	
				} else {
					$data["status"]="false";
					$data["code"]="0x0201";
					$data["responseMessage"]="無相同身份證資料!";						
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
		//echo "參數錯誤 !";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
	}
?>