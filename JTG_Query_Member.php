<?php
	include("func.php");
	
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	
	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id);

	wh_log($Insurance_no, $Remote_insurance_no, "query member entry <-", $Person_id);
	
	// 驗證 security token
	$headers =  apache_request_headers();
	$token = $headers['Authorization'];
	if(check_header($key, $token)==true)
	{
		wh_log($Insurance_no, $Remote_insurance_no, "security token succeed", $Person_id);
	}
	else
	{
		;//echo "error token";
		$data = array();
		$data["status"]="false";
		$data["code"]="0x0209";
		$data["responseMessage"]="Invalid token!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(X) security token failure"."\r\n"."query member exit ->", $Person_id);	
		exit;							
	}
	
	/*
	proposer：要保人
	insured：被保人  
	legalRepresentative：法定代理人
	agentOne:業務
	*/
	if ($Person_id != '')
	{
		$status_code = "";
		$Role = "";
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Person_id  = mysqli_real_escape_string($link,$Person_id);
			$Person_id = trim(stripslashes($Person_id));

			$sql = "SELECT * FROM memberinfo where member_trash=0 and insurance_no= '".$Insurance_no."' and remote_insurance_no= '".$Remote_insurance_no."'";
			$sql = $sql.merge_sql_string_if_not_empty("person_id", $Person_id);
			
			wh_log($Insurance_no, $Remote_insurance_no, "query memberinfo table prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					// login ok
					// user id 取得
					$mid=0;
					while($row = mysqli_fetch_array($result)){
						$mid = $row['mid'];
						$Role = $row['role'];
					}
					$data["status"]			= "true";
					$data["code"]			= "0x0200";
					$data["responseMessage"]= "已經有相同身份證資料!";
					$status_code 			= "C1";
					
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "無相同身份證資料!";
					$status_code 			= "C0";	
				}
			} else {
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";					
			}
			$Sales_id = get_sales_id($Insurance_no, $Remote_insurance_no, $link);
			
			$symbol4log = ($status_code == "C0") ? "(X) ": "";
			$sql = ($status_code == "C0") ? " :".$sql : "";
			wh_log($Insurance_no, $Remote_insurance_no, symbol4log."query memberinfo table result :".$data["responseMessage"].$sql, $Person_id);
			
			if ($status_code != "")
				$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, "", $status_code, $link);
			
			// 儲存資料至資料庫
			if ($status_code != "C1")
			{
			}
			
			if ($link != null)
				mysqli_close($link);
			wh_log($Insurance_no, $Remote_insurance_no, "query memberinfo sop finish :".$data["responseMessage"], $Person_id);
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";
			wh_log($Insurance_no, $Remote_insurance_no, "(X) query memberinfo sop catch :".$data["responseMessage"]."\r\n"."error detail :".$e.getMessage(), $Person_id);					
        }
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
	} else {
		//echo "參數錯誤 !";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
		wh_log($Insurance_no, $Remote_insurance_no, "(!)".$data["responseMessage"], $Person_id);
	}
	wh_log($Insurance_no, $Remote_insurance_no, "query member exit ->", $Person_id);
?>