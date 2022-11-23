<?php
	include("func.php");
	global $vuser, $vpwd;
	
	$data 		= array();
	$array4json	= array();
	$SSO_token  = "";
	
	$user 	= isset($_POST['user']) ? $_POST['user'] : '';
	$pwd 	= isset($_POST['pwd'])  ? $_POST['pwd']  : '';
	$Insurance_no 			= isset($_POST['Insurance_no'])  		? $_POST['Insurance_no']  		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no'])  ? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id'])  			? $_POST['Person_id']  			: '';
	
	// 模擬資料
	if ($g_test_mode)
	{
		$user = "fhuser1";
		$pwd  = "fh@2022";
	}
	$remote_ip4filename = get_remote_ip_underline();
	JTG_wh_log("GetToken", $remote_ip4filename, "Get Token Security entry <-");
	if ($user != '' &&
		$pwd  != '')
	{
		try
		{
			if (base64_encode($user) == $vuser &&
				base64_encode($pwd)  == $vpwd)
			{
				if ($Insurance_no 			!= '' &&
					$Remote_insurance_no 	!= '' &&
					$Person_id 				!= '')
				{
					$SSO_token = generate_SSO_token($Insurance_no, $Remote_insurance_no, $Person_id);
				}
				else
				{
					$time 					 = date("Y-m-d H:i:s");
					$SSO_token 				 = encrypt($key, $time);
				}
				$array4json["token"]	 = $SSO_token;
				$data = result_message("true", "0x0200", "Succeed!", $array4json);
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
	JTG_wh_log("GetToken", $remote_ip4filename, "Get Token Security exit ->");
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>