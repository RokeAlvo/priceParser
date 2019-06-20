<?php
class AutopiterClient {
    const URL = 'http://service.autopiter.ru/price.asmx?WSDL';
    private $client;
    private $isOneC;
    private $config;
    private $city;
    private $stopPhrases;
    private $portalName;
    public $debugEnabled;
    public $ERROR;

    public function __construct($config, $stopPhrases, $_1c = false, $city = 'spb')
    {
        $this->client = new SoapClient(self::URL);
        $this->setIsOneC($_1c);
        $this->setCity($city);
        $this->setConfig($config);
        $this->setStopPhrases($stopPhrases);
        $this->portalName = 'autopiter';
        if(!$this->auth()) {
            $this->ERROR = 'Can\'t auth to portal!';
        };
    }

    public function setIsOneC($_1c = true)
    {
        $this->isOneC = $_1c;
    }

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function setStopPhrases($stopPhrases)
    {
        $this->stopPhrases = $stopPhrases;
    }

    public function setCity($city)
    {
        $this->city = $city;
    }

    public function countRequest($portal, $limit = 2000) {
        $portal_city = $this->city === 'spb'
            ? $portal
            : $portal . '_' . $this->city;

        if($this->isOneC) {
            $portal_city = '1c_' . $portal_city;
        }

        $filename = sprintf("%s%s.txt", date('ymd'), $portal_city);

        $contents = (is_file($filename))
            ? file_get_contents($filename)
            : 0;

        if($contents >= $limit) {
            return 0;
        }

        $contents++;

        file_put_contents($filename, $contents);

        return ($limit - $contents);
    }

    public function auth()
    {
        $params = [
            "IsAuthorization" => ""
        ];
        $result = $this->request('IsAuthorization', $params);

        $result = objectToArray($result);

        if ($result["IsAuthorizationResult"]) {
            return true;
        }

        $accounts = $this->config['accounts'];
        $auth = $accounts['users'][0];

        if($this->isOneC) {
            $oneC = $accounts['oneC'];
            $auth = isset($oneC[$this->city])
                ? $oneC[$this->city]
                : $oneC['spb'];
        }

        $this->debug($auth, 'Auth params');

        return $this->request('Authorization', $auth);
    }

    //for production
    public function search($article, $tagsArr = []) {
        $this->ERROR = '';
        $stopPhrases = $this->stopPhrases;

        $requestData = [
            "ShortNumberDetail"	=> $article,
        ];

        $res = $this->request('FindCatalog', $requestData);

        if(!$res->FindCatalogResult || !$res->FindCatalogResult->SearchedTheCatalog) {
            $this->ERROR = 'Empty FindCatalog';
            return [];
        }

        $items = is_array($res->FindCatalogResult->SearchedTheCatalog)
            ? $res->FindCatalogResult->SearchedTheCatalog
            : [$res->FindCatalogResult->SearchedTheCatalog];



        $prices = [];
        $arResult = [];

        $catalogsCount = count($items);

        foreach($items as $item) {
            $this->debug($item, 'Start Process Catalog Item', 'h2');
            if(empty($item->id) || uStristr($item->NameDetail, $stopPhrases)) {
                continue;
            }

            $detail_id = $item->id;

            $priceRequest = [
                "ID"	=> $detail_id,
                "FormatCurrency" 	=> "РУБ",
                "SearchCross"		=> 0
            ];

            $result = $this->request('GetPriceId', $priceRequest);
            if(!$result) {
                $priceRequest["SearchCross"] = 1;
                $result = $this->request('GetPriceId', $priceRequest);
            }

            $arRes = objectToArray($result);

            $this->debug($arRes, 'Converted prices');

            if(!isset($arRes['GetPriceIdResult']['BasePriceForClient']) && $catalogsCount < 2) {
                $this->ERROR = 'Empty GetPriceId response for APdetailID=' . $detail_id;
                return [];
            }

            $basePriceForClient = isset($arRes['GetPriceIdResult']['BasePriceForClient'][0]['SalePrice'])
                ? $arRes['GetPriceIdResult']['BasePriceForClient']
                : [$arRes['GetPriceIdResult']['BasePriceForClient']];

            $this->debug($basePriceForClient, 'Prices for processing');

            foreach ($basePriceForClient as $key => $row) {
                $this->debug($key, 'Цена ' . $row['SalePrice']);
                if(isset($row['SalePrice'])
                    && !stristr($row['CitySupply'], 'уценка')
                    && !uStristr($row['NameRus'], $stopPhrases)
                ) {
                    $data = [];
                    $data['ARTICLE'] = $row['Number'];
                    $data['NAME'] = $row['NameRus'];
                    $data['PRICE'] = $row['SalePrice'];
                    $data['DELIVERY_DATE'] = $row['NumberOfDaysSupply'];
                    $data['MANUFACTURER'] = $row['NameOfCatalog'];
                    $data['QUANTITY'] = $row['NumberOfAvailable'];
                    $data['CitySupply'] = $row['CitySupply'];

                    $arr_key = $article . '-' . $item->idCatalog .'-'  . $key;

                    $prices[$arr_key]  = $row['SalePrice'];
                    $arResult[$arr_key] = $data;
                }
            }
        }

        if(!$prices) {
            $this->ERROR = 'Filtered by stop phrases';
            return [];
        }

        asort($prices);

        $arResult2['ITEMS'] = [];

        foreach ($prices as $priceKey=>$priceItem){
            $arResult2['ITEMS'][$priceKey] = $arResult[$priceKey];
        }

        return $arResult2;
    }

