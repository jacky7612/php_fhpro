<?php
	include("db_tools.php");
	include("resize-class.php"); 
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
	
	date_default_timezone_set("Asia/Taipei");
	ini_set('memory_limit','-1');
	$glogfile ="";
	function wh_log($log_msg)
	{
		global $glogfile;	
		$log_filename = "/var/www/html/member/api/uploads/log/";
		if (!file_exists($log_filename)) 
		{
			// create directory/folder uploads.
			mkdir($log_filename, 0777, true);
		}
		//$log_file_data = $glogfile;// . '.log';
		// if you don't add `FILE_APPEND`, the file will be erased each time you add a log
		file_put_contents($glogfile, date("Y-m-d H:i:s")."  ------  ".$log_msg . "\n", FILE_APPEND);

	}
	function createFolder($name)
	{
		//$log_filename = "./log";
		if (!file_exists($name)) 
		{
			// create directory/folder uploads.
			return mkdir($name, 0777, true);
		}
		else
			return true;
	} 
	function saveImagetoNas($filename, $image)
	{
		try {
			$fp = fopen($filename, "w");
			$orgLen = strlen($image);
			if($orgLen<=0)
			{
				fclose($fp);
				return -1;
			}
			
			$len = fwrite($fp, $image, strlen($image));
			if($orgLen!=$len)
			{
				fclose($fp);
				return -2;
			}
			
			fclose($fp);
		/*	
			//Verify
			$fp = fopen($filename, "r");
			$rImg = fread($fp, filesize($filename));
			if($orgLen!=strlen($rImg))
			{
				fclose($fp);
				return -3;		
			}

			fclose($fp);
		*/
		} catch(Exception $e) {
			$log = "saveImagetoNas failed:".$e->getMessage();
			wh_log($log);
			return -4;
		}
		return 1;
	}
	function watermark($from_filename, $watermark_filename, $save_filename)
	{
		$allow_format = array('jpeg', 'png', 'gif');
		$sub_name = $t = '';

		// 原圖
		$img_info = getimagesize($from_filename);
		$width    = $img_info['0'];
		$height   = $img_info['1'];
		$mime     = $img_info['mime'];

		list($t, $sub_name) = explode('/', $mime);
		if ($sub_name == 'jpg')
			$sub_name = 'jpeg';

		if (!in_array($sub_name, $allow_format))
		{
			$log = "watermark1 failed";
			wh_log($log);				
			return false;
		}

		$function_name = 'imagecreatefrom' . $sub_name;
		$image     = $function_name($from_filename);

		// 浮水印
		$img_info = getimagesize($watermark_filename);
		$w = $w_width  = $img_info['0'];
		$h = $w_height = $img_info['1'];
		//echo $w.":";
		//echo $h."\n";
		//echo $width.":";
		//echo $height."\n";
		$w_mime   = $img_info['mime'];

		list($t, $sub_name) = explode('/', $w_mime);
		if (!in_array($sub_name, $allow_format))
		{
			$log = "watermark2 failed";
			wh_log($log);			
			return false;
		}

		$function_name = 'imagecreatefrom' . $sub_name;
		$watermark = $function_name($watermark_filename);

		$watermark_pos_x = $width/2;//$width  - $w_width;
		$watermark_pos_y = $height/2;//$height - $w_height;
		//echo $watermark_pos_x.":";
		//echo $watermark_pos_y."\n";
		// imagecopymerge($image, $watermark, $watermark_pos_x, $watermark_pos_y, 0, 0, $w_width, $w_height, 100);

		// 浮水印的圖若是透明背景、透明底圖, 需要用下述兩行
		imagesetbrush($image, $watermark);
		imageline($image, $watermark_pos_x, $watermark_pos_y, $watermark_pos_x, $watermark_pos_y, IMG_COLOR_BRUSHED);

		return imagejpeg($image, $save_filename);
	}

	function save_decode_image($image, $filename, &$imageFileType)
	{
		$file = fopen($filename, "w");
		
		if($file <=0) return 0;
		$data = base64_decode($image);
		if(strlen($data) <=0) 
		{
			unlink($filename);
			return 0;
		}
		$log = "base64decode size:".strlen($data);
		wh_log($log);
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
	function setLogName($Insurance_no)
	{
		global $glogfile;
		$glogfile = "/var/www/html/member/api/uploads/log/".'log_'.date('Y-m-d').'_'.$Insurance_no.'_'.time().'.log';
	}
	
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
	setLogName($Insurance_no);

	$Insurance_no 	= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 		= check_special_char($Person_id);
	$Mobile_no 		= check_special_char($Mobile_no);
	$Member_name 	= check_special_char($Member_name);
	$FCM_Token 		= check_special_char($FCM_Token);
	$front 			= check_special_char($front);

	$log = "insurance_no:".$Insurance_no." "."remote_insurance_no:".$Remote_insurance_no." "."Person_id:".$Person_id." "."Mobile_no:".$Mobile_no." "."Member_name:".$Member_name." "."FCM_Token:".$FCM_Token." "."front:".$front;
	wh_log($log);


	if (($Person_id 			!= '') &&
		($front 				!= '') &&
		($Insurance_no 			!= '') &&
		($Remote_insurance_no 	!= '') &&
		(strlen($Person_id)>1))
	{
		//echo $Insurance_no ;
		$date = date_create();
		$file_name = guid();   //date_timestamp_get($date);
		$target_dir = "/var/www/html/member/api/uploads/";
		//$target_dir = "../uploads/";
		//$target_file = $target_dir . basename($_FILES["Pid_PicID"]["name"]);
		//$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
		$target_file = $target_dir . $file_name;// . "." . $imageFileType;
		$target_file1 = $target_dir . $file_name . "_1";//." . $imageFileType;
		$target_file2 = $target_dir . $file_name . "_2";//." . $imageFileType;
	
		//if (move_uploaded_file($_FILES["Pid_PicID"]["tmp_name"], $target_file1)) {
		if($base64imageID!='') 
		{
			$log = "base64imageID size:".strlen($base64imageID);
			wh_log($log);
		}
		if (save_decode_image($base64imageID, $target_file1, $imageFileType))
		{
			if($base64imageID!='')
			{
				$log = "save_decode_image success";
				wh_log($log);				
			}
			rename($target_file1, $target_file1.".".$imageFileType);
			
			$target_file  = $target_file.".".$imageFileType;
			$target_file1 = $target_file1.".".$imageFileType;
			$target_file2 = $target_file2.".".$imageFileType;
			
			//$image2 = addslashes(encrypt($key,base64_encode(file_get_contents($target_file))));
			//$image2 = addslashes(base64_encode(file_get_contents($target_file)));
			//$image2 = addslashes(file_get_contents($target_file));
			//unlink($target_file);

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
			unlink($target_file1);

			//add watermark
			$watermark_filename = "/var/www/html/member/api/watermark.png";
			$ret=watermark($target_file, $watermark_filename, $target_file2);
			if($ret>0)
			{
				$log = "watermark ok";
				wh_log($log);
			}
				
			$image2 = (encrypt($key,base64_encode(file_get_contents($target_file2))));
			$log = "AES encode size:".strlen($image2);
			wh_log($log);
			//
				
			unlink($target_file);
			//echo $target_file2;
			unlink($target_file2);
		} else {
				$image2 = null;
			if($base64imageID!='')
			{
			$log = "save_decode_image failed";
			wh_log($log);		
			}
		}
			
		//$image2 = addslashes(file_get_contents($_FILES['Pid_PicID']['tmp_name']));
			
		try {
			$link2 = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link2,"SET NAMES 'utf8'");

			$Person_id  = mysqli_real_escape_string($link2,$Person_id);
			$front  = mysqli_real_escape_string($link2,$front);
			$Personid = trim(stripslashes($Person_id));
			
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			if ($Personid != "") {	
				$sql = $sql." and person_id='".$Personid."'";
			}

			if ($result = mysqli_query($link2, $sql)){
				if (mysqli_num_rows($result) > 0)
				{
					$log = "person_id verify ok";
					wh_log($log);						
					try {						
						//$date = date("Ymd");
						$date = date("Y")."/".date("Ym")."/".date("Ymd");
						//$foldername ="/dis_app/dis_idphoto/".$date; 
						$foldername =NASDir().$date; 
						if(createFolder($foldername)==false)
						{
							$log = "Create NAS Folder Failed";
							wh_log($log);								
							$data["status"]="false";
							$data["code"]="0x0205";
							$data["responseMessage"]="NAS fail!";	
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
							exit;							
						}
						$log = "Create NAS Folder Success";
						wh_log($log);
						$filename = $foldername."/".$Insurance_no."_".$Personid."_".$front;
						$retimg = saveImagetoNas($filename, $image2);
						if($retimg>0)
						{
							$log = "saveImagetoNas Success";
							wh_log($log);
							$sql = "SELECT * from `idphoto` where person_id = '".$Personid."' and insurance_no= '".$Insurance_no."'";
							$ret = mysqli_query($link2, $sql);
							if (mysqli_num_rows($ret) > 0)
							{
								if($front=="0")
								{
									$sql2 = "UPDATE  `idphoto` set `saveType`='NAS', `frontpath` = '$filename', `updatedtime` = NOW() where `person_id`='".$Personid."' and insurance_no= '".$Insurance_no."' ";
									$log = "UPDATE idphoto frontpath ".$filename;
									wh_log($log);
								}
								else
								{
									$sql2 = "UPDATE  `idphoto` set `saveType`='NAS', `backpath` = '$filename', `updatedtime` = NOW() where `person_id`='".$Personid."' and insurance_no= '".$Insurance_no."' ";	
									$log = "UPDATE  idphoto backpath ".$filename;
									wh_log($log);							
								}
							} else {
								if($front=="0")
								{
									$sql2 = "INSERT INTO  `idphoto` ( `person_id`, `insurance_no`, `frontpath` , `saveType`, `updatedtime`) VALUES ('$Personid', '$Insurance_no', '$filename', 'NAS', NOW()) ";
									$log = "INSERT idphoto frontpath ".$filename;
									wh_log($log);
								}
								else
								{
									$sql2 = "INSERT INTO  `idphoto` ( `person_id`, `insurance_no`, `backpath` , `saveType`, `updatedtime`)  VALUES ('$Personid', '$Insurance_no', '$filename', 'NAS', NOW()) ";
									$log = "INSERT idphoto backpath ".$filename;
									wh_log($log);
								}
							}
							//echo $sql2;
							mysqli_query($link2,$sql2) or die(mysqli_error($link2));
						}
						else
						{
							$log = "saveImagetoNas Failed";
							wh_log($log);
							$data["status"]="false";
							$data["code"]="0x0206";
							$data["responseMessage"]="寫入NAS 失敗! (".$retimg.")";	
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
							exit;							
							
						}
						
						//echo "user data change ok!";
						$data["status"]="true";
						$data["code"]="0x0200";
						$data["responseMessage"]="身分證圖檔".$front."上傳成功!";	
						
						
					} catch (Exception $e) {
						$log = "Exception error!:".$e->getMessage();
						wh_log($log);
						$data["status"]="false";
						$data["code"]="0x0202";
						$data["responseMessage"]="Exception error!";				
						header('Content-Type: application/json');
						echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
						exit;						
					}
				} else {
					$log = "無相同身份證資料,無法更新!".$Personid;
					wh_log($log);
					$data["status"]="false";
					$data["code"]="0x0201";
					$data["responseMessage"]="無相同身份證資料,無法更新!".$Personid;	
					header('Content-Type: application/json');
					echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
					exit;					
				}
			} else {
				$log = "SQL fail!";
				wh_log($log);
				$data["status"]="false";
				$data["code"]="0x0204";
				$data["responseMessage"]="SQL fail!";	
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
				exit;				
			}
			mysqli_close($link2);
		} catch (Exception $e) {
			$log = "Exception error2!:".$e->getMessage();
			wh_log($log);
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="Exception error!";		
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

		if($base64image!='') 
		{
			$log = "base64image size:".strlen($base64image);
			wh_log($log);
		}
		if (save_decode_image($base64image, $target_file1, $imageFileType))
		{
			if($base64image!='')
			{
				$log = "save_decode_image1 success";
				wh_log($log);
			}		
			rename($target_file1, $target_file1.".".$imageFileType);
			
			$target_file = $target_file.".".$imageFileType;
			$target_file1 = $target_file1.".".$imageFileType;
			
			$resizeObj = new resize($target_file1);
		 
			$img_data = getimagesize($target_file1);
			//echo $img_data[0];
			//echo $img_data[1];
			if ($img_data[0] < $img_data[1]){
				// *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
				$resizeObj -> resizeImage(400, 600, 'auto');
			}else{
				$resizeObj -> resizeImage(600, 400, 'auto');
			}
					 
			// *** 3) Save image
			$resizeObj -> saveImage($target_file, 100);
			
			unlink($target_file1);
			//echo "OK";
			//$image = addslashes(file_get_contents($target_file)); //for db_tools
			//encrypt
			$image = (encrypt($key,base64_encode(file_get_contents($target_file))));
			$log = "AES encode size:".strlen($image);
			wh_log($log);			
						
			$image1 = file_get_contents($target_file);
			
			//$data2 = file_get_contents($_FILES['Pid_Pic']['tmp_name']);
			
			//$base64_f2 = base64_encode($data2);
			unlink($target_file);
		} else {
			$image = null;
			if($base64image!='')
			{
				$log = "save_decode_image1 Failed";
				wh_log($log);
			}
		}
		if ($image1 != null)
		{
			//frank ,先確認是否人臉, 若否回傳非人臉,請重拍
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
			$log = "call facedetect";
			wh_log($log);
			//execute post
			$result = curl_exec($ch);		
			$log = "facedetect ret".$result;
			wh_log($log);
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
		
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			if ($Personid != "") {	
				$sql = $sql." and person_id='".$Personid."'";
			}

			if ($result = mysqli_query($link, $sql)){
				if (mysqli_num_rows($result) > 0){						
					$mid=0;
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
						$data["status"]="true";
						$data["code"]="0x0200";
						$data["responseMessage"]="更新身份證資料完成!";		
						
					} catch (Exception $e) {
						$log = "Exception2 error!".$e->getMessage();
						wh_log($log);
						$data["status"]="false";
						$data["code"]="0x0202";
						$data["responseMessage"]="Exception error!";							
					}
				}else{
					$data["status"]="false";
					$data["code"]="0x0201";
					$data["responseMessage"]="無相同身份證資料,更新失敗!";	
					$log = "無相同身份證資料,更新失敗!";
					wh_log($log);
				}
			} else {
				$data["status"]="false";
				$data["code"]="0x0204";
				$data["responseMessage"]="SQL fail!";	
				$log = "SQL2 fail!";
				wh_log($log);
			}
			mysqli_close($link);
		} catch (Exception $e) {
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="Exception error!";	
			$log = "Exception3 error!".$e->getMessage();	
			wh_log($log);
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
		$log = "API parameter is required!";
		wh_log($log);
	}
	
?>