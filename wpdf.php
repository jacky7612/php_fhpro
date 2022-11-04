<?php
	$glog_pdf_file = "";
	
	
	//儲存Pdf
	function wh_pdf($Insurance_no, $Remote_insurance_no, $pdf_subname, $pdf_data)
	{
		global $glog_pdf_file;
		global $g_pdf_path;
		$pdf_path = $g_pdf_path;
		
		set_pdf_file_name($pdf_path, $Insurance_no, $Remote_insurance_no, $pdf_subname);
		create_folder($pdf_path);
		if (!file_exists($glog_pdf_file))
		{
			$file = fopen($glog_pdf_file, "w");
			//Decode pdf content
			$pdf_decoded = base64_decode($pdf_data);
			fwrite ($file, $pdf_decoded);
			fclose($file);
		}
		return $glog_pdf_file;
	}
	function set_pdf_file_name($pdf_path, $Insurance_no, $Remote_insurance_no, $pdf_subname)
	{
		global $glog_pdf_file;
		//$glogfile = "/var/www/html/fhpro/api/uploads/log/".'log_'.date('Y-m-d').'_'.$Insurance_no.'_'.$Remote_insurance_no.'_'.time().'.log';
		$glog_pdf_file = $pdf_path.$Insurance_no.'_'.$Remote_insurance_no.'_'.$pdf_subname.'.pdf';
	}
?>
