<?php
	include "db_tools.php";
	const _ENV = "PROD"; 
	//const _ENV = "UAT"; 
	$key = "cLEzfgz5c5hxQwLWauCOdAilwgfn97yj";
	//echo $key.date("Ymd");
	$Authorization = md5($key.date("Ymd"));
	//echo $Authorization;
	/*
	$headers =  apache_request_headers();
	var_dump($headers);
	$Auth = false;

	echo "<br/>My Authorization is : ".$Authorization."<br/>";

	if (array_key_exists('Authorization', $headers ) == false) {
		echo "The 'Authorization' element is not in the headers";
		exit;
	}

	try {
	  if ($headers['Authorization'] == $Authorization){
		  $Auth = true;
	  }else {
		  $Auth = false;
	  }
	} catch (Exception $e) {
	  $this->_response(null, 401, $e->getMessage());
	  echo $e->getMessage();
		
	}
	if ($Auth != true) {
		echo "The 'Authorization' key is not match!";
		exit;
	}
	*/
	function CallAPI($method, $url, $data = false, $header = null)
	{
		$curl = curl_init();

		switch ($method)
		{
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);

				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				
				//curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				if ($header != null)
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				//echo $url;			
				break;
		  case "GET":
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));			
				//curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
				if($header != null)
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);			
				break;
			case "PUT":
				curl_setopt($curl, CURLOPT_PUT, 1);
				break;
			default:
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}

		// Optional Authentication:
		//curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		//curl_setopt($curl, CURLOPT_USERPWD, "username:password");

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		$result = curl_exec($curl);
		//echo $result;
		curl_close($curl);

		return $result;
	}
	
	function Kick($mainurl, $header,$link, $kickid, $meetingid, $vid,$gateway)
	{
		//1.開始踢人
		//2.並刪除此accesscode by meetingid
		//3. accesscode 更新deletecode 狀態  (deletecode = 1)
		//4. 更新vminfo status (relese resouce, status = 0)	
		//5. delete gomeeting
		
		//2. delete virtualmeeting, 並刪除此accesscode by meetingid

		//echo 'delete accesscode'.$out.'\n';		
		//1.開始踢人
		$url = $mainurl."delete/skypeforbusiness/skypeforbusinessgatewayparticipant/disconnect";
		//for($i = 0; $i < count($kickid); $i++)
		//{
			$data					= array();
			$data['gateway'] 		= $gateway;
			$data['participant_id'] = $kickid;
			$out = CallAPI("POST", $url, $data, $header);	
			//echo 'kick people'.$out.'\n';
		///////}
	}
	
	// Api ------------------------------------------------------------------------------------------------------------------------
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$Role 				= isset($_POST['Role']) 				? $_POST['Role'] 				: '';
	$Meeting_id 		= isset($_POST['Meeting_id']) 			? $_POST['Meeting_id'] 			: '';
	//$bSaved 			= isset($_POST['bSaved']) 				? $_POST['bSaved'] 				: '';

	/*
	proposer：要保人
	insured：被保人  
	legalRepresentative：法定代理人
	agentOne:業務
	*/
	if (($Insurance_no 			!= '') &&
		($Remote_insuance_no 	!= '') &&
		($Meeting_id 			!= '') &&
		($Role 					!= '') )
	{
		//check 帳號/密碼
		//$host = 'localhost';
		//$user = 'tglmember_user';
		//$passwd = 'tglmember210718';
		//$database = 'tglmemberdb';
		
		//echo $sql;
		//exit;
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Insurance_no  		= mysqli_real_escape_string($link, $Insurance_no		);
			$Remote_insuance_no = mysqli_real_escape_string($link, $Remote_insuance_no	);
			$Meeting_id  		= mysqli_real_escape_string($link, $Meeting_id			);
			$Role  				= mysqli_real_escape_string($link, $Role				);
			//$bSaved  = mysqli_real_escape_string($link,$bSaved);

			$sql = "SELECT * FROM orderinfo where order_trash=0 ";
			if ($Insurance_no != "") {	
				$sql = $sql." and insurance_no='".$Insurance_no."'";
			}
			if ($Remote_insuance_no != "") {	
				$sql = $sql." and remote_insuance_no='".$Remote_insuance_no."'";//"' LIMIT 1";
			}

			if ($result = mysqli_query($link, $sql))
			{
				if (mysqli_num_rows($result) > 0)
				{
					//$mid=0;
					$order_status="";
					while ($row = mysqli_fetch_array($result))
					{
						//$mid = $row['mid'];
						$order_status = $row['order_status'];
						//update 線上 人數 DB
						$sql = "update gomeeting SET count=count-1 where  count >0 and insurance_no='".$Insurance_no."'";
						if ($Remote_insuance_no != "") {	
							$sql = $sql." and remote_insuance_no='".$Remote_insuance_no."'";
						}
						$ret = mysqli_query($link, $sql);
						if($Role != "agentOne")
						{
							$data["status"]="true";
							$data["code"]="0x0200";
							$data["responseMessage"]="OK";	
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
							
							exit;
						}
					}
					try {
						if ($Role == "agentOne")//業務離開
						{
							$sql = "select * from gomeeting where insurance_no='".$Insurance_no."'";
							if ($Remote_insuance_no != "") {	
								$sql = $sql." and remote_insuance_no='".$Remote_insuance_no."'";//"' LIMIT 1";
							}
							$result = mysqli_query($link, $sql);
							while($row = mysqli_fetch_array($result)){
								$vmr = $row['vmr'];
								$meeting_id = $row['meetingid'];
							}
							$gateway = "12";
							$sql =  "select * from vmrule where id = 1";// gateway = '$vmrgateway' where id = 1";
							$result = mysqli_query($link, $sql);
							if (mysqli_num_rows($result) > 0){	
								while($row = mysqli_fetch_array($result)){	
									$gateway = $row['gateway'];
								}
							}							
							//先踢人
							if(_ENV == "PROD")
								$mainurl = "http://10.67.65.174/RESTful/index.php/v1/";//PROD
							else
								$mainurl = "http://10.67.70.169/RESTful/index.php/v1/";//UAT 內網
							
							
							//$mainurl = "https://disuat.transglobe.com.tw:444/RESTful/index.php/v1/";
							$url = $mainurl."post/api/token/request";

							//1. GET Token
							$data = array();
							//$data["username"]="administrator";
							$data["username"]="administrator";
							$hash = md5("CheFR63r");
							//$hash = md5("sT7m");
							$data["data"]=md5($hash."@deltapath");
							//echo md5($hash."@deltapath");
							$out = CallAPI("POST", $url, $data);
							//echo $out;
							$ret = json_decode($out, true);
							if($ret['success'] == true)
								$token = $ret['token'];
							else
							{
									echo "error";//error;
									exit;
							}

							$header = array('X-frSIP-API-Token:'.$token);
							/*
							$url = $mainurl."get/skypeforbusiness/skypeforbusinessgatewayparticipant/view/list";
							$data= array();
							$data['gateway'] = $gateway;
							$data['service_type'] = 'conference';
							$data['start'] = '0';
							$data['limit'] = '9999';
							
							$out = CallAPI("GET", $url, $data, $header);
							//echo $out;
							//exit;
							$partdata = json_decode($out, true);
							//$part = $partdata['list'];
							*/								
							//echo "LIST PART\n";
							//echo $vmr;
							$kickid = 0;
							$data		= array();
							$data['id']	= $meeting_id;
							$url 		= $mainurl."delete/virtualmeeting/virtualmeeting/".$meeting_id ;
							$out 		= CallAPI("POST", $url, $data, $header);							
							/*
							foreach ( $partdata['list'] as $part ) // 
							{
								if($part['conference'] == $vmr)
								{
									/////echo part['conference'];
									//echo ";";
									//echo $part['id'];
									Kick($mainurl, $header,$link, $part['id'], $meeting_id, $vmr, $gateway);
								}
							}							
							*/
							//3. accesscode 更新deletecode 狀態  (deletecode = 1)
							$sql = "update accesscode set deletecode = 1 where meetingid='".$meeting_id."'";
							$result = mysqli_query($link, $sql);

							//5. delete gomeeting
							$sql = "delete from gomeeting where meetingid='".$meeting_id."'";
							$result = mysqli_query($link, $sql);
							//upate meetinglog status for stop meeting, 1:norma stop, 2:kick
							$sql = "update meetinglog set bStop = 1, bookstoptime=NOW()  where meetingid='".$meeting_id."'";
							$result = mysqli_query($link, $sql);						

							//4. 更新vminfo status (relese resouce, status = 0)	
							$sql = "update vmrinfo set status = 0 , updatetime=NOW() where vid = '".$vmr."'";
							$result = mysqli_query($link, $sql);
							
							//刪除前先釋放vmr
							/*
							$sql = "update vmrinfo SET status = '0' where vid = '".$vmr."'";  //釋放
							$ret = mysqli_query($link, $sql);
							//刪除視訊會議
							$sql = "delete  from gomeeting where  insurance_no='".$Insurance_no."'";
							$ret = mysqli_query($link, $sql);
							*/
							//save file or not?
							/*							
							if($bSaved == "0")
							{
								$sql = "update meetinglog SET bSaved = 0 where insurance_no='".$Insurance_no."'";
								$ret = mysqli_query($link, $sql);
							}
							*/						
						}
						$data 					= array();
						$data["status"] 		= "true";
						$data["code"]			= "0x0200";
						$data["responseMessage"]= "OK";
					} catch (Exception $e) {
						//$this->_response(null, 401, $e->getMessage());
						//echo $e->getMessage();
						$data["status"]			= "false";
						$data["code"]			= "0x0202";
						$data["responseMessage"]= $e->getMessage();							
					}
				} else {
					$data["status"]			= "false";
					$data["code"]			= "0x0201";
					$data["responseMessage"]= "不存在此要保流水序號的資料!";						
				}
			} else {
				$data["status"]			= "false";
				$data["code"]			= "0x0204";
				$data["responseMessage"]= "SQL fail!";					
			}
			mysqli_close($link);
		} catch (Exception $e) {
            //$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data["status"]			= "false";
			$data["code"]			= "0x0202";
			$data["responseMessage"]= $e->getMessage();					
        }
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));
	} else {
		//echo "need mail and password!";
		$data["status"]			= "false";
		$data["code"]			= "0x0203";
		$data["responseMessage"]= "API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));			
	}
?>