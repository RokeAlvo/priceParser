<?
$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(__DIR__));
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

require_once $DOCUMENT_ROOT.'/config.php';
require_once $DOCUMENT_ROOT.'/init.php';

set_time_limit(300);

$res = $mysqli->query('SELECT * FROM `article` WHERE `needEval` = ' . ITEM_NOT_PROCESSED . ' AND `portalId` = 1 ORDER BY RAND() ASC LIMIT 30');

$data = array();
while ($row = $res->fetch_assoc()) {
    $data[$row['id']] = $row;
}
print_r($data);

$autopiterClient = new AutopiterClient($config['services']['autopiter.ru'], $stopPhrases, false);

foreach($data as $rowId=>$rowItem) {
    $infTime = strtotime($rowItem['updateDate']);

    $timestamp = $infTime ? $infTime : 0;
    $autopiterArrFltrd = array();

    if($timestamp > (time() - 86400)) {
        $mysqli->query("UPDATE `article` SET `needEval` = '" . ITEM_PROCESSED . "' WHERE `id` = '".$rowId."'");
        echo "Удаление старых записей id={$rowId}";
    } else {
        echo "Обработка id={$rowId}";
        $quantity = $rowItem["queryCount"];
        $delivery = $rowItem["supplyDate"];
        $tags = $rowItem["keywords"];

        $autopiterArr = $autopiterClient->search($rowItem["queryArticle"]);
        print_r($autopiterArr);

        $tagsArr = explode(",", $tags);


        if (!count($autopiterArr['ITEMS'])){
            $mysqli->query("UPDATE `article` SET `updateDate` = NOW() WHERE `id` = '".$rowId."'");	//			 ?? ЧТО ЗА ВЕТКА, что тут делать?

            if(strpbrk($rowItem["queryArticle"], '#()?*!')) {
                $mysqli->query("UPDATE `article` SET `needEval` = '" .ITEM_PROCESSED . "' WHERE `id` = '".$rowId."'");
            }

            continue;
        }

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

        if(!$autopiterArrFltrd) {
            $mysqli->query("UPDATE `article` SET `needEval` = '" .ITEM_PROCESSED . "' WHERE `id` = '".$rowId."'");
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

        //Загрузка в БД

        $PROP = Array();
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
            . "`needEval` = '". ITEM_PROCESSED ."',"
            . "`evalDate` = NOW() WHERE `id` = '".$rowId."'"
        );


        if($res){
            echo "Element updated, ID: ".$rowId."<br />";
        }
        else {
            echo 'Error: '.$mysqli->error."<br />";
        }

    }

}
die();