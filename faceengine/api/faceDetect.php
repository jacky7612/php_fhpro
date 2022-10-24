<?php
function wh_log($log_msg)
{
    $log_filename = "./log";
    if (!file_exists($log_filename)) 
    {
        // create directory/folder uploads.
        mkdir($log_filename, 0777, true);
    }
    $log_file_data = $log_filename.'/log_compare' . date('d-M-Y') . '.log';
    // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
    file_put_contents($log_file_data, date("Y-m-d H:i:s")."  ------  ".$log_msg . "\n", FILE_APPEND);
} 
function myguid(){
	return time();
}	
set_time_limit(0); 
shell_exec("sudo /sbin/restorecon -v /var/www/html/faceengine/api/st6facedetect");
$img1 = $_POST['image_file1'];
$data = array();

if($img1 == '')
{
	$data["IsSuccess"]="false";
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_PRETTY_PRINT));					
$log = "erro input file";
//wh_log($log);	
	exit;				
}
//mkdir("./var/", 0777, true);
$id1 = myguid();//uniqid();

$imagefile1 = $id1;


$filename1 = "./tmp/".$id1.".jpg";
$file1 = fopen($filename1,"w");

fwrite($file1,base64_decode($img1));
fclose($file1);

//error_reporting(E_ALL);

$cmd = "./st6facedetect ".$filename1;
//$cmd = "ls";
//$out = array();
$ret = "";
//echo $cmd;
$out = shell_exec($cmd);//, $val);

//var_dump($out);

$log = $cmd;
$log .= " out:".$out;
//wh_log($log);
unlink($filename1);
//unlink($filename2);
if($out == 1)
	$data["IsSuccess"]="true";
else
	$data["IsSuccess"]="false";

header('Content-Type: application/json');
echo (json_encode($data, JSON_PRETTY_PRINT));	
//socket_close($socket);				
exit;		
?>
