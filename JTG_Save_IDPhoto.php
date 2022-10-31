<?php
	include("func.php");
	
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST["Person_id"])			? $_POST["Person_id"]			: '';
	$Front 					= isset($_POST["Front"])				? $_POST["Front"]				: '';//0: front, 1: back
	$PicId 					= isset($_POST["Pid_PicID"])			? $_POST["Pid_PicID"]			: '';

	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id);
	$Front 					= check_special_char($Front);
	
	$status_code 			= "D1";
	
	wh_log($Insurance_no, $Remote_insurance_no, "save idphoto entry <-", $Person_id);
	
	// 驗證 security token
	$headers =  apache_request_headers();
	$token = $headers['Authorization'];
	if(check_header($key, $token)==true)
	{
		wh_log($Insurance_no, $Remote_insurance_no, "security token succeed", $Person_id);
	}
	else
	{
		;//echo "error token";
		$data = array();
		$data["status"]="false";
		$data["code"]="0x0209";
		$data["responseMessage"]="Invalid token!";
		wh_log($Insurance_no, $Remote_insurance_no, "(X) security token failure"."\r\n"."save idphoto exit ->", $Person_id);
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
		return;							
	}
	
	if (($Insurance_no 			!= '' &&
		 $Remote_insurance_no 	!= '' &&
		 $Person_id 			!= '' &&
		 strlen($Person_id)>1) )
	{
		$image = addslashes(encrypt($key, $PicId)); //SQL Injection defence!
		//$image = ($PicId); //SQL Injection defence!
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link, "SET NAMES 'utf8'");

			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no);
			$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no);
			$Person_id  			= mysqli_real_escape_string($link, $Person_id);
			$Front  				= mysqli_real_escape_string($link, $Front);
			
			$Insurance_no 			= trim(stripslashes($Insurance_no));
			$Remote_insurance_no 	= trim(stripslashes($Remote_insurance_no));
			$Personid 				= trim(stripslashes($Person_id));
			
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);
			
			wh_log($Insurance_no, $Remote_insurance_no, "save idphoto table prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$mid = 0;
					while ($row = mysqli_fetch_array($result))
					{
						$mid = $row['mid'];
					}	
					$mid = (int)str_replace(",", "", $mid);				
					try
					{
						$subWhere = "";
						$subWhere = $subWhere.merge_sql_string_if_not_empty("insurance_no"		 , $Insuranceno			);
						$subWhere = $subWhere.merge_sql_string_if_not_empty("remote_insurance_no", $Remote_insurance_no	);
												
						$sql = "SELECT * from `idphoto` where person_id = '".$Personid."'".$subWhere;
						$ret = mysqli_query($link, $sql);
						if (mysqli_num_rows($ret) > 0)
						{
							if ($Front == "0")
								$sql2 = "UPDATE  `idphoto` set `front` = '{$image}', `updatedtime` = NOW() where `person_id`='".$Personid."'".$subWhere;
							else
								$sql2 = "UPDATE  `idphoto` set `back` = '{$image}', `updatedtime` = NOW() where `person_id`='".$Personid."'".$subWhere;
								
						} else {
							if ($Front == "0")
								$sql2 = "INSERT INTO  `idphoto` ( `insurance_no`, `remote_insurance_no`, `person_id`, `front`, `updatedtime`) VALUES ('$Insuranceno', '$Remote_insurance_no', '$Personid', '{$image}', NOW()) ";
							else
								$sql2 = "INSERT INTO  `idphoto` ( `insurance_no`, `remote_insurance_no`, `person_id`, `back` , `updatedtime`)  VALUES ('$Insuranceno', '$Remote_insurance_no', '$Personid', '{$image}', NOW()) ";
						}

						mysqli_query($link, $sql2) or die(mysqli_error($link));
						
						//echo "user data change ok!";
						$data["status"]			= "true";
						$data["code"]			= "0x0200";
						$data["responseMessage"]= "身分證上傳成功!";						
					}
					catch (Exception $e)
					{
						$data["status"]			= "false";
						$data["code"]			= "0x0202";
						$data["responseMessage"]= "Exception error!";
						$status_code 			= "D0";
					}
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "無相同身份證資料,無法更新!".$Person_id;
					$status_code 			= "D0";
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";
				$status_code 			= "D0";
			}
			
			$symbol4log = ($status_code == "D0") ? "(X) "	 : "";
			$sql 		= ($status_code == "D0") ? " :".$sql : "";
			wh_log($Insurance_no, $Remote_insurance_no, symbol4log."query memberinfo table result :".$data["responseMessage"].$sql, $Person_id);
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";
			$status_code 			= "D0";
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
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";	
		$status_code 			= "D0";
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."save idphoto exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>