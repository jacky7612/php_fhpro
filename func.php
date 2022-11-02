<?php
	include("def.php");
	include("insuranceclass.php");
	include("log.php");
	include("wjson.php");
	include("wpdf.php");
	include("funcCallAPI.php");
	include("resize-class.php");
	include("security_tools.php");
	include("db_tools.php");
	include("funcCore.php");
	include("accessDB.php");
	/*
	proposer：要保人
	insured：被保人  
	legalRepresentative：法定代理人
	agentOne:業務
	*/
	
	// 取得status ASCII編碼 private
	function getChar4Step($val)
	{
		$ArrChar = str_split($val);
		$ret["char"]  = ord($ArrChar[0]);
		$ret["value"] = intval($ArrChar[1]);
		if ($ret["char"] >= 65 && $ret["char"] <= 90)
		{
			return $ret;
		}
		$ret["char"]  = -1;
		$ret["value"] = -1;
		return $ret;
	}
	// 判斷是否允許更新遠投保單狀態 private
	function allowUpdateStep($ori_status, $cur_status)
	{
		global $INT_NULL;
		
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
	// 驗證 security token - 看門狗 public
	function protect_api($func_name, $out_str, $token, $Insurance_no, $Remote_insurance_no, $Person_id)
	{
		global $key;
		global $g_test_mode;
		
		$data = array();
		if ($g_test_mode)
		{
			$data["status"] ="true";
			return $data;
		}
		//$headers = apache_request_headers();
		//$token 	 = $headers['Authorization'];
		if (check_header($key, $token) == true)
		{
			wh_log($Insurance_no, $Remote_insurance_no, $func_name." security token succeed", $Person_id);
			$data["status"]			="true";
		}
		else
		{
			$data["status"]			="false";
			$data["code"]			="0x0209";
			$data["responseMessage"]="Invalid token!";
			wh_log($Insurance_no, $Remote_insurance_no, $func_name." security token failure", $Person_id);
			wh_log($Insurance_no, $Remote_insurance_no, $g_exit_symbol.$out_str, $Person_id);
		}
		return $data;
	}
	/*
	// 當資料不齊全時，從資料庫取得
		$ret = get_sales_person_id_if_not_exists($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $Member_name
		if (ret == false)
		{
			//echo "參數錯誤 !";
			$data["status"]="false";
			$data["code"]="0x0203";
			$data["responseMessage"]="API parameter is required!";
			return;
		}
	*/
	// 當資料不齊全時，從資料庫取得 public
	function get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, &$Person_id, &$Role,
												  &$Sales_id, &$Mobile_no, &$Member_name, $close_mysql = true)
	{
		$ret = true;
		if ($Insurance_no 			== '' ||
			$Remote_insurance_no 	== '' ||
			$Person_id 				== '')
		{
			return false;
		}
		wh_log($Insurance_no, $Remote_insurance_no, "do function - get_member_info", $Person_id);
		if ($Mobile_no == "" || $Member_name == "")
		{
			$memb = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id, $link, $close_mysql);
			if ($memb["status"] == "true")
			{
				if ($Member_name == "") $Member_name = $memb["member_name"];
				if ($Mobile_no 	 == "") $Mobile_no 	 = $memb["mobile_no"];
				if ($Role 		 == "") $Role 		 = $memb["role"];
			}
			else
				$ret = false;
		}
		if ($ret && $Sales_id == "")
		{
			wh_log($Insurance_no, $Remote_insurance_no, "do function - get_sales_id", $Person_id);
			$Sales_Id = get_sales_id($Insurance_no, $Remote_insurance_no, $Person_id, $link, $close_mysql);
		}
		
		if ($ret == false)
		{
			wh_log($Insurance_no, $Remote_insurance_no, "do function - get_jsondata_from_jsonlog_table", $Person_id);
			$data = get_jsondata_from_jsonlog_table($Insurance_no, $Remote_insurance_no, $Person_id, $json_data, $link, $close_mysql);
			if ($data["status"] == "true")
			{
				wh_log($Insurance_no, $Remote_insurance_no, "getjson data from jsonlog table succeed", $Person_id);
				$cxInsurance = json_decode($json_data);
				// 取得 json data 中的 RoleInfo 及 其他資訊
				$retJsonMemb = parse_or_print_json_data($cxInsurance, $Insurance_no, $Remote_insurance_no, $Person_id, $Mobile_no, $Sales_id);
				if ($retJsonMemb != null)
				{
					for ($i = 0; $i < count($retJsonMemb); $i++)
					{
						$roleInfo = $retJsonMemb[$i];
						for ($j = 0; $j < count($roleInfo); $j++)
						{
							if ($roleInfo[$j]["idcard"] == $Person_id)
							{
								$Member_name = $roleInfo[$j]["name"];
								$Mobile_no 	 = $roleInfo[$j]["tel"];
								$Role 		 = $roleInfo[$j]["roleKey"];
							}
						}
					}
				}
				$ret = true;
				wh_log($Insurance_no, $Remote_insurance_no, "parse json data succeed", $Person_id);
			}
			else
			{
				wh_log($Insurance_no, $Remote_insurance_no, "do function - "."get_jsondata_from_jsonlog_table result :".$data["responseMessage"], $Person_id);
			}
		}
		return $ret;
	}
	// 取得亂數編碼 public
	function get_random_keys($length)
	{
		//$pattern = "1234567890abcdefghijklmnopqrstuvwxyz";
		//$pattern = "1234567890";
		$key = "";
		$key = random_int(100, 999).random_int(100, 999);
		//for($i=0;$i<$length;$i++){
		//	$key .= $pattern{rand(0,9)};
		//}
		return $key;
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
	// 跨天 public[尚需修正]
	function over_insurance_day($dt_now, $dt_duetime)
	{
		$start_date  = new DateTime($dt_now);
		$since_start = $start_date->diff(new DateTime($dt_duetime));
		$diff_day 	 = $since_start->days;
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
		$ip = get_remote_ip();
		$ip = str_replace('.', '_', $ip);
		$ip = str_replace(':', '_', $ip);
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
	// 取得並儲存臉部照片
	function get_image_content($Insurance_no, $Remote_insurance_no, $Person_id, $base64image, $target_file, $target_file1, $update_member = false)
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
	// 取得並儲存臉部照片(含浮水印)
	function get_image_content_watermark($Insurance_no, $Remote_insurance_no, $Person_id, $base64imageID, $target_file, $target_file1, $target_file2)
	{
		if (save_decode_image($base64imageID, $target_file1, $imageFileType))
		{
			if($base64imageID != '')
			{
				wh_log($Insurance_no, $Remote_insurance_no, "base64image size:".strlen($base64imageID), $Person_id);
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
			if ($img_data[0] < $img_data[1])
			{
				$resizeObj -> resizeImage(400, 600, 'auto'); // *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
			}
			else
			{
				$resizeObj -> resizeImage(600, 400, 'auto');
			}
			$resizeObj->saveImage($target_file, 100); // *** 3) Save image
			unlink($target_file1);

			//add watermark
			$watermark_filename = $g_watermark_src_url;
			$ret = add_watermark($target_file, $watermark_filename, $target_file2);
			if ($ret > 0)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "watermark ok", $Person_id);
			}
				
			$image2 = (encrypt($key,base64_encode(file_get_contents($target_file2))));
			wh_log($Insurance_no, $Remote_insurance_no, "AES encode size:".strlen($image), $Person_id);
			
			unlink($target_file);
			//echo $target_file2;
			unlink($target_file2);
		}
		else
		{
			$image2 = null;
			if ($base64imageID != '')
			{
				wh_log($Insurance_no, $Remote_insurance_no, "save_decode_image failed", $Person_id);
			}
		}
		return $image2;
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
			$uriBase = $g_verify_is_face_apiurl;
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
