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
	$Person_id 		= isset($_POST['Person_id']) 	? $_POST['Person_id'] 	: '';
	$Mobile_no 		= isset($_POST['Mobile_no']) 	? $_POST['Mobile_no'] 	: '';
	$Member_name 	= isset($_POST['Member_name']) 	? $_POST['Member_name'] : '';
	$FCM_Token 		= isset($_POST['FCM_Token']) 	? $_POST['FCM_Token'] 	: '';
	$base64image 	= isset($_POST['Pid_Pic']) 		? $_POST['Pid_Pic'] 	: '';
	$imageFileType 	= "jpg";

	$Person_id = check_special_char($Person_id);
	$Mobile_no = check_special_char($Mobile_no);
	$Member_name = check_special_char($Member_name);
	$FCM_Token = check_special_char($FCM_Token);
	
	//$Person_id = "{$_REQUEST["Person_id"]}";
	//$Mobile_no = "{$_REQUEST["Mobile_no"]}";
	//$Member_name = "{$_REQUEST["Member_name"]}";
	//$FCM_Token = "{$_REQUEST["FCM_Token"]}";

	//$image_name = addslashes($_FILES['image']['name']);
	//$sql = "INSERT INTO `product_images` (`id`, `image`, `image_name`) VALUES ('1', '{$image}', '{$image_name}')";
	//if (!mysql_query($sql)) { // Error handling
	//    echo "Something went wrong! :("; 
	//}

	if (($Person_id 	!= '') &&
		($Mobile_no 	!= '') &&
		($Member_name 	!= '') )
	{
		$date 		= date_create();
		$file_name 	= guid(); //date_timestamp_get($date);
		$target_dir = "/var/www/html/member/api/uploads/";
		// $target_dir = "../uploads/";
		// $target_file = $target_dir . basename($_FILES["Pid_Pic"]["name"]);
		// $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		$target_file 	= $target_dir . $file_name;// . "." . $imageFileType;
		$target_file1 	= $target_dir . $file_name . "_1";//." . $imageFileType;

		// if (move_uploaded_file($_FILES["Pid_Pic"]["tmp_name"], $target_file1)) {
		if (save_decode_image($base64image, $target_file1, $imageFileType)) {
			rename($target_file, $target_file.".".$imageFileType);
			rename($target_file1, $target_file1.".".$imageFileType);
			
			$target_file = $target_file.".".$imageFileType;
			$target_file1 = $target_file1.".".$imageFileType;

			$resizeObj = new resize($target_file1);		 
			$img_data = getimagesize($target_file1);
			if ($img_data[0] < $img_data[1]){
			// *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
				$resizeObj -> resizeImage(400, 600, 'auto');
			} else {
				$resizeObj -> resizeImage(600, 400, 'auto');
			}
		 
			// *** 3) Save image
			$resizeObj -> saveImage($target_file, 100);
			
			unlink($target_file1);
			//echo "OK";
			//$image = addslashes(file_get_contents($target_file));//for DB
			//encrypt
			$image = addslashes(encrypt($key,base64_encode(file_get_contents($target_file))));
			$image1 = file_get_contents($target_file);
			//$data2 = file_get_contents($_FILES['Pid_Pic']['tmp_name']);
			//$base64_f2 = base64_encode($data2);
			unlink($target_file);
		} else {
			$image = null;
		}
		
		//$image = addslashes(file_get_contents($_FILES['Pid_Pic']['tmp_name'])); //SQL Injection defence!
		//$image = file_get_contents($_FILES['Pid_Pic']['tmp_name']);
		//frank ,先確認是否人臉, 若否回傳非人臉,請重拍
		if($image1 != null)
		{
			$base64image = base64_encode($image1);
			$uriBase = 'http://127.0.0.1/faceengine/api/faceDetect.php';
			//$uriBase = 'http://3.37.63.32/faceengine/api/faceDetect.php';
			$fields = [
				'image_file1'         => $base64image,
			];
			
			$fields_string = http_build_query($fields);	
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, $uriBase);
			curl_setopt($ch,CURLOPT_POST, true);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
			//execute post
			$result = curl_exec($ch);		

			$IsSuccess = "";
			$obj = json_decode($result, true) ;
		
			$IsSuccess = $obj['IsSuccess'];
			//echo $result2;
			if  ($IsSuccess == "true"){
				;//continue to add memeber
			}
			else
			{
				$data["status"]="false";
				$data["code"]="0x0205";
				$data["responseMessage"]="人臉無法辨識, 請重新辨識";							
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
				exit;
			}
		}
		try {
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
			
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			if ($Person_id != "") {	
				$sql = $sql." and person_id='".$Personid."'";
			}

			if ($result = mysqli_query($link, $sql)){
				if (mysqli_num_rows($result) == 0){
					$mid=0;
					try {
						$sql2 = "INSERT INTO `memberinfo` (`person_id`,`mobile_no`,`member_name`, `notificationToken`,`pid_pic`, `member_trash`, `inputdttime`) VALUES ('$Personid','$Mobileno','$Membername','$FCMToken','{$image}', 0,NOW())";
						mysqli_query($link,$sql2) or die(mysqli_error($link));
						//echo "user data change ok!";
						$data["status"]="true";
						$data["code"]="0x0200";
						$data["responseMessage"]="身份資料建檔成功!";		
						
					} catch (Exception $e) {
						$data["status"]="false";
						$data["code"]="0x0202";
						$data["responseMessage"]="Exception error!";							
					}
				}else{
					$data["status"]="false";
					$data["code"]="0x0201";
					$data["responseMessage"]="已經有相同身份證資料!";						
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
	} else {
		//echo "need mail and password!";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));			
	}
?>