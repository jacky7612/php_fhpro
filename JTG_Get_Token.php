<?php
	include("func.php");

	$data 					= array();
	$array4json				= array();
	
	$user 	= isset($_POST['user']) ? $_POST['user'] : '';
	$pwd 	= isset($_POST['pwd'])  ? $_POST['pwd']  : '';
	$data	= array();
	$remote_ip4filename = get_remote_ip_underline();
	JTG_wh_log("GetToken", $remote_ip4filename, "Get Token Security entry <-");
	if (($user != '') &&
		($pwd  != ''))
	{
		try
		{
			if (base64_encode($user) == $vuser &&
				base64_encode($pwd)  == $vpwd)
			{
				$array4json["token"]	 = $en;
				$time 					 = date("Y-m-d H:i:s");
				$en 					 = encrypt($key, $time);
				$data = result_message("true", "0x0200", "Succeed!", json_encode($array4json));
				JTG_wh_log("GetToken", $remote_ip4filename, $data["responseMessage"]);	
			}
			else
			{
				$data = result_message("false", "0x0206", "Invalid user!", "");
				JTG_wh_log("GetToken", $remote_ip4filename, get_error_symbol($data["code"]).$data["responseMessage"]);	
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "Exception error!", "");
			JTG_wh_log_Exception("GetToken", $remote_ip4filename, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage());	
        }
	}
	else
	{
		$data = result_message("false", "0x0202", "API parameter is required!", "");
		JTG_wh_log("GetToken", $remote_ip4filename, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]);
	}
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	JTG_wh_log("GetToken", $remote_ip4filename, "Get Token Security exit ->");
?>