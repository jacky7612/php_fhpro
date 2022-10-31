<?php
	include "func.php";
	
	$status_code = "A0";
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$jsondata 			 = '{"acceptId":"Ins1996","dueTime":"2022/10/31 23:00:00","applToken":"appl","prodID":"A","partnerCode":"tonlu","insuredToken":"ins2022","repreToken":"rep2022","applyNo":"appl1998","numbering":"phone2022","polSummary":[{"applyNo":"appl2022","numbering":"num123","applyVersion":"1.0","productName":"主約險種名稱","productCode":"B","policyCode":"","rolesInfo":[{"name":"張三","idcard":"A123456789","tel":"0912-345-777","roleName":"要保人","roleKey":"proposer"},{"name":"李四","idcard":"B123456789","tel":"0912-345-888","roleName":"要保人","roleKey":"insured"},{"name":"王五","idcard":"C123456789","tel":"0912-345-999","roleName":"法定代理人","roleKey":"legalRepresentative"},{"name":"業務","idcard":"E123456789","tel":"0912-345-111","roleName":"業務員","roleKey":"agentOne"}]}],"applicationData":[{"attacheCode":"pdf001","attacheName":"要保書","attacheContent":"base64","policyOwnerFlag":"Y","insuredFlag":"Y","representFlag":"Y","agentFlag":"Y","signTagSetting":"tag01","policyTagSetting":"tag02","applDateTagSetting":"tag03"}],"uploadData":[{"attacheCode":"attache001","attacheName":"附件一","policyOwnerFlag":"Y","insuredFlag":"Y","representFlag":"Y"}]}';
	$Insurance_no		 = "";
	$Remote_insurance_no = "";
	$Person_id			 = "";
	$Sales_id 			 = "";
	$Mobile_no 			 = "";
	$Role 				 = "";
	$remote_ip4filename = get_remote_ip_underline();
	wh_log("SSO_Login", $remote_ip4filename, "SSO Login for get insurance json entry <-");
	
	$data = array();
	if ($jsondata != '')
	{
		//check 帳號/密碼
	
		//$host = '10.67.70.153';
		//$host ="localhost";
		//$user = 'tglmember_user';
		//$passwd = 'tglmember210718';
		//$database = 'tglmemberdb';
		echo "host :".$host.'<br>';
		echo "user :".$user.'<br>';
		echo "passwd :".$passwd.'<br>';
		echo "database :".$database.'<br>';
		$link = null;
		try
		{
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");
			wh_log("SSO_Login", $remote_ip4filename, "connect mysql succeed");
			
			$RetJsonOk = true;
			try
			{
				$out 			= $jsondata;
				wh_log("SSO_Login", $remote_ip4filename, "sso api get response json succeed");
				//echo $out
				$cxInsurance = json_decode($out);
				print_json_data($cxInsurance, $Insurance_no, $Remote_insurance_no, $Person_id, $Mobile_no, $Sales_id);
			}
			catch (Exception $e)
			{
				$RetJsonOk = false;
			}
			if ($RetJsonOk == true)
			{
				wh_log("SSO_Login", $remote_ip4filename, "RetJsonOk succeed");
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
				$over_day  = false;//over_insurance_day(date("Y-m-d H:i:s")    , $dueTime	 );
				wh_log("SSO_Login", $remote_ip4filename, "dueTime end ");
				if ($over_12Hr == true || $over_day == true)
				{
					$status_code 	= "A1";
					$data["status"]	= "false";
					$data["code"]	= "0x0204";
					if ($over_12Hr == true)
						$data["responseMessage"]="要保日 > 12H";
					if ($over_day  == true)
						$data["responseMessage"]="要保日跨日";
					wh_log("SSO_Login", $remote_ip4filename, "(X) ".$data["responseMessage"], $Person_id);
				}
				else
				{
					$status_code = "A2";
				}
				wh_log("SSO_Login", $remote_ip4filename, "pass time");
				
				// TODO: insert status into DB
				// 儲存json
				$data = write_jsonlog_table($Insurance_no, $Remote_insurance_no, $Person_id, $out, $status_code, $remote_ip4filename, $link, false); 	// 紀錄json到資料庫
				//wh_json($Insurance_no, $Remote_insurance_no, $out); 						  						// 紀錄json到檔案
				$data = modify_order_state($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $status_code, $link, false, $Role);
				//echo $out
				
				if ($data["status"]	== "true")
				{
					$data["status"]			 = "true";
					$data["code"]			 = "0x0200";
					$data["responseMessage"] = "json資料解析成功";
					$data["json"]			 = $out;
				}
				wh_log("SSO_Login", $remote_ip4filename, $data["responseMessage"]."\r\nSSO Login for get insurance json exit ->", $Person_id);
				return;
			}
			else
			{
				wh_log("SSO_Login", $remote_ip4filename, "(X) read json data failure :"."\r\nSSO Login for get insurance json exit ->");
				echo $out;
				return;
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
		finally
		{
			wh_log("SSO_Login", $remote_ip4filename, " do finally");
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
		}
	}
	else
	{
		//echo "need mail and password!";
		$data 					= array();
		$data["status"]			="false";
		$data["code"]			="0x0203";
		$data["responseMessage"]="API parameter is required!";
		wh_log("SSO_Login", $remote_ip4filename, "(X) ".$data["responseMessage"]);
	}
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	wh_log("SSO_Login", $remote_ip4filename, "SSO Login for get insurance json exit ->");
?>