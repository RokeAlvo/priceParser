<pre>
<?
$dir = dirname(dirname(__DIR__));
require dirname($dir) . '/AutopiterClient.php';

$art = isset($_GET['art'])
    ? $_GET['art']
    : '';

$city = isset($_GET['city'])
    ? $_GET['city']
    : 'msk';
echo $city, "\n";
require $dir . '/' .$city  . '/config.php';

$per_step_limit = 60;

$query = $art
    ? 'SELECT * FROM `article` WHERE `article` = "' . $mysqli->real_escape_string($art) . '" AND `portalId` = 1 ORDER BY `updateDate` DESC LIMIT 1'
    : 'SELECT * FROM `article` WHERE `needEval` = 1 AND `portalId` = 1 ORDER BY `updateDate` ASC LIMIT 1';

$res = $mysqli->query($query);

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[$row['id']] = $row;
}

if(!$data) {
    exit('No thing to parse');
}

$autopiterClient = new AutopiterClient($config['services']['autopiter.ru'],$stopPhrases, true, $city);
$autopiterClient->debugEnabled = true;


$empty_limit = ($per_step_limit / 5) - 1;
$empty_counter = 0;

foreach($data as $rowId => $rowItem) {
    if($empty_counter > $empty_limit) {
        $empty_error_message = 'Too much empty responses';
        /*sendMailSimple($empty_error_message);
        die($empty_error_message);*/
    }

    $autopiterArrFltrd = $autopiterClient->search2($rowItem);

    if($art) {
        pr($rowItem, 'Item');
        pr($autopiterArrFltrd, 'Filtered offers');
    }

    if (!count($autopiterArrFltrd)){
        $empty_counter++;
        if(strpbrk($rowItem["queryArticle"], '#()?*!')) {
            $now = (new DateTime)->format(DateTime::ATOM);
            $mysqli->query("UPDATE `article` SET `updateDate` = NOW(), `needEval` = '0', `failProblem` = 'Wrong symbol in article {$now}' WHERE `id` = '".$rowId."'");
        }

        if($autopiterClient->ERROR) {
            echo $error = $autopiterClient->ERROR;
            $mysqli->query("UPDATE `article` SET `updateDate` = NOW(), `needEval` = '0', `failProblem` = '$error' WHERE `id` = '".$rowId."'");

            if(
                false !== mb_strpos($autopiterClient->ERROR, 'auth')
                || false !== mb_strpos($autopiterClient->ERROR, 'блокированы')
            ) {
                sendMailSimple($autopiterClient->ERROR);
                die($autopiterClient->ERROR);
            }
        }

        continue;
    }

    $autopiterFinalArr = [];
    $autopiterCntr = 0;
    foreach ($autopiterArrFltrd ? $autopiterArrFltrd : [] as $mfKey=>$mfItem){
        $autopiterCntr++;
        if ($autopiterCntr == 3){
            break;
        }
        $autopiterFinalArr[$mfKey] = $mfItem;
    }

    //Загрузка в БД

    $PROP = [];
    $priceCntr = 0;
    $article = '';
    foreach ($autopiterFinalArr as $mfnKey=>$mfnItem){
        $priceCntr++;

        $PROP["PRICE".$priceCntr] = str_replace(',', '.', $mfnItem["PRICE"]);

        if($priceCntr == 1){
            $PROP["PROD_NAME"] = $mfnItem["NAME"];
            $PROP["MANUF"] = $mfnItem["MANUFACTURER"];

            $article = $mfnItem["ARTICLE"];
        }
        else {
            $PROP["MANUF1"] = $mfnItem["MANUFACTURER"];
        }
    }

    $PROP = array_map(function($value) {
        global $mysqli;
        return $mysqli->real_escape_string($value);
    }, $PROP);

    $res = $mysqli->query("UPDATE `article` SET `updateDate` = NOW(), "
        . "`price1` = '".$PROP["PRICE1"]."',"
        . "`price2` = '".$PROP["PRICE2"]."',"
        . "`name` = '".$PROP["PROD_NAME"]."',"
        . "`producer` = '".$PROP["MANUF"]."',"
        . "`needEval` = '0',"
        . "`failProblem` = '',"
        . "`evalDate` = NOW() WHERE `id` = '".$rowId."'"
    );


    if($res){
        echo "Element updated, ID: ".$rowId."<br />";
    }
    else {
        echo 'Error: '.$mysqli->error."<br />";
    }

    if($art) {
        pr($autopiterFinalArr, 'Saved offers');
    }
}