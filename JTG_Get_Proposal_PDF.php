<?php
	include("func.php");
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$App_type 			= isset($_POST['App_type']) 			? $_POST['App_type'] 			: '';	
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_Insuance_no = isset($_POST['Remote_Insuance_no']) 	? $_POST['Remote_Insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$token 				= isset($_POST['accessToken']) 			? $_POST['accessToken'] 		: '';

	//$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	//$Sso_token = "u0K2w1L0roUR8p1k3UJgZtlRbR6DD9BZHyXkDNvCALSY4zea11svxYSjoYP4iURR";
	//$App_type = "0";//業務員
	//$Apply_no="7300000022SN001";
	if($App_type == '0')
		$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
	if($App_type == '1')
		$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫
	
	
	$PDF_time 			= isset($_POST['PDF_time']) 			? $_POST['PDF_time'] 		: '';
	$PDF_time 			= check_special_char($PDF_time);
	
	// 驗證 security token
	$headers = apache_request_headers();
	$token 	 = $headers['Authorization'];
	if(check_header($key, $token) == true)
	{
		wh_log($Insurance_no, $Remote_insurance_no, "security token succeed", $Person_id);
	}
	else
	{
		;//echo "error token";
		$data = array();
		$data["status"]			= "false";
		$data["code"]			= "0x0209";
		$data["responseMessage"]= "Invalid token!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(X) security token failure", $Person_id);
		exit;							
	}
	
	$status_code_succeed = "I1"; // 成功狀態代碼
	$status_code_failure = "I0"; // 失敗狀態代碼
	switch($PDF_time)
	{
		case 2:
			$status_code_succeed = "M1"; // 成功狀態代碼
			$status_code_failure = "M0"; // 失敗狀態代碼
			break;
		case 3: // 業務員-要保書
			$status_code_succeed = "S1"; // 成功狀態代碼
			$status_code_failure = "S0"; // 失敗狀態代碼
			break;
		case 4: // 業務員-業報書
			$status_code_succeed = "T1"; // 成功狀態代碼
			$status_code_failure = "T0"; // 失敗狀態代碼
			break;
		case 5: // 要保書-押上保單號碼
			$status_code_succeed = "W1"; // 成功狀態代碼
			$status_code_failure = "W0"; // 失敗狀態代碼
			break;
	}
	$status_code_succeed = ($PDF_time == 1) ? "I1" : "M1"; // 成功狀態代碼
	$status_code_failure = ($PDF_time == 1) ? "I0" : "M0"; // 失敗狀態代碼
	$status_code = "";
	wh_log($Insurance_no, $Remote_insurance_no, "get pdf entry <-", $Person_id);
	
	// 當資料不齊全時，從資料庫取得
	if (($Member_name 	== '') ||
		($Mobile_no 	== '') )
	{
		$memb 		 = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id);
		$Mobile_no 	 = $memb["Mobile_no"];
		$Member_name = $memb["Member_name"];
	}
	$Sales_Id = get_sales_id($Insurance_no, $Remote_insurance_no);
	
	if (($Person_id 		 != '') &&
		($Insurance_no 		 != '') &&
		($Remote_Insuance_no != ''))
	{
		$link = null;
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");
			
			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_Insuance_no  	= mysqli_real_escape_string($link, $Remote_Insuance_no	);
			$App_type  				= mysqli_real_escape_string($link, $App_type			);	
			$token  				= mysqli_real_escape_string($link, $token				);
			
			$Insurance_no2 			= trim(stripslashes($Insurance_no)		);
			$Remote_Insuance_no2 	= trim(stripslashes($Remote_Insuance_no));
			$App_type2 				= trim(stripslashes($App_type)			);
			$token2 				= trim(stripslashes($token)				);
							
			//$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			//if ($Person_id != "") {	
				////$sql = $sql." and person_id='".$Person_id."'";
			//}

			if (1) // if ($result = mysqli_query($link, $sql))
			{
				$data = array();
				if ($token2 != '')
				{
					//exit;
					//LDI-005
					//$url = $g_mpost_url. "ldi/proposal/pdf";
					//LDI-020
					$url 						= $g_PDF_apiurl;
					$data['Insurance_no']		= $Insurance_no2;
					$data['Remote_Insuance_no']	= $Remote_Insuance_no2;
					$data['appId']				= $appId ;
				
					//$jsondata = json_encode($data);
					//$out = CallAPI("POST", $url, $jsondata, $token, true);
					
					$out = CallAPI("GET", $url, $data, $token2, true);
					//echo "PDF:".$out;
					//$data = array();
					//$data["status"]="true";
					//$data["code"]="0x0200";						
					//$data["pdf"]=$out;
					//header('Content-Type: application/json');
					//echo (json_encode($data, JSON_UNESCAPED_UNICODE));
					echo $out;
					$status_code = $status_code_succeed;
					if ($status_code != "")
						$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, "", $status_code, $link);
					
					$data["status"]			= "true";
					$data["code"]			= "0x0200";
					$data["responseMessage"]= "";
					exit;
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0204";
					$data["responseMessage"]= "token fail";
					$status_code = $status_code_failure;
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";
				$status_code = $status_code_failure;				
			}
		}
		catch (Exception $e)
		{
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="系統異常";
			$status_code = $status_code_failure;					
        }
		finally
		{
			wh_log($Insurance_no, $Remote_insurance_no, "active finally function", $Person_id);
			try
			{
				if ($link != null)
				{
					if ($status_code != "")
						$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, "", $status_code, $link);
	
					mysqli_close($link);
					$link = null;
				}
			}
			catch(Exception $e)
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0202";
				$data["responseMessage"]= "Exception error: disconnect!";
			}
		}
	} else {
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."get pdf exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>