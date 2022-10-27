<?php
	const $INT_NULL = 999999;
	function getChar4Step($val, $char)
	{
		$ArrChar = str_split($val);
		$ret["char"]  = ord($ArrChar[0]);
		$ret["value"] = intval($ArrChar[1]);
		if ($ret["char"] >= 65 && $ret["char"] <=90)
		{
			return $ret;
		}
		return $INT_NULL;
	}
	function allowUpdateStep($ori_status, $cur_status)
	{
		if (!(strlen($ori_status) >= 2 && strlen($cur_status) >= 2)) return $INT_NULL;
		
		$ret_ori = getChar4Step($ori_status);
		$ret_cur = getChar4Step($cur_status);
		if (
			($ret_ori["char"]  <  $ret_cur["char"]) ||
			($ret_ori["char"]  == $ret_cur["char"]  && $ret_ori["value"] <  $ret_cur["value"])
		   )
		{
			return true;
		}
		return false;
	}
	
	function getStepStr2Int($val)
	{
		$ArrChar = str_split($val);
		return ord($ArrChar[0]) + intval($ArrChar[1]);
	}
	function Modify_order_State($Insurance_no, $Remote_insurance_no, $Person_id, $Sales_id, $Mobile_no, $Statuscode)
	{
		if (($Insurance_no 			!= '') &&
			($Remote_insurance_no 	!= '') &&
			($Sales_id 				!= '') &&
			($Person_id 			!= ''))
		{
			try {
				//$link = mysqli_connect($host, $user, $passwd, $database);
				//mysqli_query($link,"SET NAMES 'utf8'");
				$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no);
				$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no);
				$Sales_id  				= mysqli_real_escape_string($link, $Sales_id);
				$Person_id  			= mysqli_real_escape_string($link, $Person_id);
				$Mobile_no  			= mysqli_real_escape_string($link, $Mobile_no);
				$Member_type  			= mysqli_real_escape_string($link, $Member_type);
				$Status_code  			= mysqli_real_escape_string($link, $Status_code);

				$Insuranceno 		 	= trim(stripslashes($Insurance_no));
				$Remote_insuranceno 	= trim(stripslashes($Remote_insurance_no));
				$Salesid 			 	= trim(stripslashes($Sales_id));
				$Personid 			 	= trim(stripslashes($Person_id));
				$Mobileno 			 	= trim(stripslashes($Mobile_no));
				$Membertype 		 	= trim(stripslashes($Member_type));
				$Statuscode 		 	= trim(stripslashes($Status_code));

				//$Personid = encrypt($key,($Personid));
				$Mobileno = addslashes(encrypt($key,($Mobileno)));
			
				
				$sql = "SELECT * FROM orderinfo where order_trash=0 ";
				if ($Insurance_no != "") {	
					$sql = $sql." and insurance_no='".$Insuranceno."'";
				}
				if ($Remote_insurance_no != "") {	
					$sql = $sql." and remote_insurance_no='".$Remote_insurance_no."'";
				}
				if ($Sales_id != "") {	
					$sql = $sql." and sales_id='".$Salesid."'";
				}
				if ($Person_id != "") {	
					$sql = $sql." and person_id='".$Personid."'";
				}
				if ($Mobile_no != "") {	
					$sql = $sql." and mobile_no='".$Mobileno."'";
				}
				if ($Member_type != "") {	
					$sql = $sql." and member_type='".$Membertype."'";
				}
				if ($result = mysqli_query($link, $sql)) {
					if (mysqli_num_rows($result) == 0) {
						$mid=0;
						if ($Mobile_no == '') {
							$data["status"]			= "false";
							$data["code"]			= "0x0203";
							$data["responseMessage"]= "操作：新增狀態-手機號碼不可為空白!"
						} else {
							if ($Statuscode == "") $Statuscode = "0";
							try {
								$sql2 = "INSERT INTO `orderinfo` (`insurance_no`,`remote_insurance_no`,`sales_id`,`person_id`,`mobile_no`,`member_type`, `order_status`, `order_trash`, `inputdttime`) VALUES ('$Insuranceno','$Remote_insuranceno','$Salesid','$Personid','$Mobileno',$Membertype,'$Statuscode', 0,NOW())";
								mysqli_query($link,$sql2) or die(mysqli_error($link));

								$sql2 = "INSERT INTO `orderlog` (`insurance_no`,`remote_insurance_no`,`sales_id`,`person_id`,`mobile_no`,`member_type`, `order_status`, `log_date`) VALUES ('$Insuranceno','$Remote_insuranceno','$Salesid','$Personid','$Mobileno',$Membertype,'$Statuscode',NOW())";
								mysqli_query($link,$sql2) or die(mysqli_error($link));
								
								//echo "user data change ok!";
								$data["status"]="true";
								$data["code"]="0x0200";
								$data["responseMessage"]="操作：新增狀態-新增資料完成!";
							} catch (Exception $e) {
								$data["status"]="false";
								$data["code"]="0x0202";
								$data["responseMessage"]="操作：新增狀態-Exception error!";
							}
						}
					} else {
						$data["status"]="false";
						$data["code"]="0x0201";
						$data["responseMessage"]="操作：新增狀態-已經有相同要保流水序號的資料!";	
						
						$ret = update_order_state($result, $Insuranceno, $Remote_insuranceno, $Salesid, $Personid, $Mobileno, $Membertype, $Statuscode);
						if ($ret == 0) {
							$data["status"]			 = "true";
							$data["code"]			 = "0x0200";
							$data["responseMessage"] = "操作：更新狀態-完成!";
						} else if ($ret == 1) {
							$data["status"]			 = "false";
							$data["code"]			 = "0x0202";
							$data["responseMessage"] = "操作：更新狀態-Exception error!";
						} else if ($ret == 2) {
							$data["status"]			 = "false";
							$data["code"]			 = "0x0201";
							$data["responseMessage"] = "操作：更新狀態-不存在此要保流水序號的資料!";
						}
					}
				} else {
					$data["status"]="false";
					$data["code"]="0x0204";
					$data["responseMessage"]="操作：更新狀態-SQL fail!";					
				}
				//mysqli_close($link);
			} catch (Exception $e) {
				$data["status"]="false";
				$data["code"]="0x0202";
				$data["responseMessage"]="操作：新增狀態-Exception error!";					
			}
			header('Content-Type: application/json');
			return $data;
		} else {
			//echo "need mail and password!";
			$data["status"]="false";
			$data["code"]="0x0203";
			$data["responseMessage"]="操作：access status-API parameter is required!";
			header('Content-Type: application/json');
			return $data;			
		}
	}	
	
	function update_order_state($result, $Insuranceno, $Remote_insuranceno, $Salesid, $Personid, $Mobileno, $Membertype, $Statuscode)
	{
		$ret = 0;
		if (mysqli_num_rows($result) > 0) {
			$flag = 0;
			try {
				while($row = mysqli_fetch_array($result)){
					$oldorder_status = $row['order_status'];
					$oldorderstatus = str_replace(",", "", $oldorder_status);
					//$membername = $row['member_name'];
				}
				
				if (allowUpdateStep($oldorderstatus, $Statuscode)) {
					$sql2 = "update `orderinfo` set `order_status`='$Statuscode' ,`updatedttime`=NOW() where insurance_no='$Insuranceno' and remote_insurance_no='$Remote_insuranceno' and sales_id='$Salesid' and person_id='$Personid' and order_trash=0";
					if ($Mobile_no != "") {	
						$sql = $sql." and mobile_no='".$Mobileno."'";
					}
					if ($Membertype != "") {	
						$sql = $sql." and mobile_no='".$Membertype."'";
					}
					mysqli_query($link, $sql2) or die(mysqli_error($link));
					$flag = 1;
				}
				if ($flag == 1) {
					$sql2 = "INSERT INTO `orderlog` (`insurance_no`,`remote_insurance_no`,`sales_id`,`person_id`,`member_type`, `order_status`, `log_date`";
					if ($Mobile_no != "") {	
						$sql2 = $sql2.",`mobile_no`";
					}
					if ($Membertype != "") {	
						$sql2 = $sql2.",`member_type`";
					}
					$sql2 = $sql2.") VALUES ('$Insuranceno','$Remote_insuranceno','$Salesid','$Personid','$Statuscode',NOW())";
					if ($Mobile_no != "") {	
						$sql2 = $sql2.",'$Mobileno'";
					}
					if ($Membertype != "") {	
						$sql2 = $sql2.",'$Membertype'";
					}
					mysqli_query($link,$sql2) or die(mysqli_error($link));
				}
				//echo "user data change ok!";
				$ret = 0;
			} catch (Exception $e) {
				$ret = 1;
			}
		} else {
			$ret = 2;
		}
		return $ret;
	}
?>
