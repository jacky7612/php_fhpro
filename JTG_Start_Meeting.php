<?php
	include "func.php";
	
	global $g_join_meeting_apiurl, $g_join_meeting_max_license, $g_join_meeting_pincode;
	global $g_create_meeting_apiurl, $g_create_meeting_user, $g_create_meeting_hash;
		
	// initial
	$status_code_succeed 	= "L1"; // 成功狀態代碼
	$status_code_failure 	= "L0"; // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	$array4json				= array();
	$link					= null;
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
	$agent_gps_address 		= "";
	$proposer_id 			= "";
	$proposer_name 			= "";
	$proposer_gps_address 	= "";
	$insured_id 			= "";
	$insured_name 			= "";
	$insured_gps_address 	= "";
	$legalRep_id 			= "";
	$legalRep_name 			= "";
	$legalRep_gps_address 	= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	//2022/5/5, 第二階段不同角色, 視訊同框 
	//$postdata 				= file_get_contents("php://input",'r'); 
	//echo $postdata;
	//$out 					= json_decode($postdata, true);
	
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Lat 					= isset($_POST['Lat']) 					? $_POST['Lat'] 				: '';
	$Lon 					= isset($_POST['Lon']) 					? $_POST['Lon'] 				: '';
	$Role 					= isset($_POST['Role']) 				? $_POST['Role'] 				: '';
	$Mobile_no 				= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			: '';
	$Addr 					= isset($_POST['Addr']) 				? $_POST['Addr'] 				: '';
	
	//echo $out['insurance_no'];
	$Insurance_no 			= trim(stripslashes($Insurance_no));
	$Remote_insurance_no 	= trim(stripslashes($Remote_insurance_no));
	$Person_id 				= trim(stripslashes($Person_id));
	$Lat 					= trim(stripslashes($Lat));
	$Lon 					= trim(stripslashes($Lon));
	$Role 					= trim(stripslashes($Role));
	$Mobile_no 				= trim(stripslashes($Mobile_no));
	$Addr 					= trim(stripslashes($Addr));
	
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
		$data = result_message("false", "0x0203", "get data failure", "");
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	wh_log($Insurance_no, $Remote_insurance_no, "start meeting entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
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
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

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
								$insured_gps_address 	= $Addr;
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
			
			$sql 		= "select * from vmrule where id = 1";
			if ($result 	= mysqli_query($link, $sql))
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
				if (mysqli_num_rows($result) > 0)
				{
					//$mid=0;
					$order_status = "";
					
					//客戶區域-實際上只有一筆，因有對應身份證id-jacky
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
						wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
						if ($ret = mysqli_query($link, $sql))
						{
							if (mysqli_num_rows($ret) > 0)
							{
								wh_log($Insurance_no, $Remote_insurance_no, "meeting exists that you can join meeting...", $Person_id);
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
									wh_log($Insurance_no, $Remote_insurance_no, "update meeting person count = ".countp, $Person_id);
								
									
									//update GPS
									//if($Role != "0") //業務是新增的
									{//0:業務員  1:要保人 2:被保人 3: 法定代理人
										$gps = $Lat.",".$Lon;
										if ($proposer_id != '')
										{		
											if (strlen($proposer_gps_addr) > 0)
												$sql = "update meetinglog SET proposer_id = '$proposer_id', proposer_gps = '$gps' , proposer_gps_addr = '$proposer_gps_addr' where meetingid='".$meeting_id."'";
											else
												$sql = "update meetinglog SET proposer_id = '$proposer_id', proposer_gps = '$gps' where meetingid='".$meeting_id."'";
											$ret = mysqli_query($link, $sql);
											wh_log($Insurance_no, $Remote_insurance_no, "update meetinglog table proposer info", $Person_id);
										}
										if ($insured_id != '')
										{			
											if (strlen($insured_gps_addr) > 0)
												$sql = "update meetinglog SET insured_id = '$insured_id', insured_gps = '$gps', insured_gps_addr = '$insured_gps_addr'  where meetingid='".$meeting_id."'";
											else
												$sql = "update meetinglog SET insured_id = '$insured_id', insured_gps = '$gps' where meetingid='".$meeting_id."'";
											$ret = mysqli_query($link, $sql);
											wh_log($Insurance_no, $Remote_insurance_no, "update meetinglog table insured info", $Person_id);
										}
										if ($legalRep_id != '')
										{			
											if (strlen($legalRep_gps_addr) > 0)								
												$sql = "update meetinglog SET legalRep_id = '$legalRep_id', legalRep_gps = '$gps', legalRep_gps_addr = '$legalRep_gps_addr' where meetingid='".$meeting_id."'";
											else
												$sql = "update meetinglog SET legalRep_id = '$legalRep_id', legalRep_gps = '$gps' where meetingid='".$meeting_id."'";
											$ret = mysqli_query($link, $sql);
											wh_log($Insurance_no, $Remote_insurance_no, "update meetinglog table legalRep info", $Person_id);
										}
									}
									$array4json["meetingurl"]		= $meetingurl;	
									$array4json["meetingid"]		= $meeting_id;
									$data = result_message("true", "0x0200", "OK", json_encode($array4json));
									$status_code = $status_code_succeed;
									header('Content-Type: application/json');
									echo (json_encode($data, JSON_UNESCAPED_UNICODE));
									wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 01\r\n", $Person_id);								
									return;
								}
							}
							else
							{
								//還未有會議室,需要新開會議室
								if ($agent_id == '')//只有業務能開啟新會議室,此次呼叫沒有業務，而且沒有ongoing meeting
								{
									$data = result_message("false", "0x0205", "尚未到視訊會議室時間!", "");
									$status_code = $status_code_failure;
									header('Content-Type: application/json');
									echo (json_encode($data, JSON_UNESCAPED_UNICODE));
									wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 01\r\n", $Person_id);
									return;
								}
							}
						}
					}
					
					// add by jacky 20221109 - start
					if ($agent_id == '')
					{
						if ($data["status"] == "true")
						{
							$data = result_message("false", "0x0205", "非業務員無法進行往後動作!", "");
						}
						else
						{
							$data["responseMessage"] = $data["responseMessage"]."\r\n非業務員無法進行往後動作!";
						}
						header('Content-Type: application/json');
						echo (json_encode($data, JSON_UNESCAPED_UNICODE));
						wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 01\r\n", $Person_id);
						return;
					}
					// add by jacky 20221109 - end
								
					//業務員區域-jacky
					try
					{
						$mainurl 	= $g_create_meeting_apiurl;
						$url 		= $mainurl."post/api/token/request";

						//1. GET Token
						$data_input1				= array();
						$data_input1["username"]	= $g_create_meeting_user;
						$hash 						= md5($g_create_meeting_hash);
						$data_input1["data"]		= md5($hash."@deltapath");
						$out 						= CallAPI4OptMeeting("POST", $url, $data_input1);
						$ret 						= json_decode($out, true);
						
						if (strlen($out) > 0 && $ret['success'] == true)
						{
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
								$err = $e->getMessage();
							}
							$data = result_message("false", "0x0205", "Get Token Failed!".((strlen($err) > 0) ? " except".$err : ""), "");
							$status_code = $status_code_failure;
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 02.2\r\n", $Person_id);	
							return;
						}
						
						//開會議室之前須檢查兩件事
						//1. 是否超過maxlicense最大人數限制
						//2. 是否超過會議室的資源了(最保險的事假設每間會議室最少2人, 這樣資源要開50 間)
						$max = 0;
						$sql = "select SUM(count) as max from gomeeting where 1";
						$result = mysqli_query($link, $sql);
						while ($row = mysqli_fetch_array($result))
							$max = $row['max'];
						
						if (intval($max) > intval($maxlicense))
						{
							$data = result_message("false", "0x0207", "超過會議室人數上限,請稍後再開啟視訊會議", "");
							$status_code = $status_code_failure;
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 03\r\n", $Person_id);
							return;//超過會議室的上限了
						}
						//$log = "max people:".$max;
						//wh_log($log);
						
						$vmrenough = 0;
						//只有vmrinfo releae 超10分鐘以上的才可以拿來用,避免調閱檔案問題,以及重複進入問題
						$sql = "begin";
						mysqli_query($link, $sql);
						$sql = "select * from vmrinfo where status = 0 and TIMESTAMPDIFF(MINUTE, updatetime, NOW())>10 order by RAND()";
						wh_log($Insurance_no, $Remote_insurance_no, "vmrinfo sql prepare", $Person_id);
						
						// 檢查會議室是否 > 10分鐘
						if ($result = mysqli_query($link, $sql))
						{
							if (mysqli_num_rows($result) > 0)
							{
								while ($row = mysqli_fetch_array($result))
								{
									$vmr = trim(stripslashes($row['vmr']));
									$vid = trim(stripslashes($row['vid']));
									//先保護
									$sql = "update vmrinfo SET status=1, updatetime=NOW() where vid=$vid";
									$ret = mysqli_query($link, $sql);
									
									//check $vid 是否還有人在線上
									// 先得到目前線上的所有參與者
									$url 							= $mainurl."get/skypeforbusiness/skypeforbusinessgatewayparticipant/view/list";
									$data_input2					= array();
									$data_input2['gateway'] 		= '12';
									$data_input2['service_type'] 	= 'conference';	
									$data_input2['start'] 			= '0';
									$data_input2['limit'] 			= '9999';
									wh_log($Insurance_no, $Remote_insurance_no, "try call OptMeeting api ", $Person_id);
									$out = CallAPI4OptMeeting("GET", $url, $data_input2, $header);
									wh_log($Insurance_no, $Remote_insurance_no, (strlen($out) > 0) ? "try call OptMeeting succeed " : "(X) try call OptMeeting failure ", $Person_id);
									
									$partdata = json_decode($out, true);
									$bnext = 0;
									foreach ($partdata['list'] as $part)
									{
										wh_log($Insurance_no, $Remote_insurance_no, $part['conference'].":".$part["display_name"], $Person_id);
										echo $part['conference'].":".$part["display_name"]."\r\n"; // 再檢視是否必要jacky
										if ($part['conference'] == $vid)
										{
											//此會議室有人占用,所以狀態有誤, 可能是用網路連結,非透過api
											//重新取用新的
											$bnext = 1;
											wh_log($Insurance_no, $Remote_insurance_no, "此會議室有人占用,所以狀態有誤, 可能是用網路連結,非透過api", $Person_id);
											break;
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
									wh_log($Insurance_no, $Remote_insurance_no, "update status vmrinfo", $Person_id);
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
								header('Content-Type: application/json');
								echo (json_encode($data, JSON_UNESCAPED_UNICODE));
								// $sql = "commit"; 			// [怪]mark這段很奇怪-jacky
								// mysqli_query($link, $sql); 	// [怪]mark這段很奇怪-jacky
								wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 04\r\n", $Person_id);
								return;//超過會議室的上限了
							}
						}
						else
						{
							$data = result_message("false", "0x0206", "會議室目前都在使用中", "");
							$status_code = $status_code_failure;
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 04.1\r\n", $Person_id);
							return;//超過會議室的上限了
						}
						
						// $sql = "commit"; 			// [怪]mark這段很奇怪-jacky
						// mysqli_query($link, $sql); 	// [怪]mark這段很奇怪-jacky
						if ($vmrenough == 0)
						{
							$data = result_message("false", "0x0206", "超過會議室上限,請稍後再開啟視訊會議", "");
							$status_code = $status_code_failure;
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 05\r\n", $Person_id);
							return;//超過會議室的上限了							
						}
						
						// Double check
						if ($agent_id == '') // 只有業務能開啟新會議室
						{
							$data = result_message("false", "0x0205", "客戶無權限發起會議!", "");
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 06\r\n", $Person_id);
							return;
						}
						
						$header = array('X-frSIP-API-Token:'.$token);
					
						//從accesscode取得access_code
						$access_code  = 0;
						$sql = "select * from accesscode where deletecode = 0 and vid=$vid ORDER BY updatetime ASC;";
						wh_log($Insurance_no, $Remote_insurance_no, "query accesscode table prepare", $Person_id);
						$result = mysqli_query($link, $sql);
						if (mysqli_num_rows($result) > 0)
						{
							while ($row = mysqli_fetch_array($result))
							{
								$access_code = $row['code'];
								$meeting_id = $row['meetingid'];
								break;
							}
						}							
						if ($access_code == 0)
						{
							//restore status vmrinfo
							$sql  					 = "update vmrinfo SET status=0 , updatetime=NOW() where vid=$vid";
							$ret  					 = mysqli_query($link, $sql);
							$data = result_message("false", "0x0206", "系統忙碌,請稍後再開啟視訊會議", "");
							$status_code = $status_code_failure;
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 07\r\n", $Person_id);
							return;//超過會議室的上限了							
						}

						// 取得三小時的時間並回傳
						$stimestamp 				= strtotime(date("Y-m-d H:i:s"));
						$array4json["start_date"]	= date("Y-m-d", $stimestamp);
						$array4json["start_time"]	= date("H:i:s", $stimestamp);
						$stime 						= $array4json["start_date"]." ".$array4json["start_time"];
						$etimestamp 				= strtotime(date("Y-m-d H:i:s"))+1800;//(3*3600);
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
						$sql1 = "INSERT INTO gomeeting (insurance_no, Remote_insurance_no, meetingid, accesscode, vmr, starttime, stoptime, count, updatetime) VALUES ('$Insurance_no', '$Remote_insurance_no', '$meeting_id', '$access_code', '$vid', '$stime', '$etime', $countp, NOW())";
						$ret  = mysqli_query($link, $sql1);
						wh_log($Insurance_no, $Remote_insurance_no, "Insert Meeting id to gomeeting", $Person_id);
						
						//$log = $sql;
						//wh_log($log);
						//LOG Meeting id for VRMS
						// 存在與不存在都insert 感覺不正常，或許要修正 - jacky
						$gps = $Lat.",".$Lon;
						if (strlen($agent_gps_addr) > 0)
						{
							if($agent_id != '')
							{
								$sql = "INSERT INTO meetinglog (insurance_no, remote_insurance_no, vid, meetingid, agent_id, agent_gps, agent_gps_addr, bookstarttime, bookstoptime, updatetime) VALUES ('$Insurance_no', '$Remote_insurance_no', '$vid', '$meeting_id', '$agent_id', '$gps', '$agent_gps_addr', '$stime', '$etime', NOW())";
								$ret = mysqli_query($link, $sql);
							}
						}
						else
						{
							$sql = "INSERT INTO meetinglog (insurance_no, Remote_insurance_no, vid, meetingid, agent_id, agent_gps, bookstarttime, bookstoptime, updatetime) VALUES ('$Insurance_no', '$Remote_insurance_no', '$vid', '$meeting_id', '$agent_id', '$gps', '$stime', '$etime', NOW())";
							$ret = mysqli_query($link, $sql);
						}
						wh_log($Insurance_no, $Remote_insurance_no, "LOG Meeting id for VRMS", $Person_id);
						
						if ($proposer_id != '')
						{		
							if(strlen($proposer_gps_addr)>0)
								$sql = "update meetinglog SET proposer_id = '$proposer_id', proposer_gps = '$gps' , proposer_gps_addr = '$proposer_gps_addr' where meetingid='".$meeting_id."'";
							else
								$sql = "update meetinglog SET proposer_id = '$proposer_id', proposer_gps = '$gps' where meetingid='".$meeting_id."'";
							$ret = mysqli_query($link, $sql);
						}
						if ($insured_id != '')
						{			
							if(strlen($insured_gps_addr)>0)
								$sql = "update meetinglog SET insured_id = '$insured_id', insured_gps = '$gps', insured_gps_addr = '$insured_gps_addr'  where meetingid='".$meeting_id."'";
							else
								$sql = "update meetinglog SET insured_id = '$insured_id', insured_gps = '$gps' where meetingid='".$meeting_id."'";
							$ret = mysqli_query($link, $sql);
						}
						if ($legalRep_id != '')
						{			
							if(strlen($legalRep_gps_addr)>0)								
								$sql = "update meetinglog SET legalRep_id = '$legalRep_id', legalRep_gps = '$gps', legalRep_gps_addr = '$legalRep_gps_addr' where meetingid='".$meeting_id."'";
							else
								$sql = "update meetinglog SET legalRep_id = '$legalRep_id', legalRep_gps = '$gps' where meetingid='".$meeting_id."'";
							$ret = mysqli_query($link, $sql);
						}
						wh_log($Insurance_no, $Remote_insurance_no, "access meetinglog sql :". $sql, $Person_id);
						
						//$meetingurl="https://meet.deltapath.com/webapp/#/?conference=884378136732@deltapath.com&name=錢總&join=1&media";
						$array4json["meetingurl"]		= $meetingurl;
						$array4json["meetingid"]		= $meeting_id;
						$data = result_message("true", "0x0200", "OK", json_encode($array4json));
						$status_code = $status_code_succeed;
					}
					catch (Exception $e)
					{
						$data = result_message("false", "0x0202", "Exception error [inner] :".$e->getMessage(), "");
					}
				}
				else
				{
					$data = result_message("false", "0x0201", "不存在此要保流水序號的資料!", "");
				}
			}
			else
			{
				$data = result_message("false", "0x0204", "SQL fail!", "");
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0202", "Exception error [outer] :".$e->getMessage(), "");
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
				$data = result_message("false", "0x0202", "Exception error: disconnect!", "");
			}
			wh_log($Insurance_no, $Remote_insurance_no, "finally complete - status:".$status_code, $Person_id);
		}
	}
	else
	{
		$data = result_message("false", "0x0203", "API parameter is required!", "");
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n".$g_exit_symbol."start meeting exit ->"."\r\n", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>