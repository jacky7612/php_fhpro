<?php
	include "../func.php";
	
	global $g_create_meeting_apiurl, $g_join_meeting_pincode;
	
	try
	{
		wtask_log("Task_vmrupdate entry <-");
		$link = mysqli_connect($host, $user, $passwd, $database);
		$data = result_connect_error ($link);
		if ($data["status"] == "false")
		{
			wtask_log("[Task_vmrupdate] ".get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."send otp exit ->"."\r\n");
			return;
		}
		mysqli_query($link,"SET NAMES 'utf8'");
		
		$mainurl = $g_create_meeting_apiurl;
		$url = $mainurl."post/api/token/request";

		//1. GET Token
		$data 				= array();
		//$data["username"]	="administrator";
		$data["username"]	="administrator";
		$hash 				= md5("CheFR63r");
		//$hash = md5("sT7m");
		$data["data"]		= md5($hash."@deltapath");
		//echo md5($hash."@deltapath");
		$out = CallAPI4OptMeeting("POST", $url, $data);
		//echo $out;
		$ret = json_decode($out, true);
		if ($ret['success'] == true)
			$token = $ret['token'];
		else
		{
			echo "error";//error;
			return;
		}

		$header = array('X-frSIP-API-Token:'.$token);
		/*
		$url = $mainurl."get/skypeforbusiness/skypeforbusinessgatewayvmr/view/list";
		$data["gateway"]="12"; //UAT
		$data["service_type"]="conference";
		$data["start"]="0";
		$data["limit"]="500";

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
		wtask_log("finally complete"."\r\n".$g_exit_symbol."Task_vmrupdate exit ->"."\r\n");
	}
?>
