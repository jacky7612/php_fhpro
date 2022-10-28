<?php
	//include("header_check.php");
	include("db_tools.php"); 
	//include("nas_ip.php");
	include("security_tools.php");
	include("func.php");

	$Insurance_no = isset($_POST['Insurance_no']) ? $_POST['Insurance_no'] : '';
	$Remote_insurance_no = isset($_POST['Remote_insurance_no']) ? $_POST['Remote_insurance_no'] : '';
	
	$Insurance_no = check_special_char($Insurance_no);
	$Remote_insurance_no = check_special_char($Remote_insurance_no);

	// Api ------------------------------------------------------------------------------------------------------------------------
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

			$Insurance_no  			= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insurance_no  	= mysqli_real_escape_string($link, $Remote_insurance_no	);
			
			$Insuranceno 			= trim(stripslashes($Insurance_no));
			$Remote_insurance_no 	= trim(stripslashes($Remote_insurance_no));

			$sql = "SELECT a.*,b.member_name FROM orderinfo a ";
			$sql = $sql." inner join ( select person_id,member_name from memberinfo) as b ON a.person_id= b.person_id ";
			$sql = $sql." where  a.order_trash=0 ";
			//echo $sql;
			$sql = $sql.merge_sql_string_if_not_empty("insurance_no"		, $Insurance_no			);
			$sql = $sql.merge_sql_string_if_not_empty("remote_insurance_no"	, $Remote_insurance_no	);

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