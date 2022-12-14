<?php
	include("func.php");
	
	// initial
	$status_code_succeed 	= ""; // 成功狀態代碼
	$status_code_failure 	= ""; // 失敗狀態代碼
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
	
	// 模擬資料
	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
	}

	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get signature exit ->"."\r\n", $Person_id);
		
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "get signature entry <-", $Person_id);
	
	// 驗證 security token
	$ret = protect_api("JTG_Get_Signature", "get signature exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
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
		$Mobile_no 				!= '' &&
		$Role 					!= '')
	{
		try
		{
			$data = create_connect($link, $Insurance_no, $Remote_insurance_no, $Person_id);
			if ($data["status"] == "false") return;

			$Insurance_no  		 = mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insurance_no = mysqli_real_escape_string($link, $Remote_insurance_no	);
			$Person_id  		 = mysqli_real_escape_string($link, $Person_id			);
			$Mobile_no  		 = mysqli_real_escape_string($link, $Mobile_no			);
			$Role  				 = mysqli_real_escape_string($link, $Role				);

			$Insuranceno 		= trim(stripslashes($Insurance_no)		);
			$Remoteinsuanceno 	= trim(stripslashes($Remote_insurance_no));
			$Personid 			= trim(stripslashes($Person_id)			);
			$Mobileno 			= trim(stripslashes($Mobile_no)			);
			$Role 				= trim(stripslashes($Role)				);
			
			$sql = "SELECT * FROM memberinfo where insurance_no='$Insuranceno' and Remote_insurance_no='$Remoteinsuanceno' and person_id='$Personid' and mobile_no='$Mobileno' and role='$Role' and member_trash=0 ";
			
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					try
					{
						while ($row = mysqli_fetch_array($result))
						{
							$array4json["member_name"] 	 = $row['member_name'];
							$array4json["signature_pic"] = $row['signature_pic'];
						}
						$data = result_message("true", "0x0200", "取得簽名資訊成功", $array4json);
					}
					catch (Exception $e)
					{
						$data = result_message("false", "0x0209", "取得簽名資訊 - Exception error", "");
						JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
					}
				}
				else
				{
					$data = result_message("false", "0x0204", "不存在簽名資訊!", "");
				}
			}
			else
			{
				$data = result_message("false", "0x0208", "SQL fail!", "");
			}
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
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get signature exit ->"."\r\n", $Person_id);
	$data["orderStatus"]	= $order_status;
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>