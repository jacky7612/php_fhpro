<?php
	include("func.php");
	
	// initial
	$status_code_succeed 	= ""; // 成功狀態代碼
	$status_code_failure 	= ""; // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	$array4json				= array();
	$link					= null;
	$Insurance_no 			= ""; // *
	$Remote_insurance_no 	= ""; // *
	$Person_id 				= ""; // *
	$Mobile_no 				= "";
	$json_Person_id 		= "";
	$Sales_id 				= "";
	$status_code 			= "";
	$Member_name			= "";
	$base64image			= "";
	$Role 					= "";
	$jsonlog_info			= "";
	$order_status			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_Insuance_no = isset($_POST['Remote_Insuance_no']) 	? $_POST['Remote_Insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$contain_json		= isset($_POST['contain_json']) 		? $_POST['contain_json'] 		: 'false';

	
	//if($App_type == '0')
	//	$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
	//if($App_type == '1')
	//	$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫
	
	$PDF_time 			= isset($_POST['PDF_code']) 			? $_POST['PDF_code'] 			: '';
	$PDF_time 			= check_special_char($PDF_time);
		
	switch ($PDF_time)
	{
		case "1": // 客戶-要保書
			$status_code_succeed = "I1"; // 成功狀態代碼
			$status_code_failure = "I0"; // 失敗狀態代碼
		case "2": // 客戶-要保書for簽名
			$status_code_succeed = "M1"; // 成功狀態代碼
			$status_code_failure = "M0"; // 失敗狀態代碼
			$PDF_time = "1";
			break;
		case "3": // 業務員-要保書for簽名
			$status_code_succeed = "S1"; // 成功狀態代碼
			$status_code_failure = "S0"; // 失敗狀態代碼
			break;
		case "4": // 業務員-業報書for簽名
			$status_code_succeed = "T1"; // 成功狀態代碼
			$status_code_failure = "T0"; // 失敗狀態代碼
			break;
		case "5": // 要保書-押上保單號碼
			$status_code_succeed = "W1"; // 成功狀態代碼
			$status_code_failure = "W0"; // 失敗狀態代碼
			break;
	}
	
	// 模擬資料
	if ($g_test_mode)
	{
		$PDF_time 			 = "1";
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
		//$contain_json 		 = "true";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get pdf exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "get pdf entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['accessToken']) ? $_POST['accessToken'] : '';
	$ret = protect_api("JTG_Get_Proposal_PDF", "get pdf exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	// 模擬資料
	if ($g_test_mode)
	{
		$token = "any";
	}
	$contain_json = strtolower($contain_json);
	
	// start
	if ($PDF_time 			 != '' &&
		$Person_id 		 	 != '' &&
		$Insurance_no 		 != '' &&
		$Remote_insurance_no != '')
	{
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			$data = result_connect_error ($link);
			if ($data["status"] == "false")
			{
				JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get pdf exit ->"."\r\n", $Person_id);
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				return;
			}
			mysqli_query($link,"SET NAMES 'utf8'");
			
			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no	);
			$token  				= mysqli_real_escape_string($link, $token				);
			
			$Insurance_no2 			= trim(stripslashes($Insurance_no)		 );
			$Remote_Insuance_no2 	= trim(stripslashes($Remote_insurance_no));
			$token2 				= trim(stripslashes($token)				 );
			
			if ($token2 != '')
			{
				// 從pdflog table取得pdf資料
				$ret_pdflog = get_pdflog_table_info($link, $Insurance_no, $Remote_insurance_no, $Person_id, "insurance_".$PDF_time, false);
				
				if ($contain_json == "true")
				{
					// 從jsonlog table取得json資料
					$json_data = "";
					$data = get_jsondata_from_jsonlog_table($link, $Insurance_no, $Remote_insurance_no, $Person_id, $json_data, false);
					
					if ($data["status"] == "true")
					{
						/* */
						if ($ret_pdflog["status"] == "true")
						{
							//echo $ret_pdflog["json"]."\r\n\r\n";
							$jsonlog_info = json_decode($json_data);
							$pdflog_info  = json_decode($ret_pdflog["json"]);
							for ($i = 0; $i < count($jsonlog_info->applicationData); $i++)
							{
								//echo count($pdflog_info)."\r\n\r\n";
								for ($j = 0; $j < count($pdflog_info); $j++)
								{
									//echo "152 ".$pdflog_info[$j]->pdf_data."\r\n\r\n";
									$pdflog_content = $pdflog_info[$j]->pdf_data;
									$jsonlog_info->applicationData[$i]->attacheContent = $pdflog_content;
								}
							}
							$status_code = $status_code_succeed;
						}
						else
						{
							$data = $ret_pdflog;
							$status_code = $status_code_failure;
						}
						/* */
					}
				}
				else
				{
					if ($ret_pdflog["status"] == "true")
					{
						$pdflog_info  = json_decode($ret_pdflog["json"]);
						for ($j = 0; $j < count($pdflog_info); $j++)
						{
							$pdflog_content = $pdflog_info[$j]->pdf_data;
							$jsonlog_info = $pdflog_content;
						}
						$status_code = $status_code_succeed;
					}
					else
					{
						$data = $ret_pdflog;
						$status_code = $status_code_failure;
					}
				}
				if ($status_code == $status_code_succeed)
				{
					$data = result_message("true", "0x0200", "取得pdf文件成功", $jsonlog_info);
				}
			}
			else
			{
				$data = result_message("false", "0x0206", "token fail", "");
				$status_code = $status_code_failure;
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "系統異常", "");
			$status_code = $status_code_failure;
			JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
        }
		finally
		{
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "active finally function", $Person_id);
			try
			{
				if ($status_code != "")
				{
					$data_status = modify_order_state($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $status_code, false);
					if (count($data_status) > 0 && $data_status["status"] == "false")
						$data = $data_status;
				}
				$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
				
				if ($link != null)
				{
					mysqli_close($link);
					$link = null;
				}
			}
			catch(Exception $e)
			{
				$data = result_message("false", "0x0207", "Exception error: disconnect!", "");
				JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
			}
		}
	}
	else
	{
		$data = result_message("false", "0x0202", "API parameter is required!", "");
		$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, true);
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get pdf exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>