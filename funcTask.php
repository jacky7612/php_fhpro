<?php
	// 取得視訊會議token public
	function get_meeting_token($funcName, $api_url, $remote_ip4filename, $uid, $pwd)
	{
		$out = "";
		$url = $api_url."post/api/token/request";
		wtask_log($funcName, $remote_ip4filename, "1. GET Token");
		wtask_log($funcName, $remote_ip4filename, $url);
		$data_input				= array();
		$data_input["username"]	= $uid;
		$hash 					= md5($pwd);
		$data_input["data"]		= md5($hash."@deltapath");
		//echo md5($hash."@deltapath");
		wtask_log($funcName, $remote_ip4filename, "username :".$data_input["username"]."; hash :".$hash."; data :".$data_input["data"]);
		$out = CallAPI4OptMeeting("POST", $url, $data_input);
		wtask_log($funcName, $remote_ip4filename, $out);
		return $out;
	}
?>