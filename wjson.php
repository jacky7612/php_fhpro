<?php
	date_default_timezone_set("Asia/Taipei");
	ini_set('memory_limit','-1');
	$glog_json_file ="";
	$json_filepath = "/var/www/html/fhpro/json/";
	
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
	function wh_json($Insurance_no, $Remote_insurance_no, $json_data)
	{
		set_json_file_name($Insurance_no, $Remote_insurance_no);
		global $glogfile;
		create_folder($json_filepath);
		$file = fopen($glog_json_file, "w"); 
		fwrite($file,json_data); 
		fclose($file);
	}
	function set_json_file_name($Insurance_no, $Remote_insurance_no)
	{
		global $glog_json_file;
		//$glogfile = "/var/www/html/fhpro/api/uploads/log/".'log_'.date('Y-m-d').'_'.$Insurance_no.'_'.$Remote_insurance_no.'_'.time().'.log';
		$glog_json_file = $json_filepath.$Insurance_no.'_'.$Remote_insurance_no.'.json';
	}
?>
