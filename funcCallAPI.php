<?php
	// Start_Meeting這個API需另獨立，Stop_Meeting相同	
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
			$out = CallAPI4OptMeeting("POST", $url, $data, $header);	
			//echo 'kick people'.$out.'\n';
		///////}
	}
	function CallAPI4OptMeeting($method, $url, $data = false, $header = null)
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
	// Get_MeetingInfo這個API需另獨立，Get_FrsipInfo相同
	function CallAPI4GetDevInfo($method, $url, $data = false, $header = null) //查過程式，目前沒用到
	{
		$url = trim(stripslashes($url));
		$method2 = trim(stripslashes($method));

		$curl = curl_init();

		switch ($method2)
		{
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);

				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				
				//curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				if($header != null)
					curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
				//echo $url;			
				break;
			case "GET":
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
	
	function CallAPI($method, $url, $data = false, $header = null, $isProposal_PDF = false) // same SSO_Login, Send_Otp_code, Get_AgentCase,
	{
		$url = trim(stripslashes($url));
		$method2 = trim(stripslashes($method));
		$curl = curl_init();
		switch ($method2)
		{
			case "POST":
				curl_setopt($curl, CURLOPT_POST, 1);
				if ($data)
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				curl_setopt($curl, CURLOPT_HEADER,0);
				//curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				if($header != null)
				{
					//curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
					curl_setopt($curl, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Authorization: Bearer ' . $header
						));				
				}
				else
					curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
				//echo $url;
				break;
			case "GET":
				//urlencode($header);
				//curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
				if($header != null)
					curl_setopt($curl, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json',
						'Authorization: Bearer ' . $header
						));			

				if ($isProposal_PDF) // Proposal_PDF 多了這個 jacky
					$url = sprintf("%s?act=%s&%s", $url, urlencode($header),http_build_query($data));
				break;
			case "PUT":
				curl_setopt($curl, CURLOPT_PUT, 1);
				break;
			default:
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

		$result = curl_exec($curl);
		//echo $result;
		curl_close($curl);

		return $result;
	}
	
	function CallAPI_viaFormData($method, $url, $data)
	{
		$url 		= trim(stripslashes($url));
		$method2 	= trim(stripslashes($method));
		$curl 		= curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		switch ($method2)
		{
			case "POST":
				$headers = array(
				   "Content-Type: application/x-www-form-urlencoded",
				);
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				curl_setopt($curl, CURLOPT_POST, true);
				break;
			case "GET":
				break;
			case "PUT":
				break;
			default:
				if ($data)
					$url = sprintf("%s?%s", $url, http_build_query($data));
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		$result = curl_exec($curl);
		curl_close($curl);

		return $result;
	}
?>
