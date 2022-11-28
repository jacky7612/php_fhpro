<?php
	include "func.php";
	
	global $g_join_meeting_apiurl, $g_join_meeting_max_license, $g_join_meeting_pincode;
	global $g_create_meeting_apiurl, $g_meeting_uid, $g_meeting_pwd;
	global $host, $user, $passwd, $database;
		
	// initial
	$status_code_succeed 	= "L1"; // 成功狀態代碼
	$status_code_failure 	= "L0"; // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	$array4json				= array();
	// $link					= null;
	$Insurance_no 			= ""; // *
	$Remote_insurance_no 	= ""; // *
	$Person_id 				= ""; // *
	$Mobile_no 				= "";
	$json_Person_id 		= "";
	$Sales_id 				= "";
	$status_code 			= "";
	$Member_name			= "";
	$Role 					= "";
	
	$agent_id 				= "";
	$agent_name 			= "";
	$agent_gps_addr 		= "";
	$proposer_id 			= "";
	$proposer_name 			= "";
	$proposer_gps_addr 		= "";
	$insured_id 			= "";
	$insured_name 			= "";
	$insured_gps_addr	 	= "";
	$legalRep_id 			= "";
	$legalRep_name 			= "";
	$legalRep_gps_addr 		= "";
	$order_status			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	//2022/5/5, 第二階段不同角色, 視訊同框 
	//$postdata 				= file_get_contents("php://input",'r'); 
	//echo $postdata;
	//$out 					= json_decode($postdata, true);
	api_get_post_param($token, $Insurance_no, $Remote_insurance_no, $Person_id);
	$Lat 	= isset($_POST['Lat'])	? $_POST['Lat']		: '';
	$Lon	= isset($_POST['Lon'])	? $_POST['Lon']		: '';
	$Addr	= isset($_POST['Addr'])	? $_POST['Addr']	: '';
	
	$Lat	= trim(stripslashes($Lat));
	$Lon	= trim(stripslashes($Lon));
	$Addr	= trim(stripslashes($Addr));
	
	/*
	proposer：要保人
	insured：被保人  
	legalRepresentative：法定代理人
	agentOne:業務
	*/
	
	$MEETING_time 			= isset($_POST['MEETING_time']) 			? $_POST['MEETING_time'] 		: '';
	$MEETING_time 			= check_special_char($MEETING_time);
	
	$status_code_succeed = ($MEETING_time == 1) ? "L1" : "R1"; // 成功狀態代碼
	$status_code_failure = ($MEETING_time == 1) ? "L0" : "R0"; // 失敗狀態代碼
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "E123456789";
		$Lat				 = "0.0";
		$Lon				 = "0.0";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."start meeting exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "start meeting entry <-", $Person_id);
	
	// 驗證 security token
	$ret = protect_api("JTG_Start_Meeting", "start meeting exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	// start
	//echo $Insurance_no."\r\n".$Remote_insurance_no."\r\n".$Person_id."\r\n".$Lat."\r\n".$Lon."\r\n";
	if ($Insurance_no 			!= '' &&
		$Remote_insurance_no	!= '' &&
		$Person_id				!= '' &&
		$Lat 					!= '' &&
		$Lon 					!= '')
	{
		try
		{
			$data = create_connect($link, $Insurance_no, $Remote_insurance_no, $Person_id);
			if ($data["status"] == "false") return;

			// 取得保單所有角色
			$retRoleInfo = get_role_from_json($link, $Insurance_no, $Remote_insurance_no, $Person_id, false);
			for ($i = 0; $i < count($retRoleInfo); $i++)
			{
				$roleInfo = $retRoleInfo[$i];
				for ($j = 0; $j < count($roleInfo); $j++)
				{
					// 取得進入會議室id對應角色
					if ($roleInfo[$j]["idcard"] == $Person_id)
					{
						switch ($roleInfo[$j]["roleKey"])
						{
							case "agentOne":
								$agent_name 			= $roleInfo[$j]["name"];
								$agent_id 				= $roleInfo[$j]["idcard"];
								$agent_gps_addr 		= $Addr;
								break;
							case "proposer":
								$proposer_name 			= $roleInfo[$j]["name"];
								$proposer_id 			= $roleInfo[$j]["idcard"];
								$proposer_gps_addr	 	= $Addr;
								break;
							case "insured":
								$insured_name 			= $roleInfo[$j]["name"];
								$insured_id 			= $roleInfo[$j]["idcard"];
								$insured_gps_addr	 	= $Addr;
								break;
							case "legalRepresentative":
								$legalRep_name 			= $roleInfo[$j]["name"];
								$legalRep_id 			= $roleInfo[$j]["idcard"];
								$legalRep_gps_addr	 	= $Addr;
								break;
						}
					}
				}
			}
	
			// 會議室 api url
			// 取得pin code and maxlicense from vmrule
			$main_url 	= $g_join_meeting_apiurl;
			$maxlicense = $g_join_meeting_max_license;
			$pincode 	= $g_join_meeting_pincode;
			
			$sql = "select * from vmrule where id = 1";
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					while ($row = mysqli_fetch_array($result))
					{
						$pincode 	= $row['pincode'];
						$maxlicense = $row['maxlicense'];
					}
				}
			}
			
			$sql = "SELECT * FROM orderinfo where order_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_Insurance_no"	, $Remote_insurance_no	);
			$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Person_id			);
			$sql = $sql." LIMIT 1";

			if ($result = mysqli_query($link, $sql))
			{
				$rcd_count = mysqli_num_rows($result);
				if ($rcd_count > 0)
				{
					// $mid=0;
					// 其他人員-加入會議室-jacky
					// 客戶區域-實際上只有一筆，因有對應身份證id-jacky
					while ($row = mysqli_fetch_array($result))
					{
						//$mid = $row['mid'];
						$order_status = $row['order_status'];
						//
						//每次被呼叫時執行檢查看看是否有過期的會議室未被刪除的,再此刪除
						//
						/*
						$sql = "select * from gomeeting where stoptime < NOW()";
						$result = mysqli_query($link, $sql);
						while($row = mysqli_fetch_array($result)){
							$id = $row['id'];
							$vmr = $row['vmr'];
							$sql = "update vmrinfo SET status = '0' where vid = '".$vmr."'";  //釋放
							$ret = mysqli_query($link, $sql);
							
							$sql = "delete  from gomeeting where id = $id";
							$ret = mysqli_query($link, $sql);
						}
						*/
						
						
						//搜尋是否已開啟會議室,而且時間限制還未到,有可能是斷線重連的
						//$sql = "select * from gomeeting where stoptime > NOW() and Insurance_no='".$Insurance_no."' LIMIT 1";
						//新版, 不需要檢查stoptime過期與否, 因為會有定期檢查會議室是否還在使用的程式來處理
						$sql = "select * from gomeeting where Insurance_no='".$Insurance_no."'";
						$sql = $sql.merge_sql_string_if_not_empty("remote_Insurance_no"	, $Remote_insurance_no	);
						$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Person_id			);
						$sql = $sql." LIMIT 1";
						
						// join 加入已開啟的會議室
						JTG_wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
						if ($ret = mysqli_query($link, $sql))
						{
							$rcd_count = mysqli_num_rows($ret);
							if ($rcd_count > 0)
							{
								JTG_wh_log($Insurance_no, $Remote_insurance_no, "meeting exists that you can join meeting...", $Person_id);
								//有此會議室
								while ($row = mysqli_fetch_array($ret))
								{
									$meeting_id  = trim(stripslashes($row['meetingid']));
									$access_code = trim(stripslashes($row['accesscode']));
									
									// 計算累積上線數，並取得顯示資訊showName
									$gps = "<+".$Lat.",+".$Lon.">";
									$showName = "";
									$countp = 0;
									$countp = calculate_meeting_count($showName, $agent_id	 , "業務_"		, $agent_name	, $countp);
									$countp = calculate_meeting_count($showName, $proposer_id, "要保人_"	, $proposer_name, $countp);
									$countp = calculate_meeting_count($showName, $insured_id , "被保人_"	, $insured_name	, $countp);
									$countp = calculate_meeting_count($showName, $legalRep_id, "法定代理人_", $legalRep_name, $countp);
									$showName .= $gps;
									
									if ($agent_id != '')
										$meetingurl = $main_url."/webapp/#/?callType=Video&conference=".$access_code."&".$showName."&join=1&media=1&pin=".$pincode;
									else
										$meetingurl = $main_url."/webapp/#/?callType=Video&conference=".$access_code."&".$showName."&join=1&media=1&role=guest";
									
									//update 線上 人數 DB
									$sql = "update gomeeting SET count=count+$countp  where Insurance_no='".$Insurance_no."'";
									$sql = $sql.merge_sql_string_if_not_empty("remote_Insurance_no", $Remote_insurance_no);
									$ret = mysqli_query($link, $sql);
									JTG_wh_log($Insurance_no, $Remote_insurance_no, "update meeting person count = ".$countp, $Person_id);
									//echo "update 線上 人數 DB :".$sql."\r\n";
									
									//update GPS
									//if($Role != "0") //業務是新增的
									$gps = $Lat.",".$Lon;
									$data_meetinglog = modify_meetinglog($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id,
																		  $meeting_id, $vid, $stime, $etime, $gps,
																		  $agent_id		, $agent_gps_addr	,
																		  $proposer_id	, $proposer_gps_addr,
																		  $insured_id	, $insured_gps_addr	,
																		  $legalRep_id	, $legalRep_gps_addr, false);
									$array4json["meetingurl"]		= $meetingurl;	
									$array4json["meetingid"]		= $meeting_id;
									$data = result_message("true", "0x0200", "OK", json_encode($array4json));
									$status_code = $status_code_succeed;
									$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
									$data["orderStatus"] = $order_status;
									
									header('Content-Type: application/json');
									echo (json_encode($data, JSON_UNESCAPED_UNICODE));
									JTG_wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 01\r\n", $Person_id);								
									return;
								}
							}
							else
							{
								//還未有會議室,需要新開會議室
								if ($agent_id == '')//只有業務能開啟新會議室,此次呼叫沒有業務，而且沒有ongoing meeting
								{
									$data = result_message("false", "0x0206", "尚未到視訊會議室時間!", "");
									$status_code = $status_code_failure;
									$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
									$data["orderStatus"] = $order_status;
									
									header('Content-Type: application/json');
									echo (json_encode($data, JSON_UNESCAPED_UNICODE));
									JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["responseMessage"]." exit step 01\r\n", $Person_id);
									return;
								}
							}
						}
					}
					
					// add by jacky 20221109 - start
					if ($agent_id == '') // Double check
					{
						if ($data["status"] == "true")
						{
							$data = result_message("false", "0x0206", "非業務員無法進行往後動作!", "");
						}
						else
						{
							$data["responseMessage"] = $data["responseMessage"]."\r\n非業務員無法進行往後動作!";
						}
						$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
						$data["orderStatus"] = $order_status;
						
						header('Content-Type: application/json');
						echo (json_encode($data, JSON_UNESCAPED_UNICODE));
						JTG_wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 01\r\n", $Person_id);
						return;
					}
					// add by jacky 20221109 - end
					
					// 業務員建立會議室-jacky
					try
					{
						JTG_wh_log($Insurance_no, $Remote_insurance_no, "準備建立會議室\r\n", $Person_id);
						$mainurl 	= $g_create_meeting_apiurl;
						$url 		= $mainurl."post/api/token/request";

						//1. GET Token
						$out = get_meeting_token4api($Insurance_no, $Remote_insurance_no, $Person_id, $g_create_meeting_apiurl, $g_meeting_uid, $g_meeting_pwd);
						
						$ret = json_decode($out, true);
						JTG_wh_log($Insurance_no, $Remote_insurance_no, "準備建立會議室", $Person_id);
						
						if (strpos($out, "\"success\"") && strlen($out) > 0 && $ret['success'] == true)
						{
							JTG_wh_log($Insurance_no, $Remote_insurance_no, " get meeting token succeed", $Person_id);
							$token = $ret['token'];
							$status_code = $status_code_succeed;
						}
						else
						{
							// update status vmrinfo
							$err = ""; $vid = "";
							try
							{
								$sql = "update vmrinfo SET status=0 where vid=$vid"; // [怪]未先搜尋vmrinfo並取得vid - jacky
								$ret = mysqli_query($link, $sql);
							}
							catch (Exception $e)
							{
								$data = result_message("false", "0x0209", "Get Token Failed! Exception", "");
								$err = $e->getMessage();
								JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
							}
							$data = result_message("false", "0x0206", "Get Token Failed!".((strlen($err) > 0) ? " except".$err : ""), "");
							$status_code = $status_code_failure;
							$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
							$data["orderStatus"] = $order_status;
							
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["responseMessage"]." exit step 02.2\r\n", $Person_id);
							return;
						}
						
						//開會議室之前須檢查兩件事
						//1. 是否超過maxlicense最大人數限制
						//2. 是否超過會議室的資源了(最保險的事假設每間會議室最少2人, 這樣資源要開50 間)
						$max = 0;
						$sql = "select SUM(count) as max from gomeeting where 1";
						$result = mysqli_query($link, $sql);
						if (is_null($result) == false && empty($result) == false)
						{
							JTG_wh_log($Insurance_no, $Remote_insurance_no, $sql." succeed, max = ".$max, $Person_id);
							while ($row = mysqli_fetch_array($result))
								$max = $row['max'];
						}
						if (empty($max)) $max = 0; // add code by jacky 20221125
						JTG_wh_log($Insurance_no, $Remote_insurance_no, "是否超過maxlicense最大人數限制 max = ".$max, $Person_id);
						
						if (intval($max) > intval($maxlicense))
						{
							$data = result_message("false", "0x0206", "超過會議室人數上限,請稍後再開啟視訊會議", "");
							$status_code = $status_code_failure;
							$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
							$data["orderStatus"] = $order_status;
							
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["responseMessage"]." exit step 03\r\n", $Person_id);
							return;//超過會議室的上限了
						}
						//$log = "max people:".$max;
						//JTG_wh_log($log);
						
						$vmrenough = 0;
						// 只有vmrinfo releae 超10分鐘以上的才可以拿來用,避免調閱檔案問題,以及重複進入問題
						// $sql = "begin";				// 2022-11-26 mark by jacky 這行出現後，sql語法無法正常
						// mysqli_query($link, $sql);	// 2022-11-26 mark by jacky 這行出現後，sql語法無法正常
						
						/* 測試$link什麼時候失效的工具-start */
						//echo "會議室 GET Token :".$token."\r\n";
						//			$sql = "update vmrinfo SET status=0, updatetime=NOW() where vid=1020";
						//			$ret = mysqli_query($link, $sql);
						//echo "sql :".$sql."\r\n";
						//echo "ret :".$ret."\r\n";
						/* 測試$link什麼時候失效的工具-end */
						
						$sql = "select * from vmrinfo where status = 0 and TIMESTAMPDIFF(MINUTE, updatetime, NOW())>10 order by RAND()";
						JTG_wh_log($Insurance_no, $Remote_insurance_no, "vmrinfo sql prepare", $Person_id);
						
						if (empty($g_test_vmr_id) == false) $sql = "select * from vmrinfo where status = 0 and vmr='".$g_test_vmr_id."'";
						
						// 檢查會議室是否 > 10分鐘
						if ($result = mysqli_query($link, $sql))
						{
							if (mysqli_num_rows($result) > 0)
							{
								JTG_wh_log($Insurance_no, $Remote_insurance_no, "只有vmrinfo releae 超10分鐘 :".$sql, $Person_id);
								
								while ($row = mysqli_fetch_array($result))
								{
									$vmr = trim(stripslashes($row['vmr']));
									$vid = trim(stripslashes($row['vid']));
									//先保護
									$sql = "update vmrinfo SET status=1, updatetime=NOW() where vid=$vid";
									$ret = mysqli_query($link, $sql);
									JTG_wh_log($Insurance_no, $Remote_insurance_no, "先保護 :".$sql, $Person_id);
									
									$header = array('X-frSIP-API-Token:'.$token);
									//check $vid 是否還有人在線上
									// 先得到目前線上的所有參與者
									$url 							= $mainurl."get/skypeforbusiness/skypeforbusinessgatewayparticipant/view/list";
									$data_input2					= array();
									$data_input2['gateway'] 		= _MEETING_GATEWAY;
									$data_input2['service_type'] 	= 'conference';	
									$data_input2['start'] 			= '0';
									$data_input2['limit'] 			= '9999';
									JTG_wh_log($Insurance_no, $Remote_insurance_no, "try call OptMeeting api ", $Person_id);
									$out = CallAPI4OptMeeting("GET", $url, $data_input2, $header);
									JTG_wh_log($Insurance_no, $Remote_insurance_no, (strlen($out) > 0) ? "try call OptMeeting succeed " : "(X) try call OptMeeting failure ", $Person_id);
									
									$partdata = json_decode($out, true);
									$bnext = 0;
									if (is_array($partdata))
									{
										foreach ($partdata['list'] as $part)
										{
											JTG_wh_log($Insurance_no, $Remote_insurance_no, $part['conference'].":".$part["display_name"], $Person_id);
											echo $part['conference'].":".$part["display_name"]."\r\n"; // 再檢視是否必要jacky
											if ($part['conference'] == $vid)
											{
												//此會議室有人占用,所以狀態有誤, 可能是用網路連結,非透過api
												//重新取用新的
												$bnext = 1;
												JTG_wh_log($Insurance_no, $Remote_insurance_no, "此會議室有人占用,所以狀態有誤, 可能是用網路連結,非透過api", $Person_id);
												break;
											}
										}
									}
									
									//此會議室有人占用，重新取用新的
									if($bnext == 1)
									{
										$sql = "update vmrinfo SET status=0, updatetime=NOW() where vid=$vid";
										$ret = mysqli_query($link, $sql);											
										continue; // next one	
									}
									
									// 會議室建立成功，離開此while
									// update status vmrinfo
									JTG_wh_log($Insurance_no, $Remote_insurance_no, "update status vmrinfo", $Person_id);
									$sql = "update vmrinfo SET status=1, updatetime=NOW() where vid=$vid";
									$ret = mysqli_query($link, $sql);		
									$vmrenough = 1;
									break;
								}
							}
							else
							{
								$data = result_message("false", "0x0206", "超過會議室上限,請稍後再開啟視訊會議", "");
								$status_code = $status_code_failure;
								$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
								$data["orderStatus"] = $order_status;
							
								header('Content-Type: application/json');
								echo (json_encode($data, JSON_UNESCAPED_UNICODE));
								// $sql = "commit"; 			// [怪]mark這段很奇怪-jacky
								// mysqli_query($link, $sql); 	// [怪]mark這段很奇怪-jacky
								JTG_wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 04\r\n", $Person_id);
								return;//超過會議室的上限了
							}
						}
						else
						{
							$data = result_message("false", "0x0206", "會議室目前都在使用中", "");
							$status_code = $status_code_failure;
							$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
							$data["orderStatus"] = $order_status;
							
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							JTG_wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 04.1\r\n", $Person_id);
							return;//超過會議室的上限了
						}
						
						// $sql = "commit"; 			// [怪]mark這段很奇怪-jacky
						// mysqli_query($link, $sql); 	// [怪]mark這段很奇怪-jacky
						if ($vmrenough == 0)
						{
							$data = result_message("false", "0x0206", "超過會議室上限,請稍後再開啟視訊會議", "");
							$status_code = $status_code_failure;
							$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
							$data["orderStatus"] = $order_status;
							
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							JTG_wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 05\r\n", $Person_id);
							return;//超過會議室的上限了							
						}
						
						// Double check
						if ($agent_id == '') // 只有業務能開啟新會議室
						{
							$data = result_message("false", "0x0205", "客戶無權限發起會議!", "");
							$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
							$data["orderStatus"] = $order_status;
							
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
							JTG_wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 06\r\n", $Person_id);
							return;
						}
						
						$header = array('X-frSIP-API-Token:'.$token);
					
						//從accesscode取得access_code
						$access_code  = 0;
						$sql = "select * from accesscode where deletecode = 0 and vid=$vid ORDER BY updatetime ASC;";
						JTG_wh_log($Insurance_no, $Remote_insurance_no, "從accesscode取得access_code prepare sql string :".$sql, $Person_id);
						$result = mysqli_query($link, $sql);
						if (is_null($result) == false && empty($result) == false)
						{
							$rcd_count = mysqli_num_rows($result);
							JTG_wh_log($Insurance_no, $Remote_insurance_no, "find record count :".$sql, $Person_id);
							if ($rcd_count > 0)
							{
								while ($row = mysqli_fetch_array($result))
								{
									$access_code = $row['code'];
									$meeting_id = $row['meetingid'];
									break;
								}
							}
						}
						
						if ($access_code == 0)
						{
							//restore status vmrinfo
							$sql  					 = "update vmrinfo SET status=0 , updatetime=NOW() where vid=$vid";
							$ret  					 = mysqli_query($link, $sql);
							$data = result_message("false", "0x0206", "系統忙碌,請稍後再開啟視訊會議", "");
							$status_code = $status_code_failure;
							$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
							$data["orderStatus"] = $order_status;
							
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							JTG_wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 07\r\n", $Person_id);
							return;//超過會議室的上限了							
						}
						
						// 取得30 * 60秒的時間並回傳
						$stimestamp 				= strtotime(date("Y-m-d H:i:s"));
						$array4json["start_date"]	= date("Y-m-d", $stimestamp);
						$array4json["start_time"]	= date("H:i:s", $stimestamp);
						$stime 						= $array4json["start_date"]." ".$array4json["start_time"];
						$etimestamp 				= strtotime(date("Y-m-d H:i:s")) + 30 * 60;//(3*3600);
						$array4json["stop_date"]	= date("Y-m-d", $etimestamp);
						$array4json["stop_time"]	= date("H:i:s", $etimestamp);
						$etime 						= date("Y-m-d H:i:s", $etimestamp);	
						
						$gps = "<+".$Lat.",+".$Lon.">";
						$showName = "";
						$countp = 0;
						$countp = calculate_meeting_count($showName, $agent_id	 , "業務_"		, $agent_name	, $countp);
						$countp = calculate_meeting_count($showName, $proposer_id, "要保人_"	, $proposer_name, $countp);
						$countp = calculate_meeting_count($showName, $insured_id , "被保人_"	, $insured_name	, $countp);
						$countp = calculate_meeting_count($showName, $legalRep_id, "法定代理人_", $legalRep_name, $countp);
						$showName .= $gps;
						
						$meetingurl = $g_join_meeting_apiurl."/webapp/#/?callType=Video&conference=".$access_code."&".$showName."&join=1&media=1&pin=".$pincode;
						
						//Insert Meeting id to gomeeting
						$data_gomeeting = modify_gomeeting($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id,
															$meeting_id, $access_code, $vid, $stime, $etime, $countp, false);
						JTG_wh_log($Insurance_no, $Remote_insurance_no, "access Meeting id to gomeeting complete!", $Person_id);
						//var_dump($data_gomeeting);
						
						//$log = $sql;
						//JTG_wh_log($log);
						//LOG Meeting id for VRMS
						$gps = $Lat.",".$Lon;
						$data_meetinglog = modify_meetinglog($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id,
							  $meeting_id, $vid, $stime, $etime, $gps,
							  $agent_id		, $agent_gps_addr	,
							  $proposer_id	, $proposer_gps_addr,
							  $insured_id	, $insured_gps_addr	,
							  $legalRep_id	, $legalRep_gps_addr, false);
						JTG_wh_log($Insurance_no, $Remote_insurance_no, "access meetinglog sql complete!", $Person_id);
						
						//$meetingurl="https://meet.deltapath.com/webapp/#/?conference=884378136732@deltapath.com&name=錢總&join=1&media";
						$array4json["meetingurl"]		= $meetingurl;
						$array4json["meetingid"]		= $meeting_id;
						$data = result_message("true", "0x0200", "OK", $array4json);
						$status_code = $status_code_succeed;
					}
					catch (Exception $e)
					{
						$data = result_message("false", "0x0209", "業務員區域 - Exception error :".$e->getMessage(), "");
						JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
					}
				}
				else
				{
					$data = result_message("false", "0x0201", "查無資料!", "");
				}
			}
			else
			{
				$data = result_message("false", "0x0208", "SQL fail!", "");
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "Exception error :".$e->getMessage(), "");
			JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
        }
		finally
		{
			$data_close_conn = close_connection_finally($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $status_code);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}
	}
	else
	{
		$data = result_message("false", "0x0202", "API parameter is required!", "");
		$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, true);
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."start meeting exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>