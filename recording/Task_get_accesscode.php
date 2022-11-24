<?php
	include("../func.php");
	
	global $g_create_meeting_apiurl, $g_prod_meeting_apiurl;
	
	set_time_limit(0);

	wtask_log("Task_get_accesscode entry <-");
	if (file_exists("/tmp/get_accesscode.pid") == true)//還在跑
	{
		if (strtotime(date("Y-m-d H:i:s")) - filemtime("/tmp/get_accesscode.pid")> (3*60*60))//超過3小時
		{
			// 可能不正常離開
		}
		else
		{
			$msg = strtotime(date("Y-m-d H:i:s"))." - ".filemtime("/tmp/get_accesscode.pid");
			echo $msg."\r\n";
			wtask_log($msg."\r\n".$g_exit_symbol."Task_get_accesscode exit ->"."\r\n");
			return;
		}
	}
	touch("/tmp/get_accesscode.pid");

	try
	{
		$link = mysqli_connect($host, $user, $passwd, $database);
		$data = result_connect_error ($link);
		if ($data["status"] == "false")
		{
			wtask_log("[Task_get_accesscode] ".get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."send otp exit ->"."\r\n");
			return;
		}
		mysqli_query($link,"SET NAMES 'utf8'");

		$mainurl 	= $g_create_meeting_apiurl;
		$url 		= $mainurl."post/api/token/request";

		//1. GET Token
		$data 				= array();
		//$data["username"]="administrator";
		$data["username"]	= "administrator";
		$hash 				= md5("CheFR63r");
		//$hash 			= md5("sT7m");
		$data["data"]		= md5($hash."@deltapath");
		//echo md5($hash."@deltapath");
		$out = CallAPI4OptMeeting("POST", $url, $data);
		wtask_log($out);
		//echo $out;
		$ret = json_decode($out, true);
		if ($ret['success'] == true)
			$token = $ret['token'];
		else
		{
			echo "error";//error;
			unlink("/tmp/get_accesscode.pid");
			if (_ENV == "PROD")
			{
				$mainurl 			= $g_prod_meeting_apiurl;
				$url 				= $mainurl."post/api/token/request";
				$data 				= array();
				$data["username"]	= "administrator";
				$hash 				= md5("CheFR63r");
				$data["data"]		= md5($hash."@deltapath");
				$out = CallAPI4OptMeeting("POST", $url, $data);
				//echo $out;
				$ret = json_decode($out, true);
				if($ret['success'] == true)
					$token = $ret['token'];
				else
					return;//both crash
			}
			else
				return;
		}

		$header = array('X-frSIP-API-Token:'.$token);
		/*
		$url = $mainurl."get/skypeforbusiness/skypeforbusinessgatewayvmr/view/list";
		$data["gateway"]=_MEETING_GATEWAY; //UAT
		$data["service_type"]="conference";
		$data["start"]="0";
		$data["limit"]="500";

		$out = CallAPI4OptMeeting("GET", $url, $data, $header);
		echo $out;
		return;
		*/
		//先同步vmr info
		//echo  "test";
			//checkvmr($mainurl, $header,$link);

		//1. 準備每間會議室房間, 各產生五組accesscode
		$sql = "select * from vmrinfo where 1";
		$result = mysqli_query($link, $sql);
		if (mysqli_num_rows($result) > 0)
		{
			while ($row = mysqli_fetch_array($result))
			{
				$vmr = $row['vmr'];
				$vid = $row['vid'];
				$vmr = check_special_char($vmr);
				$vid = check_special_char($vid);
				//update status vmrinfo
				//$sql = "update vmrinfo SET status=1 where vid=$vid";
				//$ret = mysqli_query($link, $sql);		

				//2. 先看accesscode 同樣的vmr vid 有幾個, 若不足5個就補足, create virtualmeeting
				$today 	=  date("Y-m-d");
				$sql 	= "select * from accesscode where deletecode != 1 and vid = '".$vid."' and DATE(updatetime) >= '".$today."' ";
				$ret 	= mysqli_query($link, $sql);
				$num 	= mysqli_num_rows($ret);
				for ($i = 0; $i < 3 - intval($num); $i++)
				{
					echo "createaccesscode:".$i;
					createaccesscode($vid, $mainurl, $header,$link,$vmr);
					//break;//for test only, need to remove
				}
				//break;//for test only, need to remove
			}
		}

		//0. 先砍掉今天之前的會議室accesscode
		$today 	=  date("Y-m-d");
		$today 	= check_special_char($today);
		$today  = mysqli_real_escape_string($link,$today);
		$sql 	= "delete  from accesscode where DATE(updatetime) < '".$today."'";
		$ret 	= mysqli_query($link, $sql);
			
		//expired token

		$url = $mainurl."delete/api/token/expire";
		$data["username"]="administrator";
		$out = CallAPI4OptMeeting("POST", $url, $data, $header);	
		
		unlink("/tmp/get_accesscode.pid");
	}
	catch(Exception $e)
	{
		wtask_log_Exception("Exception error :".$e->getMessage());
	}
	finally
	{
		wtask_log("finally procedure");
		try
		{
			if ($link != null)
			{
				mysqli_close($link);
				$link = null;
			}
		}
		catch (Exception $e)
		{
			wtask_log_Exception("Exception error: disconnect! error :".$e->getMessage());
		}
		wtask_log("finally complete"."\r\n".$g_exit_symbol."Task_get_accesscode exit ->"."\r\n");
	}
	
	// function section
	function createaccesscode($vid, $mainurl, $header, $link,$vmr)
	{
		try
		{	
			$data				= array();
			$url 				= $mainurl."post/virtualmeeting/virtualmeeting/";
			$data["username"]	="1000";
			$data["title"]		="FH Meeting";//name
			$data["location"]	="";
			$data["company"]	="";
			
			$data["start_date"]	= date("Y-m-d");
			$data["start_time"]	= "00:00";
			$etimestamp 		= strtotime(date("Y-m-d H:i:s")) + (86400 + 7200);//(3*3600);
			$data["stop_date"]	= date("Y-m-d", $etimestamp);	
			$data["stop_time"]	= date("H:i", $etimestamp);
			
			$data["type"]			= "SFBGatewayVMR";
			$data["sfbgatewayvmr"]	= $vmr;
			$data["description"]	= "";
			//$data["type"]="Extension";
			$data["attendees"]		= '[{"attendee_id":"","attendee_name":"test user a","attendee_email":"test@test.test","attendee_number":"9001","attendee_role":"organizer","attendee_autodialout":"0"}]';
			var_dump($data);

			$out = CallAPI4OptMeeting("POST", $url, $data, $header);
			echo $out;
			wtask_log($out);
			$ret = json_decode($out, true);
			$meeting_id = ($ret['success'] == true) ? $ret['meeting_id'] : 0;
			
			//3. Get meeting access code
			$url =  $mainurl."get/virtualmeeting/virtualmeeting/view/list";
			$url .= '?start=0&limit=99999&type=&sort=[{"property":"starttime","direction":"DESC"}]';
			$data="";
			$out = CallAPI4OptMeeting("GET", $url, $data, $header);
			$ret = json_decode($out, true);
			foreach ( $ret['list'] as $list )
			{
				if ($meeting_id == $list['id'])
				{
					$access_code = $list['access_code'];
					break;
				}
			}
			echo $access_code;
			
			//Insert into accesscode
			if ($meeting_id != 0)
			{
				$sql = "Insert into accesscode (vid, code, meetingid, updatetime) values ('$vid', '$access_code', '$meeting_id', NOW())";
				$ret = mysqli_query($link, $sql);
			}
		}
		catch (Exception $e)
		{
			wtask_log_Exception("Exception error createaccesscode :".$e->getMessage());
			echo "error createaccesscode";
		}
	}
	function checkvmr($mainurl, $header, $link)
	{
		try
		{
			//檢查看看vmr字串是否有變-房間
			$url 	= $mainurl."get/virtualmeeting/virtualmeeting/view/form";
			$data	= "";
			$out 	= CallAPI4OptMeeting("GET", $url, $data, $header);
			$data 	= json_decode($out, true);
			$vmr 	= $data["sfbgatewayvmr"];
			$vmr 	= check_special_char($vmr);

			if (count($vmr) > 0)
			{
				//先標註要開始檢查了
				$sql = "UPDATE vmrinfo SET checkvmr = 1 , updatetime = NOW() where 1";
				mysqli_query($link, $sql);
					
			}
			$vmrgateway = "";
			for ($i = 0; $i < count($vmr); $i++)
			{
				echo "vmr:".$vmr[$i][0]."\n";
				$vmrname 	= $vmr[$i][0];
				$vid1 		= "vmr:".$vmr[$i][1];
				$vid1 		= check_special_char($vid1);
				$vid 		= explode(":", $vid1);
				$msg 		= "vmr:".trim($vid[2])."\r\n".$vid[1];
				echo $msg."\r\n";
				$vidkey 	= trim($vid[2]);//VID
				wtask_log($msg);
				
				$vmrname 	= check_special_char($vmrname);
				$vidkey 	= check_special_char($vidkey);
				if (strstr($vid[1], "Transgolbe_MCU"))
				{
					echo "ooooo";
					//更新 or insert
					$vidkey = check_special_char($vidkey);
					$sql 	= "SELECT * from vmrinfo where vid='".$vidkey."'";
					$result = mysqli_query($link, $sql);
					//echo $sql;
					wtask_log($sql);
					if (mysqli_num_rows($result) > 0)
					{
						while ($row = mysqli_fetch_array($result))
						{
							$vr 	 = $row['vmr'];
							$vr 	 = check_special_char($vr);
							$vidkey  = mysqli_real_escape_string($link,$vidkey);
							if ($vmrname != $vr)
							{//有變動,須更新
								$vidkey = check_special_char($vidkey);
								$sql 	= "UPDATE vmrinfo SET vmr = '$vmrname'  , checkvmr = 2, updatetime=NOW() where vid='".$vidkey."'";
								mysqli_query($link, $sql);
								break;
							}
							else
							{ //沒變動,但是需標註有檢查過了
								$vidkey = check_special_char($vidkey);
								$sql 	= "UPDATE vmrinfo SET checkvmr = 2, updatetime=NOW() where vid='".$vidkey."'";
								mysqli_query($link, $sql);
								break;					
							}
						}
					}
					else
					{
						//找不到此VID, 需新增
						$vidkey 	= check_special_char($vidkey);
						$vmrname 	= check_special_char($vmrname);
						$vidkey  	= mysqli_real_escape_string($link,$vidkey);
						$vmrname  	= mysqli_real_escape_string($link,$vmrname);
						$sql 		= "INSERT INTO vmrinfo (vid, vmr, status, checkvmr, updatetime) VALUES ('$vidkey', '$vmrname', '0', 2, NOW())";
						mysqli_query($link, $sql);
						//echo $sql;
					}
				}
				
			}
			$vmrgateway1 = explode("|", $vmrname);
			$vmrgateway  = $vmrgateway1[0];
			echo "vmrgateway:".$vmrgateway;
			//update $vmrgateway
			if (strlen($vmrgateway) > 1)
			{
				$sql = "update vmrrule set gateway = '$vmrgateway' where id = 1";
				mysqli_query($link, $sql);
			}
			//vmr 已被刪除
			if (count($vmr) > 0)
			{
				$sql = "delete from vmrinfo where checkvmr = 1";
				mysqli_query($link, $sql);
			}
			echo "check vmr finish\n";
			wtask_log("check vmr finish");
		}
		catch (Exception $e)
		{
			wtask_log_Exception("Exception error checkvmr :".$e->getMessage());
			echo "error checkvmr";
		}
	}
?>