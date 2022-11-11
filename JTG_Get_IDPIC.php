<?php
	include("func.php");
	
	// initial
	$status_code_succeed 	= "D2"; // 成功狀態代碼
	$status_code_failure 	= "";   // 失敗狀態代碼
	$data 					= array();
	$data_status			= array();
	$array4json				= array();
	$fields2   				= array();
	$userList  				= array();
	$numbering 				= "";
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
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	
	$Insurance_no 			= check_special_char($Insurance_no		 );
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id			 );
	
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
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	JTG_wh_log($Insurance_no, $Remote_insurance_no, "get idpic entry <-", $Person_id);

	// 驗證 security token
	$token = isset($_POST['Authorization']) ? $_POST['Authorization'] : '';
	$ret = protect_api("JTG_Get_IDPIC", "get idpic exit ->"."\r\n", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	if ($Insurance_no 			!= '' &&
		$Remote_insurance_no 	!= '' &&
		$Person_id 				!= '')
	{
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no	);
			
			$Insuranceno 			= trim(stripslashes($Insurance_no));
			$Remote_insurance_no 	= trim(stripslashes($Remote_insurance_no));

			$sql = "SELECT a.*,b.member_name FROM orderinfo a ";
			$sql = $sql." inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql = $sql." where  a.order_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("a.insurance_no"			, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("a.remote_insurance_no"	, $Remote_insurance_no	);
			$sql = $sql.merge_sql_string_if_not_empty("a.person_id"				, $Person_id			);
			
			JTG_wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$row = mysqli_fetch_assoc($result);
					
					$insuredDate = date('Ymd', strtotime($row['inputdttime']));
					$userList = getuserList($link, $Insuranceno, $Remote_insurance_no, $Person_id, false);
					$fields2 = ["status" => "true", "code" => "0x0200", "msg" => "查詢成功", "insuredDate"  => $insuredDate, "userList" => $userList ];
	
					$array4json	= $fields2;
					$data = result_message("true", "0x0200", "查詢成功", $array4json);
					$status_code = $status_code_succeed;
				}
				else
				{
					$array4json["insuredDate"]	= date('Ymd');
					$data = result_message("false", "0x0204", "查無資料!", json_encode($array4json));
					$status_code = $status_code_failure;
				}
			}
			else
			{
				$array4json["insuredDate"]	= date('Ymd');
				$data = result_message("false", "0x0208", "SQL fail!", json_encode($array4json));
				$status_code = $status_code_failure;
			}
		}
		catch (Exception $e)
		{
			$array4json["insuredDate"]	= date('Ymd');
			$data = result_message("false", "0x0209", "Exception error!", json_encode($array4json));
			$status_code = $status_code_failure;
			JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
		}
		finally
		{
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
				JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, "(X) ".$data["code"]." ".$data["responseMessage"]." error :".$e->getMessage(), $Person_id);
			}
		}
	}
	else
	{
		$array4json["insuredDate"]	= date('Ymd');
		$data = result_message("false", "0x0202", "API parameter is required!", json_encode($array4json));
	}
	JTG_wh_log($Insurance_no, $Remote_insurance_no, get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."get idpic exit ->"."\r\n", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>