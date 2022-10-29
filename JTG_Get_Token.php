<?php
	include("func.php");

	$user 	= isset($_POST['user']) ? $_POST['user'] : '';
	$pwd 	= isset($_POST['pwd'])  ? $_POST['pwd']  : '';
	$data	= array();
	$remote_ip4filename = get_remote_ip_underline();
	wh_log("GetToken", $remote_ip4filename, "Get Token Security entry <-");
	if (($user != '') &&
		($pwd  != ''))
	{
		try
		{
			if (base64_encode($user) == $vuser &&
				base64_encode($pwd)  == $vpwd)
			{
				$time 					 = date("Y-m-d H:i:s");
				$en 					 = encrypt($key, $time);
				$data["status"]			 = "true";
				$data["code"]			 = "0x0200";
				$data["responseMessage"] = "Succeed!";
				$data["token"]			 = $en;
				wh_log("GetToken", $remote_ip4filename, $data["responseMessage"]);	
			}
			else
			{
				$data["status"]			 = "false";
				$data["code"]			 = "0x0205";
				$data["responseMessage"] = "Invalid user!";
				wh_log("GetToken", $remote_ip4filename, "(X) ".$data["responseMessage"]);	
			}
		}
		catch (Exception $e)
		{
			$data["status"]			 = "false";
			$data["code"]			 = "0x0204";
			$data["responseMessage"] = "Exception error!";
			wh_log("GetToken", $remote_ip4filename, "(X) ".$data["responseMessage"]");	
        }
	}
	else
	{
		$data["status"]			 = "false";
		$data["code"]			 = "0x0203";
		$data["responseMessage"] = "API parameter is required!";
		wh_log("GetToken", $remote_ip4filename, "(X) ".$data["responseMessage"]);
	}
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	wh_log("GetToken", $remote_ip4filename, "Get Token Security exit ->");
?>