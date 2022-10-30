<?php
	include("func.php");
		
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
																											 //0:業務員  1:要保人 2:被保人 3: 法定代理人
	$Role 				= isset($_POST['Role']) 	 			? $_POST['Role'] 	   			: 'proposer';//proposer：要保人, insured：被保人, legalRepresentative：法定代理人, agentOne：業務員一
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';

	$Insurance_no 		= check_special_char($Insurance_no		);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Role 				= check_special_char($Role				);
	$Person_id 			= check_special_char($Person_id			);

	$MEETING_time 			= isset($_POST['MEETING_time']) 			? $_POST['MEETING_time'] 		: '';
	$MEETING_time 			= check_special_char($MEETING_time);
	/*
	proposer：要保人
	insured：被保人  
	legalRepresentative：法定代理人
	agentOne:業務
	*/
	
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
	
	$status_code_succeed = ($MEETING_time == 1) ? "K1" : "Q1"; // 成功狀態代碼
	$status_code_failure = ($MEETING_time == 1) ? "K0" : "Q0"; // 失敗狀態代碼
	$status_code = "";
	wh_log($Insurance_no, $Remote_insurance_no, "frsip info entry <-", $Person_id);
	
	// 當資料不齊全時，從資料庫取得
	if (($Member_name 	== '') ||
		($Mobile_no 	== '') ||
		($Role 			== ''))
	{
		$memb 		 = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id);
		$Mobile_no 	 = $memb["mobile_no"];
		$Member_name = $memb["member_name"];
		$Role 		 = $memb["role"];
	}
	$Sales_Id = get_sales_id($Insurance_no, $Remote_insurance_no);

	if (($Role 		!= '') &&
		($Person_id != ''))
	{
		//check 帳號/密碼
		//$host = 'localhost';
		//$user = 'tglmember_user';
		//$passwd = 'tglmember210718';
		//$database = 'tglmemberdb';
		//echo $sql;
		//exit;
		$link = null;
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  		= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insuance_no = mysqli_real_escape_string($link, $Remote_insuance_no	);
			$Role  				= mysqli_real_escape_string($link, $Role				);
			$Person_id  		= mysqli_real_escape_string($link, $Person_id			);

			$Insurance_no 		= trim(stripslashes($Insurance_no)		);
			$Remote_insuance_no = trim(stripslashes($Remote_insuance_no));
			$Role 				= trim(stripslashes($Role)				);
			$Personid 			= trim(stripslashes($Person_id)			);

			if($Role != "agentOne") // 業務員
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "無此權限";
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				wh_log($Insurance_no, $Remote_insurance_no, "(!) query result :".$data["responseMessage"]."\r\n"."frsip info exit ->", $Person_id);
				exit;
			}
			
			$sql = "SELECT * FROM memberinfo where role='agentOne' and member_trash=0 "; // get sales $sql = "SELECT * FROM salesinfo where sales_trash=0 ";
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insuance_no"	, $Remote_insuance_no	);
			$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Personid				);
			
			wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$frsipstatus = 0;							
					$sql = "select * from vmrule where 1";
					$result = mysqli_query($link, $sql);
					while($row = mysqli_fetch_array($result))
					{
						$frsipstatus = $row['frsipstatus'];
					}
					$data["status"]		= "true";
					$data["code"]		= "0x0200";
					$data["frsipstatus"]=  $frsipstatus;
					$status_code = $status_code_succeed;
				}
				else
				{
					$data["status"]			="false";
					$data["code"]			="0x0205";
					$data["responseMessage"]="無此業務員!";
					$status_code = $status_code_failure;
				}
			}
			else
			{
				//sql failed
				$data["status"]			= "false";
				$data["code"]			= "0x0202";
				$data["responseMessage"]= "SQL Failed!";
				$status_code = $status_code_failure;
			}
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0204";
			$data["responseMessage"]= "Exception error!";
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
	}
	else
	{
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."frsip info exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>