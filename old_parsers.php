<?php
function countRequest($portal='', $limit=2000) {

    $filename = date('ymd') . $portal .'.txt';

    $contents = (is_file($filename)) ? file_get_contents($filename) : 0;
    if($contents >= $limit) {
        return 0;
    }

    $contents++;

    file_put_contents($filename, $contents);

    return ($limit - $contents);
}

function exist_auth(&$trycntr = 0) {
    $authorized = false;
    if ($trycntr == 3){
        echo "exist auth error";
        die();
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_COOKIESESSION, true);
    curl_setopt($curl, CURLOPT_COOKIEJAR,  $_SERVER["DOCUMENT_ROOT"]."/include/cookie_exist.txt");
    curl_setopt($curl, CURLOPT_COOKIEFILE, $_SERVER["DOCUMENT_ROOT"]."/include/cookie_exist.txt");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; ru; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3');
    curl_setopt($curl, CURLOPT_URL, 'http://www.exist.ru');
    $html = curl_exec($curl);
    preg_match_all ("|<a(.+)expoparts</a>|iU", $html, $check_auth);
    if (count($check_auth[0]) == 0){
        $post = "login=expoparts&pass=expocar&save=yes&hiddenInputToUpdateATBuffer_CommonToolkitScripts=1";
        curl_setopt($curl, CURLOPT_URL, 'http://www.exist.ru/Profile/Login.aspx?');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        $html = curl_exec($curl);
        $trycntr++;
        preg_match_all ("|<a(.+)expoparts</a>|iU", $html, $check_auth_l);
        if (count($check_auth_l[0]) == 0){
            exist_auth($trycntr);
        }
        else {
            $authorized = true;
        }
    }
    else {
        $authorized = true;
    }

    return $authorized;
}

