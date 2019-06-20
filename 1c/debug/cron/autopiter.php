<pre>
<?
$dir = dirname(dirname(__DIR__));
require dirname($dir) . '/AutopiterClient.php';

$art = isset($_GET['art'])
    ? $_GET['art']
    : 'D4922';

$city = isset($_GET['city'])
? $_GET['city']
: 'msk';
echo $city, "\n";
require $dir . '/' .$city  . '/config.php';


$autopiterClient = new AutopiterClient($config['services']['autopiter.ru'],$stopPhrases, true, $city);
$autopiterArr = $autopiterClient->search($art);

echo '<pre>';
print_r($autopiterClient);
print_r($autopiterArr);


//$autopiterArr = autopiter_search($art, true, $city);
//print_r($autopiterArr);

