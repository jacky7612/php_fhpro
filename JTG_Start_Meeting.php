<?php
	include "func.php";
	
	const _ENV = "PROD"; 
	//const _ENV = "UAT"; 
		
	// Api ------------------------------------------------------------------------------------------------------------------------
	//2022/5/5, 第二階段不同角色, 視訊同框 
	$postdata 				= file_get_contents("php://input",'r'); 
	//echo $postdata;
	$out 					= json_decode($postdata, true);
	
	//echo $out['insurance_no'];
	$Insurance_no 			= trim(stripslashes($out['Insurance_no']));
	$Remote_insuance_no 	= trim(stripslashes($out['Remote_insuance_no']));
	$lat 					= trim(stripslashes($out['Lat']));
	$lon 					= trim(stripslashes($out['Lon']));
	
	$Role 					= trim(stripslashes($out['Role']));
	$Mobile_no 				= trim(stripslashes($out['Mobile_no']));
	$Addr 					= trim(stripslashes($out['Addr']));
	
	/*
	proposer：要保人
	insured：被保人  
	legalRepresentative：法定代理人
	agentOne:業務
	*/
	
	$agent_id 				= trim(stripslashes($out['Agent_id']));
	$agent_name 			= trim(stripslashes($out['Agent_name']));
	$agent_address 			= trim(stripslashes($out['Agent_address']));

	$proposer_id 			= trim(stripslashes($out['Proposer_id']));
	$proposer_name 			= trim(stripslashes($out['Proposer_name']));
	$proposer_addr 			= trim(stripslashes($out['Proposer_address']));

	$insured_id 			= trim(stripslashes($out['Insured_id']));
	$insured_name 			= trim(stripslashes($out['Insured_name']));
	$insured_addr 			= trim(stripslashes($out['Insured_address']));

	$legalRep_id 			= trim(stripslashes($out['LegalRep_id']));
	$legalRep_name 			= trim(stripslashes($out['LegalRep_name']));
	$legalRep_addr 			= trim(stripslashes($out['LegalRep_address']));

	//echo $legalRep_id;
	//if($legalRep_id == '')//沒有傳入參數
		//echo "eee";
	//var_dump($out);
	//exit;
	
	$MEETING_time 			= isset($_POST['MEETING_time']) 			? $_POST['MEETING_time'] 		: '';
	$MEETING_time 			= check_special_char($MEETING_time);
	
	$Person_id = $proposer_id;
	// 驗證 security token
	$headers = apache_request_headers();
	$token 	 = $headers['Authorization'];
	if(check_header($key, $token) == true)
	{
		wh_log($Insurance_no, $Remote_insurance_no, "security token succeed", $Person_id);
	}
	else
	{
		;//echo "error token";
		$data = array();
		$data["status"]			= "false";
		$data["code"]			= "0x0209";
		$data["responseMessage"]= "Invalid token!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(X) security token failure", $Person_id);
		exit;							
	}
	
	$status_code_succeed = "L1"; // 成功狀態代碼
	$status_code_failure = "L0"; // 失敗狀態代碼
	$status_code_succeed = ($MEETING_time == 1) ? "L1" : "R1"; // 成功狀態代碼
	$status_code_failure = ($MEETING_time == 1) ? "L0" : "R0"; // 失敗狀態代碼
	$status_code = "";
	wh_log($Insurance_no, $Remote_insurance_no, "start meeting entry <-", $Person_id);
	
	// 當資料不齊全時，從資料庫取得
	if (($Member_name 	== '') ||
		($Mobile_no 	== '') ||
		($Role 			== ''))
	{
		$memb 		 = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id);
		$Mobile_no 	 = $memb["mobile_no"];
		$Member_name = $memb["member_name"];
		$Role 		 = $memb["role"];
	}
	$Sales_Id = $agent_id;

	if (($Insurance_no 			!= '') &&
		($Remote_insuance_no	!= '') &&
		($lat 					!= '') &&
		($lon 					!= ''))
	{
		//check 帳號/密碼
		//$host = 'localhost';
		//$user = 'tglmember_user';
		//$passwd = 'tglmember210718';
		//$database = 'tglmemberdb';
		//echo $sql;
		//exit;
		$link = null;
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			//$Insurance_no  = mysqli_real_escape_string($link,$Insurance_no);
			//$Member_name  = mysqli_real_escape_string($link,$Member_name);
			//$Role  = mysqli_real_escape_string($link,$Role);
			//$lat  = mysqli_real_escape_string($link,$lat);
			//$lon  = mysqli_real_escape_string($link,$lon);
			//$Person_id  = mysqli_real_escape_string($link,$Person_id);
			//$addr  = mysqli_real_escape_string($link,$addr);
			
			//PROD
			if (_ENV == "PROD")
			{
				$main_url = "https://dis-cn1.transglobe.com.tw";
				$LB = rand(1, 10);
				if ($LB > 5)
					$main_url = "https://dis-cn2.transglobe.com.tw";
				else
					$main_url = "https://dis-cn1.transglobe.com.tw";
			}
			else
			{
				//UAT
				$main_url = "https://ldi.transglobe.com.tw";
			}
			
			// 取得pin code and maxlicense from vmrule
			$maxlicense = 250;
			$pincode 	= "53758995";
			$sql 		= "select * from vmrule where id = 1";
			$result 	= mysqli_query($link, $sql);
			while ($row = mysqli_fetch_array($result))
			{
				$pincode 	= $row['pincode'];
				$maxlicense = $row['maxlicense'];
			}
			
			$sql = "SELECT * FROM orderinfo where order_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insuance_no"	, $Remote_insuance_no	);
			$sql = $sql." LIMIT 1";

			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					//$mid=0;
					$order_status="";
					while($row = mysqli_fetch_array($result))
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
						$sql = $sql.merge_sql_string_if_not_empty("remote_insuance_no"	, $Remote_insuance_no	);
						$sql = $sql." LIMIT 1";
						
						wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
						$ret = mysqli_query($link, $sql);
						if (mysqli_num_rows($ret) > 0)
						{
							wh_log($Insurance_no, $Remote_insurance_no, "meeting exists that you can join meeting...", $Person_id);
							//有此會議室
							while ($row = mysqli_fetch_array($ret))
							{
								$meeting_id = trim(stripslashes($row['meetingid']));
								$access_code = trim(stripslashes($row['accesscode']));
								
								$gps = "<+".$lat.",+".$lon.">";
								$showName = "";
								$countp = 0;
								if ($agent_id != '')
								{
									if (strlen($showName) <= 0)
										$showName .= "name=業務_".$agent_name;
									else
										$showName .= ", 業務_".$agent_name;
									$countp ++;
								}
								if ($proposer_id != '')
								{
									if (strlen($showName) <= 0)
										$showName .= "name=要保人_".$proposer_name;
									else
										$showName .= ", 要保人_".$proposer_name;									
									$countp ++;
								}
								if ($insured_id != '')
								{
									if (strlen($showName) <= 0)
										$showName .= "name=被保人_".$insured_name;
									else
										$showName .= ", 被保人_".$insured_name;									
									$countp ++;
								}
								if ($legalRep_id != '')
								{
									if (strlen($showName) <= 0)
										$showName .= "name=法定代理人_".$legalRep_name;
									else
										$showName .= ", 法定代理人_".$legalRep_name;
									$countp ++;
								}
								$showName .= $gps;
								if ($agent_id != '')
									$meetingurl = $main_url."/webapp/#/?callType=Video&conference=".$access_code."&".$showName."&join=1&media=1&pin=".$pincode;
								else
									$meetingurl = $main_url."/webapp/#/?callType=Video&conference=".$access_code."&".$showName."&join=1&media=1&role=guest";
								
								//update 線上 人數 DB
								$sql = "update gomeeting SET count=count+$countp  where Insurance_no='".$Insurance_no."'";
								$sql = $sql.merge_sql_string_if_not_empty("remote_insuance_no"	, $Remote_insuance_no	);
								$ret = mysqli_query($link, $sql);
								wh_log($Insurance_no, $Remote_insurance_no, "update meeting person count = ".countp, $Person_id);
							
								
								//update GPS
								//if($Role != "0") //業務是新增的
								{//0:業務員  1:要保人 2:被保人 3: 法定代理人
									$gps = $lat.",".$lon;
									if ($proposer_id != '')
									{		
										if (strlen($proposer_addr) > 0)
											$sql = "update meetinglog SET proposer_id = '$proposer_id', proposer_gps = '$gps' , proposer_addr = '$proposer_addr' where meetingid='".$meeting_id."'";
										else
											$sql = "update meetinglog SET proposer_id = '$proposer_id', proposer_gps = '$gps' where meetingid='".$meeting_id."'";
										$ret = mysqli_query($link, $sql);
										wh_log($Insurance_no, $Remote_insurance_no, "update meetinglog table proposer info", $Person_id);
									}
									if ($insured_id != '')
									{			
										if (strlen($insured_addr) > 0)
											$sql = "update meetinglog SET insured_id = '$insured_id', insured_gps = '$gps', insured_addr = '$insured_addr'  where meetingid='".$meeting_id."'";
										else
											$sql = "update meetinglog SET insured_id = '$insured_id', insured_gps = '$gps' where meetingid='".$meeting_id."'";
										$ret = mysqli_query($link, $sql);
										wh_log($Insurance_no, $Remote_insurance_no, "update meetinglog table insured info", $Person_id);
									}
									if ($legalRep_id != '')
									{			
										if (strlen($legalRep_addr) > 0)								
											$sql = "update meetinglog SET legalRep_id = '$legalRep_id', legalRep_gps = '$gps', legalRep_addr = '$legalRep_addr' where meetingid='".$meeting_id."'";
										else
											$sql = "update meetinglog SET legalRep_id = '$legalRep_id', legalRep_gps = '$gps' where meetingid='".$meeting_id."'";
										$ret = mysqli_query($link, $sql);
										wh_log($Insurance_no, $Remote_insurance_no, "update meetinglog table legalRep info", $Person_id);
									}
								}
								$data					= array();
								$data["status"]			= "true";
								$data["code"]			= "0x0200";
								$data["responseMessage"]= "OK";	
								$data["meetingurl"]		= $meetingurl;	
								$data["meetingid"]		= $meeting_id;
								$status_code = $status_code_succeed;
								header('Content-Type: application/json');
								echo (json_encode($data, JSON_UNESCAPED_UNICODE));
								wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 01", $Person_id);								
								exit;
							}
						}
						else
						{
							//還未有會議室,需要新開會議室
							if ($agent_id == '')//只有業務能開啟新會議室,此次呼叫沒有業務，而且沒有ongoing meeting
							{
								$data					 = array();
								$data["status"]			 = "false";
								$data["code"]			 = "0x0205";
								$data["responseMessage"] = "尚未到視訊會議室時間!";
								$status_code = $status_code_failure;
								header('Content-Type: application/json');
								echo (json_encode($data, JSON_UNESCAPED_UNICODE));
								wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 01", $Person_id);
								exit;
							}
						}
					}
					
					
					try
					{
						if(_ENV == "PROD")
							$mainurl = "http://10.67.65.180/RESTful/index.php/v1/";//內網 //PROD
						else
							$mainurl = "http://10.67.70.169/RESTful/index.php/v1/";//內網 //UAT
						//$mainurl = "http://disuat-vdr1.transglobe.com.tw/RESTful/index.php/v1/";//內網
						$url = $mainurl."post/api/token/request";

						//1. GET Token
						$data 				= array();
						$data["username"]	= "administrator";
						$hash 				= md5("CheFR63r");
						$data["data"]		= md5($hash."@deltapath");
						$out 				= CallAPI4OptMeeting("POST", $url, $data);
						$ret 				= json_decode($out, true);
						if ($ret['success'] == true)
							$token = $ret['token'];
						else
						{
							if (_ENV == "PROD")
							{
								$mainurl 			= "http://10.67.65.174/RESTful/index.php/v1/";
								$url 				= $mainurl."post/api/token/request";
								$data 				= array();
								$data["username"]	= "administrator";
								$hash 				= md5("CheFR63r");
								$data["data"]		= md5($hash."@deltapath");
								$out 				= CallAPI4OptMeeting("POST", $url, $data);
								//echo $out;
								$ret = json_decode($out, true);
								if ($ret['success'] == true)
									$token = $ret['token'];
								else
								{			
									//update status vmrinfo
									$sql 					= "update vmrinfo SET status=0 where vid=$vid";
									$ret 					= mysqli_query($link, $sql);									
									$data["status"]			= "false";
									$data["code"]			= "0x0205";
									$data["responseMessage"]= "Get Token Failed!";
									header('Content-Type: application/json');
									echo (json_encode($data, JSON_UNESCAPED_UNICODE));
									wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 02.1", $Person_id);	
									exit;
								}
							} else {			
								//update status vmrinfo
								$sql 					= "update vmrinfo SET status=0 where vid=$vid";
								$ret 					= mysqli_query($link, $sql);									
								$data["status"]			= "false";
								$data["code"]			= "0x0205";
								$data["responseMessage"]= "Get Token Failed!";
								header('Content-Type: application/json');
								echo (json_encode($data, JSON_UNESCAPED_UNICODE));
								wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 02.2", $Person_id);	
								exit;
							}
						}
						
						//開會議室之前須檢查兩件事
						//1. 是否超過100人 
						//2. 是否超過會議室的資源了(最保險的事假設每間會議室最少2人, 這樣資源要開50 間)
						$max = 0;
						$sql = "select SUM(count) as max from gomeeting where 1";
						$result = mysqli_query($link, $sql);
						while ($row = mysqli_fetch_array($result))
						{
							$max = $row['max'];
						}
						if (intval($max) >intval($maxlicense))
						{
							$data					= array();
							$data["status"]			= "false";
							$data["code"]			= "0x0207";
							$data["responseMessage"]= "超過會議室人數上限,請稍後再開啟視訊會議";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit step 03", $Person_id);
							exit;//超過會議室的上限了
						}
						//$log = "max people:".$max;
						//wh_log($log);
						
						$vmrenough = 0;
						//只有vmrinfo releae 超10分鐘以上的才可以拿來用,避免調閱檔案問題,以及重複進入問題
						$sql = "begin";
						mysqli_query($link, $sql);
						$sql = "select * from vmrinfo where status = 0 and TIMESTAMPDIFF(MINUTE, updatetime, NOW())>10 order by RAND()";
						wh_log($Insurance_no, $Remote_insurance_no, "vmrinfo sql prepare", $Person_id);
						$result = mysqli_query($link, $sql);
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
								$url 					= $mainurl."get/skypeforbusiness/skypeforbusinessgatewayparticipant/view/list";
								$data					= array();
								$data['gateway'] 		= '12';
								$data['service_type'] 	= 'conference';	
								$data['start'] 			= '0';
								$data['limit'] 			= '9999';
								
								wh_log($Insurance_no, $Remote_insurance_no, "try call OptMeeting api ", $Person_id);
								$out = CallAPI4OptMeeting("GET", $url, $data, $header);
								//echo $out;
								//exit;
								$partdata = json_decode($out, true);
								//$part = $partdata['list'];
								$bnext = 0;
								foreach ( $partdata['list'] as $part )
								{
									echo $part['conference'];
									echo ":";
									echo $part["display_name"];
									echo "\n";
									if($part['conference'] == $vid)
									{
										//此會議室有人占用,所以狀態有誤, 可能是用網路連結,非透過api
										//重新取用新的
										$bnext = 1;
										wh_log($Insurance_no, $Remote_insurance_no, "此會議室有人占用,所以狀態有誤, 可能是用網路連結,非透過api", $Person_id);
										break;
									}
								}
								if($bnext == 1)
								{
									//釋放
									$sql = "update vmrinfo SET status=0, updatetime=NOW() where vid=$vid";
									$ret = mysqli_query($link, $sql);											
									continue;//next one	
								}
								
								//update status vmrinfo
								wh_log($Insurance_no, $Remote_insurance_no, "update status vmrinfo", $Person_id);
								$sql = "update vmrinfo SET status=1, updatetime=NOW() where vid=$vid";
								$ret = mysqli_query($link, $sql);		
								$vmrenough = 1;
								break;
							}
						}
						else
						{
							$data					= array();
							$data["status"]			= "false";
							$data["code"]			= "0x0206";
							$data["responseMessage"]= "超過會議室上限,請稍後再開啟視訊會議";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							$sql = "commit";
							mysqli_query($link, $sql);
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 04", $Person_id);
							exit;//超過會議室的上限了
						}
						$sql = "commit";
						mysqli_query($link, $sql);
						if($vmrenough == 0)
						{
							$data=array();
							$data["status"]			= "false";
							$data["code"]			= "0x0206";
							$data["responseMessage"]= "超過會議室上限,請稍後再開啟視訊會議";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 05", $Person_id);
							exit;//超過會議室的上限了							
						}
						
						//Double check
						if($agent_id == '')//只有業務能開啟新會議室
						{
								//restore status vmrinfo
								//$sql = "update vmrinfo SET status=0, updatetime=NOW() where vid=$vid";
								//$ret = mysqli_query($link, $sql);					
							$data=array();
							$data["status"]			= "false";
							$data["code"]			= "0x0205";
							$data["responseMessage"]= "客戶無權限發起會議!";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 06", $Person_id);
							exit;
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
							$data 					 = array();
							$data["status"]			 = "false";
							$data["code"]			 = "0x0206";
							$data["responseMessage"] = "系統忙碌,請稍後再開啟視訊會議";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]." exit 07", $Person_id);
							exit;//超過會議室的上限了							
						}

						/*$access_code="1234";
						$meeting_id="5678";
						$vid="99";*/
				
						$stimestamp 		= strtotime(date("Y-m-d H:i:s"));
						$data["start_date"]	= date("Y-m-d", $stimestamp);
						$data["start_time"]	= date("H:i:s", $stimestamp);
						$stime 				= $data["start_date"]." ".$data["start_time"];
						$etimestamp 		= strtotime(date("Y-m-d H:i:s"))+1800;//(3*3600);
						$data["stop_date"]	= date("Y-m-d", $etimestamp);
						$data["stop_time"]	= date("H:i:s", $etimestamp);
						$etime 				= date("Y-m-d H:i:s", $etimestamp);						
					
						$gps = "<+".$lat.",+".$lon.">";
						$showName = "";
						$countp = 0;
						if ($agent_id != '')
						{
							if(strlen($showName) <= 0)
								$showName .= "name=業務_".$agent_name;
							else
								$showName .= ", 業務_".$agent_name;
							$countp ++;
						}
						if ($proposer_id != '')
						{
							if(strlen($showName) <= 0)
								$showName .= "name=要保人_".$proposer_name;
							else
								$showName .= ", 要保人_".$proposer_name;									
							$countp ++;
						}
						if ($insured_id != '')
						{
							if(strlen($showName) <= 0)
								$showName .= "name=被保人_".$insured_name;
							else
								$showName .= ", 被保人_".$insured_name;									
							$countp ++;
						}
						if ($legalRep_id != '')
						{
							if (strlen($showName) <= 0)
								$showName .= "name=法定代理人_".$legalRep_name;
							else
								$showName .= ", 法定代理人_".$legalRep_name;
							$countp ++;
						}				
							
						$showName .= $gps;
							
						$meetingurl = "https://ldi.transglobe.com.tw/webapp/#/?callType=Video&conference=".$access_code."&".$showName."&join=1&media=1&pin=".$pincode;
						
						//Insert Meeting id to gomeeting
						$sql1 = "INSERT INTO gomeeting (insurance_no, remote_insuance_no, meetingid, accesscode, vmr, starttime, stoptime, count, updatetime) VALUES ('$Insurance_no', '$Remote_insuance_no', '$meeting_id', '$access_code', '$vid', '$stime', '$etime', $countp, NOW())";
						$ret = mysqli_query($link, $sql1);
						wh_log($Insurance_no, $Remote_insurance_no, "Insert Meeting id to gomeeting", $Person_id);
						
						//$log = $sql;
						//wh_log($log);
						//LOG Meeting id for VRMS
						// 存在與不存在都insert 感覺不正常，或許要修正 - jacky
						$gps = $lat.",".$lon;
						if (strlen($agent_addr) > 0)
						{
							if($agent_id != '')
							{
								$sql = "INSERT INTO meetinglog (insurance_no, remote_insuance_no, vid, meetingid, agent_id, agent_gps, agent_addr, bookstarttime, bookstoptime, updatetime) VALUES ('$Insurance_no', '$Remote_insuance_no', '$vid', '$meeting_id', '$agent_id', '$gps', '$agent_addr', '$stime', '$etime', NOW())";
								$ret = mysqli_query($link, $sql);
							}
						}
						else
						{
							$sql = "INSERT INTO meetinglog (insurance_no, remote_insuance_no, vid, meetingid, agent_id, agent_gps, bookstarttime, bookstoptime, updatetime) VALUES ('$Insurance_no', '$Remote_insuance_no', '$vid', '$meeting_id', '$agent_id', '$gps', '$stime', '$etime', NOW())";
							$ret = mysqli_query($link, $sql);
						}
						wh_log($Insurance_no, $Remote_insurance_no, "LOG Meeting id for VRMS", $Person_id);
						
						if ($proposer_id != '')
						{		
							if(strlen($proposer_addr)>0)
								$sql = "update meetinglog SET proposer_id = '$proposer_id', proposer_gps = '$gps' , proposer_addr = '$proposer_addr' where meetingid='".$meeting_id."'";
							else
								$sql = "update meetinglog SET proposer_id = '$proposer_id', proposer_gps = '$gps' where meetingid='".$meeting_id."'";
							$ret = mysqli_query($link, $sql);
						}
						if ($insured_id != '')
						{			
							if(strlen($insured_addr)>0)
								$sql = "update meetinglog SET insured_id = '$insured_id', insured_gps = '$gps', insured_addr = '$insured_addr'  where meetingid='".$meeting_id."'";
							else
								$sql = "update meetinglog SET insured_id = '$insured_id', insured_gps = '$gps' where meetingid='".$meeting_id."'";
							$ret = mysqli_query($link, $sql);
						}
						if ($legalRep_id != '')
						{			
							if(strlen($legalRep_addr)>0)								
								$sql = "update meetinglog SET legalRep_id = '$legalRep_id', legalRep_gps = '$gps', legalRep_addr = '$legalRep_addr' where meetingid='".$meeting_id."'";
							else
								$sql = "update meetinglog SET legalRep_id = '$legalRep_id', legalRep_gps = '$gps' where meetingid='".$meeting_id."'";
							$ret = mysqli_query($link, $sql);
						}						
						$log = $sql;
						//wh_log($log);
						
						//$meetingurl="https://meet.deltapath.com/webapp/#/?conference=884378136732@deltapath.com&name=錢總&join=1&media";
						$data					= array();
						$data["status"]			= "true";
						$data["code"]			= "0x0200";
						$data["responseMessage"]= "OK";
						$data["meetingurl"]		= $meetingurl;
						$data["meetingid"]		= $meeting_id;
					}
					catch (Exception $e)
					{
						//$this->_response(null, 401, $e->getMessage());
						//echo $e->getMessage();
						$data["status"]			= "false";
						$data["code"]			= "0x0202";
						$data["responseMessage"]= $e->getMessage();							
					}
				} else {
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "不存在此要保流水序號的資料!";						
				}
			} else {
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";					
			}
			mysqli_close($link);
		}
		catch (Exception $e)
		{
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= $e->getMessage();					
        }
		finally
		{
			wh_log($Insurance_no, $Remote_insurance_no, "active finally function", $Person_id);
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
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."frsip info exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>