function exist_search($article){
    if (exist_auth() == true){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_COOKIEJAR,  $_SERVER["DOCUMENT_ROOT"]."/include/cookie_exist.txt");
        curl_setopt($curl, CURLOPT_COOKIEFILE, $_SERVER["DOCUMENT_ROOT"]."/include/cookie_exist.txt");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; ru; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3');

        $postfields = '__EVENTTARGET=ctl00%24ctl00%24b%24b%24ddlPriceLevel&__EVENTARGUMENT=&__LASTFOCUS=&__VIEWSTATE=%2FwEPDwULLTExMjU3NDU2NDcPFgQeAnNkCyonU3lzdGVtLldlYi5VSS5XZWJDb250cm9scy5Tb3J0RGlyZWN0aW9uAB4FU3JjSWQB%2FP8WAmYPZBYCZg9kFgYCAw8WAh4HVmlzaWJsZWdkAgwPZBYCAgEPPCsABQEADxYCHg9TaXRlTWFwUHJvdmlkZXIFF0V4dGVuZGVkU2l0ZU1hcFByb3ZpZGVyZGQCEA9kFgICAg9kFgJmD2QWAmYPZBYEZg9kFgICBQ88KwARAQEQFgAWABYAZAIBD2QWCgIDD2QWBmYPZBYCAgEPFgIeBFRleHQFFNCc0L7RgdC60LLQsCDQuCDQnNCeZAIBD2QWAgIBD2QWAmYPDxYCHgtOYXZpZ2F0ZVVybAUiL2hpbnQvcHJpY2UuYXNweD9waWQ9NTM5MDkxQkEmaWQ9N2RkAgIPZBYCAgEPDxYEHwQFftCh0LDQvdC60YIt0J%2FQtdGC0LXRgNCx0YPRgNCzLCDQl9Cw0L3QtdCy0YHQutC40Lkg0L%2FRgC3Rgiwg0LQuIDY1LCDQui4gNSwg0KLQmiDCq9Cf0LvQsNGC0YTQvtGA0LzQsMK7ICjQvC4g0JvQsNC00L7QttGB0LrQsNGPKR8FBR1%2BL0Fib3V0L2hpbnQvT2ZmaWNlLmFzcHg%2FaWQ9NxYCHgdvbmNsaWNrBTdTaG93VGlwTGF5ZXIodGhpcyxldmVudCx0aGlzLmhyZWYsMzAsMzApOyByZXR1cm4gZmFsc2U7ZAIJDxAPFgQeC18hRGF0YUJvdW5kZx8CZ2QQFQgO0KDQvtC30L3QuNGG0LAQ0JjQvdGC0LXRgNC90LXRggNWSVAI0J7Qv9GCIDEI0J7Qv9GCIDII0J7Qv9GCIDMI0J7Qv9GCIDQI0J7Qv9GCIDUVCAEwATEBMgEzATQBNQE2ATcUKwMIZ2dnZ2dnZ2cWAQIBZAINDxYCHwJnZAIPDxAPFgQeB0NoZWNrZWRnHgdFbmFibGVkaGRkZGQCEw9kFgICAQ8QZA8WA2YCAQICFgMQBQPigqwFAkVVZxAFASQFAlVTZxAFB9Cg0YPQsS4FAlJVZxYBAgJkGAgFHGN0bDAwJGN0bDAwJGIkYiRtdlJlZ2lvblRleHQPD2QCAmQFG2N0bDAwJGN0bDAwJGN1c3RMb2dpbiRjdGwwNA8PZAICZAUWY3RsMDAkY3RsMDAkYiRiJG12TWFpbg8PZAIBZAUVY3RsMDAkY3RsMDAkYiRiJGN0bDA5DzwrAAwBCAIBZAUnY3RsMDAkY3RsMDAkbW9iaWxlZm9vdGVyJGNvdW50ZXJzJGN0bDAzDw9kZmQFHWN0bDAwJGN0bDAwJG1vYmlsZU1lbnUkbXZVc2VyDw9kAgFkBRVjdGwwMCRjdGwwMCRiJGIkY3RsMTAPZ2QFIWN0bDAwJGN0bDAwJGJvdHRvbSRjb3VudGVycyRjdGwwMw8PZGZk&__EVENTVALIDATION=%2FwEWGgKet9S0CwLHitagDQLX5fzOAQLI5fzOAQLJ5fzOAQLK5fzOAQLL5fzOAQLM5fzOAQLN5fzOAQLO5fzOAQKIoeKeBQK68ojtCwLhnd6dBwLRndadBwLUnd6dBwLHw4SyDQLHw4CyDQLHw4yyDQLHw5CyDQLHw5SyDQLHw5yyDQKBnpDPDQKE%2F%2FYrAvX%2B9tIJAqWn45kOAuSV%2FRs%3D&ctl00%24ctl00%24b%24b%24ddlPriceLevel=4&ctl00%24ctl00%24b%24b%24ddlValute=RU&ctl00%24ctl00%24b%24b%24hdnPid=539091BA&ctl00%24ctl00%24b%24b%24hdnSrid=&ctl00%24ctl00%24b%24b%24hdnPcode=&ctl00%24ctl00%24b%24b%24hdnPrdid=&ctl00%24ctl00%24b%24b%24hdnTimer=00%3A00%3A00.1248002';
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);

        curl_setopt($curl, CURLOPT_URL, 'http://www.exist.ru/price.aspx?pcode='.$article);
        $html = curl_exec($curl);
        curl_close($curl);

        echo $html;

        preg_match_all ('|<td style=\"white-space:nowrap;\" rowspan=\"2\" class=\"artMerge\">(.+)</td>|iU', $html, $exist_prodart);
        //$exist_prodart[1][0];
        preg_match_all ('|<td rowspan=\"2\" class=\"artMerge\">(.+)</td>|iU', $html, $exist_prodname);
        //$exist_prodname[1][0];
        preg_match_all ('|<td class=\"artMerge dotted\" rowspan=\"2\"><a[^>]+>(.+)</a></td>|iU', $html, $exist_prodmanuf);
        preg_match_all ('|<td align=\"center\">(.+)</td>|iU', $html, $exist_products1);
        preg_match_all ('|<td align=\"right\" class=\"price\">(.+)</td>|iU', $html, $exist_products2);
        foreach ($exist_products2[1] as $priceKey=>$priceItem){
            $exist_products2[1][$priceKey] = str_replace(Array("&nbsp;", "р."), "", $priceItem);
            //$exist_products2[1][$priceKey] = str_replace(Array(",", "."), "", $exist_products2[1][$priceKey]);
        }
        preg_match_all ('|<td class=\"statis\"><a[^>]+>(.+)</a></td>|iU', $html, $exist_products3);

        $existProdsArr = Array();
        $existPrice = Array();
        $existProdsArr["NAME"] = $exist_prodname[1][0];
        $existProdsArr["ARTICLE"] = $exist_prodart[1][0];
        $existCntr++;
        foreach ($exist_products1 as $prodKey=>$prodItem){
            $existPrice[$prodKey] = $exist_products2[1][$prodKey];
        }
        asort($existPrice);
        $existCntr = 0;
        foreach ($existPrice as $expriceKey=>$expriceItem){
            if(!empty($exist_prodname[1][0])) {
                $existCntr++;
                /*if ($emexCntr == 3){
                    break;
                }*/
                $arResult['ITEMS'][$expriceKey]['NAME'] = cutHTML($exist_prodname[1][0]);
                $arResult['ITEMS'][$expriceKey]['ARTICLE'] = $exist_prodart[1][0];
                $arResult['ITEMS'][$expriceKey]['MANUFACTURER'] = $exist_prodmanuf[1][0];
                $arResult['ITEMS'][$expriceKey]['PRICE'] = $expriceItem;
                $arResult['ITEMS'][$expriceKey]['DELIVERY_DATE'] = $exist_products3[1][$expriceKey];
                $arResult["ITEMS"][$expriceKey]['QUANTITY'] = $exist_products1[1][$expriceKey];
            }
        }
    }
    return $arResult;
}

