<?php
	$glog_pdf_file = "";
	$pdf_path  = $g_pdf_path;
	
	//儲存Pdf
	function wh_pdf($Insurance_no, $Remote_insurance_no, $status_code, $pdf_data)
	{
		global $pdf_path;
		global $glog_pdf_file;
		
		set_pdf_file_name($pdf_path, $Insurance_no, $Remote_insurance_no);
		create_folder($pdf_path);
		if (!file_exists($glog_pdf_file))
		{
			$file = fopen($glog_pdf_file, "w");
			//Decode pdf content
			$pdf_decoded = base64_decode ($pdf_content);
			fwrite ($file, $pdf_decoded);
			fclose($file);
		}
		return $glog_pdf_file;
	}
	function set_pdf_file_name($json_path, $Insurance_no, $status_code, $Remote_insurance_no)
	{
		global $glog_pdf_file;
		
		//$glogfile = "/var/www/html/fhpro/api/uploads/log/".'log_'.date('Y-m-d').'_'.$Insurance_no.'_'.$Remote_insurance_no.'_'.time().'.log';
		$glog_pdf_file = $pdf_path.$Insurance_no.'_'.$Remote_insurance_no.'_'.$status_code.'.json';
	}
?>
