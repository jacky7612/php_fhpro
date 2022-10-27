<?php
	include("db_tools.php");
	include("security_tools.php");
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

	$Sales_id = isset($_POST['Sales_id']) ? $_POST['Sales_id'] : '';
	//$Person_id = isset($_POST['Person_id']) ? $_POST['Person_id'] : '';
	$FCM_title = isset($_POST['FCM_title']) ? $_POST['FCM_title'] : '';
	$FCM_content = isset($_POST['FCM_content']) ? $_POST['FCM_content'] : '';

	$Sales_id = check_special_char($Sales_id);
	$FCM_title = check_special_char($FCM_title);
	$FCM_content = check_special_char($FCM_content);

	if (($Sales_id != '') && ($FCM_title != '') && ($FCM_content != '') ) {
		//&& ($Person_id != '') 
		try {
			$link = mysqli_connect($host, $user, $passwd, $database);
			mysqli_query($link,"SET NAMES 'utf8'");

			$Sales_id  = mysqli_real_escape_string($link,$Sales_id);
			//$Person_id  = mysqli_real_escape_string($link,$Person_id);
			$FCM_title  = mysqli_real_escape_string($link,$FCM_title);	
			$FCM_content  = mysqli_real_escape_string($link,$FCM_content);

		$Salesid = trim(stripslashes($Sales_id));
		$FCMtitle = trim(stripslashes($FCM_title));
		$FCMcontent = trim(stripslashes($FCM_content));
				
			//Sales_id
			$sql = "SELECT * FROM salesinfo where sales_trash=0 ";
			if ($Salesid != "") {	
				$sql = $sql." and sales_id='".$Salesid."'";
			}
			//if ($Person_id != "") {	
			//	$sql = $sql." and person_id='".$Person_id."'";
			//}
			
			if ($result = mysqli_query($link, $sql)){
				if (mysqli_num_rows($result)>0){
					while($row = mysqli_fetch_array($result)){
						
						$notificationToken = $row['notificationToken'];
						//$sql = "INSERT INTO `notification_history` (`account`,`accountType`,`type`,`msg`,`createDateTime`) VALUES ('$store_account[$k]','0','5','$notificationmsg','$nowtime')";
						//$db->query($sql);
						
						if(strlen($notificationToken)<=2) 
						{
							$data["code"]="0x0204";
							$data["responseMessage"]="notificationToken is NULL";
							header('Content-Type: application/json');
							echo (json_encode($data, JSON_UNESCAPED_UNICODE));								
							exit;
						}
						
						$fields = array(
							'to' => $notificationToken,
							"notification" => [
								"body" => $FCMcontent,
								"title" => $FCMtitle,
								"icon" => "ic_launcher",
								"sound" => "default",
							],
						);

						$headers = array(
							'Authorization: key=AAAAo_0kJqM:APA91bGINmsgm6Q4eIL4jEP5ujJQlXK3YlA3AetNvDzN9KnKG_Z0Zjl59F7qHCCv5lvNqIeWMwoy8JtOX164vtHvXN-D9LcyocoEKTrFlnkH212xDbgdUgCQvyhKemLrPDfZKKyrca74',
							'Content-Type: application/json',
						);
						for($i = 0; $i < 3; $i++)
						{
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
							curl_setopt($ch, CURLOPT_POST, true);
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
							curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
							$fcmresult = curl_exec($ch);
							
							curl_close($ch);	
							if(strlen($fcmresult)>2)
								break;							
						}
						$msg = $FCM_title."-".$FCM_content;
						$sql = "INSERT INTO notificationlog (Person_id, Role, msg, fcmresult, updatetime) VALUES ('$Sales_id', '0', '$msg', '$fcmresult', NOW())";
						mysqli_query($link, $sql);
						break;
					}
					$data["status"]="true";
					$data["code"]="0x0200";
					$data["responseMessage"]="推播發送成功!";							
				}else{
					$data["status"]="false";
					$data["code"]="0x0201";
					$data["responseMessage"]="無此業務員推播發送失敗!";						
				}
			}		
			
		} catch (Exception $e) {
			//$this->_response(null, 401, $e->getMessage());
			//echo $e->getMessage();
			$data["status"]="false";
			$data["code"]="0x0202";
			$data["responseMessage"]="Exception error!";
		}	
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
	} else {
		//echo "need mail and password!";
		$data["status"]="false";
		$data["code"]="0x0203";
		$data["responseMessage"]="API parameter is required!";
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));			
	}	
?>