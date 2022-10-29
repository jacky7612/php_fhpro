<?php
	include("func.php");

	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 	? $_POST['Person_id'] : '';
	
	$Insurance_no 			= check_special_char($Insurance_no		 );
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id			 );

	$status_code_succeed = "D2"; // 成功狀態代碼
	$status_code_failure = "";   // 失敗狀態代碼
	
	wh_log($Insurance_no, $Remote_insurance_no, "get idpic entry <-", $Person_id);
	
	// 驗證 security token
	$headers = apache_request_headers();
	$token 	 = $headers['Authorization'];
	if (check_header($key, $token) == true)
	{
		wh_log($Insurance_no, $Remote_insurance_no, "security token succeed", $Person_id);
	}
	else
	{
		//echo "error token";
		$data 					= array();
		$data["status"]			= "false";
		$data["code"]			= "0x0209";
		$data["responseMessage"]= "Invalid token!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(X) security token failure", $Person_id);	
		exit;							
	}
	
	// 當資料不齊全時，從資料庫取得
	if (($Member_name 	== '') ||
		($Mobile_no 	== '') )
	{
		$memb 		 = get_member_info($Insurance_no, $Remote_insurance_no, $Person_id);
		$Mobile_no 	 = $memb["Mobile_no"];
		$Member_name = $memb["Member_name"];
	}
	$Sales_Id = get_sales_id($Insurance_no, $Remote_insurance_no);
	
	if (($Insurance_no 			!= '') &&
		($Remote_insurance_no 	!= ''))
	{
		$link = null;
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
			//echo $sql;
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insurance_no	);

			wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					//$mid=0;
					//$order_status="";
					//$policyList = array();
					//$userposlist = array();
					//$videoList = array();
					$fields2   = array();
					$userList  = array();
					$numbering = "";
					$row = mysqli_fetch_assoc($result);
	
					//$fields2=getStatus($link,$Insurance_no);
					
					$insuredDate = date('Ymd', strtotime($row['inputdttime']));  //"20210720";
					
			
					$userList = getuserList($link,$Insuranceno,$key);
					//$userList = [ ["userId" => "A123456789","userType" => "要保人","userPhoto" => "fajsdihjproi;rjgkljdsiofjsadljasie;fijwflkjdfoia==","identifyResultStatus" => "通過", "identifyFinishDate" => "20210721121527333" ],["userId" => "A123456789","userType" => "被保人","userPhoto" => "fajsdihjproi;rjgkljdsiofjsadljasie;fijwflkjdfoia==","identifyResultStatus" => "通過", "identifyFinishDate" => "20210721121527333"] ];
					
				
					//$videoList = getvideolist($link,$Insuranceno,$sip);

					$fields2 = ["status" => "true", "code" => "0x0200", "msg" => "查詢成功", "insuredDate"  => $insuredDate, "userList" => $userList ];
	
					$data 		 = $fields2;
					$status_code = $status_code_succeed;
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "不存在此要保流水序號的資料!";
					$data["insuredDate"]	= date('Ymd');
					$status_code 			= $status_code_failure;
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0202";
				$data["responseMessage"]= "SQL fail!";
				$data["insuredDate"]	= date('Ymd');
				$status_code 			= $status_code_failure;
			}
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";
			$data["insuredDate"]	= date('Ymd');
			$status_code 			= $status_code_failure;
		}
		finally
		{
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
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0201";
		$data["responseMessage"]= "API parameter is required!";
		$data["insuredDate"]	= date('Ymd');
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."get idpic exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>