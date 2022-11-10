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
	$imageFileType 			= "jpg";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Signature_pic 		= isset($_POST['Signature_pic']) 		? $_POST['Signature_pic'] 		: '';

	$Insurance_no 		= check_special_char($Insurance_no);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Person_id 			= check_special_char($Person_id);
	
	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0203", "get data failure", "");
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	wh_log($Insurance_no, $Remote_insurance_no, "save signature entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Get_Signature", "save signature exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	// start
	if ($Insurance_no 			!= '' &&
		 $Remote_insuance_no 	!= '' &&
		 $Person_id 			!= '' &&
		 strlen($Person_id) > 1)
	{
		$image = addslashes(encrypt_string_if_not_empty($g_encrypt_image, $Signature_pic)); //SQL Injection defence!
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  		= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insuance_no = mysqli_real_escape_string($link, $Remote_insuance_no	);
			$Person_id  		= mysqli_real_escape_string($link, $Person_id			);
			
			$Insurance_no 		= trim(stripslashes($Insurance_no)		);
			$Remote_insuance_no = trim(stripslashes($Remote_insuance_no));
			$Personid 			= trim(stripslashes($Person_id)			);
			
			$sql = "SELECT * FROM memberinfo where member_trash=0 ";
			if ($Insurance_no != "") {	
				$sql = $sql." and insurance_no='".$Insurance_no."'";
			}
			if ($Remote_insuance_no != "") {	
				$sql = $sql." and remote_insuance_no='".$Remote_insuance_no."'";
			}
			if ($Personid != "") {	
				$sql = $sql." and person_id='".$Personid."'";
			}

			wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$mid = 0;
					while ($row = mysqli_fetch_array($result))
					{
						$mid = $row['mid'];
					}	
					$mid = (int)str_replace(",", "", $mid);
					try
					{
						$sql2 = "update `memberinfo` set `signature_pic`='{$image}', `updatedttime`=NOW() where mid=$mid;";
						mysqli_query($link,$sql2) or die(mysqli_error($link));
						//echo "user data change ok!";
						$data = result_message("true", "0x0200", "簽名檔上傳成功!", "");
					}
					catch (Exception $e)
					{
						$data = result_message("false", "0x0202", "Exception error!", "");
					}
				}
				else
				{
					$data = result_message("false", "0x0201", "無相同身份證資料,無法更新!".$Person_id, "");
				}
			}
			else
			{
				$data = result_message("false", "0x0204", "SQL fail!", "");
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0202", "Exception error!", "");
        }
		finally
		{
			wh_log($Insurance_no, $Remote_insurance_no, "finally procedure", $Person_id);
			try
			{
				if ($status_code != "")
					$data_status = modify_order_state($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $status_code, false);
				if (count($data_status) > 0 && $data_status["status"] == "false")
					$data = $data_status;
				
				if ($link != null)
				{
					mysqli_close($link);
					$link = null;
				}
			}
			catch(Exception $e)
			{
				$data = result_message("false", "0x0202", "Exception error: disconnect!", "");
			}
			wh_log($Insurance_no, $Remote_insurance_no, "finally complete - status:".$status_code, $Person_id);
		}
	}
	else
	{
		$data = result_message("false", "0x0203", "API parameter is required!", "");
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n".$g_exit_symbol."save signature exit ->"."\r\n", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>