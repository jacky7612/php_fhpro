<?php
	$g_mpost_url 						= "http://10.67.65.75:8080/global-server2/";//PROD不明，可能需去除
	
	$g_insurance_sso_api_url 			= "http://10.67.65.75:8080/global-server2/"			; // 取得保險公司相關資料 API url
	
	$g_root_url			 				= "/var/www/html/fhpro/"							; // 網站根目錄	  
	$g_log_filename  	 				= $g_root_url."log/"								; // log directory 
	$g_json_filename  	 				= $g_root_url."json/"								; // json directory
	$g_target_dir 						= $g_root_url."uploads/"							; // 照片 directory
	$g_watermark_src_url 				= $g_root_url."watermark.png"						; // 浮水印來源
	$g_verify_is_face_apiurl 			= 'http://127.0.0.1/faceengine/api/faceDetect.php'	; // 辨別是否為人臉API url
											// 'http://3.37.63.32/faceengine/api/faceDetect.php';
	$g_face_compare_apiurl 				= 'http://127.0.0.1/faceengine/api/faceCompare.php'	; // 比對人臉API url
											// 'http://3.37.63.32/faceengine/api/faceCompare.php';
	$g_live_compare_eyes_apiurl			= 'http://127.0.0.1/faceengine/api/faceEyeState.php'; // 遮掩/眨眼辨識
	$g_live_compare_face_pose_apiurl01 	= 'http://127.0.0.1/faceengine/api/facePosState.php'; // 臉部角度辨識
	$g_live_compare_face_pose_apiurl02 	= 'http://127.0.0.1/faceengine/api/facePosState.php'; // 臉部角度辨識
	$g_live_compare_face_pose_apiurl00 	= 'http://127.0.0.1/faceengine/api/facePosState.php'; // 臉部角度辨識
	$g_OTP_apiurl 						= $g_mpost_url. "ldi/otp/getOne"					; // OTP url
	$g_PDF_apiurl 						= $g_mpost_url. "ldi/getPdf"						; // PDF url
?>