    public function FindCatalog($requestData) {
        $res = $this->request('FindCatalog', $requestData);

        if(!$res->FindCatalogResult || !$res->FindCatalogResult->SearchedTheCatalog) {
            $this->ERROR = 'Empty FindCatalog';
            return false;
        }

        return is_array($res->FindCatalogResult->SearchedTheCatalog)
            ? $res->FindCatalogResult->SearchedTheCatalog
            : [$res->FindCatalogResult->SearchedTheCatalog];
    }

    public function GetPriceId($priceRequest, $catalogsCount = 1) {
        $result = $this->request('GetPriceId', $priceRequest);
        if(!$result) {
            $priceRequest["SearchCross"] = 1;
            $result = $this->request('GetPriceId', $priceRequest);
        }

        $arRes = objectToArray($result);

        $this->debug($arRes, 'Converted prices');

        if(!isset($arRes['GetPriceIdResult']['BasePriceForClient']) && $catalogsCount < 2) {
            $this->ERROR = 'Empty GetPriceId response for APdetailID=' . $priceRequest['ID'];
            return false;
        }

        return isset($arRes['GetPriceIdResult']['BasePriceForClient'][0]['SalePrice'])
            ? $arRes['GetPriceIdResult']['BasePriceForClient']
            : [$arRes['GetPriceIdResult']['BasePriceForClient']];
    }

