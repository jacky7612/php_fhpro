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
	$Sales_id 			= isset($_POST['Sales_id']) 			? $_POST['Sales_id'] 			: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Mobile_no 			= isset($_POST['Mobile_no']) 			? $_POST['Mobile_no'] 			: '';
	$Role 				= isset($_POST['Role']) 				? $_POST['Role'] 				: '';

	$Insurance_no 		= check_special_char($Insurance_no		);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Sales_id 			= check_special_char($Sales_id			);
	$Person_id 			= check_special_char($Person_id			);
	$Mobile_no 			= check_special_char($Mobile_no			);
	$Role 				= check_special_char($Role				);

	// 當資料不齊全時，從資料庫取得
	$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Role, $Sales_id, $Mobile_no, $Member_name);
	if (!$ret_code)
	{
		$data = result_message("false", "0x0206", "map person data failure", "");
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "get signature entry <-", $Person_id);
	
	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Get_Signature", "get signature exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	// start
	if ($Insurance_no 			!= '' &&
		$Remote_insuance_no 	!= '' &&
		$Person_id 				!= '' &&
		$Mobile_no 				!= '' &&
		$Role 					!= '')
	{

		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  		= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insuance_no = mysqli_real_escape_string($link, $Remote_insuance_no	);
			$Person_id  		= mysqli_real_escape_string($link, $Person_id			);
			$Mobile_no  		= mysqli_real_escape_string($link, $Mobile_no			);
			$Role  				= mysqli_real_escape_string($link, $Role				);

			$Insuranceno 		= trim(stripslashes($Insurance_no)		);
			$Remoteinsuanceno 	= trim(stripslashes($Remote_insuance_no));
			$Personid 			= trim(stripslashes($Person_id)			);
			$Mobileno 			= trim(stripslashes($Mobile_no)			);
			$Role 				= trim(stripslashes($Role)				);
			
			$sql = "SELECT * FROM memberinfo where insurance_no='$Insuranceno' and remote_insuance_no='$Remote_insuance_no' and person_id='$Personid' and mobile_no='$Mobileno' and role=$Role and order_trash=0 ";
			
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
						$data = result_message("true", "0x0200", "取得簽名資訊成功", json_encode($array4json));
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
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "finally procedure", $Person_id);
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
				$data = result_message("false", "0x0207", "Exception error: disconnect!", "");
				JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"]).$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
			}
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "finally complete - status:".$status_code, $Person_id);
		}
	}
	else
	{
		$data = result_message("false", "0x0202", "API parameter is required!", "");
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get signature exit ->"."\r\n", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>