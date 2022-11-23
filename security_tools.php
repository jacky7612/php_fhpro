<?php
	function check_time($value)
	{
		$token_time = strtotime($value);
		if(empty($token_time)) return false;
		
		$now = date("Y-m-d H:i:s");
		$diff = strtotime($now)-$token_time;
		return (abs($diff) <= 10800);
	}
	//新增檢查header
	function check_header($key, $value)
	{
		$token = decrypt($key, $value);
		return check_time($token);
	}
	
	//新增檢查特殊字元
	function check_special_char($str)
	{
		$str = str_replace(',', '', $str);
		$str = str_replace('‘', '', $str);
		$str = str_replace('“', '', $str);
		$str = str_replace(';', '', $str);
		$str = str_replace('+', '', $str);
		$str = str_replace('<', '', $str);
		$str = str_replace('>', '', $str);
		$str = str_replace('..', '', $str);
		$str = str_replace('/', '', $str);
		$str = str_replace(htmlspecialchars_decode("&alt"), "", $str);	
		return trim($str);
	}
	function check_special_char02($str)
	{
		$str = str_replace('\n', '', $str);
		$str = str_replace('\"', '"', $str);
		$str = str_replace('    ', '', $str);
		return trim($str);
	}
	$vuser="Zmh1c2VyMQ==";
	$vpwd="ZmhAMjAyMg==";
?>