function emex_auth(&$trycntr = 0) {
    $authorized = false;
    if ($trycntr == 3){
        echo "emex auth error";
        die();
    }
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_COOKIESESSION, true);
    curl_setopt($curl, CURLOPT_COOKIEJAR,  $_SERVER["DOCUMENT_ROOT"]."/include/cookie_emex.txt");
    curl_setopt($curl, CURLOPT_COOKIEFILE, $_SERVER["DOCUMENT_ROOT"]."/include/cookie_emex.txt");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; ru; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3');
    curl_setopt($curl, CURLOPT_URL, 'http://www.emex.ru');
    $html = curl_exec($curl);
    preg_match_all ("|<span(.+)95121</span>|iU", $html, $check_auth);
    if (count($check_auth[0]) == 0){
        $post = "username=95121&password=b28ac357";
        curl_setopt($curl, CURLOPT_URL, 'http://www.emex.ru/Account.mvc/LogOn?');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        $html = curl_exec($curl);
        $trycntr++;
        preg_match_all ("|<span(.+)95121</span>|iU", $html, $check_auth_l);
        if (count($check_auth_l[0]) == 0){
            emex_auth($trycntr);
        }
        else {
            $authorized = true;
        }
    }
    else {
        $authorized = true;
    }

    return $authorized;
}

function emex_search($article, $keywords, $delivery_date, $quantity){
    die();
    if (emex_auth() == true){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_COOKIEJAR,  $_SERVER["DOCUMENT_ROOT"]."/include/cookie_emex.txt");
        curl_setopt($curl, CURLOPT_COOKIEFILE, $_SERVER["DOCUMENT_ROOT"]."/include/cookie_emex.txt");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; ru; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3');

        curl_setopt($curl, CURLOPT_URL, 'http://www.emex.ru/find?Fs.ChkMargin=False&Fs.Margin=0&cbRegions=Москва&sortField=Цена&sortDirection=Desc&dgonumber=&typeClient=rozn&cbAmount=&MakeLogo=&QueryDetail='.$article.'&Fs.CurrencyId=34&shkey.SearchedDetailNum='.$article.'&shkey.CustomGroupKey=-1005&shkey.PGr=Original');
        $html = curl_exec($curl);
        curl_close($curl);
        preg_match_all('|<td class="lt brand"><span class="this">(.*)</span></td>|', $html, $emex_products1);

        preg_match_all('|<td><span class=\'descr-text\'>(.*)</span></td>|', $html, $emex_products2);

        preg_match_all('|<td class="av">(.*)</td>|isU', $html, $emex_products3);
        $availability = array_map('cutHTML', $emex_products3[1]);
        //preg_match_all('|<td class="rt price[^"]+"+[\s]>(.*)</td>|isU', $html, $emex_products4);
        preg_match_all('|<td class="rt price[^>]*?>(.*?)</td>|si', $html, $emex_products4);

        $price = array_map('cutHTML', $emex_products4[1]);

        preg_match_all('|<td class="infoClicker">.+<span class="this">(.*)</span>.+</td>|isU', $html, $emex_products5);
        $deliveryTime = array_map('cutHTML', $emex_products5[1]);
        foreach ($deliveryTime as $deliveryKey=>$deliveryItem){
            $deliveryTime[$deliveryKey] = str_replace(Array(" ", "дн."), "", $deliveryItem);
        }
        preg_match_all('|<td class="lt artic">(.*)</td>|isU', $html, $emex_products6);
        $article = array_map('cutHTML', $emex_products6[1]);
        foreach ($price as $priceKey2=>$priceItem2){
            $price[$priceKey2] = str_replace(Array(" ", "р."), "", $priceItem2);
            $price[$priceKey2]=str_replace(",",'.',$price[$priceKey2]);
            $price[$priceKey2]=preg_replace("/[^x\d|*\.]/","",$price[$priceKey2]);
        }

        asort($price);
        $emexCntr = 0;
        foreach ($price as $priceKey2=>$priceItem2){
            if(!empty($emex_products2[1][0])) {
                $emexCntr++;
                $arResult['ITEMS'][$priceKey2]['NAME'] = cutHTML($emex_products2[1][0]);
                $arResult['ITEMS'][$priceKey2]['ARTICLE'] = $article[0];
                $arResult['ITEMS'][$priceKey2]['MANUFACTURER'] = $emex_products1[1][0];
                $arResult['ITEMS'][$priceKey2]['PRICE'] = $priceItem2;
                $arResult['ITEMS'][$priceKey2]['DELIVERY_DATE'] = $deliveryTime[$priceKey2];
                $arResult['ITEMS'][$priceKey2]['QUANTITY'] = $availability[$priceKey2];
            }
        }
    }
    return $arResult;
}