    public function search2($rowItem) {
        $this->ERROR = '';
        $stopPhrases = $this->stopPhrases;

        $requestData = [
            "ShortNumberDetail"	=> $rowItem['article']
        ];

        $catalogs = $this->FindCatalog($requestData);
        if($catalogs === false && $this->ERROR) {
            return [];
        }

        $catalogsCount = count($catalogs);

        $prices = [];
        $arResult = [];

        $requirements = [];
        $requirements['days'] = null;
        $requirements['quant'] = null;
        $requirements['keywords'] = null;
        $requirements['stopPhrases'] = null;

        $quantity = (int) $rowItem["queryCount"];
        $delivery = (int) $rowItem["supplyDate"];
        $keywords = array_filter(array_map('trim', explode(",", $rowItem["keywords"])));

        foreach($catalogs as $item) {
            $this->debug($item, 'Start Process Catalog Item', 'h2');
            if(empty($item->id)) {
                continue;
            }

            if ($keywords) {
                $pos = false;
                $catalogName = trim($item->Name);
                foreach ($keywords as $keyword) {
                    if($pos === false) {
                        echo $pos = mb_stripos($catalogName, $keyword);
                    }
                }

                if ($pos === false) {
                    $requirements['keywords'] = 'Not found';
                    continue;
                }
            }


            if(uStristr($item->NameDetail, $stopPhrases)) {
                $requirements['stopPhrases'] = true;
                continue;
            }

            $article = $item->id;

            $priceRequest = [
                "ID"	=> $item->id,
                "FormatCurrency" 	=> "РУБ",
                "SearchCross"		=> 0
            ];

            $offers = $this->GetPriceId($priceRequest, $catalogsCount);

            if($offers === false && $this->ERROR) {
                return [];
            }

            $this->debug($offers, 'All offers for processing');

            foreach ($offers as $key => $row) {
                $hasPrice = isset($row['SalePrice']) && $row['SalePrice'] > 0;

                if(!$hasPrice) {
                    continue;
                }

                $isDiscount = stristr($row['CitySupply'], 'уценка');
                if($isDiscount) {
                    continue;
                }

                $hasStopPhrases = uStristr($row['NameRus'], $stopPhrases);
                if($hasStopPhrases) {
                    $requirements['stopPhrases'] = true;
                    continue;
                }


                if ($quantity > 0 && ((int) $row['NumberOfAvailable'] < $quantity)) {
                    $requirements['quant'] = $requirements['quant'] === null || ((int) $row['NumberOfAvailable'] < $requirements['quant'])
                        ? (int) $row['NumberOfAvailable']
                        : $requirements['quant'];
                    continue;
                }

                if ($delivery > 0 && ($delivery < (int) $row['NumberOfDaysSupply'])) {
                    $requirements['days'] = $requirements['days'] === null || ((int) $row['NumberOfDaysSupply'] < $requirements['days'])
                        ? (int) $row['NumberOfDaysSupply']
                        : $requirements['days'];
                    continue;
                }

                /*if ($keywords){
                    $pos = false;
                    foreach ($keywords as $keyword) {
                        if($pos === false) {
                            $pos = mb_stripos($row['NameOfCatalog'], trim($keyword));
                        }
                    }

                    if ($pos === false) {
                        $requirements['keywords'] = 'Not found';
                        continue;
                    }
                }*/

                $data = [];
                $data['ARTICLE'] = $row['Number'];
                $data['NAME'] = $row['NameRus'];
                $data['PRICE'] = $row['SalePrice'];
                $data['DELIVERY_DATE'] = $row['NumberOfDaysSupply'];
                $data['MANUFACTURER'] = $row['NameOfCatalog'];
                $data['QUANTITY'] = $row['NumberOfAvailable'];
                $data['CitySupply'] = $row['CitySupply'];

                $arr_key = $article . '-' . $item->idCatalog .'-'  . $key;

                $prices[$arr_key]  = $row['SalePrice'];
                $arResult[$arr_key] = $data;
            }
        }

        if(!$arResult) {
            $now = (new DateTime)->format(DateTime::ATOM);

            $msg = [];
            foreach ($requirements as $requirment => $value) {
                if($value !== null) {
                    $msg[] = $requirment . '=' . $value;
                }
            }

            $msg = trim(implode(' OR ', array_filter($msg)));

            $this->ERROR = $msg
                ? "No results for our requirements ({$msg}) {$now}"
                : "Filtered by stop phrases {$now}";

            return [];
        }

        asort($prices);

        $arResult2 = [];

        foreach ($prices as $priceKey=>$priceItem){
            $arResult2[$priceKey] = $arResult[$priceKey];
        }

        $this->debug($arResult2, 'Filtered offers');

        return $arResult2;
    }

    public function request($cmd, $params=[]) {
        if($this->countRequest($this->portalName, $this->config['requestLimit']) <=0 ) {
            $this->ERROR = 'Limit earned';
            return null;
        }
        try {
            $result = $this->client->{$cmd}($params);
            $this->debug($result, 'Request ' . $cmd);
            return $result;
        } catch (SoapFault $fault) {
            $this->ERROR = $fault->getMessage();
            return [];
        }
    }

    private function debug($var, $mark = '', $tag = 'h3') {
        if($this->debugEnabled) {
            pr($var, $mark, $tag);
        }
    }
}