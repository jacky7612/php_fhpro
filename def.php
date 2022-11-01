<?php
	$g_mpost_url 						= "https://localhost/test_fhpro/";//PROD不明，可能需去除
	
	$g_exit_symbol						= "---------------------------  ";
	$g_test_mode						= true;
	$g_wjson2file_flag					= true;
	$g_wpdf2file_flag					= false;
	$g_insurance_sso_api_url 			= "https://localhost/test_fhpro/"					; // 取得保險公司相關資料 API url
	
	$g_root_url			 				= $_SERVER['DOCUMENT_ROOT']."/test_fhpro"			; // 網站根目錄	"/var/www/html/fhpro/"
	$g_log_path		  	 				= $g_root_url."/log/"								; // log directory
	$g_json_path	  	 				= $g_root_url."/json/"								; // json directory
	$g_pdf_path		  	 				= $g_root_url."/pdf/"								; // pdf directory
	$g_target_dir 						= $g_root_url."/uploads/"							; // 照片 directory
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
