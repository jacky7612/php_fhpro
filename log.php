<?php
	date_default_timezone_set("Asia/Taipei");
	ini_set('memory_limit','-1');
	$glogfile = "";
	$log_path = $g_log_path;
	
	function create_folder($name)
	{
		if (!file_exists($name)) 
			return mkdir($name, 0777, true);
		else
			return true;
	}
	
	// write log for JTG_API
	function JTG_wh_log($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id = "")
	{
		global $g_trace_log;
		if ($g_trace_log["JTG_wh_log"] == false) return;
		wh_log_core($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id);
	}
	
	// write log for JTG_API exception
	function JTG_wh_log_Exception($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id = "")
	{
		global $g_trace_log;
		if ($g_trace_log["JTG_wh_log_Exception"] == false) return;
		wh_log_core($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id);
	}
	
	// write log for func
	function wh_log($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id = "")
	{
		global $g_trace_log;
		if ($g_trace_log["wh_log"] == false) return;
		wh_log_core($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id);
	}
	
	// write log for func watch dog
	function wh_log_watch_dog($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id = "")
	{
		global $g_trace_log;
		if ($g_trace_log["wh_log_watch_dog"] == false) return;
		wh_log_core($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id);
	}
	
	// write log for func exception
	function wh_log_Exception($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id = "")
	{
		global $g_trace_log;
		if ($g_trace_log["wh_log_Exception"] == false) return;
		wh_log_core($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id);
	}
	
	// write log core
	function wh_log_core($Insurance_no, $Remote_insurance_no, $log_msg, $Person_id = "")
	{
		global $log_path;
		global $glogfile;
		
		if ($Insurance_no == "SSO_Login") $Person_id = "";
		if (strlen($Person_id) > 0) $Person_id = "_".$Person_id;
		set_log_name($log_path, $Insurance_no, $Remote_insurance_no, $Person_id);
		create_folder($log_path);
		file_put_contents($glogfile, date("Y-m-d H:i:s")."  ------  ".$log_msg."\n", FILE_APPEND);
	}
	function set_log_name($dir, $Insurance_no, $Remote_insurance_no, $Person_id)
	{
		global $glogfile;
		
		$glogfile = $dir.'log_'.date('Y_m_d').'_'.$Insurance_no.'_'.$Remote_insurance_no.$Person_id.'.log';
	}
	function print_role_info($retRoleInfo)
	{
		$summarycnt = count($retRoleInfo);
		echo "------------------------------------------------------------<br>";
		echo "cnt = ".$summarycnt.'<br>';
		if ($summarycnt > 0)
		{
			for ($i = 0; $i < $summarycnt; $i++)
			{
				$role = $retRoleInfo[$i];
				$rolecnt = count($role);
				echo "------------------------------------------------------------<br>";
				echo "rolecnt = ".$rolecnt.'<br>';
				for ($j = 0; $j < $rolecnt; $j++)
				{
					echo "role name = ".$role[$j]["name"];
					echo "role idcard = ".$role[$j]["idcard"];
					echo "role tel = ".$role[$j]["tel"];
					echo "role roleName = ".$role[$j]["roleName"];
					echo "role roleKey = ".$role[$j]["roleKey"]."<br>";
				}
			}
		}
	}
?>
