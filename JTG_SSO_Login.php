<?php
	include "func.php";
	
	// initial
	$status_code 		 = "A0"; // 取得 SSO json data 成功
	$status_code_succeed = "A2"; // 成功狀態代碼
	$status_code_failure = "A1"; // 失敗狀態代碼
	$data 				 = array();
	$link				 = null;
	$Insurance_no		 = "";
	$Remote_insurance_no = "";
	$Person_id			 = "";
	$Sales_id 			 = "";
	$Mobile_no 			 = "";
	$base64pdf			 = "";
	$Title				 = "";
	$Role 				 = "";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	
	// 模擬資料
	$jsondataSample		 = '{"acceptId":"Ins1996","dueTime":"2022/10/31 23:00:00","applToken":"appl","prodID":"A","partnerCode":"tonlu","insuredToken":"ins2022","repreToken":"rep2022","applyNo":"appl1998","numbering":"phone2022","polSummary":[{"applyNo":"appl2022","numbering":"num123","applyVersion":"1.0","productName":"主約險種名稱","productCode":"B","policyCode":"","rolesInfo":[{"name":"張三","idcard":"A123456789","tel":"0912-345-777","roleName":"要保人","roleKey":"proposer"},{"name":"李四","idcard":"B123456789","tel":"0912-345-888","roleName":"要保人","roleKey":"insured"},{"name":"王五","idcard":"C123456789","tel":"0912-345-999","roleName":"法定代理人","roleKey":"legalRepresentative"},{"name":"業務","idcard":"E123456789","tel":"0912-345-111","roleName":"業務員","roleKey":"agentOne"}]}],"applicationData":[{"attacheCode":"pdf001","attacheName":"要保書","attacheContent":"base64","policyOwnerFlag":"Y","insuredFlag":"Y","representFlag":"Y","agentFlag":"Y","signTagSetting":"tag01","policyTagSetting":"tag02","applDateTagSetting":"tag03"}],"uploadData":[{"attacheCode":"attache001","attacheName":"附件一","policyOwnerFlag":"Y","insuredFlag":"Y","representFlag":"Y"}]}';
	
	$Sso_token = isset($_POST['Sso_token']) ? $_POST['Sso_token'] : ''; //$Sso_token = "Vfa4BO83/86F9/KEiKsQ0EHbpiIUruFn0/kiwNguXXGY4zea11svxYSjoYP4iURR";
	$App_type  = isset($_POST['App_type'])  ? $_POST['App_type']  : ''; //$App_type = "0";//業務員

	$remote_ip4filename = get_remote_ip_underline();
	wh_log("SSO_Login", $remote_ip4filename, "SSO Login for get insurance json entry <-");
	if (($Sso_token  != '') &&
		($App_type 	 != '') || true)
	{
		/*
		echo "host :".$host.'<br>';
		echo "user :".$user.'<br>';
		echo "passwd :".$passwd.'<br>';
		echo "database :".$database.'<br>';
		*/
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
			$appId = "";
			if($App_type2 == '0')
				$appId = "Q3RRdLWTwYo8fVtP"; //此 API 為業務呼叫
			if($App_type2 == '1')
				$appId = "HKgWyfYQv30ZE6AM"; //此 API 為客戶呼叫, ios
			if($App_type2 == '2')
				$appId = "8jy4wqtrCPMF1Jml"; //此 API 為客戶呼叫, android		
			
			$data['token'] 	= $Sso_token2;  							// 雲端達人給予的Token
			$data['appId'] 	= $appId ;									// 此為全球規格，要視雲端達人的規格再調整
			$url 			= $g_insurance_sso_api_url."ldi/sso/check"; // 此為全球規格，要視雲端達人的規格再調整
			$jsondata 		= json_encode($data);
			
			if ($g_test_mode)
				$out = $jsondataSample; // 模擬資料
			else
				$out = CallAPI("POST", $url, $jsondata, null, false); // 呼叫 API
			
			wh_log("SSO_Login", $remote_ip4filename, "sso api get response json succeed");
			//echo $out;
			$ret = json_decode($out, true);
			$cxInsurance = json_decode($out);
			$retRoleInfo = parse_or_print_json_data($cxInsurance, $Insurance_no, $Remote_insurance_no, $Person_id, $Mobile_no, $Sales_id);
			if ($retRoleInfo != null)//($ret['success'] == true)
			{
				//print_role_info($retRoleInfo);
				// 讀取的規格需調整
				/*
				$token 			= $ret['data']['accessToken'];
				$token_exp 		= $ret['data']['accessTokenExp'];
				$agentIdcard 	= $ret['data']['agentIdCard'];
				$secNum 		= $ret['data']['secNum'];
				$agentName 		= $ret['data']['agentName'];
				$agentMobile 	= $ret['data']['agentMobile'];
				*/
				$dueTime	 	= $cxInsurance->dueTime;
				wh_log("SSO_Login", $remote_ip4filename, "dueTime = ".$dueTime);
				
				//判斷 要保日 > 12H 或 要保日跨日 失敗：[A1] 成功：[A2]
				$over_12Hr = over_insurance_duetime(date("Y-m-d H:i:s"), $dueTime, 12);
				$over_day  = false;// over_insurance_day(	date("Y-m-d H:i:s"), $dueTime	 );
				if ($over_12Hr || $over_day)
				{
					$status_code 	= $status_code_failure;
					$data["status"]	= "false";
					$data["code"]	= "0x0204";
					if ($over_12Hr)
						$data["responseMessage"] = "要保日 > 12H";
					if ($over_day)
						$data["responseMessage"] = "要保日跨日";
					wh_log("SSO_Login", $remote_ip4filename, "(X) ".$data["responseMessage"]);
				}
				else
				{
					$status_code = $status_code_succeed;
				}
				wh_log("SSO_Login", $remote_ip4filename, "pass time");
				
				// TODO: insert status into DB
				// 儲存json
				$data = write_jsonlog_table($link, $Insurance_no, $Remote_insurance_no, $Person_id, $out, $status_code, $remote_ip4filename, false, "SSO_Login", $remote_ip4filename); 	// 紀錄json到資料庫
				if ($data["status"] == "true")
				{
					// 更新狀態
					for ($i = 0; $i < count($retRoleInfo); $i++)
					{
						$roleInfo = $retRoleInfo[$i];
						for ($j = 0; $j < count($roleInfo); $j++)
						{
							$Tmp_Person_id 	= $roleInfo[$j]["idcard"];
							$Tmp_role 		= $roleInfo[$j]["roleKey"];
							$Tmp_Mobile_no 	= $roleInfo[$j]["tel"];
							$data = modify_order_state($link, $Insurance_no, $Remote_insurance_no, $Tmp_Person_id, $Sales_id, $Tmp_Mobile_no, $status_code, false, false, $Tmp_role, "SSO_Login", $remote_ip4filename);
							//$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $status_code, $link, false, false, $Role);
						}
					}
				}
				// 紀錄json到檔案
				if ($g_wjson2file_flag)
					wh_json($Insurance_no, $Remote_insurance_no, $out);
				
				// 儲存pdf到檔案
				$pdf_path = "";
				if ($g_wpdf2file_flag)
					$pdf_path = wh_pdf($Insurance_no, $Remote_insurance_no, $out);
				// 紀錄至 pdf_log table
				$data_pdf = array();
				$data_pdf = modify_pdf_log($link, $Insurance_no, $Remote_insurance_no, $Title, $base64pdf, $pdf_path, $status_code, false, "SSO_Login", $remote_ip4filename);
				wh_log("SSO_Login", $remote_ip4filename, "pdf operator result :". $data_pdf["responseMessage"]);
				//echo $out;
				// 儲存pdf到檔案 - 成功
				
				if ($data["status"]	== "true")
				{
					$data["status"]			 = "true";
					$data["code"]			 = "0x0200";
					$data["responseMessage"] = "json資料解析成功";
					$data["json"]			 = $out;
				}
				wh_log("SSO_Login", $remote_ip4filename, $data["responseMessage"]);
			}
			else
			{
				wh_log("SSO_Login", $remote_ip4filename, "(X) read json data failure :");
				$data["status"]			 = "false";
				$data["code"]			 = "0x0201";
				$data["responseMessage"] = "json資料解析異常";
				$data["json"]			 = $out;
			}
		}
		catch (Exception $e)
		{
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="系統異常";
			wh_log("SSO_Login", $remote_ip4filename, "(X) ".$data["responseMessage"]);
        }
		finally
		{
			wh_log("SSO_Login", $remote_ip4filename, "finally procedure");
			try
			{
				if ($link != null)
				{
					mysqli_close($link);
					$link = null;
				}
			}
			catch(Exception $e)
			{
				$data["status"]			= "false";
				$data["code"]			= "0x0202";
				$data["responseMessage"]= "Exception error: disconnect!";
			}
			wh_log("SSO_Login", $remote_ip4filename, "finally complete - status:".$status_code);//."\r\n".$g_exit_symbol."SSO Login for get insurance json exit ->");
		}
	}
	else
	{
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
		wh_log("SSO_Login", $remote_ip4filename, "(X) ".$data["responseMessage"]);
	}
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	wh_log("SSO_Login", $remote_ip4filename, "SSO Login for get insurance json exit ->"."\r\n");
?>