<?php
	include("func.php");

	echo $key."<br>";
	echo $vuser."<br>";
	echo base64_decode($vuser)."<br>";
	echo base64_decode($vpwd)."<br>";
	
	if ($g_test_mode)
	{
		$PDF_time 			 = "1";
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
		//$contain_json 		 = "true";
	}
	$SSO_info["insurance_no"] = $Insurance_no;
	$SSO_info["remote_insurance_no"] = $Remote_insurance_no;
	$SSO_info["person_id"] = $Person_id;
	$SSO_info["time"] = date("Y-m-d H:i:s");
	$SSO_json = json_encode($SSO_info);
	$SSO_token = encrypt($key, $SSO_json);
	echo $SSO_token."<br><br>";
	
	$dec_SSO_token = decrypt($key, $SSO_token);
	echo $dec_SSO_token."<br><br>";
?>