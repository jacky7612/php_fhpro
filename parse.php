<?php
	// 分析json資料
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
	
	// 分析json資料
	function parse_OCR($ret_data, $with_print = false)
	{
		$data = array();
		$data = result_message("false", "0x0206", "parse ocr failure!", "");
		try
		{
			$object_id = json_decode($ret_data);
			if (strlen($object_id->ticket) > 0)
			{
				//echo count($object_id->pageList)."\r\n";
				for ($i = 0; $i < count($object_id->pageList); $i++)
				{
					$page_data = $object_id->pageList[$i];
					if (strlen($page_data->page) > 0)
					{
						for ($j = 0; $j < count($page_data->photoList); $j++)
						{
							$photoList = $page_data->photoList[$j];
							if (strlen($photoList->photo) > 0)
							{
								for ($k = 0; $k < count($photoList->photo); $k++)
								{
									$photo = $page_data->photo[$k];
									if (strlen($photo->result) > 0)
									{
										for ($m = 0; $m < count($photo->result); $m++)
										{
											$result = $photo->result[$m];
											// data here
											if ($result->text == "")
											{
												$data = result_message("false", "0x0206", $result->name." 不可為空白，請重拍身分證件", "");
												switch ($result->key)
												{
													case "ID_NAME":
														
														break;
												}
												return $data;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
		catch (Exception $e)
		{
			$data = result_message("false", "0x0206", "parse ocr failure!", "");
		}
		return $data;
	}
	function OCR_get_photo_id($ret_data)
	{
		$photo_id = "";
		try
		{
			$object_id = json_decode($ret_data);
			if (strlen($object_id->ticket) > 0)
			{
				for ($i = 0; $i < count($object_id->pageList); $i++)
				{
					$page_data = $object_id->pageList[$i];
					if (strlen($page_data->page) > 0)
					{
						for ($j = 0; $j < count($page_data->photoList); $j++)
						{
							$photoList = $page_data->photoList[$j];
							if (strlen($photoList->photo) > 0)
							{
								$photo_id = $photoList->photo;
							}
						}
					}
				}
			}
		}
		catch (Exception $e)
		{ }
		return $photo_id;
	}
?>
