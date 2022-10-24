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

	function wh_log($log_msg)
	{
		$log_filename = "./log";
		if (!file_exists($log_filename)) 
		{
			// create directory/folder uploads.
			mkdir($log_filename, 0777, true);
		}
		$log_file_data = $log_filename.'/log_Live_Check' . date('d-M-Y') . '.log';
		// if you don't add `FILE_APPEND`, the file will be erased each time you add a log
		file_put_contents($log_file_data, date("Y-m-d H:i:s")."  ------  ".$log_msg . "\n", FILE_APPEND);
	} 
	function save_decode_image($image, $filename)
	{
		$file = fopen($filename, "w");
		if($file <=0) return 0;
		$data = base64_decode($image);
		if(strlen($data) <=0) return 0;
		fwrite($file, $data);
		fclose($file);

		return 1;
	}
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: ''; // for update status
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: ''; // for update status
	$Person_id = isset($_POST['Person_id']) ? $_POST['Person_id'] : '';
	//$Person_id = "{$_REQUEST["Person_id"]}";
	$Action_id = isset($_POST['Action_id']) ? $_POST['Action_id'] : '';
	//$Action_id = "{$_REQUEST["Action_id"]}";
	$base64image =  isset($_POST['Action_Pic']) ? $_POST['Action_Pic'] : '';

	$Person_id = check_special_char($Person_id);
	$Action_id = check_special_char($Action_id);

	//$log = $Person_id.";".$Action_id;
	//wh_log($log);
	//echo $Person_id;
	//echo $Action_id;
	//exit;

	if (($Person_id != '') &&
		($Action_id != ''))
	{
		//$image = addslashes(file_get_contents($_FILES['Pid_Pic']['tmp_name'])); 

		$date = date_create();
		$file_name = guid();   //date_timestamp_get($date);
		$target_dir = "/var/www/html/member/api/uploads/";
		//$target_file = $target_dir . basename($_FILES["Action_Pic"]["name"]);
		//$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		$target_file = $target_dir . $file_name;// . "." . $imageFileType;
		$target_file1 = $target_dir . $file_name . "_1";// . $imageFileType;
		
		//if (move_uploaded_file($_FILES["Action_Pic"]["tmp_name"], $target_file1)) {
		if (save_decode_image($base64image, $target_file1))
		{
			//$resizeObj = new resize($target_file1);
		 
			// *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
			//$resizeObj -> resizeImage(400, 600, 'auto');
		 
			// *** 3) Save image
			//$resizeObj -> saveImage($target_file, 100);
			
			//unlink($target_file1);
			//echo "OK";
			//$data2 = file_get_contents($target_file);
			//$data2 = file_get_contents($_FILES['Pid_Pic']['tmp_name']);
			
			//$base64_f2 = base64_encode($data2);
			//unlink($target_file);
			
			$data2 = file_get_contents($target_file1);
			$base64_f2 = base64_encode($data2);
			unlink($target_file1);			
		}
		
		//$image = file_get_contents($_FILES['Pid_Pic']['tmp_name']); 
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Person_id  = mysqli_real_escape_string($link,$Person_id);
			$Action_id  = mysqli_real_escape_string($link,$Action_id);
			
			$Personid = trim(stripslashes($Person_id));
			$Actionid = trim(stripslashes($Action_id));
			
			//$member_pwd  = mysqli_real_escape_string($link,$member_pwd);
			//$shopping_area  = mysqli_real_escape_string($link,$shopping_area);
			//$store_type  = mysqli_real_escape_string($link,$store_type);
			
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			if ($Personid != "") {	
				$sql = $sql." and person_id='".$Personid."'";
			}

			if ($result = mysqli_query($link, $sql)){
				if (mysqli_num_rows($result) > 0){
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
								$uriBase2 = 'http://127.0.0.1/faceengine/api/faceEyeState.php';
								break;
							case "1":		//臉部角度辨識
								$uriBase2 = 'http://127.0.0.1/faceengine/api/facePosState.php';
								break;
							case "2":
								$uriBase2 = 'http://127.0.0.1/faceengine/api/facePosState.php';
								break;
							default:
								$uriBase2 = 'http://127.0.0.1/faceengine/api/facePosState.php';
						}

						$fields2 = [
							'image_file1'         => $base64_f2
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
						if ($Action == "OK"){	
							$data["status"]="true";
							$data["code"]="0x0200";
							$data["responseMessage"]="動作相符!";
						} else {
							$data["status"]="false";
							$data["code"]="0x0201";
							$data["responseMessage"]="動作不相符!";
							
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

				} else {
					$data["status"]="false";
					$data["code"]="0x0206";
					$data["responseMessage"]="身分證資料不存在!";						
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