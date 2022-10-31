<?php
	include("func.php");
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$base64image 		= isset($_POST['Pid_Pic']) 				? $_POST['Pid_Pic'] 			: '';
	//$Person_id 		= "{$_POST["Person_id"]}";
	$imageFileType 		= "jpg";

	$Insurance_no 		= check_special_char($Insurance_no);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Person_id 			= check_special_char($Person_id);

	// 驗證 security token
	$headers = apache_request_headers();
	$token 	 = $headers['Authorization'];
	if(check_header($key, $token) == true)
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
		return;							
	}
	
	$status_code_succeed = "G1"; // 成功狀態代碼
	$status_code_failure = "G0"; // 失敗狀態代碼
	$status_code = "";
	wh_log($Insurance_no, $Remote_insurance_no, "face compare entry <-", $Person_id);
	
	// 當資料不齊全時，從資料庫取得
	if (($Member_name 	== '') ||
		($Mobile_no 	== '') )
	{
		$memb 		 = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id);
		$Mobile_no 	 = $memb["Mobile_no"];
		$Member_name = $memb["Member_name"];
	}
	$Sales_Id = get_sales_id($Insurance_no, $Remote_insurance_no);
	if (($Person_id 	!= '') &&
		 $base64image  	!= '')
	{
		$date 			= date_create();
		$file_name 		= date_timestamp_get($date);
		$target_dir 	= $g_target_dir;
		$target_file 	= $target_dir . $file_name;// . "." . $imageFileType;
		$target_file1 	= $target_dir . $file_name. "_1";// . $imageFileType;
		
		// 取得比對照片
		$img = get_image_content($Insurance_no, $Remote_insurance_no, $Person_id, $base64image, $target_file, $target_file1);
		$base64_f2 = base64_encode($img);
		
		$link = null;
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Person_id  = mysqli_real_escape_string($link,$Person_id);
			
			$Personid 	= trim(stripslashes($Person_id));
		
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Personid				);
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insuance_no"	, $Remote_insuance_no	);

			wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$mid = 0;
					while ($row = mysqli_fetch_array($result))
					{
						$mid = $row['mid'];
						$pid_pic = $row['pid_pic'];
						//$base64_f1 = base64_encode($pid_pic);
						$base64_f1 = decrypt($key,$pid_pic);
					}
					$mid = (int)str_replace(",", "", $mid);
					
					// 比對
					$uriBase2 = $g_face_compare_apiurl;
					$fields2 = [
						'image_file1' => $base64_f1,
						'image_file2' => $base64_f2
					];
					
					// 呼叫API
					$fields_string2 = http_build_query($fields2);	
					$ch2 = curl_init();
					curl_setopt($ch2,CURLOPT_URL, $uriBase2);
					curl_setopt($ch2,CURLOPT_POST, true);
					curl_setopt($ch2,CURLOPT_POSTFIELDS, $fields_string2);
					curl_setopt($ch2,CURLOPT_RETURNTRANSFER, true);
					$result2 = curl_exec($ch2); //execute post

					$IsSuccess2 = "";
					$obj2 = json_decode($result2, true) ;
				
					$IsSuccess2 = $obj2['IsSuccess'];
					//echo $result2;
					if  ($IsSuccess2 == "true"){
						$confidence = doubleval($obj2['confidence']);
						if ($confidence >= 0.45)		//0.5
						{
							//echo "人臉比對完成！同一人(confidence=".$confidence.")";		
							$data["status"]			= "true";
							$data["code"]			= "0x0200";
							$data["responseMessage"]= "照片比對相同!";
							$data["confidence"]		= $confidence;
							$sql = "Insert into facecomparelog (Person_id,  confidence, updatetime) values ('$Person_id','$confidence', NOW()  )";
							mysqli_query($link, $sql);
							$status_code = $status_code_succeed;
						}
						else
						{
							//echo "人臉比對完成！不同一人(confidence=".$confidence.")";		
							$data["status"]			= "false";
							$data["code"]			= "0x0201";
							$data["responseMessage"]= "照片比對不相同!";
							$data["confidence"]		= $confidence;
							$sql = "Insert into facecomparelog (Person_id, confidence, updatetime) values ('$Personid','$confidence', NOW()  )";
							mysqli_query($link, $sql);
							$status_code = $status_code_failure;
						}
					}
					else
					{
						$data["status"]			= "false";
						$data["code"]			= "0x0207";
						$data["responseMessage"]= "沒有偵測到人臉!";
						$status_code 			= $status_code_failure;
						//$face1 = $pid_pic;
						//$face2 = addslashes(encrypt($key,base64_encode($data2image)));
						$sql = "Insert into facecomparelog (Person_id, confidence, updatetime) values ('$Personid','$confidence', NOW()  )";
						mysqli_query($link, $sql);
					}
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0206";
					$data["responseMessage"]= "身分證資料不存在!";
					$status_code 			= $status_code_failure;
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";
				$status_code 			= $status_code_failure;
			}
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";				
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
	}
	else
	{
		//echo "參數錯誤 !";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."face compare exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>