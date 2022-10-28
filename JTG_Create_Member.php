<?php
	include("func.php");
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Mobile_no 				= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			: '';
	$Member_name 			= isset($_POST['Member_name']) 			? $_POST['Member_name'] 		: '';
	$FCM_Token 				= isset($_POST['FCM_Token']) 			? $_POST['FCM_Token'] 			: '';
	$base64image 			= isset($_POST['Pid_Pic']) 				? $_POST['Pid_Pic'] 			: '';
	$imageFileType 			= "jpg";

	$Person_id 		= check_special_char($Person_id	 );
	$Mobile_no 		= check_special_char($Mobile_no	 );
	$Member_name 	= check_special_char($Member_name);
	$FCM_Token 		= check_special_char($FCM_Token	 );
	
	wh_log($Insurance_no, $Remote_insurance_no, "create member entry <-", $Person_id);
	//$Person_id = "{$_REQUEST["Person_id"]}";
	//$Mobile_no = "{$_REQUEST["Mobile_no"]}";
	//$Member_name = "{$_REQUEST["Member_name"]}";
	//$FCM_Token = "{$_REQUEST["FCM_Token"]}";

	//$image_name = addslashes($_FILES['image']['name']);
	//$sql = "INSERT INTO `product_images` (`id`, `image`, `image_name`) VALUES ('1', '{$image}', '{$image_name}')";
	//if (!mysql_query($sql)) { // Error handling
	//    echo "Something went wrong! :("; 
	//}

	// 驗證 security token
	$headers =  apache_request_headers();
	$token = $headers['Authorization'];
	if(check_header($key, $token) == true) {
		wh_log($Insurance_no, $Remote_insurance_no, "security token succeed", $Person_id);
	} else {
		//echo "error token";
		$data = array();
		$data["status"]="false";
		$data["code"]="0x0209";
		$data["responseMessage"]="Invalid token!";	
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(X) security token failure"."\r\n"."create member exit ->", $Person_id);
		exit;							
	}
	
	if (($Member_name 	== '') ||
		($Mobile_no 	== '') )
	{
		$memb = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id);
		$Mobile_no 	 = $memb["Mobile_no"];
		$Member_name = $memb["Member_name"];
	}
	
	if (($Person_id 	!= '') &&
		($Member_name 	!= '') &&
		($Mobile_no 	!= '') )
	{
		$date 		= date_create();
		$file_name 	= guid(); //date_timestamp_get($date);
		$target_dir = "/var/www/html/member/api/uploads/";
		// $target_dir = "../uploads/";
		// $target_file = $target_dir . basename($_FILES["Pid_Pic"]["name"]);
		// $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		$target_file 	= $target_dir . $file_name;// . "." . $imageFileType;
		$target_file1 	= $target_dir . $file_name . "_1";//." . $imageFileType;
		$image1 = get_image_content1($Insurance_no, $Remote_insurance_no, $Person_id, $base64image, $target_file, $target_file1);
		
		//$image = addslashes(file_get_contents($_FILES['Pid_Pic']['tmp_name'])); //SQL Injection defence!
		//$image = file_get_contents($_FILES['Pid_Pic']['tmp_name']);
		//frank ,先確認是否人臉, 若否回傳非人臉,請重拍
		$data = verify_is_face($image1);
		if ($data["status"] == "false")
		{
			header('Content-Type: application/json');
			echo (json_encode($data, JSON_UNESCAPED_UNICODE));
			wh_log($Insurance_no, $Remote_insurance_no, "create member exit ->", $Person_id);
			exit;
		}
		
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Person_id  	= mysqli_real_escape_string($link,$Person_id);
			$Mobile_no  	= mysqli_real_escape_string($link,$Mobile_no);
			$Member_name  	= mysqli_real_escape_string($link,$Member_name);
			$FCM_Token  	= mysqli_real_escape_string($link,$FCM_Token);

			$Personid 		= trim(stripslashes($Person_id));
			$Mobileno 		= trim(stripslashes($Mobile_no));
			$Membername 	= trim(stripslashes($Member_name));
			$FCMToken 		= trim(stripslashes($FCM_Token));
			
			//$Personid 	= encrypt($key,($Personid));
			$Mobileno 		= addslashes(encrypt($key,($Mobileno)));
			$Membername 	= addslashes(encrypt($key,($Membername)));
			
			$sql = "SELECT * FROM memberinfo where insurance_no='".$Insurance_no."' and remote_insurance_no='".$Remote_insurance_no."' and member_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);
			wh_log($Insurance_no, $Remote_insurance_no, "create memberinfo table prepare", $Person_id);
			$sql2 = "";
			if ($result = mysqli_query($link, $sql))
			{
				wh_log($Insurance_no, $Remote_insurance_no, "create member search", $Person_id);
				if (mysqli_num_rows($result) == 0) {
					$mid=0;
					try {
						$sql2 = "INSERT INTO `memberinfo` (`insurance_no`,`remote_insurance_no`,`person_id`,`mobile_no`,`member_name`, `notificationToken`,`pid_pic`, `member_trash`, `inputdttime`) VALUES ('$Insurance_no','$Remote_insurance_no','$Personid','$Mobileno','$Membername','$FCMToken','{$image}', 0,NOW())";
						mysqli_query($link,$sql2) or die(mysqli_error($link));
						//echo "user data change ok!";
						$data["status"]			= "true";
						$data["code"]			= "0x0200";
						$data["responseMessage"]= "身份資料建檔成功!";
						$status_code 			= "C2";
					}
					catch (Exception $e)
					{
						$data["status"]			= "false";
						$data["code"]			= "0x0202";
						$data["responseMessage"]= "Exception error!";
						$status_code 			= "";
					}
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "已經有相同身份證資料!";	
					$status_code 			= "";
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";
				$status_code 			= "";
			}
			if ($status_code != "")
				$data = Modify_order_State($Insurance_no, $Remote_insurance_no, $Personid, $Sales_id, $Mobileno, "C2");
			
			wh_log($Insurance_no, $Remote_insurance_no, symbol4log."create memberinfo table result :".$data["responseMessage"].$sql2, $Person_id);
			mysqli_close($link);
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]="Exception error!";					
		}
	}
	else
	{
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
	}
	wh_log($Insurance_no, $Remote_insurance_no, "create member exit ->", $Person_id);
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>