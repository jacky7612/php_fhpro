<?php
	include("func.php");
			
	// Api ------------------------------------------------------------------------------------------------------------------------
	$imageFileType = "jpg";

	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Mobile_no 				= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			: '';
	$Member_name 			= isset($_POST['Member_name']) 			? $_POST['Member_name'] 		: '';
	$FCM_Token 				= isset($_POST['FCM_Token']) 			? $_POST['FCM_Token'] 			: '';
	$base64image 			= isset($_POST['Pid_Pic']) 				? $_POST['Pid_Pic'] 			: ''; //大頭照

	//另外一組 for 身分證圖檔存檔
	$front 			= isset($_POST['front']) 	 ? $_POST['front'] 		: '';//0: front, 1: back
	$base64imageID 	= isset($_POST['Pid_PicID']) ? $_POST['Pid_PicID']  : '';
	
	//$Insurance_no = "{$_REQUEST["Insurance_no"]}";
	//$Person_id = "{$_REQUEST["Person_id"]}";
	//$Mobile_no = "{$_REQUEST["Mobile_no"]}";
	//$Member_name = "{$_REQUEST["Member_name"]}";
	//$FCM_Token = "{$_REQUEST["FCM_Token"]}";
	//	$front = "{$_REQUEST["front"]}";		//0: front, 1: back
		//$picId = "{$_REQUEST["Pid_PicID"]}";	
	$front = trim(stripslashes($front));
	set_log_name($Insurance_no, $Remote_insurance_no);

	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id);
	$Mobile_no 				= check_special_char($Mobile_no);
	$Member_name 			= check_special_char($Member_name);
	$FCM_Token 				= check_special_char($FCM_Token);
	$front 					= check_special_char($front);

	$headers =  apache_request_headers();
	$token = $headers['Authorization'];
	if(check_header($key, $token)==true)
	{
		wh_log($Insurance_no, $Remote_insurance_no, "security token succeed", $Person_id);
	}
	else
	{
		//echo "error token";
		$data 					= array();
		$data["status"]			= "false";
		$data["code"]			= "0x0209";
		$data["responseMessage"]= "Invalid token!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(X) security token failure"."\r\n"."update member exit ->", $Person_id);	
		exit;							
	}
	
	if (($Person_id 			!= '') &&
		($front 				!= '') &&
		($Insurance_no 			!= '') &&
		($Remote_insurance_no 	!= '') &&
		(strlen($Person_id) > 1))
	{
		//echo $Insurance_no ;
		$date 		= date_create();
		$file_name 	= guid();   //date_timestamp_get($date);
		$target_dir = "/var/www/html/member/api/uploads/";
		//$target_dir = "../uploads/";
		//$target_file = $target_dir . basename($_FILES["Pid_PicID"]["name"]);
		//$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		$target_file  = $target_dir . $file_name;// . "." . $imageFileType;
		$target_file1 = $target_dir . $file_name . "_1";//." . $imageFileType;
		$target_file2 = $target_dir . $file_name . "_2";//." . $imageFileType;
	
		//if (move_uploaded_file($_FILES["Pid_PicID"]["tmp_name"], $target_file1)) {
		$image2 = get_image_content2("update member", $Insurance_no, $Remote_insurance_no, $Person_id, $base64imageID, $target_file, $target_file1);
		//$image2 = addslashes(file_get_contents($_FILES['Pid_PicID']['tmp_name']));
		
		try {
			$link2 = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link2,"SET NAMES 'utf8'");

			$Person_id  = mysqli_real_escape_string($link2,$Person_id);
			$front  = mysqli_real_escape_string($link2,$front);
			$Personid = trim(stripslashes($Person_id));
			
			$sql = "SELECT * FROM memberinfo where insurance_no='".$Insurance_no."' and remote_insurance_no='".$Remote_insurance_no."' and member_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);
			if ($result = mysqli_query($link2, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					wh_log($Insurance_no, $Remote_insurance_no, "person_id verify ok", $Person_id);
					try {
						// 將照片儲存到 NAS
						$data = will_save2nas_prepare($Insurance_no, $Remote_insurance_no, $Person_id);
						if ($data["status"] == "false")
						{	
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
							exit;
						}
						$filename = $data["filename"];
						$retimg = save_image2nas($Insurance_no, $Remote_insurance_no, $Person_id, $filename, $image2);
						
						// 
						$log = "";
						if ($retimg > 0)
						{
							wh_log($Insurance_no, $Remote_insurance_no, "save_image2nas Success", $Person_id);
							$sql = "SELECT * from `idphoto` where person_id = '".$Personid."' and insurance_no= '".$Insurance_no."' and remote_insurance_no= '".$Remote_insurance_no."'";
							$ret = mysqli_query($link2, $sql);
							if (mysqli_num_rows($ret) > 0)
							{
								if($front=="0")
								{
									$sql2 = "UPDATE  `idphoto` set `saveType`='NAS', `frontpath` = '$filename', `updatedtime` = NOW() where `person_id`='".$Personid."' and insurance_no= '".$Insurance_no."' ";
									$log = "UPDATE idphoto frontpath ".$filename;
									
								}
								else
								{
									$sql2 = "UPDATE  `idphoto` set `saveType`='NAS', `backpath` = '$filename', `updatedtime` = NOW() where `person_id`='".$Personid."' and insurance_no= '".$Insurance_no."' ";	
									$log = "UPDATE  idphoto backpath ".$filename;
								}
							} else {
								if($front=="0")
								{
									$sql2 = "INSERT INTO  `idphoto` ( `person_id`, `insurance_no`, `frontpath` , `saveType`, `updatedtime`) VALUES ('$Personid', '$Insurance_no', '$filename', 'NAS', NOW()) ";
									$log = "INSERT idphoto frontpath ".$filename;
								}
								else
								{
									$sql2 = "INSERT INTO  `idphoto` ( `person_id`, `insurance_no`, `backpath` , `saveType`, `updatedtime`)  VALUES ('$Personid', '$Insurance_no', '$filename', 'NAS', NOW()) ";
									$log = "INSERT idphoto backpath ".$filename;
								}
							}
							//echo $sql2;
							mysqli_query($link2,$sql2) or die(mysqli_error($link2));
						}
						else
						{
							wh_log($Insurance_no, $Remote_insurance_no, "save_image2nas Failed", $Person_id);
							$data["status"]			= "false";
							$data["code"]			= "0x0206";
							$data["responseMessage"]= "寫入NAS 失敗! (".$retimg.")";	
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
							exit;							
							
						}
						//echo "user data change ok!";
						$data["status"]="true";
						$data["code"]="0x0200";
						$data["responseMessage"]="身分證圖檔".$front."上傳成功!";
						wh_log($Insurance_no, $Remote_insurance_no, $log."\r\n".$data["responseMessage"], $Person_id);
					} catch (Exception $e) {
						wh_log($Insurance_no, $Remote_insurance_no, "Exception error!:".$e->getMessage(), $Person_id);
						$data["status"]			= "false";
						$data["code"]			= "0x0202";
						$data["responseMessage"]= "Exception error!";
						header('Content-Type: application/json');
						echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
						exit;						
					}
				} else {
					wh_log($Insurance_no, $Remote_insurance_no, "無相同身份證資料,無法更新!".$Personid, $Person_id);
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "無相同身份證資料,無法更新!".$Personid;	
					header('Content-Type: application/json');
					echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
					exit;					
				}
			} else {
				wh_log($Insurance_no, $Remote_insurance_no, "SQL fail!", $Person_id);
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";	
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
				exit;				
			}
			mysqli_close($link2);
		}
		catch (Exception $e)
		{
			wh_log($Insurance_no, $Remote_insurance_no, "Exception error2!:".$e->getMessage(), $Person_id);
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";		
			header('Content-Type: application/json');
			echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
			exit;			
		}
		//header('Content-Type: application/json');
		//echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
		//exit;
	} else {
		//option , so can skip
	}	
	
	//--------------------------------------------------------------------------------------
	//$Person_id = "{$_REQUEST["Person_id"]}";
	//$Mobile_no = "{$_REQUEST["Mobile_no"]}";
	//$Member_name = "{$_REQUEST["Member_name"]}";
		
	$data = array();
	
	//echo $Person_id ;
	if (($Person_id != '') &&
		($Mobile_no != '') &&
		//($Insurance_no!='') &&
		($Member_name != '') ) 
	{

		$date = date_create();
		$file_name = guid();   //date_timestamp_get($date);
		$target_dir = "/var/www/html/member/api/uploads/";
		//$target_dir = "../uploads/";
		//$target_file = $target_dir . basename($_FILES["Pid_Pic"]["name"]);
		//$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		$target_file = $target_dir . $file_name;// . "." . $imageFileType;
		$target_file1 = $target_dir . $file_name."_1";//." . $imageFileType;
		
		//if (move_uploaded_file($_FILES["Pid_Pic"]["tmp_name"], $target_file1)) {
		$image1 = get_image_content1($Insurance_no, $Remote_insurance_no, $Person_id, $base64image, $target_file, $target_file1, true);
		
		$data = verify_is_face($image1);
		if ($data["status"] == "false")
		{
			header('Content-Type: application/json');
			echo (json_encode($data, JSON_UNESCAPED_UNICODE));
			wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]."\r\n"."update member exit ->", $Person_id);
			exit;
		}
				
		//$image = addslashes(file_get_contents($_FILES['Pid_Pic']['tmp_name'])); //SQL Injection defence!
		//$image = file_get_contents($_FILES['Pid_Pic']['tmp_name']);

		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Person_id  	= mysqli_real_escape_string($link, $Person_id);
			$Mobile_no  	= mysqli_real_escape_string($link, $Mobile_no);
			$Member_name  	= mysqli_real_escape_string($link, $Member_name);
			//FCM_Token
			$FCM_Token  	= mysqli_real_escape_string($link, $FCM_Token);

			$Personid 	= trim(stripslashes($Person_id));
			$Mobileno 	= trim(stripslashes($Mobile_no));
			$Membername = trim(stripslashes($Member_name));
			$FCMToken 	= trim(stripslashes($FCM_Token));
			$Mobileno 	= addslashes(encrypt($key,$Mobileno));
			$Membername = addslashes(encrypt($key,$Membername));
		
			$sql = "SELECT * FROM memberinfo where insurance_no='".$Insurance_no."' and remote_insurance_no='".$Remote_insurance_no."' and member_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);

			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{						
					$mid = 0;
					while($row = mysqli_fetch_array($result)){
						$mid = $row['mid'];
						//$membername = $row['member_name'];
					}	
					$mid = (int)str_replace(",", "", $mid);						
					try {

						$sql2 = "update `memberinfo` set `mobile_no`='$Mobileno',`member_name`='$Membername'";
						$sql2 = $sql2."";
						if ($FCMToken  != ""){
							$sql2 = $sql2.",`notificationToken`='$FCMToken'";
						}
						if ($image != null){ 
							$sql2 = $sql2.", `pid_pic`='{$image}' ";
						}
						
						$sql2 = $sql2.", `updatedttime`=NOW() where mid=$mid;";
						
						mysqli_query($link,$sql2) or die(mysqli_error($link));
						
						//echo "user data change ok!";
						$data["status"]			= "true";
						$data["code"]			= "0x0200";
						$data["responseMessage"]= "更新身份證資料完成!";
					}
					catch (Exception $e)
					{
						$log = "Exception2 error!:".$e->getMessage();
						$data["status"]			= "false";
						$data["code"]			= "0x0202";
						$data["responseMessage"]= "Exception error!";							
					}
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "無相同身份證資料,更新失敗!";
				}
			}
			else
			{
				$data["status"]="false";
				$data["code"]="0x0204";
				$data["responseMessage"]="SQL fail!";	
				$log = "SQL2 fail!";
			}
			wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"], $Person_id);
			mysqli_close($link);
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";
			$log = "Exception3 error!".$e->getMessage();
		}
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	} else {
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	}
	wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]."\r\n"."detail:".$log, $Person_id);
?>