<?php
	include("../func.php");
	
	global $g_create_meeting_apiurl, $g_prod_meeting_apiurl;
	
	wtask_log("Task_check_meetingroom entry <-");
	try
	{
		$link = mysqli_connect($host, $user, $passwd, $database);
		$data = result_connect_error ($link);
		if ($data["status"] == "false")
		{
			wtask_log("[Task_check_meetingroom] ".get_error_symbol($data["code"])." query result :".$data["code"]." ".$data["responseMessage"]."\r\n".$g_exit_symbol."send otp exit ->"."\r\n");
			return;
		}
		mysqli_query($link,"SET NAMES 'utf8'");
		
		$gateway = "12";
		$sql 	 =  "select * from vmrule where id = 1";// gateway = '$vmrgateway' where id = 1";
		$result  = mysqli_query($link, $sql);
		if (mysqli_num_rows($result) > 0){	
			while ($row = mysqli_fetch_array($result))
			{	
				$gateway = $row['gateway'];
			}
		}
		$gateway = check_special_char($gateway);
		set_time_limit(0);
		if (file_exists("/tmp/check_meetingroom.pid") == true)//還在跑
		{
			if(strtotime(date("Y-m-d H:i:s")) - filemtime("/tmp/check_meetingroom.pid")> 3*60*60)//超過3小時
			{
				// 可能不正常離開
			}
			else
			{
				$msg = strtotime(date("Y-m-d H:i:s"))." - ".filemtime("/tmp/check_meetingroom.pid");
				echo $msg."\r\n";
				wtask_log($msg."\r\n".$g_exit_symbol."Task_check_meetingroom exit ->"."\r\n");
				return;
			}
		}
		touch("/tmp/check_meetingroom.pid");
		
		$mainurl 	= $g_create_meeting_apiurl;
		$url 		= $mainurl."post/api/token/request";

		//1. GET Token
		$data 				= array();
		//$data["username"]	="administrator";
		$data["username"]	= "administrator";
		$hash 				= md5("CheFR63r");
		//$hash 			= md5("sT7m");
		$data["data"]		= md5($hash."@deltapath");
		//echo md5($hash."@deltapath");
		$out = CallAPI4OptMeeting("POST", $url, $data);
		//echo $out;
		$ret = json_decode($out, true);
		if($ret['success'] == true)
			$token = $ret['token'];
		else
		{
			unlink("/tmp/check_meetingroom.pid");
			echo "error";//error;
			//return;
			if (_ENV == "PROD")
			{
				$mainurl 			= $g_prod_meeting_apiurl;
				$url 				= $mainurl."post/api/token/request";
				$data 				= array();
				$data["username"]	= "administrator";
				$hash 				= md5("CheFR63r");
				$data["data"]		= md5($hash."@deltapath");
				$out = CallAPI4OptMeeting("POST", $url, $data);
				//echo $out;
				$ret = json_decode($out, true);
				if($ret['success'] == true)
					$token = $ret['token'];
				else
					return;//both crash
			}
			else
				return;
		}

		$header = array('X-frSIP-API-Token:'.$token);

		//0. 先得到目前線上的所有參與者
		$url 					= $mainurl."get/skypeforbusiness/skypeforbusinessgatewayparticipant/view/list";
		$data					= array();
		$data['gateway'] 		= $gateway;
		$data['service_type'] 	= 'conference';
		$data['start'] 			= '0';
		$data['limit'] 			= '9999';
		$out = CallAPI4OptMeeting("GET", $url, $data, $header);
		//echo $out;
		//return;
		$partdata = json_decode($out, true);
		//$part = $partdata['list'];
		foreach ($partdata['list'] as $part)
		{
			$msg = $part['conference'].":".$part["display_name"];
			echo $msg."\n";
		}		
		//return;
		$sql 	= "select * from vmrule where 1";
		$result = mysqli_query($link, $sql);
		$kicktime = 0;
		while($row = mysqli_fetch_array($result)){
			$kicktime = $row['kicktime']; 
		}
		$kicktime = check_special_char($kicktime);
		if ($kicktime <= 0) $kicktime = 900;
		
		//1. 檢查線上online 的vid from gomeeting
		$sql = "select * from gomeeting where 1";
		$result = mysqli_query($link, $sql);
		//echo $sql;
		if ($result <= 0)
		{}
		else
		{
			if (mysqli_num_rows($result) > 0)
			{
				while ($row = mysqli_fetch_array($result))
				{
					$vid 		= $row['vmr'];
					$starttime 	= $row['starttime'];
					$meetingid 	= $row['meetingid'];
					
					$vid 		= check_special_char($vid);
					$starttime 	= check_special_char($starttime);
					$meetingid 	= check_special_char($meetingid);
					
					$vid  		= mysqli_real_escape_string($link,$vid);
					$starttime  = mysqli_real_escape_string($link,$starttime);
					$meetingid  = mysqli_real_escape_string($link,$meetingid);

					//$vid = $row['vmr'];
					$count 	= 0;
					$sale 	= 0;
					$kickid = array();
					//$kickmeeting = array();
					echo "LIST PART\n";
					foreach ( $partdata['list'] as $part ) // 
					{
						if($part['conference'] == $vid)
						{
							if(strstr($part["display_name"], "業務"))
							{
								$sale =1;
							}
							if(strstr($part["display_name"], "10.67"))
							{
								//機器人不算數
							}
							else
							{
								$kickid[$count]=$part["id"];
								//$kickmeeting[$count] = $meetingid;
								$count++;
							}
						}
					}			
					//echo $part['conference'];
					//echo ";";
					echo $count;
					echo "\n";
					
					//2. 
					//echo $starttime.";";
					$now = strtotime(date('Y-m-d H:i:s'));
					$diff = $now -  strtotime($starttime);
					echo $diff;
					//echo "kicktime".$kicktime;
					if($sale == 0 && $diff>300 )//業務在5分鐘內沒進來就砍掉
					{
						Kick($mainurl, $header,$link, $kickid, $meetingid, $vid, $gateway);
						//upate meetinglog status for stop meeting, 1:norma stop, 2:kick
						$sql = "update meetinglog set bStop = 3,bookstoptime=NOW() where meetingid='".$meetingid."'";
						mysqli_query($link, $sql);				
					}
					else
					if ($count <= 1 && $diff > $kicktime)
					{
						echo "KICK\n";
						//if($part['conference'] == "1002")//for test only
							Kick($mainurl, $header,$link, $kickid, $meetingid, $vid, $gateway);
						//else
							//echo "Kick simulate\n";
							//upate meetinglog status for stop meeting, 1:norma stop, 2:kick
							$sql = "update meetinglog set bStop = 2, bookstoptime=NOW() where meetingid='".$meetingid."'";
							mysqli_query($link, $sql);
					}
					else
					{
						//若還沒超過15分鐘,就update 此筆的count人數
						$sql = "update gomeeting set count= '$count' where meetingid='".$meetingid."'";
						mysqli_query($link, $sql);
					}
				}
			}
		}
		//維持vmrinfo 與frsip 的一致
		//syncvmr($mainurl, $header,$link, $partdata);
		
		unlink("/tmp/check_meetingroom.pid");
		//4. expired token 
		$url = $mainurl."delete/api/token/expire";
		$data["username"]="administrator";
		$out = CallAPI4OptMeeting("POST", $url, $data, $header);	
	}
	catch (Exception $e)
	{
		wtask_log_Exception("Exception error :".$e->getMessage());
		echo "error";
	}
	finally
	{
		wtask_log("finally procedure");
		try
		{
			if ($link != null)
			{
				mysqli_close($link);
				$link = null;
			}
		}
		catch (Exception $e)
		{
			wtask_log_Exception("Exception error: disconnect! error :".$e->getMessage());
		}
		wtask_log("finally complete"."\r\n".$g_exit_symbol."Task_check_meetingroom exit ->."\r\n"");
	}
	
	// function section
	function syncvmr($mainurl, $header, $link, $partdata)
	{
		try
		{
			$sql = "select * from vmrinfo where 1";
			$result = mysqli_query($link, $sql);
			
			if (mysqli_num_rows($result) > 0)
			{
				while ($row = mysqli_fetch_array($result))
				{
					$vid = $row['vid'];
					$vid = check_special_char($vid);
					$count = 0;
					$used = -1;
					$vid  = mysqli_real_escape_string($link,$vid);
					
					$sql = "select * from gomeeting where vmr=$vid";
					$ret = mysqli_query($link, $sql);
					$used = ($ret > 0 && mysqli_num_rows($ret) > 0) ? 1 : 0; // 1:有預約會議室
					
					foreach ( $partdata['list'] as $part )
						if($part['conference'] == $vid)
							$count++;
					
					echo "syncvmr-count:".$count.":".$used;
					$now = date('Y-m-d H:i:s');
					if ($count <= 0 && $used == 0)//沒有人預約會議室
					{
						//update status vmrinfo
						$sql = "update vmrinfo SET status=0 , updatetime=NOW() where vid=$vid";
						$ret = mysqli_query($link, $sql);		
						
					}
				}
			}
		}
		catch(Exception $e)
		{
			wtask_log_Exception("Exception error syncvmr:".$e->getMessage());
			echo "error syncvmr";
		}
	}
	
	function Kick($mainurl, $header, $link, $kickid, $meetingid, $vid,$gateway)
	{
		//1.開始踢人
		//2.並刪除此accesscode by meetingid
		//3. accesscode 更新deletecode 狀態  (deletecode = 1)
		//4. 更新vminfo status (relese resouce, status = 0)	
		//5. delete gomeeting
		try
		{
			//2. delete virtualmeeting, 並刪除此accesscode by meetingid
			$data		= array();
			$data['id']	= $meetingid;
			$url 		=  $mainurl."delete/virtualmeeting/virtualmeeting/".$meetingid ;
			$out = CallAPI4OptMeeting("POST", $url, $data, $header);
			echo 'delete accesscode'.$out.'\n';
			wtask_log('delete accesscode 1.開始踢人'.$out);
			
			//1.開始踢人
			$url = $mainurl."delete/skypeforbusiness/skypeforbusinessgatewayparticipant/disconnect";
			for ($i = 0; $i < count($kickid); $i++)
			{
				$data					= array();
				$data['gateway'] 		= $gateway;
				$data['participant_id'] = $kickid[$i];
				$out = CallAPI4OptMeeting("POST", $url, $data, $header);	
				echo 'kick people'.$out.'\n';
			}
			$meetingid  = mysqli_real_escape_string($link,$meetingid);
			$vid  		= mysqli_real_escape_string($link,$vid);
			
			//3. accesscode 更新deletecode 狀態  (deletecode = 1)
			wtask_log('3. accesscode 更新deletecode 狀態  (deletecode = 1)');
			$sql = "update accesscode set deletecode = 1 where meetingid='".$meetingid."'";
			$result = mysqli_query($link, $sql);
			
			//4. 更新vminfo status (relese resouce, status = 0)
			wtask_log('4. 更新vminfo status (relese resouce, status = 0)');
			$vid  = mysqli_real_escape_string($link,$vid);
			$sql = "update vmrinfo set status = 0  , updatetime=NOW() where vid = '".$vid."'";
			$result = mysqli_query($link, $sql);
			
			//5. delete gomeeting
			wtask_log('5. delete gomeeting)');
			$sql = "delete from gomeeting where meetingid='".$meetingid."'";
			$result = mysqli_query($link, $sql);
		}
		catch(Exception $e)
		{
			wtask_log_Exception("Exception error kick:".$e->getMessage());
			echo "error kick";
		}
	}
?>