//Price parser
function mikado_search($article, $keywords, $delivery_date, $quantity) {

    $arResult = Array();


    if(countRequest('mikado', 2000) <=0 ) {
        return $arResult;
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_COOKIESESSION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 0);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; ru; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3');
    curl_setopt($curl, CURLOPT_URL, 'http://www.mikado-parts.ru/ws/service.asmx/Code_Search?Search_Code='.urlencode($article).'&ClientID=8541&Password=bmwc320tt');
    $html = curl_exec($curl);
    curl_close($curl);
    preg_match_all('|<ProducerCode>(.*)</ProducerCode>|', $html, $mikado_article);
    preg_match_all('|<ProducerBrand>(.*)</ProducerBrand>|', $html, $mikado_manufacturer);
    preg_match_all('|<Name>(.*)</Name>|', $html, $mikado_name);
    preg_match_all('|<PriceRUR>(.*)</PriceRUR>|', $html, $mikado_price);
    preg_match_all('|<Srock>(.*)</Srock>|', $html, $mikado_srock);

    //$arResult["REQUEST"]["ARTICLE"] = $mikado_article[1][0];
    //$arResult["REQUEST"]["NAME"] = $mikado_name[1][0];

    //$arResult["ITEMS"]["MIKADO"][0] = Array("PRICE"=>$mikado_price[1][0], "TIME"=>$srock[1][0]);

    //$arResult['RES'] = objectToArray($res);

    foreach ($mikado_article[1] as $key=>$item){
        if($mikado_name[1][$key]) {
            $arResult['ITEMS'][$key]['ARTICLE'] = $item;
            $arResult['ITEMS'][$key]['NAME'] = $mikado_name[1][$key];
            $arResult['ITEMS'][$key]['PRICE'] = $mikado_price[1][$key];
            $arResult['ITEMS'][$key]['DELIVERY_DATE'] = str_replace(Array(" ", "дн."), "", $mikado_srock[1][$key]);
            $arResult['ITEMS'][$key]['MANUFACTURER'] = $mikado_manufacturer[1][$key];
            $arResult["ITEMS"][$key]['QUANTITY'] = "Y";
        }
    }

    return $arResult;
}

