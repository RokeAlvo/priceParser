<?php
require_once __DIR__ . '/config.php';
set_time_limit(360);
$portals = Portal::getList();
$portalsName = $portals = Portal::getNames();
?>
    <p class="b-upload-new">
        <a href="priceorder/">Загрузить новую заявку</a>
    </p>

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

<?
echo '<table class="priceloader">';
echo '<thead>';
echo '<tr><td>Номер</td><td>Дата</td></tr>';
echo '</thead>';
//Массив заказов


//Удаляем старые заявки
if(empty($_REQUEST['ORDER_ID'])) {
    $mysqli->query('DELETE FROM `order` WHERE date <= DATE_SUB(NOW(), INTERVAL 7 DAY)');
    $mysqli->query('DELETE FROM `article` WHERE updateDate <= DATE_SUB(NOW(), INTERVAL 7 DAY)');
}

if(!empty($_REQUEST['delete'])) {
    $order_id = (int) $_REQUEST['delete'];
	$mysqli->query('DELETE FROM `order` WHERE id=' . $order_id);
	$mysqli->query('DELETE FROM `article` WHERE orderId=' . $order_id);
}

$data = $mysqli->query('SELECT * FROM `order` ORDER BY `id` DESC');

while ($row = $data->fetch_assoc()) {
    echo '<tr>';
    echo '<td><a href="?ORDER_ID='.$row["id"].'">'.$row["id"].'</a></td><td>'.$row["date"].'</td>';
    echo '</tr>';
}

echo '</table>';

if(!empty($_REQUEST['ORDER_ID'])) {

    $errors_counter = 0;
    $prodIDs = array();
    $bxItemArr = array();
    $counts = array();
    $countsResult = array();

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

    $tableRows = [];

    //Select items for order
    $query = 'SELECT * FROM `article` WHERE `needEval` = 0 AND `article`.`orderId` = '.intval($_REQUEST['ORDER_ID']);

    $data = $mysqli->query($query);
    while ($row = $data->fetch_assoc()) {

        if(!empty($row['name']))
            $tableRows[$row['article']]['name'] = $row['name'];

        $tableRows[$row['article']]['producer'] = $row['producer'];
        $tableRows[$row['article']]['keywords'] = $row['keywords'];
        $tableRows[$row['article']]['failProblem'] = $row['failProblem'];
        if($row['failProblem']) {
            $errors_counter++;
        }
        $id = $portalsName[$row['portalId']];

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

    echo 'Ошибок ', $errors_counter, '<br>';

    echo '<table class="priceloaderorder" cellspacing="0" cellpadding="0">';
    echo '<thead>';
    echo '<tr>'
        . '<td>Номер</td>'
        . '<td style="width:300px">Название</td>'
        . '<td>Производитель</td>'
        . '<td>Autopiter (Цена1)</td>'
        . '<td>Autopiter (Цена2)</td>'
        . '<td>Problem</td>'
        . '</tr>';
    echo '</thead>';
    foreach ($tableRows as $article=>$info){
        $tags = $info['keywords'];
        echo '<tr>';
        echo '<td>'.$article.'</td>'
            . '<td>'.$info['name'].'</td>';
        echo '<td>'.$tags.'</td>';
        foreach($info['portal'] as $key=>$portal) {
            foreach($portal as $k=>$p) {
                echo '<td>'.$p.'</td>';
            }
        }
        echo '<td>'.$info['failProblem'].'</td>';
        echo '</tr>';
    }
    echo '</table>';
}


?>