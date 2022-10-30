<?php
	include("log.php");
	include("funcCore.php");
	
	/*
	proposer：要保人
	insured：被保人  
	legalRepresentative：法定代理人
	agentOne:業務
	*/
	
	// 將資料寫入資料表 :jsonlog
	function write_jsonlog_table($Insurance_no, $Remote_insurance_no, $Person_id, $json_data, $status_code, $remote_ip4filename = "")
	{
		$link = null;
		try
		{
			if ($remote_ip4filename == "")
			{
				$remote_ip4filename = get_remote_ip_underline();
			}
			$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					
			$sql = "SELECT * FROM jsonlog where 1=1 ";
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insuranceno			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insurance_no	);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) == 0)
				{
					$sql2 = "INSERT INTO `jsonlog` (`insurance_no`,`remote_insurance_no`,`json_data`,`order_status`,`createtime`,`updatetime`) VALUES ('$Insuranceno','$Remote_insuranceno','$json_data','$status_code',NOW(),NOW())";
					mysqli_query($link,$sql2) or die(mysqli_error($link));
					wh_log($Insurance_no, $Remote_insurance_no, "write json data to mysql jsonlog table succeed", $Person_id);
				}
				else
				{
					wh_log($Insurance_no, $Remote_insurance_no, "(!) mysql jsonlog table that json data had exists", $Person_id);
				}
				$data["status"]="true";
				$data["code"]="0x0200";
				$data["responseMessage"]="資料庫操作-新增json資料成功!";
			}
			mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
		}
		catch (Exception $e)
		{
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="資料庫操作-Exception error!";
			wh_log($Insurance_no, $Remote_insurance_no, "(X) write json data to mysql jsonlog table failure :".$e->getMessage(), $Person_id);
		}
		finally
		{
			try
			{
				if ($link != null)
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			catch(Exception $e)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "(X) disconnect mysql jsonlog table failure :".$e->getMessage(), $Person_id);
			}
		}
	}
	// 取得 Sales_id
	function get_sales_id($Insurance_no, $Remote_insurance_no, $link = null)
	{
		$Sales_id = "";
		try
		{
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			
			$sql = "SELECT * FROM memberinfo where member_trash=0 and insurance_no= '".$Insurance_no."' and remote_insurance_no= '".$Remote_insurance_no."'";
			$sql = $sql.merge_sql_string_if_not_empty("role", "agentOne");
			if ($result = mysqli_query($link, $sql_person))
			{
				if (mysqli_num_rows($result) > 0)
				{
					// login ok
					// user id 取得
					$mid=0;
					while($row = mysqli_fetch_array($result)){
						$mid = $row['mid'];
						$Sales_id = $row['person_id'];
					}
					wh_log($Insurance_no, $Remote_insurance_no, "get sales_id memberinfo table result :found", $Person_id);
				}
				else
				{
					wh_log($Insurance_no, $Remote_insurance_no, "get sales_id memberinfo table result : not found", $Person_id);
				}
			}
		}
		catch (Exception $e)
		{
			wh_log($Insurance_no, $Remote_insurance_no, "(X) get sales_id memberinfo table Exception error :".$e->getMessage(), $Person_id);
			$Sales_id = "";
		}
		finally
		{
			try
			{
				if ($link != null)
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			catch(Exception $e)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "(X) get sales_id memberinfo table - disconnect mysql jsonlog table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $Sales_id;
	}
	// 取得 member info
	function get_member_info($Insurance_no, $Remote_insurance_no, $link = null)
	{
		$data = array();
		try
		{
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			$sql = "SELECT * FROM memberinfo where member_trash=0 and insurance_no= '".$Insurance_no."' and remote_insurance_no= '".$Remote_insurance_no."'";
			$sql_person = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);
			if ($result = mysqli_query($link, $sql_person))
			{
				if (mysqli_num_rows($result) > 0)
				{
					// login ok
					// user id 取得
					$mid=0;
					while($row = mysqli_fetch_array($result)){
						$mid = $row['mid'];
						$data["person_id"] 		= $row['person_id'];
						$data["member_name"] 	= $row['member_name'];
						$data["mobile_no"] 		= $row['mobile_no'];
						$data["role"] 			= $row['role'];
						$data["pid_pic"] 		= $row['pid_pic'];
					}
					wh_log($Insurance_no, $Remote_insurance_no, symbol4log."get memberinfo table result :found", $Person_id);
				}
				else
				{
					wh_log($Insurance_no, $Remote_insurance_no, symbol4log."get memberinfo table result : not found", $Person_id);
				}
			}
		}
		catch (Exception $e)
		{
			wh_log($Insurance_no, $Remote_insurance_no, "(X) get memberinfo table Exception error :".$e->getMessage(), $Person_id);
			$Sales_id = "";
		}
		finally
		{
			try
			{
				if ($link != null)
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			catch(Exception $e)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "(X) get memberinfo table - disconnect mysql memberinfo table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $data;
	}
	// 變更(Insert/Update)遠投保單狀態 public
	function modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $Statuscode, $link = null)
	{
		if (($Insurance_no 			!= '') &&
			($Remote_insurance_no 	!= '') &&
			($Sales_id 				!= '') &&
			($Person_id 			!= '') &&
			($Mobile_no 			!= '')) {
			try {
				if ($link == null)
				{
					$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				}
				$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
				$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no	);
				$Sales_id  				= mysqli_real_escape_string($link, $Sales_id			);
				$Person_id  			= mysqli_real_escape_string($link, $Person_id			);
				$Mobile_no  			= mysqli_real_escape_string($link, $Mobile_no			);
				$Member_type  			= mysqli_real_escape_string($link, $Member_type			);
				$Status_code  			= mysqli_real_escape_string($link, $Status_code			);

				$Insuranceno 		 	= trim(stripslashes($Insurance_no));
				$Remote_insuranceno 	= trim(stripslashes($Remote_insurance_no));
				$Salesid 			 	= trim(stripslashes($Sales_id));
				$Personid 			 	= trim(stripslashes($Person_id));
				$Mobileno 			 	= trim(stripslashes($Mobile_no));
				$Membertype 		 	= trim(stripslashes($Member_type));
				$Statuscode 		 	= trim(stripslashes($Status_code));

				//$Personid = encrypt($key,($Personid));
				$Mobileno = addslashes(encrypt($key,($Mobileno)));
				
				$sql = "SELECT * FROM orderinfo where order_trash=0 ";
				$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insuranceno			);
				$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insurance_no	);
				$sql = $sql.merge_sql_string_if_not_empty("sales_id"			, $Sales_id 			);
				$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Person_id 			);
				$sql = $sql.merge_sql_string_if_not_empty("mobile_no"			, $Mobile_no 			);
				$sql = $sql.merge_sql_string_if_not_empty("role"				, $Membertype			);
				
				if ($result = mysqli_query($link, $sql))
				{
					if (mysqli_num_rows($result) == 0)
					{
						$mid=0;
						if ($Mobile_no == '')
						{
							$data["status"]			= "false";
							$data["code"]			= "0x0203";
							$data["responseMessage"]= "操作：新增狀態-手機號碼不可為空白!"
						}
						else
						{
							if ($Statuscode == "") $Statuscode = "00";
							try
							{
								$sql2 = "INSERT INTO `orderinfo` (`insurance_no`,`remote_insurance_no`,`sales_id`,`person_id`,`mobile_no`,`member_type`, `order_status`, `order_trash`, `inputdttime`) VALUES ('$Insuranceno','$Remote_insuranceno','$Salesid','$Personid','$Mobileno',$Membertype,'$Statuscode', 0,NOW())";
								mysqli_query($link,$sql2) or die(mysqli_error($link));

								$sql2 = "INSERT INTO `orderlog` (`insurance_no`,`remote_insurance_no`,`sales_id`,`person_id`,`mobile_no`,`member_type`, `order_status`, `log_date`) VALUES ('$Insuranceno','$Remote_insuranceno','$Salesid','$Personid','$Mobileno',$Membertype,'$Statuscode',NOW())";
								mysqli_query($link,$sql2) or die(mysqli_error($link));
								
								//echo "user data change ok!";
								$data["status"]="true";
								$data["code"]="0x0200";
								$data["responseMessage"]="操作：新增狀態-新增資料完成!";
							}
							catch (Exception $e)
							{
								$data["status"]="false";
								$data["code"]="0x0202";
								$data["responseMessage"]="操作：新增狀態-Exception error!";
							}
						}
					}
					else
					{
						$data["status"]="false";
						$data["code"]="0x0201";
						$data["responseMessage"]="操作：新增狀態-已經有相同要保流水序號的資料!";	
						
						$ret = updateOrderState($link, $result, $Insuranceno, $Remote_insuranceno, $Salesid, $Personid, $Mobileno, $Membertype, $Statuscode);
						if ($ret == 0) {
							$data["status"]			 = "true";
							$data["code"]			 = "0x0200";
							$data["responseMessage"] = "操作：更新狀態-完成!";
						} else if ($ret == 1) {
							$data["status"]			 = "false";
							$data["code"]			 = "0x0202";
							$data["responseMessage"] = "操作：更新狀態-Exception error!";
						} else if ($ret == 2) {
							$data["status"]			 = "false";
							$data["code"]			 = "0x0201";
							$data["responseMessage"] = "操作：更新狀態-不存在此要保流水序號的資料!";
						}
					}
				} else {
					$data["status"]="false";
					$data["code"]="0x0204";
					$data["responseMessage"]="操作：更新狀態-SQL fail!";					
				}
			} catch (Exception $e) {
				$data["status"]="false";
				$data["code"]="0x0202";
				$data["responseMessage"]="操作：新增狀態-Exception error!";
			}
			finally
			{
				try
				{
					if ($link != null)
						mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				}
				catch(Exception $e)
				{
					wh_log($Insurance_no, $Remote_insurance_no, "(X) 操作：更新狀態 - disconnect mysql orderinfo table failure :".$e->getMessage(), $Person_id);
				}
			}
			header('Content-Type: application/json');
			return $data;
		} else {
			$data["status"]="false";
			$data["code"]="0x0203";
			$data["responseMessage"]="操作：access status-API parameter is required!";
			header('Content-Type: application/json');
			return $data;			
		}
	}
	// Update遠投保單狀態 private
	function updateOrderState($link, $result, $Insuranceno, $Remote_insuranceno, $Salesid, $Personid, $Mobileno, $Membertype, $Statuscode)
	{
		$ret = 0;
		if (mysqli_num_rows($result) > 0) {
			$flag = 0;
			try {
				while($row = mysqli_fetch_array($result)){
					$oldorder_status = $row['order_status'];
					$oldorderstatus = str_replace(",", "", $oldorder_status);
					//$membername = $row['member_name'];
				}
				
				if (allowUpdateStep($oldorderstatus, $Statuscode)) {
					$sql2 = "update `orderinfo` set `order_status`='$Statuscode' ,`updatedttime`=NOW() where insurance_no='$Insuranceno' and remote_insurance_no='$Remote_insuranceno' and sales_id='$Salesid' and person_id='$Personid' and order_trash=0";
					if ($Mobile_no != "") {	
						$sql = $sql." and mobile_no='".$Mobileno."'";
					}
					if ($Membertype != "") {	
						$sql = $sql." and mobile_no='".$Membertype."'";
					}
					mysqli_query($link, $sql2) or die(mysqli_error($link));
					$flag = 1;
				}
				if ($flag == 1) {
					$sql2 = "INSERT INTO `orderlog` (`insurance_no`,`remote_insurance_no`,`sales_id`,`person_id`,`member_type`, `order_status`, `log_date`";
					if ($Mobile_no != "") {	
						$sql2 = $sql2.",`mobile_no`";
					}
					if ($Membertype != "") {	
						$sql2 = $sql2.",`member_type`";
					}
					$sql2 = $sql2.") VALUES ('$Insuranceno','$Remote_insuranceno','$Salesid','$Personid','$Statuscode',NOW())";
					if ($Mobile_no != "") {	
						$sql2 = $sql2.",'$Mobileno'";
					}
					if ($Membertype != "") {	
						$sql2 = $sql2.",'$Membertype'";
					}
					mysqli_query($link,$sql2) or die(mysqli_error($link));
				}
				//echo "user data change ok!";
				$ret = 0;
			}
			catch (Exception $e)
			{
				$ret = 1;
				wh_log($Insurance_no, $Remote_insurance_no, "(X) update order state - disconnect mysql orderlog table failure :".$e->getMessage(), $Person_id);
			}
		} else {
			$ret = 2;
		}
		return $ret;
	}
	
	// 變更(Insert/Update)member public
	function modify_member($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, &$status_code, $link = null, $when_end_close_connect = true)
	{
		wh_log($Insurance_no, $Remote_insurance_no, "modify member entry <-", $Person_id);
		try
		{
			$date = date_create();
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}

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
			
			$sql = "SELECT * FROM memberinfo where insurance_no='".$Insurance_no."' and remote_insurance_no='".$Remote_insurance_no."' and member_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);
			wh_log($Insurance_no, $Remote_insurance_no, "create memberinfo table prepare", $Person_id);
			$sql2 = "";
			if ($result = mysqli_query($link, $sql))
			{
				wh_log($Insurance_no, $Remote_insurance_no, "create member search", $Person_id);
				if (mysqli_num_rows($result) == 0)
				{
					$mid = 0;
					try
					{
						$sql2 = "INSERT INTO `memberinfo` (`insurance_no`,`remote_insurance_no`,`person_id`,`mobile_no`,`member_name`, `notificationToken`,`pid_pic`, `member_trash`, `inputdttime`) VALUES ('$Insurance_no','$Remote_insurance_no','$Personid','$Mobileno','$Membername','$FCMToken','{$image}', 0,NOW())";
						mysqli_query($link,$sql2) or die(mysqli_error($link));
						//echo "user data change ok!";
						$data["status"]			= "true";
						$data["code"]			= "0x0200";
						$data["responseMessage"]= "身份資料建檔成功!";
						$status_code 			= "C2";
					}
					catch (Exception $e)
					{
						$data["status"]			= "false";
						$data["code"]			= "0x0202";
						$data["responseMessage"]= "Exception error!";
						$status_code 			= "";
					}
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "已經有相同身份證資料!";	
					$status_code 			= "";
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";
				$status_code 			= "";
			}
			if ($status_code != "")
				$data = Modify_order_State($Insurance_no, $Remote_insurance_no, $Personid, $Sales_id, $Mobileno, "C2");
			
			wh_log($Insurance_no, $Remote_insurance_no, symbol4log."create memberinfo table result :".$data["responseMessage"].$sql2, $Person_id);
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
				if ($link != null && $when_end_close_connect == true)
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			catch(Exception $e)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "(X) modify member - disconnect mysql memberinfo table failure :".$e->getMessage(), $Person_id);
			}
		}
		wh_log($Insurance_no, $Remote_insurance_no, "modify member exit ->", $Person_id);
		return $data;
	}
	// Update idphoto public
	function update_idphoto($Insuranceno, $Remote_insuranceno, $Personid, &$status_code, $link = null, $when_end_close_connect = true)
	{
		try
		{
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);
				mysqli_query($link,"SET NAMES 'utf8'");
			}
			$Person_id  = mysqli_real_escape_string($link,$Person_id);
			$front  	= mysqli_real_escape_string($link,$front	 );
			$Personid 	= trim(stripslashes($Person_id));
			
			$sql = "SELECT * FROM memberinfo where insurance_no='".$Insurance_no."' and remote_insurance_no='".$Remote_insurance_no."' and member_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					wh_log($Insurance_no, $Remote_insurance_no, "person_id verify ok", $Person_id);
					try {
						// 將照片儲存到 NAS
						$data = will_save2nas_prepare($Insurance_no, $Remote_insurance_no, $Person_id);
						if ($data["status"] == "false")
							return $data;
						
						$filename = $data["filename"];
						$retimg = save_image2nas($Insurance_no, $Remote_insurance_no, $Person_id, $filename, $image2);
						
						// 
						$log = "";
						if ($retimg > 0)
						{
							wh_log($Insurance_no, $Remote_insurance_no, "save_image2nas Success", $Person_id);
							$sql = "SELECT * from `idphoto` where person_id = '".$Personid."' and insurance_no= '".$Insurance_no."' and remote_insurance_no= '".$Remote_insurance_no."'";
							$ret = mysqli_query($link, $sql);
							if (mysqli_num_rows($ret) > 0)
							{
								if($front=="0")
								{
									$sql2 = "UPDATE  `idphoto` set `saveType`='NAS', `frontpath` = '$filename', `updatedtime` = NOW() where `person_id`='".$Personid."' and insurance_no= '".$Insurance_no."' ";
									$log = "UPDATE idphoto frontpath ".$filename;
									
								}
								else
								{
									$sql2 = "UPDATE  `idphoto` set `saveType`='NAS', `backpath` = '$filename', `updatedtime` = NOW() where `person_id`='".$Personid."' and insurance_no= '".$Insurance_no."' ";	
									$log = "UPDATE  idphoto backpath ".$filename;
								}
							} else {
								if($front=="0")
								{
									$sql2 = "INSERT INTO  `idphoto` ( `person_id`, `insurance_no`, `frontpath` , `saveType`, `updatedtime`) VALUES ('$Personid', '$Insurance_no', '$filename', 'NAS', NOW()) ";
									$log = "INSERT idphoto frontpath ".$filename;
								}
								else
								{
									$sql2 = "INSERT INTO  `idphoto` ( `person_id`, `insurance_no`, `backpath` , `saveType`, `updatedtime`)  VALUES ('$Personid', '$Insurance_no', '$filename', 'NAS', NOW()) ";
									$log = "INSERT idphoto backpath ".$filename;
								}
							}
							//echo $sql2;
							mysqli_query($link, $sql2) or die(mysqli_error($link));
						}
						else
						{
							wh_log($Insurance_no, $Remote_insurance_no, "save_image2nas Failed", $Person_id);
							$data["status"]			= "false";
							$data["code"]			= "0x0206";
							$data["responseMessage"]= "寫入NAS 失敗! (".$retimg.")";	
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
							exit;							
							
						}
						//echo "user data change ok!";
						$data["status"]			= "true";
						$data["code"]			= "0x0200";
						$data["responseMessage"]= "身分證圖檔".$front."上傳成功!";
						wh_log($Insurance_no, $Remote_insurance_no, $log."\r\n".$data["responseMessage"], $Person_id);
					} catch (Exception $e) {
						wh_log($Insurance_no, $Remote_insurance_no, "Exception error!:".$e->getMessage(), $Person_id);
						$data["status"]			= "false";
						$data["code"]			= "0x0202";
						$data["responseMessage"]= "Exception error!";
					}
				}
				else
				{
					wh_log($Insurance_no, $Remote_insurance_no, "無相同身份證資料,無法更新!".$Personid, $Person_id);
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "無相同身份證資料,無法更新!".$Personid;
					$status_code 			= "";
				}
			} else {
				wh_log($Insurance_no, $Remote_insurance_no, "SQL fail!", $Person_id);
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";
				$status_code 			= "";
			}
		}
		catch (Exception $e)
		{
			wh_log($Insurance_no, $Remote_insurance_no, "Exception error2!:".$e->getMessage(), $Person_id);
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";
			$status_code 			= "";
		}
		finally
		{
			try
			{
				if ($link != null && $when_end_close_connect == true)
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			catch(Exception $e)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "(X) disconnect mysql idphoto table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $data;
	}
	// Update member public
	function update_member($Insuranceno, $Remote_insuranceno, $Personid, &$status_code, $link = null, $when_end_close_connect = true)
	{
		try
		{
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);
				mysqli_query($link,"SET NAMES 'utf8'");
			}

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
		
			$sql = "SELECT * FROM memberinfo where insurance_no='".$Insurance_no."' and remote_insurance_no='".$Remote_insurance_no."' and member_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);

			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{						
					$mid = 0;
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
						$data["status"]			= "true";
						$data["code"]			= "0x0200";
						$data["responseMessage"]= "更新身份證資料完成!";
					}
					catch (Exception $e)
					{
						$log = "Exception2 error!:".$e->getMessage();
						$data["status"]			= "false";
						$data["code"]			= "0x0202";
						$data["responseMessage"]= "Exception error!";	
						$status_code 			= "";						
					}
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "無相同身份證資料,更新失敗!";
					$status_code 			= "";
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";	
				$log 					= "SQL2 fail!";
				$status_code 			= "";
			}
			wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"], $Person_id);
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";
			$status_code 			= "";
			wh_log($Insurance_no, $Remote_insurance_no, "(X) Exception3 error :".$e->getMessage(), $Person_id);
		}
		finally
		{
			try
			{
				if ($link != null && $when_end_close_connect == true)
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			catch(Exception $e)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "(X) disconnect mysql memberinfo table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $data;
	}
?>