<?php
$city = 'spb';
require_once dirname(__DIR__) . '/config.php';

//restart mysqli!
$mysqli = new  mysqli($host, $user, $password, $database);
$mysqli->set_charset("utf8");