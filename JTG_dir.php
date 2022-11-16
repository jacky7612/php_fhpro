<?php
	include("func.php");
	
	global $g_json_path;
	
	echo "create directory :".$g_json_path."<br>";
	try
	{
		if (create_folder($g_json_path))
			echo "succeed!"."<br>";
		else
			echo "failure!"."<br>";
	}
	catch (Exception $e)
	{
		echo "Exception error :".$e->getMessage()."<br>";
	}
?>