<?php
	$glog_json_file = "";
	$json_path = $g_json_path;
	
	/*
	function create_folder($name)
	{
		if (!file_exists($name)) 
		{
			// create directory/folder uploads.
			return mkdir($name, 0777, true);
		}
		else
		{
			return true;
		}
	}
	*/
	function wh_json($Insurance_no, $Remote_insurance_no, $json_data)
	{
		global $json_path;
		global $glog_json_file;
		
		set_json_file_name($json_path, $Insurance_no, $Remote_insurance_no);
		create_folder($json_path);
		if (!file_exists($glog_json_file))
		{
			$file = fopen($glog_json_file, "w"); 
			fwrite($file, $json_data); 
			fclose($file);
		}
		return $glog_json_file;
	}
	function set_json_file_name($json_path, $Insurance_no, $Remote_insurance_no)
	{
		global $glog_json_file;
		
		//$glogfile = "/var/www/html/fhpro/api/uploads/log/".'log_'.date('Y-m-d').'_'.$Insurance_no.'_'.$Remote_insurance_no.'_'.time().'.log';
		$glog_json_file = $json_path.$Insurance_no.'_'.$Remote_insurance_no.'.json';
	}
?>
