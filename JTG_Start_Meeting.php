<?php
	const _ENV = "PROD"; 
	//const _ENV = "UAT"; 
	include "db_tools.php";
	date_default_timezone_set("Asia/Taipei");

	function wh_log($log_msg)
	{
		$log_filename = "/var/www/html/member/api/log";
		if (!file_exists($log_filename)) 
		{
			// create directory/folder uploads.
			mkdir($log_filename, 0777, true);
		}
		$log_file_data = $log_filename.'/log_StartingMeeting' . date('d-M-Y') . '.log';
		// if you don't add `FILE_APPEND`, the file will be erased each time you add a log
		file_put_contents($log_file_data, date("Y-m-d H:i:s")."  ------  ".$log_msg . "\n", FILE_APPEND);
	} 

	function CallAPI($method, $url, $data = false, $header = null)
	{
		$curl = curl_init();

		switch ($method)
		{
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);

				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				
				//curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				if($header != null)
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				//echo $url;			
				break;
			 case "GET":
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));			
				//curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
				if($header != null)
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);			
				break;
		   case "PUT":
				curl_setopt($curl, CURLOPT_PUT, 1);
				break;
			default:
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}

		// Optional Authentication:
		//curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		//curl_setopt($curl, CURLOPT_USERPWD, "username:password");

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($curl);
		//echo $result;
		curl_close($curl);

		return $result;
	}
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	//2022/5/5, 第二階段不同角色, 視訊同框 
	$postdata = file_get_contents("php://input",'r'); 
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
		try {
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
			if(_ENV == "PROD")
			{
				$main_url = "https://dis-cn1.transglobe.com.tw";
				$LB = rand(1,10);
				if($LB >5 )
				{
					$main_url = "https://dis-cn2.transglobe.com.tw";
				} else {
					$main_url = "https://dis-cn1.transglobe.com.tw";
				}
			} else {
				//UAT
				$main_url = "https://ldi.transglobe.com.tw";
			}
			
			// 取得pin code and maxlicense from vmrule
			$maxlicense = 250;
			$pincode = "53758995";
			$sql = "select * from vmrule where id = 1";
			$result = mysqli_query($link, $sql);
			while($row = mysqli_fetch_array($result)){
				$pincode = $row['pincode'];
				$maxlicense = $row['maxlicense'];
			}			

			$sql = "SELECT * FROM orderinfo where order_trash=0 ";
			if ($Insurance_no != "") {	
				$sql = $sql." and insurance_no='".$Insurance_no."'";
			}
			if ($Remote_insuance_no != "") {
				$sql = $sql." and remote_insuance_no='".$Remote_insuance_no."' LIMIT 1";
			}

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
						if ($Remote_insuance_no != "") {
							$sql = $sql." and remote_insuance_no='".$Remote_insuance_no."' LIMIT 1";
						}
						$ret = mysqli_query($link, $sql);
						if (mysqli_num_rows($ret) > 0)
						{
							//有此會議室
							while($row = mysqli_fetch_array($ret)){
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
								if ($Remote_insuance_no != "") {
									$sql = $sql." and remote_insuance_no='".$Remote_insuance_no."'";
								}
								$ret = mysqli_query($link, $sql);
								
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
									}
									if ($insured_id != '')
									{			
										if (strlen($insured_addr) > 0)
											$sql = "update meetinglog SET insured_id = '$insured_id', insured_gps = '$gps', insured_addr = '$insured_addr'  where meetingid='".$meeting_id."'";
										else
											$sql = "update meetinglog SET insured_id = '$insured_id', insured_gps = '$gps' where meetingid='".$meeting_id."'";
										$ret = mysqli_query($link, $sql);
									}
									if ($legalRep_id != '')
									{			
										if (strlen($legalRep_addr) > 0)								
											$sql = "update meetinglog SET legalRep_id = '$legalRep_id', legalRep_gps = '$gps', legalRep_addr = '$legalRep_addr' where meetingid='".$meeting_id."'";
										else
											$sql = "update meetinglog SET legalRep_id = '$legalRep_id', legalRep_gps = '$gps' where meetingid='".$meeting_id."'";
										$ret = mysqli_query($link, $sql);
									}
								}
								$data=array();
								$data["status"]="true";
								$data["code"]="0x0200";
								$data["responseMessage"]="OK";	
								$data["meetingurl"]=$meetingurl;	
								$data["meetingid"]=$meeting_id;	
								header('Content-Type: application/json');
								echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
								exit;
							}
						} else {
							//還未有會議室,需要新開會議室
							if ($agent_id == '')//只有業務能開啟新會議室,此次呼叫沒有業務，而且沒有ongoing meeting
							{
								$data					 = array();
								$data["status"]			 = "false";
								$data["code"]			 = "0x0205";
								$data["responseMessage"] = "尚未到視訊會議室時間!";
								header('Content-Type: application/json');
								echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
								exit;
							}
						}
					}
					try {
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
						$out 				= CallAPI("POST", $url, $data);
						$ret 				= json_decode($out, true);
						if ($ret['success'] == true)
							$token = $ret['token'];
						else
						{
							if (_ENV == "PROD")
							{
								$mainurl = "http://10.67.65.174/RESTful/index.php/v1/";
								$url = $mainurl."post/api/token/request";
								$data = array();
								$data["username"]="administrator";
								$hash = md5("CheFR63r");
								$data["data"]=md5($hash."@deltapath");
								$out = CallAPI("POST", $url, $data);
								//echo $out;
								$ret = json_decode($out, true);
								if($ret['success'] == true)
									$token = $ret['token'];
								else
								{			
									//update status vmrinfo
									$sql = "update vmrinfo SET status=0 where vid=$vid";
									$ret = mysqli_query($link, $sql);									
									$data["status"]="false";
									$data["code"]="0x0205";
									$data["responseMessage"]="Get Token Failed!";
									header('Content-Type: application/json');
									echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
									exit;
								}
							} else {			
								//update status vmrinfo
								$sql = "update vmrinfo SET status=0 where vid=$vid";
								$ret = mysqli_query($link, $sql);									
								$data["status"]="false";
								$data["code"]="0x0205";
								$data["responseMessage"]="Get Token Failed!";
								header('Content-Type: application/json');
								echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
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
							$data=array();
							$data["status"]="false";
							$data["code"]="0x0207";
							$data["responseMessage"]="超過會議室人數上限,請稍後再開啟視訊會議";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							exit;//超過會議室的上限了
						}
						//$log = "max people:".$max;
						//wh_log($log);
						
						$vmrenough = 0;
						//只有vmrinfo releae 超10分鐘以上的才可以拿來用,避免調閱檔案問題,以及重複進入問題
						$sql = "begin";
						mysqli_query($link, $sql);
						$sql = "select * from vmrinfo where status = 0 and TIMESTAMPDIFF(MINUTE, updatetime, NOW())>10 order by RAND()";
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
								$url = $mainurl."get/skypeforbusiness/skypeforbusinessgatewayparticipant/view/list";
								$data= array();
								$data['gateway'] = '12';
								$data['service_type'] = 'conference';	
								$data['start'] = '0';
								$data['limit'] = '9999';	
								
								$out = CallAPI("GET", $url, $data, $header);
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
								$sql = "update vmrinfo SET status=1, updatetime=NOW() where vid=$vid";
								$ret = mysqli_query($link, $sql);		
								$vmrenough = 1;
								break;
							}
						} else {
							$data=array();
							$data["status"]="false";
							$data["code"]="0x0206";
							$data["responseMessage"]="超過會議室上限,請稍後再開啟視訊會議";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							$log = "max room";
						    wh_log($log);				
							$sql = "commit";
							mysqli_query($link, $sql);							
							exit;//超過會議室的上限了
						}
						$sql = "commit";
						mysqli_query($link, $sql);
						if($vmrenough == 0)
						{
							$data=array();
							$data["status"]="false";
							$data["code"]="0x0206";
							$data["responseMessage"]="超過會議室上限,請稍後再開啟視訊會議";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							$log = "max room";
						    wh_log($log);				
							exit;//超過會議室的上限了							
						}
						
						//Double check
						if($agent_id == '')//只有業務能開啟新會議室
						{
								//restore status vmrinfo
								//$sql = "update vmrinfo SET status=0, updatetime=NOW() where vid=$vid";
								//$ret = mysqli_query($link, $sql);					
							$data=array();
							$data["status"]="false";
							$data["code"]="0x0205";
							$data["responseMessage"]="客戶無權限發起會議!";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
							exit;
						}
						
						$header = array('X-frSIP-API-Token:'.$token);
					
						//從accesscode取得access_code
						$access_code  = 0;
						$sql = "select * from accesscode where deletecode = 0 and vid=$vid ORDER BY updatetime ASC;";
						$result = mysqli_query($link, $sql);
						if (mysqli_num_rows($result) > 0){
							while($row = mysqli_fetch_array($result)){
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
							$data["responseMessage"] ="系統忙碌,請稍後再開啟視訊會議";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));
							$log = "max room";
						    wh_log($log);				
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
							
						$meetingurl="https://ldi.transglobe.com.tw/webapp/#/?callType=Video&conference=".$access_code."&".$showName."&join=1&media=1&pin=".$pincode;
						
						//Insert Meeting id to gomeeting
						$sql1 = "INSERT INTO gomeeting (insurance_no, remote_insuance_no, meetingid, accesscode, vmr, starttime, stoptime, count, updatetime) VALUES ('$Insurance_no', '$Remote_insuance_no', '$meeting_id', '$access_code', '$vid', '$stime', '$etime', $countp, NOW())";
						$ret = mysqli_query($link, $sql1);
						
						//$log = $sql;
						//wh_log($log);
						//LOG Meeting id for VRMS
						$gps = $lat.",".$lon;
						if(strlen($agent_addr)>0)
						{
							if($agent_id != '')
							{
								$sql = "INSERT INTO meetinglog (insurance_no, remote_insuance_no, vid, meetingid, agent_id, agent_gps, agent_addr, bookstarttime, bookstoptime, updatetime) VALUES ('$Insurance_no', '$Remote_insuance_no', '$vid', '$meeting_id', '$agent_id', '$gps', '$agent_addr', '$stime', '$etime', NOW())";
								$ret = mysqli_query($link, $sql);
								//echo $sql;
							}
						
						}
						else
						{
							{
								$sql = "INSERT INTO meetinglog (insurance_no, remote_insuance_no, vid, meetingid, agent_id, agent_gps, bookstarttime, bookstoptime, updatetime) VALUES ('$Insurance_no', '$Remote_insuance_no', '$vid', '$meeting_id', '$agent_id', '$gps', '$stime', '$etime', NOW())";
								$ret = mysqli_query($link, $sql);
							}
						}	
						
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
						$data=array();
						$data["status"]="true";
						$data["code"]="0x0200";
						$data["responseMessage"]="OK";	
						$data["meetingurl"]=$meetingurl;	
						$data["meetingid"]=$meeting_id;	
						//$data["sql"]=$sql1;	
						
					} catch (Exception $e) {
						//$this->_response(null, 401, $e->getMessage());
						//echo $e->getMessage();
						$data["status"]="false";
						$data["code"]="0x0202";
						$data["responseMessage"]=$e->getMessage();							
					}
				} else {
					$data["status"]="false";
					$data["code"]="0x0201";
					$data["responseMessage"]="不存在此要保流水序號的資料!";						
				}
			} else {
				$data["status"]="false";
				$data["code"]="0x0204";
				$data["responseMessage"]="SQL fail!";					
			}
			mysqli_close($link);
		} catch (Exception $e) {
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]=$e->getMessage();					
        }
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	}else{
		//echo "need mail and password!";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));			
	}
?>