<?php
	include("log.php");
	include("wjson.php");
	include("funcCallAPI.php");
	include("db_tools.php");
	include("resize-class.php"); 
	include("security_tools.php");
	include("accessDB.php");
	
	const $INT_NULL = 999999;
	// 取得status ASCII編碼 private
	function getChar4Step($val, $char)
	{
		$ArrChar = str_split($val);
		$ret["char"]  = ord($ArrChar[0]);
		$ret["value"] = intval($ArrChar[1]);
		if ($ret["char"] >= 65 && $ret["char"] <= 90)
		{
			return $ret;
		}
		return $INT_NULL;
	}
	// 判斷是否允許更新遠投保單狀態 private
	function allowUpdateStep($ori_status, $cur_status)
	{
		if (!(strlen($ori_status) >= 2 && strlen($cur_status) >= 2)) return $INT_NULL;
		
		$ret_ori = getChar4Step($ori_status);
		$ret_cur = getChar4Step($cur_status);
		if (
			($ret_ori["char"]  <  $ret_cur["char"]) ||
			($ret_ori["char"]  == $ret_cur["char"]  && $ret_ori["value"] <  $ret_cur["value"])
		   )
		{
			return true;
		}
		return false;
	}
	
	
	// 警告時間 public
	function alarm_insurance_duetime($dt_now, $dt_duetime, $min_minutes = 30, $max_minutes = 50)
	{
		$start_date = new DateTime($dt_now);
		$since_start = $start_date->diff(new DateTime($dt_duetime));
		$minutes = $since_start->days * 24 * 60;
		$minutes += $since_start->h * 60;
		$minutes += $since_start->i;
		return ($minutes >= $min_minutes && $minutes <= $max_minutes);
	}
	// 超過時間 public
	function over_insurance_duetime($dt_now, $dt_duetime, $max_hour = 12)
	{
		$start_date = new DateTime($dt_now);
		$since_start = $start_date->diff(new DateTime($dt_duetime));
		$minutes = $since_start->days * 24 * 60;
		$minutes += $since_start->h * 60;
		$minutes += $since_start->i;
		return ($minutes > $max_hour * 60);
	}
	function over_insurance_day($dt_now, $dt_duetime)
	{
		$start_date = new DateTime($dt_now);
		$since_start = $start_date->diff(new DateTime($dt_duetime));
		$diff_day = $since_start->days;
		return ($diff_day > 0);
	}
	// 取得遠端用戶的ip public
	function get_remote_ip()
	{
		if (!empty($_SERVER["HTTP_CLIENT_IP"]))
		{
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		}
		elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))
		{
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}
		else
		{
			$ip = $_SERVER["REMOTE_ADDR"];
		}
		return $ip;
	}
	function get_remote_ip_underline()
	{
		$ip = get_remote_ip_with_dot();
		$ip = str_replace('.', '_', $ip);
		return $ip;
	}
	
	// 儲存臉部照片
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
		$log = "access base64decode size:".strlen($data);
		wh_log($log);
		fwrite($file, $data);
		fclose($file);
		switch (exif_imagetype($filename)) {
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
	
	// 取得臉部照片
	function get_image_content1($Insurance_no, $Remote_insurance_no, $Person_id, $base64image, $target_file, $target_file1, $update_member = false)
	{
		$image1 = null;
		if($base64image!='') 
		{
			wh_log($Insurance_no, $Remote_insurance_no, "base64image size:".strlen($base64image), $Person_id);
		}
		// if (move_uploaded_file($_FILES["Pid_Pic"]["tmp_name"], $target_file1)) {
		if (save_decode_image($base64image, $target_file1, $imageFileType)) {
			if (!update_member)
				rename($target_file, $target_file.".".$imageFileType);
			rename($target_file1, $target_file1.".".$imageFileType);
			
			$target_file = $target_file.".".$imageFileType;
			$target_file1 = $target_file1.".".$imageFileType;

			$resizeObj = new resize($target_file1);		 
			$img_data = getimagesize($target_file1);
			if ($img_data[0] < $img_data[1]){// *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
				$resizeObj->resizeImage(400, 600, 'auto');
			}
			else
			{
				$resizeObj->resizeImage(600, 400, 'auto');
			}
			$resizeObj->saveImage($target_file, 100);// *** 3) Save image
			
			unlink($target_file1);
			//echo "OK";
			//$image = addslashes(file_get_contents($target_file));//for DB
			if (!update_member)
			{
				$image = addslashes(encrypt($key, base64_encode(file_get_contents($target_file))));
				//$data2 = file_get_contents($_FILES['Pid_Pic']['tmp_name']);
				//$base64_f2 = base64_encode($data2);
			}
			else
			{
				//encrypt
				$image = (encrypt($key,base64_encode(file_get_contents($target_file))));
				wh_log($Insurance_no, $Remote_insurance_no, "AES encode size:".strlen($image), $Person_id);
			}
			$image1 = file_get_contents($target_file);
			unlink($target_file);
		} else {
			$image = null;
			if($base64image!='')
			{
				wh_log($Insurance_no, $Remote_insurance_no, "save_decode_image1 Failed", $Person_id);
			}
		}
		return $image1;
	}
	// 取得臉部照片
	function get_image_content2($Insurance_no, $Remote_insurance_no, $Person_id, $base64imageID, $target_file, $target_file1)
	{
		if (save_decode_image($base64imageID, $target_file1, $imageFileType))
		{
			if($base64imageID != '')
			{
				wh_log($Insurance_no, $Remote_insurance_no, "base64image size:".strlen($base64image), $Person_id);
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
			if ($img_data[0] < $img_data[1]) {
			// *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
				$resizeObj -> resizeImage(400, 600, 'auto');
			} else {
				$resizeObj -> resizeImage(600, 400, 'auto');
			}
			// *** 3) Save image
			$resizeObj->saveImage($target_file, 100);
			unlink($target_file1);

			//add watermark
			$watermark_filename = "/var/www/html/member/api/watermark.png";
			$ret = add_watermark($target_file, $watermark_filename, $target_file2);
			if ($ret > 0)
			{
				wh_log("watermark ok");
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
			if($base64imageID != '') {
				wh_log($Insurance_no, $Remote_insurance_no, "save_decode_image failed", $Person_id);
			}
		}
	}
	
	// 先確認是否人臉, 若否回傳非人臉,請重拍
	function verify_is_face($image1)
	{
		$data = array();
		$data["status"]			= "true";
		$data["code"]			= "0x0200";
		$data["responseMessage"]= "辨識人臉成功";
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
				$data["status"]			= "false";
				$data["code"]			= "0x0205";
				$data["responseMessage"]= "無法辨識為人臉, 請重新辨識";
			}
		}
		return $data;
	}
	
	// 照片加入浮水印 public
	function add_watermark($from_filename, $watermark_filename, $save_filename)
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
	
	// 照片儲入Nas事先工作 public
	function will_save2nas_prepare($Insurance_no, $Remote_insurance_no, $Person_id)
	{
		$data = array();
		$data["status"]			 = "true";
		$data["code"]			 = "0x0200";
		$data["responseMessage"] = "Create NAS Folder Success";
		$data["filename"] 		 = "";
		//$date = date("Ymd");
		$date = date("Y")."/".date("Ym")."/".date("Ymd");
		//$foldername ="/dis_app/dis_idphoto/".$date; 
		$foldername = NASDir().$date; 
		if (create_folder($foldername) == false)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0205";
			$data["responseMessage"]= "NAS fail!";
			$filename = "";
		}
		if ($data["status"] == "true")
		{
			$filename = $foldername."/".$Insurance_no."_".$Personid."_".$front;
			$data["filename"] = $filename;
		}
		wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"], $Person_id);
		return $data;
	}
	// 照片儲入Nas public
	function save_image2nas($Insurance_no, $Remote_insurance_no, $Person_id, $filename, $image)
	{
		try
		{
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
		}
		catch (Exception $e)
		{
			wh_log($Insurance_no, $Remote_insurance_no, "saveImagetoNas failed:".$e->getMessage(), $Person_id);
			return -4;
		}
		return 1;
	}
		
	// get idpic use - start	
	$saveType = "DB";
	function getSaveType()
	{
		global $saveType;
		return $saveType;
	}
	function setSaveType($Type)
	{
		global $saveType;
		$saveType = $Type;
	}
	function getpidpic2($conn,$p_id,$keys,$front, $order_no)
	{
		try {
			
			//oid,order_no,sales_id,person_id,mobile_no,member_type,order_status,log_date
			$sql3 = "SELECT * FROM idphoto ";
			//echo $sql;
			$pid = trim(stripslashes($p_id));
			
			if ($pid != "") {	
				$sql3 = $sql3." where person_id='".$pid."'";
			}
			//echo $sql3;
			if($order_no != "") {
				$sql3 .= " and insurance_id='".$order_no."'";
			}
			
			
			$pidpic2 = "";
			//echo $saveType;
			if ($result2 = mysqli_query($conn, $sql3)){
				if (mysqli_num_rows($result2) > 0){
					
					while($row2 = mysqli_fetch_array($result2)){
						
						if($row2['saveType']=='NAS'){
							setSaveType("NAS");
							//$saveType = "NAS";
							if ($front == "0"){
									if ($row2['frontpath'] != null) {
										$fp=fopen($row2['frontpath'], "r");
										$out=fread($fp, filesize($row2['frontpath']));
										fclose($fp);
										//echo decrypt($keys,$out); 
										//echo $row2['frontpath'];
										
										$pidpic2 =  decrypt($keys,$out); //decrypt($keys,$row2['front']);
									}else{
										$pidpic2 = "";
									}
								}
							if ($front == "1"){
								if ($row2['backpath'] != null) {
									$fp=fopen($row2['backpath'], "r");
									$out=fread($fp, filesize($row2['backpath']));
									fclose($fp);
										//echo decrypt($keys,$out); 
									$pidpic2 =  decrypt($keys,$out);
								}else{
									$pidpic2 = "";
								}							
							}
						}
						else
						{
							//echo "DB";
							setSaveType("DB");
							if ($front == "0"){
								if ($row2['front'] != null) {
									$pidpic2 = decrypt($keys,$row2['front']);
								}else{
									$pidpic2 = "";
								}
							}
							if ($front == "1"){
								if ($row2['back'] != null) {
									$pidpic2 = decrypt($keys,$row2['back']);
								}else{
									$pidpic2 = "";
								}						
							}
						}
						
						break;
					}
				}else {
					//有可能是舊的方式,沒有儲存 insurance_id
						$sql3 = "SELECT * FROM idphoto ";
						//echo $sql;					
						if ($pid != "") {	
							$sql3 = $sql3." where person_id='".$pid."'";
						}				
						if ($result2 = mysqli_query($conn, $sql3)){
							if (mysqli_num_rows($result2) > 0){							
								while($row2 = mysqli_fetch_array($result2)){
									if($row2['saveType']=='NAS'){
										setSaveType("NAS");
										//$saveType = "NAS";
										if ($front == "0"){
												if ($row2['frontpath'] != null) {
													$fp=fopen($row2['frontpath'], "r");
													$out=fread($fp, filesize($row2['frontpath']));
													fclose($fp);
													//echo decrypt($keys,$out); 
													//echo $row2['frontpath'];
													
													$pidpic2 =  decrypt($keys,$out); //decrypt($keys,$row2['front']);
												}else{
													$pidpic2 = "";
												}
											}
										if ($front == "1"){
											if ($row2['backpath'] != null) {
												$fp=fopen($row2['backpath'], "r");
												$out=fread($fp, filesize($row2['backpath']));
												fclose($fp);
													//echo decrypt($keys,$out); 
												$pidpic2 =  decrypt($keys,$out);
											}else{
												$pidpic2 = "";
											}							
										}
									}
									else
									{
										//echo "DB";
										setSaveType("DB");
										if ($front == "0"){
											if ($row2['front'] != null) {
												$pidpic2 = decrypt($keys,$row2['front']);
											}else{
												$pidpic2 = "";
											}
										}
										if ($front == "1"){
											if ($row2['back'] != null) {
												$pidpic2 = decrypt($keys,$row2['back']);
											}else{
												$pidpic2 = "";
											}						
										}
									}
									
									break;
									
								}
							}
							else
							{
								$pidpic2 = "";
							}
						}
					
				}
			}else {
				$pidpic2 = "";
			}
		} catch (Exception $e) {
			$pidpic2="";
		}	
		return $pidpic2;	
	}
	function getuserList($conn,$order_no,$keys){
		try {
			
			$orderno = trim(stripslashes($order_no));

			$sql2 = "( SELECT a.*,b.member_name FROM orderlog a inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql2 = $sql2." where a.order_no='".$orderno."' and a.member_type = 1 and a.order_status in ('D0','D1') order by log_date desc limit 1 )";
			$sql2 = $sql2." UNION ( SELECT a.*,b.member_name FROM orderlog a inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql2 = $sql2." where a.order_no='".$orderno."' and a.member_type = 2 and a.order_status in ('D0','D1') order by log_date desc limit 1 )";
			$sql2 = $sql2." UNION ( SELECT a.*,b.member_name FROM orderlog a inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql2 = $sql2." where a.order_no='".$orderno."' and a.member_type = 3 and a.order_status in ('D0','D1') order by log_date desc limit 1 )";

			//echo $sql2;

			$fields1 = array();
			if ($result2 = mysqli_query($conn, $sql2)){

				
				if (mysqli_num_rows($result2) > 0){
					//$mid=0;
					$order_status="";
					while($row2 = mysqli_fetch_array($result2)){
						$person_id = $row2['person_id'];
						//$member_name = $row2['member_name'];
						$member_name = decrypt($keys,stripslashes($row2['member_name']));

						$member_types = str_replace(",", "", $row2['member_type']);
						switch ($member_types) {
							case "1":
								$membertype = "要保人";
								break;
							case "2":
								$membertype = "被保人";
								break;
							case "3":
								$membertype = "法定代理人";
								break;
							default:
								$membertype = "";
						}
						$pid = str_replace(",", "", $person_id);
						$pname = str_replace(",", "", $member_name);
						$pid = check_special_char($pid);
						$pname = check_special_char($pname);
						
						$data2 = [
							'userId'       			=> $pid,   
							'userName'       		=> $pname, 
							'userType'   			=> $membertype,   
							'frontIdPhoto'    		=> getpidpic2($conn,$pid,$keys,"0", $order_no),
							'backIdPhoto'    		=> getpidpic2($conn,$pid,$keys,"1", $order_no),
							'saveType'    			=> getSaveType()
						];
						array_push($fields1, $data2);
					}
				}else{
					$fields1=null;
				}
			}else{

				$fields1=null;
			}
		} catch (Exception $e) {

			$fields1=null;
		
		}	
		return $fields1;
	}
	// get idpic use - end
?>
