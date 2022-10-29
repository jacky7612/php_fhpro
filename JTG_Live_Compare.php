<?php
	include("func.php");
	
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: ''; // for update status
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: ''; // for update status
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Action_id 			= isset($_POST['Action_id']) 			? $_POST['Action_id'] 			: '';
	$base64image 		=  isset($_POST['Action_Pic']) 			? $_POST['Action_Pic'] 			: '';

	$Person_id = check_special_char($Person_id);
	$Action_id = check_special_char($Action_id);

	$headers =  apache_request_headers();
	$token = $headers['Authorization'];
	if (check_header($key, $token) == true)
	{
		wh_log($Insurance_no, $Remote_insurance_no, "security token succeed", $Person_id);
	}
	else
	{
		;//echo "error token";
		$data = array();
		$data["status"]			= "false";
		$data["code"]			= "0x0209";
		$data["responseMessage"]= "Invalid token!";	
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(X) security token failure", $Person_id);		
		exit;							
	}
	
	$status_code_succeed = "F1"; // 成功狀態代碼
	$status_code_failure = "F0"; // 失敗狀態代碼
	$status_code = "";
	wh_log($Insurance_no, $Remote_insurance_no, "live compare entry <-", $Person_id);
	
	// 當資料不齊全時，從資料庫取得
	if (($Member_name 	== '') ||
		($Mobile_no 	== '') )
	{
		$memb 		 = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id);
		$Mobile_no 	 = $memb["Mobile_no"];
		$Member_name = $memb["Member_name"];
	}
	$Sales_Id = get_sales_id($Insurance_no, $Remote_insurance_no);
	
	if (($Person_id != '') &&
		($Action_id != ''))
	{
		$date 			= date_create();
		$file_name 		= guid();
		$target_dir 	= $g_target_dir;
		$target_file 	= $target_dir.$file_name;// . "." . $imageFileType;
		$target_file1 	= $target_dir.$file_name."_1";// . $imageFileType;
		if (save_decode_image($base64image, $target_file1, $imageFileType))
		{
			$data2 		= file_get_contents($target_file1);
			$base64_f2 	= base64_encode($data2);
			unlink($target_file1);
		}
		
		$link = null;
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Person_id  = mysqli_real_escape_string($link,$Person_id);
			$Action_id  = mysqli_real_escape_string($link,$Action_id);
			
			$Personid = trim(stripslashes($Person_id));
			$Actionid = trim(stripslashes($Action_id));
			
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Personid				);
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insuance_no"	, $Remote_insuance_no	);

			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					// login ok
					// user id 取得
					/*2021/08/03 $mid=0;
					while($row = mysqli_fetch_array($result)){
						$mid = $row['mid'];
						//$pid_pic = $row['pid_pic'];
						//$base64_f1 = base64_encode($pid_pic);
					}

					//$data1 = file_get_contents($target_file);
					//$base64_f2 = base64_encode($image);
		
					//比對
					//$uriBase2 = 'http://3.37.63.32/faceengine/api/faceCompare.php';
					$uriBase1 =   'http://3.37.63.32/faceengine/api/faceSpoof.php';
					$fields1 = [
						'image_file1'         => $base64_f2
					];
					
					$fields_string1 = http_build_query($fields1);	
					$ch1 = curl_init();
					curl_setopt($ch1,CURLOPT_URL, $uriBase1);
					curl_setopt($ch1,CURLOPT_POST, true);
					curl_setopt($ch1,CURLOPT_POSTFIELDS, $fields_string1);
					curl_setopt($ch1,CURLOPT_RETURNTRANSFER, true); 
					//execute post
					$result1 = curl_exec($ch1);		

					$obj1 = json_decode($result1, true) ;
				
					$IsSuccess1 = $obj1['IsSuccess'];
				
					if  ($IsSuccess1 == "true"){
						//真實人臉
						if ($obj1['status'] == "real face") {
					*/
						//1:點頭, 2:搖頭 3:眨眼
						switch ($Actionid) {
							case "3":		//遮掩/眨眼辨識
								$uriBase2 = $g_live_compare_eyes_apiurl;
								break;
							case "1":		//臉部角度辨識
								$uriBase2 = $g_live_compare_face_pose_apiurl01;
								break;
							case "2":
								$uriBase2 = $g_live_compare_face_pose_apiurl02;
								break;
							default:
								$uriBase2 = $g_live_compare_face_pose_apiurl00;
						}

						// 呼叫 API
						$fields2 = [
							'image_file1' => $base64_f2
						];
						$fields_string2 = http_build_query($fields2);
						$ch2 			= curl_init();
						curl_setopt($ch2,CURLOPT_URL, $uriBase2);
						curl_setopt($ch2,CURLOPT_POST, true);
						curl_setopt($ch2,CURLOPT_POSTFIELDS, $fields_string2);
						curl_setopt($ch2,CURLOPT_RETURNTRANSFER, true);
						$result2 = curl_exec($ch2); //execute post

						// 比對結果
						$IsSuccess2 = "";
						$obj2 = json_decode($result2, true) ;
					
						$IsSuccess2 = $obj2['IsSuccess'];
						$Action = "";

						if  ($IsSuccess2 == "true")
						{
							switch ($Actionid) {
								case "3":		//遮掩/眨眼辨識
									//echo $obj2['data']['LEYE'];
									//echo $obj2['data']['REYE'];
									if (($obj2['data']['LEYE']=='close')||($obj2['data']['REYE']=='close')){
										$Action = "OK";
									}else{
										$Action = "Fail";
									}
									break;
								case "1":		//臉部角度辨識:  點頭
									//echo $obj2['data']['PITCH'];
									if ((doubleval($obj2['data']['PITCH']) >= 5)||(doubleval($obj2['data']['PITCH']) <= -5 )){
										$Action = "OK";
									}else{
										$Action = "Fail";
									}
									break;
								case "2":
									//echo $obj2['data']['YAW'];
									if ((doubleval($obj2['data']['YAW']) >= 5)||(doubleval($obj2['data']['YAW']) <= -5 )){
										$Action = "OK";
									}else{
										$Action = "Fail";
									}
									break;
								default:
									//echo $obj2['data']['ROLL'];
									if ((doubleval($obj2['data']['ROLL']) >= 5)||(doubleval($obj2['data']['ROLL']) <= -5 )){
										$Action = "OK";
									}else{
										$Action = "Fail";
									}
									break;
							}
						}
						//{ 
						//	"IsSuccesss": "true",
						//	"data": {
						//	  "LEYE": "open"
						//	  "REYE": "open"
						//	}
						//} //"close", "open", "random", "unknown"
						
						//{  臉部角度辨識(轉頭YAW/俯仰PITCH/左右偏頭ROLL)
						//	"IsSuccesss": "true",
						//	"data": {
						//	  "RAW": -3.795109
						//	  "PITCH": -5.926450
						//	  "ROLL": 41.706848
						//	}
						//}
						//echo $Action;
						if ($Action == "OK")
						{	
							$data["status"]			= "true";
							$data["code"]			= "0x0200";
							$data["responseMessage"]= "動作相符!";
							$status_code = $status_code_succeed;
						}
						else
						{
							$data["status"]			= "false";
							$data["code"]			= "0x0201";
							$data["responseMessage"]= "動作不相符!";
							$status_code = $status_code_failure;
							
						}
						/* 2021/08/03 }else{
							//攻擊人臉

							$data["status"]="false";
							$data["code"]="0x0205";
							$data["responseMessage"]="照片為合成照片";
						}						
					} else {
						//echo "no face detect!";
						$data["status"]="false";
						$data["code"]="0x0205";
						$data["responseMessage"]="照片為合成照片";
					} */
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0206";
					$data["responseMessage"]= "身分證資料不存在!";
					$status_code = $status_code_failure;
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";
				$status_code = $status_code_failure;
			}
			mysqli_close($link);
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";
			$status_code = $status_code_failure;
		}
		finally
		{
			try
			{
				if ($link != null)
				{
					if ($status_code != "")
						$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, "", $status_code, $link);
	
					mysqli_close($link);
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
	} else {
		//echo "參數錯誤 !";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";	
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."live compare exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>