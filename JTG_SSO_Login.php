<?php
	include "comm.php";
	include "db_tools.php";
	$Sso_token = isset($_POST['Sso_token']) ? $_POST['Sso_token'] : '';
	$App_type  = isset($_POST['App_type']) ? $_POST['App_type'] : '';
	//$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	//$App_type = "0";//業務員
	
	function CallAPI($method, $url, $data = false, $header = null)
	{
		$url = trim(stripslashes($url));
		$method2 = trim(stripslashes($method));

		$curl = curl_init();

		switch ($method2)
		{
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);

				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				
				curl_setopt($curl, CURLOPT_HEADER,0);
				//curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				if($header != null)
				{
					//curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
					curl_setopt($curl, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Authorization: Bearer ' . $header
						));				
				}
				else
					curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				//echo $url;
				break;
			case "GET":
				
				//curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
				if($header != null)
					curl_setopt($curl, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Authorization: Bearer ' . $header
						));			
				break;
			case "PUT":
				curl_setopt($curl, CURLOPT_PUT, 1);
				break;
			default:
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

		$result = curl_exec($curl);
		//echo $result;
		curl_close($curl);

		return $result;
	}

	if (($Sso_token  != '') &&
		($App_type 	 != ''))
	{
		//check 帳號/密碼
	
		//$host = '10.67.70.153';
		//$host ="localhost";
		//$user = 'tglmember_user';
		//$passwd = 'tglmember210718';
		//$database = 'tglmemberdb';
		try {
		
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");
			
			$App_type   = mysqli_real_escape_string($link,$App_type);	
			$Sso_token  = mysqli_real_escape_string($link,$Sso_token);

			$Sso_token2 = trim(stripslashes($Sso_token)); 
			$App_type2  = trim(stripslashes($App_type));
			
			if($App_type2 == '0')
				$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
			if($App_type2 == '1')
				$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫, ios	

			if($App_type2 == '2')
				$appId = "8jy4wqtrCPMF1Jml"; //此 API 為客戶呼叫, android		
				
			$data = array();
			$data['token']=$Sso_token2;
			$data['appId']= $appId ;
			$url = $g_mpost_url."ldi/sso/check";
			//$url = "http://10.67.67.53/ldi/sso/check";
			$jsondata = json_encode($data);
			$out = CallAPI("POST", $url, $jsondata, null);			
			//echo $out;
			$ret = json_decode($out, true);
			if($ret['success']==true)
			{
				$token = $ret['data']['accessToken'];
				$token_exp = $ret['data']['accessTokenExp'];
				$agentIdcard = $ret['data']['agentIdCard'];
				$secNum = $ret['data']['secNum'];
				$agentName = $ret['data']['agentName'];
				$agentMobile = $ret['data']['agentMobile'];				
				// echo $sql;

			    //TODO: insert status into DB
			    //
				echo $out;			
				exit;
			}	
			else
			{
				echo $out;			
				exit;
			}

		} catch (Exception $e) {
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data = array();
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="系統異常";					
        }
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	} else {
		//echo "need mail and password!";
		$data = array();
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	}
?>