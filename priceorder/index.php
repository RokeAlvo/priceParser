<?php
require_once dirname(__DIR__) . '/config.php';
$uploads_dir = $base_path . '/upload/priceorders';

set_time_limit(0);

?>
    <script src="//code.jquery.com/jquery-1.12.4.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            $( "#file" ).change(function() {
                $( "#priceloader" ).submit();
            });
            $( "#process" ).click(function() {
                $( "#priceloader" ).submit();
            });
            $( "#load" ).click(function() {
                $( "#priceloader" ).submit();
            });
        });
    </script>
    <style>
        table.priceloader, table.priceloader tr, table.priceloader td {
            border:1px solid;
        }
    </style>
<?
$query = isset($_REQUEST['q']) ? $mysqli->real_escape_string($_REQUEST['q']) : '' ;
$quantity = isset($_REQUEST['quantity']) ? $mysqli->real_escape_string($_REQUEST['quantity']) : '' ;
$delivery = isset($_REQUEST['delivery']) ? $mysqli->real_escape_string($_REQUEST['delivery']) : '' ;
$tags = isset($_REQUEST['tags']) ? $mysqli->real_escape_string($_REQUEST['tags']) : '' ;

?>
    <form id="priceloader" action="" method="post" enctype="multipart/form-data">
        <table class="data">
            <tbody>
            <tr>
                <td class="sel">csv файл</td>
                <td><input id="file" type="file" name="file"></td>
            </tr>
            </tbody>
        </table>

        <?
        if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
            $tmp_name = $_FILES["file"]["tmp_name"];
            $name = md5($_FILES["file"]["name"] . time()) . '.csv';

            if (move_uploaded_file($tmp_name, $uploads_dir . '/' . $name)){
                echo 'Файл: '.$_FILES["file"]["name"].'<br />';
                echo '<input type="hidden" name="process">';
                echo '<input type="hidden" name="filename" value="'.$name.'">';
                echo '<input id="process" type="button" value="Предпросмотр">';
            }
        }
        ?>
    </form>
<?
$ajax = !empty($_REQUEST['ajax']) ? 1 : 0 ;
$ORDER_ID = !empty($_REQUEST['order']) ? $_REQUEST['order'] : 0 ;
$process = isset($_REQUEST['process']) ? 1 : 0 ;
$load = isset($_REQUEST['load']) ? 1 : 0 ;
$filename = isset($_REQUEST['filename']) ? $mysqli->real_escape_string($_REQUEST['filename']) : '' ;

