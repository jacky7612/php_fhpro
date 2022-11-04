<?php
	$g_mpost_url 						= "https://localhost/test_fhpro/";//PROD不明，可能需去除
	
	$g_exit_symbol						= "---------------------------  ";
	$g_test_mode						= true;
	$g_wjson2file_flag					= true;
	$g_wpdf2file_flag					= false;
	
	$g_encrypt_id						= false;
	$g_encrypt_mobile					= false;
	$g_encrypt_Membername				= false;
	$g_encrypt_image					= false;
	$g_Ignor_verify_face				= true;
	
	$g_insurance_sso_api_url 			= "https://localhost/test_fhpro/"					; // 取得保險公司相關資料 API url
	
	$g_root_url			 				= $_SERVER['DOCUMENT_ROOT']."/test_fhpro"			; // 網站根目錄	"/var/www/html/fhpro/"
	$g_log_path		  	 				= $g_root_url."/log/"								; // log directory
	$g_json_path	  	 				= $g_root_url."/json/"								; // json directory
	$g_pdf_path		  	 				= $g_root_url."/pdf/"								; // pdf directory
	$g_images_dir 						= $g_root_url."/images/"							; // 照片 directory
	$g_live_dir 						= $g_root_url."/live/"								; // 照片 directory
	$g_attachment_dir 					= $g_root_url."/attachment/"						; // 附件照片 directory
	$g_watermark_src_url 				= $g_root_url."/watermark.png"						; // 浮水印來源
	$g_verify_is_face_apiurl 			= $g_root_url."/faceengine/api/faceDetect.php"		; // 辨別是否為人臉API url
											// 'http://3.37.63.32/faceengine/api/faceDetect.php';
	$g_face_compare_apiurl 				= $g_root_url."/faceengine/api/faceCompare.php"		; // 比對人臉API url
											// 'http://3.37.63.32/faceengine/api/faceCompare.php';
	$g_live_compare_eyes_apiurl			= $g_root_url."/faceengine/api/faceEyeState.php"	; // 遮掩/眨眼辨識
	$g_live_compare_face_pose_apiurl01 	= $g_root_url."/faceengine/api/facePosState.php"	; // 臉部角度辨識
	$g_live_compare_face_pose_apiurl02 	= $g_root_url."/faceengine/api/facePosState.php"	; // 臉部角度辨識
	$g_live_compare_face_pose_apiurl00 	= $g_root_url."/faceengine/api/facePosState.php"	; // 臉部角度辨識
	
	$g_OTP_enable						= false;
	$g_OTP_apiurl 						= "http://biz3.every8d.com.tw/firstlife/API21/HTTP/sendSMS.ashx"; // OTP url
	$g_OTP_UID_key						= "UID";
	$g_OTP_UID_value					= "NBTAONLINE";
	$g_OTP_PWD_key						= "PWD";
	$g_OTP_PWD_value					= "zaq12wsx";
	$g_OTP_subject_key					= "SB";
	$g_OTP_subject_value				= "第一金OTP驗證碼";
	$g_OTP_message_key					= "MSG";
	$g_OTP_message_value				= "第一金遠距行動投保APP(一次性驗證碼簡訊),您的驗證碼為:";
	$g_OTP_mobile_key					= "DEST";
	
	$g_PDF_apiurl 						= $g_mpost_url. "ldi/getPdf"						; // PDF url
?>
