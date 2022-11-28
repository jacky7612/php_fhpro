<?php
	include "../func.php";
	
	global $g_create_meeting_apiurl, $g_join_meeting_pincode;
	
	$link 		= null;
	$data_conn 	= array();
	try
	{
		$remote_ip4filename = get_remote_ip_underline();
		wtask_log("Task_vmrupdate", $remote_ip4filename, "Task_vmrupdate entry <-");
		
		// connect mysql
		$data_conn = task_create_connect($link, "Task_vmrupdate", $remote_ip4filename);
		if ($data_conn["status"] == "false") return;
		
		$mainurl = $g_create_meeting_apiurl;
		$url = $mainurl."post/api/token/request";
		
		//1. GET Token
		$out = get_meeting_token("Task_vmrupdate", $g_create_meeting_apiurl, $remote_ip4filename, $g_meeting_uid, $g_meeting_pwd);
		if (strpos($out, "\"success\"") == false) return;
		
		//echo $out;
		$ret = json_decode($out, true);
		if($ret['success'] == true)
		{
			echo "get token succeed\r\n";
			$token = $ret['token'];
		}
		else
		{
			echo "error";//error;
			return;
		}

		$header = array('X-frSIP-API-Token:'.$token);
		/*
		$url = $mainurl."get/skypeforbusiness/skypeforbusinessgatewayvmr/view/list";
		$data["gateway"]		= _MEETING_GATEWAY; //UAT
		$data["service_type"]	= "conference";
		$data["start"]			= "0";
		$data["limit"]			= "500";

		$out = CallAPI4OptMeeting("GET", $url, $data, $header);
		echo $out;
		return;
		*/
		//先同步vmr info
		//echo  "test";
		$pin = $g_join_meeting_pincode;
		$sql = "select * from vmrule where id=1";
		$result = mysqli_query($link, $sql);
		
		while ($row = mysqli_fetch_array($result))
			$pin = $row['pincode'];
	
		//1. 準備每間會議室房間, 各產生五組accesscode
		$sql = "select * from vmrinfo where 1";
		$result = mysqli_query($link, $sql);
		if (mysqli_num_rows($result) > 0)
		{
			while ($row = mysqli_fetch_array($result))
			{
				$vmr = $row['vmr'];
				$vid = $row['vid'];
				
				$vmrarray = explode("|", $vmr);
				$vmrid = $vmrarray[1];			
				
				$url = $mainurl."put/skypeforbusiness/skypeforbusinessgatewayvmr/".$vmrid;
				$data 						= array();
				$data["id"]					= $vmrid;
				$data["gateway"]			= $vmrarray[0];
				$data["service_type"]		= "conference";
				$data["conference_owner"]	= "1000";
				$data["name"]				= $vid;
				$data["record_conference"]	= "on";
				$data["room_security"]		= "2";//HOST/GUEST, 0:no limit
				$data["pin"]				= $pin;
				$data["allow_guests"]		= "off";//depend on room_security
				$data["participant_limit"]	= "5";
				$data["host_view"]			= "one_main_seven_pips";
				var_dump($data);
				$out = CallAPI4OptMeeting("POST", $url, $data, $header);
				echo $out;			
				
				//	break;
				//break;//for test only, need to remove
			}
		}

		//expired token
		$url 				= $mainurl."delete/api/token/expire";
		$data["username"]	= "administrator";
		$out = CallAPI4OptMeeting("POST", $url, $data, $header);
	}
	catch (Exception $e)
	{
		wtask_log_Exception("Task_vmrupdate", $remote_ip4filename, "Exception error :".$e->getMessage());
		echo "error";
	}
	finally
	{
		wtask_log("Task_vmrupdate", $remote_ip4filename, "finally procedure");
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
			wtask_log_Exception("Task_vmrupdate", $remote_ip4filename, "Exception error: disconnect! error :".$e->getMessage());
		}
		wtask_log("Task_vmrupdate", $remote_ip4filename, "finally complete"."\r\n".$g_exit_symbol."Task_vmrupdate exit ->"."\r\n");
	}
?>
