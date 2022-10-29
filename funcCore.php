<?php
	include("def.php");
	include("db_tools.php");
	
	// 組裝sql語法-非空白字  public
	function merge_sql_string_if_not_empty($column_name, $val)
	{
		$ret = ($val != "") ? " and ".$column_name."='".$val."'" : "";
		return $ret;
	}
	// 照片儲入Nas事先工作 public
	function will_save2nas_prepare($Insurance_no, $Remote_insurance_no, $Person_id)
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
			$filename = $foldername."/".$Insurance_no."_".$Personid."_".$front;
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
