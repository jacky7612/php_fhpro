<?php
	include "db_tools.php";
	include("security_tools.php");

	$user 	= isset($_POST['user']) ? $_POST['user'] : '';
	$pwd 	= isset($_POST['pwd'])  ? $_POST['pwd']  : '';
	$data	= array();
	
	if (($user != '') && ($pwd != '')) {
		try {
			if(base64_encode($user) == $vuser && base64_encode($pwd) == $vpwd)
			{
				$time = date("Y-m-d H:i:s");
				$en = encrypt($key, $time);
				$data["status"]="true";
				$data["code"]="0x0200";
				$data["token"]=$en;
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));							
			}
			else
			{
				$data["status"]="false";
				$data["code"]="0x0205";
				$data["responseMessage"]="Invalid user!";
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				exit;
			}
		} catch (Exception $e) {
			$data["status"]="false";
			$data["code"]="0x0204";
			$data["responseMessage"]="Exception error!";				
        }
	} else {
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
		exit;
	}
?>