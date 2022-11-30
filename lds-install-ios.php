<?php
	$title 	   = mb_convert_encoding("第一金人壽遠距投保", "UTF-8", "BIG5"); //原始編碼為BIG5轉UTF-8
	$content01 = mb_convert_encoding("您好，歡迎使用第一金人壽遠距投保，請點選", "UTF-8", "BIG5");
	$content02 = mb_convert_encoding("隱私權政策", "UTF-8", "BIG5");
	$content03 = mb_convert_encoding("瀏覽聲明內容。", "UTF-8", "BIG5");
	$content04 = mb_convert_encoding("點選以下連結安裝", "UTF-8", "BIG5");
	$content05 = mb_convert_encoding("遠距投保APP", "UTF-8", "BIG5");
	$content06 = mb_convert_encoding("安裝 APP", "UTF-8", "BIG5");
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title><?php echo $title ?></title>
	</head>

	<body style="margin: 100px">
		<div>
			<h1 style="font-size:60px; color: dodgerblue;"><?php echo $title ?></h1>
			<div>
				<br>
				<b style="font-size: 42px; color: dimgray;"><?php echo $content01 ?> <a href="https://mposapp.transglobe.com.tw/ota/prod/appprivacy.pdf" target="_blank"><?php echo $content02 ?></a>
					<?php echo $content03 ?></b>
				<br><br>
				<h3 style="font-size:46px;"><?php echo $content04 ?> <u><?php echo $content05 ?></u></h3>
				<p>
				</p>
				<h2 style="font-size:52px;"><a href="https://testflight.apple.com/join/EPgjIv9n"><?php echo $content06 ?></a></h2>            
			</div>
		</div>
	</body>
</html>