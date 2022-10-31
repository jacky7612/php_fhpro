<?php
	class CXinsurance
	{
		// property declaration
		public $acceptId 		= ''		; // 行動投保序號
		public $dueTime 		= ''		; // 遠距投保到期時間
		public $applToken 		= ''		; // 要保人Token
		public $prodID 			= ''		; // 險種代碼
		public $partnerCode 	= ''		; // 通路代碼
		public $insuredToken 	= ''		; // 被保人Token
		public $repreToken 		= ''		; // 法定代理人Token
		public $polSummary 		= array()	; // 要保書摘要			cxPolSummary
		public $applicationData = array()	; // 要保書檔案明細		cxApplicationData
		public $uploadData 		= array()	; // 上傳檔案明細		cxUploadData
	}
	class CXpolSummary
	{
		// property declaration
		public $applyNo			= ''		; // 遠距投保流水序號
		public $numbering 		= ''		; // 行動投保流水序號
		public $applyVersion 	= ''		; // 要保資料PDF版次[可能沒有此定義]
		public $productName		= ''		; // 主約險種名稱
		public $productCode 	= ''		; // 主約險種代碼
		public $policyCode  	= ''		; // 保單號碼 		[可能沒有此定義]
		public $rolesInfo 		= array()	; // 身分別資訊		cxRolesInfo
	}
	class CXrolesInfo
	{
		// property declaration
		public $name			= ''		; // 姓名
		public $idcard 			= ''		; // 身分證字號
		public $tel 			= ''		; // 電話
		public $roleName		= ''		; // 角色名稱
		public $roleKey 		= ''		; // 角色代碼
											/*
											proposer：要保人 
											insured：被保人 
											legalRepresentative：法定代理人 
											agentOne：業務員一
											*/
	}
	class CXapplicationData
	{
		// property declaration
		public $attacheCode			= ''	; // 文件代碼
		public $attacheName			= ''	; // 文件名稱
		public $attacheContent		= ''	; // 文件內容
		public $policyOwnerFlag		= ''	; // 要保人顯示旗標 	Y/N
		public $insuredFlag			= ''	; // 被保人顯示旗標 	Y/N
		public $representFlag		= ''	; // 法定代理人顯示旗標 Y/N
		public $agentFlag			= ''	; // 業務員顯示旗標		Y/N
		public $signTagSetting		= ''	; // 簽名標籤設定
		public $policyTagSetting	= ''	; // 保單號標籤設定
		public $applDateTagSetting	= ''	; // 要保申請日標籤設定
	}
	
	class CXuploadData
	{
		// property declaration
		public $attacheCode			= ''	; // 文件代碼
		public $attacheName			= ''	; // 文件名稱
		public $policyOwnerFlag		= ''	; // 要保人顯示旗標 	Y/N
		public $insuredFlag			= ''	; // 被保人顯示旗標 	Y/N
		public $representFlag		= ''	; // 法定代理人顯示旗標 Y/N
	}
?>
