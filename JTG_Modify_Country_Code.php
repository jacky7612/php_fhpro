<?php
	include("func.php");
	
	// initial
	$status_code_succeed = "B1"; // 成功狀態代碼
	$status_code_failure = "B0"; // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	// $link					= null;
	$Insurance_no 			= ""; // *
	$Remote_insurance_no 	= ""; // *
	$Person_id 				= ""; // *
	$Mobile_no 				= ""; // *
	$Sales_id 				= ""; // *
	$status_code 			= ""; // *
	$json_Person_id 		= "";
	$Role 					= "";
	$order_status			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	api_get_post_param($token, $Insurance_no, $Remote_insurance_no, $Person_id);
	$Country_code = isset($_POST['Country_code']) ? $_POST['Country_code'] : '';
	$Country_code = check_special_char($Country_code);

	// 模擬資料
	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
		$Mobile_no 			 = "0912-345-777";
		$Country_code 		 = "tw";
	}
	
	// 當資料不齊全時，從資料庫取得
	$json_Person_id = $Person_id;
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $json_Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."Country Code exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "Country Code entry <-", $Person_id);
	
	// 驗證 security token
	$ret = protect_api("JTG_Modify_Country_Code", "Country Code exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	// start
	if (($Person_id 			!= '') &&
		($Insurance_no 			!= '') &&
		($Remote_insurance_no 	!= '') &&
		($Country_code 			!= ''))
	{
		try
		{
			$data = create_connect($link, $Insurance_no, $Remote_insurance_no, $Person_id);
			if ($data["status"] == "false") return;
			
			// 初始化的SSO token(僅綁定行投、遠投號碼)
			$SSO_token = generate_SSO_token($Insurance_no, $Remote_insurance_no, "");
				
			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no);
			$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no);
			$Person_id  			= mysqli_real_escape_string($link, $Person_id);
			$Country_code  			= mysqli_real_escape_string($link, $Country_code);
			
			$Insurance_no 			= trim(stripslashes($Insurance_no));
			$Remote_insurance_no 	= trim(stripslashes($Remote_insurance_no));
			$Person_id 				= trim(stripslashes($Person_id));
			$Country_code 			= trim(stripslashes($Country_code));
			$sql = "SELECT * from countrylog where person_id='$Person_id' and insurance_no= '$Insurance_no' and remote_insurance_no= '$Remote_insurance_no' ";
			$result = mysqli_query($link, $sql);
			if (mysqli_num_rows($result) > 0)
			{
				$sql = "UPDATE countrylog SET countrycode='$Country_code' WHERE person_id='$Person_id' and insurance_no= '$Insurance_no' and remote_insurance_no= '$Remote_insurance_no' ";
			}
			else
			{
				$sql = "INSERT INTO countrylog (person_id, insurance_no, remote_insurance_no, countrycode, updatetime ) VALUES ('$Person_id', '$Insurance_no', '$Remote_insurance_no', '$Country_code', NOW() )  ";
			}
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "modify countrylog table prepare", $Person_id);

			if ($result = mysqli_query($link, $sql))
			{
				$data = result_message("true", "0x0200", "更新成功!", "");
				$status_code = $status_code_succeed;
			}
			else
			{
				$data = result_message("false", "0x0208", "SQL fail!", "");
				$status_code = $status_code_failure;
			}
			$symbol4log = ($status_code == $status_code_failure) ? get_error_symbol($data["code"]): "";
			$sql = ($status_code == $status_code_failure) ? " :".$sql : "";
			JTG_wh_log($Insurance_no, $Remote_insurance_no, $symbol4log."modify countrylog table result :".$data["responseMessage"].$sql, $Person_id);
			if ($status_code != "")
				$data_Status = modify_order_state($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $status_code, false);
			
			if ($data["status"] 	   == "true" &&
				count($data_status) 	> 0 	 &&
				$data_Status["status"] == "false")
			{
				$data = $data_Status;
			}
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "modify countrylog sop finish :".$data["responseMessage"], $Person_id);
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "Exception error!", "");
			JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
		}
		finally
		{
			$data_close_conn = close_connection_finally($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $status_code);
			if ($data_close_conn["status"] == "false") $data = $data_close_conn;
		}	
	}
	else
	{
		$data = result_message("false", "0x0202", "API parameter is required!", "");
		$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, true);
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."Country Code exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>