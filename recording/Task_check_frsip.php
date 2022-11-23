<?php
	header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

	include("../func.php");
	
	global $g_create_meeting_apiurl, $g_prod_meeting_apiurl;
	
	wtask_log("Task_check_meetingroom entry <-");
	set_time_limit(0);
	try
	{

		if(file_exists("/tmp/check_frsip.pid")==true)//還在跑
		{
			if(strtotime(date("Y-m-d H:i:s")) - filemtime("/tmp/check_frsip.pid")> (3*60*60))//超過3小時
			{
				// 可能不正常離開
			}
			else
			{
				$msg = strtotime(date("Y-m-d H:i:s"))." - ".filemtime("/tmp/check_frsip.pid");
				echo $msg."\r\n";
				wtask_log($msg."\r\n".$g_exit_symbol."Task_check_frsip exit ->"."\r\n");
				return;
			}
		}
		touch("/tmp/check_frsip.pid");

		$link = mysqli_connect($host, $user, $passwd, $database);
		mysqli_query($link,"SET NAMES 'utf8'");

		$mainurl = $g_create_meeting_apiurl
		$url = $mainurl."post/api/token/request";

		//1. GET Token
		$data 				= array();
		//$data["username"]	= "administrator";
		$data["username"]	= "administrator";
		$hash 				= md5("CheFR63r");
		//$hash 			= md5("sT7m");
		$data["data"]		= md5($hash."@deltapath");
		//echo md5($hash."@deltapath");
		$out = CallAPI4OptMeeting("POST", $url, $data);
		//echo $out;
		$ret = json_decode($out, true);
		if($ret['success'] == true)
			$token = $ret['token'];
		else
		{
			//第二次機會
			if(_ENV == "PROD")
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
				{
					echo "error";//error;
					unlink("/tmp/check_frsip.pid");
					//寫入資料庫, server error
					$sql = "update vmrule set frsipstatus = 1  where 1";
					$result = mysqli_query($link, $sql);		
					return;
				}
			}
		}

		$header = array('X-frSIP-API-Token:'.$token);

		$url 				= $mainurl."get/systemstatus/serverstatus/2";
		$data 				= array();
		$data["serverId"]	= "2";
		$out = CallAPI4OptMeeting("GET", $url, $data, $header);
		echo $out;
		if (strlen($out) <= 0)
		{
			//寫入資料庫, server error
			unlink("/tmp/check_frsip.pid");
			$sql = "update vmrule set frsipstatus = 2  where 1";
			$result = mysqli_query($link, $sql);		
			return;
		}

		$ret = json_decode($out, true);
		if (strlen($ret['disk']) > 0)
		{
			$diskusage = $ret['disk'];
			$diskusage = check_special_char($diskusage);

			//echo $diskusage;
			if(strstr($diskusage, "100%") && strstr($diskusage, "99%"))
			{
				//error 
				unlink("/tmp/check_frsip.pid");
				$sql = "update vmrule set frsipstatus = 3 where 1";
				$result = mysqli_query($link, $sql);			
				return;
			}
		}
		if ($ret['status'] == "Offline")
		{
			unlink("/tmp/check_frsip.pid");
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
			unlink("/tmp/check_frsip.pid");
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
			unlink("/tmp/check_frsip.pid");
			$sql = "update vmrule set frsipstatus = 6 where 1";
			$result = mysqli_query($link, $sql);			
			return;	
		}

		//alive
		$sql = "update vmrule set frsipstatus = 0  where 1";
		$result = mysqli_query($link, $sql);

		unlink("/tmp/check_frsip.pid");

		$data = array();
		// expire token 
		$url = $mainurl."delete/api/token/expire";
		$data["username"]="administrator";
		$out = CallAPI4OptMeeting("POST", $url, $data, $header);	
		
	}
	catch (Exception $e)
	{
		wtask_log_Exception("Exception error :".$e->getMessage());
		echo "error";
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
		wtask_log("finally complete"."\r\n".$g_exit_symbol."Task_check_frsip exit ->"."\r\n");
	}
?>
