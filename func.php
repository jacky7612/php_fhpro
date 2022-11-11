<?php
	include("def.php");
	include("policyclass.php");
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
		$data = result_message("true", "0x0200", "Succeed!", "");
		if ($g_test_mode)
		{
			return $data;
		}
		//$headers = apache_request_headers();
		//$token 	 = $headers['Authorization'];
		if (check_header($key, $token) == true)
		{
			wh_log_watch_dog($Insurance_no, $Remote_insurance_no, $func_name." security token succeed", $Person_id);
		}
		else
		{
			$data = result_message("false", "0x0205", "Invalid token!", "");
			wh_log_watch_dog($Insurance_no, $Remote_insurance_no, $func_name."(X) security token failure \r\n".$g_exit_symbol.$out_str, $Person_id);
		}
		return $data;
	}
	
	// 取的保單所有關係人員 public
	function get_role_from_json(&$link, $Insurance_no, $Remote_insurance_no, &$Person_id, $close_mysql = true)
	{
		$retJsonRole = array();
		wh_log($Insurance_no, $Remote_insurance_no, "do function - get_jsondata_from_jsonlog_table", $Person_id);
		$data = get_jsondata_from_jsonlog_table($link, $Insurance_no, $Remote_insurance_no, $Person_id, $json_data, $close_mysql);
		if ($data["status"] == "true")
		{
			wh_log($Insurance_no, $Remote_insurance_no, "getjson data from jsonlog table succeed", $Person_id);
			$cxInsurance = json_decode($json_data);
			// 取得 json data 中的 RoleInfo 及 其他資訊
			$retJsonMemb = parse_or_print_json_data($cxInsurance, $Insurance_no, $Remote_insurance_no, $Person_id, $Mobile_no, $Sales_id);
			if ($retJsonMemb != null)
			{
				$retJsonRole = $retJsonMemb;
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
		return $retJsonRole;
	}
	// 當資料不齊全時，從資料庫取得 public
	function get_salesid_personinfo_if_not_exists(&$link, $Insurance_no, $Remote_insurance_no, &$Person_id, &$Role,
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
			$memb = get_member_info($link, $Insurance_no, $Remote_insurance_no, $Person_id, $close_mysql);
			if ($memb["status"] == "true")
			{
				if ($memb["member_name"] != "") $Member_name = $memb["member_name"];
				if ($memb["mobile_no"] 	 != "") $Mobile_no 	 = $memb["mobile_no"];
				if ($memb["role"] 		 != "") $Role 		 = $memb["role"];
			}
			else
				$ret = false;
		}
		if ($ret && $Sales_id == "")
		{
			wh_log($Insurance_no, $Remote_insurance_no, "do function - get_sales_id", $Person_id);
			$ret = get_sales_id($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $close_mysql);
		}
		
		if ($ret == false)
		{
			wh_log($Insurance_no, $Remote_insurance_no, "do function - get_jsondata_from_jsonlog_table", $Person_id);
			$data = get_jsondata_from_jsonlog_table($link, $Insurance_no, $Remote_insurance_no, $Person_id, $json_data, $close_mysql);
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
	// 跨天 public
	function over_insurance_day($dt_duetime)
	{
		$str_now = date('Y'.'m'.'d');
		$date = new DateTime($dt_duetime);
		$str_duetime = $date->format('Ymd');
		return ($str_now != $str_duetime);
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
	function base64ToImage($Insurance_no, $Remote_insurance_no, $Person_id, $imageData, $Dst_filename, &$imageFileType)
	{
		$ret = 1;
		$fileName = $Dst_filename;
		try
		{
			list($type, $imageData) = explode(';', $imageData);
			list(,$extension) 		= explode('/', $type	 );
			list(,$imageData)      	= explode(',', $imageData);
			$imageFileType = $extension;
			$fileName = $fileName.'.'.$extension;
			$imageData = base64_decode($imageData);
			file_put_contents($fileName, $imageData);
		}
		catch (Exception $e)
		{
			wh_log($Insurance_no, $Remote_insurance_no, "(X) base64ToImage Exception error :".$e->getMessage(), $Person_id);
			$ret = 0;
		}
		return $ret;
	}
	// 取得並儲存臉部照片
	function get_image_content($Insurance_no, $Remote_insurance_no, $Person_id, $base64image, $target_file, $target_file1, $update_member = false)
	{
		global $g_encrypt;
		
		$ret_image = null;
		if ($base64image != '') 
		{
			wh_log($Insurance_no, $Remote_insurance_no, "base64image size:".strlen($base64image), $Person_id);
		}
		
		if (base64ToImage($Insurance_no, $Remote_insurance_no, $Person_id, $base64image, $target_file, $imageFileType))
		{
			$target_file = $target_file.".".$imageFileType;
			$target_file1 = $target_file1.".".$imageFileType;
			copy($target_file, $target_file1);

			$resizeObj = new resize($target_file1);		 
			$img_data = getimagesize($target_file1);
			if ($img_data[0] < $img_data[1]) // *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
			{
				$resizeObj->resizeImage(400, 600, 'auto');
			}
			else
			{
				$resizeObj->resizeImage(600, 400, 'auto');
			}
			$resizeObj->saveImage($target_file1, 100);// *** 3) Save image
			
			if (!$update_member)
			{
				$image = addslashes(encrypt_string_if_not_empty($g_encrypt["image"], base64_encode(file_get_contents($target_file))));
				wh_log($Insurance_no, $Remote_insurance_no, "addslashes encode size:".strlen($image), $Person_id);
			}
			else
			{
				//encrypt
				$image = encrypt_string_if_not_empty($g_encrypt["image"], base64_encode(file_get_contents($target_file)));
				wh_log($Insurance_no, $Remote_insurance_no, "AES encode size:".strlen($image), $Person_id);
			}
			$ret_image = file_get_contents($target_file1);
			unlink($target_file1); // delete file
			unlink($target_file); // delete file
		}
		else
		{
			if($base64image!='')
			{
				wh_log($Insurance_no, $Remote_insurance_no, "base64ToImage Failed", $Person_id);
			}
		}
		return $ret_image;
	}
	// 取得並儲存臉部照片(含浮水印)
	function get_image_content_watermark($Insurance_no, $Remote_insurance_no, $Person_id, $base64imageID, $target_file, $target_file1, $target_file2)
	{
		global $g_encrypt;
		global $g_watermark_src_url;
		
		if (base64ToImage($Insurance_no, $Remote_insurance_no, $Person_id, $base64imageID, $target_file1, $imageFileType))
		{
			if ($base64imageID != '')
			{
				wh_log($Insurance_no, $Remote_insurance_no, "base64image size:".strlen($base64imageID), $Person_id);
			}
			
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
				$resizeObj->resizeImage(400, 600, 'auto'); // *** 2) Resize image (options: exact, portrait, landscape, auto, crop)
			}
			else
			{
				$resizeObj->resizeImage(600, 400, 'auto');
			}
			$resizeObj->saveImage($target_file, 100); // *** 3) Save image

			//add watermark
			$watermark_filename = $g_watermark_src_url;
			$ret = add_watermark($target_file, $watermark_filename, $target_file2);
			if ($ret > 0)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "watermark ok", $Person_id);
			}
			
			$image2 = (encrypt_string_if_not_empty($g_encrypt["image"], base64_encode(file_get_contents($target_file2))));
			wh_log($Insurance_no, $Remote_insurance_no, "AES encode size:".strlen($image2), $Person_id);
			
			unlink($target_file1);
			unlink($target_file);
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
	function verify_is_face($src_image)
	{
		global $g_verify_is_face_apiurl;
		
		$data = array();
		$data = result_message("true", "0x0200", "辨識人臉成功", "");
		if ($src_image != null)
		{
			$base64image = base64_encode($src_image);
			$uriBase = $g_verify_is_face_apiurl;
			$fields = [
				'image_file1'         => $base64image,
			];
			 
			//execute post
			$fields_string = http_build_query($fields);	
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, $uriBase);
			curl_setopt($ch,CURLOPT_POST, true);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			
			$obj = json_decode($result, true);
			$IsSuccess = "";
			if ($obj != null)
				$IsSuccess = $obj['IsSuccess'];
			//echo $result2;
			if  ($IsSuccess == "true")
			{
			}
			else
			{
				$data = result_message("false", "0x0206", "無法辨識為人臉, 請重新辨識", "");
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
	
	function getuserList(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, $close_mysql = true)
	{
		global $g_encrypt;
		
		try
		{
			$RoleName = "";
			$Insurance_no = trim(stripslashes($Insurance_no));
			$Remote_insurance_no = trim(stripslashes($Remote_insurance_no));

			$sql2 = "( SELECT a.*,b.member_name FROM orderlog a inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql2 = $sql2." where a.insurance_no='".$Insurance_no."' and a.remote_insurance_no='".$Remote_insurance_no."' and a.role = 'proposer' and a.order_status in ('D1','D3') order by log_date desc limit 1 )";
			$sql2 = $sql2." UNION ( SELECT a.*,b.member_name FROM orderlog a inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql2 = $sql2." where a.insurance_no='".$Insurance_no."' and a.remote_insurance_no='".$Remote_insurance_no."' and a.role = 'insured' and a.order_status in ('D1','D3') order by log_date desc limit 1 )";
			$sql2 = $sql2." UNION ( SELECT a.*,b.member_name FROM orderlog a inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql2 = $sql2." where a.insurance_no='".$Insurance_no."' and a.remote_insurance_no='".$Remote_insurance_no."' and a.role = 'legalRepresentative' and a.order_status in ('D1','D3') order by log_date desc limit 1 )";

			$fields1 = array();
			if ($result2 = mysqli_query($link, $sql2))
			{
				if (mysqli_num_rows($result2) > 0)
				{
					//$mid=0;
					$order_status = "";
					while ($row2 = mysqli_fetch_array($result2))
					{
						$person_id = $row2['person_id'];
						//$member_name = $row2['member_name'];
						//$member_name = decrypt_string_if_not_empty($keys, stripslashes($row2['member_name']));
						$member_name = decrypt_string_if_not_empty($g_encrypt["member_name"], stripslashes($row2['member_name']));
						
						$Role 		= str_replace(",", "", $row2['role']);
						$RoleName 	= get_role_name($Role);
						$pid 		= str_replace(",", "", $person_id);
						$pname 		= str_replace(",", "", $member_name);
						$pid 		= check_special_char($pid);
						$pname 		= check_special_char($pname);
						
						$data2 = [
							'userId'       			=> $pid,
							'userName'       		=> $pname, 
							'userType'   			=> $RoleName,
							'frontIdPhoto'    		=> getpidpic2($link, $Insurance_no, $Remote_insurance_no, $Person_id, "0", false),
							'backIdPhoto'    		=> getpidpic2($link, $Insurance_no, $Remote_insurance_no, $Person_id, "1", false),
							'saveType'    			=> getSaveType()
						];
						array_push($fields1, $data2);
					}
				}
			}
		}
		catch (Exception $e)
		{ }
		finally
		{
			if (count($fields1) == 0)
			{
				$retJsonMemb = get_role_from_json($link, $Insurance_no, $Remote_insurance_no, $Person_id, $close_mysql);
				
				if ($retJsonMemb != null)
				{
					for ($i = 0; $i < count($retJsonMemb); $i++)
					{
						$roleInfo = $retJsonMemb[$i];
						for ($j = 0; $j < count($roleInfo); $j++)
						{
							$pid = $roleInfo[$j]["idcard"];
							$pname = $roleInfo[$j]["name"];
							$RoleName = get_role_name($roleInfo[$j]["roleKey"]);
							if ($RoleName != "")
							{
								$data2 = [
									'userId'       			=> $pid,   
									'userName'       		=> $pname, 
									'userType'   			=> $RoleName,   
									'frontIdPhoto'    		=> getpidpic2($link, $Insurance_no, $Remote_insurance_no, $pid, "0", false),
									'backIdPhoto'    		=> getpidpic2($link, $Insurance_no, $Remote_insurance_no, $pid, "1", false),
									'saveType'    			=> getSaveType()
								];
								array_push($fields1, $data2);
							}
						}
					}
				}
			}
		}
		return $fields1;
	}
	// get idpic use - end
	
	function calculate_meeting_count(&$showName, $id, $name_title, $name, $Cur_count)
	{
		if ($id != '')
		{
			if (strlen($showName) <= 0)
				$showName .= "name=".$name_title.$name;
			else
				$showName .= ", ".$name_title.$name;
			$Cur_count ++;
		}
		return $Cur_count;
	}
?>
