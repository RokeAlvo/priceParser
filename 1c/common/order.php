<?php
set_time_limit(360);

//Удаляем заявку по желанию юзера
if(!empty($_REQUEST['delete'])) {
	$order_id = (int) $_REQUEST['delete'];
	$mysqli->query('DELETE FROM `order` WHERE id=' . $order_id);
	$mysqli->query('DELETE FROM `article` WHERE orderId=' . $order_id);
}

//Удаляем старые заявки
if(empty($_REQUEST['ORDER_ID'])) {
	$mysqli->query('DELETE FROM `order` WHERE date <= DATE_SUB(NOW(), INTERVAL 14 DAY)');
	$mysqli->query('DELETE FROM `article` WHERE updateDate <= DATE_SUB(NOW(), INTERVAL 14 DAY)');
}

$data = $mysqli->query('SELECT * FROM `order` ORDER BY `id` DESC');
?>
    <style>
        table.priceloader, table.priceloader tr, table.priceloader td {
            border:1px solid;
        }
        table.priceloaderorder, table.priceloaderorder tr, table.priceloaderorder td {
            border:1px solid;
            margin:0;
            padding:0;
            font-size: 12px;
        }
    </style>

    <table class="priceloader">
        <thead>
        <tr><td>Номер</td><td>Дата</td><td>Готовность</td><td>Отправлен на EMAIL</td></tr>
        </thead>

        <?php
        while ($row = $data->fetch_assoc()) {
            echo '<tr>';
            echo '<td><a href="?ORDER_ID='.$row["id"].'">'.$row["id"].'</a></td><td>'.$row["date"].'</td><td>'.($row["timeFinished"] ? date('m.d.y H:i', $row["timeFinished"]) : 'Нет').'</td><td>'.($row["timeSent"] ? date('m.d.y H:i', $row["timeSent"]): 'Нет').'</td>';
            echo '</tr>';
        }
        ?>
    </table>

<?
if(!empty($_REQUEST['ORDER_ID'])) {

    $_REQUEST['ORDER_ID'] = intVal($_REQUEST['ORDER_ID']);
    $prodIDs = array();
    $bxItemArr = array();
    $counts = array();
    $countsResult = array();

    //Массив заказов

    foreach(Portal::getList() as $key=>$value) {
        $data = $mysqli->query('SELECT COUNT(*) as Cnt FROM `article` WHERE `article`.`orderId` = '.intval($_REQUEST['ORDER_ID']).' AND portalId = '.$value);
        while ($row = $data->fetch_assoc()) {
            if($row['Cnt'] > 0)  {
                $counts[$key]=$row['Cnt'];
            }
        }
    }

    foreach(Portal::getList() as $key=>$value) {
        $data = $mysqli->query('SELECT COUNT(*) as Cnt FROM `article` WHERE `needEval` = 0 AND `article`.`orderId` = '.intval($_REQUEST['ORDER_ID']).' AND portalId = '.$value);
        while ($row = $data->fetch_assoc()) {
            if($row['Cnt'] > 0)  {
                $countsResult[$key]=$row['Cnt'];
            }
        }
    }

    if($countsResult['Autopiter'] < $counts['Autopiter']) {
        echo $countsResult['Autopiter'];
        echo ' из ';
        echo $counts['Autopiter'];
    }

    $tableRows = [];

    $data = $mysqli->query('SELECT * FROM `article` WHERE `needEval` = 0 AND `article`.`orderId` = '.intval($_REQUEST['ORDER_ID']));
    while ($row = $data->fetch_assoc()) {

        if(!empty($row['name']))
            $tableRows[$row['article']]['name'] = $row['name'];
        $tableRows[$row['article']]['producer'] = $row['producer'];
        $tableRows[$row['article']]['keywords'] = $row['keywords'];
        $id = Portal::getNames()[$row['portalId']];

        $arr = array(
            $row['price1'],
            $row['price2']
        );
        $tableRows[$row['article']]['portal'][$id] = $arr;

    }
    if(!count($tableRows)) {
        die('Нет элементов для отображения');
    }

    foreach ($counts as $name=>$count) {
        echo "<p> {$name}: '{$countsResult[$name]}' из {$count}</p>";
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

    ob_clean();

    header("Content-type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename={$_REQUEST['ORDER_ID']}_parser.csv");
    header("Pragma: no-cache");
    header("Expires: 0");


    echo $csv;
}