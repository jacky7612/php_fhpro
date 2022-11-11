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
	function wh_log_anywhere($log_msg)
	{
		global $log_filename;
		global $glogfile;
		
		echo $log_path;
		create_folder($log_path);
		file_put_contents($glogfile, date("Y-m-d H:i:s")."  ------  ".$log_msg."\n", FILE_APPEND);
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
	function parse_or_print_json_data($cxInsurance, &$Insurance_no, &$Remote_insurance_no, &$Person_id, &$Mobile_no, &$Sales_id, $with_print = false)
	{
		$ret = null;
		try
		{
			if ($with_print)
			{
				echo "cxPolSummary count = ".count($cxInsurance->polSummary)."<br>"."<br>";
				echo "cxUploadData count = ".count($cxInsurance->applicationData)."<br>"."<br>";
				echo "cxUploadData count = ".count($cxInsurance->uploadData)."<br>"."<br>";
				
				for ($i = 0; $i < count($cxInsurance->polSummary); $i++)
				{
					echo "cxRolesInfo count = ".count($cxInsurance->polSummary[$i]->rolesInfo)."<br>"."<br>";
				}
				
				echo "------------------------------------------------------------<br>";
				echo "行動投保序號 :".$cxInsurance->acceptId."<br>";
				echo "遠距投保到期時間 :".$cxInsurance->dueTime."<br>";
				echo "要保人Token :".$cxInsurance->applToken."<br>";
				echo "險種代碼 :".$cxInsurance->prodID."<br>";
				echo "通路代碼 :".$cxInsurance->partnerCode."<br>";
				echo "被保人Token :".$cxInsurance->insuredToken."<br>";
				echo "法定代理人Token :".$cxInsurance->repreToken."<br>"."<br>";
			}
			$Insurance_no = $cxInsurance->acceptId;
			for ($i = 0; $i < count($cxInsurance->polSummary); $i++)
			{
				//$Insurance_no 		 = $cxInsurance->polSummary[$i]->numbering;
				$Remote_insurance_no = $cxInsurance->polSummary[$i]->applyNo;
				if ($with_print)
				{
					echo "------------------------------------------------------------<br>";
					echo "polSummary 遠距投保流水序號 :".$cxInsurance->polSummary[$i]->applyNo."<br>";
					echo "polSummary 行動投保流水序號 :".$cxInsurance->polSummary[$i]->numbering."<br>";
					echo "polSummary 要保資料PDF版次 :".$cxInsurance->polSummary[$i]->applyVersion."<br>";
					echo "productName 主約險種名稱 :".$cxInsurance->polSummary[$i]->productName."<br>";
					echo "productCode 主約險種代碼 :".$cxInsurance->polSummary[$i]->productCode."<br>";
					echo "polSummary 保單號碼 :".$cxInsurance->polSummary[$i]->policyCode."<br>"."<br>";
					echo "rolesInfo count :".count($cxInsurance->polSummary[$i]->rolesInfo);
				}
				for ($j = 0; $j < count($cxInsurance->polSummary[$i]->rolesInfo); $j++)
				{
					if ($with_print)
					{
							echo "polSummary 姓名 :".$cxInsurance->polSummary[$i]->rolesInfo[$j]->name."<br>";
							echo "polSummary 身分證字號 :".$cxInsurance->polSummary[$i]->rolesInfo[$j]->idcard."<br>";
							echo "polSummary 電話 :".$cxInsurance->polSummary[$i]->rolesInfo[$j]->tel."<br>";
							echo "polSummary 角色名稱 :".$cxInsurance->polSummary[$i]->rolesInfo[$j]->roleName."<br>";
							echo "polSummary 角色代碼 :".$cxInsurance->polSummary[$i]->rolesInfo[$j]->roleKey."<br>"."<br>";
					}
					$roleinfo = array(
									"name"		=> $cxInsurance->polSummary[$i]->rolesInfo[$j]->name,
									"idcard"	=> $cxInsurance->polSummary[$i]->rolesInfo[$j]->idcard,
									"tel"		=> $cxInsurance->polSummary[$i]->rolesInfo[$j]->tel,
									"roleName"	=> $cxInsurance->polSummary[$i]->rolesInfo[$j]->roleName,
									"roleKey"	=> $cxInsurance->polSummary[$i]->rolesInfo[$j]->roleKey
								);
					$ret[$i][$j] = $roleinfo;
					if ($Person_id == "" &&
						$cxInsurance->polSummary[$i]->rolesInfo[$j]->roleKey == "proposer")
					{
						$Person_id = $cxInsurance->polSummary[$i]->rolesInfo[$j]->idcard;
						$Mobile_no = $cxInsurance->polSummary[$i]->rolesInfo[$j]->tel;
					}
					/*
					if ($Mobile_no == "" &&
						$cxInsurance->polSummary[$i]->rolesInfo[$j]->roleKey == "insured")
					{
						$Mobile_no = $cxInsurance->polSummary[$i]->rolesInfo[$j]->tel;
					}
					if ($Mobile_no == "" &&
						$cxInsurance->polSummary[$i]->rolesInfo[$j]->roleKey == "legalRepresentative")
					{
						$Mobile_no = $cxInsurance->polSummary[$i]->rolesInfo[$j]->tel;
					}
					*/
					if ($Sales_id == "" &&
						$cxInsurance->polSummary[$i]->rolesInfo[$j]->roleKey == "agentOne")
					{
						$Sales_id = $cxInsurance->polSummary[$i]->rolesInfo[$j]->idcard;
					}
				}
			}
			if ($with_print)
			{
				for ($i = 0; $i < count($cxInsurance->applicationData); $i++)
				{
					echo "------------------------------------------------------------<br>";
					echo "applicationData 文件代碼 :".$cxInsurance->applicationData[$i]->attacheCode."<br>";
					echo "applicationData 文件名稱 :".$cxInsurance->applicationData[$i]->attacheName."<br>";
					echo "applicationData 文件內容 :".$cxInsurance->applicationData[$i]->attacheContent."<br>";
					echo "applicationData 要保人顯示旗標 :".$cxInsurance->applicationData[$i]->policyOwnerFlag."<br>";
					echo "applicationData 被保人顯示旗標 :".$cxInsurance->applicationData[$i]->insuredFlag."<br>";
					echo "applicationData 法定代理人顯示旗標 :".$cxInsurance->applicationData[$i]->representFlag."<br>";
					echo "applicationData 業務員顯示旗標 :".$cxInsurance->applicationData[$i]->agentFlag."<br>";
					echo "applicationData 簽名標籤設定 :".$cxInsurance->applicationData[$i]->signTagSetting."<br>";
					echo "applicationData 保單號標籤設定 :".$cxInsurance->applicationData[$i]->policyTagSetting."<br>";
					echo "applicationData 要保申請日標籤設定 :".$cxInsurance->applicationData[$i]->applDateTagSetting."<br>"."<br>";
				}
				for ($i = 0; $i < count($cxInsurance->uploadData); $i++)
				{
					echo "------------------------------------------------------------<br>";
					echo "uploadData 文件代碼 :".$cxInsurance->uploadData[$i]->attacheCode."<br>";
					echo "uploadData 文件名稱 :".$cxInsurance->uploadData[$i]->attacheName."<br>";
					echo "uploadData 要保人顯示旗標 :".$cxInsurance->uploadData[$i]->policyOwnerFlag."<br>";
					echo "uploadData 被保人顯示旗標 :".$cxInsurance->uploadData[$i]->insuredFlag."<br>";
					echo "uploadData 法定代理人顯示旗標 :".$cxInsurance->uploadData[$i]->representFlag."<br>"."<br>";
				}
			}
		}
		catch (Exception $e)
		{
			$ret = null;
		}
		return $ret;
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
