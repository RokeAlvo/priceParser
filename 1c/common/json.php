<?php
require_once dirname(__DIR__) . '/config.php';
set_time_limit(0);

$filename = 'log.txt';

$fileopen=fopen($filename, "a");
fwrite($fileopen,"Старт, принимаем json \n");
fclose($fileopen);

$json = '';
$input = fopen('php://input', 'r');
$gets = array();
while (!feof($input)) {
	$gets[] = trim(fgets($input));
}
fclose($input);
if(count($gets)) {
	$json = implode('', $gets);
}

$dataArr = json_decode($json, true);

if(count($dataArr) > 0) {
    //Create new order record in DB
	try {
		$mysqli->query('INSERT INTO `order` SET `date` = NOW()');
		$orderId = $mysqli->insert_id;
			
	} catch (Exception $e) {
		echo 'Connection failed: ' . $e->getMessage();
	}
	
	$orderNumber = date("Y-m-d H:i:s");
	$portalItem = Portal::getList()['Autopiter'];
	$insertData = array();
	
	foreach ($dataArr as $arr) {
		
		$arr = array_map('trim',$arr);
		
		$insertData[] = "('".implode("', '",array(
			$arr['art'],
			$arr['keyword'],
			md5($arr['art'] . $arr['keyword'] . $arr['quantity'] . $arr['days']),
			$portalItem,
			$orderId,
			1,
			$arr['quantity'],
			$arr['days'],
			$arr['art'],
	)) . "')";
	}
	
	if(count($insertData) > 0) {
		$mysqli->query('INSERT INTO `article` (article,keywords,md5,portalId,orderId,needEval,queryCount,supplyDate,queryArticle) VALUES '.implode(",",$insertData));
		echo $orderId;
		die();
	}
}
?>
<h1>Пример:</h1>
<?php echo json_encode(array('art'=>"1'001'150'001", 'keyword'=>'GREAT WALL', 'quantity'=>'1', 'days'=>'6'))?>