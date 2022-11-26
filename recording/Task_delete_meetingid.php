<?php
	include("../func.php");
	
	global $g_create_meeting_apiurl, $g_prod_meeting_apiurl, $g_test_vmr_id, $g_test_mode, $g_exit_symbol;
	
	set_time_limit(0);
	$mainurl = $g_create_meeting_apiurl;
	try
	{
		$remote_ip4filename = get_remote_ip_underline();
		//1. GET Token
		$out = get_meeting_token("Task_vmrupdate", $g_create_meeting_apiurl, $remote_ip4filename, $g_meeting_uid, $g_meeting_pwd);
		if (strpos($out, "\"success\"") == false) return;
		$ret = json_decode($out, true);
		if($ret['success'] == true)
		{
			echo "get token succeed\r\n";
			$token = $ret['token'];
		}
		
		$header = array('X-frSIP-API-Token:'.$token);
		$Meeting_id = isset($_POST['Meeting_id']) ? $_POST['Meeting_id'] : '';
		$remote_ip4filename = get_remote_ip_underline();
		wtask_log("Task_delete_meetingid", $remote_ip4filename, "Task_delete_meetingid entry <-");
		$data		= array();
		$data['id']	= $Meeting_id;
		$url 		= $mainurl."delete/virtualmeeting/virtualmeeting/".$Meeting_id ;
		$out = CallAPI4OptMeeting("POST", $url, $data, $header);
		echo "delete accesscode :".$out."\r\n";
		echo "complete!\r\n";
	}
	catch(Exception $e)
	{
		echo "(x)Exception error!".$e->getMessage()."\r\n";
		wtask_log_Exception("Task_delete_meetingid", $remote_ip4filename, "Exception error :".$e->getMessage());
	}
	finally
	{
		wtask_log("Task_delete_meetingid", $remote_ip4filename, "finally procedure");
		try
		{
			//if ($link != null)
			//{
			//	mysqli_close($link);
			//	$link = null;
			//}
		}
		catch (Exception $e)
		{
			wtask_log_Exception("Task_delete_meetingid", $remote_ip4filename, "Exception error: disconnect! error :".$e->getMessage());
		}
		wtask_log("Task_delete_meetingid", $remote_ip4filename, "finally complete"."\r\n".$g_exit_symbol."Task_delete_meetingid exit ->"."\r\n");
	}
?>