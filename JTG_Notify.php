<?php
	include("func.php");
	
	global $g_notify_apiurl, $g_FCM_API_ACCESS_KEY;
	
	// initial
	$status_code_succeed 	= "K1"; // 成功狀態代碼
	$status_code_failure 	= "K0"; // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	$link					= null;
	$Insurance_no 			= ""; // *
	$Remote_insurance_no 	= ""; // *
	$Person_id 				= ""; // *
	$Mobile_no 				= "";
	$json_Person_id 		= "";
	$Sales_id 				= "";
	$status_code 			= "";
	$Member_name			= "";
	$Role 					= "";
	$FCM_title				= "";
	$FCM_content			= "";
	$order_status 			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$FCM_title 			= isset($_POST['FCM_title']) 			? $_POST['FCM_title'] 			: '';
	$FCM_content 		= isset($_POST['FCM_content']) 			? $_POST['FCM_content'] 		: '';

	$Insurance_no 		= check_special_char($Insurance_no		);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Person_id 			= check_special_char($Person_id			);
	$FCM_title 			= check_special_char($FCM_title			);
	$FCM_content 		= check_special_char($FCM_content		);
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
		$FCM_title 		 	 = "FCM_title";
		$FCM_content 		 = "FCM_content";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "notify entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Notify", "notify exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	// start
	try
	{
		$link = mysqli_connect($host, $user, $passwd, $database);
		mysqli_query($link,"SET NAMES 'utf8'");
		
		$Insurance_no  			= mysqli_real_escape_string($link,$Insurance_no			);
		$Remote_insuance_no  	= mysqli_real_escape_string($link,$Remote_insuance_no	);
		$Person_id  			= mysqli_real_escape_string($link,$Person_id			);
		$FCM_title  			= mysqli_real_escape_string($link,$FCM_title			);	
		$FCM_content  			= mysqli_real_escape_string($link,$FCM_content			);
	
		$Insurance_no 			= trim(stripslashes($Insurance_no));
		$Remote_insuance_no 	= trim(stripslashes($Remote_insuance_no));
		$Personid 				= trim(stripslashes($Person_id));
		$FCMtitle 				= trim(stripslashes($FCM_title));
		$FCMcontent 			= trim(stripslashes($FCM_content));
		$Insuranceno 			= trim(stripslashes($Insurance_no));
		
		$sql = "SELECT * FROM memberinfo where member_trash=0 ";
		$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
		$sql = $sql.merge_sql_string_if_not_empty("remote_insuance_no"	, $Remote_insuance_no	);
		
		// 當為業務員時，要推播給其他相關人員；否則通知業務員要上線進行保單相關流程
		switch ($Role)
		{
			case "proposer":
			case "insured":
			case "legalRepresentative":
				$sql = $sql.merge_sql_string_if_not_empty("person_id", $Sales_id);
				break;
				
			case "agentOne":
				// $sql = $sql.merge_sql_string_if_not_empty("person_id", $Personid);
				break;
		}
		
		if ($result = mysqli_query($link, $sql))
		{
			if (mysqli_num_rows($result) > 0)
			{
				while ($row = mysqli_fetch_array($result))
				{
					if ($row["person_id"] == $Personid) continue;
					$notificationToken = $row['notificationToken'];
					if ($notificationToken == null || strlen($notificationToken) <= 2)
					{
						$data = result_message("false", "0x0204", "notificationToken is NULL", "");
						JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])."send FCM message error :token invalid!", $Person_id);
					}
					
					$send_data = array(
						'to' 			=> $notificationToken, // 接收端 Token
						"notification" 	=> [
							"title"	 		=> $FCMtitle,
							"body" 			=> $FCMcontent,
							"icon" 			=> "ic_launcher",
							"sound" 		=> "default"
						],
					);
					
					//firebase認證 與 傳送格式
					$headers = array (
								'Authorization: key='.$g_FCM_API_ACCESS_KEY,
								'Content-Type: application/json',
								);
					JTG_wh_log($Insurance_no, $Remote_insurance_no, "send FCM message had ready", $Person_id);
					
					// 呼叫FCM推播 API /*curl至firebase server發送到接收端*/
					try
					{
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $g_notify_apiurl);
						curl_setopt($ch, CURLOPT_POST, true);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($send_data));
						$ret_fcm = curl_exec($ch); // ret_fcm 是firebase server的結果
						curl_close($ch); // 關閉CURL連線
					}
					catch (Exception $e)
					{
						JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$row["person_id"]." call FCM exeception error : ".$e->getMessage(), $Person_id);
					}
					JTG_wh_log($Insurance_no, $Remote_insurance_no, "FCM result :".$ret_fcm, $Person_id);
					
					// 紀錄發佈紀錄至notification log
					$msg = $FCMtitle."-".$FCMcontent;
					$sql = "INSERT INTO notificationlog (insurance_no, remote_Insurance_no, person_id, role, msg, fcmresult, updatetime) VALUES ('$Insurance_no', '$Remote_insuance_no', '$Personid', 'proposer', '$msg', '$ret_fcm', NOW())";
					mysqli_query($link, $sql);
				}
			}
			else
			{
				$data = result_message("false", "0x0204", "無此人員推播失敗", "");
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				return;				
			}
		}
		else
		{
			$data = result_message("false", "0x0208", "SQL fail!", "");
			header('Content-Type: application/json');
			echo (json_encode($data, JSON_UNESCAPED_UNICODE));
			return;
		}
		
		$data = result_message("true", "0x0200", "推播發送成功", "");
	}
	catch (Exception $e)
	{
		$data = result_message("false", "0x0209", "系統異常", "");
		JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
	}
	finally
	{
		JTG_wh_log($Insurance_no, $Remote_insurance_no, "finally procedure", $Person_id);
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
		catch (Exception $e)
		{
			$data = result_message("false", "0x0207", "Exception error: disconnect!", "");
			JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
		}
		JTG_wh_log($Insurance_no, $Remote_insurance_no, "finally complete - status:".$status_code, $Person_id);
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."notify exit ->"."\r\n", $Person_id);
	
	$data["order_status"] = $order_status;
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>