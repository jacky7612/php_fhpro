<?php
	include("func.php");
	
	
	$Mobile_no 				= "";
	$Sales_id 				= "";
	
	$Insurance_no 			= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insurance_no 	= isset($_POST['Remote_insurance_no']) 	? $_POST['Remote_insurance_no'] : '';
	$Person_id 				= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Country_code 			= isset($_POST['Country_code']) 		? $_POST['Country_code'] 		: '';

	$Insurance_no 			= check_special_char($Insurance_no);
	$Remote_insurance_no 	= check_special_char($Remote_insurance_no);
	$Person_id 				= check_special_char($Person_id);
	$Country_code 			= check_special_char($Country_code);

	if ($g_test_mode)
	{
		$Insurance_no 		 = "Ins1996";
		$Remote_insurance_no = "appl2022";
		$Person_id 			 = "A123456789";
		$Country_code 		 = "tw";
	}
	
	wh_log($Insurance_no, $Remote_insurance_no, "Country Code entry <-", $Person_id);
	$token 			= isset($_POST['Authorization']) 		? $_POST['Authorization'] 		: '';
	$ret = protect_api("JTG_Modify_Country_Code", "Country Code exit ->", $token, $Insurance_no, $Remote_insurance_no, $Person_id);
	if ($ret["status"] == "false")
	{
		header('Content-Type: application/json');
		echo (json_encode($ret, JSON_UNESCAPED_UNICODE));
		return;
	}
	
	$status_code = "";
	if (($Person_id 			!= '') &&
		($Insurance_no 			!= '') &&
		($Remote_insurance_no 	!= '') &&
		($Country_code 			!= ''))
	{
		$link = null;
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			// 當資料不齊全時，從資料庫取得
			$ret_code = get_salesid_personinfo_if_not_exists($link, $Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $Member_name, false);
			if (!$ret_code)
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0203";
				$data["responseMessage"]= "API parameter is required!";
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));
				return;
			}
			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no);
			$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no);
			$Person_id  			= mysqli_real_escape_string($link, $Person_id);
			$Country_code  			= mysqli_real_escape_string($link, $Country_code);
			
			$Insurance_no 			= trim(stripslashes($Insurance_no));
			$Remote_insurance_no 	= trim(stripslashes($Remote_insurance_no));
			$Person_id 				= trim(stripslashes($Person_id));
			$Country_code 			= trim(stripslashes($Country_code));

			$sql = "SELECT * from countrylog where person_id='$Person_id' and insurance_no= '$Insurance_no' and remote_insurance_no= '$Remote_insurance_no' ";
			$result = mysqli_query($link, $sql);
			if (mysqli_num_rows($result) > 0)
			{
				$sql = "UPDATE countrylog SET countrycode='$Country_code' WHERE person_id='$Person_id' and insurance_no= '$Insurance_no' and remote_insurance_no= '$Remote_insurance_no' ";
			}
			else
			{
				$sql = "INSERT INTO countrylog (person_id, insurance_no, remote_insurance_no, countrycode, updatetime ) VALUES ('$Person_id', '$Insurance_no', '$Remote_insurance_no', '$Country_code', NOW() )  ";
			}
			wh_log($Insurance_no, $Remote_insurance_no, "modify countrylog table prepare", $Person_id);

			if ($result = mysqli_query($link, $sql))
			{
				$data["status"]			= "true";
				$data["code"]			= "0x0200";
				$data["responseMessage"]= "更新成功!";
				$status_code 			= "B0";
			}
			else
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";
				$status_code 			= "B1";
			}
			$symbol4log = ($status_code == "B1") ? "(X) ": "";
			$sql = ($status_code == "B1") ? " :".$sql : "";
			wh_log($Insurance_no, $Remote_insurance_no, $symbol4log."modify countrylog table result :".$data["responseMessage"].$sql, $Person_id);
			$data_Status = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $status_code, $link, false);
			
			if ($data["status"] 	   == "true" &&
				$data_Status["status"] == "false")
			{
				$data = $data_Status;
			}
			wh_log($Insurance_no, $Remote_insurance_no, "modify countrylog sop finish :".$data["responseMessage"], $Person_id);
		}
		catch (Exception $e)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= "Exception error!";
			wh_log($Insurance_no, $Remote_insurance_no, "(X) modify countrylog sop catch :".$data["responseMessage"]."\r\n"."error detail :".$e->getMessage(), $Person_id);			
		}
		finally
		{
			try
			{
				if ($link != null)
				{
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
		//echo "參數錯誤 !";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
	}
	$symbol_str = ($data["code"] == "0x0202" || $data["code"] == "0x0204") ? "(X)" : "(!)";
	if ($data["code"] == "0x0200") $symbol_str = "";
	wh_log($Insurance_no, $Remote_insurance_no, $symbol_str." query result :".$data["responseMessage"]."\r\n".$g_exit_symbol."Country Code exit ->", $Person_id);
	
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
?>