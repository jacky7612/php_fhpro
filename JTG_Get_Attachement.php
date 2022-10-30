<?php
	include("func.php");
	
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';

	$Insurance_no 		= check_special_char($Insurance_no);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Person_id 			= check_special_char($Person_id);

	//$Mobile_no = isset($_POST['Mobile_no']) ? $_POST['Mobile_no'] : '';
	//$Member_name = isset($_POST['Member_name']) ? $_POST['Member_name'] : '';

	//$Person_id = "{$_REQUEST["Person_id"]}";

	//$image_name = addslashes($_FILES['image']['name']);
	//$sql = "INSERT INTO `product_images` (`id`, `image`, `image_name`) VALUES ('1', '{$image}', '{$image_name}')";
	//if (!mysql_query($sql)) { // Error handling
	//    echo "Something went wrong! :("; 
	//}

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
	
	$status_code_succeed = "P1"; // 成功狀態代碼
	$status_code_failure = "P0"; // 失敗狀態代碼
	$status_code = "";
	wh_log($Insurance_no, $Remote_insurance_no, "attachement entry <-", $Person_id);
	
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

	if (($Insurance_no 			!= '' &&
		 $Remote_insuance_no 	!= '' &&
		 $Person_id 			!= '' &&
		 strlen($Person_id) > 1) )
	{
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
			
			$sql = "SELECT * FROM attachement where ";
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no	  	 );
			$sql = $sql.merge_sql_string_if_not_empty("remote_insuance_no"	, $Remote_insuance_no);
			$sql = $sql.merge_sql_string_if_not_empty("person_id"			, $Personid			 );

			wh_log($Insurance_no, $Remote_insurance_no, "query prepare", $Person_id);
			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					$data["status"]			= "true";
					$data["code"]			= "0x0200";
					$data["responseMessage"]= "取得附件資訊成功!";
					$status_code = $status_code_succeed;
				}
				else
				{
					$data["status"]			= "false";
					$data["code"]			= "0x0200";
					$data["responseMessage"]= "查無此附件資訊!";
					$status_code = $status_code_failure;						
				}
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";					
			}
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";					
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
	} else {
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n"."attachement exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>