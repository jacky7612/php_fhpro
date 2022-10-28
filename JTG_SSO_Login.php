<?php
	include "comm.php";
	include "func.php";
	
	$status_code = "A0";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Sso_token = isset($_POST['Sso_token']) ? $_POST['Sso_token'] : '';
	$App_type  = isset($_POST['App_type'])  ? $_POST['App_type']  : '';
	//$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	//$App_type = "0";//業務員

	$remote_ip4filename = get_remote_ip_underline();
	wh_log("GetToken", $remote_ip4filename, "SSO Login for get insurance json entry <-");
	if (($Sso_token  != '') &&
		($App_type 	 != ''))
	{
		//check 帳號/密碼
	
		//$host = '10.67.70.153';
		//$host ="localhost";
		//$user = 'tglmember_user';
		//$passwd = 'tglmember210718';
		//$database = 'tglmemberdb';
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");
			wh_log("SSO_Login", $remote_ip4filename, "connect mysql succeed");
			
			$App_type   = mysqli_real_escape_string($link,$App_type);	
			$Sso_token  = mysqli_real_escape_string($link,$Sso_token);

			$Sso_token2 = trim(stripslashes($Sso_token)); 
			$App_type2  = trim(stripslashes($App_type));
			
			wh_log("SSO_Login", $remote_ip4filename, "connect mysql succeed");
			if($App_type2 == '0')
				$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
			if($App_type2 == '1')
				$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫, ios
			if($App_type2 == '2')
				$appId = "8jy4wqtrCPMF1Jml"; //此 API 為客戶呼叫, android		
				
			$data 			= array();
			$data['token'] 	= $Sso_token2;  							// 雲端達人給予的Token
			$data['appId'] 	= $appId ;									// 此為全球規格，要視雲端達人的規格再調整
			$url 			= $g_insurance_sso_api_url."ldi/sso/check"; // 此為全球規格，要視雲端達人的規格再調整
			$jsondata 		= json_encode($data);
			$out 			= CallAPI("POST", $url, $jsondata, null, false); // 呼叫 API
			wh_log("SSO_Login", $remote_ip4filename, "sso api get response json succeed");
			//echo $out;
			$ret = json_decode($out, true);
			if ($ret['success'] == true)
			{
				// 讀取的規格需調整
				$token 			= $ret['data']['accessToken'];
				$token_exp 		= $ret['data']['accessTokenExp'];
				$agentIdcard 	= $ret['data']['agentIdCard'];
				$secNum 		= $ret['data']['secNum'];
				$agentName 		= $ret['data']['agentName'];
				$agentMobile 	= $ret['data']['agentMobile'];
				$dueTime	 	= $ret['data']['dueTime'];
				
				//判斷 要保日 > 12H 或 要保日跨日 失敗：[A1] 成功：[A2]
				$over_12Hr = over_insurance_duetime(Now(), $dueTime, 12);
				$over_day  = over_insurance_day(Now()	 , $dueTime	   );
				if (over_12Hr || $over_day)
				{
					$status_code = "A1";
					$data["status"]="false";
					$data["code"]="0x0204";
					if (over_12Hr)
						$data["responseMessage"]="要保日 > 12H";
					if (over_day)
						$data["responseMessage"]="要保日跨日";
					wh_log($Insurance_no, $Remote_insurance_no, "(X) ".$data["responseMessage"], $Person_id);
				}
				else
				{
					$status_code = "A2";
				}
				
				// TODO: insert status into DB
				// 儲存json
				$data = write_jsonlog_table($Insurance_no, $Remote_insurance_no, $Person_id, $out, $status_code, $remote_ip4filename); 	// 紀錄json到資料庫
				//wh_json($Insurance_no, $Remote_insurance_no, $out); 						  						// 紀錄json到檔案
				$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $status_code);
				echo $out;
				
				if ($data["status"]	== "true")
				{
					$data["status"]			 = "true";
					$data["code"]			 = "0x0200";
					$data["responseMessage"] = "json資料解析成功";
					$data["json"]			 = $out;
				}
				wh_log($Insurance_no, $Remote_insurance_no, $data["responseMessage"]."\r\nSSO Login for get insurance json exit ->", $Person_id);
				exit;
			}	
			else
			{
				wh_log("SSO_Login", $remote_ip4filename, "(X) read json data failure :"."\r\nSSO Login for get insurance json exit ->");
				echo $out;
				exit;
			}
		}
		catch (Exception $e)
		{
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data = array();
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="系統異常";
			wh_log("SSO_Login", $remote_ip4filename, "(X) ".$data["responseMessage"]);
        }
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	} else {
		//echo "need mail and password!";
		$data = array();
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		wh_log("SSO_Login", $remote_ip4filename, "(X) ".$data["responseMessage"]);
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	}
	wh_log("SSO_Login", $remote_ip4filename, "SSO Login for get insurance json exit ->");
?>