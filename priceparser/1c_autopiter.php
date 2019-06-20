<?
$DOCUMENT_ROOT = dirname(__DIR__);

require_once $DOCUMENT_ROOT.'/1cconfig.php';
require_once $DOCUMENT_ROOT.'/init.php';

set_time_limit(300);

/*$autopiterArr = autopiter_search('3K2NB51030');
print_r($autopiterArr);
die();*/

$res = $mysqli->query('SELECT * FROM `article` WHERE `needEval` != 1  AND `portalId` = 1 ORDER BY `updateDate` ASC LIMIT 40');

$data = array();
while ($row = $res->fetch_assoc()) {
    $data[$row['id']] = $row;
}
//print_r($data);

foreach($data as $rowId => $rowItem) {
    $infTime = strtotime($rowItem['updateDate']);
    $timestamp = $infTime ? $infTime : 0;
    $autopiterArrFltrd = [];

    //if older one day not process
    if($timestamp > (time() - 86400)) {
        $mysqli->query("UPDATE `article` SET `needEval` = '1' WHERE `id` = '".$rowId."'");
        continue;
    }

    $quantity = $rowItem["queryCount"];
    $delivery = $rowItem["supplyDate"];
    $tags = $rowItem["keywords"];

    $autopiterArr = autopiter_search($rowItem["queryArticle"], true);
    print_r($autopiterArr);

    $tagsArr = explode(",", $tags);


    if (count($autopiterArr['ITEMS'])>0){
        foreach ($autopiterArr['ITEMS'] as $autopiterKey=>$autopiterItem){
            if (intval($quantity)>0 && (intval($autopiterItem["QUANTITY"]) < intval($quantity))){
                continue;
            }
            if (intval($delivery)>0 && intval($delivery) < intval($autopiterItem["DELIVERY_DATE"])){
                continue;
            }
            if (strlen($tags)>0){
                $pos = false;
                foreach ($tagsArr as $tagKey=>$tagItem){
                    if($pos === false){
                        $pos = mb_stripos($autopiterItem["MANUFACTURER"], trim($tagItem));
                    }
                }
                if ($pos === false) {
                    continue;
                }
            }
            $autopiterArrFltrd[$autopiterKey] = $autopiterItem;
        }
    } else {
        $mysqli->query("UPDATE `article` SET `updateDate` = NOW() WHERE `id` = '".$rowId."'");	//			 ?? ЧТО ЗА ВЕТКА, что тут делать?
        if(strpbrk($rowItem["queryArticle"], '#()?*!')) {
            $mysqli->query("UPDATE `article` SET `needEval` = '1' WHERE `id` = '".$rowId."'");
        }

        continue;
    }

    $autopiterFinalArr = Array();
    $autopiterCntr = 0;
    foreach ($autopiterArrFltrd ? $autopiterArrFltrd : array() as $mfKey=>$mfItem){
        $autopiterCntr++;
        if ($autopiterCntr == 3){
            break;
        }
        $autopiterFinalArr[$mfKey] = $mfItem;
    }

    //Загрузка в Битрикс

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

    $res = $mysqli->query("UPDATE `article` SET `updateDate` = NOW(), "
        . "`price1` = '".$PROP["PRICE1"]."',"
        . "`price2` = '".$PROP["PRICE2"]."',"
        . "`name` = '".$PROP["PROD_NAME"]."',"
        . "`producer` = '".$PROP["MANUF"]."',"
        . "`needEval` = '1',"
        . "`evalDate` = NOW() WHERE `id` = '".$rowId."'"
    );


    if($res){
        echo "Element updated, ID: ".$rowId."<br />";
    }
    else {
        echo 'Error: '.$mysqli->error."<br />";
    }
}
die();