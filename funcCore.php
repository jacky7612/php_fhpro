<?php
	// 訊息中心 public
	function result_message($status, $code, $responseMessage, $json)
	{
		$data = array();
		$data["status"]			= $status;
		$data["code"]			= $code;
		$data["order_status"]	= "";
		$data["responseMessage"]= $responseMessage;
		$data["json"]			= $json;
		return $data;
	}
	function result_connect_error($link)
	{
		$data = array();
		if (!$link)
		{
			$data = result_message("false", "0x0206", "連接錯誤：".mysqli_connect_error(), "");
		}
		else
		{
			$data = result_message("true", "0x0200", "連接成功", "");
		}
		return $data;
	}
	// 取得訊息符號
	function get_error_symbol($val)
	{
		/*
		0x0200	data parse succeed
		0x0201	data parse error					(X)
		0x0202	API parameter is required!			(!)
		0x0203	data exists							(!)
		0x0204	data not exists						(!)
		0x0205	dog err								(X)
		0x0206	other message - condiction			(!)
		0x0207	Exception error: disconnect!		(!)
		0x0208	SQL fail! please check query str	(!)
		0x0209	Exception error!					(X)
		*/
		$ret = "";
		
		if ($val == "0x0202" || $val == "0x0203" || $val == "0x0204" ||
			$val == "0x0206" || $val == "0x0207" || $val == "0x0208")
			$ret = "(!) ";
		else if ($val == "0x0201" || $val == "0x0205" || $val == "0x0209")
			$ret = "(X) ";
		return $ret;
	}
	
	function get_role_name($val)
	{
		$ret = "";
		switch ($val) {
			case "proposer":
				$ret = "要保人";
				break;
			case "insured":
				$ret = "被保人";
				break;
			case "legalRepresentative":
				$ret = "法定代理人";
				break;
			default:
				$ret = "";
		}
		return $ret;
	}
	// encrypt-加密  public
	function encrypt_string_if_not_empty($flag, $val)
	{
		global $key;
		
		$ret = $val;
		if ($val == "") return $ret;
		if ($flag)
			$ret = encrypt($key, $val);
		return $ret;
	}
	// decrypt-解密  public
	function decrypt_string_if_not_empty($flag, $val)
	{
		global $key;
		
		$ret = $val;
		if ($val == "") return $ret;
		if ($flag)
			$ret = decrypt($key, $val);
		return $ret;
	}
	// 組裝sql語法-非空白字  public
	function merge_sql_string_if_not_empty($column_name, $val)
	{
		$ret = ($val != "") ? " and ".$column_name."='".$val."'" : "";
		return $ret;
	}
	// 照片儲入Nas事先工作 public
	function will_save2nas_prepare($Insurance_no, $Remote_insurance_no, $Person_id, $front)
	{
		$data = array();
		$data["status"]			 = "true";
		$data["code"]			 = "0x0200";
		$data["responseMessage"] = "Create NAS Folder Success";
		$data["filename"] 		 = "";
		//$date = date("Ymd");
		$date = date("Y")."/".date("Ym")."/".date("Ymd");
		//$foldername ="/dis_app/dis_idphoto/".$date; 
		$foldername = NASDir().$date; 
		if (create_folder($foldername) == false)
		{
			$data["status"]			= "false";
			$data["code"]			= "0x0205";
			$data["responseMessage"]= "NAS fail!";
			$filename = "";
		}
		if ($data["status"] == "true")
		{
			$filename = $foldername."/".$Insurance_no."_".$Person_id."_".$front;
			$data["filename"] = $filename;
		}
		wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"], $Person_id);
		return $data;
	}
	// 照片儲入Nas public
	function save_image2nas($Insurance_no, $Remote_insurance_no, $Person_id, $filename, $image)
	{
		try
		{
			$fp = fopen($filename, "w");
			$orgLen = strlen($image);
			if($orgLen<=0)
			{
				fclose($fp);
				return -1;
			}
			
			$len = fwrite($fp, $image, strlen($image));
			if($orgLen!=$len)
			{
				fclose($fp);
				return -2;
			}
			
			fclose($fp);
		/*	
			//Verify
			$fp = fopen($filename, "r");
			$rImg = fread($fp, filesize($filename));
			if($orgLen!=strlen($rImg))
			{
				fclose($fp);
				return -3;		
			}

			fclose($fp);
		*/
		}
		catch (Exception $e)
		{
			wh_log($Insurance_no, $Remote_insurance_no, "saveImagetoNas failed:".$e->getMessage(), $Person_id);
			return -4;
		}
		return 1;
	}
?>
