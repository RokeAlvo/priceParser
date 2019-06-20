<?php
$article = 'BK4183716000';

$client = new SoapClient("http://www.spb-part.ru/webservice/search.php?wsdl");

$params = Array(
		"login" => "expocar",
		"password" => "3340334Q",
		"number"	=> $article,
		"findSubstitutes"	=> 1,
);

var_dump($client);
$res = $client->findDetail('expocar123', 'expocar1234', $article, 1);
var_dump($res);

die();

?>