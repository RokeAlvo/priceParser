<?php
//require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/services/autopiter.php';
//global $config, $stopPhrases;
$config = [
    'services' => [
        'autopiter.ru' => [
            'accounts' => [
                'users' => [
                    [
                        "UserID" => "56839",
                        "Password" => "910271101",
                        "Save"	=> true
                    ]
                ],
                'oneC' => [
                    'spb' => [
                        "UserID" => "175664",
                        "Password" => "n6JG2p",
                        "Save"	=> true
                    ],
                    'msk' => [
                        "UserID" => "340840",
                        "Password" => "316497",
                        "Save"	=> true
                    ],
                ],
            ],
            'requestLimit' => 70000
        ]
    ]
];

$stopPhrases = array('дефект', 'не оригинал', 'брак', 'царапин', 'вмятин', 'помята', 'погнут', 'потертость', 'сломан', 'трещин', 'загнут', 'поврежден', 'деформация', 'нарушен','замят', 'некомплект','надлом','залом', 'уценка','cкол','отломлен','загиб');

setlocale(LC_ALL, "ru_RU");

$smtp = [
    'Host' => 'vip1.hosting.reg.ru',
    'Port' => 465,
    //'SMTPDebug' => 2,
    'SMTPSecure' => 'ssl', //with 'tls' reg.ru has bug
    'SMTPAuth' => true,
    'Username' => "parser@expocarshop.ru",
    'Password' => "t4u9gFAw"
];

//Db config
$user = 'u0309008_default';
$password = 'Ax_7romd';
$database = 'u0309008_parser';
$host = 'localhost';

//Mail config
$to = [
    [
        'address' => '1c@expocar.biz',
        'name' => 'Denis'
    ]
    /*, [
        'address' => 'rhino2030@ya.ru',
        'name' => 'Rinat'
    ]*/
];

$from = [
    'address' => 'parser@expocarshop.ru',
    'name' => 'Expocar Parse Service'
];

$subject = "EXPOCAR PARSE";

//Php config
#error_reporting(E_ALL^E_NOTICE);
#ini_set('display_errors',1);

//Start headers
header ("Content-Type: text/html; charset=UTF-8");

$base_path = __DIR__;

require $base_path . '/init.php';

//Start mysql client
$mysqli = new mysqli($host, $user, $password, $database);
$mysqli->set_charset("utf8");

class Portal {
    public static function getList() {
        return Array(
            #"Mikado" => 27,
            "Autopiter" => 1,
            #"Exist" => 29,
            #"Emex" => 30,
            //"Spbparts" => 2,
        );
    }

    public static function getNames() {
        return array(
            1 => "Autopiter",
            //2 => "Spbparts"
        );
    }
}

/**
 * @deprecated use Portal::getList();
 */
$portals = Portal::getList();
/**
 * @deprecated use Portal::getNames();
 */
$portalNames = Portal::getNames();