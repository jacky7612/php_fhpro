<?php
function myguid(){
	return time();
}
set_time_limit(0); 
shell_exec("sudo /sbin/restorecon -v /var/www/html/faceengine/api/st6facespoof");
$img1 = $_POST['image_file1'];

$data = array();

if($img1 == '')
{
	$data["IsSuccess"]="false";
	header('Content-Type: application/json');
	echo (json_encode($data, JSON_PRETTY_PRINT));					
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

$cmd = "./st6facespoof ".$filename1;
//$cmd = "ls";
//$out = array();
$ret = "";
//echo $cmd;
$out = shell_exec($cmd);//, $val);

//var_dump($out);
unlink($filename1);
	
$data["IsSuccess"]="true";
$data["status"]=$out;
header('Content-Type: application/json');
echo (json_encode($data, JSON_PRETTY_PRINT));	
//socket_close($socket);				
exit;		
?>
