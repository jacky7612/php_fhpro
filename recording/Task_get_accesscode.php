<?php
	include("../func.php");
	
	global $g_create_meeting_apiurl, $g_prod_meeting_apiurl, $g_test_vmr_id, $g_test_mode, $g_exit_symbol;
	
	set_time_limit(0);
	$mainurl = $g_create_meeting_apiurl;
	try
	{
		$remote_ip4filename = get_remote_ip_underline();
		wtask_log("Task_get_accesscode", $remote_ip4filename, "Task_get_accesscode entry <-");
		if (file_exists("/tmp/get_accesscode.pid") == true)//還在跑
		{
			if (strtotime(date("Y-m-d H:i:s")) - filemtime("/tmp/get_accesscode.pid") > 3*60*60)//超過3小時
			{
				// 可能不正常離開
			}
			else
			{
				$msg = strtotime(date("Y-m-d H:i:s"))." - ".filemtime("/tmp/get_accesscode.pid");
				echo $msg."\r\n";
				wtask_log("Task_get_accesscode", $remote_ip4filename, $msg."\r\n".$g_exit_symbol."Task_get_accesscode exit ->"."\r\n");
				return;
			}
			touch("/tmp/get_accesscode.pid");
		}
		
		// connect mysql
		$link = mysqli_connect($host, $user, $passwd, $database);
		$data = result_connect_error ($link);
		if ($data["status"] == "false")
		{
			wtask_log("Task_get_accesscode", $remote_ip4filename, "[Task_get_accesscode] ".get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."send otp exit ->"."\r\n");
			return;
		}
		mysqli_query($link, "SET NAMES 'utf8'");

		//1. GET Token
		$out = get_meeting_token("Task_check_frsip", $g_create_meeting_apiurl, $remote_ip4filename, $g_meeting_uid, $g_meeting_pwd);
		if (strpos($out, "\"success\"") == false) return;
		
		$ret = json_decode($out, true);
		if ($ret['success'] == true)
		{
			echo "get token succeed\r\n";
			$token = $ret['token'];
		}
		else
		{
			echo "error";//error;
			if (file_exists("/tmp/get_accesscode.pid") == true) unlink("/tmp/get_accesscode.pid");
			if (_ENV == "PROD")
			{
				$mainurl = $g_prod_meeting_apiurl;
				$out = get_meeting_token("Task_check_frsip", $g_prod_meeting_apiurl, $remote_ip4filename, $g_meeting_uid, $g_meeting_pwd);
				if (strpos($out, "\"success\"") == false) return;
				
				$ret = json_decode($out, true);
				if($ret['success'] == true)
				{
					echo "get prod token succeed\r\n";
					$token = $ret['token'];
				}
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
		//checkvmr($remote_ip4filename, $mainurl, $header,$link);

		// 是否已同步
		$sql = "select * from vmrinfo where 1";
		$result = mysqli_query($link, $sql);
		if (mysqli_num_rows($result) == 0)
		{
			echo "進行同步vmr info\r\n";
			wtask_log("Task_get_accesscode", $remote_ip4filename, "進行同步vmr info");
			symatric_vmr($remote_ip4filename, $mainurl, $header, $link);
			echo "同步vmr info完成\r\n";
		}
		
		// 1. 準備每間會議室房間, 各產生五組accesscode
		$sql = "select * from vmrinfo where 1";
		$result = mysqli_query($link, $sql);
		$rcd_count = mysqli_num_rows($result);
		echo "rcd_count :".$rcd_count."\r\n";
		if ($rcd_count > 0)
		{
			wtask_log("Task_get_accesscode", $remote_ip4filename, "1. 準備每間會議室房間, 各產生五組accesscode");
			while ($row = mysqli_fetch_array($result))
			{
				$vmr = $row['vmr'];
				$vid = $row['vid'];
				$vmr = check_special_char($vmr);
				$vid = check_special_char($vid);
				//update status vmrinfo
				//$sql = "update vmrinfo SET status=1 where vid=$vid";
				//$ret = mysqli_query($link, $sql);	
				$skip = false;
				if (empty($g_test_vmr_id) == false)
				{
					$skip = ($vmr != $g_test_vmr_id);
				}
				
				//2. 先看accesscode 同樣的vmr vid 有幾個, 若不足5個就補足, create virtualmeeting
				if ($skip == false)
				{
					$today 	=  date("Y-m-d");
					$sql 	= "select * from accesscode where deletecode != 1 and vid = '".$vid."' and DATE(updatetime) >= '".$today."' ";
					$ret 	= mysqli_query($link, $sql);
					echo $sql."\r\n\r\n";
					$num = (is_null($ret) == false && empty($ret) == false) ? mysqli_num_rows($ret) : 0;
					for ($i = 0; $i < _MEETING_ACCESSCODE_MAX - intval($num); $i++)
					{
						echo "createaccesscode:".$i."\r\n";
						createaccesscode($remote_ip4filename, $vid, $mainurl, $header, $link, $vmr);
						//break;//for test only, need to remove
					}
					//break;//for test only, need to remove
				}
			}
		}
		else
		{
			echo "vmrinfo data record not found :".$sql."\r\n";
			wtask_log("Task_get_accesscode", $remote_ip4filename, "vmrinfo data record not found :".$sql);
			return;
		}

		echo "先砍掉今天之前的會議室accesscode\r\n";
		//0. 先砍掉今天之前的會議室accesscode
		$today 	=  date("Y-m-d");
		$today 	= check_special_char($today);
		$today  = mysqli_real_escape_string($link,$today);
		$sql 	= "delete  from accesscode where DATE(updatetime) < '".$today."'";
		$ret 	= mysqli_query($link, $sql);
		
		//expired token
		$url 			  = $mainurl."delete/api/token/expire";
		$data["username"] = $g_meeting_uid;
		$out = CallAPI4OptMeeting("POST", $url, $data, $header);
		echo $url."\r\n";
		echo $out."\r\n";
		wtask_log("Task_get_accesscode", $remote_ip4filename, "砍掉今天之前的會議室accesscode :".$out);
		
		if (file_exists("/tmp/get_accesscode.pid") == true) unlink("/tmp/get_accesscode.pid");
		echo "complete!\r\n";
	}
	catch(Exception $e)
	{
		echo "(x)Exception error!".$e->getMessage()."\r\n";
		wtask_log_Exception("Task_get_accesscode", $remote_ip4filename, "Exception error :".$e->getMessage());
	}
	finally
	{
		wtask_log("Task_get_accesscode", $remote_ip4filename, "finally procedure");
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
			wtask_log_Exception("Task_get_accesscode", $remote_ip4filename, "Exception error: disconnect! error :".$e->getMessage());
		}
		wtask_log("Task_get_accesscode", $remote_ip4filename, "finally complete"."\r\n".$g_exit_symbol."Task_get_accesscode exit ->"."\r\n");
	}
	
	// function section
	function createaccesscode($remote_ip4filename, $vid, $mainurl, $header, $link, $vmr)
	{
		global $g_test_mode, $g_test_vmr_id;
		
		$access_code = "";
		try
		{	
			$data				= array();
			$url 				= $mainurl."post/virtualmeeting/virtualmeeting/";
			$data["username"]	= "1000";
			$data["title"]		= "FH Meeting";//name
			$data["location"]	= "";
			$data["company"]	= "";
			
			$stimestamp 		= strtotime(date("Y-m-d H:i:s")) + _MEETING_START_TIME_APPOINTMENT;
			$data["start_date"]	= date("Y-m-d", $stimestamp);
			$data["start_time"]	= date("H:i"  , $stimestamp);
			$etimestamp 		= $stimestamp + _MEETING_END_TIME_APPOINTMENT; // strtotime(date("Y-m-d")." 23:59:00"); // strtotime(date("Y-m-d H:i:s")) + _MEETING_END_TIME_APPOINTMENT;
			$data["stop_date"]	= date("Y-m-d"	, $etimestamp);
			$data["stop_time"]	= date("H:i"	, $etimestamp);
			
			$data["type"]			= "SFBGatewayVMR";
			$data["sfbgatewayvmr"]	= $vmr;
			$data["description"]	= "";
			//$data["type"]="Extension";
			$data["attendees"]		= '[{"attendee_id":"","attendee_name":"test user a","attendee_email":"test@test.test","attendee_number":"9001","attendee_role":"organizer","attendee_autodialout":"0"}]';
			var_dump($data);

			$out = CallAPI4OptMeeting("POST", $url, $data, $header);
			echo $out."\r\n";
			wtask_log("Task_get_accesscode", $remote_ip4filename, $out);
			$ret = json_decode($out, true);
			$meeting_id = ($ret['success'] == true) ? $ret['meeting_id'] : 0;
			
			//3. Get meeting access code
			$url =  $mainurl."get/virtualmeeting/virtualmeeting/view/list";
			$url .= '?start=0&limit=99999&type=&sort=[{"property":"starttime","direction":"DESC"}]';
			$data ="";
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
			echo "access_code :".$access_code."\r\n";
			
			//Insert into accesscode
			if ($meeting_id != 0)
			{
				// $sql = "Insert into accesscode (vid, code, meetingid, updatetime) values ('$vid', '$access_code', '$meeting_id', NOW())";
				$sql = "Insert into accesscode (vid, code, meetingid, updatetime, start_time, end_time) values ('$vid', '$access_code', '$meeting_id', NOW(), '".date('Y-m-d H:i:s', $stimestamp)."', '".date('Y-m-d H:i:s', $etimestamp)."')";
				$ret = mysqli_query($link, $sql);
			}
		}
		catch (Exception $e)
		{
			wtask_log_Exception("Task_get_accesscode", $remote_ip4filename, "Exception error createaccesscode :".$e->getMessage());
			echo "error createaccesscode";
		}
	}
	function checkvmr($remote_ip4filename, $mainurl, $header, $link)
	{
		global $g_exit_symbol, $g_vmr_map_title;
		
		try
		{
			//檢查看看vmr字串是否有變-房間
			$url 	= $mainurl."get/virtualmeeting/virtualmeeting/view/form";
			$data	= "";
			$out 	= CallAPI4OptMeeting("GET", $url, $data, $header);
			$data 	= json_decode($out, true);
			$vmr 	= $data["sfbgatewayvmr"];
			if (is_array($vmr) == false) $vmr = check_special_char($vmr);
			wtask_log("Task_get_accesscode", $remote_ip4filename, "checkvmr url :".$url."\r\n".$g_exit_symbol."result :".$out);

			if (is_array($vmr) == false) return;
			if (count($vmr) > 0)
			{
				//先標註要開始檢查了
				wtask_log("Task_get_accesscode", $remote_ip4filename, "要開始檢查了");

				$sql = "UPDATE vmrinfo SET checkvmr = 1 , updatetime = NOW() where 1";
				mysqli_query($link, $sql);
					
			}
			$vmrgateway = "";
			for ($i = 0; $i < count($vmr); $i++)
			{
				$vmrname 	= $vmr[$i][0];
				//echo "vmrname :".$vmrname."\r\n";
				wtask_log("Task_get_accesscode", $remote_ip4filename, "vmrname :".$vmrname);
				
				$vid1 		= "vmr:".$vmr[$i][1];
				$vid1 		= check_special_char($vid1);
				$vid 		= explode(":", $vid1);
				$msg 		= "vmr msg :".trim($vid[2])."; ".$vid[1];
				//echo $msg."\r\n";
				$vidkey 	= trim($vid[2]);//VID
				wtask_log("Task_get_accesscode", $remote_ip4filename, $msg);
				
				$vmrname 	= check_special_char($vmrname);
				$vidkey 	= check_special_char($vidkey);
				if (strstr($vid[1], $g_vmr_map_title))
				{
					echo "ooooo";
					//更新 or insert
					$vidkey = check_special_char($vidkey);
					$sql 	= "SELECT * from vmrinfo where vid='".$vidkey."'";
					$result = mysqli_query($link, $sql);
					//echo $sql;
					wtask_log("Task_get_accesscode", $remote_ip4filename, $sql);
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
								wtask_log("Task_get_accesscode", $remote_ip4filename, "有變動,須更新");
								break;
							}
							else
							{ //沒變動,但是需標註有檢查過了
								$vidkey = check_special_char($vidkey);
								$sql 	= "UPDATE vmrinfo SET checkvmr = 2, updatetime=NOW() where vid='".$vidkey."'";
								mysqli_query($link, $sql);
								wtask_log("Task_get_accesscode", $remote_ip4filename, "沒變動,但是需標註有檢查過了");
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
						wtask_log("Task_get_accesscode", $remote_ip4filename, "找不到此VID, 需新增");
						//echo $sql;
					}
				}
			}
			echo "\r\n";
			$vmrgateway1 = explode("|", $vmrname);
			$vmrgateway  = $vmrgateway1[0];
			echo "vmrgateway :".$vmrgateway."\r\n";
			wtask_log("Task_get_accesscode", $remote_ip4filename, "vmrgateway :".$vmrgateway);
			//update $vmrgateway
			if (strlen($vmrgateway) > 1)
			{
				$sql = "update vmrrule set gateway = '$vmrgateway' where id = 1";
				mysqli_query($link, $sql);
				wtask_log("Task_get_accesscode", $remote_ip4filename, "update vmrgateway");
			}
			//vmr 已被刪除
			if (count($vmr) > 0)
			{
				$sql = "delete from vmrinfo where checkvmr = 1";
				mysqli_query($link, $sql);
				wtask_log("Task_get_accesscode", $remote_ip4filename, "vmr 已被刪除");
			}
			echo "check vmr finish\n";
			wtask_log("Task_get_accesscode", $remote_ip4filename, "check vmr finish");
		}
		catch (Exception $e)
		{
			wtask_log_Exception("Task_get_accesscode", $remote_ip4filename, "Exception error checkvmr :".$e->getMessage());
			echo "error checkvmr";
		}
	}
	function symatric_vmr($remote_ip4filename, $mainurl, $header, $link)
	{
		$url 					= $mainurl."get/skypeforbusiness/skypeforbusinessgatewayvmr/view/list";
		$data["gateway"]		= _MEETING_GATEWAY; //UAT
		$data["service_type"]	= "conference";
		$data["start"]			= "0";
		$data["limit"]			= "500";

		$out = CallAPI4OptMeeting("GET", $url, $data, $header);
		wtask_log("Task_get_accesscode", $remote_ip4filename, "symatric_vmr 同步vmr info結果 :".$out);
		//先同步vmr info
		//echo  "test";
		checkvmr($remote_ip4filename, $mainurl, $header, $link);
	}
?>