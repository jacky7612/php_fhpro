<?php
	//新增檢查header
	function check_header($key, $value)
	{
		$token = decrypt($key, $value);
		if(empty(strtotime($token)))
		{
			return false;
		}
		$now = date("Y-m-d H:i:s");
		$diff = strtotime($now)-strtotime($token);
		if(abs($diff)<=10800)//3小時內有效
		  return true;
		else
		  return false;		
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
	$vuser="dGdsdXNlcg==";
	$vpwd="VGdsQDIwMjI=";
?>