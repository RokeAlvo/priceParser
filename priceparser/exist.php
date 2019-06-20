<?
$_SERVER['DOCUMENT_ROOT'] = '/home/projects/0000/expocar/www/';
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

error_reporting(E_ALL);
ini_set('display_errors',1);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require_once $DOCUMENT_ROOT.'config.php';
require_once $DOCUMENT_ROOT.'init.php';

#require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
set_time_limit(300);

$itemsrArr = exist_search('S18D-2915010');
//print_r($itemsrArr);
die();
?>
<?
#if(CModule::IncludeModule("iblock") && CModule::IncludeModule("sale") && CModule::IncludeModule("catalog"))
#{
	
	#$el = new CIBlockElement;
	
	
	#$site_format = CSite::GetDateFormat("SHORT");
	#$php_format = $DB->DateFormatToPHP($site_format);
	//Элементы заявок
	/*$elemsAPCache = new CPHPCache();
	$elemsCache_id = "priceElems_autopiter_ALL";
	$bxElemArr = null;
	$elemsCache_path = '/';
	$arParams["CACHE_TIME"] = *//*5*60*//*0;
	if ($arParams["CACHE_TIME"] > 0 && $elemsAPCache->InitCache($arParams["CACHE_TIME"], $elemsCache_id, $elemsCache_path))
	{
		$elemsRes = $elemsAPCache->GetVars();
		if (is_array($elemsRes["bxElemArr"]) && (count($elemsRes["bxElemArr"]) > 0)){
			$bxElemArr = $elemsRes["bxElemArr"];
		}
	}*/
	$res = $mysqli->query('SELECT * FROM `article` WHERE `needEval` = 31 AND `portalId` = 1 ORDER BY `updateDate` ASC LIMIT 30');
	
	$data = array();
	while ($row = $res->fetch_assoc()) {
	    $data[$row['id']] = $row;		
	}
	print_r($data);
	
	if (!is_array($bxElemArr))
	{
		/*$arElemSelect = Array("XML_ID", "ID", "NAME", "PROPERTY_ORDER_ID", "PROPERTY_PRICE_DATE", "PROEPRTY_MANUF", "PROPERTY_TAGS", "PROPERTY_QUANTITY", "PROPERTY_DELIVERY_DATE", "PROPERTY_QUERY_ARTICLE", "PROPERTY_QUERY_QUANTITY", "PROPERTY_MD5_STRING", "PROPERTY_QUERY_ARTICLE", "PROPERTY_MD5_STRING");
		$arElemFilter = Array("IBLOCK_ID"=>10, "PROPERTY_PARSED_VALUE"=>"Не обработан", "PROPERTY_PORTAL_VALUE"=>"Autopiter");
		$rsElems = CIBlockElement::GetList(Array("TIMESTAMP_X"=>"ASC"), $arElemFilter, false, Array("nPageSize"=>30), $arElemSelect);*/
		/*while ($arElem = $rsElems->GetNext())
		{
			$bxElemArr[$arElem["ID"]] = $arElem;
		}*/
		/*if ($arParams["CACHE_TIME"] > 0)
		{
			$elemsAPCache->StartDataCache($arParams["CACHE_TIME"], $elemsCache_id, $elemsCache_path);
			$elemsAPCache->EndDataCache(array("bxElemArr"=>$bxElemArr));
		}*/
	}
	

	foreach($data as $rowId=>$rowItem) {			
		$infTime = strtotime($rowItem['updateDate']);
		$timestamp = $infTime ? $infTime : 0;
		$autopiterArrFltrd = array();
		
		
		if($timestamp > (time() - 86400)) {			
			
			$mysqli->query("UPDATE `article` SET `needEval` = '32' WHERE `id` = '".$rowId."'");
		} else {			
			$quantity = $rowItem["queryCount"];
			$delivery = $rowItem["supplyDate"];
			$tags = $rowItem["keywords"];
			
			$autopiterArr = autopiter_search($rowItem["queryArticle"]);
			
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
				$PRODUCT_ID = $rowId;
				
				$mysqli->query("UPDATE `article` SET `updateDate` = NOW() WHERE `id` = '".$rowId."'");	//			 ?? ЧТО ЗА ВЕТКА, что тут делать?
				if(strpbrk($rowItem["queryArticle"], '#()?*!')) {
					$mysqli->query("UPDATE `article` SET `needEval` = '32' WHERE `id` = '".$rowId."'");					
				}
				
				continue;
			}

			/*if(count($autopiterArrFltrd) == 0){
				foreach ($autopiterArr['ITEMS'] as $autopiterKey=>$autopiterItem){
					if (intval($quantity)>0 && (intval($autopiterItem["QUANTITY"]) < intval($quantity))){
						continue;
					}
					if (intval($delivery)>0 && intval($delivery) < intval($autopiterItem["DELIVERY_DATE"])){
						continue;
					}
				}
				$autopiterArrFltrd[$autopiterKey] = $autopiterItem;
			}*/

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
			
			$PROP = Array();
			$priceCntr = 0;
			#print_r($autopiterFinalArr);
			$article = '';
			foreach ($autopiterFinalArr as $mfnKey=>$mfnItem){
				$priceCntr++;
				
				$PROP["PRICE".$priceCntr] = $mfnItem["PRICE"];
				
				if($priceCntr == 1){
					$PROP["PROD_NAME"] = $mfnItem["NAME"];
					$PROP["MANUF"] = $mfnItem["MANUFACTURER"];
					
					$article = $mfnItem["ARTICLE"];
				}
				else {
					$PROP["MANUF1"] = $mfnItem["MANUFACTURER"];
				}
			}

			if(strlen($tags)>0){
				$PROP["TAGS"] = $tags;
			}
			if(strlen($quantity)>0){
				$PROP["QUANTITY"] = intval($quantity);
			}
			else{
				$PROP["TAGS"] = $rowItem["keywords"];
			}
			if(intval($delivery)>0){
				$PROP["DELIVERY_DATE"] = intval($delivery);
			}
			#$PROP["MD5_STRING"] = $rowItem["md5"];
			#$PROP["ORDER_ID"] = $rowItem["orderId"];
			#$PROP["QUERY_ARTICLE"] = $rowItem["queryArticle"];
			/*if (intval($rowItem["queryCount"]) >= 1 && intval($rowItem["queryCount"]) < 10){
				$PROP[87] = 1;
			}
			elseif (intval($rowItem["queryCount"]) >= 10 && intval($rowItem["queryCount"]) < 100){
				$PROP[87] = 10;
			}
			elseif (intval($rowItem["queryCount"]) >= 100){
				$PROP[87] = 100;
			}*/
			$PROP["PORTAL"] = $portals['Autopiter'];
			$PROP['needEval'] = 32;
			$PROP["PRICE_DATE"] = date("Y-m-d H:i:s");
			
			#var_dump('((('.iconv("utf-8","windows-1251",iconv("windows-1251","utf-8",$PROP["PROD_NAME"])));
			#echo "<br>____".$PROP["PROD_NAME"]."****".iconv("windows-1252",'windows-1251',$PROP["PROD_NAME"]);
			$res = $mysqli->query("UPDATE `article` SET `updateDate` = NOW(), "
						. "`price1` = '".$PROP["PRICE1"]."',"
						. "`price2` = '".$PROP["PRICE2"]."',"
						. "`name` = '".$PROP["PROD_NAME"]."',"
						. "`producer` = '".$PROP["MANUF"]."',"						
						. "`needEval` = '".$PROP['needEval']."',"
						. "`evalDate` = NOW() WHERE `id` = '".$rowId."'"
						);
			
			/*if(empty($article)) {
				
				
				$arLoadProductArray = Array(
					"XML_ID"		   => $PROP["MD5_STRING"],
					//"MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
					"IBLOCK_ID"      => 10,
					"PROPERTY_VALUES"=> $PROP,
					//"NAME"           => $article,
					"ACTIVE"         => "Y",
				);
			} else {
				$arLoadProductArray = Array(
						"XML_ID"		   => $PROP["MD5_STRING"],
						//"MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
						"IBLOCK_ID"      => 10,
						"PROPERTY_VALUES"=> $PROP,
						//"NAME"           => $article,
						"ACTIVE"         => "Y",
				);
			}*/

			
			#$res = $el->Update($PRODUCT_ID, $arLoadProductArray, false, false, false);
			
			/*$arElemSelect = Array("XML_ID", "ID", "NAME", "PROPERTY_PRICE_DATE", "PROEPRTY_MANUF", "PROPERTY_TAGS", "PROPERTY_QUANTITY", "PROPERTY_DELIVERY_DATE", "PROPERTY_QUERY_ARTICLE", "PROPERTY_QUERY_QUANTITY", "PROPERTY_MD5_STRING", "PROPERTY_QUERY_ARTICLE");
			$arElemFilter = Array("IBLOCK_ID"=>10, "PROPERTY_PARSED_VALUE"=>"Не обработан", "PROPERTY_PORTAL_VALUE"=>"Autopiter", "PROPERTY_MD5_STRING"=>$bxItem["PROPERTY_MD5_STRING"]);
			$rsElems = CIBlockElement::GetList(Array(), $arElemFilter, false, Array("nPageSize"=>20), $arElemSelect);
			while ($arElem = $rsElems->GetNext())
			{
				$el->Update($arElem["ID"], $arLoadProductArray, false, false, false);
			}
			*/
			if($res){
				echo "Element updated, ID: ".$rowId."<br />";
			}
			else {
				echo 'Error: '.$mysqli->error."<br />";
			}
			
		}
		
	}
	die();
	
#}
#require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>