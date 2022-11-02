<?php
	include("func.php");
	
	// initial
	$status_code_succeed 	= "C2"; // 成功狀態代碼
	$status_code_failure 	= ""; // 失敗狀態代碼
	$data 					= array();
	$link					= null;
	$Insurance_no 			= ""; // *
	$Remote_insurance_no 	= ""; // *
	$Person_id 				= ""; // *
	$Mobile_no 				= "";
	$json_Person_id 		= "";
	$Sales_id 				= "";
	$status_code 			= "";
	$Role 					= "";
	$Member_name			= "";
	$FCM_Token 				= "";
	$base64image			= "";
	$Role 					= "";
	$imageFileType 			= "jpg";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Mobile_no 				= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			: '';
	$Member_name 			= isset($_POST['Member_name']) 			? $_POST['Member_name'] 		: '';
	$FCM_Token 				= isset($_POST['FCM_Token']) 			? $_POST['FCM_Token'] 			: ''; //大頭照
	$base64image 			= isset($_POST['Pid_Pic']) 				? $_POST['Pid_Pic'] 			: '';
	
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
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	wh_log($Insurance_no, $Remote_insurance_no, "modify member entry <-", $Person_id);

	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Modify_Member_Code", "modify member exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
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
				return;
			}
			
			// connect mysql
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");
			
			// update mysql
			$data = modify_member($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $link, false);
			
			// 操作：更新
			if ($data["status"]				== "false" &&
				$data["code"]				== "0x0201" &&
				$data["responseMessage"]	== "身份資料建檔-無法重複建立，已經有相同身份證資料!")
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
							return;
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
					$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, "", $status_code, $link, false);
	
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