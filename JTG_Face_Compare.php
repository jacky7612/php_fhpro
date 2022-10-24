<?php
	//include("header_check.php");
	include("db_tools.php"); 
	include("resize-class.php");
	include("security_tools.php");
	/* jacky mark 2022-10-24
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
	*/

	function save_decode_image($image, $filename, &$imageFileType)
	{
		$file = fopen($filename, "w");
		if($file <=0) return 0;
		$data = base64_decode($image);
		if(strlen($data) <=0) return 0;
		fwrite($file, $data);
		fclose($file);
		switch(exif_imagetype($filename)) {
			case IMAGETYPE_GIF: 
				$imageFileType = "gif";
				break;
			case IMAGETYPE_JPEG:
				$imageFileType = "jpg";
				break;
			case IMAGETYPE_PNG:
				$imageFileType = "png";
				break;		
		}
		return 1;
	}

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

	if (($Person_id != '') &&
		 $base64image  != '')
	{

		//$image = addslashes(file_get_contents($_FILES['Pid_Pic']['tmp_name'])); 

		$date 		= date_create();
		$file_name 	= date_timestamp_get($date);
		
		$target_dir = "/var/www/html/member/api/uploads/";
		//$target_dir = "../uploads/";
		//$target_file = $target_dir . basename($_FILES["Pid_Pic"]["name"]);
		//$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		//$target_file = $target_dir . $file_name . "." . $imageFileType;
		
		//2022/02/16 change to base64_encode image
		//$target_file = $target_dir . $file_name . "." . $imageFileType;
		$target_file = $target_dir . $file_name;// . "." . $imageFileType;
		//$target_file1 = $target_dir . $file_name . "_1." . $imageFileType;
		$target_file1 = $target_dir . $file_name. "_1";// . $imageFileType;
			
		//if (move_uploaded_file($_FILES["Pid_Pic"]["tmp_name"], $target_file1)) {
		
		if (save_decode_image($base64image, $target_file1, $imageFileType))
		{
			rename($target_file, $target_file.".".$imageFileType);
			rename($target_file1, $target_file1.".".$imageFileType);
			
			$target_file = $target_file.".".$imageFileType;
			$target_file1 = $target_file1.".".$imageFileType;
			
			$resizeObj = new resize($target_file1);

			$img_data = getimagesize($target_file1);
			if ($img_data[0] < $img_data[1]){
			// *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
				$resizeObj -> resizeImage(400, 600, 'auto');
			}else{
				$resizeObj -> resizeImage(600, 400, 'auto');
			}
			// *** 3) Save image
			$resizeObj -> saveImage($target_file, 100);
			//$img_data = getimagesize($target_file);
			//echo $img_data[0].":";
			//echo $img_data[1];			
			unlink($target_file1);
			/////echo "OK";
			$data2 = file_get_contents($target_file);
			//$data2 = file_get_contents($_FILES['Pid_Pic']['tmp_name']);
			$data2image = file_get_contents($target_file);
			$base64_f2 = base64_encode($data2);
			unlink($target_file);
		}
		
		//$image = file_get_contents($_FILES['Pid_Pic']['tmp_name']); 
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Person_id  = mysqli_real_escape_string($link,$Person_id);
			//$member_pwd  = mysqli_real_escape_string($link,$member_pwd);
			//$shopping_area  = mysqli_real_escape_string($link,$shopping_area);
			//$store_type  = mysqli_real_escape_string($link,$store_type);

			$Personid = trim(stripslashes($Person_id));
		
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			if ($Personid != "") {	
				$sql = $sql." and person_id='".$Personid."'";
			}
			if ($Insurance_no != "") {	
				$sql = $sql." and insurance_no='".$Insurance_no."'";
			}
			if ($Remote_insuance_no != "") {	
				$sql = $sql." and remote_insuance_no='".$Remote_insuance_no."'";
			}

			if ($result = mysqli_query($link, $sql)){
				if (mysqli_num_rows($result) > 0){
					// login ok
					// user id 取得
					$mid=0;
					while($row = mysqli_fetch_array($result)){
						$mid = $row['mid'];
						$pid_pic = $row['pid_pic'];
						//$base64_f1 = base64_encode($pid_pic);
						$base64_f1 = decrypt($key,$pid_pic);
					}
					$mid = (int)str_replace(",", "", $mid);

					//$data1 = file_get_contents($target_file);
					//$base64_f2 = base64_encode($image);
		
					//比對
					$uriBase2 = 'http://127.0.0.1/faceengine/api/faceCompare.php';
					//$uriBase2 = 'http://3.37.63.32/faceengine/api/faceCompare.php';
					$fields2 = [
						'image_file1'         => $base64_f1,
						'image_file2'         => $base64_f2
					];
					
					$fields_string2 = http_build_query($fields2);	
					$ch2 = curl_init();
					curl_setopt($ch2,CURLOPT_URL, $uriBase2);
					curl_setopt($ch2,CURLOPT_POST, true);
					curl_setopt($ch2,CURLOPT_POSTFIELDS, $fields_string2);
					curl_setopt($ch2,CURLOPT_RETURNTRANSFER, true); 
					//execute post
					$result2 = curl_exec($ch2);		

					$IsSuccess2 = "";
					$obj2 = json_decode($result2, true) ;
				
					$IsSuccess2 = $obj2['IsSuccess'];
					//echo $result2;
					if  ($IsSuccess2 == "true"){
						$confidence = doubleval($obj2['confidence']);
						//echo $confidence;	

						if ($confidence >= 0.45) {		//0.5
							//echo "人臉比對完成！同一人(confidence=".$confidence.")";		
							$data["status"]="true";
							$data["code"]="0x0200";
							$data["responseMessage"]="照片比對相同!";
							$data["confidence"]=$confidence;
							$sql = "Insert into facecomparelog (Person_id,  confidence, updatetime) values ('$Person_id','$confidence', NOW()  )";
							mysqli_query($link, $sql);
						}else{
							//echo "人臉比對完成！不同一人(confidence=".$confidence.")";		
							$data["status"]="false";
							$data["code"]="0x0201";
							$data["responseMessage"]="照片比對不相同!";
							$data["confidence"]=$confidence;
							//$face1 = addslashes($pid_pic);
							//$face2 = addslashes($data2image);
							//$face1 = $pid_pic;
							//$face2 = addslashes(encrypt($key,base64_encode($data2image)));
							$sql = "Insert into facecomparelog (Person_id, confidence, updatetime) values ('$Personid','$confidence', NOW()  )";
							mysqli_query($link, $sql);
						}
						//exit;
					}else{
						//echo "no face detect!";
						//$errmsg = $IsSuccess2;
						//echo $errmsg;
						//exit;
						$data["status"]="false";
						$data["code"]="0x0207";
						$data["responseMessage"]="沒有偵測到人臉!";							
							//$face1 = $pid_pic;
							//$face2 = addslashes(encrypt($key,base64_encode($data2image)));
							$sql = "Insert into facecomparelog (Person_id, confidence, updatetime) values ('$Personid','$confidence', NOW()  )";
							mysqli_query($link, $sql);
					}

				}else{
					$data["status"]="false";
					$data["code"]="0x0206";
					$data["responseMessage"]="身分證資料不存在!";						
				}
			}else {
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
		//echo "參數錯誤 !";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
	}
?>