function autopiter_search($article, $_1c=false, $city = 'spb') {
    global $stopPhrases;
    global $config;
    $localConfig = $config['services']['autopiter.ru'];

    $accounts = $localConfig['accounts'];
    $apiRequestLimit = $localConfig['requestLimit'];

    $portalLimitFile = ($_1c ? '1c_' : '') . 'autopiter' . ($city === 'spb' ? '' : '_' . $city);
    if(countRequest($portalLimitFile, $apiRequestLimit) <=0 ) {
        return array();
    }

    $arResult2['ITEMS'] = [];


    $client2 = new SoapClient("http://service.autopiter.ru/price.asmx?WSDL");

    $params2_1 = Array(
        "IsAuthorization" => ""
    );
    $res2_1 = $client2->IsAuthorization($params2_1);

    $res2_1 = objectToArray($res2_1);

    if ($res2_1["IsAuthorizationResult"] == false){
        $auth = $accounts['users'][0];

        if($_1c) {
            $oneC = $accounts['oneC'];
            $auth = $oneC['spb'];
            if(isset($oneC[$city])) {
                $auth = $oneC[$city];
            }
        }
        $client2->Authorization($auth);
    }

    //print_r($auth);

    //$article = '101800613251';
    //$article = 'BK4183716000';
    #var_dump($article);
    $params = Array(
        "ShortNumberDetail"	=> $article,
    );
    //var_dump($params);
    try{
        $res = $client2->FindCatalog($params);
    } catch (SoapFault $fault) {
        echo $fault->getMessage();
        return [];
    }



    $items = array();

    if(is_array($res->FindCatalogResult->SearchedTheCatalog)) {
        $items = $res->FindCatalogResult->SearchedTheCatalog;
    }
    else {
        $items[] = $res->FindCatalogResult->SearchedTheCatalog;
    }


    $priceSort = array();


    foreach($items as $item) {

        #var_dump($item);
        $article = $item->id;

        if(empty($item->id) || uStristr($item->NameDetail, $stopPhrases)) {

            continue;
        }

        $params2_3 = Array(
            "ID"	=> $article,
            "FormatCurrency" 	=> "РУБ",
            "SearchCross"		=> 0
        );

        //var_dump($params2_3);

        try {
            $res2_3 = $client2->GetPriceId($params2_3);

        } catch (SoapFault $fault) {
            $params2_3 = Array(
                "ID"	=> $article,
                "FormatCurrency" 	=> "РУБ",
                "SearchCross"		=> 1
            );
            #var_dump($params2_3);
            $res2_3 = $client2->GetPriceId($params2_3);
        }

        if(countRequest($portalLimitFile, $apiRequestLimit) <=0 ) {
            return array();
        }


        //print_r(objectToArray($res2_3));

        $arResult['RES2'] = objectToArray($res2_3);
        #var_dump($arResult['RES2']);

        //var_dump(22222, $arResult['RES2'], objectToArray($res2_3), 1111111);

        #var_dump($arResult['RES2']['GetPriceIdResult']['BasePriceForClient']);
        if(is_array($arResult['RES2']['GetPriceIdResult']['BasePriceForClient'])
            && is_array((reset($arResult['RES2']['GetPriceIdResult']['BasePriceForClient'])))) {


            foreach ($arResult['RES2']['GetPriceIdResult']['BasePriceForClient'] as $key => $row) {
                if(count($row) && isset($row['SalePrice']) && !(stristr($row['CitySupply'], 'уценка')) && !uStristr($row['NameRus'], $stopPhrases)) {
                    $priceSort[$article . '-'  . $key]  = $row['SalePrice'];

                    $arResult['ITEMS'][$article . '-'  .  $key]['ARTICLE'] = $row['Number'];
                    $arResult['ITEMS'][$article . '-'  .  $key]['NAME'] = $row['NameRus'];
                    $arResult['ITEMS'][$article . '-'  .  $key]['PRICE'] = $row['SalePrice'];
                    $arResult['ITEMS'][$article . '-'  .  $key]['DELIVERY_DATE'] = $row['NumberOfDaysSupply'];
                    $arResult['ITEMS'][$article . '-'  .  $key]['MANUFACTURER'] = $row['NameOfCatalog'];
                    $arResult["ITEMS"][$article . '-'  .  $key]['QUANTITY'] = $row['NumberOfAvailable'];
                    $arResult["ITEMS"][$article . '-'  .  $key]['CitySupply'] = $row['CitySupply'];
                }
            }

        }
        else {
            if(isset($arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['SalePrice'])  && !(stristr($arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['CitySupply'], 'уценка'))  && !uStristr($arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['NameRus'], $stopPhrases) ) {
                $priceSort[$article]  = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['SalePrice'];
                $arResult['ITEMS'][$article]['ARTICLE'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['Number'];
                $arResult['ITEMS'][$article]['NAME'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['NameRus'];
                $arResult['ITEMS'][$article]['PRICE'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['SalePrice'];
                $arResult['ITEMS'][$article]['DELIVERY_DATE'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['NumberOfDaysSupply'];
                $arResult['ITEMS'][$article]['MANUFACTURER'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['NameOfCatalog'];
                $arResult["ITEMS"][$article]['QUANTITY'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['NumberOfAvailable'];
                $arResult["ITEMS"][$article]['CitySupply'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['CitySupply'];

            }
        }

    }


    asort($priceSort);

    //print_r($priceSort);
    //print_r($arResult['ITEMS']);


    foreach ($priceSort as $priceKey=>$priceItem){
        $arResult2['ITEMS'][$priceKey] = $arResult['ITEMS'][$priceKey];
    }

    //print_r($arResult['ITEMS']);


    return $arResult2;
}

function spbparts_search($article){

    if(countRequest('spbparts', 15000) <=0 ) {
        return array();
    }

    $arResult2['ITEMS'] = array();


    $client = new SoapClient("http://www.spb-part.ru/webservice/search.php?wsdl");

    $params = Array(
        "login" => "expocar",
        "password" => "3340334Q",
        "number"	=> $article,
        "findSubstitutes"	=> 1,
    );

    var_dump($client);
    $res = $client->findDetail($params);
    var_dump($res);

    die();

    $items = array();

    if(is_array($res->FindCatalogResult->SearchedTheCatalog)) {
        $items = $res->FindCatalogResult->SearchedTheCatalog;
    }
    else {
        $items[] = $res->FindCatalogResult->SearchedTheCatalog;
    }


    $priceSort = array();


    foreach($items as $item) {

        var_dump($item);
        $article = $item->id;

        if(empty($item->id)) {
            continue;
        }

        $params2_3 = Array(
            "ID"	=> $article,
            "FormatCurrency" 	=> "РУБ",
            "SearchCross"		=> 0
        );

        //var_dump($params2_3);

        try {
            $res2_3 = $client2->GetPriceId($params2_3);

        } catch (SoapFault $fault) {
            $params2_3 = Array(
                "ID"	=> $article,
                "FormatCurrency" 	=> "РУБ",
                "SearchCross"		=> 1
            );
            var_dump($params2_3);
            $res2_3 = $client2->GetPriceId($params2_3);
        }

        if(countRequest('autopiter', 15000) <=0 ) {
            return array();
        }

        //print_r(objectToArray($res2_3));

        $arResult['RES2'] = objectToArray($res2_3);


        //var_dump(22222, $arResult['RES2'], objectToArray($res2_3), 1111111);

        if(is_array((reset($arResult['RES2']['GetPriceIdResult']['BasePriceForClient'])))) {


            foreach ($arResult['RES2']['GetPriceIdResult']['BasePriceForClient'] as $key => $row) {
                if(count($row) && isset($row['SalePrice'])) {
                    $priceSort[$article . '-'  . $key]  = $row['SalePrice'];

                    $arResult['ITEMS'][$article . '-'  .  $key]['ARTICLE'] = $row['Number'];
                    $arResult['ITEMS'][$article . '-'  .  $key]['NAME'] = $row['NameRus'];
                    $arResult['ITEMS'][$article . '-'  .  $key]['PRICE'] = $row['SalePrice'];
                    $arResult['ITEMS'][$article . '-'  .  $key]['DELIVERY_DATE'] = $row['NumberOfDaysSupply'];
                    $arResult['ITEMS'][$article . '-'  .  $key]['MANUFACTURER'] = $row['NameOfCatalog'];
                    $arResult["ITEMS"][$article . '-'  .  $key]['QUANTITY'] = $row['NumberOfAvailable'];

                }
            }

        }
        else {
            if(isset($arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['SalePrice'])) {
                $priceSort[$article]  = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['SalePrice'];
                $arResult['ITEMS'][$article]['ARTICLE'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['Number'];
                $arResult['ITEMS'][$article]['NAME'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['NameRus'];
                $arResult['ITEMS'][$article]['PRICE'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['SalePrice'];
                $arResult['ITEMS'][$article]['DELIVERY_DATE'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['NumberOfDaysSupply'];
                $arResult['ITEMS'][$article]['MANUFACTURER'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['NameOfCatalog'];
                $arResult["ITEMS"][$article]['QUANTITY'] = $arResult['RES2']['GetPriceIdResult']['BasePriceForClient']['NumberOfAvailable'];

            }
        }

    }

    asort($priceSort);

    //print_r($priceSort);
    //print_r($arResult['ITEMS']);


    foreach ($priceSort as $priceKey=>$priceItem){
        $arResult2['ITEMS'][$priceKey] = $arResult['ITEMS'][$priceKey];
    }

    //print_r($arResult['ITEMS']);
    //print_r($arResult2);

    return $arResult2;
}