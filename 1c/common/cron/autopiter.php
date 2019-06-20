<?
$DOCUMENT_ROOT = dirname(__DIR__, 3);

require_once $DOCUMENT_ROOT.'/init.php';

set_time_limit(110);

$per_step_limit = 160;
$art = '';

$query = "SELECT * FROM `article` WHERE `needEval` = 1 AND `portalId` = 1 ORDER BY `updateDate` ASC LIMIT {$per_step_limit}";
if(isset($_GET['art'])) {
    echo '<pre>';
    $art = $_GET['art'];
    $query = "SELECT * FROM `article` WHERE `article` = '{$art}'  AND `portalId` = 1 ORDER by `id` DESC LIMIT 1";
}

$res = $mysqli->query($query);

if(!$res) {
    die($mysqli->error);
}

$data = array();
while ($row = $res->fetch_assoc()) {
    $data[$row['id']] = $row;
}

if(!$data) {
    exit('Nothing to parse');
}

$autopiterClient = new AutopiterClient($config['services']['autopiter.ru'], $stopPhrases, true, $city);

$empty_limit = ($per_step_limit / 5) - 1;
$empty_counter = 0;

foreach($data as $rowId => $rowItem) {
    if($empty_counter > $empty_limit) {
        $empty_error_message = 'Too much empty responses';
        //sendMailSimple($empty_error_message);
        //die($empty_error_message);
    }

    $autopiterArrFltrd = [];
    $now = (new DateTime)->format(DateTime::ATOM);

    /*$infTime = strtotime($rowItem['updateDate']);
    $timestamp = $infTime
        ? $infTime
        : 0;

    //if older one day not process
    if($timestamp > (time() - 86400)) {
        $mysqli->query("UPDATE `article` SET `needEval` = '0', `failProblem` = 'Old item {$now}' WHERE `id` = '".$rowId."'");
        continue;
    }*/

    $quantity = (int) $rowItem["queryCount"];
    $delivery = (int) $rowItem["supplyDate"];
    $tagsArr = array_filter(array_map('trim', explode(",", $rowItem["keywords"])));

    $autopiterArr = $autopiterClient->search($rowItem["queryArticle"], $tagsArr);

    //print_r($autopiterArr);

    if (!count($autopiterArr['ITEMS'])) {
        $empty_counter++;
        echo $rowItem["queryArticle"], ' ', $autopiterClient->ERROR;
        $mysqli->query("UPDATE `article` SET `updateDate` = NOW() WHERE `id` = '".$rowId."'");	//			 ?? ЧТО ЗА ВЕТКА, что тут делать?
        if(strpbrk($rowItem["queryArticle"], '#()?*!')) {
            $mysqli->query("UPDATE `article` SET `needEval` = '0', `failProblem` = 'Wrong symbol in article {$now}' WHERE `id` = '".$rowId."'");
        }

        if($autopiterClient->ERROR) {
            $error = $autopiterClient->ERROR . ' ' . $now;
            $mysqli->query("UPDATE `article` SET `needEval` = '0', `failProblem` = '$error' WHERE `id` = '".$rowId."'");

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

    $requirements = [];
    $requirements['days'] = null;
    $requirements['quant'] = null;
    $requirements['keywords'] = null;

    //Фильтруем по кол-ву, сроку доставки и ключ. словам
    foreach ($autopiterArr['ITEMS'] as $autopiterKey=>$autopiterItem){
        if ($quantity > 0 && ((int) $autopiterItem["QUANTITY"] < $quantity)) {
            $requirements['quant'] = $requirements['quant'] === null || ((int) $autopiterItem["QUANTITY"] < $requirements['quant'])
                ? (int) $autopiterItem["QUANTITY"]
                : $requirements['quant'];
            continue;
        }
        if ($delivery > 0 && ($delivery < (int) $autopiterItem["DELIVERY_DATE"])) {
            $requirements['days'] = $requirements['days'] === null || ((int) $autopiterItem["DELIVERY_DATE"] < $requirements['days'])
                ? (int) $autopiterItem["DELIVERY_DATE"]
                : $requirements['days'];
            continue;
        }
        if ($tagsArr){
            $pos = false;
            foreach ($tagsArr as $tagKey=>$tagItem){
                if($pos === false){
                    $pos = mb_stripos($autopiterItem["MANUFACTURER"], trim($tagItem));
                }
            }

            if ($pos === false) {
                $requirements['keywords'] = 'Not found';
                continue;
            }
        }

        $autopiterArrFltrd[$autopiterKey] = $autopiterItem;
    }

    if(!$autopiterArrFltrd) {

        $msg = '';
        foreach ($requirements as $requirment => $value) {
            if($value !== null) {
                $msg .= $requirment . '=' . $value . ' ';
            }
        }

        $msg = trim($msg);

        $failProblem = $msg
            ? "No results for our requirements ({$msg}) {$now}"
            : "No results for our requirements {$now}";

        $query = "UPDATE `article` SET `needEval` = '0', `updateDate` = NOW(), `failProblem` = '{$failProblem}' WHERE `id` = '" . $rowId . "'";

        $mysqli->query($query);
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
    #print_r($autopiterFinalArr);
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

    if(!$PROP["PRICE1"]) {
        $mysqli->query("UPDATE `article` SET `needEval` = '0', `updateDate` = NOW(), `failProblem` = 'No price1 {$now}' WHERE `id` = '".$rowId."'");
        continue;
    }

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
        pr($rowItem, 'Item');
        pr($autopiterArr['ITEMS'], 'All prices');
        pr($autopiterArr['ITEMS'], 'Filtered prices');
    }
}
die();