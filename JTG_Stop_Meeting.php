<?php
	include("func.php");
	
	const _ENV = "PROD"; 
	//const _ENV = "UAT"; 
	$key = "cLEzfgz5c5hxQwLWauCOdAilwgfn97yj";
	//echo $key.date("Ymd");
	$Authorization = md5($key.date("Ymd"));
	
	// initial
	$status_code_succeed 	= "L3"; // 成功狀態代碼
	$status_code_failure 	= "L2"; // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	$link					= null;
	$Insurance_no 			= ""; // *
	$Remote_insurance_no 	= ""; // *
	$Person_id 				= ""; // *
	$Mobile_no 				= "";
	$json_Person_id 		= "";
	$Sales_id 				= "";
	$status_code 			= "";
	$Member_name			= "";
	$base64image			= "";
	$Role 					= "";
	$imageFileType 			= "jpg";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no = isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Role 				= isset($_POST['Role']) 				? $_POST['Role'] 				: '';
	$Meeting_id 		= isset($_POST['Meeting_id']) 			? $_POST['Meeting_id'] 			: '';
	//$bSaved 			= isset($_POST['bSaved']) 				? $_POST['bSaved'] 				: '';

	$MEETING_time 			= isset($_POST['MEETING_time']) 			? $_POST['MEETING_time'] 		: '';
	$MEETING_time 			= check_special_char($MEETING_time);
	
	/*
	proposer：要保人
	insured：被保人  
	legalRepresentative：法定代理人
	agentOne:業務
	*/
	
	$status_code_succeed = ($MEETING_time == 1) ? "L3" : "R3"; // 成功狀態代碼
	$status_code_failure = ($MEETING_time == 1) ? "L2" : "R2"; // 失敗狀態代碼
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "E123456789";
		$Meeting_id			 = "0";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
		$data["json"]			= "";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	wh_log($Insurance_no, $Remote_insurance_no, "stop meeting entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Face_Compare", "stop meeting exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	//echo $Insurance_no."\r\n".$Remote_insurance_no."\r\n".$Person_id."\r\n".$Meeting_id."\r\n".$Role;
	if ($Insurance_no 			!= '' &&
		$Remote_insurance_no 	!= '' &&
		$Person_id 				!= '' &&
		$Meeting_id 			!= '' &&
		$Role 					!= '' )
	{
		//check 帳號/密碼
		//$host = 'localhost';
		//$user = 'tglmember_user';
		//$passwd = 'tglmember210718';
		//$database = 'tglmemberdb';
		
		//echo $sql;
		//return;
		$link = null;
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  		 = mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insurance_no = mysqli_real_escape_string($link, $Remote_insurance_no);
			$Meeting_id  		 = mysqli_real_escape_string($link, $Meeting_id			);
			$Role  				 = mysqli_real_escape_string($link, $Role				);
			//$bSaved  = mysqli_real_escape_string($link,$bSaved);

			$sql = "SELECT * FROM orderinfo where order_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insurance_no	);
			$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Person_id			);
			
			wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					//$mid=0;
					$order_status = "";
					while ($row = mysqli_fetch_array($result))
					{
						//$mid = $row['mid'];
						$order_status = $row['order_status'];
						//update 線上 人數 DB
						$sql = "update gomeeting SET count=count-1 where  count >0 and insurance_no='".$Insurance_no."'";
						$sql = $sql.merge_sql_string_if_not_empty("remote_Insurance_no"	, $Remote_insurance_no	);
						$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Person_id			);
						$ret = mysqli_query($link, $sql);
						if ($Role != "agentOne")
						{
							$data["status"]			= "true";
							$data["code"]			= "0x0200";
							$data["responseMessage"]= "OK";
							$data["json"]			= "";
							$status_code = $status_code_succeed;
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." 更新線上人數", $Person_id);
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							return;
						}
					}
					try
					{
						if ($Role == "agentOne") // 業務離開
						{
							$sql = "select * from gomeeting where insurance_no='".$Insurance_no."'";
							$sql = $sql.merge_sql_string_if_not_empty("remote_Insurance_no"	, $Remote_insurance_no	);
							$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Person_id			);
							
							wh_log($Insurance_no, $Remote_insurance_no, "業務離開", $Person_id);
							if ($result = mysqli_query($link, $sql))
							{
								while ($row = mysqli_fetch_array($result))
								{
									$vmr = $row['vmr'];
									$meeting_id = $row['meetingid'];
								}
							}
							$gateway = "12";
							$sql =  "select * from vmrule where id = 1";// gateway = '$vmrgateway' where id = 1";
							if ($result = mysqli_query($link, $sql))
							{
								if (mysqli_num_rows($result) > 0)
								{	
									while ($row = mysqli_fetch_array($result))
										$gateway = $row['gateway'];
								}
							}
							
							//先踢人
							$mainurl = $g_create_meeting_apiurl;
							$url = $mainurl."post/api/token/request";
							
							//1. GET Token
							$data_input1 				= array();
							$data_input1["username"]	= $g_create_meeting_user;
							$hash 						= md5($g_create_meeting_hash);
							$data_input1["data"]		= md5($hash."@deltapath");
							$out 						= CallAPI4OptMeeting("POST", $url, $data_input1);
							wh_log($Insurance_no, $Remote_insurance_no, "呼叫踢人 API", $Person_id);
							$ret = json_decode($out, true);
							if (strlen($ret) > 0 && $ret['success'] == true)
								$token = $ret['token'];
							else
							{
								$data["status"]			= "false";
								$data["code"]			= "0x0202";
								$data["responseMessage"]= "(X) 呼叫踢人 API error token invalid!";
								$data["json"]			= "";
								wh_log($Insurance_no, $Remote_insurance_no, "先踢人 error", $Person_id);
								header('Content-Type: application/json');
								echo (json_encode($data, JSON_UNESCAPED_UNICODE));
								return;
							}
							$header = array('X-frSIP-API-Token:'.$token);
							
							$kickid 	= 0;
							$data_input2		= array();
							$data_input2['id']	= $meeting_id;
							$url 				= $mainurl."delete/virtualmeeting/virtualmeeting/".$meeting_id;
							$out 				= CallAPI4OptMeeting("POST", $url, $data_input2, $header);
							wh_log($Insurance_no, $Remote_insurance_no, "呼叫關閉會議室 API", $Person_id);
							
							//3. accesscode 更新deletecode 狀態  (deletecode = 1)
							$sql = "update accesscode set deletecode = 1 where meetingid='".$meeting_id."'";
							$result = mysqli_query($link, $sql);
							wh_log($Insurance_no, $Remote_insurance_no, "accesscode 更新deletecode 狀態", $Person_id);	

							//5. delete gomeeting
							$sql = "delete from gomeeting where meetingid='".$meeting_id."'";
							$result = mysqli_query($link, $sql);
							wh_log($Insurance_no, $Remote_insurance_no, "delete gomeeting", $Person_id);
							
							//upate meetinglog status for stop meeting, 1:norma stop, 2:kick
							$sql = "update meetinglog set bStop = 1, bookstoptime=NOW()  where meetingid='".$meeting_id."'";
							$result = mysqli_query($link, $sql);
							wh_log($Insurance_no, $Remote_insurance_no, "upate meetinglog status for stop meeting, 1:norma stop, 2:kick", $Person_id);					

							//4. 更新vminfo status (relese resouce, status = 0)	
							$sql = "update vmrinfo set status = 0 , updatetime=NOW() where vid = '".$vmr."'";
							$result = mysqli_query($link, $sql);
							wh_log($Insurance_no, $Remote_insurance_no, "更新vminfo status", $Person_id);
							
							//刪除前先釋放vmr
							/*
							$sql = "update vmrinfo SET status = '0' where vid = '".$vmr."'";  //釋放
							$ret = mysqli_query($link, $sql);
							//刪除視訊會議
							$sql = "delete  from gomeeting where  insurance_no='".$Insurance_no."'";
							$ret = mysqli_query($link, $sql);
							*/
							//save file or not?
							/*							
							if($bSaved == "0")
							{
								$sql = "update meetinglog SET bSaved = 0 where insurance_no='".$Insurance_no."'";
								$ret = mysqli_query($link, $sql);
							}
							*/						
						}
						$data["status"] 		= "true";
						$data["code"]			= "0x0200";
						$data["responseMessage"]= "OK";
						$data["json"]			= "";
						$status_code = $status_code_succeed;
					}
					catch (Exception $e)
					{
						//$this->_response(null, 401, $e->getMessage());
						//echo $e->getMessage();
						$data["status"]			= "false";
						$data["code"]			= "0x0202";
						$data["responseMessage"]= $e->getMessage();
						$data["json"]			= "";
					}
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "不存在此要保流水序號的資料!";
					$data["json"]			= "";						
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";
				$data["json"]			= "";				
			}
		}
		catch (Exception $e)
		{
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= $e->getMessage();
			$data["json"]			= "";			
        }
		finally
		{
			wh_log($Insurance_no, $Remote_insurance_no, "active finally function", $Person_id);
			try
			{
				if ($link != null)
				{
					if ($status_code != "")
						$data_status = modify_order_state($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $status_code, false);
					if ($data["status"] == "true" && count($data_status) > 0 && $data_status["status"] == "false")
						$data = $data_status;
				
					mysqli_close($link);
					$link = null;
				}
			}
			catch (Exception $e)
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0202";
				$data["responseMessage"]= "Exception error: disconnect!";
				$data["json"]			= "";
			}
		}
	}
	else
	{
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
		$data["json"]			= "";	
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n".$g_exit_symbol."stop meeting exit ->"."\r\n", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>