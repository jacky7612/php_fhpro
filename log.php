<?php
	date_default_timezone_set("Asia/Taipei");
	ini_set('memory_limit','-1');
	$glogfile ="";
	$log_filename = "/var/www/html/fhpro/log/";
	
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
	function wh_log($log_msg)
	{
		global $glogfile;	
		create_folder(log_filename);
		//$log_file_data = $glogfile;// . '.log';
		// if you don't add `FILE_APPEND`, the file will be erased each time you add a log
		file_put_contents($glogfile, date("Y-m-d H:i:s")."  ------  ".$log_msg."\n", FILE_APPEND);
	}
	function wh_log($Insurance_no, $Remote_insurance_no, $log_msg, $Personal_id = "")
	{
		if (strlen($Personal_id) > 0) $Personal_id = "_".$Personal_id;
		set_log_name($Insurance_no, $Remote_insurance_no, $Personal_id);
		global $glogfile;
		create_folder(log_filename);
		//$log_file_data = $glogfile;// . '.log';
		// if you don't add `FILE_APPEND`, the file will be erased each time you add a log
		file_put_contents($glogfile, date("Y-m-d H:i:s")."  ------  ".$log_msg."\n", FILE_APPEND);
	}
	function set_log_name($Insurance_no, $Remote_insurance_no, $Personal_id)
	{
		global $glogfile;
		//$glogfile = "/var/www/html/fhpro/api/uploads/log/".'log_'.date('Y-m-d').'_'.$Insurance_no.'_'.$Remote_insurance_no.'_'.time().'.log';
		$glogfile = $log_filename.'log_'.date('Y-m-d').'_'.$Insurance_no.'_'.$Remote_insurance_no.$Personal_id.'.log';
	}
?>
