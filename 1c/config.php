<?php
require_once dirname(__DIR__) . '/config.php';

//Db config
$database = isset($city) && $city !== 'spb'
    ? 'u0309008_1cparser_' . $city
    : 'u0309008_1cparser'; //by default spb

//Php config
error_reporting(E_ALL^E_NOTICE);