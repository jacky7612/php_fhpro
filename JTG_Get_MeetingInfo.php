<?php
	include("func.php");
	
	// initial
	$status_code_succeed 	= "K3"; // 成功狀態代碼
	$status_code_failure 	= "K2"; // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	$array4json				= array();
	//$link					= null;
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
	$imageFileType 			= "jpg";
	$order_status			= "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	api_get_post_param($token, $Insurance_no, $Remote_insurance_no, $Person_id);
	$MEETING_time 			= isset($_POST['MEETING_time']) 			? $_POST['MEETING_time'] 		: '';
	$MEETING_time 			= check_special_char($MEETING_time);
	
	$status_code_succeed = ($MEETING_time == 1) ? "K3" : "Q3"; // 成功狀態代碼
	$status_code_failure = ($MEETING_time == 1) ? "K2" : "Q3"; // 失敗狀態代碼
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "E123456789";
	}
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get meeting info exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "meeting info entry <-", $Person_id);
	
	// 驗證 security token
	$ret = protect_api("JTG_Get_MeetingInfo", "get meeting info exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	// start
	if ($Insurance_no 			!= '' &&
		$Remote_insurance_no 	!= '' &&
		$Person_id 				!= '' &&
		$Role 					!= '')
	{
		try
		{
			$data = create_connect($link, $Insurance_no, $Remote_insurance_no, $Person_id);
			if ($data["status"] == "false") return;

			$Role  		= mysqli_real_escape_string($link,$Role);
			$Person_id  = mysqli_real_escape_string($link,$Person_id);

			$Role 		= trim(stripslashes($Role));
			$Personid 	= trim(stripslashes($Person_id));

			if($Role != "agentOne") // 業務員
			{
				$data = result_message("false", "0x0206", "無此權限", "");
				$get_data = get_order_state($link, $order_status, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, false);
				$data["orderStatus"] = $order_status;
				
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				JTG_wh_log($Insurance_no, $Remote_insurance_no, "(!) query result :".$data["responseMessage"]."\r\n"."meeting info exit ->", $Person_id);
				return;
			}
			
			$sql = "SELECT * FROM memberinfo where role='agentOne' and member_trash=0 "; // get sales $sql = "SELECT * FROM salesinfo where sales_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insurance_no	);
			$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Personid				);
			
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$max = 0;
					while ($row = mysqli_fetch_array($result))
					{
						$sql = "select SUM(count) as max from gomeeting where 1";
						$result = mysqli_query($link, $sql);
						while($row = mysqli_fetch_array($result))
						{
							$max = $row['max'];
						}
						$max = (int)str_replace(",", "", $max);
					}
					$array4json["count"] = $max;
					$data = result_message("true", "0x0200", "取得會議室資訊成功", json_encode($array4json));
					$status_code = $status_code_succeed;
				}
				else
				{
					//沒有權限
					$data = result_message("false", "0x0206", "沒有呼叫此API的權限", "");
					$status_code = $status_code_failure;
				}
			}
			else
			{
				//sql failed
				$data = result_message("false", "0x0208", "SQL Failed!", "");
				$status_code = $status_code_failure;
			}			
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0209", "Exception error!", "");
			$status_code = $status_code_failure;
			JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
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
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get meeting info exit ->"."\r\n", $Person_id);
	$data["orderStatus"] = $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>