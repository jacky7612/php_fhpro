<?php
	// 資料庫
	// -----------------------------------------------------------------------------------------------------------------
	$g_db_ip							= "127.0.0.1";
	$g_db_user							= "root";
	$g_db_pwd							= "JTG@1qaz@WSX";
	$g_db_name							= "fhmemberdb";
	
	// 加密金鑰
	// -----------------------------------------------------------------------------------------------------------------
	//$key = "9Dcl8uXVFt/vSYaizaE+KkAgXtYO0807"; //prod	
	$key 	= "YcL+NyCRl5FYMWhozdV5V8eu6qv3cLDL";	//uat
	$g_iv  	= "77215989@jotangi";
	
	// 系統參數
	// -----------------------------------------------------------------------------------------------------------------
	$g_exit_symbol						= "---------------------------  ";
	$g_test_mode						= true;
	$g_skip_over_12hr_day				= true;
	$g_wjson2file_flag					= true;
	$g_wpdf2file_flag					= true;
	
	$g_encrypt = [
					'id'       					=> false,
					'mobile'      				=> false,
					'member_name'   			=> false,
					'image'    					=> false,
					'ignor_verify_face'    		=> true
				 ];
	
	$g_trace_log = [
					'JTG_wh_log'       			=> true,
					'JTG_wh_log_Exception'      => true,
					'wh_log'   					=> true,
					'wh_log_watch_dog'    		=> true,
					'wh_log_Exception'    		=> true,
					'wtask_log'    				=> true,
					'wtask_log_Exception'    	=> true
				   ];
	
	// 向雲端達人取得保單的url
	// -----------------------------------------------------------------------------------------------------------------
	$g_insurance_sso_api_url 			= "https://localhost/fhpro/"						; // 取得保險公司相關資料 API url
	
	// 路徑
	// -----------------------------------------------------------------------------------------------------------------
	$g_root_url			 				= "https://localhost/test_fhpro/"					;
	$g_download_ios_url			 		= "https://localhost/test_fhpro/lds-install-ios.php"; // 一般營業員使用ipad，通常為ios系統
	$g_root_dir			 				= $_SERVER["DOCUMENT_ROOT"]."/fhpro/api/"			; // 網站根目錄	"/var/www/html/fhpro/"
	$g_log_path		  	 				= $g_root_dir."log/"								; // log directory
	$g_json_path	  	 				= $g_root_dir."json/"								; // json directory
	$g_pdf_path		  	 				= $g_root_dir."pdf/"								; // pdf directory
	$g_images_dir 						= $g_root_dir."images/"								; // 照片 directory
	$g_live_dir 						= $g_root_dir."live/"								; // 照片 directory
	$g_attachment_dir 					= $g_root_dir."attachment/"							; // 附件照片 directory
	$g_watermark_src_url 				= $g_root_url."watermark.png"						; // 浮水印來源
	
	// 人臉辨識
	// -----------------------------------------------------------------------------------------------------------------
	$g_verify_is_face_apiurl 			= $g_root_url."faceengine/api/faceDetect.php"		; // 辨別是否為人臉API url
											// 'http://3.37.63.32/faceengine/api/faceDetect.php';
	$g_face_compare_apiurl 				= $g_root_url."faceengine/api/faceCompare.php"		; // 比對人臉API url
											// 'http://3.37.63.32/faceengine/api/faceCompare.php';
	$g_live_compare_eyes_apiurl			= $g_root_url."faceengine/api/faceEyeState.php"		; // 遮掩/眨眼辨識
	$g_live_compare_face_pose_apiurl01 	= $g_root_url."faceengine/api/facePosState.php"		; // 臉部角度辨識
	$g_live_compare_face_pose_apiurl02 	= $g_root_url."faceengine/api/facePosState.php"		; // 臉部角度辨識
	$g_live_compare_face_pose_apiurl00 	= $g_root_url."faceengine/api/facePosState.php"		; // 臉部角度辨識
	$g_NAS_dir 							= "/dis_app/dis_idphoto/"							; // NAS 路徑
	
	// OTP 簡訊
	// -----------------------------------------------------------------------------------------------------------------
	$g_OTP_enable						= false												; // 啟用OTP API - true:呼叫API; false:使用假資料
	$g_return_OTP_code_enable			= true												; // true:return code 中增加json欄位"OTP_code"返回OTP碼
	$g_OTP_apiurl 						= "http://biz3.every8d.com.tw/firstlife/API21/HTTP/sendSMS.ashx"; // OTP url
	$g_OTP_api_value 					= [
											"UID"       			=> "NBTAONLINE",
											"PWD"      				=> "zaq12wsx",
											"SB"   					=> "第一金OTP驗證碼",
											"MSG"    				=> "第一金遠距行動投保APP(一次性驗證碼簡訊),您的驗證碼為:",
											"DEST"    				=> ""
										  ];
	
	// 業務最後按送出時，回傳保單至雲端達人
	// -----------------------------------------------------------------------------------------------------------------
	$g_PolicyNo_enable					= false															; // 雲端達人-取得保單號碼，並押上保單號碼
	$g_PolicyNo_apiurl 					= "http://biz3.every8d.com.tw/firstlife/API21/HTTP/sendSMS.ashx"; // 雲端達人-取得保單號碼 url
	
	$g_Policy_enable					= false; // 回傳保單資訊至雲端達人
	$g_Policy_apiurl 					= "http://biz3.every8d.com.tw/firstlife/API21/HTTP/sendSMS.ashx"; // 回傳保單資訊至雲端達人 url
	
	// FCM推播
	// -----------------------------------------------------------------------------------------------------------------
	$g_notify_apiurl					= "https://fcm.googleapis.com/fcm/send"							; // 推播 url
	$g_FCM_API_ACCESS_KEY				= "AAAAo_0kJqM:APA91bGINmsgm6Q4eIL4jEP5ujJQlXK3YlA3AetNvDzN9KnKG_Z0Zjl59F7qHCCv5lvNqIeWMwoy8JtOX164vtHvXN-D9LcyocoEKTrFlnkH212xDbgdUgCQvyhKemLrPDfZKKyrca74"; // FCM金鑰
	
	// 視訊會議
	// -----------------------------------------------------------------------------------------------------------------
	const _ENV 								= "UAT"		;
	const _MEETING_GATEWAY 					= "1"		;
	const _MEETING_START_TIME_APPOINTMENT	= 10 * 60	; // 第三方限制預約時段需提前5分鐘，保險時間為10分鐘，排程最好設在23：50
	const _MEETING_END_TIME_APPOINTMENT		= 60 * 60	; // 24 * 60 * 60 + 2 * 60 * 60; // 預約時段
	const _MEETING_ACCESSCODE_MAX			= 5			; // 取得5個accessCode
	
	$g_join_meeting_apiurl				= "https://jtgpexconf.jotangi.net"								; // 會議室 url
										  // Prod url :"https://dis-cn1.transglobe.com.tw"
										  /*
										  $LB = rand(1, 10);
											if ($LB > 5)
												$main_url = "https://dis-cn2.transglobe.com.tw";
											else
												$main_url = "https://dis-cn1.transglobe.com.tw";
										  */
	$g_join_meeting_max_license			= 250;
	$g_join_meeting_pincode				= "53758995";
	
	$g_test_vmr_id						= "1|21"														; // 測試時對應的vmr_id
	$g_vmr_map_title					= "Pexip"														; // 取得accessCode的辨識碼
	$g_meeting_uid 						= "administrator"												; // 存取meeting tocken API 帳號
	$g_meeting_pwd						= "JtG@53758995"												; // "CheFR63r" // 存取meeting tocken API 密碼
	$g_meeting_ip 						= "203.145.222.190"												; // API url 001 "10.67.70.169"
	$g_meeting_prod_ip					= "203.145.222.190"												; // API url 002 "10.67.65.174";
	$g_create_meeting_apiurl			= "http://".$g_meeting_ip."/RESTful/index.php/v1/"				; // 會議室 url
	$g_prod_meeting_apiurl				= "http://".$g_meeting_prod_ip."/RESTful/index.php/v1/"			; // 會議室 url
										  /*
										  if(_ENV == "PROD")
												$mainurl = "http://10.67.65.174/RESTful/index.php/v1/";
												或
												$mainurl = "http://10.67.65.180/RESTful/index.php/v1/";//內網 //PROD
											else
												$mainurl = "http://10.67.70.169/RESTful/index.php/v1/";//內網 //UAT
										  */
	
	// OCR辨識
	// -----------------------------------------------------------------------------------------------------------------
	$g_OCR_apiurl 						= "https://disuat.transglobe.com.tw:1443/jotangi/api/"			; // ocr url
	$g_OCR_front_type_code				= "5"; // 正面
	$g_OCR_back_type_code				= "4"; // 背面
	$g_OCR_get_token_param 				= [ // API requestToken 所需要的輸入參數
											"id"	=>	"Jotangi01",
											"psw"	=>	"Jotangi01"
										  ];
	$g_OCR_get_info_param				= [ // API uploadAndWait_base64 所需要的輸入參數
											"token"       			=> "",
											"file"      			=> "",
											"type"					=> ""
										  ];
	$g_OCR_get_head_graph_param			= [ // API requestHeadImage 所需要的輸入參數
											"token"       			=> "",
											"id"      				=> ""
										  ];
										  
	$g_gov_id_url						= "https://ldi.transglobe.com.tw"; // 內政部
?>
