<?php
$DOCUMENT_ROOT = dirname(__DIR__);
require_once $DOCUMENT_ROOT.'/1cconfig.php';
require_once $DOCUMENT_ROOT . '/PHPMailer/PHPMailerAutoload.php';

set_time_limit(360);
$data = $mysqli->query('SELECT * FROM `order` WHERE `timeFinished` <= 0 AND `timeSent` <= 0 order by timeFinished');

while ($row = $data->fetch_assoc()) {
    foreach(Portal::getList() as $key=>$value) {
        $dataCnt = $mysqli->query('SELECT COUNT(*) as Cnt FROM `article` WHERE `needEval` > 0 AND `article`.`orderId` = '.intval($row["id"]).' AND portalId = '.$value);
        while ($rowCnt = $dataCnt->fetch_assoc()) {
            if($rowCnt['Cnt'] > 0) {
                $dataCnt2 = $mysqli->query('SELECT COUNT(*) as Cnt FROM `article` WHERE `article`.`orderId` = '.intval($row["id"]).' AND portalId = '.$value);
                if($rowCnt2 = $dataCnt2->fetch_assoc()) {
                    if($rowCnt['Cnt'] === $rowCnt2['Cnt']) {
                        $time = time();
                        $mysqli->query("UPDATE `order` SET `timeFinished` = '{$time}' WHERE `id` = '".intval($row["id"])."'");

                    }
                }
            }
        }
    }

}

$data = $mysqli->query('SELECT * FROM `order` WHERE `timeFinished` > 0 AND `timeSent` <= 0 order by timeFinished limit 1');
while ($row = $data->fetch_assoc()) {

    $ORDER_ID = $row["id"];

    $tableRows = [];

    $dataArt = $mysqli->query('SELECT * FROM `article` WHERE `needEval` = 1 AND `article`.`orderId` = '.intval($row["id"]));
    while ($rowArt = $dataArt->fetch_assoc()) {

        if(!empty($rowArt['name']))
            $tableRows[$rowArt['article']]['name'] = $rowArt['name'];
        $tableRows[$rowArt['article']]['producer'] = $rowArt['producer'];
        $tableRows[$rowArt['article']]['keywords'] = $rowArt['keywords'];
        $id = Portal::getNames()[$rowArt['portalId']];

        $arr = array(
            $rowArt['price1'],
            $rowArt['price2']
        );
        $tableRows[$rowArt['article']]['portal'][$id] = $arr;

    }
    if(!count($tableRows)) {
        die('Нет элементов для отображения');
    }

    $csv = "Номер;Название;Производитель;Autopiter (Цена1);Autopiter (Цена2)\n";
    foreach ($tableRows as $article=>$info){

        $csv .= "{$article};{$info['name']};{$info['keywords']}";
        foreach($info['portal'] as $key=>$portal) {
            foreach($portal as $k=>$p) {
                $csv .= ";{$p}";
            }
        }
        $csv .= "\n";
    }

    echo $csv;

    $subject = "EXPOCAR PARSE #{$ORDER_ID}";

    $filename = $DOCUMENT_ROOT . "/priceparser/csv/order{$ORDER_ID}.csv";
    file_put_contents($filename, $csv);

    //send the message, check for errors
    if(sendMail($from, $to, $subject, $filename, $smtp)) {
        $time = time();
        $mysqli->query("UPDATE `order` SET `timeSent` = '{$time}' WHERE `id` = '".intval($row["id"])."'");
    }

    unlink($filename);
}

die();