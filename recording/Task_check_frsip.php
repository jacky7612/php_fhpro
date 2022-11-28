<?php
	header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

	include("../func.php");
	
	global $g_create_meeting_apiurl, $g_prod_meeting_apiurl;
	
	$mainurl = $g_create_meeting_apiurl;
	set_time_limit(0);
	$link 		= null;
	$data_conn 	= array();
	try
	{
		$remote_ip4filename = get_remote_ip_underline();
		wtask_log("Task_check_frsip", $remote_ip4filename, "Task_check_meetingroom entry <-");

		if (file_exists("/tmp/check_frsip.pid") == true) // 還在跑
		{
			if(strtotime(date("Y-m-d H:i:s")) - filemtime("/tmp/check_frsip.pid") > 3*60*60) // 超過3小時
			{
				// 可能不正常離開
			}
			else
			{
				$msg = strtotime(date("Y-m-d H:i:s"))." - ".filemtime("/tmp/check_frsip.pid");
				echo $msg."\r\n";
				wtask_log("Task_check_frsip", $remote_ip4filename, $msg."\r\n".$g_exit_symbol."Task_check_frsip exit ->"."\r\n");
				return;
			}
			touch("/tmp/check_frsip.pid");
		}
		
		// connect mysql
		$data_conn = task_create_connect($link, "Task_check_frsip", $remote_ip4filename);
		if ($data_conn["status"] == "false") return;
		
		//1. GET Token
		$out = get_meeting_token("Task_check_frsip", $g_create_meeting_apiurl, $remote_ip4filename, $g_meeting_uid, $g_meeting_pwd);
		if (strpos($out, "\"success\"") == false) return;
		
		$ret = json_decode($out, true);
		if($ret['success'] == true)
		{
			echo "get token succeed\r\n";
			$token = $ret['token'];
		}
		else
		{
			//第二次機會
			if(_ENV == "PROD")
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
				{
					echo "error";//error;
					if (file_exists("/tmp/check_frsip.pid") == true) unlink("/tmp/check_frsip.pid");
					//寫入資料庫, server error
					$sql = "update vmrule set frsipstatus = 1  where 1";
					$result = mysqli_query($link, $sql);		
					return;
				}
			}
		}
		
		$header 					= array('X-frSIP-API-Token:'.$token);
		$url 						= $mainurl."get/systemstatus/serverstatus/2";
		$data_input02 				= array();
		$data_input02["serverId"]	= "2";
		wtask_log("Task_check_frsip", $remote_ip4filename, "api url :".$url);
		wtask_log("Task_check_frsip", $remote_ip4filename, "serverId :".$data_input02["serverId"]);
		$out = CallAPI4OptMeeting("GET", $url, $data_input02, $header);
		wtask_log("Task_check_frsip", $remote_ip4filename, "query serverstatus api result :".$out);
		echo "query serverstatus api result :".$out."\r\n";
		if (strlen($out) <= 0)
		{
			//寫入資料庫, server error
			if (file_exists("/tmp/check_frsip.pid") == true) unlink("/tmp/check_frsip.pid");
			$sql = "update vmrule set frsipstatus = 2  where 1";
			$result = mysqli_query($link, $sql);		
			return;
		}
		if (strpos($out, "\"disk\"") == false) return;
		
		$ret = json_decode($out, true);
		if (strlen($ret['disk']) > 0)
		{
			$diskusage = $ret['disk'];
			$diskusage = check_special_char($diskusage);

			//echo $diskusage;
			if(strstr($diskusage, "100%") && strstr($diskusage, "99%"))
			{
				//error 
				if (file_exists("/tmp/check_frsip.pid") == true) unlink("/tmp/check_frsip.pid");
				$sql = "update vmrule set frsipstatus = 3 where 1";
				$result = mysqli_query($link, $sql);			
				return;
			}
		}
		if ($ret['status'] == "Offline")
		{
			if (file_exists("/tmp/check_frsip.pid") == true) unlink("/tmp/check_frsip.pid");
			$sql = "update vmrule set frsipstatus = 5 where 1";
			$result = mysqli_query($link, $sql);			
			return;	
		}
		//////////////////
		$url = $mainurl."get/systemstatus/serverstatus/1";
		$data = array();
		$data["serverId"]="1";
		$out = CallAPI4OptMeeting("GET", $url, $data, $header);
		echo $out;
		if (strlen($out) <= 0)
		{
			//寫入資料庫, server error
			if (file_exists("/tmp/check_frsip.pid") == true) unlink("/tmp/check_frsip.pid");
			$sql = "update vmrule set frsipstatus = 4  where 1";
			$result = mysqli_query($link, $sql);		
			return;
		}

		$ret = json_decode($out, true);
		if (strlen($ret['disk']) > 0)
		{
			$diskusage = $ret['disk'];
			if(strstr($diskusage, "100%") && strstr($diskusage, "99%"))
			{
				//error 
				$sql = "update vmrule set frsipstatus = 5 where 1";
				$result = mysqli_query($link, $sql);			
				return;
			}
		}

		if ($ret['status'] == "Offline")
		{
			if (file_exists("/tmp/check_frsip.pid") == true) unlink("/tmp/check_frsip.pid");
			$sql = "update vmrule set frsipstatus = 6 where 1";
			$result = mysqli_query($link, $sql);			
			return;	
		}

		//alive
		$sql = "update vmrule set frsipstatus = 0  where 1";
		$result = mysqli_query($link, $sql);

		if (file_exists("/tmp/check_frsip.pid") == true) unlink("/tmp/check_frsip.pid");

		$data = array();
		// expire token 
		$url = $mainurl."delete/api/token/expire";
		$data["username"]="administrator";
		$out = CallAPI4OptMeeting("POST", $url, $data, $header);	
		
	}
	catch (Exception $e)
	{
		wtask_log_Exception("Task_check_frsip", $remote_ip4filename, "Exception error :".$e->getMessage());
		echo "error";
	}
	finally
	{
		wtask_log("Task_check_frsip", $remote_ip4filename, "finally procedure");
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
			wtask_log_Exception("Task_check_frsip", $remote_ip4filename, "Exception error: disconnect! error :".$e->getMessage());
		}
		wtask_log("Task_check_frsip", $remote_ip4filename, "finally complete"."\r\n".$g_exit_symbol."Task_check_frsip exit ->"."\r\n");
	}
?>
