<?php
	//$headers =  apache_request_headers();
	//var_dump($headers);
	//echo $headers['Authorization'];
	include "comm.php";
	include "db_tools.php";		
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
	
	$Insurance_no 		= isset($_POST['Insurance_no']) 		? $_POST['Insurance_no'] 		: '';
	$Remote_insuance_no = isset($_POST['Remote_insuance_no']) 	? $_POST['Remote_insuance_no'] 	: '';
	$Person_id 			= isset($_POST['Person_id']) 			? $_POST['Person_id'] 			: '';
	$FCM_title 			= isset($_POST['FCM_title']) 			? $_POST['FCM_title'] 			: '';
	$FCM_content 		= isset($_POST['FCM_content']) 			? $_POST['FCM_content'] 		: '';

	$Insurance_no 		= check_special_char($Insurance_no		);
	$Remote_insuance_no = check_special_char($Remote_insuance_no);
	$Person_id 			= check_special_char($Person_id			);
	$FCM_title 			= check_special_char($FCM_title			);
	$FCM_content 		= check_special_char($FCM_content		);
	//$Sales_id = isset($_POST['Sales_id']) ? $_POST['Sales_id'] : '';
	//$host = 'localhost';
	//$user = 'tglmember_user';
	//$passwd = 'tglmember210718';
	//$database = 'tglmemberdb';
	try {
		$link = mysqli_connect($host, $user, $passwd, $database);
		mysqli_query($link,"SET NAMES 'utf8'");
		
		$Insurance_no  	= mysqli_real_escape_string($link,$Insurance_no);
		$Remote_insuance_no  	= mysqli_real_escape_string($link,$Remote_insuance_no);
		$Person_id  	= mysqli_real_escape_string($link,$Person_id);
		$FCM_title  	= mysqli_real_escape_string($link,$FCM_title);	
		$FCM_content  	= mysqli_real_escape_string($link,$FCM_content);
		//$Sales_id  = mysqli_real_escape_string($link,$Sales_id);
	
		$Personid = trim(stripslashes($Person_id));
		$FCMtitle = trim(stripslashes($FCM_title));
		$FCMcontent = trim(stripslashes($FCM_content));
		$Insuranceno = trim(stripslashes($Insurance_no));
	
		
		$sql = "SELECT * FROM memberinfo where member_trash=0 ";
		if ($Personid != "") {	
			$sql = $sql." and Person_id='".$Personid."'";
		}
		
		if ($result = mysqli_query($link, $sql)){
			if (mysqli_num_rows($result)>0){
				while($row = mysqli_fetch_array($result)){
					
							$notificationToken = $row['notificationToken'];
							//need to LOG for future use
							//$sql = "INSERT INTO `notification_history` (`account`,`accountType`,`type`,`msg`,`createDateTime`) VALUES ('$store_account[$k]','0','5','$notificationmsg','$nowtime')";
							//$db->query($sql);
							
							if(strlen($notificationToken)<=2) 
							{
								//error;				$data["status"]="false";
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
							$msg = $FCMtitle."-".$FCMcontent;
							$sql = "INSERT INTO notificationlog (Person_id, role, msg, fcmresult, updatetime) VALUES ('$Personid', '1', '$msg', '$fcmresult', NOW())";
							mysqli_query($link, $sql);
							
							break;
				}
			}
			else
			{
				$data["status"]="false";
				$data["code"]="0x0205";
				$data["responseMessage"]="無此人員推播失敗";
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
				exit;				
			}
		}
		else
		{
				$data["status"]="false";
				$data["code"]="0x0204";
				$data["responseMessage"]="SQL fail!";
				header('Content-Type: application/json');
				echo (json_encode($data, JSON_UNESCAPED_UNICODE));	
				exit;
		}
		
		$data["status"]="true";
		$data["code"]="0x0200";
		$data["responseMessage"]="推播發送成功";	
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));		
	}catch (Exception $e) {
		//$this->_response(null, 401, $e->getMessage());
		//echo $e->getMessage();
		$data["status"]="false";
		$data["code"]="0x0202";
		$data["responseMessage"]="系統異常";	
		header('Content-Type: application/json');
		echo (json_encode($data, JSON_UNESCAPED_UNICODE));			
	}			
?>
