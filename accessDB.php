<?php
	/*
	proposer：要保人
	insured：被保人  
	legalRepresentative：法定代理人
	agentOne:業務
	*/
	
	// 取得json資料，讀取資料表 :jsonlog
	function get_jsondata_from_jsonlog_table(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, &$json_data, $close_mysql = true, $log_title = "", $log_subtitle = "")
	{
		global $host, $user, $passwd, $database;
	
		$dst_title 		= ($log_title 	 == "") ? $Insurance_no 		: $log_title	;
		$dst_subtitle 	= ($log_subtitle == "") ? $Remote_insurance_no 	: $log_subtitle	;
		$data			= array();
		$data["status"]	= "false";
		$mid 			= "";
		$json_path 		= "";
		try
		{
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				$data = result_connect_error ($link);
				if ($data["status"] == "false") return $data;
				mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			
			$sql = "SELECT * FROM jsonlog where 1=1 ";
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insurance_no	);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					while ($row = mysqli_fetch_array($result))
					{
						$mid 		= $row['id'];
						$json_path 	= $row['json_path'];
					}
					$fp = fopen($json_path, "r");
					$json_data = fread($fp, filesize($json_path));
					fclose($fp);
					//echo $json_data."\r\n\r\n";
					$data = result_message("true", "0x0200", "資料庫操作-query json資料成功!", "");
				}
				else
				{
					$data = result_message("false", "0x0201", "資料庫操作-找不到資料!", "");
					wh_log($dst_title, $dst_subtitle, "(!) ".$data["code"]." jsonlog - mysql jsonlog table that json data not found", $Person_id);
				}
			}
			else
			{
				wh_log($dst_title, $dst_subtitle, "(!) jsonlog - mysql jsonlog table that json data not found", $Person_id);
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "資料庫操作jsonlog - Exception error!", "");
			wh_log($dst_title, $dst_subtitle, "(X) ".$data["code"]." write json data to mysql jsonlog table failure :".$e->getMessage(), $Person_id);
		}
		finally
		{
			try
			{
				if ($link != null && $close_mysql)
				{
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$link = null;
				}
			}
			catch(Exception $e)
			{
				$data = result_message("false", "0x0207", "資料庫操作jsonlog - disconnect mysql-Exception error!", "");
				wh_log($dst_title, $dst_subtitle, "(X) jsonlog - disconnect mysql jsonlog table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $data;
	}
	// 將資料寫入資料表 :jsonlog
	function write_jsonlog_table(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, $json_path, $status_code, $remote_ip4filename = "", $close_mysql = true, $log_title = "", $log_subtitle = "")
	{
		global $host, $user, $passwd, $database;

		$dst_title 		= ($log_title 	 == "") ? $Insurance_no 		: $log_title	;
		$dst_subtitle 	= ($log_subtitle == "") ? $Remote_insurance_no 	: $log_subtitle	;
		$data 			= array();
		$data["status"]	= "true";
		try
		{
			if ($remote_ip4filename == "")
			{
				$remote_ip4filename = get_remote_ip_underline();
			}
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky// 检查连接
				$data = result_connect_error ($link);
				if ($data["status"] == "false") return $data;
				mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			
			$sql = "SELECT * FROM jsonlog where 1=1 ";
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insurance_no	);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) == 0)
				{
					$sql2 = "INSERT INTO `jsonlog` (`insurance_no`,`remote_insurance_no`,`json_path`,`order_status`,`createtime`,`updatetime`) VALUES ('$Insurance_no','$Remote_insurance_no','$json_path','$status_code',NOW(),NOW())";
					mysqli_query($link,$sql2) or die(mysqli_error($link));
					wh_log($dst_title, $dst_subtitle, "jsonlog - write json data to mysql jsonlog table succeed", $Person_id);
				}
				else
				{
					wh_log($dst_title, $dst_subtitle, "(!) jsonlog - mysql jsonlog table that json data had exists", $Person_id);
				}
				$data = result_message("true", "0x0200", "資料庫操作jsonlog-新增資料成功!", "");
			}
			else
			{
				$data = result_message("false", "0x0201", "資料庫操作jsonlog-資料已存在!", "");
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0206", "資料庫操作jsonlog- Exception error!", "");
			wh_log($dst_title, $dst_subtitle, "(X) ".$data["code"]." jsonlog - write json data to mysql jsonlog table failure :".$e->getMessage(), $Person_id);
		}
		finally
		{
			try
			{
				if ($link != null && $close_mysql)
				{
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$link = null;
				}
			}
			catch(Exception $e)
			{
				$data = result_message("false", "0x0207", "get sales_id memberinfo table - Exception error: disconnect!", "");
				wh_log($dst_title, $dst_subtitle, "(X) jsonlog - get sales_id memberinfo table - disconnect mysql jsonlog table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $data;
	}
	// 取得 Sales_id
	function get_sales_id(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, &$Sales_id, $close_mysql = true)
	{
		global $host;
		global $user;
		global $passwd;
		global $database;
		
		$data = array();
		$data["status"] = "true";
		$ret = true;
		try
		{
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				$data = result_connect_error ($link);
				if ($data["status"] == "false") return $ret;
				mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}
			
			$sql = "SELECT * FROM memberinfo where member_trash=0 and insurance_no= '".$Insurance_no."' and remote_insurance_no= '".$Remote_insurance_no."'";
			$sql = $sql.merge_sql_string_if_not_empty("role", "agentOne");
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					// login ok
					// user id 取得
					$mid = 0;
					while ($row = mysqli_fetch_array($result))
					{
						$mid 	  = $row['mid'];
						$Sales_id = $row['person_id'];
					}
					wh_log($Insurance_no, $Remote_insurance_no, "get sales_id memberinfo table result :found", $Person_id);
				}
				else
				{
					wh_log($Insurance_no, $Remote_insurance_no, "get sales_id memberinfo table result : not found", $Person_id);
					$ret = false;
				}
			}
		}
		catch (Exception $e)
		{
			wh_log($Insurance_no, $Remote_insurance_no, "(X) get sales_id memberinfo table Exception error :".$e->getMessage(), $Person_id);
			$ret = false;
		}
		finally
		{
			try
			{
				if ($link != null && $close_mysql)
				{
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$link = null;
				}
			}
			catch(Exception $e)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "(X) get sales_id memberinfo table - disconnect mysql jsonlog table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $ret;
	}
	// 取得 member info
	function get_member_info(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, $close_mysql = true)
	{
		global $host;
		global $user;
		global $passwd;
		global $database;
	
		$data = array();
		$data["status"] = "false";
		try
		{
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				$data = result_connect_error ($link);
				if ($data["status"] == "false") return $data;
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
					$mid = 0;
					while ($row = mysqli_fetch_array($result))
					{
						$mid = $row['mid'];
						$data["status"] 		= "true";
						$data["person_id"] 		= $row['person_id'];
						$data["member_name"] 	= $row['member_name'];
						$data["mobile_no"] 		= $row['mobile_no'];
						$data["role"] 			= $row['role'];
						$data["pid_pic"] 		= $row['pid_pic'];
					}
					wh_log($Insurance_no, $Remote_insurance_no, "get memberinfo table result :found", $Person_id);
				}
				else
				{
					wh_log($Insurance_no, $Remote_insurance_no, "get memberinfo table result : not found", $Person_id);
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
				if ($link != null && $close_mysql)
				{
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$link = null;
				}
			}
			catch(Exception $e)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "(X) get memberinfo table - disconnect mysql memberinfo table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $data;
	}
	// 變更(Insert/Update)遠投保單狀態 public
	function get_order_state(&$link, &$status_code, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $close_mysql = true)
	{
		global $g_encrypt;
		global $host, $user, $passwd, $database;
		
		$order_status = "";
		$data = array();
		if ($Insurance_no 			!= '' &&
			$Remote_insurance_no 	!= '' &&
			$Person_id 				!= '' )
		{
			try
			{
				if ($link == null)
				{
					$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$data = result_connect_error ($link);
					if ($data["status"] == "false") return $data;
					mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				}

				$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
				$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no	);
				$Sales_id  				= mysqli_real_escape_string($link, $Sales_id			);
				$Person_id  			= mysqli_real_escape_string($link, $Person_id			);
				$Mobile_no  			= mysqli_real_escape_string($link, $Mobile_no			);
				$Role  					= mysqli_real_escape_string($link, $Role				);
				
				$Insuranceno 		 	= trim(stripslashes($Insurance_no));
				$Remote_insuranceno 	= trim(stripslashes($Remote_insurance_no));
				$Salesid 			 	= trim(stripslashes($Sales_id));
				$Personid 			 	= trim(stripslashes($Person_id));
				$Mobileno 			 	= trim(stripslashes($Mobile_no));
				$Role 		 			= trim(stripslashes($Role));
				
				$Personid = encrypt_string_if_not_empty($g_encrypt["id"] 	, $Personid);
				$Mobileno = encrypt_string_if_not_empty($g_encrypt["mobile"], $Mobileno);
				
				$sql = "SELECT * FROM orderinfo where order_trash=0 ";
				$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insuranceno			);
				$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insurance_no	);
				$sql = $sql.merge_sql_string_if_not_empty("sales_id"			, $Sales_id 			);
				$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Person_id 			);
				$sql = $sql.merge_sql_string_if_not_empty("mobile_no"			, $Mobileno 			);
				$sql = $sql.merge_sql_string_if_not_empty("role"				, $Role					);
				
				wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
				if ($result = mysqli_query($link, $sql))
				{
					if (mysqli_num_rows($result) > 0)
					{
						//$mid=0;
						while ($row = mysqli_fetch_array($result))
						{
							//$mid = $row['mid'];
							$order_status = $row['order_status'];
						}
						$order_status = str_replace(",", "", $order_status);
						try
						{
							//echo "user data change ok!";
							$status_code = $order_status;
							$array4json["order_status"] = $order_status;
							$data = result_message("true", "0x0200", "取得保單目前狀態成功", json_encode($array4json));
						}
						catch (Exception $e)
						{
							$data = result_message("false", "0x0209", "取得保單目前狀態 - Exception error!", "");
							JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
						}
					}
					else
					{
						$data = result_message("false", "0x0204", "無資料!", "");
					}
				}
				else
				{
					$data = result_message("false", "0x0208", "SQL fail!", "");
				}
			}
			catch (Exception $e)
			{
				$data = result_message("false", "0x0209", "Exception error!", "");
				JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
			}
			finally
			{
				try
				{
					if ($link != null && $close_mysql)
					{
						mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
						$link = null;
					}
				}
				catch(Exception $e)
				{
					$data = result_message("false", "0x0207", "操作：取得狀態 - disconnect mysql orderinfo table Exception error!", "");
					wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])."操作：取得狀態 - disconnect mysql orderinfo table failure :".$e->getMessage(), $Person_id);
				}
			}
		}
		return $data;
	}
	// 變更(Insert/Update)遠投保單狀態 public
	function modify_order_state(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Status_code, $close_mysql = true, $UpdateAllStatus = false, $ChangeStatusAnyway = false, $log_title = "", $log_subtitle = "")
	{
		global $g_encrypt;
		global $host, $user, $passwd, $database;
			
		$dst_title 		= ($log_title 	 == "") ? $Insurance_no 		: $log_title	;
		$dst_subtitle 	= ($log_subtitle == "") ? $Remote_insurance_no 	: $log_subtitle	;
		$data = array();
		//echo $Insurance_no."\r\n".$Remote_insurance_no."\r\n".$Sales_id."\r\n".$Person_id."\r\n".$Mobile_no."\r\n";
		if ($Insurance_no 			!= '' &&
			$Remote_insurance_no 	!= '' &&
			$Sales_id 				!= '' &&
			$Person_id 				!= '' &&
			$Mobile_no 				!= '')
		{
			try
			{
				if ($link == null)
				{
					$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$data = result_connect_error ($link);
					if ($data["status"] == "false") return $data;
					mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				}

				$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
				$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no	);
				$Sales_id  				= mysqli_real_escape_string($link, $Sales_id			);
				$Person_id  			= mysqli_real_escape_string($link, $Person_id			);
				$Mobile_no  			= mysqli_real_escape_string($link, $Mobile_no			);
				$Role  					= mysqli_real_escape_string($link, $Role				);
				$Status_code  			= mysqli_real_escape_string($link, $Status_code			);
				
				$Insuranceno 		 	= trim(stripslashes($Insurance_no));
				$Remote_insuranceno 	= trim(stripslashes($Remote_insurance_no));
				$Salesid 			 	= trim(stripslashes($Sales_id));
				$Personid 			 	= trim(stripslashes($Person_id));
				$Mobileno 			 	= trim(stripslashes($Mobile_no));
				$Role 		 			= trim(stripslashes($Role));
				$Statuscode 		 	= trim(stripslashes($Status_code));
				
				$Personid = encrypt_string_if_not_empty($g_encrypt["id"] 	, $Personid);
				$Mobileno = encrypt_string_if_not_empty($g_encrypt["mobile"], $Mobileno);
				
				$sql = "SELECT * FROM orderinfo where order_trash=0 ";
				$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insuranceno			);
				$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insurance_no	);
				$sql = $sql.merge_sql_string_if_not_empty("sales_id"			, $Sales_id 			);
				$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Person_id 			);
				$sql = $sql.merge_sql_string_if_not_empty("mobile_no"			, $Mobileno 			);
				$sql = $sql.merge_sql_string_if_not_empty("role"				, $Role					);
				$sql2 = "";
				if ($result = mysqli_query($link, $sql))
				{
					if (mysqli_num_rows($result) == 0)
					{
						$mid = 0;
						if ($Mobile_no == '')
						{
							$data = result_message("false", "0x0201", "操作：新增狀態-手機號碼不可為空白!", "");
						}
						else
						{
							if ($Statuscode == "") $Statuscode = "00";
							try
							{
								$sql2 = "INSERT INTO `orderinfo` (`insurance_no`,`remote_insurance_no`,`sales_id`,`person_id`,`mobile_no`,`role`, `order_status`, `order_trash`, `inputdttime`) VALUES ('$Insuranceno','$Remote_insuranceno','$Salesid','$Personid','$Mobileno','$Role','$Statuscode', 0,NOW())";
								mysqli_query($link, $sql2) or die(mysqli_error($link));
								
								$sql2 = "INSERT INTO `orderlog`  (`insurance_no`,`remote_insurance_no`,`sales_id`,`person_id`,`mobile_no`, `order_status`, `log_date`) VALUES ('$Insuranceno','$Remote_insuranceno','$Salesid','$Personid','$Mobileno','$Statuscode',NOW())";
								mysqli_query($link, $sql2) or die(mysqli_error($link));
								
								$data = result_message("true", "0x0200", "操作：新增狀態-新增資料完成!", "");
							}
							catch (Exception $e)
							{
								$data = result_message("false", "0x0208", "操作：操作：新增狀態-Exception error!", "");
							}
						}
					}
					else
					{
						$data = result_message("false", "0x0203", "操作：新增狀態-已經有相同要保流水序號的資料!", "");
						$ret = 0;
						
						$ret = updateOrderState($link, $result, $Insuranceno, $Remote_insuranceno, $Salesid, $Personid, $Mobileno, $Role, $Statuscode, $UpdateAllStatus, $ChangeStatusAnyway);
						switch ($ret)
						{
							case 0:
								$data = result_message("true", "0x0200", "操作：更新狀態-完成!", "");
								break;
							case 1:
								$data = result_message("false", "0x0208", "操作：更新狀態-Exception error!", "");
								break;
							case 2:
								$data = result_message("false", "0x0204", "操作：更新狀態-不存在此要保流水序號的資料!", "");
								break;
						}
					}
				}
				else
				{
					$data = result_message("false", "0x0208", "操作：更新狀態-SQL fail!", "");
				}
			}
			catch (Exception $e)
			{
				$data = result_message("false", "0x0209", "操作：新增狀態-Exception error!", "");
			}
			finally
			{
				try
				{
					if ($link != null && $close_mysql)
					{
						mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
						$link = null;
					}
				}
				catch(Exception $e)
				{
					$data = result_message("false", "0x0207", "操作：更新狀態 - disconnect mysql orderinfo table Exception error!", "");
					wh_log($dst_title, $dst_subtitle, "(X) 操作：更新狀態 - disconnect mysql orderinfo table failure :".$e->getMessage(), $Person_id);
				}
				return $data;
			}
		}
		else
		{
			$data = result_message("false", "0x0202", "操作：更新狀態 - API parameter is required!", "");
		}
		return $data;
	}
	// Update遠投保單狀態 private
	function updateOrderState(&$link, $result, $Insuranceno, $Remote_insuranceno, $Salesid, $Personid, $Mobileno, $Role, $Statuscode, $UpdateAllStatus = false, $ChangeStatusAnyway = false)
	{
		$ret = 0;
		if (mysqli_num_rows($result) > 0)
		{
			$flag = 0;
			try
			{
				while ($row = mysqli_fetch_array($result))
				{
					$oldorder_status = $row['order_status'];
					$oldorderstatus = str_replace(",", "", $oldorder_status);
					//$membername = $row['member_name'];
				}
				
				if (allowUpdateStep($oldorderstatus, $Statuscode) || $ChangeStatusAnyway)
				{
					$sql2 = "update `orderinfo` set `order_status`='$Statuscode' ,`updatedttime`=NOW() where insurance_no='$Insuranceno' and remote_insurance_no='$Remote_insuranceno' and sales_id='$Salesid' and order_trash=0";
					if ($UpdateAllStatus == false)
					{
						$sql2 = $sql2.merge_sql_string_if_not_empty("person_id"	, $Personid		);
						$sql2 = $sql2.merge_sql_string_if_not_empty("mobile_no"	, $Mobileno		);
						$sql2 = $sql2.merge_sql_string_if_not_empty("role"		, $Role			);
					}
					mysqli_query($link, $sql2) or die(mysqli_error($link));
					$flag = 1;
				}
				if ($flag == 1)
				{
					$sql2 = "INSERT INTO `orderlog` (`insurance_no`,`remote_insurance_no`,`sales_id`,`person_id`, `order_status`, `log_date`";
					if ($Mobileno  != "") $sql2 = $sql2.",`mobile_no`";
					if ($Role != "") $sql2 = $sql2.",`role`";
					$sql2 = $sql2.") VALUES ('$Insuranceno','$Remote_insuranceno','$Salesid','$Personid','$Statuscode',NOW()";
					if ($Mobileno  != "") $sql2 = $sql2.",'$Mobileno'";
					if ($Role != "") $sql2 = $sql2.",'$Role'";
					$sql2 = $sql2.")";
					mysqli_query($link,$sql2) or die(mysqli_error($link));
				}
				//echo "user data change ok!";
				$ret = 0;
			}
			catch (Exception $e)
			{
				$ret = 1;
				wh_log($Insuranceno, $Remote_insuranceno, "(X) update order state - disconnect mysql orderlog table failure :".$e->getMessage(), $Personid);
			}
		}
		else
		{
			$ret = 2;
		}
		return $ret;
	}
	
	// 變更(Insert/Update)member public
	function modify_member(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Member_name, $Mobile_no, $FCM_Token, $Image_pid_pic, &$status_code, $close_mysql = true)
	{
		global $g_encrypt;
		global $host, $user, $passwd, $database;
		
		$data = array();
		wh_log($Insurance_no, $Remote_insurance_no, "modify member entry <-", $Person_id);
		try
		{
			$date = date_create();
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				$data = result_connect_error ($link);
				if ($data["status"] == "false") return $data;
				mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
			}

			$Person_id  	= mysqli_real_escape_string($link, $Person_id	);
			$Mobile_no  	= mysqli_real_escape_string($link, $Mobile_no	);
			$Member_name  	= mysqli_real_escape_string($link, $Member_name	);
			$FCM_Token  	= mysqli_real_escape_string($link, $FCM_Token	);
			$Role  			= mysqli_real_escape_string($link, $Role		);

			$Personid 		= trim(stripslashes($Person_id)  );
			$Mobileno 		= trim(stripslashes($Mobile_no)  );
			$Membername 	= trim(stripslashes($Member_name));
			$FCMToken 		= trim(stripslashes($FCM_Token)  );
			$Role 			= trim(stripslashes($Role)  	 );
			
			$Personid 	= encrypt_string_if_not_empty($g_encrypt["id"]	 		, $Personid);
			$Mobileno 	= encrypt_string_if_not_empty($g_encrypt["mobile"]		, $Mobileno);
			$Membername = encrypt_string_if_not_empty($g_encrypt["member_name"]	, $Membername);
			
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
						$sql2 = "INSERT INTO `memberinfo` (`insurance_no`,`remote_insurance_no`,`person_id`,`mobile_no`,`member_name`";
						if ($Role != "") $sql2 = $sql2.",`role`";
						if ($FCM_Token != "") $sql2 = $sql2.",`notificationToken`";
						if ($Image_pid_pic != "") $sql2 = $sql2.",`pid_pic`";
						$sql2 = $sql2.",`member_trash`, `inputdttime`) VALUES ('$Insurance_no','$Remote_insurance_no','$Personid','$Mobileno','$Membername'";
						if ($Role != "") $sql2 = $sql2.",'$Role'";
						if ($FCM_Token != "") $sql2 = $sql2.",'$FCMToken'";
						if ($Image_pid_pic != "") $sql2 = $sql2.",'{$Image_pid_pic}'";
						$sql2 = $sql2.", 0,NOW())";
						//echo $sql2."\r\n";
						mysqli_query($link, $sql2) or die(mysqli_error($link));
						//echo "user data change ok!";
						$data = result_message("true", "0x0200", "身份資料建檔成功!", "");
						$status_code = "";
					}
					catch (Exception $e)
					{
						$data = result_message("false", "0x0209", "access member Exception error!", "");
						$status_code = "";
					}
				}
				else
				{
					$data = result_message("false", "0x0203", "身份資料建檔-無法重複建立，已經有相同身份證資料!", "");
					$status_code = "";
				}
			}
			else
			{
				$data = result_message("false", "0x0208", "access member SQL fail!", "");
				$status_code 			= "";
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "access member Exception error!", "");
			wh_log($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
        }
		finally
		{
			try
			{
				if ($link != null && $close_mysql)
				{
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$link = null;
				}
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
	function update_idphoto(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, $front, $Image_src, &$status_code, $close_mysql = true)
	{
		global $g_encrypt;
		global $host, $user, $passwd, $database;
		
		$data = array();
		try
		{
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);
				$data = result_connect_error ($link);
				if ($data["status"] == "false") return $data;
				mysqli_query($link,"SET NAMES 'utf8'");
			}

			$Person_id  = mysqli_real_escape_string($link, $Person_id);
			$front  	= mysqli_real_escape_string($link, $front	 );
			$Personid 	= trim(stripslashes($Person_id));
			
			$Personid 	= encrypt_string_if_not_empty($g_encrypt["id"], $Personid);
			
			$sql = "SELECT * FROM memberinfo where insurance_no='".$Insurance_no."' and remote_insurance_no='".$Remote_insurance_no."' and member_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					wh_log($Insurance_no, $Remote_insurance_no, "person_id verify ok", $Person_id);
					try {
						// 將照片儲存到 NAS
						$data = will_save2nas_prepare($Insurance_no, $Remote_insurance_no, $Person_id, $front);
						if ($data["status"] == "false")
							return $data;
						
						$filename = $data["filename"];
						$retimg = save_image2nas($Insurance_no, $Remote_insurance_no, $Person_id, $filename, $Image_src);
						
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
									$sql2 = "UPDATE  `idphoto` set `saveType`='NAS', `frontpath` = '$filename', `updatedtime` = NOW() where `person_id`='".$Personid."' and insurance_no= '".$Insurance_no."' and remote_insurance_no= '".$Remote_insurance_no."' ";
									$log = "UPDATE idphoto frontpath ".$filename;
									
								}
								else
								{
									$sql2 = "UPDATE  `idphoto` set `saveType`='NAS', `backpath` = '$filename', `updatedtime` = NOW() where `person_id`='".$Personid."' and insurance_no= '".$Insurance_no."' and remote_insurance_no= '".$Remote_insurance_no."' ";	
									$log = "UPDATE  idphoto backpath ".$filename;
								}
							}
							else
							{
								if($front=="0")
								{
									$sql2 = "INSERT INTO  `idphoto` ( `person_id`, `insurance_no`, `remote_insurance_no`, `frontpath` , `saveType`, `updatedtime`) VALUES ('$Personid', '$Insurance_no', '$Remote_insurance_no', '$filename', 'NAS', NOW()) ";
									$log = "INSERT idphoto frontpath ".$filename;
								}
								else
								{
									$sql2 = "INSERT INTO  `idphoto` ( `person_id`, `insurance_no`, `remote_insurance_no`, `backpath` , `saveType`, `updatedtime`)  VALUES ('$Personid', '$Insurance_no', '$Remote_insurance_no', '$filename', 'NAS', NOW()) ";
									$log = "INSERT idphoto backpath ".$filename;
								}
							}
							mysqli_query($link, $sql2) or die(mysqli_error($link));
							wh_log($Insurance_no, $Remote_insurance_no, "UPDATE idphoto :".$log, $Person_id);
						}
						else
						{
							$data = result_message("false", "0x0208", "寫入NAS 失敗! (".$retimg.")", "");
							wh_log($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." update_idphoto - save_image2nas Failed", $Person_id);
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
							return;
						}
						$data = result_message("true", "0x0200", "身分證圖檔".$front."上傳成功!", "");
						wh_log($Insurance_no, $Remote_insurance_no, $data["code"].$data["responseMessage"]." ".$log, $Person_id);
					}
					catch (Exception $e)
					{
						$data = result_message("true", "0x0209", "Exception error!", "");
						wh_log($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." Exception error :".$e->getMessage(), $Person_id);
					}
				}
				else
				{
					$data = result_message("false", "0x0204", "無相同身份證資料,無法更新!", "");
					wh_log($Insurance_no, $Remote_insurance_no, "(!) ".$data["code"]." ".$data["responseMessage"].$Personid, $Person_id);
					$status_code = "";
				}
			}
			else
			{
				$data = result_message("false", "0x0208", "SQL fail!", "");
				wh_log($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"], $Person_id);
				$status_code 			= "";
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "Exception error!", "");
			wh_log($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." "."Exception error2!:".$e->getMessage(), $Person_id);
			$status_code 			= "";
		}
		finally
		{
			try
			{
				if ($link != null && $close_mysql)
				{
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$link = null;
				}
			}
			catch (Exception $e)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "(X) disconnect mysql idphoto table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $data;
	}
	// Update member public
	function update_member(&$link, $Insurance_no, $Remote_insurance_no, $image, $Person_id, $Member_name, $Mobile_no, $FCM_Token, &$status_code, $close_mysql = true)
	{
		global $g_encrypt;
		global $host, $user, $passwd, $database;
		
		$data = array();
		try
		{
			if ($link == null)
			{
				$link = mysqli_connect($host, $user, $passwd, $database);
				$data = result_connect_error ($link);
				if ($data["status"] == "false") return $data;
				mysqli_query($link,"SET NAMES 'utf8'");
			}

			$Person_id  	= mysqli_real_escape_string($link, $Person_id);
			$Mobile_no  	= mysqli_real_escape_string($link, $Mobile_no);
			$Member_name	= mysqli_real_escape_string($link, $Member_name);
			$FCM_Token  	= mysqli_real_escape_string($link, $FCM_Token);
	
			$Personid 		= trim(stripslashes($Person_id));
			$Mobileno 		= trim(stripslashes($Mobile_no));
			$Membername 	= trim(stripslashes($Member_name));
			$FCMToken 		= trim(stripslashes($FCM_Token));
			
			// 加密處理
			$Personid 	= encrypt_string_if_not_empty($g_encrypt["id"]	 		, $Personid);
			$Mobileno 	= encrypt_string_if_not_empty($g_encrypt["mobile"]		, $Mobileno);
			$Membername = encrypt_string_if_not_empty($g_encrypt["member_name"]	, $Membername);
		
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
						
						$data = result_message("true", "0x0200", "更新身份證資料完成!", "");
					}
					catch (Exception $e)
					{
						$data = result_message("false", "0x0209", "Exception error!", "");
						wh_log($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"].$e->getMessage(), $Person_id);
						$status_code = "";
					}
				}
				else
				{
					$data = result_message("false", "0x0204", "無相同身份證資料,更新失敗!", "");
					wh_log($Insurance_no, $Remote_insurance_no, "(!) ".$data["code"]." ".$data["responseMessage"], $Person_id);
					$status_code 			= "";
				}
			}
			else
			{
				$data = result_message("false", "0x0208", "SQL fail!", "");
				wh_log($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"], $Person_id);
				$status_code = "";
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "Exception error!", "");
			$status_code = "";
			wh_log($Insurance_no, $Remote_insurance_no, "(X) Exception3 error :".$e->getMessage(), $Person_id);
		}
		finally
		{
			try
			{
				if ($link != null && $close_mysql)
				{
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$link = null;
				}
			}
			catch(Exception $e)
			{
				wh_log($Insurance_no, $Remote_insurance_no, "(X) disconnect mysql memberinfo table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $data;
	}
	
	// 變更(Insert/Update)pdflog public
	function modify_pdf_log(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, $Mobile_no, $Title, $base64pdf, $pdf_path, $Status_code, $close_mysql = true, $log_title = "", $log_subtitle = "")
	{
		global $host, $user, $passwd, $database;

		$sql2 = "";
		$dst_title 		= ($log_title 	 == "") ? $Insurance_no 		: $log_title	;
		$dst_subtitle 	= ($log_subtitle == "") ? $Remote_insurance_no 	: $log_subtitle	;
		$data = array();
		// echo $Insurance_no."\r\n".$Remote_insurance_no."\r\n".$base64pdf."\r\n".$pdf_path."\r\n".$Status_code."\r\n";
		if (($Insurance_no 			!= '') &&
			($Remote_insurance_no 	!= '') &&
			!($base64pdf == '' && $pdf_path == '') &&
			($Status_code 			!= ''))
		{
			try
			{
				if ($link == null)
				{
					$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$data = result_connect_error ($link);
					if ($data["status"] == "false") return $data;
					mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				}

				$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
				$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no	);
				$Person_id  			= mysqli_real_escape_string($link, $Person_id			);
				$Title  				= mysqli_real_escape_string($link, $Title				);
				$pdf_path  				= mysqli_real_escape_string($link, $pdf_path			);
				$Status_code  			= mysqli_real_escape_string($link, $Status_code			);

				$Insuranceno 		 	= trim(stripslashes($Insurance_no));
				$Remote_insuranceno 	= trim(stripslashes($Remote_insurance_no));
				$Person_id 				= trim(stripslashes($Person_id));
				$Title 			 		= trim(stripslashes($Title));
				$pdf_path 			 	= trim(stripslashes($pdf_path));
				$Statuscode 		 	= trim(stripslashes($Status_code));
				
				$sql = "SELECT * FROM pdflog where 1=1 ";
				$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insuranceno			);
				$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insuranceno	);
				$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Person_id	);
				$sql = $sql.merge_sql_string_if_not_empty("title"				, $Title				);
				$sql = $sql.merge_sql_string_if_not_empty("order_status"		, $Statuscode			);
				if ($result = mysqli_query($link, $sql))
				{
					if (mysqli_num_rows($result) == 0)
					{
						$mid = 0;
						if ($Mobile_no == '')
						{
							$data = result_message("false", "0x0206", "操作：儲存pdf-手機號碼不可為空白!", "");
						}
						else
						{
							if ($Statuscode == "") $Statuscode = "00";
							try
							{
								$pdf_content["data"] = $base64pdf;
								$sql2 = "INSERT INTO `pdflog` (`insurance_no`,`remote_insurance_no`";
								if ($Person_id != "") $sql2 = $sql2.",`person_id`";
								if ($Title 	   != "") $sql2 = $sql2.",`title`";
								if ($base64pdf != "") $sql2 = $sql2.",`pdf_data`";
								if ($pdf_path  != "") $sql2 = $sql2.",`pdf_path`";
								$sql2 = $sql2.",`order_status`, `updatetime`) VALUES ('$Insuranceno','$Remote_insuranceno'";
								
								if ($Person_id != "") $sql2 = $sql2.",'$Person_id'";
								if ($Title 	   != "") $sql2 = $sql2.",'$Title'";
								if ($base64pdf != "") $sql2 = $sql2.",'".json_encode($pdf_content)."'";
								if ($pdf_path  != "") $sql2 = $sql2.",'$pdf_path'";
								$sql2 = $sql2.",'$Statuscode', NOW())";
								
								mysqli_query($link, $sql2) or die(mysqli_error($link));
								//echo "user data change ok!";
								$data = result_message("true", "0x0200", "操作：儲存pdf-新增資料完成!", "");
							}
							catch (Exception $e)
							{
								$data = result_message("false", "0x0208", "操作：儲存pdf-Exception error!", "");
							}
						}
					}
					else
					{
						$data = result_message("false", "0x0203", "操作：儲存pdf-已經有相同要保流水序號的資料!", "");
						$ret = 0;
					}
				}
				else
				{
					$data = result_message("false", "0x0208", "操作：儲存pdf-SQL fail!", "");
				}
			}
			catch (Exception $e)
			{
				$data = result_message("false", "0x0209", "操作：儲存pdf-Exception error!", "");
			}
			finally
			{
				try
				{
					if ($link != null && $close_mysql)
					{
						mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
						$link = null;
					}
				}
				catch(Exception $e)
				{
					wh_log($dst_title, $dst_subtitle, "(X) 操作：儲存pdf - disconnect mysql orderinfo table failure :".$e->getMessage(), $Person_id);
				}
				return $data;
			}
		}
		else
		{
			$data = result_message("false", "0x0202", "操作：pdf parameter is required!", "");
		}
		return $data;
	}
	// 取得 pdf info
	function get_pdflog_table_info(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, $Title, $close_mysql = true, $get_All_signature = false, $log_title = "", $log_subtitle = "")
	{
		global $host, $user, $passwd, $database;

		$sql2 = "";
		$dst_title 		= ($log_title 	 == "") ? $Insurance_no 		: $log_title	;
		$dst_subtitle 	= ($log_subtitle == "") ? $Remote_insurance_no 	: $log_subtitle	;
		$data 		= array();
		$ret_data 	= array();
		// echo $Insurance_no."\r\n".$Remote_insurance_no."\r\n".$base64pdf."\r\n".$pdf_path."\r\n".$Status_code."\r\n";
		if ($Insurance_no 			!= '' &&
			$Remote_insurance_no 	!= '' &&
			($Title					!= '' || $get_All_signature)
			)
		{
			try
			{
				if ($link == null)
				{
					$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$data = result_connect_error ($link);
					if ($data["status"] == "false") return $data;
					mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				}

				$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
				$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no	);
				$Title  				= mysqli_real_escape_string($link, $Title				);

				$Insuranceno 		 	= trim(stripslashes($Insurance_no));
				$Remote_insuranceno 	= trim(stripslashes($Remote_insurance_no));
				$Title 			 		= trim(stripslashes($Title));
				
				$sql = "SELECT * FROM pdflog where 1=1 ";
				$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insuranceno			);
				$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insuranceno	);
				if ($get_All_signature)
					$sql = $sql." and title like '%' ";
				else
					$sql = $sql.merge_sql_string_if_not_empty("title"			, $Title				);
				
				if ($result = mysqli_query($link, $sql))
				{
					if (mysqli_num_rows($result) > 0)
					{
						$i = 0;
						while ($row = mysqli_fetch_array($result))
						{
							$ret_data[$i]["pdf_name"] = $row['title'];
							$ret_data[$i]["pdf_path"] = $row['pdf_path'];
							$pdf_data = json_decode($row['pdf_data']);
							$ret_data[$i]["pdf_data"] = $pdf_data->data;
							$i++;
						}
						$data = result_message("true", "0x0200", "操作：查詢pdf完成!", json_encode($ret_data));
					}
					else
					{
						$data = result_message("false", "0x0204", "操作：查無pdf資料!", "");
						$ret = 0;
					}
				}
				else
				{
					$data = result_message("true", "0x0208", "查詢pdf-SQL fail!", "");
				}
			}
			catch (Exception $e)
			{
				$data = result_message("false", "0x0209", "操作：查詢pdf-Exception error!", "");
				wh_log($dst_title, $dst_subtitle, "(X) ".$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
			}
			finally
			{
				try
				{
					if ($link != null && $close_mysql)
					{
						mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
						$link = null;
					}
				}
				catch(Exception $e)
				{
					wh_log($dst_title, $dst_subtitle, "(X) 操作：查詢pdf - disconnect mysql orderinfo table failure :".$e->getMessage(), $Person_id);
				}
				return $data;
			}
		}
		else
		{
			$data = result_message("false", "0x0202", "操作：query pdflog status-API parameter is required!", "");
		}
		return $data;
	}
	// 取得對應的身份證正反面照片
	function getpidpic2(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, $front, $close_mysql = true, $log_title = "", $log_subtitle = "")
	{
		global $g_encrypt;
		
		//0: front, 1: back
		try
		{
			$dst_title 		= ($log_title 	 == "") ? $Insurance_no 		: $log_title	;
			$dst_subtitle 	= ($log_subtitle == "") ? $Remote_insurance_no 	: $log_subtitle	;
			
			//oid,order_no,sales_id,person_id,mobile_no,member_type,order_status,log_date
			$sql3 = "SELECT * FROM idphoto where 1=1 ";
			//echo $sql;
			$Person_id = trim(stripslashes($Person_id));
			
			if ($Person_id 			 != "") $sql3 = $sql3." and person_id='".$Person_id."'";
			if ($Insurance_no 		 != "") $sql3 = $sql3." and insurance_no='".$Insurance_no."'";
			if ($Remote_insurance_no != "") $sql3 = $sql3." and remote_insurance_no='".$Remote_insurance_no."'";
			
			$pidpic2 = "";
			//echo $saveType;
			if ($result2 = mysqli_query($link, $sql3))
			{
				if (mysqli_num_rows($result2) > 0)
				{
					while ($row2 = mysqli_fetch_array($result2))
					{
						if ($row2['saveType']=='NAS')
						{
							setSaveType("NAS");
							//$saveType = "NAS";
							if ($front == "0")
							{
								if ($row2['frontpath'] != null)
								{
									$fp = fopen($row2['frontpath'], "r");
									$out = fread($fp, filesize($row2['frontpath']));
									fclose($fp);
									$pidpic2 = decrypt_string_if_not_empty($g_encrypt["image"], $out); //decrypt($keys,$row2['front']);
								}
								else
								{
									$pidpic2 = "";
								}
							}
							if ($front == "1")
							{
								if ($row2['backpath'] != null)
								{
									$fp = fopen($row2['backpath'], "r");
									$out = fread($fp, filesize($row2['backpath']));
									fclose($fp);
									$pidpic2 = decrypt_string_if_not_empty($g_encrypt["image"], $out);
								}
								else
								{
									$pidpic2 = "";
								}							
							}
						}
						else
						{
							//echo "DB";
							setSaveType("DB");
							if ($front == "0")
							{
								if ($row2['front'] != null)
								{
									$pidpic2 = decrypt_string_if_not_empty($g_encrypt["image"], $row2['front']);
								}
								else
								{
									$pidpic2 = "";
								}
							}
							if ($front == "1")
							{
								if ($row2['back'] != null)
								{
									$pidpic2 = decrypt_string_if_not_empty($g_encrypt["image"], $row2['back']);
								}
								else
								{
									$pidpic2 = "";
								}						
							}
						}
						break;
					}
				}
				else
				{
					//有可能是舊的方式,沒有儲存 insurance_id
					$sql3 = "SELECT * FROM idphoto WHERE 1=1 ";
					//echo $sql;					
					
					if ($Person_id 			 != "") $sql3 = $sql3." and person_id='".$Person_id."'";
					if ($Insurance_no 		 != "") $sql3 = $sql3." and insurance_no='".$Insurance_no."'";
					if ($Remote_insurance_no != "") $sql3 = $sql3." and remote_insurance_no='".$Remote_insurance_no."'";
		
					if ($result2 = mysqli_query($link, $sql3))
					{
						if (mysqli_num_rows($result2) > 0)
						{							
							while($row2 = mysqli_fetch_array($result2))
							{
								if($row2['saveType']=='NAS')
								{
									setSaveType("NAS"); //$saveType = "NAS";
									if ($front == "0")
									{
										if ($row2['frontpath'] != null)
										{
											$fp = fopen($row2['frontpath'], "r");
											$out = fread($fp, filesize($row2['frontpath']));
											fclose($fp);
											$pidpic2 =  decrypt_string_if_not_empty($g_encrypt["image"], $out); //decrypt($keys,$row2['front']);
										}
										else
										{
											$pidpic2 = "";
										}
									}
									if ($front == "1")
									{
										if ($row2['backpath'] != null)
										{
											$fp=fopen($row2['backpath'], "r");
											$out=fread($fp, filesize($row2['backpath']));
											fclose($fp);
											$pidpic2 =  decrypt_string_if_not_empty($g_encrypt["image"], $out);
										}
										else
										{
											$pidpic2 = "";
										}							
									}
								}
								else
								{
									//echo "DB";
									setSaveType("DB");
									if ($front == "0")
									{
										if ($row2['front'] != null)
										{
											$pidpic2 = decrypt_string_if_not_empty($g_encrypt["image"], $row2['front']);
										}
										else
										{
											$pidpic2 = "";
										}
									}
									if ($front == "1")
									{
										if ($row2['back'] != null)
										{
											$pidpic2 = decrypt_string_if_not_empty($g_encrypt["image"], $row2['back']);
										}
										else
										{
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
			}
			else
			{
				$pidpic2 = "";
			}
		}
		catch (Exception $e)
		{
			$pidpic2 = "";
		}
		finally
		{
			try
			{
				if ($link != null && $close_mysql)
				{
					mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$link = null;
				}
			}
			catch(Exception $e)
			{
				wh_log($dst_title, $dst_subtitle, "(X) [getpidpic2] get sales_id memberinfo table - disconnect mysql jsonlog table failure :".$e->getMessage(), $Person_id);
			}
		}
		return $pidpic2;	
	}
	// 取得 attachment 附件
	function get_attachment_table_info(&$link, $Insurance_no, $Remote_insurance_no, $Person_id, $AttachName, $close_mysql = true, $get_All_signature = false, $log_title = "", $log_subtitle = "")
	{
		global $g_encrypt;
		global $host, $user, $passwd, $database;

		$sql2 = "";
		$dst_title 		= ($log_title 	 == "") ? $Insurance_no 		: $log_title	;
		$dst_subtitle 	= ($log_subtitle == "") ? $Remote_insurance_no 	: $log_subtitle	;
		$data 		= array();
		$ret_data 	= array();
		// echo $Insurance_no."\r\n".$Remote_insurance_no."\r\n".$base64pdf."\r\n".$pdf_path."\r\n".$Status_code."\r\n";
		if ($Insurance_no 			!= '' &&
			$Remote_insurance_no 	!= '' &&
			$AttachName				!= '')
		{
			try
			{
				if ($link == null)
				{
					$link = mysqli_connect($host, $user, $passwd, $database);	// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
					$data = result_connect_error ($link);
					if ($data["status"] == "false") return $data;
					mysqli_query($link,"SET NAMES 'utf8'");						// 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
				}
				
				$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
				$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no	);
				$AttachName  			= mysqli_real_escape_string($link, $AttachName			);

				$Insuranceno 		 	= trim(stripslashes($Insurance_no));
				$Remote_insuranceno 	= trim(stripslashes($Remote_insurance_no));
				$AttachName 			= trim(stripslashes($AttachName));
				
				$sql = "SELECT * FROM attachement where 1=1 ";
				$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no	  	 );
				$sql = $sql.merge_sql_string_if_not_empty("remote_insuance_no"	, $Remote_insurance_no);
				
				if ($AttachName == "")
					$sql = $sql.merge_sql_string_if_not_empty("attach_title"	, $AttachName		 );
				
				if ($result = mysqli_query($link, $sql))
				{
					if (mysqli_num_rows($result) > 0)
					{
						$i = 0;
						while ($row = mysqli_fetch_array($result))
						{
							$ret_data[$i]["attache_title"] = $row['attach_title'];
							$ret_data[$i]["attach_graph"] = addslashes(decrypt_string_if_not_empty($g_encrypt["image"], $row['attach_graph']));
							$i++;
						}
						$data = result_message("true", "0x0200", "操作：查詢attachment完成!", json_encode($ret_data));
					}
					else
					{
						$data = result_message("false", "0x0204", "操作：查無attachment資料!", "");
					}
				}
				else
				{
					$data = result_message("false", "0x0208", "操作：查詢attachment-SQL fail!", "");
				}
			}
			catch (Exception $e)
			{
				$data = result_message("false", "0x0209", "操作：查詢attachment-Exception error!", "");
			}
			finally
			{
				try
				{
					if ($link != null && $close_mysql)
					{
						mysqli_close($link); // 因呼叫者已開啟sql，避免重覆開啟連線數-jacky
						$link = null;
					}
				}
				catch(Exception $e)
				{
					wh_log($dst_title, $dst_subtitle, "(X) 操作：查詢attachment - disconnect mysql orderinfo table failure :".$e->getMessage(), $Person_id);
				}
				return $data;
			}
		}
		else
		{
			$data = result_message("false", "0x0202", "操作：query attachment status-API parameter is required!", "");
		}
		return $data;
	}
?>
