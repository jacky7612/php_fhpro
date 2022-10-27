<?php
	//include("header_check.php");
	include("db_tools.php"); 
	//include("nas_ip.php");
	include("security_tools.php");

	$Insurance_no = isset($_POST['Insurance_no']) ? $_POST['Insurance_no'] : '';
	$Insurance_no = isset($_POST['Insurance_no']) ? $_POST['Insurance_no'] : '';
	$Insurance_no = check_special_char($Insurance_no);

	$saveType = "DB";
	function getSaveType()
	{
		global $saveType;
		return $saveType;
	}
	function setSaveType($Type)
	{
		global $saveType;
		$saveType = $Type;
	}
	function getpidpic2($conn,$p_id,$keys,$front, $order_no){
		try {
			
			//oid,order_no,sales_id,person_id,mobile_no,member_type,order_status,log_date
			$sql3 = "SELECT * FROM idphoto ";
			//echo $sql;
			$pid = trim(stripslashes($p_id));
			
			if ($pid != "") {	
				$sql3 = $sql3." where person_id='".$pid."'";
			}
			//echo $sql3;
			if($order_no != "") {
				$sql3 .= " and insurance_id='".$order_no."'";
				
			}
			
			
			$pidpic2 = "";
			//echo $saveType;
			if ($result2 = mysqli_query($conn, $sql3)){
				if (mysqli_num_rows($result2) > 0){
					
					while($row2 = mysqli_fetch_array($result2)){
						
						if($row2['saveType']=='NAS'){
							setSaveType("NAS");
							//$saveType = "NAS";
							if ($front == "0"){
									if ($row2['frontpath'] != null) {
										$fp=fopen($row2['frontpath'], "r");
										$out=fread($fp, filesize($row2['frontpath']));
										fclose($fp);
										//echo decrypt($keys,$out); 
										//echo $row2['frontpath'];
										
										$pidpic2 =  decrypt($keys,$out); //decrypt($keys,$row2['front']);
									}else{
										$pidpic2 = "";
									}
								}
							if ($front == "1"){
								if ($row2['backpath'] != null) {
									$fp=fopen($row2['backpath'], "r");
									$out=fread($fp, filesize($row2['backpath']));
									fclose($fp);
										//echo decrypt($keys,$out); 
									$pidpic2 =  decrypt($keys,$out);
								}else{
									$pidpic2 = "";
								}							
							}
						}
						else
						{
							//echo "DB";
							setSaveType("DB");
							if ($front == "0"){
								if ($row2['front'] != null) {
									$pidpic2 = decrypt($keys,$row2['front']);
								}else{
									$pidpic2 = "";
								}
							}
							if ($front == "1"){
								if ($row2['back'] != null) {
									$pidpic2 = decrypt($keys,$row2['back']);
								}else{
									$pidpic2 = "";
								}						
							}
						}
						
						break;
					}
				}else {
					//有可能是舊的方式,沒有儲存 insurance_id
						$sql3 = "SELECT * FROM idphoto ";
						//echo $sql;					
						if ($pid != "") {	
							$sql3 = $sql3." where person_id='".$pid."'";
						}				
						if ($result2 = mysqli_query($conn, $sql3)){
							if (mysqli_num_rows($result2) > 0){							
								while($row2 = mysqli_fetch_array($result2)){
									if($row2['saveType']=='NAS'){
										setSaveType("NAS");
										//$saveType = "NAS";
										if ($front == "0"){
												if ($row2['frontpath'] != null) {
													$fp=fopen($row2['frontpath'], "r");
													$out=fread($fp, filesize($row2['frontpath']));
													fclose($fp);
													//echo decrypt($keys,$out); 
													//echo $row2['frontpath'];
													
													$pidpic2 =  decrypt($keys,$out); //decrypt($keys,$row2['front']);
												}else{
													$pidpic2 = "";
												}
											}
										if ($front == "1"){
											if ($row2['backpath'] != null) {
												$fp=fopen($row2['backpath'], "r");
												$out=fread($fp, filesize($row2['backpath']));
												fclose($fp);
													//echo decrypt($keys,$out); 
												$pidpic2 =  decrypt($keys,$out);
											}else{
												$pidpic2 = "";
											}							
										}
									}
									else
									{
										//echo "DB";
										setSaveType("DB");
										if ($front == "0"){
											if ($row2['front'] != null) {
												$pidpic2 = decrypt($keys,$row2['front']);
											}else{
												$pidpic2 = "";
											}
										}
										if ($front == "1"){
											if ($row2['back'] != null) {
												$pidpic2 = decrypt($keys,$row2['back']);
											}else{
												$pidpic2 = "";
											}						
										}
									}
									
									break;
									
								}
							}
							else
							{
								$pidpic2 = "";
							}
						}
					
				}
			}else {
				$pidpic2 = "";
			}
		} catch (Exception $e) {
			$pidpic2="";
		}	
		return $pidpic2;	
	}
	function getuserList($conn,$order_no,$keys){
		try {
			
			$orderno = trim(stripslashes($order_no));

			$sql2 = "( SELECT a.*,b.member_name FROM orderlog a inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql2 = $sql2." where a.order_no='".$orderno."' and a.member_type = 1 and a.order_status in ('D0','D1') order by log_date desc limit 1 )";
			$sql2 = $sql2." UNION ( SELECT a.*,b.member_name FROM orderlog a inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql2 = $sql2." where a.order_no='".$orderno."' and a.member_type = 2 and a.order_status in ('D0','D1') order by log_date desc limit 1 )";
			$sql2 = $sql2." UNION ( SELECT a.*,b.member_name FROM orderlog a inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql2 = $sql2." where a.order_no='".$orderno."' and a.member_type = 3 and a.order_status in ('D0','D1') order by log_date desc limit 1 )";

			//echo $sql2;

			$fields1 = array();
			if ($result2 = mysqli_query($conn, $sql2)){

				
				if (mysqli_num_rows($result2) > 0){
					//$mid=0;
					$order_status="";
					while($row2 = mysqli_fetch_array($result2)){
						$person_id = $row2['person_id'];
						//$member_name = $row2['member_name'];
						$member_name = decrypt($keys,stripslashes($row2['member_name']));

						$member_types = str_replace(",", "", $row2['member_type']);
						switch ($member_types) {
							case "1":
								$membertype = "要保人";
								break;
							case "2":
								$membertype = "被保人";
								break;
							case "3":
								$membertype = "法定代理人";
								break;
							default:
								$membertype = "";
						}
						$pid = str_replace(",", "", $person_id);
						$pname = str_replace(",", "", $member_name);
						$pid = check_special_char($pid);
						$pname = check_special_char($pname);
						
						$data2 = [
							'userId'       			=> $pid,   
							'userName'       		=> $pname, 
							'userType'   			=> $membertype,   
							'frontIdPhoto'    		=> getpidpic2($conn,$pid,$keys,"0", $order_no),
							'backIdPhoto'    		=> getpidpic2($conn,$pid,$keys,"1", $order_no),
							'saveType'    			=> getSaveType()
						];
						array_push($fields1, $data2);
					}
				}else{
					$fields1=null;
				}
			}else{

				$fields1=null;
			}
		} catch (Exception $e) {

			$fields1=null;
		
		}	
		return $fields1;
	}


	//-----------------start main process -------------------------
	$headers =  apache_request_headers();
	$token = $headers['Authorization'];
	
	if(check_header($key, $token)==true)
	{
		;//echo "valid token";
		
	}
	else
	{
		;//echo "error token";
		$data = array();
		$data["status"]="false";
		$data["code"]="0x0209";
		$data["responseMessage"]="Invalid token!";	
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
		exit;							
	}
	
	if (($Insurance_no != '')) {

		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  = mysqli_real_escape_string($link,$Insurance_no);
			$Insuranceno = trim(stripslashes($Insurance_no));

			$sql = "SELECT a.*,b.member_name FROM orderinfo a ";
			$sql = $sql." inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql = $sql." where  a.order_trash=0 ";
			//echo $sql;
			if ($Insuranceno != "") {	
				$sql = $sql." and a.order_no='".$Insuranceno."'";
			}

			if ($result = mysqli_query($link, $sql)){
				if (mysqli_num_rows($result) > 0){
					//$mid=0;
					//$order_status="";
					$fields2 = array();
					//$policyList = array();
					$userList = array();
					//$userposlist = array();
					//$videoList = array();
					$numbering = "";
					
					$row = mysqli_fetch_assoc($result);
	
					//$fields2=getStatus($link,$Insurance_no);
					
					$insuredDate = date('Ymd', strtotime($row['inputdttime']));  //"20210720";
					
			
					$userList = getuserList($link,$Insuranceno,$key);
					//$userList = [ ["userId" => "A123456789","userType" => "要保人","userPhoto" => "fajsdihjproi;rjgkljdsiofjsadljasie;fijwflkjdfoia==","identifyResultStatus" => "通過", "identifyFinishDate" => "20210721121527333" ],["userId" => "A123456789","userType" => "被保人","userPhoto" => "fajsdihjproi;rjgkljdsiofjsadljasie;fijwflkjdfoia==","identifyResultStatus" => "通過", "identifyFinishDate" => "20210721121527333"] ];
					
				
					//$videoList = getvideolist($link,$Insuranceno,$sip);

					$fields2 = ["code" => "0", "msg" => "查詢成功","insuredDate"  => $insuredDate, "userList" => $userList ];
	
					$data = $fields2;					

				}else{
					$data["code"]="-1";
					$data["msg"]="不存在此要保流水序號的資料!";	
					$data["insuredDate"]=date('Ymd');				
				}
			}else {
					$data["code"]="-1";
					$data["msg"]="SQL fail!";	
					$data["insuredDate"]=date('Ymd');	
			}
			mysqli_close($link);
		} catch (Exception $e) {
			$data["code"]="-1";
			$data["msg"]="Exception error!";	
			$data["insuredDate"]=date('Ymd');	
		}
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	}else{
		//echo "need mail and password!";
		$data["code"]="-1";
		$data["msg"]="API parameter is required!";
		$data["insuredDate"]=date('Ymd');	
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));			
	}
?>