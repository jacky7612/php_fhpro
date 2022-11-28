<?php
	header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
	include("../func.php");

	global $g_create_meeting_apiurl, $g_prod_meeting_apiurl;
	
	set_time_limit(0);

	$link 		= null;
	$data_conn 	= array();
	if (file_exists("/tmp/routinedl.pid") == true)//還在跑
	{
		if (strtotime(date("Y-m-d H:i:s")) - filemtime("/tmp/routinedl.pid") > 3 * 60 * 60)//超過3小時
		{
			// 可能不正常離開
		}
		else
		{
			echo strtotime(date("Y-m-d H:i:s"))." - ".filemtime("/tmp/routinedl.pid");
			echo "\n";
			return;
		}
		touch("/tmp/routinedl.pid");
	}

	try
	{
		$mainurl = $g_create_meeting_apiurl;
		$remote_ip4filename = get_remote_ip_underline();
		wtask_log("Task_routinedl", $remote_ip4filename, "Task_routinedl entry <-");
		
		// connect mysql
		$data_conn = task_create_connect($link, "Task_routinedl", $remote_ip4filename, $Person_id);
		if ($data_conn["status"] == "false") return;

		//1. GET Token
		$out = get_meeting_token("Task_routinedl", $g_create_meeting_apiurl, $remote_ip4filename, $g_meeting_uid, $g_meeting_pwd);
		if (strpos($out, "\"success\"") == false) return;
		
		$ret = json_decode($out, true);
		if($ret['success'] == true)
		{
			echo "get token succeed\r\n";
			$token = $ret['token'];
		}
		else
		{
			//$sql = "update meetinglog SET log = 'can not get the frsip token' where meetingid = '".$meetingid."'";
			//$ret = mysqli_query($link, $sql);					
			if (file_exists("/tmp/routinedl.pid") == true) unlink("/tmp/routinedl.pid");

			if (_ENV == "PROD")
			{
				$mainurl = $g_prod_meeting_apiurl;
				$out = get_meeting_token("Task_routinedl", $g_prod_meeting_apiurl, $remote_ip4filename, $g_meeting_uid, $g_meeting_pwd);
				if (strpos($out, "\"success\"") == false) return;
				
				$ret = json_decode($out, true);
				if($ret['success'] == true)
				{
					echo "get prod token succeed\r\n";
					$token = $ret['token'];
				}
				else
					return;//both crash
			}
		}

		$header = array('X-frSIP-API-Token:'.$token);
		
		/*
		//檢查看看vmr字串是否有變
		$url = $mainurl."get/virtualmeeting/virtualmeeting/view/form";
		$data= "";
		$out = CallAPI4OptMeeting("GET", $url, $data, $header);
		$data = json_decode($out, true);
		$vmr = $data["sfbgatewayvmr"];

		for($i = 0; $i < count($vmr); $i++)
		{
			echo "vmr:".$vmr[$i][0];//=>12|23, VMR
			echo "\n";
			$vmrname = $vmr[$i][0];
			$vid1 = "vmr:".$vmr[$i][1];
			$vid = explode(":", $vid1);
			$vid = trim(stripslashes($vid));
			echo "vmr:".trim($vid[2]);//VID
			echo $vid[1];
			echo "\n";
			$vidkey = trim($vid[2]);//VID
			$vidkey = trim(stripslashes($vidkey));
			if(strstr($vid[1], "Transgolbe_MCU"))
			{
				echo "ooooo";
				//更新 or insert
				$sql = "SELECT * from vmrinfo where vid='".$vidkey."'";
				$result = mysqli_query($link, $sql);
				//echo $sql;
				if (mysqli_num_rows($result)>0){
					while($row = mysqli_fetch_array($result)){
						if($vmrname != $row['vmr'])
						{//有變動,須更新
							$sql = "UPDATE vmrinfo SET vmr = '$vmrname' where vid='".$vidkey."'";
							mysqli_query($link, $sql);
							break;
						}
					}
				}
				else
				{//找不到此VID, 需新增
					$sql = "INSERT INTO vmrinfo (vid, vmr, status) VALUES ('$vidkey', '$vmrname', '0')";
					mysqli_query($link, $sql);
					//echo $sql;
				}
			}
			
		}
		*/
		
		//recording
		$url 			= $mainurl."get/callrecordings/callrecordings/view/list";
		$data			= array();
		$data['start']	= '0';
		$data['limit']	= '999999';
		$today 			= date("Y-m-d");
		$tomorrow 		= strtotime(date("Y-m-d"));
		$tomorrow 		= strtotime("+1 day", $tomorrow);
		$tomorrow 		= strftime("%Y-%m-%d", $tomorrow);
		
		$data['filter']='{"cb_starttime":"on","cb_stoptime":"on","tf_starttime_date":"'.$today.'","tf_starttime_time":"00:00:00","tf_stoptime_date":"'.$tomorrow.'","tf_stoptime_time":"01:00:00"}';
		
		$out = CallAPI4OptMeeting("GET", $url, $data, $header);
		//echo $out;
		$data = json_decode($out, true);
		$idx  = 0;
		
		for ($k = 0; $k < count($data['list']); $k++)
		{
			//foreach($value as $data)
			//var_dump($data['list'][$k]['dstnum');
			//break;
			
			$filedata[$idx]['dstnum'] 		= $data['list'][$k]['dstnum'];
			$filedata[$idx]['starttime'] 	= strtotime($data['list'][$k]['starttime']);//實際時間
			$filedata[$idx]['stoptime'] 	= strtotime($data['list'][$k]['stoptime']);
			$filedata[$idx]['id'] 			= $data['list'][$k]['id'];		
			//echo  $data['list'][$k]['dstnum'];
			$idx++;
		}

		//2. To DB to get the meeting id for download and information
		//
		$sql = "select * from meetinglog where bSaved = 1 and  bDownload != 1";//有要存檔,但未下載過的
		if ($result = mysqli_query($link, $sql))
		{
			$rcd_count = mysqli_num_rows($result);
			echo "rcd_count :".$rcd_count."; sql query :".$sql."\r\n";
			if ($rcd_count > 0)
			{
				while ($row = mysqli_fetch_array($result))
				{
					$recordingid 			= 0;
					$vidname 				= "VMR-".$row['vid'];//vid
					$vid 					= $row['vid'];
					$insurance_no 			= $row['insurance_no'];
					$remote_insurance_no 	= $row['remote_insurance_no'];
					$bookstarttime 			= strtotime($row['bookstarttime']);
					$bookstoptime 			= strtotime($row['bookstoptime']);
					$realstartime 			= $row['starttime'];
					$bSaved 				= $row['bSaved'];//0 =>業務選擇不存檔的, 1=>選擇存檔的
					
					$insurance_no			= trim(stripslashes($insurance_no));
					$remote_insurance_no	= trim(stripslashes($remote_insurance_no));
					$bookstarttime			= trim(stripslashes($bookstarttime));
					$bookstoptime			= trim(stripslashes($bookstoptime));
					$realstartime			= trim(stripslashes($realstartime));
					$vidname				= trim(stripslashes($vidname));
					$vid					= trim(stripslashes($vid));
					$duplicate				= 0;//這間房間有無多個得檔案

					$realstartime1 			= "";
					
					//echo $bookstarttime;
					//find the recording id to download 
					echo "find the recording id to download...\r\n";
					for ($i = 0; $i < $idx; $i++)
					{
						echo "find time :".$i."\r\n";
						if ($filedata[$i]['dstnum'] == $vidname)
						{
							//echo "************".$vid;
							//找出這個會議室, 而且實際時間吻合在預約時段內的
							$realtimestamp = $filedata[$i]['starttime'];
							// echo "realtimestamp".$realtimestamp."\n";
							// $bookstarttimestamp = strtotime($bookstarttime);
							// echo "bookstarttime".$bookstarttime."\n";
							
							//if( $bookstarttime <= $filedata[$i]['starttime'] && $bookstoptime >= $filedata[$i]['starttime']
							//&& $bookstarttime <= $filedata[$i]['stoptime'])// && $bookstoptime >= $filedata[$i]['stoptime'] )
							if ($bookstarttime >= ($realtimestamp - 420) &&
								$bookstarttime <= ($realtimestamp + 300))
							{
								$recordingid = $filedata[$i]['id'];
								// echo "recordingid:".$recordingid."\n" ;
								
								if($recordingid == 0)
								{
									wtask_log("Task_routinedl", $remote_ip4filename, "skip");
									continue;
								}
							
								//2. Get the information of a call recording
								echo "2. Get the information of a call recording\r\n";
								$data 		= array();
								$url 		= $mainurl."get/callrecordings/callrecordings/".$recordingid;
								$data["Id"]	= $recordingid ;
								$out = CallAPI4OptMeeting("GET", $url, $data, $header);
								// echo $out;
								// echo "\n\n";
								$ret = json_decode($out, true);
								if($ret['success'] == true)
								{
									$stime = $ret['data']['starttime']; //start time
									$etime = $ret['data']['stoptime']; //stop time
									if (strlen($stime) <= 0)
									{
										wtask_log("Task_routinedl", $remote_ip4filename, "no recording file");
										continue;
									
									}
									if ($duplicate == 0)
									{
										if ($stime == $realstartime)
										{
											wtask_log("Task_routinedl", $remote_ip4filename, "#######had download########");
											continue;//這個檔已經下載過了
											//$duplicate= 1;
											
										}
									}
									$bstarttime = strftime("%Y-%m-%d %H-%M-%S", $bookstarttime); 
									//get date
									$date 		= strftime("%Y%m%d", strtotime($stime));
									$foldername = "/dis_vdm/".$date; 
									if (createFolder($foldername) == false)
									{
										$sql = "update meetinglog SET log = 'can not create folder' where vid = '".$vid."' and insurance_no='".$insurance_no."' and remote_insurance_no='".$remote_insurance_no."' and bookstarttime = '".$bstarttime."'";
										$ret = mysqli_query($link, $sql);											
										continue;
									}

									//3. Download
									echo "3. Download\r\n";
									if($bSaved == 1)
									{
										if($duplicate == 1)
										{
											if(strtotime($stime)<strtotime($realstartime1)) //後面的會議才是正確的
											{
												wtask_log("Task_routinedl", $remote_ip4filename, "error vmr -".$vidname."\n"."error stime-".$stime."\n"."error realstartime-".$realstartime1);
												continue;
											}
										}
										
										$url 		=  $mainurl."get/callrecordings/callrecordings/".$recordingid."/download";
										$data 		= array();
										$data["Id"]	= $recordingid ;
										$out = CallAPI4OptMeeting("GET", $url, $data, $header);
										
										$name 	  = strftime("%Y%m%d%H%M%S", strtotime($stime));
										$name    .= "_".$bookstarttime."_".$recordingid;
										$filename = $foldername."/".$name.".mp4";
										
										try
										{
											$fp = fopen($filename, "w");
											
											$pieces = str_split($out, 1024 * 4);
											foreach ($pieces as $piece)
												fwrite($fp, $piece, strlen($piece));
											fclose($fp);
										}
										catch (Exception $e)
										{
											$msg = "error execute routine download\n"."error vmr -".$vidname."\n"."error stime-".$stime."\n"."error filename-".$filename;
											echo $msg."\r\n";
											wtask_log("Task_routinedl", $remote_ip4filename, $msg."error :".$e->getMessage());
											continue;
										}
										
										$size = filesize($filename);
										if($size > 1024)
										{
											//save to DB
											//if($duplicate == 1)//因為這種情況只發生在會議室只有業務進去,但是客戶還沒進去時,業務又退出,這時業務又馬上進去,然後客戶也進去了
											////	$sql = "update meetinglog SET starttime = '$stime', stoptime = '$etime', bDownload = 1, filename='$filename', log='' where vid = '".$vid."' and insurance_no='".$insurance_no."'  and bookstarttime = '".$bstarttime."'";
											//else
											$sql = "update meetinglog SET starttime = '$stime', stoptime = '$etime', bDownload = 1, filename='$filename', log='' where vid = '".$vid."' and insurance_no='".$insurance_no."' and remote_insurance_no='".$remote_insurance_no."'  and bookstarttime = '".$bstarttime."'";
											
											$ret = mysqli_query($link, $sql);
											$realstartime1 = $stime;
											//delete this file from FRSIP;
											
											//$url =  $mainurl."delete/callrecordings/callrecordings/".$recordingid."/all";
											$url =  $mainurl."delete/callrecordings/callrecordings/".$recordingid."/all";
											$data = array();
											$data["Id"]=$recordingid ;
											$data["type"]="type";
											//$out = CallAPI4OptMeeting("POST", $url, $data, $header);					
										}
										else
										{
											//skip for next time
											$log = 'download file failed:'.$stime."-".$etime.":".$out;
											$sql = "update meetinglog SET bDownload = -1, log = '$log' where vid = '".$vid."' and insurance_no='".$insurance_no."' and remote_insurance_no='".$remote_insurance_no."'  and bookstarttime = '".$bstarttime."'";
											$ret = mysqli_query($link, $sql);
										}					
									}
									else if($bSaved == 0)
									{
										$url 			=  $mainurl."delete/callrecordings/callrecordings/".$recordingid."/all";
										$data 			= array();
										$data["Id"]		= $recordingid ;
										$data["type"]	= "type";
										//$out = CallAPI4OptMeeting("POST", $url, $data, $header);
										//echo "###########DELETE##############\n\n";
										//echo $out;
									}
								}
								else
								{
									$sql = "update meetinglog SET log = 'can not get the meeting information' where vid = '".$vid."' and insurance_no='".$insurance_no."' and remote_insurance_no='".$remote_insurance_no."'  and bookstarttime = '".$bstarttime."'";
									$ret = mysqli_query($link, $sql);
								}
								
								$duplicate = 1;
							}
							else
							{
								wtask_log("Task_routinedl", $remote_ip4filename, "not match recordingid:".$recordingid);
								//echo "not match recordingid:".$recordingid."\n";
							}
						}
					}
				}
			}
			echo "complete!\r\n";
			return;
		}
		echo "nothing to do complete!\r\n";
	}
	catch (Exception $e)
	{
		echo "error execute routine download\n";
		wtask_log_Exception("Task_vmrupdate", $remote_ip4filename, "Exception error :".$e->getMessage());
	}
	finally
	{
		wtask_log("Task_routinedl", $remote_ip4filename, "finally procedure");
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
			wtask_log_Exception("Task_routinedl", $remote_ip4filename, "Exception error: disconnect! error :".$e->getMessage());
		}
		wtask_log("Task_routinedl", $remote_ip4filename, "finally complete");
	}
	if (file_exists("/tmp/routinedl.pid") == true) unlink("/tmp/routinedl.pid");
	
	//4. expire token 
	$url 				= $mainurl."delete/api/token/expire";
	$data["username"]	= "administrator";
	$out = CallAPI4OptMeeting("POST", $url, $data, $header);
	wtask_log("Task_routinedl", $remote_ip4filename, "Task_routinedl exit ->"."\r\n");
?>