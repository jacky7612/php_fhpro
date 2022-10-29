<?php
	include("func.php");
	
	$status_code = "C2"
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Mobile_no 				= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			: '';
	$Member_name 			= isset($_POST['Member_name']) 			? $_POST['Member_name'] 		: '';
	$FCM_Token 				= isset($_POST['FCM_Token']) 			? $_POST['FCM_Token'] 			: ''; //大頭照
	$base64image 			= isset($_POST['Pid_Pic']) 				? $_POST['Pid_Pic'] 			: '';
	$imageFileType 			= "jpg";
	
	$Insurance_no 			= check_special_char($Insurance_no		 );
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id	 		 );
	$Mobile_no 				= check_special_char($Mobile_no	 		 );
	$Member_name 			= check_special_char($Member_name		 );
	$FCM_Token 				= check_special_char($FCM_Token	 		 );
	
	//另外一組 for 身分證圖檔存檔 update section
	$front 			= isset($_POST['front']) 	 ? $_POST['front'] 		: ''; // update section 0: front, 1: back
	$base64imageID 	= isset($_POST['Pid_PicID']) ? $_POST['Pid_PicID']  : ''; // update section
	$front 			= trim(stripslashes($front)); // update section
	$front 			= check_special_char($front); // update section
	
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
	
	// 當資料不齊全時，從資料庫取得
	if (($Member_name 	== '') ||
		($Mobile_no 	== '') )
	{
		$memb 		 = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id);
		$Mobile_no 	 = $memb["Mobile_no"];
		$Member_name = $memb["Member_name"];
	}
	$Sales_Id = get_sales_id($Insurance_no, $Remote_insurance_no);
	
	try
	{
		if (($Person_id 	!= '') &&
			($Sales_Id 		!= '') &&
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
			$image1 = get_image_content($Insurance_no, $Remote_insurance_no, $Person_id, $base64image, $target_file, $target_file1);
			
			//$image = addslashes(file_get_contents($_FILES['Pid_Pic']['tmp_name'])); //SQL Injection defence!
			//$image = file_get_contents($_FILES['Pid_Pic']['tmp_name']);
			//frank ,先確認是否人臉, 若否回傳非人臉,請重拍
			$data = verify_is_face($image1);
			if ($data["status"] == "false")
			{
				$status_code 			= "";
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				wh_log($Insurance_no, $Remote_insurance_no, "create member exit ->", $Person_id);
				exit;
			}
			
			// connect mysql
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");
			
			// update mysql
			$data = modify_member($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $link, false);
			
			// 已經有相同身份證資料
			if ($data["status"]				== "false" &&
				$data["code"]				== "0x0201" &&
				$data["responseMessage"]	== "已經有相同身份證資料!")
			{
				if (($Person_id 			!= '') &&
					($front 				!= '') &&
					($Insurance_no 			!= '') &&
					($Remote_insurance_no 	!= '') &&
					(strlen($Person_id) > 1))
				{
					$date 		= date_create();
					$file_name 	= guid();
					$target_dir = "/var/www/html/member/api/uploads/";
					$target_file  = $target_dir . $file_name;// . "." . $imageFileType;
					$target_file1 = $target_dir . $file_name . "_1";//." . $imageFileType;
					$target_file2 = $target_dir . $file_name . "_2";//." . $imageFileType;
					$image2 = get_image_content_watermark($Insurance_no, $Remote_insurance_no, $Person_id, $base64imageID, $target_file, $target_file1, $target_file2);
					
					
					// update mysql
					$data = update_idphoto($Insurance_no, $Remote_insurance_no, $Person_id, $link, false);
					
					if (($Person_id != '') &&
						($Mobile_no != '') &&
						//($Insurance_no!='') &&
						($Member_name != '') ) 
					{

						$date 		= date_create();
						$file_name 	= guid();
						$target_dir = "/var/www/html/member/api/uploads/";
						$target_file = $target_dir . $file_name;// . "." . $imageFileType;
						$target_file1 = $target_dir . $file_name."_1";//." . $imageFileType;
						$image1 = get_image_content($Insurance_no, $Remote_insurance_no, $Person_id, $base64image, $target_file, $target_file1, true);
						
						$data = verify_is_face($image1);
						if ($data["status"] == "false")
						{
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]."\r\n"."update member exit ->", $Person_id);
							exit;
						}
						
						// update mysql
						$data = update_member($Insurance_no, $Remote_insurance_no, $Person_id, $link, false);
					}
					else
					{
						//echo "need mail and password!";
						$data["status"]			= "false";
						$data["code"]			= "0x0203";
						$data["responseMessage"]= "member - API parameter is required!";
						$status_code 			= "";
					}
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0203";
					$data["responseMessage"]= "idphoto - API parameter is required!";
					$status_code 			= "";
					//option , so can skip
				}
			}
		}
		else
		{
			//echo "need mail and password!";
			$data["status"]			= "false";
			$data["code"]			= "0x0203";
			$data["responseMessage"]= "API parameter is required!";
			$status_code 			= "";
		}
	}
	catch (Exception $e)
	{
		$data["status"]			= "false";
		$data["code"]			= "0x0202";
		$data["responseMessage"]= "Exception error!";
		$status_code 			= "";
	}
	finally
	{
		try
		{
			if ($link != null)
			{
				if ($status_code != "")
					$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, "", $status_code, $link);
	
				mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				$link = null;
			}
		}
		catch(Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error: disconnect!";
		}
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."create member exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>