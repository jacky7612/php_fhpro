<?php
	include("func.php");
	
	global $g_download_ios_url;
	
	$CloudToken = isset($_POST['token']) ? $_POST['token'] : '';
	
	$title 	   	= mb_convert_encoding("第一金人壽遠距投保", "UTF-8", "BIG5"); //原始編碼為BIG5轉UTF-8
	$dev_type 	= get_device_type();
	$url 		= "";
	if ($dev_type == "ios") $url = $g_download_ios_url;
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title><?php echo $title ?></title>
		<script>
			function AndroidOpenApp(timeout_url, cloud_token)
			{
				var before = new Date().valueOf();
				setTimeout(function(){
				  var after = new Date().valueOf();
				  if (after - before > 200){ return; }
				  window.location = (timeout_url);
				}, 50);  
				window.location = ("twitter://post?message=hello%20world%23thisisyourhashtag.");
			}

			function iOSOpenApp(timeout_url, cloud_token)
			{
				var before = new Date().valueOf();
				setTimeout(function () {
				  var after = new Date().valueOf();
				  if (after - before > 2000){ return; }
				  window.location = (timeout_url);
				}, 1000);
				window.location = ('twitter://post?message=hello%20world');
				// window.location = 'googletranslate://';
			}
			
			function entry(phone_type, timeout_url, cloud_token)
			{
				if (phone_type == "ios") iOSOpenApp(timeout_url, cloud_token);
			}
		</script>
	</head>

	<body onload='entry("<?php echo $dev_type; ?>", "<?php echo $url; ?>", "<?php echo $CloudToken; ?>")'>
	</body>
</html>