if ($process == 1){
    //parse csv
    $csv = file_get_contents($uploads_dir . DIRECTORY_SEPARATOR .$filename);
    $csv = iconv('CP1251', 'UTF-8', $csv);

    $row = 0;
    $dataArr = [];

    $data_str = explode("\n", $csv);
    //print_r($data_str);

    foreach ($data_str as $str)
    {
        $row++;

        //Skip headers row
        if ($row == 1) {
            continue;
        }

        $data = explode(';', $str);

        $data[0] = trim($data[0]);
        $data[1] = trim($data[1]);
        $data[4] = str_replace(",", ".", $data[4]);
        $data[5] = str_replace(",", ".", $data[5]);
        $data[6] = str_replace(",", ".", $data[6]);
        $data[7] = str_replace(",", ".", $data[7]);
        $data[8] = str_replace(",", ".", $data[8]);

        $dataArr[] = $data;
    }

    if ($load == 0){
        if (count($dataArr)>0){
            echo '<form action="" method="post" enctype="multipart/form-data">';

            echo '<input type="hidden" name="load">';
            echo '<input type="hidden" name="process">';
            echo '<input type="hidden" name="filename" value="'.$filename.'">';
            echo '<input type="submit" value="Загрузить в базу сайта">';
            echo '</form>';

            echo '<table class="priceloader">';
            echo '<thead>';
            echo '<tr><td>Артикул</td><td>Производитель</td><td>Наличие</td><td>Поставка</td></tr>';
            echo '</thead>';
            $cntr = 0;
            foreach ($dataArr as $key=>$dataItem){
                $cntr++;
                //$dataItem[0] = mb_convert_encoding($dataItem[0], "UTF-8", "WINDOWS-1251");
                //$dataItem[1] = mb_convert_encoding($dataItem[1], "UTF-8", "WINDOWS-1251");

                $dataItem[0] = trim($dataItem[0]);
                $dataItem[1] = trim($dataItem[1], ',');

                if($key > 500) {
                    break;
                }
                echo '<tr>';
                echo '<td>'.$dataItem[0].'</td><td>'.$dataItem[1].'</td><td>'.$dataItem[2].'</td><td>'.$dataItem[3].'</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo 'Total: '.$cntr;
        }
    }
    else {

        $cnt = 0;
        $p = !empty($_REQUEST['p']) ? (int) $_REQUEST['p'] : 1;
        $limit = 20000;
        $step = $limit*$p;

        $count = count($dataArr);


        echo '<div id="result">';
        if($ajax) {
            ob_clean();
        }
        echo '<table class="priceloader">';
        echo '<thead>';
        echo '<tr><td>Артикул</td><td>Портал</td></tr>';
        echo '</thead>';

        $insertData = array();

        //create order record in db
        if($cnt === 0 && empty($orderId)) {
            try {
                $mysqli->query('INSERT INTO `order` SET `date` = NOW()');
                $orderId = $mysqli->insert_id;

            } catch (Exception $e) {
                echo 'Connection failed: ' . $e->getMessage();
            }

        }

        if (empty($orderId)) {
            die('Не передан номер заявки!');
        }

        //process item in list
        foreach ($dataArr as $dataItem) {

            $cnt++;

            if(($cnt <= ($step - $limit)) && $p > 1) {
                continue;
            }

            echo '<tr>';
            echo '<td style="width:250px">'. $cnt . ') '. $dataItem[0].'</td>';
            echo '<td> ';

            $dataItem[0] = trim($dataItem[0]);
            $dataItem[1] = trim($dataItem[1], ',');

            echo '<tr>';
            echo '<td>'.$dataItem[0].'</td>';
            echo '<td>';
            if (!empty($dataItem[0])){
                foreach (Portal::getList() as $portalname => $portalItem){

                    echo $portalname. ' ';
                    $PROP = Array();
                    if(strlen($dataItem[2])>0){
                        if (intval($dataItem[2]) >= 1 && intval($dataItem[2]) < 10){
                            $PROP[87] = Array("VALUE" => 1);
                        }
                        elseif (intval($dataItem[2]) >= 10 && intval($dataItem[2]) < 100){
                            $PROP[87] = Array("VALUE" => 10);
                        }
                        elseif (intval($dataItem[2]) >= 100){
                            $PROP[87] = Array("VALUE" => 100);
                        }
                    }

                    $insertData[] = "('".implode("', '", [
                            $dataItem[0],
                            $dataItem[1],
                            md5($dataItem[0].$dataItem[2].$dataItem[3].$dataItem[1].$portalItem),
                            $portalItem,
                            $orderId,
                            1,
                            $PROP[87]['VALUE'],
                            $dataItem[3],
                            $dataItem[0],
                        ]) . "')";


                }

            }
            echo '</td>';
            echo '</tr>';
        }

        $mysqli->query('INSERT INTO `article` (article,keywords,md5,portalId,orderId,needEval,queryCount,supplyDate,queryArticle) VALUES '.implode(",",$insertData));

        echo '</table>';
        echo 'Total: '.$cnt;
        echo '<p><a href="/local/tools/priceparser/?ORDER_ID=' . $orderId . '">Посмотреть</a></p>';


        if(!$ajax) {
            echo '</div>';
        }

        if($cnt >= $count) {
            unlink($uploads_dir . DIRECTORY_SEPARATOR .$filename);

            echo '
                <script>
                alert("Загрузка завершена!");
                </script>
            ';
        }
    }
}