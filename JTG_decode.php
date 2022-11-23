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
	$SSO_token_obj = generate_SSO_token($Insurance_no, $Remote_insurance_no, $Person_id);
	echo "encode :".$SSO_token_obj."<br>";
	$SSO = json_decode($SSO_token_obj);
	echo $SSO->sso_token."<br>";
	
	if (protect_api_dog($SSO->sso_token, $get_Insurance_no, $get_Remote_insurance_no, $get_Person_id))
	{
		echo $get_Insurance_no."<br>";
		echo $get_Remote_insurance_no."<br>";
		echo $get_Person_id."<br>";
	}
	return;
	
	$SSO_info["insurance_no"] 			= $Insurance_no;
	$SSO_info["remote_insurance_no"] 	= $Remote_insurance_no;
	$SSO_info["person_id"] 				= $Person_id;
	$SSO_info["expire"] 				= date("Y-m-d H:i:s");
	$SSO_json 							= json_encode($SSO_info);
	$SSO_token 							= encrypt($key, $SSO_json);
	echo $SSO_token."<br><br>";
	//return;
	if (protect_api_dog($SSO_token, $get_Insurance_no, $get_Remote_insurance_no, $get_Person_id))
	{
		echo $get_Insurance_no."<br>";
		echo $get_Remote_insurance_no."<br>";
		echo $get_Person_id."<br>";
	}
	echo $get_Insurance_no."<br>";
	echo $get_Remote_insurance_no."<br>";
	echo $get_Person_id."<br>";
	//echo $SSO_token."<br><br>";
	//echo "user :".base64_encode("fhuser")."<br>";
	//echo "pwd :".base64_encode("fh@2022")."<br>";
	//
	//$dec_SSO_token = decrypt($key, $SSO_token);
	//echo $dec_SSO_token."<br><br>";
?>