<?php
	//check 帳號/密碼
	$host = getHost();
	$user = getUser();
	$passwd = getPassword();
	$database = getDatabase();
	date_default_timezone_set("Asia/Taipei");
	
	function getHost()
	{
		global $g_db_ip;
		$hostname = $g_db_ip;
		$hostname2 = trim(stripslashes($hostname));
		return str_replace(",", "", $hostname2);
	}
	
	function getUser()
	{
		global $g_db_user;
		$dbuser = $g_db_user;
		//$dbuser="tglmember_user";
		$dbuser2 = trim(stripslashes($dbuser));
		return str_replace(",", "_", $dbuser2);
	}
	
	function getPassword()
	{
		global $g_db_pwd;
		$dbpwd = $g_db_pwd;
		$dbpwd2 = trim(stripslashes($dbpwd));
		return str_replace(",", "", $dbpwd2);
	}
	
	function getDatabase()
	{
		global $g_db_name;
		$dbname=$g_db_name;
		$dbname2 = trim(stripslashes($dbname));
		return str_replace(",", "", $dbname2);
	}
	
	function guid()
	{
		mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$uuid = substr($charid, 0, 8)
			.substr($charid, 8, 4)
			.substr($charid,12, 4)
			.substr($charid,16, 4)
			.substr($charid,20,12);
		return $uuid;
	}		
	
	function encrypt($key, $payload)
	{
		global $g_iv;
		//$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
		$iv = $g_iv;
		$encrypted = openssl_encrypt($payload, 'aes-256-cbc', $key, 1, $iv);
		//return base64_encode($encrypted . '::' . $iv);
		return base64_encode($encrypted);
	}

	function decrypt($key, $garble)
	{
		global $g_iv;
		//list($encrypted_data, $iv) = explode('::', base64_decode($garble), 2);
		$iv = $g_iv;
		$encrypted_data = base64_decode($garble);
		return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 1, $iv);
	}
	function NASDir()
	{
		global $g_NAS_dir;
		$nasfolder = $g_NAS_dir;
		//$nasfolder = "/var/www/html/member/api/uploads/dis_idphoto/";//開發機
		return $nasfolder;
	}
?>
